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
 			if ($folders = $stmt->fetchAll(PDO::FETCH_CLASS, "Folder")) {
				return $folders;
			}
		} catch (PDOException $e) {
			error_log("FeedAggregator::FolderManager::getFolders: ".$e->getMessage(), 0);
		}
		return false;


	}
	
}


?>

