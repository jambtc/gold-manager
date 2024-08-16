<?php
	require_once('../auth.php');
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	$wformazione   = $_REQUEST['formazione'];

	// VERIFICO SE GIA' ESISTE LA TATTICA
	include "../connect_db.php";	

	$controllo = mysqli_query($link, "SELECT * FROM tattica WHERE t_id_team=\"$nome_team\"");
	if (!$controllo)
	{
    	echo 'Errore nella query tattica: ' . mysqli_error();
	    exit();
	}

	$righe = mysqli_num_rows($controllo);
	if ($righe == 0)   // Se non esiste la tattica, inserisco!
	{
		$qry = "INSERT INTO tattica (t_id_team, t_tattica, t_marcatura, t_formazione, t_forma,t_impegno) 
				VALUES (\"$nome_team\",'Nessuna','Marcatura a uomo','Formazione 1','0','100')";
		$result = mysqli_query($link, $qry);
	
		if (!$result)
		{
    		echo 'Errore nella query inserimento tattica: ' . mysqli_error();
		    exit();
		}
	}
	else
	{
		//Se la tattica � gi� esistente, AGGIORNA!
		$qry = "UPDATE tattica SET  t_formazione = \"$wformazione\"	WHERE t_id_team = \"$nome_team\"";

		//echo $qry;
		$result = mysqli_query($link, $qry);
	
		if (!$result)
		{
    		echo 'Errore nella query aggiornamento tattica: ' . mysqli_error();
		    exit();
		}
	}
	mysqli_close($link);
?>
