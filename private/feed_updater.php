<?php
// Ticks use required for signal handling
declare (ticks = 1);
// This script runs as a daemon on the server and periodically updates the feeds for all users
include_once "classes/FeedParser.php";
include_once "classes/FeedManager.php";

$pid = pcntl_fork();
if ($pid == -1) {
	exit ("Could not fork");
}else if ($pid) {
	//In parent
	exit("Successfully created a child process with id: ".$pid);
}else {

	// Redirect STDIN, STDOUT, STDERR
	fclose(STDIN);
	fclose(STDOUT);
	fclose(STDERR);

	$stdin = fopen("/dev/null", "r");
	$stdout = fopen("log/feed_updater_log", "w");
	$stderr = $stdout;

// In child, detach from parent
	if (posix_setsid() == -1) {
		exit("Could not detach from parent process");
	}
	$myPID = posix_getpid();
	// Handle signals
	pcntl_signal(SIGTERM, "signalHandler");

    
	// Write pid to a file
	$fd = fopen("files/pid", "c");
	if (!$fd) {
		exit("Couldn't open pid file");
	}
	// Check if an instance is already running
	if (!flock($fd, LOCK_EX | LOCK_NB)) {
		exit("An instance is already running");
	}
	ftruncate($fd, 0);
	fwrite($fd, "$myPID");
	fflush($fd);
	
	// update feeds
	while (1) {
		updateFeeds();
		sleep(60 * 30);
	}
}

function updateFeeds() {
	$feedParser = new FeedParser();
	$feedManager = new FeedManager();
	$entryManager = new EntryManager();
	$now = new DateTime();
	$lastCheckedAt = $now->sub(new DateInterval("PT30M")); // update every 30 minutes
	$feedRecs = $feedManager->getFeedsToUpdate($lastCheckedAt->getTimestamp()); // Get Feeds that need update
    foreach ($feedRecs as $feedRec) {
        echo "Parsing".$feedRec->selfLink."\n";
		if ($feed = $feedParser->parseFeed($feedRec->selfLink)) {
            echo "Updating\n";
			$feed->id = $feedRec->id;
		    $feedManager->updateFeed(0, 0, $feed);
		}

	}
	// Delete entries that are older than 2 weeks 
	$oldDate = $now->sub(new DateInterval("P2W")); // two weeks ago	
	$entryManager->deleteOldEntries($oldDate->getTimestamp());
}

function signalHandler($sigNum) {
	switch ($sigNum) {
		case SIGTERM:
			cleanupAndExit("Received SIGTERM\n");
			break;
	
	}

}

// Remove PID file and exit with a given message
function cleanupAndExit($msg) {
	unlink("pid");
	exit($msg);	
	

}


?>
