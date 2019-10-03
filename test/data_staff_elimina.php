<?php
	require_once('auth.php');

	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];

	include "connect_db.php";	

	$qry = "DELETE FROM staff WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$controllo\" LIMIT 1";
	
	$result = mysql_query($qry);
	
	if (!$result)
	{
    	echo 'Errore nella query STAFF: ' . mysql_error();
	   exit();
	}
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! STATISTICHE
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

	$qry = "DELETE FROM stat_staff WHERE s_id_team = \"$nome_team\" AND s_id_staff = \"$controllo\"";
	
	$result = mysql_query($qry);
	if (!$result) {
		echo 'Errore nella query STAT STAFF: ' . mysql_error();
		exit();
	}
	
		
	mysql_close($link);
	
	header("location: m-index.php?fnz=staff&pg=22");

?>
