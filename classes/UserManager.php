<?php
include_once $_SERVER["DOCUMENT_ROOT"]."/includes/util.php";

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
			// throw exceptions on PDO errors, an exception will also rollback any transactions
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
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
        try {
			$stmt = $this->dbh->prepare("SELECT id FROM User WHERE username = :username");
    	    if ($this->execQuery($stmt, array(":username" => $username), "userExists: Check if username is present")) {
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
        	if ($this->execQuery($stmt, array(":userId" => $userId), "authenticate user")) {	
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
			$stmt = $this->dbh->prepare("INSERT INTO User (name, username, password) VALUES(:name, :username, :password)");
			$args = array(":name" => $user->getName(), ":username" => $user->getUsername(), ":password" => $user->getPassword());
			if ($this->execQuery($stmt, $args, "createUser: insert a new user record")) {
				return $this->dbh->lastInsertId();
			}
		}catch (PDOException $e) {
			error_log("FeedAggregator::UserManager::createUser: ".$e->getMessage(),0);
		}
		return false;
	}


   // Helper function to execute a query and log err Msg 
    // Returns true on success, false on failure
    private function execQuery($stmt, $args, $msg) {
        $result = $stmt->execute($args);
        if (!$result) {
            error_log("FeedAggregator::UserManager:: ".$msg.": ".implode(",", $stmt->errorInfo()), 0);
        }
        return $result;
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

