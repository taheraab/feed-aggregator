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
		if (self::$instance == null) self::$instance = new UserManager();
		return self::$instance;
		
	}
	
	// Checks if the given user exists in the database
	//Returns userId on success, false on failure
	public function userExists($username) {
		if ($this->dbh == null) $this->connectToDB();
        $stmt = $this->dbh->query("SELECT id FROM User WHERE username = '$username'");
        if (!$stmt) {
            error_log("FeedAggregator::UserManager::userExists: ".implode("," $this->dbh->errorInfo()), 0);
            return false;
        }
		if ($stmt->rowCount()) {
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			return $row["id"];
		}
   		
		return false;
	}

	// Authenticates an existing user with the given password
	public function authenticate($userId, $password) {
		if ($this->dbh == null) $this->connectToDB();
        $stmt = $this->dbh->query("SELECT password FROM User WHERE id = '$userId'");
        if (!$stmt) {
            error_log("FeedAggregator::UserManager::authenticate: ".implode(",", $this->dbh->errorInfo()), 0);
            return false;
         }
		// will return exactly one row
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if (crypt($password, self::CRYPT_SALT) == $row["password"]) return true;
		else return false;
	
	}
	
	// Add a new user to the database
	// Returns the new user Id on success, false on failure
	public function createUser(User $user) {
		if ($this->dbh == null) $this->connectToDB();
		$stmt = $this->dbh->query("INSERT INTO User (name, username, password) VALUES('".$user->getName()."','".$user->getUsername()."','".$user->getPassword()."')");
	    if (!$stmt) {
               error_log("FeedAggregator::UserManager::createUser: ".implode(",", $this->dbh->errorInfo()), 0);
			return false;
    	}
		return $this->dbh->lastInsertId();
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

