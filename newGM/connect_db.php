<?php
	include "config.php";

	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if (!$link) {
    	die('Could not connect: ' . mysqli_error());
	}

	$rest = mysqli_select_db($link, DB_DATABASE);
	if (!$rest) {
    	die('Could not selecte dbase: ' . mysqli_error());
	}
?>
