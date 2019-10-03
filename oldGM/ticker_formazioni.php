<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	$quale_css = "index_live".$_SESSION['SESS_LARGHEZZA'].".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";

	include("connect_db.php");	
	$nome_team = $_SESSION['SESS_TEAM'];

	$ID_PARTITA = $_GET['id'];
	
	$qry = "SELECT * FROM z_calendario WHERE id_partita='$ID_PARTITA'";
	$result = mysql_query($qry);
	$row_calendario = mysql_fetch_array($result);	
	
	//CARICO I DATI DELLA PARTITA
	$qry = "SELECT * FROM z_partita WHERE id_partita='$ID_PARTITA'";
	$result = mysql_query($qry);
	$row_partita = mysql_fetch_array($result);
	
	// stabilisce se gioca in casa o fuori
	if ($row_calendario['casa'] == $nome_team)
	{
		$sq_casa = $nome_team;
		$sq_fuori = $row_calendario['fuori'];
		$c_goal = $row_calendario['gol_casa'];
		$f_goal = $row_calendario['gol_fuori'];
	}
	else
	{
		$sq_fuori = $nome_team;
		$sq_casa = $row_calendario['fuori'];
		$f_goal = $row_calendario['gol_casa'];
		$c_goal = $row_calendario['gol_fuori'];
	}	
	//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY RIQUADRO CHE CONTERRA' IL NUMERO DI MAGLIA
	$appoggio = array('f_id_team','f_1','f_2','f_3','f_4','f_5','f_6','f_7','f_8','f_9','f_10',
					 'f_11','f_12','f_13','f_14','f_15','f_16','f_17','f_18','f_19','f_20',
					 'f_21','f_22','f_23','f_24','f_25','f_26','f_27','f_28','f_29','f_30',
					 'f_31','f_32','f_33','f_34','f_35','f_36','f_37','f_38','f_39','f_40',
					 'f_41','f_42','f_43','f_44','f_45','f_46','f_47','f_48','f_49','f_50',
					 'f_51','f_52','f_53','f_54','f_55','f_56','f_57','f_58','f_59','f_60',
					 'f_61','f_62','f_63','f_64','f_65','f_66','f_67','f_68','f_69','f_70');
	
	$impegno = "100%";
	
	$Portiere = 0;
	$Difesa = 0;
	$Centrocampo = 0;
	$Attacco = 0;
	
	$ds = 0;
	$dc = 0;
	$dd = 0;
	$cs = 0;
	$cc = 0;
	$cd = 0;
	$as = 0;
	$ac = 0;
	$ad = 0;

	$Panchina = 0;
	$Forza = 0;
	$Forma = 0;
	$Freschezza = 0;
	$Condizione = 0;
	$Tattica[0] = 0;
	$Tattica[1] = 0;
	$Tattica[2] = 0;
	$Panchina = 0;
	$Giocatori = 0;
	$b_allenatore = 0;
	$b_viceallena = 0;
	$b_alleportie = 0;
	$filosofia = "bilanciato";
	$impegno_dif = array(-7.5,-5,0,5,7.5);
	$impegno_cen = array(-7.5,-5,0,5,7.5);
	$impegno_att = array(-7.5,-5,0,5,7.5);
	$lista_impegno = array(" 50%"," 75%","100%","125%","150%");
	
	$av_impegno = "100%";
	$av_Portiere = 0;
	$av_Difesa = 0;
	$av_Centrocampo = 0;
	$av_Attacco = 0;
	
	
	$av_ds = 0;
	$av_dc = 0;
	$av_dd = 0;
	$av_cs = 0;
	$av_cc = 0;
	$av_cd = 0;
	$av_as = 0;
	$av_ac = 0;
	$av_ad = 0;

	$av_Panchina = 0;
	$av_Forza = 0;
	$av_Forma = 0;
	$av_Freschezza = 0;
	$av_Condizione = 0;
	$av_Tattica[0] = 0;
	$av_Tattica[1] = 0;
	$av_Tattica[2] = 0;
	$av_Panchina = 0;
	$av_Giocatori = 0;
	$av_b_allenatore = 0;
	$av_b_viceallena = 0;
	$av_b_alleportie = 0;
	$av_filosofia = "bilanciato";
	$av_impegno_dif = array(-7.5,-5,0,5,7.5);
	$av_impegno_cen = array(-7.5,-5,0,5,7.5);
	$av_impegno_att = array(-7.5,-5,0,5,7.5);
	$av_lista_impegno = array(" 50%"," 75%","100%","125%","150%");
	
	// squadra casa
	$conta = 0;
	foreach ($lista_impegno as $test) 
	{
		if ($test == $impegno) 
		{ 
			$impegno_pos = $conta ; 
		}
		$conta ++;
	}

	// squadra avversaria
	$conta = 0;
	foreach ($av_lista_impegno as $test) 
	{
		if ($test == $av_impegno) 
		{ 
			$av_impegno_pos = $conta ; 
		}
		$conta ++;
	}
	/*
	// CREO ELENCO DELLE TATTICHE E MARCATURE
	$tattica_result = mysql_query("SELECT * FROM bonus_tattica WHERE 1 ORDER BY t_id");
	if (!$tattica_result)
	{
		echo 'Errore nella query bonus tattica: ' . mysql_error();
		exit();
	}
	$conta = 1;
	while   ($row   =   mysql_fetch_array($tattica_result)) {
		$ListaTattica[$conta] = $row['t_descrizione'];
		//array contenente bonus tattiche e marcature
		$Bonus[$ListaTattica[$conta]] = array($row['t_id'],$row['q1'],$row['q2'],$row['q3'],$row['q4'],$row['q5'],
											  $row['q6'],$row['q7'],$row['q8'],$row['q9']);
		$conta++;
	}
	
	// CARICO L'EFFICIENZA DEGLI ALLENATORI squadra di casa
	$all_result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$sq_casa\" ");
	if (!$all_result)
	{
		echo 'Errore nella query allenatore: ' . mysql_error();
		exit();
	}
	
	while ($row   =   mysql_fetch_array($all_result)){
		$effic = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
		switch ($row['s_descrizione']) {
		case "Allenatore":
			$b_allenatore = 0.3 * $effic ;
			$filosofia = $row['s_fil'];
				break;
		case "Vice Allenatore":
			$b_viceallena = $effic / 30.3 ;
				break;
		case "Allenatore Portieri":
			$b_alleportie = 0.035 * $effic ;
				break;
		}
	}
	// CARICO L'EFFICIENZA DEGLI ALLENATORI squadra avversaria
	$av_all_result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$sq_fuori\" ");
	if (!$av_all_result)
	{
		echo 'Errore nella query allenatore avversario: ' . mysql_error();
		exit();
	}
	
	while ($av_row   =   mysql_fetch_array($av_all_result))
	{
		$av_effic = (0.9 * $av_row['s_abi'] * $av_row['s_mot']) / 100 + $av_row['s_esp']/8;
		switch ($av_row['s_descrizione'])
		{
		case "Allenatore":
			$av_b_allenatore = 0.3 * $av_effic ;
			$av_filosofia = $av_row['s_fil'];
				break;
		case "Vice Allenatore":
			$av_b_viceallena = $av_effic / 30.3 ;
				break;
		case "Allenatore Portieri":
			$av_b_alleportie = 0.035 * $av_effic ;
				break;
		}
	}
	
	// CARICO TABELLA BONUS ALLENATORE squadra casa
	$all_result = mysql_query("SELECT * FROM bonus_allenatore WHERE b_descrizione = \"$filosofia\" ");
	if (!$all_result) {
		echo 'Errore nella query bonus allenatore: ' . mysql_error();
		exit();
	}
	$row = mysql_fetch_array($all_result);
	$bonus_allenatore = array($row['b_dif'],$row['b_cen'],$row['b_att']);
	
	// CARICO TABELLA BONUS ALLENATORE squadra avversaria
	$av_all_result = mysql_query("SELECT * FROM bonus_allenatore WHERE b_descrizione = \"$av_filosofia\" ");
	if (!$av_all_result)
	{
		echo 'Errore nella query bonus allenatore avversario: ' . mysql_error();
		exit();
	}
	$av_row = mysql_fetch_array($av_all_result);
	$av_bonus_allenatore = array($av_row['b_dif'],$av_row['b_cen'],$av_row['b_att']);
	
	// CARICO LA TATTICA E LA MARCATURA DEL TEAM di casa
	$tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$sq_casa\" ");
	if (!$tipo_result)
	{
		echo 'Errore nella query tattica: ' . mysql_error();
		exit();
	}
	$row   =   mysql_fetch_array($tipo_result);
	$TipoTattica = $row['t_tattica'];
	$TipoMarcatura = $row['t_marcatura'];
	$TipoBonus = "SI"; //$row['t_bonus'];
	
	if ($TipoTattica == "")
	{
		$TipoTattica = "Nessuna";
		$TipoMarcatura = "Marcatura a uomo";
	}
	
	// CARICO LA TATTICA E LA MARCATURA DEL TEAM avversario
	$av_tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$sq_fuori\" ");
	if (!$av_tipo_result)
	{
		echo 'Errore nella query tattica avversario: ' . mysql_error();
		exit();
	}
	$av_row   =   mysql_fetch_array($av_tipo_result);
	$av_TipoTattica = $av_row['t_tattica'];
	$av_TipoMarcatura = $av_row['t_marcatura'];
	$av_TipoBonus = "SI"; //$av_row['t_bonus'];
	
	if ($av_TipoTattica == "")
	{
		$av_TipoTattica = "Nessuna";
		$av_TipoMarcatura = "Marcatura a uomo";
	}
	*/
	//CARICO LA FORMAZIONE DEL TEAM di casa
	$qry = "SELECT * FROM z_formazione WHERE id_partita='$ID_PARTITA' AND f_id_team=\"$sq_casa\"";
	$formazione_result = mysql_query($qry);
	if (!$formazione_result)
	{
			echo 'Errore nella query: ' . mysql_error();
			exit();
	}
	$row   =   mysql_fetch_array($formazione_result);
	$conta = 1;

	while ($conta < 71) 
	{
		$conteggio[$conta] = 0;
		$riquadro[$conta] = $row[$appoggio[$conta]];
		if ($riquadro[$conta] == "")
		{
			$riquadro[$conta] = 0 ;
		}
		//NASCONDI SE IL VALORE DI MAGLIA == 0
		if ($riquadro[$conta] == 0)
		{
			$class[$conta] = "board";
		}
		else
		{
			// Associazione parametri dei giocatori solo se la casella contiene un valore di maglietta
			if ($conta < 64) 
				{
					$class[$conta] = "brdgioca"; //giocatore in campo
				}
				elseif ($conta >=64 and $conta <=66)
				{
					$class[$conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($conta ==67 )
				{
					$class[$conta] = "brdportiere"; //giocatore in porta
				}
				elseif ($conta ==68)
				{
					$class[$conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($conta ==69)
				{
					$class[$conta] = "brdpanchinaportiere"; //portiere in panchina
				}
							
		} // endif
		$conta ++;	
	} // end while
	
	//CARICO LA FORMAZIONE DEL TEAM AVVERSARIO
	$qry = "SELECT * FROM z_formazione WHERE id_partita='$ID_PARTITA' AND f_id_team=\"$sq_fuori\"";
	$formazione_result = mysql_query($qry);
	if (!$formazione_result)
	{
			echo 'Errore nella query: ' . mysql_error();
			exit();
	}
	$row   =   mysql_fetch_array($formazione_result);
	$conta = 1;

	while ($conta < 71) 
	{
		$conteggio[$conta] = 0;
		$av_riquadro[$conta] = $row[$appoggio[$conta]];
		if ($av_riquadro[$conta] == "")
		{
			$av_riquadro[$conta] = 0 ;
		}
		//NASCONDI SE IL VALORE DI MAGLIA == 0
		if ($av_riquadro[$conta] == 0)
		{
			$av_class[$conta] = "board";
		}
		else
		{
			// Associazione parametri dei giocatori solo se la casella contiene un valore di maglietta
			if ($conta < 64) 
				{
					$av_class[$conta] = "brdgioca"; //giocatore in campo
				}
				elseif ($conta >=64 and $conta <=66)
				{
					$av_class[$conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($conta ==67 )
				{
					$av_class[$conta] = "brdportiere"; //giocatore in porta
				}
				elseif ($conta ==68)
				{
					$av_class[$conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($conta ==69)
				{
					$av_class[$conta] = "brdpanchinaportiere"; //portiere in panchina
				}
							
		} // endif
		$conta ++;	
	} // end while
	
	
	if ($row_partita['sorteggio'] == 1)
	{
		$Portiere = $row_partita['c_po'];
		$Difesa = $row_partita['c_df'];
		$Centrocampo = $row_partita['c_df'];
		$Attacco = $row_partita['c_at'];
		$Forma = $row_partita['c_fma'];
		$Freschezza = $row_partita['c_frs'];
		$Condizione = $row_partita['c_con'];
		$Forza = $row_partita['c_for'];
		$Panchina = $row_partita['c_pan'];
		$TipoTattica = $row_partita['c_tipotattica'];
		$TipoMarcatura = $row_partita['c_tipomarcatura'];
		$caratteri = $row_partita['c_caratteri'];
		$impegno = $row_partita['c_impegno'];
		
		$av_Portiere = $row_partita['f_po'];
		$av_Difesa = $row_partita['f_df'];
		$av_Centrocampo = $row_partita['f_df'];
		$av_Attacco = $row_partita['f_at'];
		$av_Forma = $row_partita['f_fma'];
		$av_Freschezza = $row_partita['f_frs'];
		$av_Condizione = $row_partita['f_con'];
		$av_Forza = $row_partita['f_for'];		
		$av_Panchina = $row_partita['f_pan'];
		$av_TipoTattica = $row_partita['f_tipotattica'];
		$av_TipoMarcatura = $row_partita['f_tipomarcatura'];
		$av_caratteri = $row_partita['f_caratteri'];
		$av_impegno = $row_partita['f_impegno'];
		
	}
	else
	{
		$Portiere = $row_partita['f_po'];
		$Difesa = $row_partita['f_df'];
		$Centrocampo = $row_partita['f_df'];
		$Attacco = $row_partita['f_at'];
		$Forma = $row_partita['f_fma'];
		$Freschezza = $row_partita['f_frs'];
		$Condizione = $row_partita['f_con'];
		$Forza = $row_partita['f_for'];
		$Panchina = $row_partita['f_pan'];
		$TipoTattica = $row_partita['f_tipotattica'];
		$TipoMarcatura = $row_partita['f_tipomarcatura'];
		$caratteri = $row_partita['f_caratteri'];
		$impegno = $row_partita['f_impegno'];
		
		$av_Portiere = $row_partita['c_po'];
		$av_Difesa = $row_partita['c_df'];
		$av_Centrocampo = $row_partita['c_df'];
		$av_Attacco = $row_partita['c_at'];
		$av_Forma = $row_partita['c_fma'];
		$av_Freschezza = $row_partita['c_frs'];
		$av_Condizione = $row_partita['c_con'];
		$av_Forza = $row_partita['c_for'];		
		$av_Panchina = $row_partita['c_pan'];
		$av_TipoTattica = $row_partita['c_tipotattica'];
		$av_TipoMarcatura = $row_partita['c_tipomarcatura'];
		$av_caratteri = $row_partita['c_caratteri'];
		$av_impegno = $row_partita['c_impegno'];
	}


	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//FINE CALCOLO SQUADRA AVVERSARIA	 
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	
	
	echo "<span id='formazioni'>";
	echo "<fieldset style='width: 97%;'>";
	
	echo "<legend><a href='ticker_formazioni.php?id=$ID_PARTITA' target='ticker_dx'>Formazioni</a> - <a href='ticker_statistiche.php?id=$ID_PARTITA' target='ticker_dx'>Statistiche partita</a></legend>";
	echo "<table border='0' width='100%'>";
	echo "<tr height='400'><td>&nbsp;</td></tr>";	
	echo "</table>";	
	
	echo "<span id='c_squadra'>$sq_casa</span>";
	echo "<span id='c_vs'>VS</span>";
	echo "<span id='f_squadra'>$sq_fuori</span>";
	
	echo "<span id='c_campo'><img src='images/campo_di_gioco.png' width='208' height='220' border='0'></span>";
	echo "<span id='c_panca' ><img src='images/panchina_di_gioco.png' alt='' width='40' height='110' ></span>";
	echo "<span id='f_campo'><img src='images/campo_di_gioco.png' width='208' height='220' border='0'></span>";
	echo "<span id='f_panca' ><img src='images/panchina_di_gioco.png' alt='' width='40' height='110' ></span>";
	
	// VISUALIZZAZIONE SQUADRA CASA
	echo "
	<span id='riga1' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr>
	 <td><input name='btn' class=\"$class[1]\" value=$riquadro[1]  type='button'></td>
	 <td><input name='btn' class=\"$class[2]\" value=$riquadro[2]  type='button'></td>
	 <td><input name='btn' class=\"$class[3]\" value=$riquadro[3]  type='button'></td>
	 <td><input name='btn' class=\"$class[4]\" value=$riquadro[4]  type='button'></td>
	 <td><input name='btn' class=\"$class[5]\" value=$riquadro[5]  type='button'></td>
	 <td><input name='btn' class=\"$class[6]\" value=$riquadro[6]  type='button'></td>
	 <td><input name='btn' class=\"$class[7]\" value=$riquadro[7]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='riga2' > 
	 <table border='0' cellpadding='0' cellspacing='1'>
	 <tr>
	 <td><input name='btn' class=\"$class[8]\" value=$riquadro[8]  type='button'></td>
	 <td><input name='btn' class=\"$class[9]\" value=$riquadro[9]  type='button'></td>
	 <td><input name='btn' class=\"$class[10]\" value=$riquadro[10]  type='button'></td>
	 <td><input name='btn' class=\"$class[11]\" value=$riquadro[11]  type='button'></td>
	 <td><input name='btn' class=\"$class[12]\" value=$riquadro[12]  type='button'></td>
	 <td><input name='btn' class=\"$class[13]\" value=$riquadro[13]  type='button'></td>
	 <td><input name='btn' class=\"$class[14]\" value=$riquadro[14]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='riga3' > 
	 <table border='0' cellpadding='0' cellspacing='2'>
	 <tr>
	 <td><input name='btn' class=\"$class[15]\" value=$riquadro[15]  type='button'></td>
	 <td><input name='btn' class=\"$class[16]\" value=$riquadro[16]  type='button'></td>
	 <td><input name='btn' class=\"$class[17]\" value=$riquadro[17]  type='button'></td>
	 <td><input name='btn' class=\"$class[18]\" value=$riquadro[18]  type='button'></td>
	 <td><input name='btn' class=\"$class[19]\" value=$riquadro[19]  type='button'></td>
	 <td><input name='btn' class=\"$class[20]\" value=$riquadro[20]  type='button'></td>
	 <td><input name='btn' class=\"$class[21]\" value=$riquadro[21]  type='button'></td>
	 </tr>
	 </table>
	 </span>
	";
	echo "
	<span id='riga4' > 
	 <table border='0' cellpadding='0' cellspacing='3'>
	 <tr>
	 <td><input name='btn' class=\"$class[22]\" value=$riquadro[22]  type='button'></td>
	 <td><input name='btn' class=\"$class[23]\" value=$riquadro[23]  type='button'></td>
	 <td><input name='btn' class=\"$class[24]\" value=$riquadro[24]  type='button'></td>
	 <td><input name='btn' class=\"$class[25]\" value=$riquadro[25]  type='button'></td>
	 <td><input name='btn' class=\"$class[26]\" value=$riquadro[26]  type='button'></td>
	 <td><input name='btn' class=\"$class[27]\" value=$riquadro[27]  type='button'></td>
	 <td><input name='btn' class=\"$class[28]\" value=$riquadro[28]  type='button'></td>
	 </tr>
	 </table>
	 </span>
	";
	echo "
	<span id='riga5' > 
	 <table border='0' cellpadding='0' cellspacing='3'>
	 <tr>
	 <td><input name='btn' class=\"$class[29]\" value=$riquadro[29]  type='button'></td>
	 <td><input name='btn' class=\"$class[30]\" value=$riquadro[30]  type='button'></td>
	 <td><input name='btn' class=\"$class[31]\" value=$riquadro[31]  type='button'></td>
	 <td><input name='btn' class=\"$class[32]\" value=$riquadro[32]  type='button'></td>
	 <td><input name='btn' class=\"$class[33]\" value=$riquadro[33]  type='button'></td>
	 <td><input name='btn' class=\"$class[34]\" value=$riquadro[34]  type='button'></td>
	 <td><input name='btn' class=\"$class[35]\" value=$riquadro[35]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='riga6' > 
	 <table border='0' cellpadding='0' cellspacing='4'>
	 <tr>
	 <td><input name='btn' class=\"$class[36]\" value=$riquadro[36]  type='button'></td>
	 <td><input name='btn' class=\"$class[37]\" value=$riquadro[37]  type='button'></td>
	 <td><input name='btn' class=\"$class[38]\" value=$riquadro[38]  type='button'></td>
	 <td><input name='btn' class=\"$class[39]\" value=$riquadro[39]  type='button'></td>
	 <td><input name='btn' class=\"$class[40]\" value=$riquadro[40]  type='button'></td>
	 <td><input name='btn' class=\"$class[41]\" value=$riquadro[41]  type='button'></td>
	 <td><input name='btn' class=\"$class[42]\" value=$riquadro[42]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='riga7' > 
	 <table border='0' cellpadding='0' cellspacing='5'>
	 <tr>
	 <td><input name='btn' class=\"$class[43]\" value=$riquadro[43]  type='button'></td>
	 <td><input name='btn' class=\"$class[44]\" value=$riquadro[44]  type='button'></td>
	 <td><input name='btn' class=\"$class[45]\" value=$riquadro[45]  type='button'></td>
	 <td><input name='btn' class=\"$class[46]\" value=$riquadro[46]  type='button'></td>
	 <td><input name='btn' class=\"$class[47]\" value=$riquadro[47]  type='button'></td>
	 <td><input name='btn' class=\"$class[48]\" value=$riquadro[48]  type='button'></td>
	 <td><input name='btn' class=\"$class[49]\" value=$riquadro[49]  type='button'></td>
	 </table>
	</span>
	";
	echo "
	<span id='riga8' > 
	 <table border='0' cellpadding='0' cellspacing='5'>
	 <tr>
	 <td><input name='btn' class=\"$class[50]\" value=$riquadro[50]  type='button'></td>
	 <td><input name='btn' class=\"$class[51]\" value=$riquadro[51]  type='button'></td>
	 <td><input name='btn' class=\"$class[52]\" value=$riquadro[52]  type='button'></td>
	 <td><input name='btn' class=\"$class[53]\" value=$riquadro[53]  type='button'></td>
	 <td><input name='btn' class=\"$class[54]\" value=$riquadro[54]  type='button'></td>
	 <td><input name='btn' class=\"$class[55]\" value=$riquadro[55]  type='button'></td>
	 <td><input name='btn' class=\"$class[56]\" value=$riquadro[56]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='riga9' > 
	 <table border='0' cellpadding='0' cellspacing='6'>
	 <tr>
	 <td><input name='btn' class=\"$class[57]\" value=$riquadro[57]  type='button'></td>
	 <td><input name='btn' class=\"$class[58]\" value=$riquadro[58]  type='button'></td>
	 <td><input name='btn' class=\"$class[59]\" value=$riquadro[59]  type='button'></td>
	 <td><input name='btn' class=\"$class[60]\" value=$riquadro[60]  type='button'></td>
	 <td><input name='btn' class=\"$class[61]\" value=$riquadro[61]  type='button'></td>
	 <td><input name='btn' class=\"$class[62]\" value=$riquadro[62]  type='button'></td>
	 <td><input name='btn' class=\"$class[63]\" value=$riquadro[63]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='rigapanca64' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$class[64]\" value='$riquadro[64]'  type='button'></tr>
	</table>
	</span>
	";
	echo "
	<span id='rigapanca65' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$class[65]\" value='$riquadro[65]'  type='button'></tr>
	 </table>
	</span>";
	echo "
	<span id='rigapanca66' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$class[66]\" value='$riquadro[66]'  type='button'></tr>
	 </table>
	</span>
	";
	echo "
	<span id='riga10' > 
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr>
	 <td><input name='btn' class=\"$class[67]\" value=$riquadro[67]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='rigapanca68' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$class[68]\" value='$riquadro[68]'  type='button'></tr>
	 </table>
	</span>
	";
	echo "
	<span id='rigapanca69' >
	 <table border='1' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$class[69]\" value='$riquadro[69]'  type='button'></tr>
	 </table>
	</span>
	";
	
	echo "
	<span id='tattica'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Tattica:</td>
	<td>&nbsp;</td>
	<td>$TipoTattica</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='marcatura'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>$TipoMarcatura</td>
	</tr>
	</table>
	</span>
	";

	echo "
	<span id='forza'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Forza</td>
	</tr>
	<tr>
	<td>$Forza</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='forma'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Forma</td>
	</tr>
	<tr>
	<td>$Forma %</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='freschezza'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Fres.</td>
	</tr>
	<tr>
	<td>$Freschezza %</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='condizione'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Cond.</td>
	</tr>
	<tr>
	<td>$Condizione %</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='portiere'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Por</td>
	</tr>
	<tr>
	<td><center>$Portiere</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='difesa'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Dif</td>
	</tr>
	<tr>
	<td><center>$Difesa</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='centrocampo'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Cen</td>
	</tr>
	<tr>
	<td><center>$Centrocampo</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='attacco'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Att</td>
	</tr>
	<tr>
	<td><center>$Attacco</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='panchina'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Pan</td>
	</tr>
	<tr>
	<td>$Panchina</td>
	</tr>
	</table>
	</span>
	";
	
	$Malus = 0;
	$ContaCar = count($caratteri);
	if ($ContaCar < 5 ) 
	{ 
		$Malus = round(($Portiere + $Difesa + $Centrocampo + $Attacco)/100*5,1);
	} 
	
	$StampaForza = $Portiere + $Difesa + $Centrocampo + $Attacco - $Malus;
	
	echo "
	<span id='totale2'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Totale:</td>
	<td>&nbsp;</td>
	<td><center>$StampaForza</td>
	</tr>
	</table>
	</span>
	";
	
	echo "
	<span id='caratteri'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Caratteri:</td>
	<td>&nbsp;</td>
	";
	if ($ContaCar >= 5)
	{
		echo"<td>$ContaCar</td>";
	}
	else
	{
		echo"<td><h5>$ContaCar</h5></td>";
	}	
	echo"</tr>
	</table>
	
	</span>
	";

	// VISUALIZZAZIONE SQUADRA AVVERSARIA
	echo "
	<span id='av_riga1' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[1]\" value=$av_riquadro[1]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[2]\" value=$av_riquadro[2]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[3]\" value=$av_riquadro[3]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[4]\" value=$av_riquadro[4]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[5]\" value=$av_riquadro[5]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[6]\" value=$av_riquadro[6]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[7]\" value=$av_riquadro[7]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_riga2' > 
	 <table border='0' cellpadding='0' cellspacing='1'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[8]\" value=$av_riquadro[8]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[9]\" value=$av_riquadro[9]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[10]\" value=$av_riquadro[10]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[11]\" value=$av_riquadro[11]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[12]\" value=$av_riquadro[12]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[13]\" value=$av_riquadro[13]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[14]\" value=$av_riquadro[14]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_riga3' > 
	 <table border='0' cellpadding='0' cellspacing='2'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[15]\" value=$av_riquadro[15]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[16]\" value=$av_riquadro[16]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[17]\" value=$av_riquadro[17]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[18]\" value=$av_riquadro[18]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[19]\" value=$av_riquadro[19]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[20]\" value=$av_riquadro[20]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[21]\" value=$av_riquadro[21]  type='button'></td>
	 </tr>
	 </table>
	 </span>
	";
	echo "
	<span id='av_riga4' > 
	 <table border='0' cellpadding='0' cellspacing='3'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[22]\" value=$av_riquadro[22]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[23]\" value=$av_riquadro[23]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[24]\" value=$av_riquadro[24]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[25]\" value=$av_riquadro[25]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[26]\" value=$av_riquadro[26]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[27]\" value=$av_riquadro[27]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[28]\" value=$av_riquadro[28]  type='button'></td>
	 </tr>
	 </table>
	 </span>
	";
	echo "
	<span id='av_riga5' > 
	 <table border='0' cellpadding='0' cellspacing='3'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[29]\" value=$av_riquadro[29]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[30]\" value=$av_riquadro[30]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[31]\" value=$av_riquadro[31]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[32]\" value=$av_riquadro[32]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[33]\" value=$av_riquadro[33]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[34]\" value=$av_riquadro[34]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[35]\" value=$av_riquadro[35]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_riga6' > 
	 <table border='0' cellpadding='0' cellspacing='4'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[36]\" value=$av_riquadro[36]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[37]\" value=$av_riquadro[37]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[38]\" value=$av_riquadro[38]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[39]\" value=$av_riquadro[39]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[40]\" value=$av_riquadro[40]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[41]\" value=$av_riquadro[41]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[42]\" value=$av_riquadro[42]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_riga7' > 
	 <table border='0' cellpadding='0' cellspacing='5'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[43]\" value=$av_riquadro[43]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[44]\" value=$av_riquadro[44]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[45]\" value=$av_riquadro[45]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[46]\" value=$av_riquadro[46]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[47]\" value=$av_riquadro[47]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[48]\" value=$av_riquadro[48]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[49]\" value=$av_riquadro[49]  type='button'></td>
	 </table>
	</span>
	";
	echo "
	<span id='av_riga8' > 
	 <table border='0' cellpadding='0' cellspacing='5'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[50]\" value=$av_riquadro[50]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[51]\" value=$av_riquadro[51]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[52]\" value=$av_riquadro[52]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[53]\" value=$av_riquadro[53]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[54]\" value=$av_riquadro[54]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[55]\" value=$av_riquadro[55]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[56]\" value=$av_riquadro[56]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_riga9' > 
	 <table border='0' cellpadding='0' cellspacing='6'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[57]\" value=$av_riquadro[57]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[58]\" value=$av_riquadro[58]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[59]\" value=$av_riquadro[59]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[60]\" value=$av_riquadro[60]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[61]\" value=$av_riquadro[61]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[62]\" value=$av_riquadro[62]  type='button'></td>
	 <td><input name='btn' class=\"$av_class[63]\" value=$av_riquadro[63]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_rigapanca64' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$av_class[64]\" value='$av_riquadro[64]'  type='button'></tr>
	</table>
	</span>
	";
	echo "
	<span id='av_rigapanca65' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$av_class[65]\" value='$av_riquadro[65]'  type='button'></tr>
	 </table>
	</span>";
	echo "
	<span id='av_rigapanca66' >
	 <table border='1' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$av_class[66]\" value='$av_riquadro[66]'  type='button'></tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_riga10' > 
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr>
	 <td><input name='btn' class=\"$av_class[67]\" value=$av_riquadro[67]  type='button'></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_rigapanca68' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$av_class[68]\" value='$av_riquadro[68]'  type='button'></tr>
	 </table>
	</span>
	";
	echo "
	<span id='av_rigapanca69' >
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr><input name='btn' class=\"$av_class[69]\" value='$av_riquadro[69]'  type='button'></tr>
	 </table>
	</span>
	";
	
	echo "
	<span id='av_forza'>
	<table border='0' cellpadding='0' cellspacing='0'>

	<tr>
	<td>Forza</td>
	</tr>
	<tr>
	<td>$av_Forza</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_forma'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Forma</td>
	</tr>
	<tr>
	<td>$av_Forma %</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_freschezza'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Fres.</td>
	</tr>
	<tr>
	<td>$av_Freschezza %</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_condizione'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Cond.</td>
	</tr>
	<tr>
	<td>$av_Condizione %</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_portiere'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Por</td>
	</tr>
	<tr>
	<td><center>$av_Portiere</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_difesa'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Dif</td>
	</tr>
	<tr>
	<td><center>$av_Difesa</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_centrocampo'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Cen</td>
	</tr>
	<tr>
	<td><center>$av_Centrocampo</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_attacco'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Att</td>
	</tr>
	<tr>
	<td><center>$av_Attacco</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_panchina'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Pan</td>
	</tr>
	<tr>
	<td>$av_Panchina</td>
	</tr>
	</table>
	</span>
	";
	$av_Malus = 0;
	$av_ContaCar = count($av_caratteri);
	if ($av_ContaCar < 5 ) 
	{ 
		$av_Malus = round(($av_Portiere + $av_Difesa + $av_Centrocampo + $av_Attacco)/100*5,1);
	} 
	
	$av_StampaForza = $av_Portiere + $av_Difesa + $av_Centrocampo + $av_Attacco - $av_Malus;
	
	echo "
	<span id='av_totale2'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Totale:</td>
	<td>&nbsp;</td>
	<td><center>$av_StampaForza</td>
	</tr>
	</table>
	</span>
	";
	
	echo "
	<span id='av_caratteri'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Caratteri:</td>
	<td>&nbsp;</td>
	";
	if ($av_ContaCar >= 5)
	{
		echo"<td>$av_ContaCar</td>";
	}
	else
	{
		echo"<td><h5>$av_ContaCar</h5></td>";
	}	
	echo"</tr>
	</table>
	
	</span>
	";

	echo "
	<span id='av_tattica'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>Tattica:</td>
	<td>&nbsp;</td>
	<td>$av_TipoTattica</td>
	</tr>
	</table>
	</span>
	";
	echo "
	<span id='av_marcatura'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>$av_TipoMarcatura</td>
	</tr>
	</table>
	</span>
	";
	
	echo "</fieldset>";
	echo "</span>";?>

