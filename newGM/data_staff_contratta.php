<?php
	require_once('auth.php');

	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];
	$contra = $_REQUEST['contra'];

	include "connect_db.php";	

	$qry = "UPDATE staff_mercato SET s_contrattazioni=$contra WHERE s_id_team=\"$nome_team\" AND s_id=$controllo ";
	
	$result = mysql_query($qry);
	
	if (!$result)
	{
    	echo 'Errore nella query STAFF Mercato: ' . mysql_error();
	    exit();
	}
				
	mysql_close($link);
	header("location: staff_contra.php?id=$controllo");
?>
