<?php
include_once dirname(__FILE__)."/../classes/OPMLReader.php";
include_once dirname(__FILE__)."/../classes/FeedManager.php";
include_once dirname(__FILE__)."/../classes/FeedParser.php";

$userId = $argv[1];
$rootFolderId = $argv[2];
$filename = "files/import_subscriptions".$userId;

$opmlReader = new OPMLReader();
$feedUrls = $opmlReader->parseFile($filename);
if ($feedUrls) {
  $feedParser = new FeedParser();
  $feedManager = new FeedManager();
  foreach ($feedUrls as $feedUrl) {
		if ($feed = $feedParser->parseFeed($feedUrl)) {
    	if (!$feedManager->createFeed($userId, $rootFolderId, $feed)) 
				echo "Error subscribing to  ".$feedUrl;
			else 
				echo "Subscription successful for ".$feedUrl;
    }else 
			echo "Error parsing ".$feedUrl."\n";
	}  

}else 
	echo "Error parsing subscriptions file ".$filename."\n";
    
?>
