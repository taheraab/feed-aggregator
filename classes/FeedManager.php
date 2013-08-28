<?php

include_once "DBManager.php";
include_once "Feed.php";
include_once "EntryManager.php";

//Singleton that manages Feeds in the database
class FeedManager extends DBManager{
	
	private $entryManager = null;
		
	public function __construct() {
		parent::__construct();
		$this->entryManager = new EntryManager($this->dbh);
	}

	public function __destruct() {
		parent::__destruct();

	}
	
	//Get Feed recs from a folder
	// Returns a list of Feed objects on success, false on failure
	public function getFeedsFromFolder($folderId) {
		try {
			$stmt = $this->dbh->prepare("SELECT Feed.title, Feed.selfLink, Feed.alternateLink FROM UserFeedRel INNER JOIN Feed ".
				"ON UserFeedRel.feed_id = Feed.id WHERE UserFeedRel.folder_id = :folderId");
			$stmt->bindValue(":folderId", (int)$folderId, PDO::PARAM_INT);
			if (!$this->execQuery($stmt, "getFeedsFromFolder: Get feed Recs from a folder")) return false;
 			if ($feeds = $stmt->fetchAll(PDO::FETCH_CLASS, "Feed")) {
				// Unescape title, subtitle
				foreach ($feeds as $feed) {
					$feed->title = stripslashes($feed->title);
				}
			}
			return $feeds;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeedsFromFolder: ".$e->getMessage(), 0);
		}
		return false;
	}

