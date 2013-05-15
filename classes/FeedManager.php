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


	//	Check if feed already exists in the database
	// Returns feed Id on success, false on failure
	public function feedExists($XMLFeedId) {
		if ($this->dbh == null) $this->connectTODB();
            $stmt = $this->dbh->query("SELECT id FROM Feed WHERE feedId = '".$XMLFeedId."'");
            if (!$stmt) {
                error_log("FeedAggregator::FeedManager::feedExists: ".implode(",", $this->dbh->errorInfo()), 0);
                return false;
            }
			if ($stmt->rowCount()) {
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				return $row["id"];
			}
   		
		return false;

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
		$feedId = $this->feedExists($feed->feedId);
		if ($feedId) {
			//Feed already exists in the database
			// Check if it has changed and update the entries
			if(!$this->updateFeed($feed)) return false;
		}else {
			// Insert a new feed, along with its entries
			$this->dbh->beginTransaction();
			// insert feed record	
			$stmt = $this->dbh->query("INSERT INTO Feed (feedId, title, subtitle, selfLink, updated, authors, alternateLink) VALUES ('$feed->feedId', '".addslashes($feed->title)."', '".addslashes($feed->subtitle)."', '$feed->selfLink', '$feed->updated', '$feed->authors', '$feed->alternateLink')");
		    if (!$stmt) {
                error_log("FeedAggregator::FeedManager::createFeed: Inserting feed record: ".implode(",", $this->dbh->errorInfo()), 0);
				$this->dbh->rollBack();
				return false;
    		}
			// Insert new record in UserFeedRel
    		$feedId = $this->dbh->lastInsertId();
			$stmt = $this->dbh->query("INSERT INTO UserFeedRel (user_id, feed_id) VALUES ('$userId', '$feedId')");
		    if (!$stmt) {
                error_log("FeedAggregator::FeedManager::createFeed: Inserting User-Feed relationship record: ".implode(",", $this->dbh->errorInfo()), 0);
				$this->dbh->rollBack();
				return false;
			}	
			// Insert the feed entries
			$query = "INSERT INTO Entry (entryId, title, updated, authors, alternateLink, contentType, content, feed_id) VALUES "; 
			foreach ($feed->entries as $entry) {
				$query = $query."('$entry->entryId', '".addslashes($entry->title)."', '$entry->updated', '$entry->authors', '$entry->alternateLink', '$entry->contentType', '".addslashes($entry->content)."', '$feedId'),";
			}
			$query = rtrim($query, ",");
			var_dump($query);
			$stmt = $this->dbh->query($query);
		    if (!$stmt) {
                error_log("FeedAggregator::FeedManager::createFeed: Inserting entries:  ".implode(",",$this->dbh->errorInfo()), 0);
				$this->dbh->rollBack();
				return false;
			}	
			$this->dbh->commit();
		}
		
		return $feedId;
	}

	// Update an existing feed
	// Returns true on success, false on failure
	public function updateFeed(AtomFeed $feed) {
		return true;

	}	
}


?>

