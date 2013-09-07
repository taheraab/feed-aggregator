<?php
include_once "includes/util.php";
include_once "classes/FeedParser.php";
include_once "classes/FeedManager.php";
include_once "classes/FolderManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
	header("Location: ".createRedirectURL("login.php"));
	exit;
}

if (isset($_SESSION["subsErrMsg"])) {
	$subsErrMsg = $_SESSION["subsErrMsg"];
	unset($_SESSION["subsErrMsg"]);
}

if (isset($_SESSION["unsubscribeErrMsg"])) {
	$unsubscribeErrMsg = $_SESSION["unsubscribeErrMsg"];
	unset($_SESSION["unsubscribeErrMsg"]);
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" >
	<title>Feed Reader</title>
	<link rel="stylesheet" href="styles/main.css">
	<script src="js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
	<script src="js/main.js"></script>

</head>
<body>	
	<?php require_once "includes/header.php"; ?>
	<div id="toolbar">
		<div>
			<div class="errMsg"><?php if (isset($subsErrMsg)) echo $subsErrMsg; ?></div>
			<button type="button" name="subscribe" onclick="$('#subsForm').toggleClass('hidden');">Subscribe </button><br />
			<form class="hidden" id="subsForm" method="post" action="manage_feeds.php?subscribeToFeed" 
				onsubmit="$(this).find('input[name=\'folderId\']').val(activeFolderId);" >
				<input type='hidden' name='folderId' />
				Website or Atom/RSS Link: <br /><input type="url" name="url" required />
				<input type="submit" value="Submit" />
			</form> 
		</div>
		<div id="feedMenu">
			View &nbsp;<input type="radio" onchange = "filter = 'all'; filterView();" name="filter" checked >All </input> 
			<input type="radio" onchange= "filter = 'starred'; filterView();" name="filter"  >Starred</input>
			<input type="radio" onchange= "filter = 'unread'; filterView();" name="filter"  >Unread</input>
			<input type="radio" onchange= "filter= 'read'; filterView();" name = "filter"  >Read</input>
		</div>
		<div id="unsubscribe">
			<form method="post" action="manage_feeds.php?unsubscribeFeed" 
				onsubmit="$(this).find('input[name=\'feedId\']').val(myFeeds[activeFeedIndex].id);" >
				<input type="hidden" name="feedId" ></input>
				<input type="submit" value= "Unsubscribe" ></input>
			</form>
			<span class="errMsg"><?php if (isset($unsubscribeErrMsg)) echo $unsubscribeErrMsg ?></span>
		</div>
		<div id="settingsMenu">
			<button type="button" onclick = "$(this).next().toggleClass('hidden');"> </button>
			<ul class="hidden"> 
				<li onclick = "window.location = 'settings.php';">Reader settings</li>
			</ul>
		</diV>
		
	</div>
	<div id="content">
		<article id="entryList">
			<p> You are currently not subscribed to any Feeds. 
				<a href="settings.php?import">Import</a> an OPML file</a> or 
				<span class="link" onclick="$('button[name=\'subscribe\']').click();" >Subscribe</span> to a feed.
			</p> 
		</article>
		<nav>
			<p><a href="index.php"> Home </a></p>
			<p id="allItems" onclick = "setActiveFeed(-1, $(this));"> <span> All Items </span> 
					<span></span></p>
			<ul>
				<li id="subscriptions" ><div onclick="$(this).parent().toggleClass('collapsed');"> <span ></span><span>Subscriptions</span></div>
					<img id="newFolder" src="resources/new_folder_icon.png" onclick="createFolder();" /> 
					<ul id="feedList">
					</ul>
				</li>
			</ul>
		</nav>
	</div>
</body>
</html>

