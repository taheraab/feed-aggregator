<?php
include_once "classes/FeedManager.php";
include_once "classes/EntryManager.php";
include_once "classes/FolderManager.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
    header("Location: ".createRedirectURL("login.php"));
    exit;
}

$result = false;
header("Content-type: application/json");
if (isset($_GET["getFeeds"])) {
	$feedManager = new FeedManager();
	$feeds = $feedManager->getFeeds($_SESSION["currentUserId"]);
	$result = json_encode($feeds);
}else if (isset($_GET["getEntries"])) {
	$entryManager = new EntryManager();
	$feedId = filter_var($_GET["feedId"], FILTER_SANITIZE_NUMBER_INT);
	$entryPageSize = filter_var($_GET["entryPageSize"], FILTER_SANITIZE_NUMBER_INT);
	$lastLoadedEntryId = filter_var($_GET["lastLoadedEntryId"], FILTER_SANITIZE_NUMBER_INT);
	$entries = $entryManager->getEntries($_SESSION["currentUserId"], $feedId, $entryPageSize, $lastLoadedEntryId);
	$result = json_encode($entries);
}else if (isset($_REQUEST["updateEntries"])) {
	$entryManager = new EntryManager();
	$entries = json_decode($HTTP_RAW_POST_DATA, false, 3);
	if ($entries != null) {
		$result = $entryManager->updateUserEntryRelRecs($_SESSION["currentUserId"], $entries);
	}
	$result = json_encode($result);	
}else if (isset($_REQUEST["getFeedsForSettings"])) {
	$feedManager = new FeedManager();
	$feeds = $feedManager->getFeedsForSettings($_SESSION["currentUserId"]);
	$result = json_encode($feeds);
}else if (isset($_REQUEST["getFolders"])) {
	$folderManager = new FolderManager();
	$folders = $folderManager->getFolders($_SESSION["currentUserId"]);
	$result = json_encode($folders);
}else if (isset($_REQUEST["createFolder"])) {
	$folderManager = new FolderManager();
	$name = htmlspecialchars($_GET["name"]);
	if (!empty($name)) {
		$result = $folderManager->folderExists($_SESSION["currentUserId"], $name);
		if ($result == false)  
			$result = $folderManager->createFolder($_SESSION["currentUserId"], $name);
		else $result = false; // folder already exists
	}
	$result = json_encode($result);
}

echo $result; 

?>
