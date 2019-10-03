<?php
	require_once('../auth.php');

	$nome_team = $_SESSION['SESS_TEAM'];
	$wforma = $_REQUEST['forma'];
	$box = $_REQUEST['box'];
	
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
				VALUES (\"$nome_team\",'Nessuna','Marcatura a uomo','Formazione 1',\"$wforma\",'100',0)";
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
		$qry = "UPDATE tattica SET  t_forma = \"$wforma\"	WHERE t_id_team = \"$nome_team\"";

		//echo $qry;
		$result = mysql_query($qry);
	
		if (!$result)
		{
    		echo 'Errore nella query aggiornamento tattica: ' . mysql_error();
		    exit();
		}
	}
	mysql_close($link);
	
	$stampa = array("","Forma","Freschezza","Condizione");
	switch ($wforma)
	{
		case 0:
			$checkFres = "";
			$checkForm = "";
			$checkCond = "";
			$BnFFC2 = 0;
			$BnFFC1 = 0;
			$BnFFC3 = 0;
			break;
		case 1:
			$checkFres = "";
			$checkForm = "";
			$checkCond = "checked";
			$BnFFC2 = 0;
			$BnFFC1 = 0;
			$BnFFC3 = 1;
			break;
		case 2:
			$checkFres = "";
			$checkForm = "checked";
			$checkCond = "";
			$BnFFC2 = 0;
			$BnFFC1 = 2;
			$BnFFC3 = 0;
			break;
		case 3:
			$checkFres = "";
			$checkForm = "checked";
			$checkCond = "checked";
			$BnFFC2 = 0;
			$BnFFC1 = 2;
			$BnFFC3 = 1;
			break;
		case 4:
			$checkFres = "checked";
			$checkForm = "";
			$checkCond = "";
			$BnFFC2 = 4;
			$BnFFC1 = 0;
			$BnFFC3 = 0;
			break;
		case 5:
			$checkFres = "checked";
			$checkForm = "";
			$checkCond = "checked";
			$BnFFC2 = 4;
			$BnFFC1 = 0;
			$BnFFC3 = 1;
			break;
		case 6:
			$checkFres = "checked";
			$checkForm = "checked";
			$checkCond = "";
			$BnFFC2 = 4;
			$BnFFC1 = 2;
			$BnFFC3 = 0;
			break;
		case 7:
			$checkFres = "checked";
			$checkForm = "checked";
			$checkCond = "checked";
			$BnFFC2 = 4;
			$BnFFC1 = 2;
			$BnFFC3 = 1;
			break;
	}
	
	$BnFFC = array("",$BnFFC1,$BnFFC2,$BnFFC3);
	$check = array("",$checkForm,$checkFres,$checkCond);
	
	echo "<input id='ws_ffc$box' name='ws_ffc$box' type='checkbox' $check[$box] value='$BnFFC[$box]' onclick='javascript:ffc($box);' class='fld_y'/>&nbsp;$stampa[$box]";
	
?>
