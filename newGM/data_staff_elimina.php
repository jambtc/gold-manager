<?php
	require_once('auth.php');

	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];

	include "connect_db.php";	

	$qry = "DELETE FROM staff WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$controllo\" LIMIT 1";
	
	$result = mysqli_query($link, $qry);
	
	if (!$result)
	{
    	echo 'Errore nella query STAFF: ' . mysqli_error();
	   exit();
	}
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! STATISTICHE
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

	$qry = "DELETE FROM stat_staff WHERE s_id_team = \"$nome_team\" AND s_id_staff = \"$controllo\"";
	
	$result = mysqli_query($link, $qry);
	if (!$result) {
		echo 'Errore nella query STAT STAFF: ' . mysqli_error();
		exit();
	}
	
		
	mysqli_close($link);
	
	header("location: m-index.php?fnz=staff&pg=22");

?>
