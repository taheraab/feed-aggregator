<?php
include_once "classes/UserManager.php";

session_start();

$userManager = new UserManager();
if (isset($_REQUEST["login"])) {
	$emailId = filter_var($_POST["emailId"], FILTER_SANITIZE_EMAIL);
    if ($userManager->userExists($emailId)) { // check only confirmed users
        $password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
        if (crypt($password, $user->password) == $user->password) {
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
       	if (empty($name) || empty($emailId) || empty($password)) {
			$errMsg = "One or more of required fields (name, email and password) are empty, try again";
		}else {
		    $user = new User();
		    $user->emailId = $emailId;
		    $user->password = crypt($password);
			$user->name = $name;
 	        if ($token = $userManager->sendConfirmationLink($emailId, $name)) {
				$now = new DateTime();
				$user->token = $token;
				$user->tokenTimestamp = $now->getTimestamp(); 
		   	  	if ($userManager->createUser($user)) {
					$msg = "A confirmation email has been sent to given email Id. Click on the link to confirm email before logging in";
		   		}else $errMsg = "Error creating user, please try again";		
		    } else $errMsg = "Confirmation email could not be sent, please try again";
       }
	}
	if (isset($errMsg)) {
		$_SESSION["loginErrMsg"] = $errMsg;
	 	header("Location: ".createRedirectURL("login.php?register"));
    	exit;
	}

}else if (isset($_REQUEST["resetPassword"])) {
    $emailId = filter_var($_POST["emailId"], FILTER_SANITIZE_EMAIL);
	if ($user = $userManager->userExists($emailId)) { // check only confirmed users
	    //Send reset password request
		if ($userManager->sendResetPasswordLink($user->id, $user->emailId)) {
			$msg = "Sent passord reset link to given email Id. Complete reset process from link given in email."
		}else $errMsg =  "Error sending reset password request, please try again";
	}else $errMsg = "Error sending reset password request, please try again";
	if (isset($errMsg)) {
		$_SESSION["loginErrMsg"] = $errMsg;
	 	header("Location: ".createRedirectURL("login.php?register"));
    	exit;
	}



}else if (isset($_REQUEST["changePassword"])) {


}

?>
<!DOCTYPE html>
<html>
<head>
    <title> Confirmation </title>
<link rel="stylesheet" href="styles/user.css" >
</head>
<body>
    <?php include_once ("includes/header.php"); ?>
    <p><?php if (isset($msg)) echo $msg; ?></p>
</body>
</html>
