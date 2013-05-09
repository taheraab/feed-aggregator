<?php
include $_SERVER["DOCUMENT_ROOT"]."/constants.php";

//Singleton that manages Users in the database
class UserManager {
    const CRYPT_SALT = "\$2y\$07\$feedaggregatorpassword";
	
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
    		error_log("FeedAggregator::UserManager::connectToDB: ".$e->getMessage(), 0);		
		}
	}

	public static function getInstance() {
		if (UserManager::$instance == null) UserManager::$instance = new UserManager();
		return UserManager::$instance;
		
	}
	
	// Checks if the given user exists in the database
	public function userExists($username) {
		if ($this->dbh == null) connectToDB();
        try {
            $stmt = $this->dbh->query("SELECT username FROM User WHERE username = '".$username."'");
            if (!$stmt) {
                error_log("FeedAggregator::UserManager::userExists: ".$this->dbh->errorInfo(), 0);
                return false;
            }
			if ($stmt->rowCount()) return true;
        } catch (PDOException $e) {
            error_log("FeedAggregator::UserManager::userExists: ".$e->getMessage(),0);
            return false;
        }
   		
		return false;
	}

	// Authenticates an existing user with the given password
	public function authenticate($username, $password) {
		if ($this->dbh == null) connectToDB();
        try {
            $stmt = $this->dbh->query("SELECT password FROM User WHERE username = '".$username."'");
            if (!$stmt) {
                error_log("FeedAggregator::UserManager::authenticate: ".$this->dbh->errorInfo(), 0);
                return false;
            }
			// will return exactly one row
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if (crypt($password, UserManager::CRYPT_SALT) == $row["password"]) return true;
        } catch (PDOException $e) {
            error_log("FeedAggregator::UserManager::authenticate: ".$e->getMessage(),0);
            return false;
        }
   		
		return false;
	
	}
	
	// Add a new user to the database
	public function createUser(User $user) {
		if ($this->dbh == null) connectToDB();
		var_dump($user);
		try {
			$stmt = $this->dbh->query("INSERT INTO User () VALUES('".$user->getName()."','".$user->getUsername()."','".$user->getPassword()."')");
		    if (!$stmt) {
                error_log("FeedAggregator::UserManager::createUser: ".$this->dbh->errorInfo(), 0);
				return false;
    		}
		} catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::createUser: ".$e->getMessage(),0);
			return false;
		}
		return true;
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

