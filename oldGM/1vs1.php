<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link href="live.css" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="jquery-1.4.2.js"></script>


<script>
function tempo(n)
{
    team = document.getElementById('ws_team').value ;

	if(n > 1)
	{
		document.getElementById('avvia').disabled = "true";
		document.getElementById('ws_team').disabled = "true";
	}
	
	document.getElementById('contatore').innerHTML = n;
	conta = eval(n);
	
	c_po = document.getElementById('c_po').value;
	c_df = document.getElementById('c_df').value;
	c_cn = document.getElementById('c_cn').value;	
	c_at = document.getElementById('c_at').value;
	c_tot = document.getElementById('c_totale').value;	
	
	f_po = document.getElementById('f_po').value;
	f_df = document.getElementById('f_df').value;
	f_cn = document.getElementById('f_cn').value;	
	f_at = document.getElementById('f_at').value;	
	f_tot = document.getElementById('f_totale').value;	
	
	cgol = document.getElementById('cgol').value;	
	fgol = document.getElementById('fgol').value;	
	
	c_num = Math.round(c_cn*Math.random());
	f_num = Math.round(f_cn*Math.random());
	
	//alert("centrocampo: "+c_num+" - "+f_num);
	if(c_num > f_num)
	{
		c_num1 = Math.round(c_at*Math.random());
		f_num1 = Math.round(f_df*Math.random());
		//alert("attacco casa: "+c_num1+" - "+f_num1);

		if(c_num1 > f_num1)
		{
			c_num2 = Math.round(c_tot*Math.random());
			f_num2 = Math.round(f_tot*Math.random());
			//alert("azione casa: "+c_num2+" - "+f_num2);
		
			if(c_num2 > f_num2)
			{
				indirizzo="telecronaca.php?avversario="+team+"&start="+conta;
				window.location.href=indirizzo;
			}
		}
	}
	else
	{
		f_num1 = Math.round(f_at*Math.random());
		c_num1 = Math.round(c_df*Math.random());
		//alert("attacco fuori: "+f_num1+" - "+c_num1);
	
		if(f_num1 > c_num1)
		{
			f_num2 = Math.round(f_tot*Math.random());
			c_num2 = Math.round(c_tot*Math.random());
			//alert("azione fuori: "+f_num2+" - "+c_num2);

			if(f_num2 > c_num2)
			{
				indirizzo="telecronaca.php?avversario="+team+"&start="+conta+"&ospiti=1";
				window.location.href=indirizzo;		
			}
			
		}
	}
	if(n == 45)
	{
        indirizzo="telecronaca.php?avversario="+team+"&start=45.4";
		window.location.href=indirizzo;		
	}
	else if(n == 90)
	{
        indirizzo="telecronaca.php?avversario="+team+"&start=90.4";
		window.location.href=indirizzo;		
	}	
	
	conta = eval(n)+1;
			
    if (n < 91)
	{
		setTimeout("tempo(conta)",10000); //aspetto x secondi (1000 = 1sec.) e richiamo la funzione//5000
	}
}

function intervallo(n)
{
    team = document.getElementById('ws_team').value ;

	if(n > 1)
	{
		document.getElementById('avvia').disabled = "true";
		document.getElementById('ws_team').disabled = "true";
	}
	
	document.getElementById('contatore').innerHTML = "intervallo";
	
	if(n == 0)
	{
        indirizzo="telecronaca.php?avversario="+team+"&start=45.5";
		window.location.href=indirizzo;		
	}
		
	
    if (n > 0)
	{
		n = 0;	
		setTimeout("intervallo(0)",30000); //60000aspetto x secondi (1000 = 1sec.) e richiamo la funzione
	}
}
function fine(n)
{
    team = document.getElementById('ws_team').value ;

	if(n > 1)
	{
		document.getElementById('avvia').disabled = "true";
		document.getElementById('ws_team').disabled = "true";
	}
	
	document.getElementById('contatore').innerHTML = "Fine Partita";
	
	if(n == 0)
	{
	}
	
    if (n > 0)
	{
		n = 0;	
		setTimeout("fine(0)",60000); //60000aspetto x secondi (1000 = 1sec.) e richiamo la funzione
	}
}

function scegli_squadra()
{
	arg = document.getElementById('ws_team').value ;

	indirizzo="1vs1.php?avversario="+arg;
	
	window.location.href=indirizzo;
}
function inizia()
{
	arg = document.getElementById('ws_team').value ;
	
	c_po = document.getElementById('c_po').value;
	c_df = document.getElementById('c_df').value;
	c_cn = document.getElementById('c_cn').value;	
	c_at = document.getElementById('c_at').value;
	c_forza = document.getElementById('c_forza').value;
	c_forma = document.getElementById('c_forma').value;
	c_freschezza = document.getElementById('c_freschezza').value;
	c_condizione = document.getElementById('c_condizione').value;
	c_tattica = document.getElementById('c_tattica').value;
	c_totale = document.getElementById('c_totale').value;	
	
	f_po = document.getElementById('f_po').value;
	f_df = document.getElementById('f_df').value;
	f_cn = document.getElementById('f_cn').value;	
	f_at = document.getElementById('f_at').value;	
	f_forza = document.getElementById('f_forza').value;
	f_forma = document.getElementById('f_forma').value;
	f_freschezza = document.getElementById('f_freschezza').value;
	f_condizione = document.getElementById('f_condizione').value;
	f_tattica = document.getElementById('f_tattica').value;
	f_totale = document.getElementById('f_totale').value;	

	indirizzo="telecronaca.php?avversario="+arg+"&start=0.4";
	
	indirizzo += "&c_po="+c_po;
	indirizzo += "&c_df="+c_df;
	indirizzo += "&c_cn="+c_cn;
	indirizzo += "&c_at="+c_at;
	indirizzo += "&c_forza="+c_forza;
	indirizzo += "&c_forma="+c_forma;
	indirizzo += "&c_freschezza="+c_freschezza;
	indirizzo += "&c_condizione="+c_condizione;
	indirizzo += "&c_tattica="+c_tattica;
	indirizzo += "&c_totale="+c_totale;
	
	indirizzo += "&f_po="+f_po;
	indirizzo += "&f_df="+f_df;
	indirizzo += "&f_cn="+f_cn;
	indirizzo += "&f_at="+f_at;
	indirizzo += "&f_forza="+f_forza;
	indirizzo += "&f_forma="+f_forma;
	indirizzo += "&f_freschezza="+f_freschezza;
	indirizzo += "&f_condizione="+f_condizione;
	indirizzo += "&f_tattica="+f_tattica;
	indirizzo += "&f_totale="+f_totale;
	
	window.location.href=indirizzo;
}
</script>
</head>

