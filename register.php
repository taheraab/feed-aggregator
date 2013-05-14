<!DOCTYPE html>
<html>
<head> 
	<title>Register to Feed Aggregator </title>
	<script src="js/misc.js"></script>
</head>
<body>
<?php
include "classes/UserManager.php";
session_start();
$userManager = UserManager::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if ($userManager->userExists(htmlspecialchars($_POST["username"]))) {
		echo "<p> ERROR: Username not available, try again </p>";
		displayRegisterForm();
	} else {
		//Add user to the database and session	
		if (isset($_POST["firstname"])) $name = htmlspecialchars($_POST["firstname"]);
 		if (isset($_POST["lastname"])) $name = $name." ".htmlspecialchars($_POST["lastname"]);
		$user = new User($name, htmlspecialchars($_POST["username"]), htmlspecialchars($_POST["password"]));
		$userId = $userManager->createUser($user);
		if ($userId) {// if creation was successful
			$_SESSION["currentUsername"] = $user->getUsername();
			$_SESSION["currentUserId"] = $userId;
			echo "<button type=\"button\" onclick=\"gotoPage('login.php')\"> Proceed to login </button>";
		}else {
			"<p> Error creating new user, try again </p>";
			displayRegisterForm();
		}
	}
}else displayRegisterForm();

function displayRegisterForm() {
?>
	<p> Please enter the following information about yourself </p>
	<form action="register.php" method="post">
		FirstName: <input type="text" name="firstname" />  LastName: <input type="text" name="lastname" /><br>
		Username: <input type="text" name="username" /> <br>
		Password: <input type="password" name="password" /> <br>
		Confirm Password: <input type="password" /> <br>
		<input type="submit" value="Submit" />
	</form>
<?php
}
?>
</body>
</html>
		
