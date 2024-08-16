<?php
	require_once('../auth.php');
	include "../connect_db.php";
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$tipologia = $_REQUEST['tip'];
	
	if ($tipologia == 0)
	{
		$min = $_REQUEST['minu'];
		$cond = $_REQUEST['cond'];
		$entra = $_REQUEST['entra'];
		$esce = $_REQUEST['esce'];
	
		$qry = "INSERT INTO istruzioni (id_team, tipologia, min, condizione, entra, esce) 
					VALUES (\"$nome_team\",'$tipologia','$min','$cond','$entra','$esce')";
		$result = mysqli_query($link, $qry);
		
		if (!$result)
		{
			echo 'Errore nella query inserimento SOSTITUZIONE: ' . mysqli_error();
			exit();
		}
	}
	else
	{
		$min = $_REQUEST['minu'];
		$cond = $_REQUEST['cond'];
		$regola = $_REQUEST['rego'];
	
		$qry = "INSERT INTO istruzioni (id_team, tipologia, min, condizione, regola) 
					VALUES (\"$nome_team\",'$tipologia','$min','$cond','$regola')";
		$result = mysqli_query($link, $qry);
		
		if (!$result)
		{
			echo 'Errore nella query inserimento REGOLA: ' . mysqli_error();
			exit();
		}
	}
	mysqli_close($link);
?>
