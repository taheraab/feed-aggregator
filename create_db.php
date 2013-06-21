<?php
// This script creates the MySQL database schema for Feed Aggregator
include_once "includes/util.php";

$procedure = <<<EOP
CREATE PROCEDURE createDB()
BEGIN
CREATE TABLE IF NOT EXISTS User (id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), username VARCHAR(50) UNIQUE NOT NULL, password CHAR(61));
CREATE TABLE IF NOT EXISTS Feed (id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, feedId VARCHAR(100) NOT NULL, title VARCHAR(255), subtitle VARCHAR(255), selfLink VARCHAR(255), updated INT UNSIGNED NOT NULL, authors VARCHAR(255), alternateLink VARCHAR(255), lastCheckedAt INT UNSIGNED NOT NULL);
CREATE TABLE IF NOT EXISTS UserFeedRel (user_id TINYINT UNSIGNED, feed_id SMALLINT UNSIGNED, PRIMARY KEY (user_id, feed_id), FOREIGN KEY (user_id) REFERENCES User (id) ON DELETE CASCADE, FOREIGN KEY (feed_id) REFERENCES Feed (id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS Entry (id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, entryId VARCHAR(100) NOT NULL, title VARCHAR(255), updated INT UNSIGNED NOT NULL, authors VARCHAR(255), alternateLink VARCHAR(255), contentType VARCHAR(50), content TEXT, feed_id SMALLINT UNSIGNED NOT NULL, FOREIGN KEY (feed_id) REFERENCES Feed (id) ON DELETE CASCADE);
CREATE TABLE IF NOT EXISTS  UserEntryRel (user_id TINYINT UNSIGNED, entry_id SMALLINT UNSIGNED, status ENUM ('new', 'unread', 'read') NOT NULL, type ENUM ('unstarred', 'starred') NOT NULL, PRIMARY KEY (user_id, entry_id), FOREIGN KEY (user_id) REFERENCES User (id) ON DELETE CASCADE, FOREIGN KEY (entry_id) REFERENCES Entry (id) ON DELETE CASCADE);
END
EOP;

try {
	$dbh = new PDO(MYSQL_HOST, DB_USERNAME, DB_PASSWORD);
	execQuery($dbh, "CREATE DATABASE IF NOT EXISTS ".DB_NAME." DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci");
	execQuery($dbh, "USE FeedAggrDB");
	execQuery($dbh, $procedure);
	execQuery($dbh, "CAll createDB()");
	execQuery($dbh, "DROP PROCEDURE createDB");
	$dbh = null;
}catch (PDOException $e) {
	echo $e->getMessage()."\n";
}

function execQuery($dbh, $query) {
	$stmt = $dbh->query($query);
	if (!$stmt) {
		print_r($dbh->errorInfo());
	}

}

?>
