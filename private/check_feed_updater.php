#!/usr/bin/php
<?php
// This file is a cron task that checks if daemon is still running and restarts it if necessary
//Check if daemon pid file exists
$pidFile = dirname(__FILE__)."/../files/pid";
$fd = fopen($pidFile, "r");
$running = false;
if ($fd) {
	// Get pid and send signal 0 to it to verify if it is still running
	$pid = fgets($fd);
	if ($pid) {
		if (posix_kill($pid, 0)) $running = true;
	}
	fclose($fd);
}
if (!$running) {
	shell_exec("php feed_updater.php");
	echo "Restarted feed_updater.php";
}else echo "feed_updater.php already running";


?>
