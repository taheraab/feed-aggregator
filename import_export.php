<?php
include_once "classes/OPMLReader.php";
include_once "classes/FeedManager.php";
include_once "classes/FeedParser.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
    header("Location: ".createRedirectURL("login.php"));
    exit;
}

if (isset($_FILES["subscriptionsFile"])) {
	if ($_FILES["subscriptionsFile"]["error"] == UPLOAD_ERR_OK) {
		$filename = $_FILES["subscriptionsFile"]["tmp_name"];
		move_uploaded_file($filename, "files/import_subscriptions".$_SESSION["currentUserId"]);
		$rootFolderId = filter_var($_POST["rootFolderId"], FILTER_SANITIZE_NUMBER_INT);
		//launch a background script
		$cmd = "/usr/bin/php private/import.php ".$_SESSION["currentUserId"]." ".$rootFolderId; 
		$logFile = "log/importLog".$_SESSION["currentUserId"];
		$cmd = "nohup ".$cmd." 1>".$logFile." 2>&1 </dev/null & echo $!";
		exec($cmd, $output);
		$pid = (int)$output[0];
		if ($pid) $errMsg = "Initiated import(".$pid."), it will take a few seconds for import to complete";
	}else $errMsg = "No file uploaded, please try again";
}

?>

<html>
<head>
<style type="text/css">
	body {
		margin: 0;
		padding: 0;
		color: rgb(255, 0, 0);
	}
</style>
</head>
<body>
	<span> <?php echo $cmd; ?> </span>
	<span> <?php if (isset($errMsg)) echo $errMsg; ?> </span>
</body>
</html> 

