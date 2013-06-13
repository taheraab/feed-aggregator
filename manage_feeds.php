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
}else if (isset($_REQUEST["updateEntries"])) {
	$entries = json_decode($HTTP_RAW_POST_DATA, false, 3);
	if ($entries != null) {
		$result = $feedManager->updateUserEntryRelRecs($_SESSION["currentUserId"], $entries);
	}	
}

echo $result; 

?>
