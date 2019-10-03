<?php
	include "config.php";

	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if (!$link) {
    	die('Could not connect: ' . mysql_error());
	}

	$rest = mysql_select_db(DB_DATABASE);
	if (!$rest) {
    	die('Could not selecte dbase: ' . mysql_error());
	}
?>
