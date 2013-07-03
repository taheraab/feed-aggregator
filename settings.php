<?php
include_once "includes/util.php";
include_once "classes/FeedParser.php";
include_once "classes/FeedManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
	header("Location: ".createRedirectURL("login.php"));
	exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	// Received a new subscription request
	if (isset($_POST["url"])) {
		$feedParser = new FeedParser();
		$feedManager = FeedManager::getInstance();
		$feed = $feedParser->parseFeed(htmlspecialchars($_POST["url"]));	
		if ($feed) {
			if(!$feedManager->createFeed($_SESSION["currentUserId"], $feed)) {
				$newSubsErrMsg = "Couldn't create feed, try again";
			}
		}else {
			$newSubsErrMsg = "Couldn't parse feed, try again";
		}

	}
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
				<li onclick = "setActiveTab($(this), 'prefs');">Preferences</li>
				<li onclick = "setActiveTab($(this), 'subscriptions');">Subscriptions</li>
				<li onclick = "setActiveTab($(this), 'import');">Import/Export</li>
			</ul>
		</nav>
		<article>
			<section id="prefs" class="hidden">
				Prefs
			</section>
			<section id="subscriptions" class="hidden">
				Subscriptions
			</section>
			<section id="import" class="hidden">
				<h4>Import your subscriptions </h4>
				<form method="post" action="settings.php">
				<label for="subscriptionFile">Select an OPML file </label><input type="file" name="subscriptionFile"> </input> <br />
				<input type="submit" value="Upload"></input>
			</section>
		</article>
	</div>
</body>
</html>

