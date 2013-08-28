<?php
include_once "classes/UserManager.php";

if (isset($_GET["token"])) {
	$token = filter_var($_GET["token"], FILTER_SANITIZE_STRING);
	$userManager = new UserManager();
	if ($userManager->confirmUser($token)) $msg = "Email confirmed.";
	else $errMsg = "Error confirming email, token invalid.";
}else {
	  header("Location: ".createRedirectURL("login.php"));
      exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title> Confirmation </title>
<link rel="stylesheet" href="styles/user.css" >
</head>
<body>
    <?php include_once ("includes/header.php"); 
	if (isset($msg)) {
		echo "<p>".$msg."</p><a href='login.php'>Proceed to login</a>";
	}else if (isset($errMsg)) {
		echo "<p class='errMsg'>".$errMsg."</p><a href='login.php?register'>Register Again</a>";
	}
	?>
</body>
</html>