<?php 
	include "connect_db.php";	
	$nome_team = $_SESSION['SESS_TEAM'];

	$c_goal=0;
	$f_goal=0;
	$secondi="";
	$immbarretta = "images/barretta.png";
	$immbianca = "images/barrettabianca.png";

	if (isset($_REQUEST['avversario']))
	{
		$avversario = $_REQUEST['avversario'];
	}
	else
	{
		$avversario = "";
	}
	
	if (isset($_REQUEST['start']) 	&& $_REQUEST['start'] != "")
	{
		$qry = "SELECT * FROM partita WHERE id_team=\"$nome_team\"";
		$result = mysql_query($qry);
		if (!$result)
		{
		    echo 'Errore nella query partita: ' . mysql_error();
		    exit();
		}
		$totale_partita = mysql_num_rows($result);
		if ($totale_partita == 0)
		{
			$secondi = 0;
		}
		else
		{
			$row = mysql_fetch_array($result);
			$secondi = $row['min'];
			$c_goal = $row['c_goal'];
			$f_goal = $row['f_goal'];
			if ($secondi == 45)
			{
				echo "<body onLoad=\"javascript:intervallo(45)\">";
			}
			elseif ($secondi == 90)
			{
				echo "<body onLoad=\"javascript:fine(90)\">";
			}
			else
			{
				echo "<body onLoad=\"javascript:tempo('$secondi')\">";
			}
		}
	}
	else
	{
		echo "<body>";
		$qry1 = "DELETE FROM partita WHERE id_team=\"$nome_team\" LIMIT 1";
		$qry2 = "DELETE FROM telecronaca WHERE id_team=\"$nome_team\" ";
		$result1 = mysql_query($qry1);	
		$result2 = mysql_query($qry2);	
		if (!$result1 or !$result2)
		{
			echo 'Errore nella query delete: ' . mysql_error();
			exit();
		}	
	}
	
	//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY RIQUADRO CHE CONTERRA' IL NUMERO DI MAGLIA
	$appoggio= array('f_id_team','f_1','f_2','f_3','f_4','f_5','f_6','f_7','f_8','f_9','f_10',
					 'f_11','f_12','f_13','f_14','f_15','f_16','f_17','f_18','f_19','f_20',
					 'f_21','f_22','f_23','f_24','f_25','f_26','f_27','f_28','f_29','f_30',
					 'f_31','f_32','f_33','f_34','f_35','f_36','f_37','f_38','f_39','f_40',
					 'f_41','f_42','f_43','f_44','f_45','f_46','f_47','f_48','f_49','f_50',
					 'f_51','f_52','f_53','f_54','f_55','f_56','f_57','f_58','f_59','f_60',
					 'f_61','f_62','f_63','f_64','f_65','f_66','f_67','f_68','f_69','f_70',
					 'f_formazione');
	
	$wformazione = "Formazione 1";
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
	$all_result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ");
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
	$av_all_result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$avversario\" ");
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
	$tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\" ");
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
	$av_tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$avversario\" ");
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
	
	//CARICO LA FORMAZIONE DEL TEAM di casa
	$qry = "SELECT * FROM formazione WHERE f_id_team=\"$nome_team\" AND f_formazione=\"$wformazione\"";
	$formazione_result = mysql_query($qry);
	if (!$formazione_result)
	{
			echo 'Errore nella query: ' . mysql_error();
			exit();
	}
	$row   =   mysql_fetch_array($formazione_result);
	$conta = 1;
	$caratteri = array();

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
			$ricerca = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND nr=\"$riquadro[$conta]\" ");
			if (!$ricerca)
			{
				echo 'Errore nella query RICERCA PARAMETRI: ' . mysql_error();
				exit();
			}
			$rig   =   mysql_fetch_array($ricerca);
				
			// Associazione parametri dei giocatori solo se la casella contiene un valore di maglietta
			if ($conta < 64) 
				{
					$class[$conta] = "brdgioca"; //giocatore in campo
					$Forma = $Forma + $rig['forma'];
					$Freschezza = $Freschezza + $rig['fresc'];
					$Condizione = $Condizione + $rig['cond'];
					$skill = $rig['skill'];
					
					if (!in_array($rig['carattere'],$caratteri)) { $caratteri[] = $rig['carattere'];	}
					$Giocatori ++;
				}
				elseif ($conta >=64 and $conta <=66)
				{
					$class[$conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($conta ==67 )
				{
					$class[$conta] = "brdportiere"; //giocatore in porta
					$Forma = $Forma + $rig['forma'];
					$Freschezza = $Freschezza + $rig['fresc'];
					$Condizione = $Condizione + $rig['cond'];
					$skill = $rig['skill'];
					if (!in_array($rig['carattere'],$caratteri)) { $caratteri[] = $rig['carattere'];	}
					$Giocatori ++;
				}
				elseif ($conta ==68)
				{
					$class[$conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($conta ==69)
				{
					$class[$conta] = "brdpanchinaportiere"; //portiere in panchina
				}
				// dati del singolo giocatore
				$wpo = $rig['po'];
				$wdf = $rig['df'];
				$wcn = $rig['cn'];
				$wpa = $rig['pa'];
				$wrg = $rig['rg'];
				$wcr = $rig['cr'];
				$wtc = $rig['tc'];
				$wtr = $rig['tr'];
				$wes = $rig['esp'];
				
				$controllo = 15; // valore dell'esperienza
				$formula = "Formula 2";
							
				// CARICO I DATI DAL CALCOLATORE
				$calc_result = mysql_query("SELECT * FROM calcolatore WHERE formula=\"$formula\" ORDER BY ord");
				if (!$calc_result) { echo 'Errore nella query calcolatore : ' . mysql_error(); exit(); }
				
				$wii = 1; 
				while   ($rga   =   mysql_fetch_array($calc_result)) 
				{
					// CONTROLLA TUTTI I CASI DELLE CASELLE
					switch ($wii) { 
						//qsinistro
						case 1:
						case 2:
						case 8:
						case 9:
						case 15:
						case 16:
						case 22:
						case 23:
						case 29:
						case 30:
						case 36:
						case 37:
						case 43:
						case 44:
						case 50:
						case 51:
						case 57:
						case 58:
							if (strtoupper($rig['piede']) == "R")  {$pdd = -6;}
							elseif (strtoupper($rig['piede']) == "L") {$pdd = 6;}
							else{$pdd = 4;}
							break;
						//qcentrale
						case 3:
						case 4:
						case 5:
						case 10:
						case 11:
						case 12:
						case 17:
						case 18:
						case 19:
						case 24:
						case 25:
						case 26:
						case 31:
						case 32:
						case 33:
						case 38:
						case 39:
						case 40:
						case 45:
						case 46:
						case 47:
						case 52:
						case 53:
						case 54:
						case 59:
						case 60:				
						case 61:
							if (strtoupper($rig['piede']) == "R")  {$pdd = 4;}
							elseif (strtoupper($rig['piede']) == "L") {$pdd = 4;}
							else{$pdd = 7;}
							break;
						//qdestro
						case 6:
						case 7:
						case 13:
						case 14:
						case 20:
						case 21:
						case 27:
						case 28:
						case 34:
						case 35:
						case 41:
						case 42:
						case 48:
						case 49:
						case 55:
						case 56:
						case 62:
						case 63:
							if (strtoupper($rig['piede']) == "R")  {$pdd = 6;}
							elseif (strtoupper($rig['piede']) == "L") {$pdd = -6;}
							else{$pdd = 4;}
							break;
						//q0	
						case 64:
							$pdd = 4;
							break;
					} // end switch
					$conteggio[$wii] = $pdd + $wpo*$rga['po'] + $wdf*$rga['df'] + $wcn*$rga['cn'] + $wpa*$rga['pa'] ;
					$conteggio[$wii] = $conteggio[$wii] + $wrg*$rga['rg'] + $wcr*$rga['cr'];
					$conteggio[$wii] = round(($conteggio[$wii] + $wtc*$rga['tc'] + $wtr*$rga['tr'])/(101-$controllo),1) ;
					
					$wii ++ ;
				}
				
				// CONTROLLA TUTTI I CASI DELLE CASELLE
				switch ($conta) {
					case 1:
					case 2:
					case 8:
					case 9:
					case 15:
					case 16:
						$Tattica[0] ++;
						$as = $as + $conteggio[$conta];
						//$Attacco = $Attacco + $conteggio[$conta];
						//$Attacco = $Attacco + intval($Attacco*$Bonus[$TipoTattica][7]/100); //attacco sinistro Q7
						$Forza = $Forza + $rig['skill'];
						break;
					case 3:
					case 4:
					case 5:
					case 10:
					case 11:
					case 12:
					case 17:
					case 18:
					case 19:
						$Tattica[0] ++;
						$ac = $ac + $conteggio[$conta];
						//$Attacco = $Attacco + $conteggio[$conta];
						//$Attacco = $Attacco + intval($Attacco*$Bonus[$TipoTattica][8]/100); // attacco centrale
						$Forza = $Forza + $rig['skill'];
						break;
					case 6:
					case 7:
					case 13:
					case 14:
					case 20:
					case 21:
						$Tattica[0] ++;
						$ad = $ad + $conteggio[$conta];
						//$Attacco = $Attacco + $conteggio[$conta];
						//$Attacco = $Attacco + intval($Attacco*$Bonus[$TipoTattica][9]/100); // attacco destro
						$Forza = $Forza + $rig['skill'];
						break;
					case 22:
					case 23:
					case 29:
					case 30:
					case 36:
					case 37:
						$Tattica[1] ++;
						$cs = $cs + $conteggio[$conta];
						//$Centrocampo = $Centrocampo + $conteggio[$conta];
						//$Centrocampo = $Centrocampo + intval($Centrocampo*$Bonus[$TipoMarcatura][4]/100); //centrocampo sinistra
						//$Centrocampo = $Centrocampo + intval($Centrocampo*$Bonus[$TipoTattica][4]/100);
						$Forza = $Forza + $rig['skill'];
						break;
					case 24:
					case 25:
					case 26:
					case 31:
					case 32:
					case 33:
					case 38:
					case 39:
					case 40:
						$Tattica[1] ++;
						$cc = $cc + $conteggio[$conta];
						//$Centrocampo = $Centrocampo + $conteggio[$conta];
						//$Centrocampo = $Centrocampo + intval($Centrocampo*$Bonus[$TipoTattica][5]/100); //centrocampo centrale
						$Forza = $Forza + $rig['skill'];
						break;
					case 27:
					case 28:
					case 34:
					case 35:
					case 41:
					case 42:
						$Tattica[1] ++;
						$cd = $cd + $conteggio[$conta];
						//$Centrocampo = $Centrocampo + $conteggio[$conta];
						//$Centrocampo = $Centrocampo + intval($Centrocampo*$Bonus[$TipoMarcatura][6]/100)	; //centrocampo destra
						//$Centrocampo = $Centrocampo + intval($Centrocampo*$Bonus[$TipoTattica][6]/100);
						$Forza = $Forza + $rig['skill'];
						break;
					case 43:
					case 44:
					case 50:
					case 51:
					case 57:
					case 58:
						$Tattica[2] ++;
						$ds = $ds + $conteggio[$conta];
						//$Difesa = $Difesa + $conteggio[$conta];
						//$Difesa = $Difesa + intval($Difesa*$Bonus[$TipoMarcatura][1]/100); //difesa sinistra
						//$Difesa = $Difesa + intval($Difesa*$Bonus[$TipoTattica][1]/100);
						$Forza = $Forza + $rig['skill'];
						break;
					case 45:
					case 46:
					case 47:
					case 52:
					case 53:
					case 54:
					case 59:
					case 60:				
					case 61:
						$Tattica[2] ++;
						$dc = $dc + $conteggio[$conta];
						//$Difesa = $Difesa + $conteggio[$conta];
						//$Difesa = $Difesa + intval($Difesa*$Bonus[$TipoTattica][2]/100); //difesa centrale
						$Forza = $Forza + $rig['skill'];
						break;
					case 48:
					case 49:
					case 55:
					case 56:
					case 62:
					case 63:
						$Tattica[2] ++;
						$dd = $dd + $conteggio[$conta];
						//$Difesa = $Difesa + $conteggio[$conta];
						//$Difesa = $Difesa + intval($Difesa*$Bonus[$TipoMarcatura][3]/100); //difesa destra
						//$Difesa = $Difesa + intval($Difesa*$Bonus[$TipoTattica][3]/100);
						$Forza = $Forza + $rig['skill'];
						break;
					case 67:
						$Portiere = $conteggio[64] ; // portiere
						$Forza = $Forza + $rig['skill'];
						break;
					case 64:
					case 65:
					case 66:
					case 68:
					case 69:
						$Panchina = $Panchina + $rig['skill'];
						break;
				} // end switch
		} // endif
		$conta ++;	
	} // end while
	
	//assegnazione valori tattiche
	$as = $as + $as*$Bonus[$TipoTattica][7]/100;
	$ac = $ac + $ac*$Bonus[$TipoTattica][8]/100;
	$ad = $ad + $ad*$Bonus[$TipoTattica][9]/100;
	$cs = $cs + $cs*$Bonus[$TipoTattica][4]/100;
	$cc = $cc + $cc*$Bonus[$TipoTattica][5]/100;
	$cd = $cd + $cd*$Bonus[$TipoTattica][6]/100;
	$ds = $ds + $ds*$Bonus[$TipoTattica][1]/100;
	$dc = $dc + $dc*$Bonus[$TipoTattica][2]/100;
	$dd = $dd + $dd*$Bonus[$TipoTattica][3]/100;
	
	//assegnazione valori marcatura
	$cs = $cs + $cs*$Bonus[$TipoMarcatura][4]/100;
	$cd = $cd + $cd*$Bonus[$TipoMarcatura][6]/100;
	$ds = $ds + $ds*$Bonus[$TipoMarcatura][1]/100;
	$dd = $dd + $dd*$Bonus[$TipoMarcatura][3]/100;
	
	$Difesa = round($ds + $dc + $dd,1);
	$Centrocampo = round($cs + $cc + $cd,1);
	$Attacco = round($as + $ac + $ad,1);

	if ($Giocatori > 0)
	{
		$Forma = intval($Forma / $Giocatori);
		$Freschezza = intval($Freschezza / $Giocatori);
		$Condizione = intval($Condizione / $Giocatori);
	}
	
	$Portiere    = round($Portiere    + $Portiere    * ($b_viceallena + $b_alleportie) / 100,1);
	$Difesa      = round($Difesa      + $Difesa      * ($b_viceallena + $b_allenatore*$bonus_allenatore[0]/100) / 100,1);
	$Centrocampo = round($Centrocampo + $Centrocampo * ($b_viceallena + $b_allenatore*$bonus_allenatore[1]/100) / 100,1);
	$Attacco     = round($Attacco     + $Attacco     * ($b_viceallena + $b_allenatore*$bonus_allenatore[2]/100) / 100,1);
	$check = "Checked";
	
	$Difesa 	 = $Difesa 		+ round($Difesa		*$impegno_dif[$impegno_pos]/100,1);
	$Centrocampo = $Centrocampo + round($Centrocampo*$impegno_cen[$impegno_pos]/100,1);
	$Attacco	 = $Attacco 	+ round($Attacco	*$impegno_att[$impegno_pos]/100,1);
	 
	//CARICO LA FORMAZIONE DEL TEAM avversario
	$av_qry = "SELECT * FROM formazione WHERE f_id_team=\"$avversario\" AND f_formazione=\"$wformazione\"";
	$av_formazione_result = mysql_query($av_qry);
	if (!$av_formazione_result)
	{
			echo 'Errore nella query seleziona squadra avversaria: ' . mysql_error();
			exit();
	}
	$av_row   =   mysql_fetch_array($av_formazione_result);
	$av_conta = 1;
	$av_caratteri = array();

	while ($av_conta < 71) 
	{
		$av_conteggio[$av_conta] = 0;
		$av_riquadro[$av_conta] = $av_row[$appoggio[$av_conta]];

		if ($av_riquadro[$av_conta] == "")
		{
			$av_riquadro[$av_conta] = 0 ;
		}
			
		//NASCONDI SE IL VALORE DI MAGLIA == 0
		if ($av_riquadro[$av_conta] == 0)
		{
			$av_class[$av_conta] = "board";
		}
		else
		{
			$av_ricerca = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$avversario\" AND nr=\"$av_riquadro[$av_conta]\" ");
			if (!$av_ricerca)
			{
				echo 'Errore nella query RICERCA PARAMETRI avversario: ' . mysql_error();
				exit();
			}
			$av_rig   =   mysql_fetch_array($av_ricerca);
				
			// Associazione parametri dei giocatori solo se la casella contiene un valore di maglietta
			if ($av_conta < 64) 
				{
					$av_class[$av_conta] = "brdgioca"; //giocatore in campo
					$av_Forma = $av_Forma + $av_rig['forma'];
					$av_Freschezza = $av_Freschezza + $av_rig['fresc'];
					$av_Condizione = $av_Condizione + $av_rig['cond'];
					$av_skill = $av_rig['skill'];
					
					if (!in_array($av_rig['carattere'],$av_caratteri)) {$av_caratteri[] = $av_rig['carattere'];}
					$av_Giocatori ++;
				}
				elseif ($av_conta >=64 and $av_conta <=66)
				{
					$av_class[$av_conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($av_conta ==67 )
				{
					$av_class[$av_conta] = "brdportiere"; //giocatore in porta
					$av_Forma = $av_Forma + $av_rig['forma'];
					$av_Freschezza = $av_Freschezza + $av_rig['fresc'];
					$av_Condizione = $av_Condizione + $av_rig['cond'];
					$av_skill = $av_rig['skill'];
					if (!in_array($av_rig['carattere'],$av_caratteri)) {$av_caratteri[] = $av_rig['carattere'];}
					$av_Giocatori ++;
				}
				elseif ($av_conta ==68)
				{
					$av_class[$av_conta] = "brdpanchina"; //giocatore in panchina
				}
				elseif ($av_conta ==69)
				{
					$av_class[$av_conta] = "brdpanchinaportiere"; //portiere in panchina
				}
				// dati del singolo giocatore
				$av_wpo = $av_rig['po'];
				$av_wdf = $av_rig['df'];
				$av_wcn = $av_rig['cn'];
				$av_wpa = $av_rig['pa'];
				$av_wrg = $av_rig['rg'];
				$av_wcr = $av_rig['cr'];
				$av_wtc = $av_rig['tc'];
				$av_wtr = $av_rig['tr'];
				$av_wes = $av_rig['esp'];
				
				$av_controllo = 15; // valore dell'esperienza
				$formula = "Formula 2";
							
				// CARICO I DATI DAL CALCOLATORE
				$calc_result = mysql_query("SELECT * FROM calcolatore WHERE formula=\"$formula\" ORDER BY ord");
				if (!$calc_result) { echo 'Errore nella query calcolatore avversario: ' . mysql_error(); exit(); }
				
				$av_wii = 1; 
				while   ($av_rga   =   mysql_fetch_array($calc_result)) 
				{
					// CONTROLLA TUTTI I CASI DELLE CASELLE
					switch ($av_wii) { 
						//qsinistro
						case 1:
						case 2:
						case 8:
						case 9:
						case 15:
						case 16:
						case 22:
						case 23:
						case 29:
						case 30:
						case 36:
						case 37:
						case 43:
						case 44:
						case 50:
						case 51:
						case 57:
						case 58:
							if (strtoupper($av_rig['piede']) == "R")  {$av_pdd = -6;}
							elseif (strtoupper($av_rig['piede']) == "L") {$av_pdd = 6;}
							else{$av_pdd = 4;}
							break;
						//qcentrale
						case 3:
						case 4:
						case 5:
						case 10:
						case 11:
						case 12:
						case 17:
						case 18:
						case 19:
						case 24:
						case 25:
						case 26:
						case 31:
						case 32:
						case 33:
						case 38:
						case 39:
						case 40:
						case 45:
						case 46:
						case 47:
						case 52:
						case 53:
						case 54:
						case 59:
						case 60:				
						case 61:
							if (strtoupper($av_rig['piede']) == "R")  {$av_pdd = 4;}
							elseif (strtoupper($av_rig['piede']) == "L") {$av_pdd = 4;}
							else{$av_pdd = 7;}
							break;
						//qdestro
						case 6:
						case 7:
						case 13:
						case 14:
						case 20:
						case 21:
						case 27:
						case 28:
						case 34:
						case 35:
						case 41:
						case 42:
						case 48:
						case 49:
						case 55:
						case 56:
						case 62:
						case 63:
							if (strtoupper($av_rig['piede']) == "R")  {$av_pdd = 6;}
							elseif (strtoupper($av_rig['piede']) == "L") {$av_pdd = -6;}
							else{$av_pdd = 4;}
							break;
							
						//q0	
						case 64:
							$av_pdd = 4;
							break;
					} // end switch
	$av_conteggio[$av_wii] = $av_pdd + $av_wpo*$av_rga['po'] + $av_wdf*$av_rga['df'] + $av_wcn*$av_rga['cn'] + $av_wpa*$av_rga['pa'] ;
	$av_conteggio[$av_wii] = $av_conteggio[$av_wii] + $av_wrg*$av_rga['rg'] + $av_wcr*$av_rga['cr'];
	$av_conteggio[$av_wii] = round(($av_conteggio[$av_wii] + $av_wtc*$av_rga['tc'] + $av_wtr*$av_rga['tr'])/(101-$av_controllo),1) ;
					
					$av_wii ++ ;
				}
				// CONTROLLA TUTTI I CASI DELLE CASELLE
				switch ($av_conta) {
					case 1:
					case 2:
					case 8:
					case 9:
					case 15:
					case 16:
						$av_Tattica[0] ++;
						$av_as = $av_as + $av_conteggio[$av_conta];
						//$av_Attacco = $av_Attacco + $av_conteggio[$av_conta];
						//$av_Attacco = $av_Attacco + intval($av_Attacco*$Bonus[$av_TipoTattica][7]/100); //attacco sinistro Q7
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 3:
					case 4:
					case 5:
					case 10:
					case 11:
					case 12:
					case 17:
					case 18:
					case 19:
						$av_Tattica[0] ++;
						$av_ac = $av_ac + $av_conteggio[$av_conta];
						//$av_Attacco = $av_Attacco + $av_conteggio[$av_conta];
						//$av_Attacco = $av_Attacco + intval($av_Attacco*$Bonus[$av_TipoTattica][8]/100); // attacco centrale
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 6:
					case 7:
					case 13:
					case 14:
					case 20:
					case 21:
						$av_Tattica[0] ++;
						$av_ad = $av_ad + $av_conteggio[$av_conta];
						//$av_Attacco = $av_Attacco + $av_conteggio[$av_conta];
						//$av_Attacco = $av_Attacco + intval($av_Attacco*$Bonus[$av_TipoTattica][9]/100); // attacco destro
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 22:
					case 23:
					case 29:
					case 30:
					case 36:
					case 37:
						$av_Tattica[1] ++;
						$av_cs = $av_cs + $av_conteggio[$av_conta];
						//$av_Centrocampo = $av_Centrocampo + $av_conteggio[$av_conta];
						//$av_Centrocampo = $av_Centrocampo + intval($av_Centrocampo*$Bonus[$av_TipoMarcatura][4]/100); //centr. sin.
						//$av_Centrocampo = $av_Centrocampo + intval($av_Centrocampo*$Bonus[$av_TipoTattica][4]/100);
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 24:
					case 25:
					case 26:
					case 31:
					case 32:
					case 33:
					case 38:
					case 39:
					case 40:
						$av_Tattica[1] ++;
						$av_cc = $av_cc + $av_conteggio[$av_conta];
						//$av_Centrocampo = $av_Centrocampo + $av_conteggio[$av_conta];
						//$av_Centrocampo = $av_Centrocampo + intval($av_Centrocampo*$Bonus[$av_TipoTattica][5]/100); //centr. centrale
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 27:
					case 28:
					case 34:
					case 35:
					case 41:
					case 42:
						$av_Tattica[1] ++;
						$av_cd = $av_cd + $av_conteggio[$av_conta];
						//$av_Centrocampo = $av_Centrocampo + $av_conteggio[$av_conta];
						//$av_Centrocampo = $av_Centrocampo + intval($av_Centrocampo*$Bonus[$av_TipoMarcatura][6]/100)	; //centr.destra
						//$av_Centrocampo = $av_Centrocampo + intval($av_Centrocampo*$Bonus[$av_TipoTattica][6]/100);
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 43:
					case 44:
					case 50:
					case 51:
					case 57:
					case 58:
						$av_Tattica[2] ++;
						$av_ds = $av_ds + $av_conteggio[$av_conta];
						//$av_Difesa = $av_Difesa + $av_conteggio[$av_conta];
						//$av_Difesa = $av_Difesa + intval($av_Difesa*$Bonus[$av_TipoMarcatura][1]/100); //difesa sinistra
						//$av_Difesa = $av_Difesa + intval($av_Difesa*$Bonus[$av_TipoTattica][1]/100);
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 45:
					case 46:
					case 47:
					case 52:
					case 53:
					case 54:
					case 59:
					case 60:				
					case 61:
						$av_Tattica[2] ++;
						$av_dc = $av_dc + $av_conteggio[$av_conta];
						//$av_Difesa = $av_Difesa + $av_conteggio[$av_conta];
						//$av_Difesa = $av_Difesa + intval($av_Difesa*$Bonus[$av_TipoTattica][2]/100); //difesa centrale
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 48:
					case 49:
					case 55:
					case 56:
					case 62:
					case 63:
						$av_Tattica[2] ++;
						$av_dd = $av_dd + $av_conteggio[$av_conta];
						//$av_Difesa = $av_Difesa + $av_conteggio[$av_conta];
						//$av_Difesa = $av_Difesa + intval($av_Difesa*$Bonus[$av_TipoMarcatura][3]/100); //difesa destra
						//$av_Difesa = $av_Difesa + intval($av_Difesa*$Bonus[$av_TipoTattica][3]/100);
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 67:
						$av_Portiere = $av_conteggio[64] ; // portiere
						$av_Forza = $av_Forza + $av_rig['skill'];
						break;
					case 64:
					case 65:
					case 66:
					case 68:
					case 69:
						$av_Panchina = $av_Panchina + $av_rig['skill'];
						break;
				} // end switch
		} // endif
		$av_conta ++;	
	} // end while
	
	//assegnazione valori tattiche
	$av_as = $av_as + $av_as*$Bonus[$av_TipoTattica][7]/100;
	$av_ac = $av_ac + $av_ac*$Bonus[$av_TipoTattica][8]/100;
	$av_ad = $av_ad + $av_ad*$Bonus[$av_TipoTattica][9]/100;
	$av_cs = $av_cs + $av_cs*$Bonus[$av_TipoTattica][4]/100;
	$av_cc = $av_cc + $av_cc*$Bonus[$av_TipoTattica][5]/100;
	$av_cd = $av_cd + $av_cd*$Bonus[$av_TipoTattica][6]/100;
	$av_ds = $av_ds + $av_ds*$Bonus[$av_TipoTattica][1]/100;
	$av_dc = $av_dc + $av_dc*$Bonus[$av_TipoTattica][2]/100;
	$av_dd = $av_dd + $av_dd*$Bonus[$av_TipoTattica][3]/100;
	
	//assegnazione valori marcatura
	$av_cs = $av_cs + $av_cs*$Bonus[$av_TipoMarcatura][4]/100;
	$av_cd = $av_cd + $av_cd*$Bonus[$av_TipoMarcatura][6]/100;
	$av_ds = $av_ds + $av_ds*$Bonus[$av_TipoMarcatura][1]/100;
	$av_dd = $av_dd + $av_dd*$Bonus[$av_TipoMarcatura][3]/100;
	
	$av_Difesa = round($av_ds + $av_dc + $av_dd,1);
	$av_Centrocampo = round($av_cs + $av_cc + $av_cd,1);
	$av_Attacco = round($av_as + $av_ac + $av_ad,1);
	
	
	
	if ($av_Giocatori >0)
	{
		$av_Forma = intval($av_Forma / $av_Giocatori);
		$av_Freschezza = intval($av_Freschezza / $av_Giocatori);
		$av_Condizione = intval($av_Condizione / $av_Giocatori);
	}
	
	$av_Portiere = round($av_Portiere    + $av_Portiere    * ($av_b_viceallena + $av_b_alleportie) / 100,1);
	$av_Difesa   = round($av_Difesa      + $av_Difesa      * ($av_b_viceallena + $av_b_allenatore*$av_bonus_allenatore[0]/100) / 100,1);
	$av_Centrocampo=round($av_Centrocampo + $av_Centrocampo * ($av_b_viceallena + $av_b_allenatore*$av_bonus_allenatore[1]/100) / 100,1);
	$av_Attacco   = round($av_Attacco     + $av_Attacco     * ($av_b_viceallena + $av_b_allenatore*$av_bonus_allenatore[2]/100) / 100,1);
	$av_check = "Checked";

	$av_Difesa 	 = $av_Difesa 		+ round($av_Difesa		*$av_impegno_dif[$av_impegno_pos]/100,1);
	$av_Centrocampo = $av_Centrocampo + round($av_Centrocampo*$av_impegno_cen[$av_impegno_pos]/100,1);
	$av_Attacco	 = $av_Attacco 	+ round($av_Attacco	*$av_impegno_att[$av_impegno_pos]/100,1);
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//FINE CALCOLO SQUADRA AVVERSARIA	 
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	$squadra = array();
	$result1 = mysql_query("SELECT * FROM members WHERE 1");
	while ($row1   =   mysql_fetch_array($result1))
	{	
		$conta_giocatori = 0;
		$nome_squadra = $row1['team'];
		$qry = "SELECT * FROM formazione WHERE f_id_team=\"$nome_squadra\" AND f_formazione=\"Formazione 1\" ";
		$result2 = mysql_query($qry);
		while ($row2   =   mysql_fetch_array($result2))
		{	
			for ($xy = 1; $xy < 71; $xy ++)
			{
				if ($row2[$appoggio[$xy]] != 0)
				{
					$conta_giocatori ++;
				}
			}
			if ($conta_giocatori >= 11)
			{
				if (!in_array($row1['team'],$squadra))
				{
					$squadra[] = $row1['team'];
				}
			}
		}
	}
	//VISUALIZZAZIONE DATI A SCHEZMO
	echo "<h1>";
	echo "<table width='120' border='0' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td>Sfida 1 vs 1</td>";
	echo "</tr>";
	echo "</table>";
	echo "</h1>";

	echo "<span id='formazioni'>";
	echo "<form>";
	echo "<fieldset style='width: 97%;'>";
	
	echo "<legend>Formazioni</legend>";
	echo "<table border='0' width='570'>";
	echo "<tr height='400'><td>&nbsp;</td></tr>";	
	echo "</table>";	
	
	echo "<span id='c_squadra'>$nome_team</span>";
	echo "<span id='c_vs'>VS</span>";
	echo "<span id='f_squadra'>";
	
	echo "<select name=\"ws_team\" id=\"ws_team\" onchange='javascript:scegli_squadra();' class='fld_y'>";
	echo "  <option value=\"$avversario\" selected='selected'>$avversario</option>
			<option value='$row[pos]'>__________</option>";
			foreach ($squadra as $data)
			{
				echo "<option value=\"$data\">$data</option>";
			}
	echo "</select></span>";
	
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
	
	$disable = "disabled";
	if ($avversario != "")
	{ 
		$disable = "";
	}
	
	
	echo "
	<span id='avvia_partita'>
	<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
	<td>
	<input type='button' id='avvia' value='Avvia' $disable onclick='javascript:inizia();' class='fieldbutton'  >
	</td>
	</tr>
	</table>
	</span>
	";
	echo "</fieldset>";
	echo "</form>";
	echo "</span>";

	
	if ($secondi < 1)
	{
		$qry = "";
	}	
	
	$c_stanchezza = $Freschezza;
	$f_stanchezza = $av_Freschezza;
	
	$chiedi = "SELECT * FROM partita WHERE id_team=\"$nome_team\"";
	$resultx = mysql_query($chiedi);
	if (!$resultx)
	{
		echo 'Errore nella query partita1: ' . mysql_error();
		exit();
	}
	$totalex = mysql_num_rows($resultx);
	if ($totalex != 0)
	{
		$row = mysql_fetch_array($resultx);
		
		$prog = $row['prog'];
		$c_fresc = $row['c_frs'];
		$c_azioni = $row['c_azioni'];
		$c_stanchezza = $c_fresc + $c_azioni;
		
		$f_fresc = $row['f_frs'];
		$f_azioni = $row['f_azioni'];
		$f_stanchezza = $f_fresc + $c_azioni;
	}

	echo "<span id='svolgimento'>";
	echo "<form name='paginavuota'>";
	echo "<fieldset style='width: 97%;'>";
	
	echo "<legend>Partita</legend>";
	echo "<table border='0' width='570'>";
	echo "<tr>";
	echo "<td class='tabella1' align='right' width='40%'>$nome_team</td>";
	echo "<td align='center'>-</td>";
	echo "<td class='tabella2' align='left' width='40%'>$avversario</td></tr>";
	echo "<tr><td align='right'>";

	$corri = $c_stanchezza;
	echo "100% ";
	if ($corri >100) { $corri = 100; }
	if ($corri <0) { $corri = 0; }
			
	for ($qw=100; $qw >= 0 ; $qw--)
	{
		$nome = "img_casa_".$qw;
		if ($qw > $corri) { $immagina = $immbianca ;	} else { $immagina = $immbarretta ; }
		echo "<img id=$nome name=$nome src=$immagina >";
	}
	echo " 0%";
	
	echo "</td>	<td>&nbsp;</td>	<td align='left'>";

	$corri = $f_stanchezza;
	echo "0% ";
	if ($corri >100) { $corri = 100; }
	for ($qw=0; $qw <= 100 ; $qw++)
	{
		$nome = "img_avversario_".$qw;
		if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immbianca ; }
		echo "<img id=$nome name=$nome src=$immagina >";
	}
	echo " 100%";
	echo "</td>	</tr>";
	
	echo "<tr class='tabella3'><td align='right'>$c_goal</td>";
	echo "<td align='center'>:</td>";
	echo "<td align='left'>$f_goal</td></tr>";

	echo "<tr><td></td><td align='center'><div id='contatore'></div>min.</td></tr>";
	echo "<tr height='292'><td>&nbsp;</td></tr>";	
	echo "</table>";	
	
	echo "<span id='telecronaca'>";
	echo "<div align='left' style='width:540px;height:280px;overflow-y: scroll; border:1px;'>";
	$qry = "SELECT * FROM telecronaca WHERE id_team=\"$nome_team\" ORDER BY prog DESC";
	$res = mysql_query($qry);
	
	if (mysql_num_rows($res) < 1)
	{	
		echo "La partita non è ancora iniziata.";
	}
	else
	{
		//CARICO LA TELECRONACA
		while ($row   =   mysql_fetch_array($res))
		{
			$riga = $row['descri'];

			echo "<div id='MsgPsa'>";
			echo "<br><br>$row[min]&deg;: ";
			echo "$riga<br>";
			echo "</div>";
		}
		echo "<script>";
		echo "$('#MsgPsa').slideUp(0).delay(800).fadeIn(1200);";
		echo "</script>";
	}
	echo "</div>";
	echo "</span>";
	
	$cTattica = $Tattica[2]." ".$Tattica[1]." ".$Tattica[0];
	$fTattica = $av_Tattica[2]." ".$av_Tattica[1]." ".$av_Tattica[0];
	echo "<input id='c_po' type='hidden' value='$Portiere' />";
	echo "<input id='c_df' type='hidden' value='$Difesa' />";
	echo "<input id='c_cn' type='hidden' value='$Centrocampo' />";
	echo "<input id='c_at' type='hidden' value='$Attacco' />";
	echo "<input id='c_forza' type='hidden' value='$Forza' />";
	echo "<input id='c_forma' type='hidden' value='$Forma' />";
	echo "<input id='c_freschezza' type='hidden' value='$Freschezza' />";
	echo "<input id='c_condizione' type='hidden' value='$Condizione' />";
	echo "<input id='c_tattica' type='hidden' value='$cTattica' />";
	echo "<input id='c_totale' type='hidden' value='$StampaForza' />";
		
	echo "<input id='f_po' type='hidden' value='$av_Portiere' />";
	echo "<input id='f_df' type='hidden' value='$av_Difesa' />";
	echo "<input id='f_cn' type='hidden' value='$av_Centrocampo' />";
	echo "<input id='f_at' type='hidden' value='$av_Attacco' />";
	echo "<input id='f_forza' type='hidden' value='$av_Forza' />";
	echo "<input id='f_forma' type='hidden' value='$av_Forma' />";
	echo "<input id='f_freschezza' type='hidden' value='$av_Freschezza' />";
	echo "<input id='f_condizione' type='hidden' value='$av_Condizione' />";
	echo "<input id='f_tattica' type='hidden' value='$fTattica' />";
	echo "<input id='f_totale' type='hidden' value='$av_StampaForza' />";
	
	echo "<input id='cgol' type='hidden' value='$c_goal' />";
	echo "<input id='fgol' type='hidden' value='$f_goal' />";
	

?>	

</body>
</html>
