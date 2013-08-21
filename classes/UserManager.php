<?php
include_once "DBManager.php";
include_once "FolderManager.php";

//Singleton that manages Users in the database
class UserManager extends DBManager {
	private $folderManager;	
	
	public function __construct() {
		parent::__construct();
		$this->folderManager = new FolderManager($this->dbh);
	}

	public function __destruct() {
		parent::__destruct();
		
	}

	
	// Checks if the given user exists in the database and has his email confirmed
	//Returns user object (with name, id & password)  on success, false on failure
	public function userExists($emailId) {
        try {
			$stmt = $this->dbh->prepare("SELECT id, name, password FROM User WHERE emailId = :emailId and confirmed = 'yes'");
			$stmt->bindValue(":emailId", $emailId, PDO::PARAM_STR);
    	    if ($this->execQuery($stmt, "userExists: Check if user email is present")) {
				return $stmt->fetch(PDO::FETCH_CLASS, "User");
			}
   		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::userExists: ".$e->getMessage(),0);
		}
		return false;
	}

	
	// Add a new user to the database
	// Returns the new user Id on success, false on failure
	public function createUser(User $user) {
		try {
			// check if an unconfirmed user exists
			$stmt = $this->dbh->prepare("SELECT id from User WHERE emailId = :emailId and confirmed = 'no'");
			$stmt->bindValue(":emailId", $user->emailId, PDO::PARAM_STR);
			if (!$this->execQuery($stmt, "createUser: check if an unconfirmed user exists")) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				// user exists, update user record because this is a new registration
				$userId = $row["id"];
				$stmt = $this->dbh->prepare("UPDATE User set name=:name, emailId=:emailId, password=:password, ".
					"token=:token, tokenTimestamp=:tokenTS WHERE id=".$userId;		
				$stmt->bindValue(":name", $user->name, PDO::PARAM_STR);
				$stmt->bindValue(":emailId", $user->emailId, PDO::PARAM_STR);
				$stmt->bindValue(":password", $user->password, PDO::PARAM_STR);
				$stmt->bindValue(":token", $user->token, PDO::PARAM_STR);
				$stmt->bindValue(":tokenTS", $user->tokenTimestamp, PDO:PARAM_INT);
				if ($this->execQuery($stmt, "createUser:: update user record")) return $userId;
			}else {
				// insert a new record
				$stmt = $this->dbh->prepare("INSERT INTO User (name, emailId, password) VALUES (:name, :emailId, :password)");
				$stmt->bindValue(":name", $user->name, PDO::PARAM_STR);
				$stmt->bindValue(":emailId", $user->emailId, PDO::PARAM_STR);
				$stmt->bindValue(":password", $user->password, PDO::PARAM_STR);
				if ($this->execQuery($stmt, "createUser: insert a new user record")) {
					return $this->dbh->lastInsertId();
				}
			}	
		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::createUser: ".$e->getMessage(),0);
		}
		return false;
	}


	// Confirm user if token matches
	// returns true on success, false on failure
	public function confirmUser($token)	{
	    try {
			$this->dbh->beginTransaction();
			// token validity is 20 minutes
			$now = new DateTime();
			$date = $now->sub(new DateInterval("PT20M")); // 20 minutes before
			$stmt = $this->dbh->prepare("SELECT id WHERE token=:token AND tokenTimestamp>=".$date->getTimestamp()." AND confirmed='no'");
			$stmt->bindValue(":token", $token, PDO::PARAM_STR);
    	    if (!$this->execQuery($stmt, "confirmUser: Check user for given token", true)) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$userId = row["id"];
				// token is valid, update user record if valid
				$stmt = $this->dbh->prepare("UPDATE User SET confirmed='yes', token='', tokenTimestamp=0 WHERE id=".$userId;
				if ($this->execQuery($stmt, "confirmUser: Update user record to confirmed", true)) {
					// Insert a root folder for this user
					if ($this->folderManager->createFolder($userId, "root")) {
						$this->dbh->commit();
						return true;
					}
				} 
				
			}
   		}catch (PDOException $e) {
			$this->dbh->rollBack();
			error_log("FeedAggregator::UserManager::confirmUser: ".$e->getMessage(),0);
		}
		return false;
	}


	//Send confirmation email to user
	// Returns token  on success and false on failure
	public function sendConfirmationLink($emailId, $username) {
		$token = md5(mt_rand());
		$msg = "<div><a href='".createRedirectURL("confirm.php?token=".$token)."' > Click here confirm your email Id </a></div>";
		if (sendHTMLMail($emailId, $msg)) {
			return $token;
		}
		return false;

	}
	
	//Send reset password email to user
	// Returns true on success and false on failure
	public function sendResetPasswordLink($userId) {
		

	}

	
	// Send html email with given message

}


class User {
	public $id;
	public $name;
	public $emailId;
	public $password;
	public $confirmed;
	public $token = null;
	public $tokenTimestamp = null;


}


?>
