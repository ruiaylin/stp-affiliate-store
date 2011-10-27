<?php 
	require_once('config.php');

	// Connect to the database.
	// Returns a connection handle to the database.
	function db_connect() {
		global $db_host,$db_user,$db_pass,$db_name;
		static $conn;
		
		if(!$conn) {
			if($conn = mysql_connect($db_host,$db_user,$db_pass)) {
				mysql_select_db($db_name);
			}
		}
		return $conn;
	}
?>