	//Get Feed recs for folder organization on the Settings page
	// Returns list of Feed objects on success, false on failure
	public function getFeedsForSettings($userId) {
		try {
			$stmt = $this->dbh->prepare("SELECT Feed.id, Feed.title, Feed.selfLink, UserFeedRel.folder_id FROM UserFeedRel INNER JOIN Feed ".
				"ON UserFeedRel.feed_id = Feed.id WHERE UserFeedRel.user_id = :userId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			if (!$this->execQuery($stmt, "getFeedsForSettings: Get feed Recs for folder organization")) return false;
 			if ($feeds = $stmt->fetchAll(PDO::FETCH_CLASS, "Feed")) {
				// Unescape title and get folder name
				foreach ($feeds as $feed) {
					$feed->title = stripslashes($feed->title);
				}
			}
			return $feeds;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeedsForSettings: ".$e->getMessage(), 0);
		}
		return false;
	}


	

	// Returns all feeds for a given user
	// Returns a list of Feed objects on success, false on failure.
	public function getFeeds($userId) {
		try {
			$stmt = $this->dbh->prepare("SELECT Feed.*, UserFeedRel.folder_id FROM UserFeedRel INNER JOIN Feed ON UserFeedRel.feed_id = Feed.id ".
				"WHERE UserFeedRel.user_id = :userId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			if (!$this->execQuery($stmt, "getFeeds: Get all feeds for given user")) return false;
 			if ($feeds = $stmt->fetchAll(PDO::FETCH_CLASS, "Feed")) {
				// Unescape title, subtitle
				foreach ($feeds as $feed) {
					$feed->title = stripslashes($feed->title);
					$feed->subtitle = stripslashes($feed->subtitle);
					// Get num of unread entry count for each feed
					$n = $this->entryManager->getNumUnreadEntries($userId, $feed->id);
					if (is_string($n)) $feed->numUnreadEntries = $n;
				}
			}
			return $feeds;
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeeds: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Returns all feed records that were last checked for update before or at the given timestamp
	public function getFeedsToUpdate($timestamp) {
		try {
			$stmt = $this->dbh->prepare("SELECT id, selfLink FROM Feed WHERE lastCheckedAt <= :lastCheckedAt");
			$stmt->bindValue(":lastCheckedAt", $timestamp, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "getFeedsToUpdate: Get all feeds that must be checked for update")) {
 				return $stmt->fetchAll(PDO::FETCH_CLASS, "Feed");
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::getFeedsToUpdate: ".$e->getMessage(), 0);
		}
		return false;

	}


	// Adds a feed to the database for the given user
	// Returns true on success, false on failure
	public function createFeed($userId, $folderId, Feed $feed) {
		try {
   		    // Check if feed already exists
			$stmt = $this->dbh->prepare("SELECT id FROM Feed WHERE feedId = :feedId");
			$stmt->bindValue(":feedId", $feed->feedId, PDO::PARAM_STR);
			if (!$this->execQuery($stmt, "createFeed: Check if feed exists", false)) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				//Feed already exists in the database
				// Check if it has changed and update the entries
				$feed->id = $row["id"];
				if ($this->updateFeed($userId, $folderId, $feed)) return $feed->id;
			}else {
				// Insert a new feed, along with its entries
				$this->dbh->beginTransaction();
				$feed->id = $this->insertFeedRec($userId, $folderId, $feed);
				if ($feed->id) {
					// Insert entries
					if ($this->entryManager->insertEntryRecs($userId, $feed->id, $feed->entries)) {
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
	public function updateFeed($userId, $folderId, Feed $feed) {
		try {	
			$this->dbh->beginTransaction();
			if ($userId) { // if userId is given, then this may be a new subscription
				// Update UserFeedRel and UserEntryRel for the given user. This could be a new subscription
				if(!$this->insertUserFeedRelRec($userId, $folderId, $feed->id)) {
					$this->dbh->beginTransaction(); // transaction has been rolled back in previous statement
					// Change folder if this is not a new subscription
					if (!$this->changeFolder($userId, $feed->id, $folderId)) return false;
				}
				if(!$this->entryManager->insertUserEntryRelRecs($userId, $feed->id, true)) return false; //ignore duplicates
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
				$n = count($feed->entries);
				for ($i = $n-1; $i >= 0; $i--) {
					$entry = $feed->entries[$i];
					$stmt->bindValue(":entryId", $entry->entryId, PDO::PARAM_STR);
					$stmt->bindValue(":feed_id", (int)$feed->id, PDO::PARAM_INT);
					// Check if this is a new entry
					if (!$this->execQuery($stmt, "updateFeed: Check if entry s present", true)) return false;
					if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
						// entry is present, check if it needs to be updated
						$entry->id = $row["id"];
						if ($entry->updated > $row["updated"]) { 
							// entry has changed
							if(!$this->entryManager->updateEntryRec($entry)) return false;
						}
					}else {
						// insert a new entry
						if (!$this->entryManager->insertEntryRec($feed->id, $entry)) return false;
		
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
			$this->dbh->rollBack();
			error_log("FeedAggregator::FeedManager::updateFeed: ".$e->getMessage(), 0);
		}
		return false;
	}


	
	// Inserts a new feed record and updates the UserFeedRel
	// Returns feed id on success, false on failure
	private function insertFeedRec($userId, $folderId, Feed $feed) {
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
	   		if ($this->execQuery($stmt, "insertFeedRec: Inserting a new feed record", true)) {
				// Insert new record in UserFeedRel
   				$feedId = $this->dbh->lastInsertId();
				if($this->insertUserFeedRelRec($userId, $folderId, $feedId)) return $feedId;
			}
		} catch (PDOException $e) {
			$this->dbh->rollBack();
			error_log("FeedAggregator::FeedManager::insertFeedRec ".$e->getMessage(), 0);
		}
		return false;

	}

	// insert a record in UserFeedRel
	//returns true on success, false on failure
	private function insertUserFeedRelRec($userId, $folderId, $feedId, $ignoreDuplicates = false) {
		$ignore = $ignoreDuplicates ? "IGNORE " : "";
		try {
			$stmt = $this->dbh->prepare("INSERT ".$ignore."INTO UserFeedRel (user_id, feed_id, folder_id) VALUES (:userId, :feedId, :folderId)");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
			$stmt->bindValue(":folderId", (int)$folderId, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "insertUserFeedRelRec: Inserting new UseFeedRel record", true)) return true;
		} catch (PDOException $e) {
			$this->dbh->rollBack();
			error_log("FeedAggregator::FeedManager::insertUserFeedRelRec: ".$e->getMessage(), 0);
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
			$this->dbh->rollBack();
			error_log("FeedAggregator::FeedManager::updateFeedRec: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Changes folder for a feed for a given user
	//Returns true on success, false on failure
	public function changeFolder($userId, $feedId, $newFolderId) {
		try {
			$stmt = $this->dbh->prepare("UPDATE UserFeedRel SET folder_id = :newFolderId WHERE feed_id = :feedId AND user_id = :userId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
			$stmt->bindValue(":newFolderId", (int)$newFolderId, PDO::PARAM_INT);
			return $this->execQuery($stmt, "changeFolder: Change folder to which feed belongs", true);
		} catch (PDOException $e) {
			error_log("FeedAggregator::FeedManager::changeFolder: ".$e->getMessage(), 0);
		}
		return false;

	}


	// Unsubscribe user from given feed
	// Returns true on success, false on failure
	public function unsubscribeFeed($userId, $feedId) {
		try {
			$this->dbh->beginTransaction();
			// First remove all entry association 
			$stmt = $this->dbh->prepare("DELETE FROM UserEntryRel WHERE user_id = :userId AND entry_id IN (SELECT id FROM Entry WHERE feed_id = :feedId)");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
			if($this->execQuery($stmt, "unsubscribeFeed: Delete user entry association", true)) {
				// Remove  user Feed  Association 
				$stmt = $this->dbh->prepare("DELETE FROM UserFeedRel WHERE user_id = :userId AND feed_id = :feedId");
				$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
				$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
				if($this->execQuery($stmt, "unsubscribeFeed: Delete user feed association", true)) {
					$this->dbh->commit();	
					return true;
				}

			}
		} catch (PDOException $e) {
			$this->dbh->rollBack();
			error_log("FeedAggregator::FeedManager::unsubscribeFeed: ".$e->getMessage(), 0);
		}
		return false;
	}


}


?>
