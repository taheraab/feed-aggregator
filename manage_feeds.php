<?php
include_once "classes/FeedManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
    header("Location: ".createRedirectURL("login.php"));
    exit;
}


$result = false;
header("Content-type: application/json");
if (isset($_GET["getFeeds"])) {
	$feedManager = FeedManager::getInstance();
	$feeds = $feedManager->getFeeds($_SESSION["currentUserId"]);
	$result = json_encode($feeds);
}else if (isset($_GET["getEntries"])) {
	$entryManager = EntryManager::getInstance();
	$feedId = filter_var($_GET["feedId"], FILTER_SANITIZE_NUMBER_INT);
	$entryPageSize = filter_var($_GET["entryPageSize"], FILTER_SANITIZE_NUMBER_INT);
	$lastLoadedEntryId = filter_var($_GET["lastLoadedEntryId"], FILTER_SANITIZE_NUMBER_INT);
	$entries = $entryManager->getEntries($_SESSION["currentUserId"], $feedId, $entryPageSize, $lastLoadedEntryId);
	$result = json_encode($entries);
}else if (isset($_REQUEST["updateEntries"])) {
	$entryManager = EntryManager::getInstance();
	$entries = json_decode($HTTP_RAW_POST_DATA, false, 3);
	if ($entries != null) {
		$result = $entryManager->updateUserEntryRelRecs($_SESSION["currentUserId"], $entries);
	}	
}else if (isset($_REQUEST["getFeedsForSettings"])) {
	$feedManager = FeedManager::getInstance();
	$feeds = $feedManager->getFeedsForSettings($_SESSION["currentUserId"]);
	$result = json_encode($feeds);
}else if (isset($_REQUEST["getFolders"])) {
	$folderManager = FolderManager::getInstance();
	$folders = $folderManager->getFolders($_SESSION["currentUserId"]);
	$result = json_encode($folders);
}

echo $result; 

?>
