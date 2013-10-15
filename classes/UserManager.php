<?php
include_once "DBManager.php";
include_once "FolderManager.php";
include_once dirname(__FILE__)."/../includes/util.php";

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
			$stmt = $this->dbh->prepare("SELECT id, name, password, emailId FROM User WHERE emailId = :emailId and confirmed = 'yes'");
			$stmt->bindValue(":emailId", $emailId, PDO::PARAM_STR);
    	    if ($this->execQuery($stmt, "userExists: Check if user email is present")) {
				return $stmt->fetchObject("User");
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
			$user->password = crypt($user->password);
			// check if an unconfirmed user exists
			$stmt = $this->dbh->prepare("SELECT id from User WHERE emailId = :emailId and confirmed = 'no'");
			$stmt->bindValue(":emailId", $user->emailId, PDO::PARAM_STR);
			if (!$this->execQuery($stmt, "createUser: check if an unconfirmed user exists")) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				// user exists, update user record because this is a new registration
				$userId = $row["id"];
				$stmt = $this->dbh->prepare("UPDATE User set name=:name, emailId=:emailId, password=:password, ".
					"token=:token, tokenTimestamp=:tokenTS WHERE id=".$userId);		
				$stmt->bindValue(":name", $user->name, PDO::PARAM_STR);
				$stmt->bindValue(":emailId", $user->emailId, PDO::PARAM_STR);
				$stmt->bindValue(":password", $user->password, PDO::PARAM_STR);
				$stmt->bindValue(":token", $user->token, PDO::PARAM_STR);
				$stmt->bindValue(":tokenTS", $user->tokenTimestamp, PDO::PARAM_INT);
				if ($this->execQuery($stmt, "createUser:: update user record")) return $userId;
			}else {
				// insert a new record
				$stmt = $this->dbh->prepare("INSERT INTO User (name, emailId, password, token, tokenTimestamp) VALUES ".
					"(:name, :emailId, :password, :token, :tokenTS)");
				$stmt->bindValue(":name", $user->name, PDO::PARAM_STR);
				$stmt->bindValue(":emailId", $user->emailId, PDO::PARAM_STR);
				$stmt->bindValue(":password", $user->password, PDO::PARAM_STR);
				$stmt->bindValue(":token", $user->token, PDO::PARAM_STR);
				$stmt->bindValue(":tokenTS", $user->tokenTimestamp, PDO::PARAM_INT);
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
			if ($userId = $this->isTokenValid($token)) {
				$this->dbh->beginTransaction();
				// token is valid, update user record if valid
				$stmt = $this->dbh->prepare("UPDATE User SET confirmed='yes', token='', tokenTimestamp=0 WHERE id=".$userId);
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
	public function sendConfirmationLink($user) {
		$token = md5(mt_rand());
		$msg = "<div><a href='".createRedirectURL("confirm.php?token=".$token)."' > Click here confirm your email Id. </a></div>";
		if (sendHTMLMail($user->emailId, $user->name, $msg)) {
			return $token;
		}
		return false;

	}
	
	//Send reset password email to user
	// Returns true on success and false on failure
	public function sendResetPasswordLink($user) {
		try {
			$token = md5(mt_rand());
			$msg = "<div><a href='".createRedirectURL("reset_password.php?token=".$token)."' > Click here to reset your password. </a></div";
			if (sendHTMLMail($user->emailId, $user->name, $msg)) {
				$now = new DateTime();
				// update user record with given token
				$stmt = $this->dbh->prepare("UPDATE User set token=:token, tokenTimestamp=:tokenTS WHERE id=".$user->id);		
				$stmt->bindValue(":token", $token, PDO::PARAM_STR);
				$stmt->bindValue(":tokenTS", $now->getTimestamp(), PDO::PARAM_INT);
				return $this->execQuery($stmt, "sendResetPasswordLink:: update user record");
		
			}
   		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::sendResetPasswordLink: ".$e->getMessage(),0);
		}
		return false;
	}

	// Checks if given token is valid
	// Returns userId on success, false on failure
	public function isTokenValid($token) {
		try {
			// token validity is 20 minutes
			$now = new DateTime();
			$date = $now->sub(new DateInterval("PT20M")); // 20 minutes before
			$stmt = $this->dbh->prepare("SELECT id FROM User WHERE token = :token AND tokenTimestamp >= ".$date->getTimestamp());
			$stmt->bindValue(":token", $token, PDO::PARAM_STR);
    	    if (!$this->execQuery($stmt, "istokenValid: Check user for given token")) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) return $row["id"];

		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::changePassword: ".$e->getMessage(), 0);
		}
		return false;

	}


	// Change password, also update token info to invalidate any token
	// Returns true on success, false on failure
	public function changePassword($userId, $newPassword) {
		$newPassword = crypt($newPassword);
		try {
			$stmt = $this->dbh->prepare("UPDATE User SET password='".$newPassword."', token='', tokenTimestamp=0 WHERE id = ".$userId);
			return $this->execQuery($stmt, "changePassword: Update user password");
				
		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::changePassword: ".$e->getMessage(), 0);
		}
		return false;
	}

	// Authenticate user, check if passwords are equal
	// Returns true on success, false on failure
	public function authenticateUser($password, $newPassword) {
		return (crypt($newPassword, $password) == $password);
		
	}

	// Re-authenticate user, (for changing user settings)
	// Returns true on success, false on failure
	public function reAuthenticateUser($userId, $password) {
		// get user password
		try {
			$stmt = $this->dbh->prepare("SELECT password from User WHERE id = ".$userId);
			if(!$this->execQuery($stmt, "reAuthenticateUser: Get user password")) return false;
			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				return $this->authenticateUser($row["password"], $password);
			}
				
		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::reAuthenticateUser: ".$e->getMessage(), 0);
		}
		return false;

	}
	

	

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
