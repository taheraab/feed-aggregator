<?php
include_once "classes/UserManager.php";

session_start();

if (isset($_REQUEST["token"])) {
	if (isset($_SESSION["resetPasswordErrMsg"])) {
		$errMsg = $_SESSION["resetPasswordErrMsg"];
		unset($_SESSION["resetPasswordErrMsg"]);
	} 
	if (isset($_SESSION["resetPasswordMsg"])) {
		$msg = $_SESSION["resetPasswordMsg"];
		unset($_SESSION["resetPasswordMsg"]);
	}
}else {
	  header("Location: ".createRedirectURL("login.php"));
      exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title> Reset Password </title>
<link rel="stylesheet" href="styles/user.css" >
<script src="js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
<script src="js/common.js" > </script>
</head>
<body>
    <?php include_once ("includes/header.php"); ?> 
	<h4> Reset Password </h4>
	<?php if (isset($msg)) {
		echo "<p>".$msg."</p><a href='login.php'>Proceed to login</a>";
	}else { ?>
		<div class="errMsg"><?php if (isset($errMsg)) echo $errMsg; ?></div>
		<form method="post" action="manage_user.php?resetPassword" onsubmit="validatePasswords($(this));" >
		<?php echo "<input type='hidden' name='token' value='".$_GET["token"]."' />"; ?>
		Password: <input type="password" name="password" required /><br />
		Confirm Password: <input type="password" name="confirmPassword" required /><br />
		<input type= "submit" value="Submit" />
		</form>
	<?php } ?>
</body>
</html>

