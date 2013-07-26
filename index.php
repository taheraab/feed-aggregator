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
	<script src="js/misc.js"></script>

</head>
<body>	
	<?php require_once "includes/header.php"; ?>
	<div id="toolbar">
		<div>
			<div class="errMsg"><?php if (isset($subsErrMsg)) echo $subsErrMsg; ?></div>
			<button type="button" onclick="$('#subsForm').toggleClass('hidden');">Subscribe </button><br />
			<form class="hidden" id="subsForm" method="post" action="manage_feeds.php?subscribeToFeed" onsubmit="setFolderId($(this))" >
				<input type='hidden' name='folderId' />
				Atom/RSS Link: <br /><input type="url" name="url" />
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
				<li>Something Else Like</li>
			</ul>
		</diV>
		
	</div>
	<div id="content">
		<article id="entryList">
		</article>
		<nav>
			<p><a href="index.php"> Home </a></p>
			<ul>
				<li id="allItems" onclick = "setActiveFeed(-1, $(this));"> <span> All Items </span> 
					<span></span></li><br />
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

