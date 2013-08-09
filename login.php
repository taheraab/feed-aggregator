<?php
include_once "classes/UserManager.php";
// Get login info from session to check if user is already logged in
session_start();
$userManager = new UserManager();
$errMsg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
	$userId = $userManager->userExists($username);
	if ($userId) {
		$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
		if($userManager->authenticate($userId, $password)) {
			// if authentication succeeds, set the current_user in session
			$_SESSION["currentUsername"] = $username;
			$_SESSION["currentUserId"] = $userId;
		}else {
			$errMsg =  "<p> ERROR: Incorrect password, please try again </p>";
		}	
	}else $errMsg =  "<p> ERROR: Username doesn't exist, please try again </p>";
}
if (isset($_SESSION["currentUserId"])) {
	if (isset($_GET["logout"])) {
		unset($_SESSION["currentUsername"]);
		unset($_SESSION["currentUserId"]);
	} else {
		header("Location: ".createRedirectURL("index.php"));
		exit;
	}
}

?>
<!DOCTYPE html>
<html>
<head>
	<title> Login to FeedAggregator </title>
<script src="js/misc.js"></script>
</head>
<body>
	<?php echo $errMsg; ?>
	<form method="post" action="login.php">	
		Username: <input type="text" name="username" /><br>
		Password: <input type="password" name="password" /><br>
		<input type="submit" value="Submit" />
	</form>
	<p> New User? <a href="register.php"> Click here to register </a> </p>
</body>
</html>
