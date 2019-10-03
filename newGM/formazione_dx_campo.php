<?php
require_once('auth.php');
?><head>
	<link href="formazione.css" rel="stylesheet" type="text/css" />
</head>
<?php
//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY RIQUADRO CHE CONTERRA' IL NUMERO DI MAGLIA
$appoggio= array('f_id_team','f_1','f_2','f_3','f_4','f_5','f_6','f_7','f_8','f_9','f_10',
					     'f_11','f_12','f_13','f_14','f_15','f_16','f_17','f_18','f_19','f_20',
						 'f_21','f_22','f_23','f_24','f_25','f_26','f_27','f_28','f_29','f_30',
						 'f_31','f_32','f_33','f_34','f_35','f_36','f_37','f_38','f_39','f_40',
						 'f_41','f_42','f_43','f_44','f_45','f_46','f_47','f_48','f_49','f_50',
						 'f_51','f_52','f_53','f_54','f_55','f_56','f_57','f_58','f_59','f_60',
						 'f_61','f_62','f_63','f_64','f_65','f_66','f_67','f_68','f_69','f_70',
						 'f_71','f_72','f_73',
						 'f_formazione');


							 
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

if ($QualeFormazione == "" )
{
	$TipoTattica = "Nessuna";
	$TipoMarcatura = "Marcatura a uomo";
	$QualeFormazione = "Formazione 1";
	$TipoImpegno = "100";
}
if (isset($_REQUEST['formazione']))
{
	$QualeFormazione = $_REQUEST['formazione'];
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
			$title[$conta] = "";
		}
		else
		{
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


for ($ax = 0; $ax <=3; $ax++)
{
	if ($riquadro[70+$ax] == 0)
	{
		$ws_specialita[] = "";
	}
	else
	{
		$ws_specialita[] = $riquadro[70+$ax];
	}
}


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

if ($Giocatori >0)
{
	$Forma = intval($Forma / $Giocatori);
	$Freschezza = intval($Freschezza / $Giocatori);
	$Condizione = intval($Condizione / $Giocatori);
}

$Portiere    = round($Portiere    + $Portiere    * ($b_viceallena + $b_alleportie) / 100,1);
$Difesa      = round($Difesa      + $Difesa      * ($b_viceallena + $b_allenatore*$bonus_allenatore[0]/100) / 100,1);
$Centrocampo = round($Centrocampo + $Centrocampo * ($b_viceallena + $b_allenatore*$bonus_allenatore[1]/100) / 100,1);
$Attacco     = round($Attacco     + $Attacco     * ($b_viceallena + $b_allenatore*$bonus_allenatore[2]/100) / 100,1);

$Difesa 	 = $Difesa 		+ round($Difesa		*$impegno_arr[$impegno_pos]/100,1);
$Centrocampo = $Centrocampo + round($Centrocampo*$impegno_arr[$impegno_pos]/100,1);
$Attacco	 = $Attacco 	+ round($Attacco	*$impegno_arr[$impegno_pos]/100,1);

$Difesa = round($Difesa*$aggiorna[$TipoTattica]/100,1);
$Centrocampo = round($Centrocampo*$aggiorna[$TipoTattica]/100,1);
$Attacco = round($Attacco*$aggiorna[$TipoTattica]/100,1);
 
$ContaCar = count($caratteri);
$Malus = 0;
if ($ContaCar < 5 ) 
{ 
	$Malus = round(($Portiere + $Difesa + $Centrocampo + $Attacco)/100*5,1);
} 
$StampaForza = $Portiere + $Difesa + $Centrocampo + $Attacco - $Malus;

for ($x=0; $x<74; $x++)
{
	if ($x < 65 and $Giocatori >=11)
	{
		$divID[$x] = "NONmettiqui_".$x;
	}
	else
	{
		$divID[$x] = "mettiqui_".$x;
	}
	$classID[$x] = "cl_".$x;
	$inputID[$x] = "bt_".$x;
}
for ($x=1; $x<10; $x++)
{
	$clSpace[$x] = $x+2;
	$spRiga[$x] = "riga".$x;
}
for ($x=65; $x<70; $x++)
{
	$spPanca[$x] = "rigapanca".$x;
}
?>


<form name='field' border='0'>
<div class='contenuto_a_tre'>
	<div class='contenuto_uno'>
		<div id='sposta_totale'>
			<div id='campo_formazione'>
				<img src='images/in_campo.png' id='campo_formazione'>
			</div>
		
			<div id='sposta_righe'>
				<?php 
					
					for ($x=1; $x<10; $x++)
					{
						echo "<span id='$spRiga[$x]'>";
						echo "<table border='0' cellpadding='0' cellspacing='$clSpace[$x]'><tr>";
						for ($y=1+(($x-1)*7); $y<8+(($x-1)*7); $y++) // 7 è il numero di giocatori per riga
						{
							echo "<td><div id='$divID[$y]' class='$classID[$y]'>
									<input name='btn' class='$class[$y]' value=$riquadro[$y] type='button' onclick='javascript:svuota($y);' id='$inputID[$y]' title='$title[$y]'/>
								</div></td>";
						}
						echo "</tr></table></span>";
					}
			
					echo "<span id='riga10' > 
							<table border='0' cellpadding='0' cellspacing='0'>
							<tr>
							 <td><div id='$divID[64]' class='cl_64'><input name='btn' class=\"$class[64]\"' value=$riquadro[64]  type='button' onclick='javascript:svuota(64);' id='bt_64' title='$title[64]'></div></td>
							</tr>
							</table>
						 </span>";
			
					
				?>
			</div>
		</div>
	</div>
	
	<div class='contenuto_due'>
		<div id='panchina_formazione' >
			<img src='images/panchina_di_gioco.png' id='panchina_formazione' > 
		</div>
		<?php 
			echo "<div id='forza_panchina_formazione'>
					<table border='0' cellpadding='0' cellspacing='1'>
						<tr><td>Panchina</td><td>:&nbsp;$Panchina</td></tr>
					</table>
					</div>";
			for ($y=65; $y<70; $y++)
			{
				echo "<span id='$spPanca[$y]'>";
				echo "<table border='0' cellpadding='0' cellspacing='0'><tr>";
				echo "<td>
						<div id='$divID[$y]' class='$classID[$y]'>
							<input name='btn' class='$class[$y]' value=$riquadro[$y] type='button' onclick='javascript:svuota($y);' id='$inputID[$y]' title='$title[$y]'/>
						</div>
						</td>";
					echo "</tr></table></span>";
			}
		?>
	</div>
	<div class='contenuto_tre'>
		<div class='contenuto_tre_sopra'>
			<fieldset style='width: 97%;'>
			<legend class='legenda'>Condizione squadra</legend>
			<table border='0' cellpadding='0' cellspacing='1'>
				<tr><td>Forza</td><td>:&nbsp;<?php echo $Forza ?></td></tr>
				<tr><td>Forma</td><td>:&nbsp;<?php echo $Forma ?>&nbsp;%</td></tr>
				<tr><td>Freschezza</td><td>:&nbsp;<?php echo $Freschezza ?>&nbsp;%</td></tr>
				<tr><td>Condizione</td><td>:&nbsp;<?php echo $Condizione ?>&nbsp;%</td></tr>
				<tr><td colspan="2"><hr /></td></tr>
				<tr><td>Caratteri</td>
				<?php 
				if ($ContaCar >= 5)
				{
					echo"<td>:&nbsp;$ContaCar</td></tr>";
					echo"<tr><td colspan='2' style='color: #333333;'>-> Nessun Malus</td></tr>";
				}
				else
				{
					echo"<td style='color: #FF0000;'>:&nbsp;$ContaCar</td></tr>";
					echo"<tr><td colspan='2' style='color: #FF0000;'>-> Malus</td></tr>";
				}
				?>	
				</tr>
			</table>
			</fieldset>
		</div>
		<div class='contenuto_tre_sotto'>
			<fieldset style='width: 97%;'>
				<legend class='legenda'>Compiti Speciali</legend>
				<?php
				echo "<table border='0' cellpadding='0' cellspacing='3'>";
				echo "<tr>";
					echo "<td><img src='images/t_calcidipunizione.png' width=19px height=19px /></td>";
					echo "<td>&nbsp;</td>";
					echo "<td>Punizioni</td>";
					echo "<td>&nbsp;</td>";
					echo "<td class='compiti'><div id='mettiqui_70' class='cl_70'><input name='btn' class=\"$class[70]\" value='$ws_specialita[0]' type='button' onclick='javascript:svuota(70);' id='bt_70' title='$title[70]'></div></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td><img src='images/t_calciodangolo.png' width=19px height=19px /></td>";
					echo "<td>&nbsp;</td>";
					echo "<td>Angoli</td>";
					echo "<td>&nbsp;</td>";
					echo "<td class='compiti'><div id='mettiqui_71' class='cl_71'><input name='btn' class=\"$class[71]\" value='$ws_specialita[1]' type='button' onclick='javascript:svuota(71);' id='bt_71' title='$title[71]'></div></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td><img src='images/t_rigori.png' width=19px height=19px /></td>";
					echo "<td>&nbsp;</td>";
					echo "<td>Rigori</td>";
					echo "<td>&nbsp;</td>";
					echo "<td class='compiti'><div id='mettiqui_72' class='cl_72'><input name='btn' class=\"$class[72]\" value='$ws_specialita[2]' type='button' onclick='javascript:svuota(72);' id='bt_72' title='$title[72]'></div></td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td><img src='images/t_capitano.png' width=19px height=19px /></td>";
					echo "<td>&nbsp;</td>";
					echo "<td>Capitano</td>";
					echo "<td>&nbsp;</td>";
					echo "<td class='compiti'><div id='mettiqui_73' class='cl_73'><input name='btn' class=\"$class[73]\" value='$ws_specialita[3]' type='button' onclick='javascript:svuota(73);' id='bt_73' title='$title[73]'></div></td>";
				echo "</tr>";
			echo "</table>";
			?>
			</fieldset>
		</div>
	</div>
</div>
<div class='contenuto_sotto'>
	<fieldset style='width: 90%;'>
		<legend class='legenda'>Valori Formazione</legend>
	<?php 
		echo "<div id='portiere_formazione'>
				<table border='0' cellpadding='0' cellspacing='0'>
					<tr><td>Portiere</td></tr>
					<tr><td><center>$Portiere</td></tr>
				</table>
			</div>";
		echo "<div id='difesa_formazione'>
				<table border='0' cellpadding='0' cellspacing='0'>
					<tr><td>Difesa</td></tr>
					<tr><td><center>$Difesa</td></tr>
				</table>
			</div>";
		echo "<div id='centrocampo_formazione'>
				<table border='0' cellpadding='0' cellspacing='0'>
					<tr><td>Centrocampo</td></tr>
					<tr><td><center>$Centrocampo</td></tr>
				</table>
			</div>";
		echo "<div id='attacco_formazione'>
				<table border='0' cellpadding='0' cellspacing='0'>
					<tr><td>Attacco</td></tr>
					<tr><td><center>$Attacco</td></tr>
				</table>
			</div>";
		echo "<div id='totale_formazione'>
				<table border='0' cellpadding='0' cellspacing='0'>
					<tr><td>Totale</td></tr>
					<tr><td><center>$StampaForza</td></tr>
				</table>
			</div>";
	?>
</div>


<div style="display:none ; border: 1px dashed black;"> <!-- turn it into display: none;-->
	<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr align="center" valign="middle">
			<td><input name="group" value="1"  type="radio"></td>
			<td><input name="group" value="2"  type="radio"></td>
			<td><input name="group" value="3"  type="radio"></td>
			<td><input name="group" value="4"  type="radio"></td>
			<td><input name="group" value="5"  type="radio"></td>
			<td><input name="group" value="6"  type="radio"></td>
			<td><input name="group" value="7"  type="radio"></td>
			<td><input name="group" value="8"  type="radio"></td>
			<td><input name="group" value="9"  type="radio"></td>
			<td><input name="group" value="10" type="radio"></td>
			<td><input name="group" value="11"  type="radio"></td>
			<td><input name="group" value="12"  type="radio"></td>
			<td><input name="group" value="13"  type="radio"></td>
			<td><input name="group" value="14"  type="radio"></td>
			<td><input name="group" value="15"  type="radio"></td>
			<td><input name="group" value="16"  type="radio"></td>
			<td><input name="group" value="17"  type="radio"></td>
			<td><input name="group" value="18"  type="radio"></td>
			<td><input name="group" value="19"  type="radio"></td>
			<td><input name="group" value="20"  type="radio"></td>
			<td><input name="group" value="21"  type="radio"></td>
			<td><input name="group" value="22"  type="radio"></td>
			<td><input name="group" value="23"  type="radio"></td>
			<td><input name="group" value="24"  type="radio"></td>
			<td><input name="group" value="25"  type="radio"></td>
			<td><input name="group" value="26"  type="radio"></td>
			<td><input name="group" value="27"  type="radio"></td>
			<td><input name="group" value="28"  type="radio"></td>
			<td><input name="group" value="29"  type="radio"></td>
			<td><input name="group" value="30"  type="radio"></td>
			<td><input name="group" value="31"  type="radio"></td>
			<td><input name="group" value="32"  type="radio"></td>
			<td><input name="group" value="33"  type="radio"></td>
			<td><input name="group" value="34"  type="radio"></td>
			<td><input name="group" value="35"  type="radio"></td>
			<td><input name="group" value="36"  type="radio"></td>
			<td><input name="group" value="37"  type="radio"></td>
			<td><input name="group" value="38"  type="radio"></td>
			<td><input name="group" value="39"  type="radio"></td>
			<td><input name="group" value="40"  type="radio"></td>
			<td><input name="group" value="41"  type="radio"></td>
			<td><input name="group" value="42"  type="radio"></td>
			<td><input name="group" value="43"  type="radio"></td>
			<td><input name="group" value="44"  type="radio"></td>
			<td><input name="group" value="45"  type="radio"></td>
			<td><input name="group" value="46"  type="radio"></td>
			<td><input name="group" value="47"  type="radio"></td>
			<td><input name="group" value="48"  type="radio"></td>
			<td><input name="group" value="49"  type="radio"></td>
			<td><input name="group" value="50"  type="radio"></td>
			<td><input name="group" value="51"  type="radio"></td>
			<td><input name="group" value="52"  type="radio"></td>
			<td><input name="group" value="53"  type="radio"></td>
			<td><input name="group" value="54"  type="radio"></td>
			<td><input name="group" value="55"  type="radio"></td>
			<td><input name="group" value="56"  type="radio"></td>
			<td><input name="group" value="57"  type="radio"></td>
			<td><input name="group" value="58"  type="radio"></td>
			<td><input name="group" value="59"  type="radio"></td>
			<td><input name="group" value="60" type="radio"></td>
			<td><input name="group" value="61"  type="radio"></td>
			<td><input name="group" value="62"  type="radio"></td>
			<td><input name="group" value="63"  type="radio"></td>
			<td><input name="group" value="64"  type="radio"></td>
			<td><input name="group" value="65"  type="radio"></td>
			<td><input name="group" value="66"  type="radio"></td>
			<td><input name="group" value="67"  type="radio"></td>
			<td><input name="group" value="68"  type="radio"></td>
			<td><input name="group" value="69"  type="radio"></td>
			<td><input name="group" value="70"  type="radio"></td>
			<td><input name="group" value="71"  type="radio"></td>
			<td><input name="group" value="72"  type="radio"></td>
			<td><input name="group" value="73"  type="radio"></td>
			<td style='border:1px; border-color:#00FF00;'><input name="group" value="74"  type="radio"></td>
		</tr>
	</table>
</div>
</form>

