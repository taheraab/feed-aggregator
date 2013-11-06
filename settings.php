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
	<title>Feed Reader</title>
	<link rel="stylesheet" href="styles/settings.css">
	<script src="js/jquery.js"></script>
	<script src="js/common.js"></script>
	<script src="js/settings.js"></script>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>

</head>
<body>	
	<?php require_once "includes/header.php"; ?>
	<div id="content">
		<div>
			<h3> Settings &nbsp; &nbsp;</h3>
			<a href="index.php">&lt;&lt; back to reader </a>
		</div>
		<nav>
			<ul>
				<li name="subscriptions" onclick = "setActiveTab($(this), 'subscriptions');">Subscriptions</li>
				<li name="folders" onclick = "setActiveTab($(this), 'folders');">Folders</li>
				<li name="import" onclick = "setActiveTab($(this), 'import');">Import/Export</li>
				<li name="myAccount" onclick = "setActiveTab($(this), 'myAccount');">My account</li>
			</ul>
		</nav>
		<article>
			<section id="subscriptions" class="hidden">
				<h4>Subscriptions</h4>
				<div class="aggrMenu">Select &nbsp; <input type="radio" name="selectFeeds" 
					onclick="$('#feedList').find('input[type=\'checkbox\']').prop('checked', true);">All</input>
				<input type="radio" name="selectFeeds" 
					onclick="$('#feedList').find('input[type=\'checkbox\']').prop('checked', false);">None</input>&nbsp; &nbsp;
				<form id="subscriptionsForm" action="manage_feeds.php" method="post">
				<button onclick="unsubscribeFeeds();" type="button">Unsubscribe</button> &nbsp; &nbsp;
				<select onchange="moveFeedsToFolder();" id="actions" name="folder">
				</select></form>
				<span class="errMsg"><?php if (isset($subscriptionsErrMsg)) echo $subscriptionsErrMsg; ?></span>
				</div>
				
				<div id="feedList">
					
				</div>		
				<form id="foldersForm" action="manage_feeds.php" method="post"></form>
			</section>
			<section id="import" class="hidden">
				<h4>Import your subscriptions </h4>
				<iframe class="hidden" seamless name="errMsg"></iframe>
				<form enctype="multipart/form-data" method="post" action="import_export.php" target="errMsg" 
					onsubmit="$(this).parent().find('iframe').removeClass('hidden'); ">
					<label for="subscriptionsFile">Select an OPML file </label>
					<input type="file" accept="application/xml" name="subscriptionsFile"> </input> <br />
					<input type="submit" value="Upload"></input>
				</form>
				<hr />
				<h4>Export your subscriptions to an OPML file </h4>
				<form method="post" action="import_export.php?export" >
					<input type="submit" value="Export" </input>
				</form>	
			</section>
			<section id="folders" class="hidden">
				<h4> Folders </h4>
				<div class="aggrMenu" >Select &nbsp; <input type="radio" name="selectFolders" 
					onclick="$('#folderList').find('input[type=\'checkbox\']').prop('checked', true);">All</input>
				<input type="radio" name="selectFolders" 
					onclick="$('#folderList').find('input[type=\'checkbox\']').prop('checked', false);">None</input>&nbsp; &nbsp;
				<img class="delete" onclick="deleteFolders();" src="resources/delete_icon.png" ></img> 
				<span class="errMsg"><?php if (isset($foldersErrMsg)) echo $foldersErrMsg; ?></span>
				</div>
				<div id="folderList">

				</div> 
			</section>
			<section id="myAccount" class="hidden">
				<h4> Change Password </h4>
		        <div class="errMsg"><?php if (isset($myAccountErrMsg)) echo $myAccountErrMsg; ?></div>
   			    <form method="post" action="manage_user.php?changePassword" onsubmit="validatePasswords($(this));" >
   			        Current Password: <input type='password' name='currentPassword' required /><br />
    			    New Password: <input type="password" name="password" required /><br />
    			    Confirm Password: <input type="password" name="confirmPassword" required /><br />
      			  <input type= "submit" value="Submit" />
      	  	    </form>
				<?php if (isset($myAccountMsg)) {
		    		  echo "<p>".$myAccountMsg."</p>";
			    }?>
				
			</section>
		</article>
	</div>
</body>
</html>

