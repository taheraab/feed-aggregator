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
	<link rel="stylesheet" href="styles/main.css">
	<script src="js/jquery-ui-1.10.2/jquery-1.9.1.js"></script>
	<script src="js/main.js"></script>
	<script src="js/misc.js"></script>

</head>
<body>	
	<header>
		<h1>Feed Reader</h1>
		<div>
<?php
	echo "<span> Welcome ".$_SESSION["currentUsername"]."</span>";
	echo "<button type=\"button\" onclick = \"gotoPage('login.php?logout=1')\"> Logout </button>";
?>		
	</header>
		</div>
	<div id="content">
		<article id="entryList">
		</article>
		<nav>
			<?php if (isset($newSubsErrMsg)) echo "<p class=\"errMsg\"> $newSubsErrMsg </p>"; ?>
			<button type="button" onclick="$('#subsForm').toggleClass('hidden');">Subscribe </button><br />
			<form class="hidden" id="subsForm" method="post" action="index.php">
				Atom/RSS Link: <input type="url" name="url" />
				<input type="submit" value="Submit" />
			</form> <br />
			<p><a href="index.php"> Home </a></p>
			<ul id="subsList">
				<li id="allItems"> <a href="#" onclick = 'setActiveFeed(-1, $(this).parent());'> All Items </a> <span></span></li> <br />
				<li> <a href="#">Subscriptions</a> 
					<ul id="feedList">
					</ul>
				</li>
			</ul>
		</nav>
		<aside>
			<p> Aside </p>
		</aside>
	</div>
	<footer>
		<p> Footer </p>
	</footer>
</body>
</html>

