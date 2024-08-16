<?php
	require_once('../auth.php');
	include "../connect_db.php";
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	$id = $_REQUEST['id'];
	
	$qry = "DELETE FROM  istruzioni WHERE id='$id'";
	$result = mysqli_query($link, $qry);
		
	if (!$result)
	{
		echo 'Errore nella query CANCELLA REGOLA: ' . mysqli_error();
		exit();
	}

	mysqli_close($link);
?>
