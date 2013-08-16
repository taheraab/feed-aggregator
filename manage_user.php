<?php
include_once "classes/UserManager.php";

session_start();

$userManager = new UserManager();
if (isset($_REQUEST["login"])) {
	error_log("in login", 0);
	$username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
    $userId = $userManager->userExists($username);
    if ($userId) {
        $password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
        if($userManager->authenticate($userId, $password)) {
            // if authentication succeeds, set the current_user in session
            $_SESSION["currentUsername"] = $username;
            $_SESSION["currentUserId"] = $userId;
        }else {
            $errMsg =  "Incorrect password, please try again";
        }
    }else $errMsg =  "Username doesn't exist, please try again";

	if (isset($errMsg)) $_SESSION["loginErrMsg"] = $errMsg;
 	header("Location: ".createRedirectURL("login.php"));
    exit;

}else if (isset($_REQUEST["logout"])) {
 	unset($_SESSION["currentUsername"]);
    unset($_SESSION["currentUserId"]);
	header("Location: ".createRedirectURL("login.php"));
    exit;

}else if (isset($_REQUEST["register"])) {
    $username = filter_var($_POST["username"], FILTER_SANITIZE_STRING);
    if ($userManager->userExists($username)) {
        $errMsg = "Username not available, please try again ";
    } else {
        //Add user to the database and session  
        if (isset($_POST["firstname"])) $name = filter_var($_POST["firstname"], FILTER_SANITIZE_STRING);
        if (isset($_POST["lastname"])) $name = $name." ".filter_var($_POST["lastname"], FILTER_SANITIZE_STRING);
		$emailId = filter_var($_POST["emailId"], FILTER_SANITIZE_EMAIL);
		$password = filter_var($_POST["password"], FILTER_SANITIZE_STRING);
       	if (empty($username) || empty($emailId) || empty($password)) {
			$errMsg = "One or more of required fields (username, email and password) are empty, try again";
		}else {
		   $user = new User($name, $username, $password, $emailId);
 	       $userId = $userManager->createUser($user);
    	    if ($userId) {// if creation was successful
        	    $_SESSION["currentUsername"] = $user->getUsername();
            	$_SESSION["currentUserId"] = $userId;
       	    	header("Location: ".createRedirectURL("login.php"));
        	    exit; 
	       }else $errMsg = "Error creating new user, please try again ";
       }
	}
	if (isset($errMsg)) $_SESSION["loginErrMsg"] = $errMsg;
 	header("Location: ".createRedirectURL("login.php?register"));
    exit;

}else if (isset($_REQUEST["resetPassword"])) {


}else if (isset($_REQUEST["changePassword"])) {


}

?>
