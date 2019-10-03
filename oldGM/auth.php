<?php
	//Start session
	session_start();
	
	//Check whether the session variable SESS_USER is present or not
	if(!isset($_SESSION['SESS_USER']) || (trim($_SESSION['SESS_USER']) == '')) {
		header("location: access-denied-page.php");
		exit();
	}
?>