<?php
include_once dirname(__FILE__)."/../classes/OPMLReader.php";
include_once dirname(__FILE__)."/../classes/FeedManager.php";
include_once dirname(__FILE__)."/../classes/FeedParser.php";
include_once dirname(__FILE__)."/../classes/FolderManager.php";

$userId = $argv[1];
$filename = "files/import_subscriptions".$userId;

$opmlReader = new OPMLReader();
$feedUrls = $opmlReader->parseFile($filename);
if ($feedUrls) {
  $feedParser = new FeedParser();
  $feedManager = new FeedManager();
  $folderManager = new FolderManager();

  echo "Importing...\n";
  $errMsg = "";
  foreach ($feedUrls as $folderName => $xmlUrls) {
		$folderId = $folderManager->folderExists($userId, $folderName);
		if (!$folderId) $folderId = $folderManager->createFolder($userId, $folderName); 
		if ($folderId) {
			foreach($xmlUrls as $xmlUrl) {
				if ($feed = $feedParser->parseFeed($xmlUrl)) {
    				if (!$feedManager->createFeed($userId, $folderId, $feed)) $errMsg = "Error creating subscription, please try again";
	  			}else $errMsg = "Invalid Atom or RSS xml";
				echo $xmlUrl."   ".$errMsg."\n";
			}
		}else echo "Error creating folder: ".$folderName."\n";
	}
	echo "Import Complete\n";  
}else echo "Error parsing subscriptions file (not a valid OPML file?): ".$filename."\n";
    
?>
