<?php
	require_once('auth.php');
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$QualeFrame = 0;

	/*
	if(isset($_REQUEST['tattica']))
	{
		$wtattica   = $_REQUEST['tattica'];
		$QualeFrame = 2;
	}
	if(isset($_REQUEST['marcatura'])) 
	{   
		$wmarcatura = $_REQUEST['marcatura'];
		$QualeFrame = 2;
	}
	if(isset($_REQUEST['bonus']))
	{
		$wbonus = $_REQUEST['bonus'];
		$QualeFrame = 2;
	}
	if(isset($_REQUEST['impegno']))
	{
		$wimpegno = $_REQUEST['impegno'];
		$QualeFrame = 2;
	}
	if(isset($_REQUEST['forma'])) 
	{
		$wforma = $_REQUEST['forma'];
		$QualeFrame = 1;
	}
	*/
	$wforma = $_REQUEST['forma'];
	$wformazione   = $_REQUEST['formazione'];

	// VERIFICO SE GIA' ESISTE LA TATTICA
	include "connect_db.php";	

	$controllo = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\"");
	if (!$controllo)
	{
    	echo 'Errore nella query tattica: ' . mysql_error();
	    exit();
	}

	$righe = mysql_num_rows($controllo);
	if ($righe == 0)   // Se non esiste la tattica, inserisco!
	{
		$qry = "INSERT INTO tattica (t_id_team, t_tattica, t_marcatura, t_bonus, t_forma,t_impegno) 
				VALUES (\"$nome_team\",\"$wtattica\",\"$wmarcatura\",\"$wbonus\",\"$wforma\",'$wimpegno')";
		$result = mysql_query($qry);
	
		if (!$result)
		{
    		echo 'Errore nella query inserimento tattica: ' . mysql_error();
		    exit();
		}
	}
	else
	{
		//Se la tattica è già esistente, AGGIORNA!
		if ($QualeFrame == 2)
		{
			$qry = "UPDATE tattica SET  t_tattica = \"$wtattica\", 
									t_marcatura = \"$wmarcatura\",
									t_bonus = \"$wbonus\",
									t_impegno = '$wimpegno'
									WHERE t_id_team = \"$nome_team\"";
		}
		else
		{
			$qry = "UPDATE tattica SET  t_forma = \"$wforma\"	WHERE t_id_team = \"$nome_team\"";
		}
		$result = mysql_query($qry);
	
		if (!$result)
		{
    		echo 'Errore nella query aggiornamento tattica: ' . mysql_error();
		    exit();
		}
	}
	mysql_close($link);

	/*
	if ($QualeFrame == 2)
	{
		header("location: formazione_dx.php?box=0&maglia=0&formazione=$wformazione");
	}
	else
	{
		header("location: formazione_sx.php?box=0&maglia=0&formazione=$wformazione");
	}*/
	
?>
