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
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" >
	<title>Feed Reader</title>
	<link rel="stylesheet" href="styles/settings.css">
	<script src="js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
	<script src="js/settings.js"></script>
	<script src="js/misc.js"></script>

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
				<iframe seamless name="errMsg"></iframe>
				<form enctype="multipart/form-data" method="post" action="import_export.php" target="errMsg" 
					onsubmit="$(this).find('input[name=\'rootFolderId\']').val(rootId); ">
				<label for="subscriptionsFile">Select an OPML file </label>
					<input type="file" accept="application/xml" name="subscriptionsFile"> </input> <br />
				<input type="submit" value="Upload"></input>
				<input type="hidden" name="rootFolderId" > </input>
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
		</article>
	</div>
</body>
</html>

