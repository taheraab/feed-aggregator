<?php
include_once "classes/UserManager.php";

session_start();

$userManager = new UserManager();
if (isset($_REQUEST["login"])) {
	$emailId = filter_var($_POST["emailId"], FILTER_SANITIZE_EMAIL);
    if ($user = $userManager->userExists($emailId)) { // check only confirmed users
        $password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
        if ($userManager->authenticateUser($user->password, $password)) {
            // if authentication succeeds, set the current_user in session
            $_SESSION["currentUsername"] = $user->name;
            $_SESSION["currentUserId"] = $user->id;
        }else {
            $errMsg =  "Email Id or password incorrect, please try again";
        }
    }else $errMsg =  "Email Id or password incorrect, please try again";

	if (isset($errMsg)) $_SESSION["loginErrMsg"] = $errMsg;
 	header("Location: ".createRedirectURL("login.php"));
    exit;

}else if (isset($_REQUEST["logout"])) {
 	unset($_SESSION["currentUsername"]);
    unset($_SESSION["currentUserId"]);
	header("Location: ".createRedirectURL("login.php"));
    exit;

}else if (isset($_REQUEST["register"])) {
    $emailId = filter_var($_POST["emailId"], FILTER_SANITIZE_EMAIL);
    if ($userManager->userExists($emailId)) { // check confirmed users
        $errMsg = "Error creating new user, please try again ";
    } else {
        //Add user to the database and session  
        if (isset($_POST["firstname"])) $name = filter_var($_POST["firstname"], FILTER_SANITIZE_STRING);
        if (isset($_POST["lastname"])) $name = $name." ".filter_var($_POST["lastname"], FILTER_SANITIZE_STRING);
		$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
       	if (empty($name) || empty($emailId) || empty($password)) 
			$errMsg = "One or more of required fields (name, email and password) are empty, try again";
		else if (strlen($password) < 8) $errMsg = "Password should be 8 or more chars long";
		else {
		    $user = new User();
		    $user->emailId = $emailId;
		    $user->password = $password;
			$user->name = $name;
 	        if ($token = $userManager->sendConfirmationLink($user)) {
				$now = new DateTime();
				$user->token = $token;
				$user->tokenTimestamp = $now->getTimestamp(); 
		   	  	if ($userManager->createUser($user)) 
					$msg = "A confirmation link has been sent to given email Id. Click on the link to confirm email before logging in.";
		   		else $errMsg = "Error creating user, please try again";		
		    } else $errMsg = "Confirmation email could not be sent, please try again";
       }
	}
	if (isset($errMsg)) {
		$_SESSION["loginErrMsg"] = $errMsg;
	 	header("Location: ".createRedirectURL("login.php?register"));
    	exit;
	}

}else if (isset($_REQUEST["sendResetPasswordLink"])) {
    $emailId = filter_var($_POST["emailId"], FILTER_SANITIZE_EMAIL);
	if ($user = $userManager->userExists($emailId)) { // check only confirmed users
	    //Send reset password request
		if ($userManager->sendResetPasswordLink($user)) 
			$msg = "Sent password reset link to given email Id. Complete reset process from link given in email.";
		else $errMsg = "Error sending reset password request, please try again";
	}else $errMsg = "Error sending reset password request, please try again";
	if (isset($errMsg)) {
		$_SESSION["loginErrMsg"] = $errMsg;
	 	header("Location: ".createRedirectURL("login.php?resetPassword"));
    	exit;
	}



}else if (isset($_REQUEST["resetPassword"])) {
    $token = filter_var($_POST["token"], FILTER_SANITIZE_STRING);
    $password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
    if ($userId = $userManager->isTokenValid($token)) {
		$msg = "valid token";
		// if token is valid, change password
		if (empty($password)) $errMsg = "Password cannot be empty";
		else if (strlen($password) < 8) $errMsg = "Password must be 8 or more chars long";
		else {
	        if ($userManager->changePassword($userId, $password)) $msg = "Password has been successfully reset.";
			else $errMsg = "Couldn't change password, try again";
		}
    }else $errMsg = "Password reset failed, token invalid.";

	if (isset($errMsg)) $_SESSION["resetPasswordErrMsg"] = $errMsg;
	else if (isset($msg)) $_SESSION["resetPasswordMsg"] = $msg;	
	
	header("Location: ".createRedirectURL("reset_password.php?token=".$token));
	exit;    

}else {
	// These options are only available if user is logged in 
	if (!isset($_SESSION["currentUserId"])) {
		header("Location: ".createRedirectURL("login.php"));
		exit; 
	}
	if (isset($_REQUEST["changePassword"])) {
		$currentPassword = filter_var($_POST["currentPassword"], FILTER_SANITIZE_STRING);
		$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
		if (empty($password)) $errMsg = "Password cannot be empty";
		else if (strlen($password) < 8) $errMsg = "Password must be 8 or more chars long";
		else {
          if ($userManager->reAuthenticateUser($_SESSION["currentUserId"], $currentPassword)) {
			// if current password works, change password
			if ($userManager->changePassword($_SESSION["currentUserId"], $password)) $msg = "Password changed successfully.";
			else $errMsg = "Couldn't change password, try again";

		  }else $errMsg = "Authentication failed, try again";
        }
		if (isset($errMsg)) $_SESSION["myAccountErrMsg"] = $errMsg;
		else if (isset($msg)) $_SESSION["myAccountMsg"] = $msg;	
		header("Location: ".createRedirectURL("settings.php?myAccount"));
		exit;    

	}
}

?>
<!DOCTYPE html>
<html>
<head>
    <title> Confirmation </title>
<link rel="stylesheet" href="styles/user.css" >
<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet" media="screen">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
</head>
<body>
    <?php include_once ("includes/header.php"); ?>
    <p><?php if (isset($msg)) echo $msg; ?></p>
</body>
</html>
