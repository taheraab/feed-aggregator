<?php

include_once (dirname(__FILE__)."/../includes/util.php");
include_once "Feed.php";

//Singleton that manages Feeds in the database
class FeedManager {
	
	private static $instance = null;
	private $dbh = null;
	private $purifier = null;
	
	private function __construct() {
		$this->connectToDB();
	}

	function __destruct() {
		$this->dbh = null;
	}


	// Convenience method to connect or reconnect to DB 
	private function connectToDB() {
		try {
			$this->dbh = new PDO(MYSQL_DSN, DB_USERNAME, DB_PASSWORD);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch (PDOException $e) {
    		error_log("FeedAggregator::FeedManager::connectToDB: ".$e->getMessage(), 0);		
		}
	}

	public static function getInstance() {
		if (self::$instance == null) self::$instance = new FeedManager();
		return self::$instance;
		
	}


	
	// Returns all feeds for a given user
	// Returns a list of Feed objects on success, false on failure.
	public function getFeeds($userId) {
   		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("SELECT Feed.* FROM UserFeedRel INNER JOIN Feed ON UserFeedRel.feed_id = Feed.id ".
				"WHERE UserFeedRel.user_id = :userId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			if (!$this->execQuery($stmt, "getFeeds: Get all feeds for given user")) return false;
 			if ($feeds = $stmt->fetchAll(PDO::FETCH_CLASS, "Feed")) {
				// Unescape title, subtitle
				foreach ($feeds as $feed) {
					$feed->title = stripslashes($feed->title);
					$feed->subtitle = stripslashes($feed->subtitle);
					// Get num of unread entry count for each feed
					$stmt = $this->dbh->prepare("SELECT COUNT(*) FROM Entry INNER JOIN UserEntryRel ON ".
						"Entry.id = UserEntryRel.entry_id WHERE Entry.feed_id = :feedId AND UserEntryRel.user_id = :userId AND ".
						"(UserEntryRel.status = 'unread' OR UserEntryRel.status = 'new')");
					$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
					$stmt->bindValue(":feedId", (int)$feed->id, PDO::PARAM_INT);
					if (!$this->execQuery($stmt, "getFeeds: Get the unread entry count")) return false;
					if ($result = $stmt->fetch(PDO::FETCH_NUM)) {
						$feed->numUnreadEntries = $result[0];
					}
				}
				return $feeds;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeeds: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Returns all feed records that were last checked for update before or at the given timestamp
	public function getFeedsToUpdate($timestamp) {
		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("SELECT id, selfLink FROM Feed WHERE lastCheckedAt <= :lastCheckedAt");
			$stmt->bindValue(":lastCheckedAt", $timestamp, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "getFeedsToUpdate: Get all feeds that must be checked for update")) {
 				$feeds = $stmt->fetchAll(PDO::FETCH_CLASS, "Feed");
				return $feeds;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeedsToUpdate: ".$e->getMessage(), 0);
		}
		return false;

	}

	// Delete 'unstarred' and 'read' entries older than given timestamp
	public function deleteOldEntries($timestamp) {
		if ($this->dbh == null) $this->connectToDB();
		try {
		echo $timestamp."\n";
			$stmt = $this->dbh->prepare("DELETE FROM Entry WHERE updated < :timestamp AND id NOT IN ".
				"(SELECT DISTINCT entry_id FROM UserEntryRel WHERE type = 'starred' OR status = 'unread' OR status = 'new')");
			$stmt->bindValue(":timestamp", $timestamp, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "deleteOldEntries: delete read entries older than given time")) {
				return true;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::deleteOldEntries: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Returns requested number of entries with ids less than the lastLoadedEntryId for a given feed.
	// Returns all entries if feedId = 0;
	// Returns List of Entry objects on success, false on failure
	public function getEntries($userId, $feedId, $entryPageSize, $lastLoadedEntryId) {
   		if ($this->dbh == null) $this->connectToDB();
		try {
			$query = "SELECT Entry.*, UserEntryRel.status, UserEntryRel.type FROM Entry INNER JOIN UserEntryRel ON ".
				"Entry.id = UserEntryRel.entry_id WHERE UserEntryRel.user_id = :userId";
			if ((int)$feedId) $query = $query." AND Entry.feed_id = :feedId";
			if ((int)$lastLoadedEntryId) {
				// If this is not the first page
				$query = $query." AND Entry.id < :entryId"; 
			}
			$query = $query." ORDER BY Entry.id DESC LIMIT :entryPageSize";
			$stmt = $this->dbh->prepare($query);
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			if ((int)$feedId) $stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
		 	if ((int)$lastLoadedEntryId) $stmt->bindValue(":entryId", (int)$lastLoadedEntryId, PDO::PARAM_INT);
			$stmt->bindValue(":entryPageSize", (int)$entryPageSize, PDO::PARAM_INT);
			
			if (!$this->execQuery($stmt, "getEntries: Get requested entries for given feed")) 
				return false;
			if ($entries = $stmt->fetchALL(PDO::FETCH_CLASS, "Entry")) {
				// Unescape title and content
				foreach ($entries as $entry) {
					if (!(int)$feedId) {
						// We're in all items, retrive feed title for each entry.
						$stmt = $this->dbh->prepare("SELECT title from Feed WHERE id = :feedId");
						$stmt->bindValue(":feedId", (int)$entry->feed_id, PDO::PARAM_INT);
						if ($this->execQuery($stmt, "getEntries: Get feed title for 'All Items'")) {
							if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
								$entry->feedTitle = stripslashes($row["title"]);
							}
						} 
					}
					$entry->title = stripslashes($entry->title);
					$entry->content = stripslashes($entry->content);	
				}
				return $entries;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getEntries: ".$e->getMessage(), 0);
		}
		return false;
	}
	
	// Adds a feed to the database for the given user
	// Returns true on success, false on failure
	public function createFeed($userId, Feed $feed) {
		if ($this->dbh == null) $this->connectToDB();
		try {
   		    // Check if feed already exists
			$stmt = $this->dbh->prepare("SELECT id FROM Feed WHERE feedId = :feedId");
			$stmt->bindValue(":feedId", $feed->feedId, PDO::PARAM_STR);
			if (!$this->execQuery($stmt, "createFeed: Check if feed exists", false)) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				//Feed already exists in the database
				// Check if it has changed and update the entries
				$feed->id = $row["id"];
				if ($this->updateFeed($userId, $feed)) return $feed->id;
			}else {
				// Insert a new feed, along with its entries
				$this->dbh->beginTransaction();
				$feed->id = $this->insertFeedRec($userId, $feed);
				if ($feed->id) {
					// Insert entries
					if ($this->insertEntryRecs($userId, $feed->id, $feed->entries)) {
						$this->dbh->commit();
						return $feed->id; 
					}
				}
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::createFeed: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Update an existing feed, called when a new subscription is added for a user
	// Returns true on success, false on failure
	public function updateFeed($userId, Feed $feed) {
		if ($this->dbh == null) $this->connectToDB();
		try {	
			$this->dbh->beginTransaction();
			if ($userId) { // if userId is given, then this is a new subscription
				// Update UserFeedRel and UserEntryRel for the given user. This could be a new subscription
				if(!$this->insertUserFeedRelRec($userId, $feed->id, true)) return false; //ignore duplicates
				if(!$this->insertUserEntryRelRecs($userId, $feed->id, true)) return false; //ignore duplicates
			}
			// Check if feed updated value has changed 
			$stmt = $this->dbh->prepare("SELECT id FROM Feed WHERE id = :id AND updated < :updated");
			$stmt->bindValue(":id", (int)$feed->id, PDO::PARAM_INT);
			$stmt->bindValue(":updated", (int)$feed->updated, PDO::PARAM_INT);
			if (!$this->execQuery($stmt, "updateFeed: Check if feed has changed", true)) return false;
			if ($stmt->fetch(PDO::FETCH_ASSOC)) {
				// Feed has changed. Update Feed record
				if(!$this->updateFeedRec($feed)) return false;
				// Now check if any entry is modified or new entries are added
				$stmt = $this->dbh->prepare("SELECT id, updated FROM Entry WHERE entryId = :entryId AND feed_id = :feed_id");
				$stmt->bindValue(":entryid", $entry->entryId, PDO::PARAM_STR);
				$stmt->bindValue(":feed_id", (int)$feed->id, PDO::PARAM_INT);
				foreach ($feed->entries as $entry) {
					// Check if this is a new entry
					if (!$this->execQuery($stmt, "updateFeed: Check if entry s present", true)) return false;
					if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
						// entry is present, check if it needs to be updated
						$entry->id = $row["id"];
						if ($entry->updated > $row["updated"]) { 
							// entry has changed
							if(!$this->updateEntryRec($entry)) return false;
						}
					}else {
						// insert a new entry
						if (!$this->insertEntryRec($feed->id, $entry)) return false;
		
					}
				}

			} else {
				// Feed doesn't need to be updated, just change lastCheckedAt value
				$stmt = $this->dbh->prepare("UPDATE Feed SET lastCheckedAt = :lastCheckedAt WHERE id = :id");
				$now = new DateTime();
				$stmt->bindValue(":lastCheckedAt", $now->getTimestamp(), PDO::PARAM_INT);
				$stmt->bindValue(":id", (int)$feed->id, PDO::PARAM_INT);
				if (!$this->execQuery($stmt, "updateFeed: update lastCheckedAt", true))
					return false;
				
			}
			$this->dbh->commit();
			return true;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::createFeed: ".$e->getMessage(), 0);
		}
		return false;
	}


	// Helper function to execute a query and log err Msg and optional rollback the transaction
	// Returns the true on success, false on failure
	private function execQuery($stmt, $msg, $rollback = false) {
	    $res = $stmt->execute();
		if (!$res) {
    		error_log("FeedAggregator::FeedManager:: ".$msg.": ".implode(",", $stmt->errorInfo()), 0);
			if ($rollback) $this->dbh->rollBack();
    	}
		return $res;
	}
	
	// Inserts a new feed record and updates the UserFeedRel
	// Returns feed id on success, false on failure
	private function insertFeedRec($userId, Feed $feed) {
		// insert feed record	
		try {
			$stmt = $this->dbh->prepare("INSERT INTO Feed (feedId, title, subtitle, selfLink, updated, authors, alternateLink, lastCheckedAt) ".
				"VALUES (:feedId, :title, :subtitle, :selfLink, :updated, :authors, :alternateLink, :lastCheckedAt)");
			$now = new DateTime();
			$stmt->bindValue(":feedId", $feed->feedId, PDO::PARAM_STR);
			$stmt->bindValue(":title", addslashes($feed->title), PDO::PARAM_STR);
			$stmt->bindValue(":subtitle", addslashes($feed->subtitle), PDO::PARAM_STR);
			$stmt->bindValue(":selfLink", $feed->selfLink, PDO::PARAM_STR);
			$stmt->bindValue(":updated", (int)$feed->updated, PDO::PARAM_INT);
			$stmt->bindValue(":authors", $feed->authors, PDO::PARAM_STR);
			$stmt->bindValue(":alternateLink", $feed->alternateLink, PDO::PARAM_STR);
			$stmt->bindValue(":lastCheckedAt", $now->getTimestamp(), PDO::PARAM_INT);
	   		if ($this->execQuery($stmt, $args, "insertFeedRec: Inserting a new feed record", true)) {
				// Insert new record in UserFeedRel
   				$feedId = $this->dbh->lastInsertId();
				if($this->insertUserFeedRelRec($userId, $feedId)) return $feedId;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertFeedRec ".$e->getMessage(), 0);
		}
		return false;

	}

	// insert a record in UserFeedRel
	//returns true on success, false on failure
	private function insertUserFeedRelRec($userId, $feedId, $ignoreDuplicates = false) {
		$ignore = $ignoreDuplicates ? "IGNORE " : "";
		try {
			$stmt = $this->dbh->prepare("INSERT ".$ignore."INTO UserFeedRel (user_id, feed_id) VALUES (:userId, :feedId)");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "insertUserFeedRelRec: Inserting new UseFeedRel record", true)) return true;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertUserFeedRelRec: ".$e->getMessage(), 0);
		}
		return false;
		

	}	

	// insert records in UserEntryRel
	//returns true on success, false on failure
	private function insertUserEntryRelRecs($userId, $feedId, $ignoreDuplicates = false) {
		// Insert new records in UserEntryRel table
		$ignore = $ignoreDuplicates ? "IGNORE " : "";
		try {
			$stmt = $this->dbh->prepare("INSERT ".$ignore.
				"INTO UserEntryRel (user_id, entry_id) SELECT :userId, id FROM Entry WHERE feed_id = :feedId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "insertUserEntryRelRecs: Adding new records in UserEntryRel",true)) return true;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertUserEntryRelRecs: ".$e->getMessage(), 0);
		}
		return false;

	}

	// Updates UserEntryRel for given entries (objects containing values for UserEntryRel row)
	// Returns true if all entries are updated , false on failure
	public function updateUserEntryRelRecs($userId, $entries) {
		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("UPDATE UserEntryRel SET status = :status, type = :type WHERE entry_id = :entryId AND user_id = :userId");
			$result = true;
			foreach($entries as $entry) {
				$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
				$stmt->bindValue(":entryId", (int)$entry->id, PDO::PARAM_INT);
				$stmt->bindValue(":status", $entry->status, PDO::PARAM_STR);
				$stmt->bindValue(":type", $entry->type, PDO::PARAM_STR);
				if (!$this->execQuery($stmt, "updateUserEntryRelRecs: Updating entry status and type for a given user"))
					$result = false;
			}
			return $result;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::updateUserEntryRelRecs: ".$e->getMessage(), 0);
		}
		return false;

	}

	// Inserts a single entry record, also updates UserEntryRel 
	// Returns entryId on success, false on failure
	private function insertEntryRec($feedId, Entry $entry) {
		try {
			$stmt = $this->dbh->prepare("INSERT INTO Entry (entryId, title, updated, authors, alternateLink, contentType, content, feed_id) ".
				"VALUES (:entryId, :title, :updated, :authors, :alternateLink, :contentType, :content, :feed_id)");
			$stmt->bindValue(":entryId", $entry->entryId, PDO::PARAM_STR);
			$stmt->bindValue(":title", addslashes($entry->title), PDO::PARAM_STR);
			$stmt->bindValue(":updated", (int)$entry->updated, PDO::PARAM_INT);
			$stmt->bindValue(":authors", $entry->authors, PDO::PARAM_STR);
			$stmt->bindValue(":alternateLink", $entry->alternateLink, PDO::PARAM_STR);
			$stmt->bindValue(":contentType", $entry->contentType, PDO::PARAM_STR);
			$stmt->bindValue(":content", addslashes($entry->content), PDO::PARAM_STR);
			$stmt->bindValue(":feed_id", (int)$feedId, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "insertEntryRec: Inserting new entry", true)) {
				$entryId = $this->dbh->lastInsertId();
				// This method is called when inserting a new entry into an existing feed.
				// So update UserEntryRel table for all users to whom this feed belongs
				$stmt = $this->dbh->prepare("INSERT INTO UserEntryRel (user_id, entry_id) ".
					"SELECT user_id, :entryId FROM UserFeedRel WHERE feed_id = :feedId");
				$stmt->bindValue(":entryId", (int)$entryId, PDO::PARAM_INT);
				$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
				if ($this->execQuery($stmt, "insertEntryRec: Update UserEntryRel", true)) 
					return $entryId;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertEntryRec: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Update entry record , also updates Userentryrel
	// Returns true on success, false on failure
	private function updateEntryRec(Entry $entry) {
		try {
			$stmt = $this->dbh->prepare("UPDATE Entry SET title = :title, updated = :updated, authors = :authors, ".
				"alternateLink = :alternateLink, contentType = :contentType, content = :content WHERE id = :id");
			$stmt->bindValue(":title", addslashes($entry->title), PDO::PARAM_STR);
			$stmt->bindValue(":updated", (int)$entry->updated, PDO::PARAM_INT);
			$stmt->bindValue(":authors", $entry->authors, PDO::PARAM_STR);
			$stmt->bindValue(":alternateLink", $entry->alternateLink, PDO::PARAM_STR);
			$stmt->bindValue(":contentType", $entry->contentType, PDO::PARAM_STR);
			$stmt->bindValue(":content", addslashes($entry->content), PDO::PARAM_STR);
			$stmt->bindValue(":id", (int)$entry->id, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "updateEntryRec: Update Entry", true)) {
				$stmt = $this->dbh->prepare("Update UserEntryRel SET status = 'new' where entry_id = :id");
				$stmt->bindValue(":id", (int)$entry->id, PDO::PARAM_INT);
				if ($this->execQuery($stmt, "updateEntryRec: Update UserentryRel", true)) return true;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::updateEntryRec: ".$e->getMessage(), 0);
		}
		return false;
	}
	
	
	// Inserts new entries and updates the UserEntryRel
	// Returns id for last inserted entry, false on failure
	private function insertEntryRecs($userId, $feedId, $entries) {
		try {
			$stmt = $this->dbh->prepare("INSERT INTO Entry (entryId, title, updated, authors, alternateLink, contentType, content, feed_id) ".
				"VALUES (:entryId, :title, :updated, :authors, :alternateLink, :contentType, :content, :feed_id)");
			foreach ($entries as $entry) {
				$stmt->bindValue(":entryId", $entry->entryId, PDO::PARAM_STR);
				$stmt->bindValue(":title", addslashes($entry->title), PDO::PARAM_STR);
				$stmt->bindValue(":updated", (int)$entry->updated, PDO::PARAM_INT);
				$stmt->bindValue(":authors", $entry->authors, PDO::PARAM_STR);
				$stmt->bindValue(":alternateLink", $entry->alternateLink, PDO::PARAM_STR);
				$stmt->bindValue(":contentType", $entry->contentType, PDO::PARAM_STR);
				$stmt->bindValue(":content", addslashes($entry->content), PDO::PARAM_STR);
				$stmt->bindValue(":feed_id", (int)$feedId, PDO::PARAM_INT);
				if (!$this->execQuery($stmt, "insertEntryRecs: Inserting new entry records", true)) return false;
			}
			$lastEntryId = $this->dbh->lastInsertId();
			if ($this->insertUserEntryRelRecs($userId, $feedId)) return $lastEntryId;	
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertEntryRecs: ".$e->getMessage(), 0);
		}
		return false;

	}

	// Updates the feed record 
	// Returns true on success, false on failure
	private function updateFeedRec(Feed $feed) {
		try {
			$stmt = $this->dbh->prepare("UPDATE Feed SET title = :title, subtitle = :subtitle, updated = :updated, authors = :authors, ".
				"alternateLink = :alternateLink, selfLink = :selfLink, lastCheckedAt = :lastCheckedAt WHERE id = :id");
			$now = new DateTime();
			$stmt->bindValue(":title", addslashes($feed->title), PDO::PARAM_STR);
			$stmt->bindValue(":subtitle", addslashes($feed->subtitle), PDO::PARAM_STR);
			$stmt->bindValue(":selfLink", $feed->selfLink, PDO::PARAM_STR);
			$stmt->bindValue(":updated", (int)$feed->updated, PDO::PARAM_INT);
			$stmt->bindValue(":authors", $feed->authors, PDO::PARAM_STR);
			$stmt->bindValue(":alternateLink", $feed->alternateLink, PDO::PARAM_STR);
			$stmt->bindValue(":lastCheckedAt", $now->getTimestamp(), PDO::PARAM_INT);
			$stmt->bindValue(":id", (int)$feed->id, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "updateFeedRec: Update Feed record", true)) {
				 return true;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::updateFeedRec: ".$e->getMessage(), 0);
		}
		return false;
	}

}


?>

