<?php
include_once "classes/FeedManager.php";
include_once "classes/EntryManager.php";
include_once "classes/FolderManager.php";
include_once "classes/FeedParser.php";
include_once "includes/util.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
    header("Location: ".createRedirectURL("login.php"));
    exit;
}

$result = false;
header("Content-type: application/json");
if (isset($_GET["getFeeds"])) {
	$feedManager = new FeedManager();
	$result = $feedManager->getFeeds($_SESSION["currentUserId"]);

}else if (isset($_GET["getEntries"])) {
	$entryManager = new EntryManager();
	$feedId = filter_var($_GET["feedId"], FILTER_SANITIZE_NUMBER_INT);
	$entryPageSize = filter_var($_GET["entryPageSize"], FILTER_SANITIZE_NUMBER_INT);
	$lastLoadedEntryId = filter_var($_GET["lastLoadedEntryId"], FILTER_SANITIZE_NUMBER_INT);
	$result = $entryManager->getEntries($_SESSION["currentUserId"], $feedId, $entryPageSize, $lastLoadedEntryId);

}else if (isset($_GET["getNumUnreadEntries"])) {
	$entryManager = new EntryManager();
	$feedId = filter_var($_GET["feedId"], FILTER_SANITIZE_NUMBER_INT);
	$result = $entryManager->getNumUnreadEntries($_SESSION["currentUserId"], $feedId);

}else if (isset($_REQUEST["updateEntries"])) {
	$entryManager = new EntryManager();
	$entries = json_decode($HTTP_RAW_POST_DATA, false, 3);
	if ($entries != null) {
		$result = $entryManager->updateUserEntryRelRecs($_SESSION["currentUserId"], $entries);
	}

}else if (isset($_REQUEST["getFeedsForSettings"])) {
	$feedManager = new FeedManager();
	$result = $feedManager->getFeedsForSettings($_SESSION["currentUserId"]);

}else if (isset($_REQUEST["changeFolder"])){
	$feedManager = new FeedManager();
	$feedId = filter_var($_GET["feedId"], FILTER_SANITIZE_NUMBER_INT);
	if (isset($_GET["newId"])) {
		$newFolderId = filter_var($_GET["newId"], FILTER_SANITIZE_NUMBER_INT);
	}else {
		$newName = filter_var($_GET["newName"], FILTER_SANITIZE_STRING);
		$newFolderId = createFolder($newName);
	}
	if ($newFolderId) {
		if ($feedManager->changeFolder($_SESSION["currentUserId"], $feedId, $newFolderId)) 
			$result = $newFolderId;
	}

}else if (isset($_REQUEST["unsubscribeFeed"])) {
	$feedManager = new FeedManager();
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		//Called from index.php
	    $feedId = filter_var($_POST["feedId"], FILTER_SANITIZE_NUMBER_INT);
		if (!$feedManager->unsubscribeFeed($_SESSION["currentUserId"], $feedId)) 
			$errMsg = "Unsubscribe failed, please try again";
		if (isset($errMsg)) $_SESSION["unsubscribeErrMsg"] = $errMsg;
	 	header("Location: ".createRedirectURL("index.php"));
    	exit;
	}else {
		$feedId = filter_var($_GET["feedId"], FILTER_SANITIZE_NUMBER_INT);
		$result = $feedManager->unsubscribeFeed($_SESSION["currentUserId"], $feedId);
	}

}else if (isset($_REQUEST["getFolders"])) {
	$folderManager = new FolderManager();
	$result = $folderManager->getFolders($_SESSION["currentUserId"]);

}else if (isset($_REQUEST["createFolder"])) {
	$folderManager = new FolderManager();
	$name = filter_var($_GET["name"], FILTER_SANITIZE_STRING);
	$result = createFolder($name);

}else if (isset($_REQUEST["deleteFolder"])) {
	$folderManager = new FolderManager();
	$folderId = filter_var($_GET["folderId"], FILTER_SANITIZE_NUMBER_INT);
	$result = $folderManager->deleteFolder($_SESSION["currentUserId"], $folderId);

}else if (isset($_REQUEST["renameFolder"])) {
	$folderManager = new FolderManager();
	$folderId = filter_var($_GET["folderId"], FILTER_SANITIZE_NUMBER_INT);
	$newName = filter_var($_GET["newName"], FILTER_SANITIZE_STRING);
	$result = $folderManager->renameFolder($_SESSION["currentUserId"], $folderId, $newName);

}else if (isset($_REQUEST["subscribeToFeed"])) {
	$folderId = filter_var($_POST["folderId"], FILTER_SANITIZE_NUMBER_INT);
    $feedParser = new FeedParser();
    $feedManager = new FeedManager();
    $url = filter_var($_POST["url"], FILTER_SANITIZE_STRING);
    $feedUrl = getFeedUrlFromHtml($url);
    if (empty($feedUrl)) $errMsg = "Couldn't find a Atom/RSS Url in given link";
    else {
        $feed = $feedParser->parseFeed($feedUrl);
        if ($feed) {
           if(!$feedManager->createFeed($_SESSION["currentUserId"], $folderId, $feed)) {
               $errMsg = "Couldn't create feed, please try again";
           }
        }else {
           $errMsg = "Invalid Atom/RSS xml";
        }
    }
	if (isset($errMsg)) $_SESSION["subsErrMsg"] = $errMsg;
 	header("Location: ".createRedirectURL("index.php"));
    exit;

}else if (isset($_REQUEST["unsubscribeFeeds"])) {
    $feedManager = new FeedManager();
    foreach ($_POST["feedIds"] as $feedId) {
	    $feedId = filter_var($feedId, FILTER_SANITIZE_NUMBER_INT);
		if (!$feedManager->unsubscribeFeed($_SESSION["currentUserId"], $feedId)) 
			$errMsg = "Unsubscribe for some or all feeds failed, please try again";
    }
	if (isset($errMsg)) $_SESSION["subscriptionsErrMsg"] = $errMsg;
 	header("Location: ".createRedirectURL("settings.php?subscriptions"));
    exit;

}else if (isset($_REQUEST["moveFeedsToFolder"])) {
	$feedManager = new FeedManager();
	$folder = $_POST["folder"];
	// Check if it is a folderId or a name
	if (is_numeric($folder)) {
		$newFolderId = filter_var($folder, FILTER_SANITIZE_NUMBER_INT);
	}else {
		$newFolderId = createFolder(filter_var($folder, FILTER_SANITIZE_STRING));
	}
	if ($newFolderId) {
		foreach ($_POST["feedIds"] as $feedId) {
			$feedId = filter_var($feedId, FILTER_SANITIZE_NUMBER_INT);
			if (!$feedManager->changeFolder($_SESSION["currentUserId"], $feedId, $newFolderId)) 
				$errMsg = "Move of some or all feeds to selected folder failed, please try again";
		}
	}else {
		$errMsg = "Couldn't create new folder or invalid folder id";
	}
	
	if (isset($errMsg)) $_SESSION["subscriptionsErrMsg"] = $errMsg;
 	header("Location: ".createRedirectURL("settings.php?subscriptions"));
    exit;

}else if (isset($_REQUEST["deleteFolders"])) {
    $folderManager = new FolderManager();
    foreach ($_POST["folderIds"] as $folderId) {
	    $folderId = filter_var($folderId, FILTER_SANITIZE_NUMBER_INT);
		if (!$folderManager->deleteFolder($_SESSION["currentUserId"], $folderId)) 
			$errMsg = "Unsubscribe for some or all feeds failed, please try again";
    }
	$errMsg = "something";
	if (isset($errMsg)) $_SESSION["foldersErrMsg"] = $errMsg;
 	header("Location: ".createRedirectURL("settings.php?folders"));
    exit;

}



// Calls folderManager->createFolder and returns it's result
function createFolder($name) {
	if (!strcasecmp($name, "root")) return false;
	$folderManager = new FolderManager();
	$result = $folderManager->folderExists($_SESSION["currentUserId"], $name);
	if ($result == false)  {
		return $folderManager->createFolder($_SESSION["currentUserId"], $name);
	}
	
	return false;

}

echo json_encode($result);

?>
