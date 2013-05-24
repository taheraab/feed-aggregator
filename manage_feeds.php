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
}

echo $result; 

?>
