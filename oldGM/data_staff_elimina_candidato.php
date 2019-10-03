<?php
	require_once('auth.php');

	$controllo = $_REQUEST['delete'];
	$nome_team = $_SESSION['SESS_TEAM'];

	include "connect_db.php";	

	$qry = "DELETE FROM staff_mercato WHERE s_id_team=\"$nome_team\" AND s_id=$controllo LIMIT 1";
	
	$result = mysql_query($qry);
	
	if (!$result)
	{
    	echo 'Errore nella query STAFF Mercato: ' . mysql_error();
	    exit();
	}
				
	mysql_close($link);
	header("location: form_staff.php?pagina=1");

?>
