<?php
include_once "classes/OPMLReader.php";
include_once "classes/FeedManager.php";
include_once "classes/OPMLWriter.php";
include_once "includes/util.php";

session_start();
if (!isset($_SESSION["currentUserId"])) {
    header("Location: ".createRedirectURL("login.php"));
    exit;
}

if (isset($_FILES["subscriptionsFile"])) {
	if ($_FILES["subscriptionsFile"]["error"] == UPLOAD_ERR_OK) {
		$filename = $_FILES["subscriptionsFile"]["tmp_name"];
		move_uploaded_file($filename, "files/import_subscriptions".$_SESSION["currentUserId"]);
		//launch a background script
		$cmd = "/usr/bin/php private/import.php ".$_SESSION["currentUserId"]; 
		$logFile = "log/importLog".$_SESSION["currentUserId"];
		$errFile = "log/importErrors".$_SESSION["currentUserId"];
		$cmd = "nohup ".$cmd." 1>".$logFile." 2>".$errFile." </dev/null & echo $!";
		exec($cmd, $output);
		$pid = (int)$output[0];
		if ($pid) {
			$_SESSION["importTaskPid"] = $pid;	
			$msg = "Initiated import. It will take a few seconds to complete.";
			$importTaskExists = true;
		}
	}else $errMsg = "No file uploaded, please try again";

}else if (isset($_REQUEST["checkImportTask"])) {
	//check if the import task is still running and display it's output
	if (isset($_SESSION["importTaskPid"])) {
		exec("ps -p ".$_SESSION["importTaskPid"], $output);
		if (count($output) >= 2) {
			//Process is running
			$importTaskExists = true;
		}else {
			$importTaskExists = false;
		}
	}
	$msg = file_get_contents("log/importLog".$_SESSION["currentUserId"]);

}else if (isset($_REQUEST["export"])) {
	$feedManager = new FeedManager();
	$folderManager = new FolderManager();
	$OPMLWriter = new OPMLWriter();
	$filename = "files/export_subscriptions".$_SESSION["currentUserId"].".xml";
	if ($OPMLWriter->exportFeedsToFile($_SESSION["currentUserId"], $filename)) {
		// File is successfully generated
		header("Content-type: application/xml");
		echo file_get_contents($filename);
		exit;
	}else $errMsg = "Error generating OPML file";
}

?>
<html>
<head>
<style type="text/css">
	body {
		margin: 0;
		padding: 0;
	}
	.errMsg {
		color: rgb(255, 0, 0);
	}
</style>
<script src="//code.jquery.com/jquery.js"></script>
<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet" media="screen">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
<script type="text/javascript">
<?php if (isset($importTaskExists) && $importTaskExists) {
?>
	window.setInterval(pollImportTask, 10000); 
<?php
}
?>
	function pollImportTask() {
		$("#pollForm").submit();		

	}
</script>
</head>
<body>
	<form id="pollForm" action="import_export.php?checkImportTask" method="post"> </form>
	<pre> <?php if (isset($msg)) echo $msg; ?> </pre>
	<span class="errMsg"> <?php if (isset($errMsg)) echo $errMsg; ?> </span>
</body>
</html> 

