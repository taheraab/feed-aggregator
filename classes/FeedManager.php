<?php
//include $_SERVER["DOCUMENT_ROOT"]."/constants.php";
include "/home/tahera/workspace/webApps/reader/constants.php";

//Singleton that manages Feeds in the database
class FeedManager {
	
	private static $instance = null;
	private $dbh = null;
	
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
		}catch (PDOException $e) {
    		error_log("FeedAggregator::FeedManager::connectToDB: ".$e->getMessage(), 0);		
		}
	}

	public static function getInstance() {
		if (self::$instance == null) self::$instance = new FeedManager();
		return self::$instance;
		
	}


	
	// Returns all feeds for a given user
	// Returns a list of Atomfeed object on success, false on failure.
	public function getfeeds($userId) {
   		
		return false;
	}

	// Returns all entries for a given feed
	// Returns List of AtomEntry objects on success, false on failure
	public function getEntries($feedId) {
   		
		return false;
	
	}
	
	// Adds a feed to the database for the given user
	// Returns true on success, false on failure
	public function createFeed($userId, AtomFeed $feed) {
		if ($this->dbh == null) $this->connectToDB();
        // Check if feed already exists
		$stmt = $this->execQuery("SELECT id FROM Feed WHERE feedId = '".$feed->feedId."'", "createFeed: Check if feed exists", false);
		if (!$stmt) return false;
		if ($stmt->rowCount()) {
			//Feed already exists in the database
			// Check if it has changed and update the entries
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$feed->id = $row["id"];
			if (!$this->updateFeed($userId, $feed)) return false;
		}else {
			// Insert a new feed, along with its entries
			$this->dbh->beginTransaction();
			$feed->id = $this->insertFeedRec($userId, $feed);
			if (!$feed->id) return false;
			// Insert entries
			if (!$this->insertEntryRecs($userId, $feed->id, $feed->entries)) return false; 
			$this->dbh->commit();
		}
		
		return $feed->id;
	}

	// Update an existing feed
	// Returns true on success, false on failure
	private function updateFeed($userId, AtomFeed $feed) {
		if ($this->dbh == null) $this->connectToDB();
	
		$this->dbh->beginTransaction();
		// Update UserFeedRel and UserEntryRel for the given user. This could be a new subscription
		if(!$this->insertUserFeedRelRec($userId, $feed->id, true)) return false; //ignore duplicates
		if(!$this->insertUserEntryRelRecs($userId, $feed->id, true)) return false; //ignore duplicates
		// Check if feed updated value has changed 
		$stmt = $this->execQuery("SELECT id FROM Feed WHERE id = '$feed->id' AND updated < '$feed->updated'", "updateFeed: Check if feed has changed", true);
		if (!$stmt) return false;
		if ($stmt->rowCount()) {
			// Feed has changed. Update Feed record
			if(!$this->updateFeedRec($userId, $feed)) return false;
			// Now check if any entry is modified or new entries are added
			foreach ($feed->entries as $entry) {
				// Check if this is a new entry
				$stmt = $this->execQuery("SELECT id, updated FROM Entry WHERE entryId = '$entry->entryId' AND feed_id = '$feed->id'", "updateFeed: Check if entry s present", true);
				if (!$stmt) return false;
				if ($stmt->rowCount()) {
					// entry is present, check if it needs to be updated
					$row = $stmt->fetch(PDO::FETCH_ASSOC);
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
	}	

	// Helper function to execute a query and log err Msg and optional rollback the transaction
	// Returns the statement object on success, false on failure
	private function execQuery($query, $errMsg, $rollback = false) {
	    $stmt = $this->dbh->query($query);
		if (!$stmt) {
    		error_log("FeedAggregator::FeedManager:: ".$errMsg.": ".implode(",", $this->dbh->errorInfo()), 0);
			if ($rollback) $this->dbh->rollBack();
    	}
		return $stmt;
	}
	
	// Inserts a new feed record and updates the UserFeedRel
	// Returns feed id on success, false on failure
	private function insertFeedRec($userId, AtomFeed $feed) {
		// insert feed record	
		$query ="INSERT INTO Feed (feedId, title, subtitle, selfLink, updated, authors, alternateLink) VALUES ('$feed->feedId', '".addslashes($feed->title)."', '".addslashes($feed->subtitle)."', '$feed->selfLink', '$feed->updated', '$feed->authors', '$feed->alternateLink')";
	   	$stmt = $this->execQuery($query, "insertFeed: Inserting a new feed record", true);
		if (!stmt) return false;
		// Insert new record in UserFeedRel
   		$feedId = $this->dbh->lastInsertId();
		if(!$this->insertUserFeedRelRec($userId, $feedId)) return false;	
		return $feedId;

	}

	// insert a record in UserFeedRel
	//returns true on success, false on failure
	private function insertUserFeedRelRec($userId, $feedId, $ignoreDuplicates = false) {
		$ignore = $ignoreDuplicates ? "IGNORE " : "";
		$query = "INSERT ".$ignore."INTO UserFeedRel (user_id, feed_id) VALUES ('$userId', '$feedId')";
		$stmt = $this->execQuery($query, "insertFeed: Inserting new UseFeedRel record", true);
	    if (!$stmt) return false;
		
		return true;	

	}	

	// insert records in UserEntryRel
	//returns true on success, false on failure
	private function insertUserEntryRelRecs($userId, $feedId, $ignoreDuplicates = false) {
		// Insert new records in UserEntryRel table
		$ignore = $ignoreDuplicates ? "IGNORE " : "";
		$stmt = $this->execQuery("INSERT ".$ignore."INTO UserEntryRel (user_id, entry_id) SELECT '$userId', id FROM Entry WHERE feed_id = '$feedId'", "insertEntries: Adding new records in UserEntryRel",true);
		if (!$stmt) return false;
		return true;	

	}

	// Inserts a single entry record, also updates UserEntryRel 
	// Returns entryId on success, false on failure
	private function insertEntryRec($userId, $feedId, AtomEntry $entry) {
		$query = "INSERT INTO Entry (entryId, title, updated, authors, alternateLink, contentType, content, feed_id) VALUES ('$entry->entryId', '".addslashes($entry->title)."', '$entry->updated', '$entry->authors', '$entry->alternateLink', '$entry->contentType', '".addslashes($entry->content)."', '$feedId')";
		$stmt = $this->execQuery($query, "insertEntryRec: Inserting new entry", true);
		if (!$stmt) return false;
		$entryId = $this->dbh->lastInsertId();
		// This method is called when inserting a new entry into an existing feed.
		// So update UserEntryRel table for all users to whom this feed belongs
		$stmt = $this->execQuery("INSERT INTO UserEntryRel (user_id, entry_id) SELECT user_id, '$entryId' FROM UserFeedRel WHERE feed_id = '$feedId'", "insertEntryRec: Update UserEntryRel", true);
		if (!$stmt) return false;	
		
		return $entryId;
	}

	// Update entry record , also updates Userentryrel
	// Returns true on success, false on failure
	private function updateEntryRec($userId, AtomEntry $entry) {
		$query = "UPDATE Entry SET title = '".addslashes($entry->title)."', updated = '$entry->updated', authors = '$entry->authors', alternateLink = '$entry->alternateLink', contentType = '$entry->contentType', content = '".addslashes($entry->content)."' WHERE id = '$entry->id'";
		$stmt = $this->execQuery($query, "updateEntryRec: Update Entry", true);
		if (!$stmt) return false;
		$stmt = $this->execQuery("Update UserEntryRel SET status = 'unread' where entry_id = '$entry->id'", "updateEntryRec: Update UserentryRel", true);
		if (!$stmt) return false;

		return true;
	
	}
	
	
	// Inserts new entries and updates the UserEntryRel
	// Returns id for last inserted entry, false on failure
	private function insertEntryRecs($userId, $feedId, $entries) {
		$query = "INSERT INTO Entry (entryId, title, updated, authors, alternateLink, contentType, content, feed_id) VALUES "; 
		foreach ($entries as $entry) {
			$query = $query."('$entry->entryId', '".addslashes($entry->title)."', '$entry->updated', '$entry->authors', '$entry->alternateLink', '$entry->contentType', '".addslashes($entry->content)."', '$feedId'),";
		}
		$query = rtrim($query, ",");
		$stmt = $this->execQuery($query, "insertEntryRecs: Inserting new entry records", true);
		if (!$stmt) return false;
		$lastEntryId = $this->dbh->lastInsertedId();
		if(!$this->insertUserEntryRelRecs($userId, $feedId)) return false;	
		return $lastEntryId;	

	}

	// Updates the feed record and corresponding UserFeedRel
	// Returns true on success, false on failure
	private function updateFeedRec($userId, AtomFeed $feed) {
		$query = "UPDATE Feed SET title = '".addslashes($feed->title)."', subtitle = '".addslashes($feed->subtitle)."', updated = '$feed->updated', authors = '$feed->authors', alternateLink = '$feed->alternateLink', selfLink = '$feed->selfLink' WHERE id = '$feed->id'";
		$stmt = $this->execQuery($query, "updateFeedRec: Update Feed record", true);
		if (!$stmt) return false;
		if(!$this->insertUserFeedRelRec($userId, $feed->id, true)) return false; //ignore duplicates
		
		return true;
	}

}


?>

