<?php
include_once "classes/FeedManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
    header("Location: ".createRedirectURL("login.php"));
    exit;
}

$feedManager = FeedManager::getInstance();
$result = false;
header("Content-type: application/json");
if (isset($_GET["getFeeds"])) {
	$feeds = $feedManager->getFeeds($_SESSION["currentUserId"]);
	$result = json_encode($feeds);
}else if (isset($_GET["getEntries"])) {
	$feedId = htmlspecialchars($_GET["feedId"]);
	$entries = $feedManager->getEntries($_SESSION["currentUserId"], $feedId);
	$result = json_encode($entries);

}

echo $result; 

?>
