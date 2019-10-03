<?php
require_once('auth.php');
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
$check = "";
$filosofia = "bilanciato";
$impegno_arr = array(-7.5,-5,0,5,7.5);
$lista_impegno = array("50","75","100","125","150");

// CREO ELENCO DELLE TATTICHE E MARCATURE
$tattica_result = mysql_query("SELECT * FROM bonus_tattica WHERE 1 ORDER BY t_id");
if (!$tattica_result) {
    echo 'Errore nella query bonus tattica: ' . mysql_error();
    exit();
}
$conta = 1;
while   ($row   =   mysql_fetch_array($tattica_result))
{
	$ListaTattica[$conta] = $row['t_descrizione'];
	
	//array contenente bonus tattiche e marcature
	$Bonus[$ListaTattica[$conta]] = array($row['t_id'],$row['q1'],$row['q2'],$row['q3'],$row['q4'],$row['q5'],
										  $row['q6'],$row['q7'],$row['q8'],$row['q9']);
	$conta++;
}

// CARICO L'ALLENAMENTO DELLE TATTICHE 
$result_allena = mysql_query("SELECT * FROM allena_tattiche WHERE a_id_team=\"$nome_team\"");
if (!$result_allena)
{
	echo 'Errore nella query allena tattiche: ' . mysql_error();
	exit();
}
$riga_tattica = mysql_fetch_array($result_allena) ;
if (count($riga_tattica != 0))
{	
	$aggiorna["Nessuna"] = 70;  // 
	$aggiorna["Pressing"] = round($riga_tattica['ta_press_val']/327*100,1);
	$aggiorna["Contropiede"] = round($riga_tattica['ta_contr_val']/327*100,1);
	$aggiorna["Possesso palla"] = round($riga_tattica['ta_poss_val']/327*100,1);
	$aggiorna["Pallone lungo"] = round($riga_tattica['ta_pall_val']/327*100,1);
	$aggiorna["Gioco sulle fasce"] = round($riga_tattica['ta_gioc_val']/327*100,1);
	$aggiorna["Catenaccio"] = round($riga_tattica['ta_cate_val']/327*100,1);
}

// CARICO L'EFFICIENZA DEGLI ALLENATORI
$all_result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ");
if (!$all_result) {
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

// CARICO TABELLA BONUS ALLENATORE
$all_result = mysql_query("SELECT * FROM bonus_allenatore WHERE b_descrizione = \"$filosofia\" ");
if (!$all_result) {
    echo 'Errore nella query bonus allenatore: ' . mysql_error();
    exit();
}
$row = mysql_fetch_array($all_result);
$bonus_allenatore = array($row['b_dif'],$row['b_cen'],$row['b_att']);

// CARICO LA TATTICA E LA MARCATURA DEL TEAM
$tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\" ");
if (!$tipo_result) {
    echo 'Errore nella query tattica: ' . mysql_error();
    exit();
}
$row   =   mysql_fetch_array($tipo_result);
$TipoTattica = $row['t_tattica'];
$TipoMarcatura = $row['t_marcatura'];
$QualeFormazione = $row['t_formazione'];
$TipoImpegno = $row['t_impegno'];
$Fuorigioco = $row['t_fuorigioco'];

if ($QualeFormazione == "")
{
	$TipoTattica = "Nessuna";
	$TipoMarcatura = "Marcatura a uomo";
	$QualeFormazione = "Formazione 1";
	$TipoImpegno = "100";
	$Fuorigioco = "NO";
}

$conta = 0;
foreach ($lista_impegno as $test) {
	if ($test == $TipoImpegno) { $impegno_pos = $conta ; }
	$conta ++;
}
//CARICO LA FORMAZIONE DEL TEAM
$formazione_result = mysql_query("SELECT * FROM formazione WHERE f_id_team=\"$nome_team\" AND f_formazione=\"$QualeFormazione\" ");
if (!$formazione_result) {
		echo 'Errore nella query: ' . mysql_error();
		exit();
}
$row   =   mysql_fetch_array($formazione_result);
$conta = 1;
$caratteri = array();
$class = array();
$title = array();

