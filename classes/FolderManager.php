<?php

include_once "DBManager.php";
include_once "Feed.php";

//Singleton that manages Folders in the database
class FolderManager extends DBManager{
	

	public function __construct($dbh = null) {
		parent::__construct($dbh);
	}

	public function __destruct() {
		parent::__destruct();
	}
	
	// Create a new folder
	// Returns id for new folder on success, false on failure
	public function createFolder($userId, $name) {
		if (empty($name)) return false;
		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("INSERT INTO Folder (name, user_id) VALUES (:name, :userId)");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":name", $name, PDO::PARAM_STR);
			if (!$this->execQuery($stmt, "createFolder: create a new folder for a given user", true)) return false;
			return $this->dbh->lastInsertId();
		} catch (PDOException $e) {
			error_log("FeedAggregator::FolderManager::createFolder: ".$e->getMessage(), 0);
		}
		return false;


	}

	// Check if folder exists and return it's id on success
	public function folderExists($userId, $name) {
		if ($this->dbh == null) $this->connectToDB();
		try {
			// Check if folder exists 
			$stmt = $this->dbh->prepare("SELECT id FROM Folder WHERE name = :name AND user_id = :userId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			$stmt->bindValue(":name", $name, PDO::PARAM_STR);
			if (!$this->execQuery($stmt, "folderExists: check if folder exists")) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC))	
				return $row["id"];
		} catch (PDOException $e) {
			error_log("FeedAggregator::FolderManager::folderExists: ".$e->getMessage(), 0);
		}
		return false;


	}
	
	// Returns all folders for a given user
	// Returns a list of Folder objects on success, false on failure
	public function getFolders($userId) {
		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("SELECT id, name FROM Folder WHERE user_id = :userId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
			if (!$this->execQuery($stmt, "getFolders: Get folders for a given user")) return false;
 			return $stmt->fetchAll(PDO::FETCH_CLASS, "Folder");
		} catch (PDOException $e) {
			error_log("FeedAggregator::FolderManager::getFolders: ".$e->getMessage(), 0);
		}
		return false;


	}

	// Delete a folder, associate all it's feeds with the root folder
	// Returns true on success, false on failure
	public function deleteFolder($userId, $folderId) {
        if ($this->dbh == null) $this->connectToDB();
        try {
            $this->dbh->beginTransaction();
			// First get root id
            $stmt = $this->dbh->prepare("SELECT id FROM Folder WHERE user_id = :userId AND name = 'root'");
            $stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
            if(!$this->execQuery($stmt, "deleteFolder: get root id", true)) return false;
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$rootId = $row["id"];
				$stmt = $this->dbh->prepare("SELECT feed_id FROM UserFeedRel WHERE folder_id = :folderId");
           		$stmt->bindValue(":folderId", (int)$folderId, PDO::PARAM_INT);
				if (!$this->execQuery($stmt, "deleteFolder: Get feed ids for given folder", true)) return false;
            	if ($feedIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0)) { 
					$stmt = $this->dbh->prepare("UPDATE UserFeedRel SET folder_id = ".$rootId." WHERE feed_id IN (".implode(",", $feedIds).")");
					if (!$this->execQuery($stmt, "deleteFolder: Move feeds to root", true)) return false; 
				}
				$stmt = $this->dbh->prepare("DELETE FROM Folder WHERE id = :folderId");
              	$stmt->bindValue(":folderId", (int)$folderId, PDO::PARAM_INT);
            	if ($this->execQuery($stmt, "deleteFolder: Delete folder", true)) {
                	$this->dbh->commit();
                	return true;
               	 }
				
            }

        } catch (PDOException $e) {
            error_log("FeedAggregator::FolderManager::deleteFolder: ".$e->getMessage(), 0);
        }
        return false;
	}

	
	// rename folder 
	//Returns true on success, false on failure
	public function renameFolder($userId, $folderId, $newName) {
		if (!strcasecmp($newName, "root") || empty($newName)) return false; // do not rename
		if ($this->folderExists($userId, $newName)) return false; // keep folder names unique
		try {
			$stmt = $this->dbh->prepare("UPDATE Folder SET name = :newName WHERE id = :folderId");
			$stmt->bindValue(":folderId", (int)$folderId, PDO::PARAM_INT);
			$stmt->bindValue(":newName", $newName, PDO::PARAM_STR);
			return $this->execQuery($stmt, "renameFolder: rename folder");
		} catch (PDOException $e) {
			error_log("FeedAggregator::FolderManager::renameFolder: ".$e->getMessage(), 0);
		}
		return false;
	}
	
}


?>

