<?php
include_once $_SERVER["DOCUMENT_ROOT"]."/includes/util.php";
//include_once "/home/tahera/workspace/webApps/reader/includes/util.php";
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
			if (!$this->execQuery($stmt, array (":userId" => $userId), "getFeeds: Get all feeds for given user")) return false;
 			if ($feeds = $stmt->fetchAll(PDO::FETCH_CLASS, "Feed")) {
				// Unescape title, subtitle
				foreach ($feeds as $feed) {
					$feed->title = stripslashes($feed->title);
					$feed->subtitle = stripslashes($feed->subtitle);
				}
				return $feeds;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeeds: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Returns all entries for a given feed and a given user
	// Returns List of Entry objects on success, false on failure
	public function getEntries($userId, $feedId) {
   		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("SELECT Entry.*, UserEntryRel.status FROM Entry INNER JOIN UserEntryRel ON ".
				"Entry.id = UserEntryRel.entry_id WHERE Entry.feed_id = :feedId AND UserEntryRel.user_id = :userId");
			if (!$this->execQuery($stmt, array(":feedId" => $feedId, ":userId" => $userId), "getEntries: Get all entries for given feed")) return false;
			if ($entries = $stmt->fetchALL(PDO::FETCH_CLASS, "Entry")) {
				// Unescape title and content
				foreach ($entries as $entry) {
					$entry->title = stripslashes($entry->title);
					$entry->content = stripslashes($entry->content);	
				}
				return $entries;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeeds: ".$e->getMessage(), 0);
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
			if (!$this->execQuery($stmt, array (":feedId" => $feed->feedId), "createFeed: Check if feed exists", false)) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				echo "In update";
				//Feed already exists in the database
				// Check if it has changed and update the entries
				$feed->id = $row["id"];
				if ($this->updateFeed($userId, $feed)) return $feed->id;
			}else {
				echo "In insert";
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

	// Update an existing feed
	// Returns true on success, false on failure
	private function updateFeed($userId, Feed $feed) {
		if ($this->dbh == null) $this->connectToDB();
		try {	
			$this->dbh->beginTransaction();
			// Update UserFeedRel and UserEntryRel for the given user. This could be a new subscription
			if(!$this->insertUserFeedRelRec($userId, $feed->id, true)) return false; //ignore duplicates
			if(!$this->insertUserEntryRelRecs($userId, $feed->id, true)) return false; //ignore duplicates
			// Check if feed updated value has changed 
			$stmt = $this->dbh->prepare("SELECT id FROM Feed WHERE id = :id AND updated < :updated");
			if (!$this->execQuery($stmt, array(":id" => $feed->id, ":updated" => $feed->updated), 
					"updateFeed: Check if feed has changed", true)) return false;
			if ($stmt->fetch(PDO::FETCH_ASSOC)) {
				// Feed has changed. Update Feed record
				if(!$this->updateFeedRec($userId, $feed)) return false;
				// Now check if any entry is modified or new entries are added
				$stmt = $this->dbh->prepare("SELECT id, updated FROM Entry WHERE entryId = :entryId AND feed_id = :feed_id");
				foreach ($feed->entries as $entry) {
					// Check if this is a new entry
					if (!$this->execQuery($stmt, array(":entryId" => $entry->entryId, ":feed_id" => $feed->id), 
								"updateFeed: Check if entry s present", true)) return false;
					if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
						// entry is present, check if it needs to be updated
						$entry->id = $row["id"];
						if ($entry->updated > $row["updated"]) { 
							// entry has changed
							if(!$this->updateEntryRec($userId, $entry)) return false;
						}
					}else {
						// insert a new entry
						if (!$this->insertEntryRec($userId, $feed->id, $entry)) return false;
		
					}
				}

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
	private function execQuery($stmt, $args, $msg, $rollback = false) {
	    $res = $stmt->execute($args);
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
			$stmt = $this->dbh->prepare("INSERT INTO Feed (feedId, title, subtitle, selfLink, updated, authors, alternateLink) ".
				"VALUES (:feedId, :title, :subtitle, :selfLink, :updated, :authors, :alternateLink)");
			$args =array(":feedId" => $feed->feedId, ":title" => addslashes($feed->title), ":subtitle" => addslashes($feed->subtitle), 
			":selfLink" => $feed->selfLink, ":updated" => $feed->updated, ":authors" => $feed->authors, ":alternateLink" => $feed->alternateLink);
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
			if ($this->execQuery($stmt, array(":userId" => $userId, ":feedId" => $feedId), 
				"insertUserFeedRelRec: Inserting new UseFeedRel record", true)) return true;
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
			if ($this->execQuery($stmt, array(":userId" => $userId, ":feedId" => $feedId), 
					"insertUserEntryRelRecs: Adding new records in UserEntryRel",true)) return true;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertUserEntryRelRecs: ".$e->getMessage(), 0);
		}
		return false;

	}

	// Inserts a single entry record, also updates UserEntryRel 
	// Returns entryId on success, false on failure
	private function insertEntryRec($userId, $feedId, Entry $entry) {
		try {
			$stmt = $this->dbh->prepare("INSERT INTO Entry (entryId, title, updated, authors, alternateLink, contentType, content, feed_id) ".
				"VALUES (:entryId, :title, :updated, :authors, :alternateLink, :contentType, :content, :feed_id)");
			$args = array(":entryId" => $entry->entryId, ":title" => addslashes($entry->title), ":updated" => $entry->updated, 
				":authors" => $entry->authors, ":alternateLink" => $entry->alternateLink, ":contentType" => $entry->contentType, 
				":content" => addslashes($entry->content), ":feed_id" => $feedId);
			if ($this->execQuery($stmt, $args, "insertEntryRec: Inserting new entry", true)) {
				$entryId = $this->dbh->lastInsertId();
				// This method is called when inserting a new entry into an existing feed.
				// So update UserEntryRel table for all users to whom this feed belongs
				$stmt = $this->dbh->prepare("INSERT INTO UserEntryRel (user_id, entry_id) ".
					"SELECT user_id, :entryId FROM UserFeedRel WHERE feed_id = :feedId");
				if ($this->execQuery($stmt, array(":entryId" => $entryId, ":feedId" => $feedId), "insertEntryRec: Update UserEntryRel", true)) 
					return $entryId;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertEntryRec: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Update entry record , also updates Userentryrel
	// Returns true on success, false on failure
	private function updateEntryRec($userId, Entry $entry) {
		try {
			$stmt = $this->dbh->prepare("UPDATE Entry SET title = :title, updated = :updated, authors = :authors, ".
				"alternateLink = :alternateLink, contentType = :contentType, content = :content WHERE id = :id");
			$args = array(":title" => addslashes($entry->title), ":updated" => $entry->updated, ":authors" => $entry->authors,
				 ":alternateLink" => $entry->alternateLink, ":contentType" => $entry->contentType, ":content" => addslashes($entry->content), 
		   		 ":id" => $entry->id);
			if ($this->execQuery($stmt, $args, "updateEntryRec: Update Entry", true)) {
				$stmt = $this->dbh->prepare("Update UserEntryRel SET status = 'unread' where entry_id = :id");
				if ($this->execQuery($stmt, array(":id" => $entry->id), "updateEntryRec: Update UserentryRel", true)) return true;
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
				$args = array(":entryId" => $entry->entryId, ":title" => addslashes($entry->title), ":updated" => $entry->updated, 
					":authors" => $entry->authors, ":alternateLink" => $entry->alternateLink, ":contentType" => $entry->contentType, 
					":content" => addslashes($entry->content), ":feed_id" => $feedId);
				if (!$this->execQuery($stmt, $args, "insertEntryRecs: Inserting new entry records", true)) return false;
			}
			$lastEntryId = $this->dbh->lastInsertId();
			if ($this->insertUserEntryRelRecs($userId, $feedId)) return $lastEntryId;	
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::insertEntryRecs: ".$e->getMessage(), 0);
		}
		return false;

	}

	// Updates the feed record and corresponding UserFeedRel
	// Returns true on success, false on failure
	private function updateFeedRec($userId, Feed $feed) {
		try {
			$stmt = $this->dbh->prepare("UPDATE Feed SET title = :title, subtitle = :subtitle, updated = :updated, authors = :authors, ".
				"alternateLink = :alternateLink, selfLink = :selfLink WHERE id = :id");
			$args = array(":title" =>  addslashes($feed->title), ":subtitle" => addslashes($feed->subtitle), ":updated" => $feed->updated,
				 ":authors" => $feed->authors, ":alternateLink" => $feed->alternateLink, ":selfLink" => $feed->selfLink, ":id" => $feed->id);
			if ($this->execQuery($stmt, $args, "updateFeedRec: Update Feed record", true)) {
				if ($this->insertUserFeedRelRec($userId, $feed->id, true)) return true; //ignore duplicates
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::updateFeedRec: ".$e->getMessage(), 0);
		}
		return false;
	}

}


?>

