<?php
	require_once('../auth.php');
	include "../connect_db.php";
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	$id = $_REQUEST['id'];
	
	$qry = "DELETE FROM  istruzioni WHERE id='$id'";
	$result = mysql_query($qry);
		
	if (!$result)
	{
		echo 'Errore nella query CANCELLA REGOLA: ' . mysql_error();
		exit();
	}

	mysql_close($link);
?>
