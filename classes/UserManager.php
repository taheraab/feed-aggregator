<?php
include_once "DBManager.php";
include_once "FolderManager.php";

//Singleton that manages Users in the database
class UserManager extends DBManager {
    const CRYPT_SALT = "\$2y\$07\$feedaggregatorpassword";
	private $folderManager;	
	
	public function __construct() {
		parent::__construct();
		$this->folderManager = new FolderManager($this->dbh);
	}

	public function __destruct() {
		parent::__destruct();
		
	}

	
	// Checks if the given user exists in the database
	//Returns userId on success, false on failure
	public function userExists($username) {
		if ($this->dbh == null) $this->connectToDB();
        try {
			$stmt = $this->dbh->prepare("SELECT id FROM User WHERE username = :username");
			$stmt->bindValue(":username", $username, PDO::PARAM_STR);
    	    if ($this->execQuery($stmt, "userExists: Check if username is present")) {
				if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					return $row["id"];
				}
			}
   		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::userExists: ".$e->getMessage(),0);
		}
		return false;
	}

	// Authenticates an existing user with the given password
	public function authenticate($userId, $password) {
		if ($this->dbh == null) $this->connectToDB();
		try {
			$stmt = $this->dbh->prepare("SELECT password FROM User WHERE id = :userId");
			$stmt->bindValue(":userId", (int)$userId, PDO::PARAM_INT);
        	if ($this->execQuery($stmt, "authenticate user")) {	
				// will return exactly one row
				if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if (crypt($password, self::CRYPT_SALT) == $row["password"]) return true;
				}
			}
   		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::authenticate: ".$e->getMessage(),0);
		}
		
		return false;
	
	}
	
	// Add a new user to the database
	// Returns the new user Id on success, false on failure
	public function createUser(User $user) {
		if ($this->dbh == null) $this->connectToDB();
		try {
			$this->dbh->beginTransaction();
			$stmt = $this->dbh->prepare("INSERT INTO User (name, username, password) VALUES (:name, :username, :password)");
			$stmt->bindValue(":name", $user->getName(), PDO::PARAM_STR);
			$stmt->bindValue(":username", $user->getUsername(), PDO::PARAM_STR);
			$stmt->bindValue(":password", $user->getPassword(), PDO::PARAM_STR);
			if ($this->execQuery($stmt, "createUser: insert a new user record", true)) {
				$userId = $this->dbh->lastInsertId();
				// Insert a root folder record for this user
				if ($this->folderManager->createFolder($userId, "root")) {
					$this->dbh->commit();
					return $userId;
				}
			}	
		}catch (PDOException $e) {
			$this->dbh->rollBack();
			error_log("FeedAggregator::UserManager::createUser: ".$e->getMessage(),0);
		}
		return false;
	}


	
}

class User {
	private $name;
	private $username;
	private $password;

	public function __construct($name, $username, $password) {
		$this->name = $name;
		$this->username = $username;
		$this->password = crypt($password, UserManager::CRYPT_SALT);
	}

	public function getName() {
		return $this->name;
	}

	public function getUsername() {
		return $this->username;
	}

	public function getPassword() {
		return $this->password;
	}
}


?>

