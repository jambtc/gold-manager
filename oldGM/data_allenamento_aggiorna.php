<?php
	require_once('auth.php');
	
	//echo "<font color:#fff>";
	
	include "connect_db.php";
	$nome_team = $_SESSION['SESS_TEAM'];
	
	
	$wbox = $_REQUEST['box'];
	$wpagina = $_REQUEST['pagina'];
	
	//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY 
	if ($wpagina == 1)
	{
		$a_campi = array('sk_forma','sk_cond','sk_po','sk_df','sk_cn','sk_pa','sk_rg','sk_cr','sk_tc','sk_tr');
		$a_valori = array('sk_forma_val','sk_cond_val','sk_po_val','sk_df_val','sk_cn_val','sk_pa_val','sk_rg_val','sk_cr_val','sk_tc_val','sk_tr_val');
		$tabella = "allena_skill";
	}
	else
	{
		$a_campi=array('ta_press','ta_contr','ta_poss','ta_pall','ta_gioc','ta_cate','ta_fuor','ta_calc');
		$a_valori = array('ta_press_val','ta_contr_val','ta_poss_val','ta_pall_val','ta_gioc_val','ta_cate_val','ta_fuor_val','ta_calc_val');
	
		$tabella = "allena_tattiche";	
	}
	$tot_campi = count($a_campi);
	$tot_valori = count($a_valori);
	
	if ($wbox > 0 ) // 
	{
		$conta = 1;
		$min = intval(($wbox-1) / 10) * 10 ;
		$campo = ($min / 10) + 1 ;
		$quanto = 0;
		$maxbox = 0;
		
		while ( $conta <= 10)
		{
			if ($min+$conta <= $wbox)
			{
				$quanto ++;
			}
			$conta ++;
		}
	}
	
	
	$controllo = mysql_query("SELECT * FROM $tabella WHERE a_id_team=\"$nome_team\"");
	if (!$controllo)
	{
		echo 'Errore nella query select $tabella: ' . mysql_error();
		exit();
	}
	$righe = mysql_num_rows($controllo);
	
	if ($righe == 0)   // Se non esiste, inserisco dati a zero e data odierna!!
	{
		for ($id = 0; $id < $tot_campi; $id++) 
		{
			$riquadro[$id] = 0; // imposto tutti i parametri a zero
		}
		$riquadro[$campo-1] = $quanto;
		
		$qry1 = "INSERT INTO $tabella ( a_id_team, ";
		$qry2 = "";
		$qry3 = "";
	
		for ($conta = 0; $conta < $tot_campi; $conta++)
		{
			$qry2 = $qry2 . $a_campi[$conta] . ", " ;
			if ($conta < $tot_valori)
			{
				$qry3 = $qry3 . $riquadro[$conta] . ", " ;
			}
		}

		$qry2 = substr($qry2,0,-2);	
		$qry3 = substr($qry3,0,-2);			
	
		$qry = $qry1 . $qry2 .") VALUES ( \"$nome_team\", " . $qry3 . ")";
			
	}
	else
	{
		$row = mysql_fetch_array($controllo);
		
		//CALCOLO DEL MAX BOX
		for ($id=0; $id < $tot_campi; $id++)
		{
			$riquadro[$id] = $row[$a_campi[$id]];
			$maxbox = $maxbox + $riquadro[$id];
		}
		$maxbox = $maxbox - $riquadro[$campo-1]; // deve andare qui
		if ($quanto == 1 and $riquadro[$campo-1] == 1)
		{
			$riquadro[$campo-1] = 0;
		}
		else
		{
			$riquadro[$campo-1] = $quanto;
		}
		$maxbox = $maxbox + $quanto; // deve andare qui
	
		$qry1 = "UPDATE $tabella SET ";
		$qry2 = "";

		for ($conta = 0 ; $conta < $tot_valori; $conta ++)
		{
			$qry2 = $qry2 . $a_campi[$conta] . " = " . "\"" . $riquadro[$conta] . "\", ";
		}			
		$qry2 = substr($qry2,0,-2);	
		$qry = $qry1 . $qry2 . " WHERE a_id_team=\"$nome_team\"";
	}
	
	if ($wpagina == 2 and $maxbox > 10)
	{
		echo "<script>";
		echo "window.location.href='allena_tattiche.php?maxbox=$maxbox';";
		echo "</script>";
		exit;
	}	
	if ($wpagina == 1 and $maxbox > 15)
	{
		echo "<script>";
		echo "window.location.href='allena_skill.php?maxbox=$maxbox';";
		echo "</script>";
		exit;
	}	
	
	$result = mysql_query($qry);
	if (!$result)
	{
		echo "Errore nella query $tabella, insert=$righe " . mysql_error();
		exit();
	}
	
	mysql_close($link);
	
	if ($wpagina == 1)
	{
		echo "<script>";
		echo "window.location.href='allena_skill.php?maxbox=$maxbox';";
		echo "</script>";
	}
	else
	{
		echo "<script>";
		echo "window.location.href='allena_tattiche.php?maxbox=$maxbox';";
		echo "</script>";
	}	
	
	
?>
