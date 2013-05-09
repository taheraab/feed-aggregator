<?php
// This script creates the MySQL database schema for Feed Aggregator
include "constants.php";
try {
	$dbh = new PDO(MYSQL_DSN, DB_USERNAME, DB_PASSWORD);
	$stmt = $dbh->query("CREATE TABLE IF NOT EXISTS User (name VARCHAR(50), username VARCHAR(20) UNIQUE, password CHAR(61)) DEFAULT CHARACTER SET=utf8");
	if (!$stmt) {
		print_r($dbh->errorInfo());
	}
	$dbh = null;
}catch (PDOException $e) {
	echo $e->getMessage()."\n";
}

?>
