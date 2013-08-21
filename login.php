<?php
include_once "classes/UserManager.php";
// Get login info from session to check if user is already logged in
session_start();

if (isset($_SESSION["loginErrMsg"])) {
    $loginErrMsg = $_SESSION["loginErrMsg"];
    unset($_SESSION["loginErrMsg"]);
}


if (isset($_SESSION["currentUserId"])) {
	header("Location: ".createRedirectURL("index.php"));
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
	<title> Login to FeedAggregator </title>
<link rel="stylesheet" href="styles/user.css" >
<script src="js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
<script src="js/user.js"></script>
</head>
<body>
	<?php include_once ("includes/header.php"); ?>
	<div class="errMsg"><?php if (isset($loginErrMsg)) echo $loginErrMsg; ?></div>
	<div id="loginContent">
		<section id="login">
			<h4> Login </h4>
			<form method="post" action="manage_user.php?login" >	
				Email: <input type="email" name="emailId" required /><br>
				Password: <input type="password" name="password" required  /><br>
				<input type="submit" value="Submit" />
			</form>
			<div class="option"> New User? <span class="link" onclick="activateForm($('#register'));"> Click here to register </span> </div>
			<div class="option"> Forgotten Password? <span class="link" onclick="activateForm($('#resetPassword'));"> Reset password </span> </div>
		</section>
      	<section class="hidden" id="register">
            <h4>Register </h4>
                <form action="manage_user.php?register" method="post" onsubmit="validateRegisterInput($(this));" >
                FirstName: <input type="text" name="firstname" required /> <br/> 
				LastName: <input type="text" name="lastname" /><br>
				Email: <input type="email" name="emailId"  /> <br />
                Password: <input type="password" name="password" required /> <br />
                Confirm Password: <input type="password" name="confirmPassword" required /> <br />
                <input type="submit" value="Submit" />
                <input type="button" onclick= "activateSection($('#login'));" value="Cancel" />
            </form>
        </section>
		<section class="hidden" class="hidden" id="resetPassword">
			<h4> Reset Password <h4>
			<form method="post" action="manage_user.php?resetPassword" >
				Email: <input type="email" name="emailId" required /><br>
				<input type="submit" value="Submit" />
			</form>
		</section>
		<section id="introText">
			<?php include_once "includes/welcome.html" ?> 
		</section>
	</div>
</body>
</html>
