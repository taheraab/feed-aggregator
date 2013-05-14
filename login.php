<!DOCTYPE html>
<html>
<head>
	<title> Login to FeedAggregator </title>
<script src="js/misc.js"></script>
</head>
<body>
<?php
include "classes/UserManager.php";
// Get login info from session to check if user is already logged in
session_start();
$userManager = UserManager::getInstance();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$username = htmlspecialchars($_POST["username"]);
	$userId = $userManager->userExists($username);
	if ($userId) {
		$password = htmlspecialchars($_POST["password"]);
		if($userManager->authenticate($userId, $password)) {
			// if authentication succeeds, set the current_user in session
			$_SESSION["currentUsername"] = $username;
			$_SESSION["currentUserId"] = $userId;
		}else {
			echo "<p> ERROR: Incorrect password, please try again </p>";
		}	
	}else echo "<p> ERROR: Username doesn't exist, please try again </p>";
}
if (isset($_SESSION["currentUserId"])) {
	if (isset($_GET["logout"]) && htmlspecialchars($_GET["logout"])) {
		unset($_SESSION["currentUsername"]);
		unset($_SESSION["currentUserId"]);
		displayLoginForm();
	} else {
	echo "<p> Welcome {$_SESSION["currentUsername"]}</p>
		  <button type=\"button\" onclick=\"gotoPage('login.php?logout=1')\">Logout</button> ";
	}
}else displayLoginForm();


function displayLoginForm() {
?>
	<form method="post" action="login.php">	
		Username: <input type="text" name="username" /><br>
		Password: <input type="password" name="password" /><br>
		<input type="submit" value="Submit" />
	</form>
	<p> New User? <a href="register.php"> Click here to register </a> </p>
<?php
}
?>
</body>
</html>
