<?php
	require_once('../auth.php');

	$nome_team = $_SESSION['SESS_TEAM'];
	$formazione = $_REQUEST['form'];
	
	// VERIFICO SE GIA' ESISTE LA TATTICA
	include "../connect_db.php";	

	$controllo = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\"");
	if (!$controllo)
	{
    	echo 'Errore nella query tattica: ' . mysql_error();
	    exit();
	}

	$righe = mysql_num_rows($controllo);
	if ($righe == 0)   // Se non esiste la tattica, inserisco!
	{
		$qry = "INSERT INTO tattica (t_id_team, t_tattica, t_marcatura, t_formazione, t_forma,t_impegno,t_fuorigioco) 
				VALUES (\"$nome_team\",'Nessuna','Marcatura a uomo','Formazione 1',\"$formazione\",'100',0)";
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
		$qry = "UPDATE tattica SET  t_formazione = \"$formazione\"	WHERE t_id_team = \"$nome_team\"";

		//echo $qry;
		$result = mysql_query($qry);
	
		if (!$result)
		{
    		echo 'Errore nella query aggiornamento tattica: ' . mysql_error();
		    exit();
		}
	}
	mysql_close($link);
?>
<select id='ws_formazione' name='ws_formazione' onchange='javascript:CambiaFormazione();' >
		<option value='<?php echo $formazione ?>' selected='selected'><?php echo $formazione ?></option>
		<option value='<?php echo $formazione ?>'>------------------</option>
		<option value='Formazione 1'>Formazione 1</option>
		<option value='Formazione 2'>Formazione 2</option>
		<option value='Formazione 3'>Formazione 3</option>
		<option value='Formazione 4'>Formazione 4</option>
		<option value='Formazione 5'>Formazione 5</option>
</select>
