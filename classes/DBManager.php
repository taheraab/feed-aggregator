<?php

include_once (dirname(__FILE__)."/../includes/util.php");

//Base class that manages DB
class DBManager {
	
	protected $dbh = null;
		
	public function __construct($dbh = null) {
		if ($dbh == null) $this->connectToDB();
		else $this->dbh = $dbh;
	}

	public function __destruct() {
		$this->dbh = null;
	}


	// Convenience method to connect or reconnect to DB 
	protected function connectToDB() {
		try {
			$this->dbh = new PDO(MYSQL_DSN, DB_USERNAME, DB_PASSWORD);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch (PDOException $e) {
    		error_log("FeedAggregator::DBManager::connectToDB: ".$e->getMessage(), 0);		
		}
	}


	// Helper function to execute a query and log err Msg and optional rollback the transaction
	// Returns the true on success, false on failure
	protected function execQuery($stmt, $msg, $rollback = false) {
	    $res = $stmt->execute();
		if (!$res) {
    		error_log("FeedAggregator::DBManager:: ".$msg.": ".implode(",", $stmt->errorInfo()), 0);
			if ($rollback) $this->dbh->rollBack();
    	}
		return $res;
	}
	
}


?>
