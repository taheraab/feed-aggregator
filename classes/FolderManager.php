<?php

include_once "DBManager.php";
include_once "Feed.php";

//Singleton that manages Folders in the database
class FolderManager extends DBManager{
	
	private static $instance = null;
	

	public static function getInstance() {
		if (self::$instance == null) self::$instance = new FolderManager();
		return self::$instance;
		
	}


	
	// Returns all folders for a given user
	// Returns a list of Folder objects on success, false on failure
	public function getFolders($userId) {
		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("SELECT Folder.id, Folder.name FROM UserFeedRel INNER JOIN Folder ".
				"ON UserFeedRel.folder_id = Folder.id WHERE UserFeedRel.user_id = :userId");
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

