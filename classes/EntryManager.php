<?php

include_once "DBManager.php";
include_once "Feed.php";

//Singleton that manages Feed Entries in the database
class EntryManager extends DBManager{
	
	public function __construct($dbh = null) {
		parent::__construct($dbh);

	}

	public function __destruct() {
		parent::__destruct();
	
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
			error_log("FeedAggregator::EntryManager::deleteOldEntries: ".$e->getMessage(), 0);
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
			error_log("FeedAggregator::EntryManager::getEntries: ".$e->getMessage(), 0);
		}
		return false;
	}
	

	

	// insert records in UserEntryRel
	//returns true on success, false on failure
	public function insertUserEntryRelRecs($userId, $feedId, $ignoreDuplicates = false) {
		// Insert new records in UserEntryRel table
		$ignore = $ignoreDuplicates ? "IGNORE " : "";
		try {
			$stmt = $this->dbh->prepare("INSERT ".$ignore.
				"INTO UserEntryRel (user_id, entry_id) SELECT :userId, id FROM Entry WHERE feed_id = :feedId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":feedId", (int)$feedId, PDO::PARAM_INT);
			if ($this->execQuery($stmt, "insertUserEntryRelRecs: Adding new records in UserEntryRel",true)) return true;
		} catch (PDOException $e) {
			error_log("FeedAggregator::EntryManager::insertUserEntryRelRecs: ".$e->getMessage(), 0);
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
			error_log("FeedAggregator::EntryManager::updateUserEntryRelRecs: ".$e->getMessage(), 0);
		}
		return false;

	}

	// Inserts a single entry record, also updates UserEntryRel 
	// Returns entryId on success, false on failure
	public function insertEntryRec($feedId, Entry $entry) {
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
			error_log("FeedAggregator::EntryManager::insertEntryRec: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Update entry record , also updates Userentryrel
	// Returns true on success, false on failure
	public function updateEntryRec(Entry $entry) {
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
			error_log("FeedAggregator::EntryManager::updateEntryRec: ".$e->getMessage(), 0);
		}
		return false;
	}
	
	
	// Inserts new entries and updates the UserEntryRel
	// Returns id for last inserted entry, false on failure
	public function insertEntryRecs($userId, $feedId, $entries) {
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
			error_log("FeedAggregator::EntryManager::insertEntryRecs: ".$e->getMessage(), 0);
		}
		return false;

	}


}


?>

