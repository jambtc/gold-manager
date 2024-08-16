<?php
	require_once('auth.php');
	include "connect_db.php";	
	
	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];
	
	$result = mysqli_query($link, "UPDATE staff SET s_addestramento = 14 WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$controllo\"");
	
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysqli_error();
	    exit();
	}
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! messaggio news
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! aggiorna budget
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! messaggio serie
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			
	mysqli_close($link);
	header("location: form_staff.php");


?>

