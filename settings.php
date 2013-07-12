<?php
include_once "includes/util.php";
include_once "classes/FeedParser.php";
include_once "classes/FeedManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
	header("Location: ".createRedirectURL("login.php"));
	exit;
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
		<h3> Settings &nbsp; &nbsp;</h3>
		<a href="index.php">&lt;&lt; back to reader </a>
		<nav>
			<ul>
				<li onclick = "setActiveTab($(this), 'subscriptions');">Subscriptions</li>
				<li onclick = "setActiveTab($(this), 'folders');">Folders</li>
				<li onclick = "setActiveTab($(this), 'import');">Import/Export</li>
			</ul>
		</nav>
		<article>
			<section id="subscriptions" class="hidden">
				<h4>Subscriptions</h4>
				Select &nbsp; <input type="radio" name="selectFeeds" value="all">All</input>
				<input type="radio" name="selectFeeds" value="none">None</input>&nbsp; &nbsp;
				<button type="button">Unsubscribe</button> &nbsp; &nbsp;
				<select id="actions" name="actions">
					<option selected>Add to Folder</option>
					<option value="new">New Folder</option>
				</select><br />
				<div id="feedList">
					
				</div>		
			</section>
			<section id="import" class="hidden">
				<h4>Import your subscriptions </h4>
				<form method="post" action="settings.php">
				<label for="subscriptionFile">Select an OPML file </label><input type="file" name="subscriptionFile"> </input> <br />
				<input type="submit" value="Upload"></input>
			</section>
			<section id="folders">
				<h4> Folders </h4>
				<div id="folderList">

				</div> 
			</section>
		</article>
	</div>
</body>
</html>

