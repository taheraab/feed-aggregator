<?php
include_once "includes/util.php";
include_once "classes/FeedParser.php";
include_once "classes/FeedManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
	header("Location: ".createRedirectURL("login.php"));
	exit;
}

if (isset($_SESSION["subscriptionsErrMsg"])) {
	$subscriptionsErrMsg = $_SESSION["subscriptionsErrMsg"];
	unset($_SESSION["subscriptionsErrMsg"]);
}

if (isset($_SESSION["foldersErrMsg"])) {
	$foldersErrMsg = $_SESSION["foldersErrMsg"];
	unset($_SESSION["foldersErrMsg"]);
}

if (isset($_SESSION["myAccountErrMsg"])) {
	$myAccountErrMsg = $_SESSION["myAccountErrMsg"];
	unset($_SESSION["myAccountErrMsg"]);
}

if (isset($_SESSION["myAccountMsg"])) {
	$myAccountMsg = $_SESSION["myAccountMsg"];
	unset($_SESSION["myAccountMsg"]);
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" >
	<title>Feed Reader Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="//code.jquery.com/jquery.js"></script>
	<!-- Bootstrap -->	
    <link href='http://fonts.googleapis.com/css?family=Vast+Shadow' rel='stylesheet' type='text/css'>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet" media="screen">
	<link rel="stylesheet" href="styles/settings.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
	<script src="js/common.js"></script>
	<script src="js/settings.js"></script>
</head>
<body>	
 <div class="container">
  <?php require_once "includes/header.php"; ?>
  <ul class="nav nav-tabs" id="settingsNav">
    <li><a href="#subscriptions" data-toggle="tab">Subscriptions</a></li>
    <li><a href="#folders" data-toggle="tab">Folders</a></li>
    <li><a href="#import" data-toggle="tab">Import/Export</a></li>
    <li><a href="#myAccount" data-toggle="tab">My Account</a></li>
  </ul>
  <!-- tab content -->
  <div class="tab-content">
  <div class="tab-pane" id="subscriptions" >
  <div class="panel panel-default">
    <div class="panel-heading coloredHeader">
      <form id="subscriptionsForm" class="form-inline" role="form" method="post">
      Select &nbsp;
      <label class="radio-inline">
        <input type="radio" name="selectFeeds" onchange="$('#feedList').find('input[type=\'checkbox\']').prop('checked', true);"> All
      </label>
      <label class= "radio-inline">
        <input type="radio" name="selectFeeds" onchange="$('#feedList').find('input[type=\'checkbox\']').prop('checked', false);"> None
      </label>&nbsp;&nbsp;
        <button type="button" class="btn btn-default btn-sm" onclick="unsubscribeFeeds();">Unsubscribe</button>&nbsp;&nbsp;
        <div class="form-group">
          <select class="form-control input-sm" id="actions" name="folder" onchange="moveFeedsToFolder();">
          </select>     
        </div>
        &nbsp;&nbsp;&nbsp; 
        <span class="text-danger"><?php if (isset($subscriptionsErrMsg)) echo $subscriptionsErrMsg; ?></span>
      </form>
    </div>
    <div id="feedList" class="panel-body">
       
    </div>
  </div> <!-- panel -->
  </div> <!-- tab pane -->

  <!-- Folders tab pane -->
  <div class="tab-pane" id="folders">
  <div class="panel panel-default">
    <div class="panel-heading coloredHeader">
      Select &nbsp;
      <label class="radio-inline">
        <input type="radio" name='selectFolders' onclick="$('#folderList').find('input[type=\'checkbox\']').prop('checked', true);"> All
      </label>
      <label class= "radio-inline">
        <input type="radio" name='selectFolders' onclick="$('#folderList').find('input[type=\'checkbox\']').prop('checked', false);"> None
      </label>&nbsp;&nbsp;&nbsp;
        <a href="#" onclick="deleteFolders(); return false;"><span class="glyphicon glyphicon-trash"></span></a>&nbsp;&nbsp;&nbsp;
        <span class="text-danger"><?php if (isset($foldersErrMsg)) echo $foldersErrMsg; ?></span>
    </div>
    <form id="foldersForm"  method="post" ></form>
    <div id='folderList' class="panel-body">

     </div> <!-- panel body-->
     </form>
    </div> <!-- panel -->
  </div> <!-- tab-pane -->

  <!-- Import/Export tab pane -->
  <div class="tab-pane" id="import">
    <div class="row">
    <div class="col-md-6">
      <div class="panel panel-default">
        <div class="panel-heading coloredHeader">
          Import your subscriptions 
        </div>
        <div class="panel-body">
          <iframe class="hidden" seamless name="errMsg"></iframe>
          <form enctype="multipart/form-data" method="post" action="import_export.php" target="errMsg" role="form" 
            onsubmit="$(this).parent().find('iframe[name=\'errMsg\']').removeClass('hidden');">
            <div class="form-group">
              <label for="subscrptionsFile">OPML file </label>
                <input id="subscriptionsFile"  name="subscriptionsFile" accept="application/xml" type="file" />
            </div>
            <button type="submit" class="btn btn-default btn-sm">Upload</button>
          </form>    
        </div>
      </div>
    </div>  
    </div>
   
    <div class="row">
    <div class="col-md-6">
      <div class="panel panel-default">
        <div class="panel-heading coloredHeader">
          Export your subscriptions to an OPML file
        </div>
        <div class="panel-body">
          <form method="post" action="import_export.php?export" role="form">
            <button type="submit" class="btn btn-default btn-sm">Export</button>
          </form>
        </div>
      </div>
    </div>
    </div>
 
  </div>

  <!-- My account tab pane -->
  <div class="tab-pane" id="myAccount">
    <div class="row">
    <div class="col-md-6">
      <div class="panel panel-default">
        <div class="panel-heading coloredHeader">
          Change Password 
        </div>
        <div class="panel-body">
          <div class="text-danger"><?php if (isset($myAccountErrMsg)) echo $myAccountErrMsg; ?></div>
          <form class="form-horizontal" role="form" method="post" action="manage_user.php?changePassword" 
            onsubmit="validatePasswords($(this), $(this.parent().find('.text-danger')));">
            <div class="form-group">
              <label class="col-md-4 control-label" for="currentPassword" > Current Password</label>
              <div class="col-md-6">
                <input type="password" class="form-control input-sm" id="currentPassword" name="currentPassword" required placeholder="Current Password" />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-4 control-label" for="newPassword" > New Password</label>
              <div class="col-md-6">
                <input type="password" class="form-control input-sm" id="newPassword" name="password" required placeholder="New Password" />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-4 control-label" for="confirmPassword" > Confirm Password</label>
              <div class="col-md-6">
                <input type="password" class="form-control input-sm" id="confirmPassword" name="confirmPassword" required placeholder="Confirm Password" />
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-offset-4 col-md-6">
              <button type="submit" class="btn btn-default btn-sm">Submit</button>
              </div>
            </div>
          </form>   
          <div class="text-success"><?php if(isset($myAccountMsg)) echo $myAccountMsg; ?></div> 
        </div>
      </div>
    </div>  
    </div>
  
  </div>

  </div> <!-- tab content -->
	</div> <!-- container -->
    <?php include_once "includes/dialogs.php"; ?>
            

</body>
</html>