while ($conta < 74) 
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
		if ($conta < 70)
		{
			//$class[$conta] = "board";
			$title[$conta] = "";
		}
		else
		{
			$class[$conta] = "brdgioca";
			$title[$conta] = "Scegli un compito per i giocatori";
		}
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
				
				$title[$conta] = "Clicca sul giocatore per toglierlo dalla squadra";
			}
			elseif ($conta ==64 )
			{
				$class[$conta] = "brdportiere"; //giocatore in porta
				$Forma = $Forma + $rig['forma'];
				$Freschezza = $Freschezza + $rig['fresc'];
				$Condizione = $Condizione + $rig['cond'];
				$skill = $rig['skill'];
				if (!in_array($rig['carattere'],$caratteri)) { $caratteri[] = $rig['carattere'];	}
				$Giocatori ++;
				$title[$conta] = "Clicca sul giocatore per toglierlo dalla squadra";
			}
			elseif ($conta >=65 and $conta <=68)
			{
				$class[$conta] = "brdpanchina"; //giocatore in panchina
				$title[$conta] = "Clicca sul giocatore per toglierlo dalla panchina";
			}

			elseif ($conta ==69)
			{
				$class[$conta] = "brdpanchinaportiere"; //portiere in panchina
				$title[$conta] = "Clicca sul giocatore per toglierlo dalla squadra";
			}
			elseif ($conta >=70 )
			{
				$class[$conta] = "brdgioca"; //giocatore in campo
				$title[$conta] = "Clicca sul giocatore per toglierlo dai compiti";
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
			
			if ($conta < 65)// or $conta == 67) 
			{
				$numero[] = $rig['nr'];
				
				//RIGORISTA
				switch ($rig['carattere'])
				{
					case "razionale":
						$conteggio_rigori = ($skill+$wtc*1.3+$wtr)+20;
						break;
					case "emotivo":
						$conteggio_rigori = ($skill+$wtc+$wtr)-20;		
						break;
					default:
						$conteggio_rigori = ($skill+$wtc*1.2+$wtr);			
				}
				if ($rig['talento'] == "Rigori")
				{
					$conteggio_rigori = $conteggio_rigori * (2*$rig['qta']);		
				}
				$rigori[] = $conteggio_rigori;
				
				// calcia punizioni
				if ($rig['talento'] == "Calci di punizione")
				{
					$conteggio_punizioni = ($wtc*1.3+$wtr)* (2*$rig['qta']);
				}
				else
				{
					$conteggio_punizioni = ($wtc*1.3+$wtr);
				}
				$punizioni[] = $conteggio_punizioni;
				
				// calcia angoli
				if ($rig['talento'] == "Calcio d´angolo")
				{	
					$conteggio_angoli = ($wtc*1.3+$wcr)*(2*$rig['qta']);
				}
				else
				{
					$conteggio_angoli = ($wtc*1.3+$wcr);
				}
				$angoli[] = $conteggio_angoli;	
				
				// capitano
				switch ($rig['carattere'])
				{
					case "carismatico":
						$conteggio_capitano = ($skill)*25;
						break;
					case "popolare":
						$conteggio_capitano = ($skill)*10;
						break;
					case "introverso":
						$conteggio_capitano = ($skill);
						break;
					default:
					$conteggio_capitano = ($skill)*5;
				}
				$conteggio_capitano = $conteggio_capitano + round(($wes/100*15),1);	
				$capitani[] = $conteggio_capitano;
			}
								
			// DATI DI CONTROLLO PER IL CALCOLATORE
			$controllo = 15;
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
					$Forza = $Forza + $rig['skill'];
					break;
				case 64:
					$Portiere = $conteggio[64] ; // portiere
					$Forza = $Forza + $rig['skill'];
					break;
				case 65:
				case 66:
				case 67:
				case 68:
				case 69:
					$Panchina = $Panchina + $rig['skill'];
					break;
			} // end switch
	} // endif
	
	$conta ++;	
} // end while


// assegnazione valori rigori punizioni capitano e calci d'angolo
$ws_rigorista = "";
$ws_punizione = "";
$ws_angolo = "";
$ws_capitano = "";

