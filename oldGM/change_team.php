<?php
	//Start session
	session_start();
	
	$_SESSION['SESS_TEAM'] = $_REQUEST['team'];
	define("ID_TEAM",$_REQUEST['team']);
					
	session_write_close();
	header("location: m-index.php");
	exit();
?>