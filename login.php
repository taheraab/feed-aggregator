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
	<title> Login to Feed Reader </title>
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href='http://fonts.googleapis.com/css?family=Vast+Shadow' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="styles/common.css" >
    <script src="//code.jquery.com/jquery.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
    <script src="js/common.js"> </script>
    <script src="js/user.js"></script>
</head>
<body>
  <div class="container">
	<?php include_once ("includes/header.php"); ?>
  <div class="row">
    <div class="col-md-4">
	<div class="text-danger"><?php if (isset($loginErrMsg)) echo $loginErrMsg; ?></div>
    <!-- login panel -->
      <div id="login" class="panel panel-default">
         <div class="panel-heading coloredHeader">
            Login
         </div>
         <div class="panel-body"> 
          <form class="form-horizontal" role="form" method="post" action="manage_user.php?login">
            <div class="form-group">
              <label class="col-md-3 control-label" for="emailId" > Email</label>
              <div class="col-md-9">
                <input type="email" class="form-control input-sm" id="emailId" name="emailId" placeholder="Email" required />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label" for="password" > Password</label>
              <div class="col-md-9">
                <input type="password" class="form-control input-sm" id="password" name="password" placeholder="Password" required />
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-offset-3 col-md-9">
              <button type="submit" class="btn btn-primary btn-sm">Sign in</button>
              </div>
            </div>
          </form>   
          <p><small> New User? <a href="#" onclick="activateSection($('#register')); event.preventDefault();"> Register here</a></small></p>
          <p><small> Forgotton Password? <a href="#" onclick="activateSection($('#resetPassword')); event.preventDefault();" >Reset Password</a></small></p>
         </div>
       </div>
     <!-- register panel -->
      <div id="register" class="panel panel-default hidden">
         <div class="panel-heading coloredHeader">
            Register
         </div>
         <div class="panel-body"> 
          <form class="form-horizontal" role="form" method="post" action="manage_user.php?register" onsubmit="validatePasswords($(this), $('.text-danger'));">
              <div class="form-group">
              <label class="col-md-3 control-label" for="firstname" > Firstname</label>
              <div class="col-md-9">
                <input type="text" class="form-control input-sm" id="firstname" name="firstname" placeholder="Firstname" required />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label" for="lastname" > Lastname</label>
              <div class="col-md-9">
                <input type="text" class="form-control input-sm" id="lastname" name="lastname" placeholder="Lastname" />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label" for="emailId1" > Email</label>
              <div class="col-md-9">
                <input type="email" class="form-control input-sm" id="emailId1" name="emailId" placeholder="Email" required  />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label" for="currentPassword" > Password</label>
              <div class="col-md-9">
                <input type="password" class="form-control input-sm" id="currentPassword" name="password" placeholder="Password" required/>
              </div>
            </div>
             <div class="form-group">
              <label class="col-md-3 control-label" for="confirmPassword" > Confirm Password</label>
              <div class="col-md-9">
                <input type="password" class="form-control input-sm" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required />
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-offset-3 col-md-9">
                <button type="submit" class="btn btn-primary btn-sm">Submit</button>&nbsp; &nbsp;
                <button type="button" class="btn btn-default btn-sm" onclick="activateSection($('#login'));">
                  Cancel</button>
              </div>
            </div>
          </form>   
         </div>
       </div>
 
     <!-- reset password panel -->
       <div id="resetPassword" class="panel panel-default hidden">
         <div class="panel-heading coloredHeader">
            Reset Password
         </div>
         <div class="panel-body"> 
          <form class="form-horizontal" role="form" method="post" action="manage_user.php?sendResetPasswordLink" >
            <div class="form-group">
              <label class="col-md-3 control-label" for="emailId2" > Email</label>
              <div class="col-md-9">
                <input type="email" class="form-control input-sm" id="emailId2" name="emailId" placeholder="Email" required />
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-offset-3 col-md-9">
                <button type="submit" class="btn btn-primary btn-sm">Submit</button>&nbsp; &nbsp;
                <button type="button" class="btn btn-default btn-sm" onclick="activateSection($('#login'));">
                  Cancel</button>
              </div>
            </div>
          </form>   
         </div>
       </div> <!-- panel -->
     </div> <!-- col -->

      <!-- Intro text -->
      <div class="col-md-8">
        <h3> Welcome to Feed Reader! </h3>
        <p> Inspired by Google Reader, this application is designed to aggregate updates (Atom/RSS feed entries) from your favourite websites in one place. </p>
        <h4> Features:</h4>
        <ul>
          <li> Import your subscriptions/feeds (from OPML file or from a Feed URL). </li>
	      <li> View entries per subscription/feed or all-together. </li>
	      <li> Subscriptions are automatically updated periodically, so you can see the latest entries. </li>
	      <li> Maintain entry status as Read, Unread, Starred. </li>
	      <li> Organize subscriptions/feeds into folders. </li>
	      <li> Export your subscriptions into an OPML file. </li>
        </ul>
      </div>
    </div> <!-- row -->

	</div> <!-- container -->
</body>
</html>
