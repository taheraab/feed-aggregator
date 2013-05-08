<?php
class UserManager {

	// Checks if the given user exists in the database
	public static function userExists($username) {
		return false;
	}

	// Authenticates an existing user with the given password
	public static function authenticate($username, $password) {
		return true;
	
	}
	
	// Add a new user to the database
	public static function createUser(User $user) {
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
		$this->password = $password;
	}

	public function getName() {
		return $this->name;
	}

	public function getUsername() {
		return $this->username;
	}
}


?>

