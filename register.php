<?php
include_once "classes/UserManager.php";
include_once "includes/util.php";
session_start();
$userManager = new UserManager();
$errMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
	if ($userManager->userExists($username)) {
		$errMsg = "<p> ERROR: Username not available, try again </p>";
	} else {
		//Add user to the database and session	
		if (isset($_POST["firstname"])) $name = filter_var($_POST["firstname"], FILTER_SANITIZE_STRING);
 		if (isset($_POST["lastname"])) $name = $name." ".filter_var($_POST["lastname"], FILTER_SANITIZE_STRING);
		$user = new User($name, $username, filter_var($_POST["password"], FILTER_SANITIZE_STRING));
		$userId = $userManager->createUser($user);
		if ($userId) {// if creation was successful
			$_SESSION["currentUsername"] = $user->getUsername();
			$_SESSION["currentUserId"] = $userId;
			header("Location: ".createRedirectURL("login.php"));
			exit;
			//echo "<button type=\"button\" onclick=\"gotoPage('login.php')\"> Proceed to login </button>";
		}else {
			$errMsg = "<p> Error creating new user, try again </p>";
		}
	}
}

?>
<!DOCTYPE html>
<html>
<head> 
	<title>Register to Feed Aggregator </title>
	<script src="js/misc.js"></script>
</head>
<body>
	<?php echo $errMsg; ?>
	<p> Please enter the following information about yourself </p>
	<form action="register.php" method="post">
		FirstName: <input type="text" name="firstname" />  LastName: <input type="text" name="lastname" /><br>
		Username: <input type="text" name="username" /> <br>
		Password: <input type="password" name="password" /> <br>
		Confirm Password: <input type="password" /> <br>
		<input type="submit" value="Submit" />
	</form>
</body>
</html>
		