if (isset($rigori))
{
	$wrigori = array($rigori,$numero);
	$wpunizioni = array($punizioni,$numero);
	$wangoli = array($angoli,$numero);
	$wcapitani = array($capitani,$numero);
					
	array_multisort($wrigori[0], SORT_DESC, $wrigori[1], SORT_DESC);
	array_multisort($wpunizioni[0], SORT_DESC, $wpunizioni[1], SORT_DESC);
	array_multisort($wangoli[0], SORT_DESC, $wangoli[1], SORT_DESC);
	array_multisort($wcapitani[0], SORT_DESC, $wcapitani[1], SORT_DESC);

	$ws_rigorista = $wrigori[1][0];
	$ws_punizione = $wpunizioni[1][0];
	$ws_angolo = $wangoli[1][0];
	$ws_capitano = $wcapitani[1][0];
}

$ws_specialita[0] = $ws_punizione;
$ws_specialita[1] = $ws_angolo;
$ws_specialita[2] = $ws_rigorista;
$ws_specialita[3] = $ws_capitano;

// ASSEGNAZIONI VALORI PERCNTUALI DELLE TATTICHE
$result = mysql_query("SELECT * FROM allena_tattiche WHERE a_id_team=\"$nome_team\"");
if (!$result)
{
	echo 'Errore nella query: ' . mysql_error();
	exit();
}
if (mysql_num_rows($result) > 0)
{
	$rig = mysql_fetch_array($result) ;

	$righi[1] = -10;
	$righi[2] = $rig['ta_press_val'];
	$righi[3] = $rig['ta_contr_val'];
	$righi[4] = $rig['ta_poss_val'];
	$righi[5] = $rig['ta_pall_val'];
	$righi[6] = $rig['ta_gioc_val'];
	$righi[7] = $rig['ta_cate_val'];
	$righi[8] = -10;
	$righi[9] = -10;
}
else
{
	$righi[1] = -10;
	$righi[2] = 0;
	$righi[3] = 0;
	$righi[4] = 0;
	$righi[5] = 0;
	$righi[6] = 0;
	$righi[7] = 0;
	$righi[8] = -10;
	$righi[9] = -10;
}			

$a_tattica = array($righi,$ListaTattica);
array_multisort($a_tattica[0], SORT_DESC, $a_tattica[1], SORT_DESC);

/* ******** VISUALIZZAZIONE DATI A SCHERMO ************/
echo "<div id='perc_tattiche'>";
	echo "<fieldset style='width: 97%;'>";
		echo "<legend>Percentuale di allenamento</legend>";
		echo "<table id='table_tattiche' cellpadding='0' cellspacing='0'>";
		for ($ind=0; $ind < 6; $ind++)
		{
			$num = 1 + $ind;
			$wnome = $a_tattica[1][$ind];
			$wskill = round($a_tattica[0][$ind]/327*100,1);
					
			echo "<tr>
					<td>$num&deg;</td>	<td>$wnome</td>	<td><center>$wskill %</td>
				  </tr>";
		}
		echo "</table>";
	echo "</fieldset>";
echo "</div>";
 
