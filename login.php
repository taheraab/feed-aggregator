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
		<section id="loginForm">
			<form method="post" action="manage_user.php?login" >	
				Username: <input type="text" name="username" required /><br>
				Password: <input type="password" name="password" required  /><br>
				<input type="submit" value="Submit" />
			</form>
			<div class="option"> New User? <span class="link" onclick="toggleActiveForm();"> Click here to register </span> </div>
			<div class="option"> Forgotten Password? <span class="link" onclick="activateForm('reset');"> Reset password </span> </div>
		</section>
      	<section class="hidden" id="registerForm">
            <p> Please enter the following information about yourself </p>
                <form action="manage_user.php?register" method="post" onsubmit="validateRegisterInput($(this));" >
                FirstName: <input type="text" name="firstname" /> <br/> 
				LastName: <input type="text" name="lastname" /><br>
                Username: <input type="text" name="username" required /> <br>
				Email: <input type="email" name="emailId"  /> <br />
                Password: <input type="password" name="password" required /> <br />
                Confirm Password: <input type="password" name="confirmPassword" required /> <br />
                <input type="submit" value="Submit" />
                <input type="button" onclick= "toggleActiveForm()" value="Cancel" />
            </form>
        </section>
		<section id="introText">
			<?php include_once "includes/welcome.html" ?> 
		</section>
	</div>
</body>
</html>