echo "<div id='speciale_formazione'>";
	echo "<fieldset style='width: 97%;'>";
	echo "<legend>Compiti Suggeriti</legend>";
		echo "<table border='0' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td><img src='images/t_calcidipunizione.png' width=19px height=19px /></td>";
				echo "<td>&nbsp;</td>";
				echo "<td>Punizioni</td>";
				echo "<td>&nbsp;</td>";
				echo "<td class='compiti'><div id='NONmettiqui_70' class='cl_70'><input name='btn' class=\"$class[70]\" value='$ws_specialita[0]' type='button' onclick='javascript:svuota(70);' id='bt_70' title='$title[70]'></div></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td><img src='images/t_calciodangolo.png' width=19px height=19px /></td>";
				echo "<td>&nbsp;</td>";
				echo "<td>Angoli</td>";
				echo "<td>&nbsp;</td>";
				echo "<td class='compiti'><div id='NONmettiqui_71' class='cl_71'><input name='btn' class=\"$class[71]\" value='$ws_specialita[1]' type='button' onclick='javascript:svuota(71);' id='bt_71' title='$title[71]'></div></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td><img src='images/t_rigori.png' width=19px height=19px /></td>";
				echo "<td>&nbsp;</td>";
				echo "<td>Rigori</td>";
				echo "<td>&nbsp;</td>";
				echo "<td class='compiti'><div id='NONmettiqui_72' class='cl_72'><input name='btn' class=\"$class[72]\" value='$ws_specialita[2]' type='button' onclick='javascript:svuota(72);' id='bt_72' title='$title[72]'></div></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td><img src='images/t_capitano.png' width=19px height=19px /></td>";
				echo "<td>&nbsp;</td>";
				echo "<td>Capitano</td>";
				echo "<td>&nbsp;</td>";
				echo "<td class='compiti'><div id='NONmettiqui_73' class='cl_73'><input name='btn' class=\"$class[73]\" value='$ws_specialita[3]' type='button' onclick='javascript:svuota(73);' id='bt_73' title='$title[73]'></div></td>";
			echo "</tr>";
		echo "</table>";
	echo "</fieldset>";
echo "</div>";
	
	
echo "<div id='form_tattiche'>";
echo "<hr>";
echo "<table border='1' cellpadding='5' cellspacing='5'>";
echo "
	<tr>
		<td>Tattica</td>
		<td>
		<select name='ws_TipoTattica' id='ws_TipoTattica' onchange='javascript:Agg_tattiche();' class='fld_y'>
			<option value='$TipoTattica' selected='selected'>$TipoTattica</option>
			<option value='$TipoTattica'>---------------</option>
			<option value='$ListaTattica[1]'>$ListaTattica[1]</option>
			<option value='$ListaTattica[2]'>$ListaTattica[2]</option>
			<option value='$ListaTattica[3]'>$ListaTattica[3]</option>
			<option value='$ListaTattica[4]'>$ListaTattica[4]</option>
			<option value='$ListaTattica[5]'>$ListaTattica[5]</option>
			<option value='$ListaTattica[6]'>$ListaTattica[6]</option>
			<option value='$ListaTattica[7]'>$ListaTattica[7]</option>
		</select>
		</td>
	</tr>

	<tr>
		<td>Impegno</td>	
		<td>
		<select name='ws_impegno' id='ws_impegno' onchange='javascript:Agg_tattiche();' class='fld_y'>
			<option value='$TipoImpegno' selected>$TipoImpegno%</option>
			<option value='$TipoImpegno'>------</option>
			<option value='$lista_impegno[0]'>$lista_impegno[0]%</option>
			<option value='$lista_impegno[1]'>$lista_impegno[1]%</option>
			<option value='$lista_impegno[2]'>$lista_impegno[2]%</option>
			<option value='$lista_impegno[3]'>$lista_impegno[3]%</option>
			<option value='$lista_impegno[4]'>$lista_impegno[4]%</option>								
		</select>
		</td>
	</tr>

	<tr>
		<td>Marcatura</td>
		<td>
		<select name='ws_TipoMarcatura' id='ws_TipoMarcatura' onchange='javascript:Agg_tattiche();' class='fld_y'>
			<option value='$TipoMarcatura' selected='selected'>$TipoMarcatura</option>
			<option value='$TipoMarcatura'>--------------------------</option>
			<option value='$ListaTattica[8]'>$ListaTattica[8]</option>
			<option value='$ListaTattica[9]'>$ListaTattica[9]</option>
		</select>
		</td>
	</tr>
	
	<tr>
		<td>Fuorigioco</td>
		<td>
		<select name='ws_fuorigioco' id='ws_fuorigioco' onchange='javascript:Agg_tattiche();' class='fld_y'>
			<option value='$Fuorigioco' selected='selected'>$Fuorigioco</option>
			<option value='$Fuorigioco'>-----</option>
			<option value='NO'>NO</option>
			<option value='SI'>SI</option>
		</td>
		<div class='ch_tattica'><input value='$tabella' type='hidden' id='ws_pagina' ></div>
	</tr>
		
	
	";

	
echo "
</tr>
</table>
</div>
";






	
	
echo "</div>";


?>



