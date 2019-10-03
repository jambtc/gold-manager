<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	$quale_css = "index".$_SESSION['SESS_LARGHEZZA'].".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
?>
</head>
<body >
<?php 
	include "connect_db.php";	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	//FUNZIONE DI STAMPA DATI A VIDEO 
	function my_echo($id, $conta, $quadro, $nome, $maglia, $potenza, $infortunio, $speciale = "")
	{
		($conta % 2 == 0) ? $cla = "BGreen2" : $cla = "LGreen2"; 
		($conta % 2 == 0) ? $stl = "filter:alpha(opacity=90); -moz-opacity:0.9; opacity:0.9;  width:100%; height:auto; height:100%;" : $stl = "filter:alpha(opacity=70); -moz-opacity:0.7; opacity:0.7;  width:100%; height:auto; height:100%;"; 
			
		if ($id == 0)
		{
			echo "<tr class=\"$cla\" style=\"$stl\">";
			echo "<th width='40'><center>$quadro</th>";
		}
		if ($id % 2 == 1 or ($id % 2 == 0 and $id != 0)) 
		{
		 	echo "<tr class=\"$cla\" style=\"$stl\">";
			echo "<td >&nbsp;</td>";
		}

		if ($infortunio == 1)
		{
			echo "<td style='color:#990000;' width='30'><center>$maglia</td>"; 
			echo "<td style='color:#990000;' width='150'>$nome</td>"; 
			echo "<td style='color:#990000;' width='30'>$potenza</td>"; 
			echo "<td width='50'>";
		}
		else
		{
			echo "<td width='30'><center>$maglia</td>"; 
			echo "<td width='150'>$nome</td>"; 
			echo "<td width='30'>$potenza</td>"; 
			echo "<td width='50'>";
		}

		if ($maglia == $speciale[0]) {	
			echo "<img src='images/t_calcidipunizione.png' title='Calci di punizione'/>";
		}
		if ($maglia == $speciale[1]) {	
			echo "<img src='images/t_calciodangolo.png' title='Calcio d&acute;angolo'/>";
		}
		if ($maglia == $speciale[2]) {	
			echo "<img src='images/t_rigori.png' title='Rigori'/>";
		}
		if ($maglia == $speciale[3]) {	
			echo "<img src='images/t_capitano.png' title='Capitano'/>";
		}
		
		echo "</td>"; 
		echo "</tr>";		
	}
	//->FINE FUNZIONE STAMPA DATI A VIDEO

	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
	
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0) {
			while ($rig = mysql_fetch_array($result)){
				$nome[] = $rig['nome'];
				$nr[] = $rig['nr'];
				$wskill[] = $rig['skill'];
				$wpo[] = $rig['po'];
				$wdf[] = $rig['df'];
				$wcn[] = $rig['cn'];
				$wpa[] = $rig['pa'];
				$wrg[] = $rig['rg'];
				$wcr[] = $rig['cr'];
				$wtc[] = $rig['tc'];
				$wtr[] = $rig['tr'];
				$wes[] = $rig['esp'];
				$wpd[] = strtoupper($rig['piede']);
				$wruolo[] = $rig['pos'];
				$wcarattere[] = $rig['carattere'];
				$wtalento[] = $rig['talento'];
				$wqta[] = $rig['qta'];
				$winfortunio[] = $rig['infortunio'];
			}
			// TOTALE GIOCATORI
			$totgio = mysql_num_rows($result);
			$formula = "Formula 2";
			$tattica = "4-4-2";
			$qu0_val[0] = 0;
			$qu1_val[0] = 0;
			$qu2_val[0] = 0;
			$qu3_val[0] = 0;
			$qu4_val[0] = 0;
			$qu5_val[0] = 0;
			$qu6_val[0] = 0;
			$qu7_val[0] = 0;
			$qu8_val[0] = 0;
			$qu9_val[0] = 0;			
			$qu0_nom[0] = "";
			$qu1_nom[0] = "";
			$qu2_nom[0] = "";
			$qu3_nom[0] = "";
			$qu4_nom[0] = "";
			$qu5_nom[0] = "";
			$qu6_nom[0] = "";
			$qu7_nom[0] = "";
			$qu8_nom[0] = "";
			$qu9_nom[0] = "";

			//creao array contenente numeri da 1 a 64
			for ($xx = 1; $xx < 65; $xx++)
			{
				$box_numeri[] = $xx;
			}
	
			// LISTA ORDINATA PER I MIGLIORI valori
			$conta = 0;
			$max_pun = 0;
			$max_rig = 0;
			$max_ang = 0;
			$max_cap = 0;
			while ($conta < $totgio) 
			{
					$controllo = 15; // valore dell'esperienza
					
					// CARICO I DATI DAL CALCOLATORE
					$calc_result = mysql_query("SELECT * FROM calcolatore WHERE formula=\"$formula\" ORDER BY ord");
					if (!$calc_result) { echo 'Errore nella query calcolatore: ' . mysql_error(); exit(); }
					
					$max = 0;
					unset($box);
					unset($val);

					$ii = 1; 
					while   ($riga   =   mysql_fetch_array($calc_result)) 
					{
						// CONTROLLA TUTTI I CASI DELLE CASELLE
						switch ($ii) { 
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
								if ($wpd[$conta] == "R")  {$pdd = -6;}
								elseif ($wpd[$conta] == "L") {$pdd = 6;}
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
								if ($wpd[$conta] == "R")  {$pdd = 4;}
								elseif ($wpd[$conta] == "L") {$pdd = 4;}
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
								if ($wpd[$conta] == "R")  {$pdd = 6;}
								elseif ($wpd[$conta] == "L") {$pdd = -6;}
								else{$pdd = 4;}
								break;
								
							//q0	
							case 64:
								$pdd = 4;
								break;
						} // end switch
						
				$riquadro[$ii] = $pdd + $wpo[$conta]*$riga['po'] + $wdf[$conta]*$riga['df'] + $wcn[$conta]*$riga['cn'];
				$riquadro[$ii] = $riquadro[$ii] + $wpa[$conta]*$riga['pa'] + $wrg[$conta]*$riga['rg'] + $wcr[$conta]*$riga['cr'];
				$riquadro[$ii] = $riquadro[$ii] + $wtc[$conta]*$riga['tc'] + $wtr[$conta]*$riga['tr'];
						$riquadro[$ii] = $riquadro[$ii] / (101-$controllo) ;
						$riquadro[$ii] = round($riquadro[$ii],1);		
						
						$ii ++ ;
					} // end while controllo sul calcolatore
					
					$wgiocatore = array($riquadro,$box_numeri);
					array_multisort($wgiocatore[0], SORT_DESC, $wgiocatore[1], SORT_DESC);
					
					for ($ix = 0; $ix <=10; $ix ++)
					{
						//echo $nome[$conta]." ".$wgiocatore[1][$ix]." ".$wgiocatore[0][$ix];
						// CONTROLLA TUTTI I CASI DELLE CASELLE
							switch ($wgiocatore[1][$ix])
							{
								case 1:
								case 2:
								case 8:
								case 9:
								case 15:
								case 16:
									//attacco sinistro Q7
									if (!in_array($nome[$conta],$qu7_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu7_val[] = $wgiocatore[0][$ix];
											$qu7_nom[] = $nome[$conta];
										}
									}
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
									// attacco centrale Q8
									if (!in_array($nome[$conta],$qu8_nom))
									{
										$qu8_val[] = $wgiocatore[0][$ix];
										$qu8_nom[] = $nome[$conta]; 
									}
									break;
								case 6:
								case 7:
								case 13:
								case 14:
								case 20:
								case 21:
									// attacco destro Q9
									if (!in_array($nome[$conta],$qu9_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
										{	
											$qu9_val[] = $wgiocatore[0][$ix];
											$qu9_nom[] = $nome[$conta]; 
										}
									}
									break;
								case 22:
								case 23:
								case 29:
								case 30:
								case 36:
								case 37:
									//centrocampo sinistra Q4
									if (!in_array($nome[$conta],$qu4_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu4_val[] = $wgiocatore[0][$ix];
											$qu4_nom[] = $nome[$conta]; 
										}
									}
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
									if (!in_array($nome[$conta],$qu5_nom))
									{
										$qu5_val[] = $wgiocatore[0][$ix];
										$qu5_nom[] = $nome[$conta]; //centrocampo centrale Q5
									}
									break;
								case 27:
								case 28:
								case 34:
								case 35:
								case 41:
								case 42:
									if (!in_array($nome[$conta],$qu6_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu6_val[] = $wgiocatore[0][$ix];
											$qu6_nom[] = $nome[$conta]; //centrocampo destra Q6
										}
									}
									break;
								case 43:
								case 44:
								case 50:
								case 51:
								case 57:
								case 58:
									if (!in_array($nome[$conta],$qu1_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu1_val[] = $wgiocatore[0][$ix];
											$qu1_nom[] = $nome[$conta]; //difesa sinistra Q1
										}
									}
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
									if (!in_array($nome[$conta],$qu2_nom))
									{
										$qu2_val[] = $wgiocatore[0][$ix];
										$qu2_nom[] = $nome[$conta]; //difesa centrale Q2
									}
									break;
								case 48:
								case 49:
								case 55:
								case 56:
								case 62:
								case 63:
									if (!in_array($nome[$conta],$qu3_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu3_val[] = $wgiocatore[0][$ix];
											$qu3_nom[] = $nome[$conta]; //difesa destra Q3
										}
									}
									break;
								case 64:
									if (!in_array($nome[$conta],$qu0_nom))
									{
										$qu0_val[] = $wgiocatore[0][$ix];
										$qu0_nom[] = $nome[$conta]; // portiere Q0
									}
									break;
							} // end switch
						
					}//end for
					
					// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
					// !! INIZIO COMPITI SPECIALI !!
					// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

					// calcia punizioni
					if ($wtalento[$conta] == "Calci di punizione")
					{
						$conteggio_punizioni = ($wtc[$conta]*1.3+$wtr[$conta])* (2*$wqta[$conta]);
					}
					else
					{
						$conteggio_punizioni = ($wtc[$conta]*1.3+$wtr[$conta]);
					}
					if ($max_pun < $conteggio_punizioni)
					{
						$max_pun = $conteggio_punizioni;
						$gio_pun = $nr[$conta];
					}
					// calcia rigori
					if ($wcarattere[$conta] == "razionale")
					{	
						$conteggio_rigori = ($wskill[$conta]+$wtc[$conta]*1.3+$wtr[$conta])+20;		
					}
					elseif ($wcarattere[$conta] == "emotivo")
					{	
						$conteggio_rigori = ($wskill[$conta]+$wtc[$conta]+$wtr[$conta])-20;		
					}
					else 
					{	
						$conteggio_rigori = ($wskill[$conta]+$wtc[$conta]*1.2+$wtr[$conta]);			
					}
					if ($wtalento[$conta] == "Rigori")
					{
						$conteggio_rigori = $conteggio_rigori * (2*$wqta[$conta]);		
					}
					if ($max_rig < $conteggio_rigori)
					{
						$max_rig = $conteggio_rigori;
						$gio_rig = $nr[$conta];
					}
					// calcia angoli
					if ($wtalento[$conta] == "Calcio d´angolo")
					{	
						$conteggio_angoli = ($wtc[$conta]*1.3+$wcr[$conta])* (2*$wqta[$conta]);
					}
					else
					{
						$conteggio_angoli = ($wtc[$conta]*1.3+$wcr[$conta]);
					}
					if ($max_ang < $conteggio_angoli)
					{
						$max_ang = $conteggio_angoli;
						$gio_ang = $nr[$conta];
					}
					// capitano
					switch ($wcarattere[$conta])
					{
							case "carismatico":
								$conteggio_capitano = ($wskill[$conta])*25;
								break;
							case "popolare":
								$conteggio_capitano = ($wskill[$conta])*10;
								break;
							case "introverso":
								$conteggio_capitano = ($wskill[$conta]);
								break;
							default:
								$conteggio_capitano = ($wskill[$conta])*5;
					}
					$conteggio_capitano = $conteggio_capitano + round(($wes[$conta]/100*15),1);	
					if ($max_cap < $conteggio_capitano)
					{
						$max_cap = $conteggio_capitano;
						$gio_cap = $nr[$conta];
					}
					// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
					// !! FINE COMPITI SPECIALI !!
					// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
					
					$conta ++;
			} // end while

			// creo array contenente i nomi e i numeri di maglietta!! FONDAMENTALE!!
			$numappo = array_combine($nome , $nr);
			//
			$speciale = array($gio_pun,$gio_ang,$gio_rig,$gio_cap);
			//
			$ar_infortunio = array_combine($nr, $winfortunio);
			//
			echo "<table  width='100%' border='0'>";
			echo "<tr>";
			echo "<th  width='8' scope='col'><span class='Stile8'>Quadr.</span></th>";
			echo "    <th width='20' scope='col'><span class='Stile8'>Nr.</span></th>";
			echo "    <th width='80' scope='col' align='left'><span class='Stile8'>Nome</span></th>";
			echo "    <th width='30' scope='col' align='left'><span class='Stile8'>Forza</span></th>";
			echo "    <th width='67' scope='col' align='left'><span class='Stile8'>Compiti</span></th>";
			echo "</tr>";
									
			$counter = 0;
			// Q U A D R A N T E --> 0
			$elenco = count($qu0_val);
			if ($elenco > 1)
			{
				$wportiere = array($qu0_val,$qu0_nom);
				array_multisort($wportiere[0], SORT_DESC, $wportiere[1], SORT_DESC);
				
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $wportiere[1][$ii];
					$p_pote = $wportiere[0][$ii];
										
					if (isset($numappo[$wportiere[1][$ii]]))
					{
						$p_maglia = $numappo[$wportiere[1][$ii]];			
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q0", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 1
			$elenco = count($qu1_val);
			if ($elenco > 1)
			{		
				++$counter;	
				$wdif_sin = array($qu1_val,$qu1_nom);
				array_multisort($wdif_sin[0], SORT_DESC, $wdif_sin[1], SORT_DESC);
															
				for ($ii = 0; $ii < $elenco; $ii++)
				{						
					$p_nome = $wdif_sin[1][$ii];
					$p_pote = $wdif_sin[0][$ii];
					
					if (isset($numappo[$wdif_sin[1][$ii]]))
					{	
						$p_maglia = $numappo[$wdif_sin[1][$ii]];	
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q1", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 2
			$elenco = count($qu2_val);
			if ($elenco > 1)
			{
				++$counter;
				$wdif_cen = array($qu2_val,$qu2_nom);
				array_multisort($wdif_cen[0], SORT_DESC, $wdif_cen[1], SORT_DESC);
							
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $wdif_cen[1][$ii];
					$p_pote = $wdif_cen[0][$ii];
					
					if (isset($numappo[$wdif_cen[1][$ii]]))
					{
						$p_maglia = $numappo[$wdif_cen[1][$ii]];	
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q2", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 3
			$elenco = count($qu3_val);
			if ($elenco > 1)
			{
				++$counter;
				$wdif_des = array($qu3_val,$qu3_nom);
				array_multisort($wdif_des[0], SORT_DESC, $wdif_des[1], SORT_DESC);
											
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $wdif_des[1][$ii];
					$p_pote = $wdif_des[0][$ii];
					
					if (isset($numappo[$wdif_des[1][$ii]]))
					{
						$p_maglia = $numappo[$wdif_des[1][$ii]];					
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q3", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 4
			$elenco = count($qu4_val);
			if ($elenco > 1)
			{
				++$counter;
				$wcen_sin = array($qu4_val,$qu4_nom);
				array_multisort($wcen_sin[0], SORT_DESC, $wcen_sin[1], SORT_DESC);
												
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $wcen_sin[1][$ii];
					$p_pote = $wcen_sin[0][$ii];
					
					if (isset($numappo[$wcen_sin[1][$ii]]))
					{
						$p_maglia = $numappo[$wcen_sin[1][$ii]];	
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q4", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 5
			$elenco = count($qu5_val);
			if ($elenco > 1)
			{	
				++$counter;		
				$wcen_cen = array($qu5_val,$qu5_nom);
				array_multisort($wcen_cen[0], SORT_DESC, $wcen_cen[1], SORT_DESC);
												
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $wcen_cen[1][$ii];
					$p_pote = $wcen_cen[0][$ii];
					
					if (isset($numappo[$wcen_cen[1][$ii]]))
					{
						$p_maglia = $numappo[$wcen_cen[1][$ii]];					
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q5", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 6
			$elenco = count($qu6_val);
			if ($elenco > 1)
			{	
				++$counter;				
				$wcen_des = array($qu6_val,$qu6_nom);
				array_multisort($wcen_des[0], SORT_DESC, $wcen_des[1], SORT_DESC);
												
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $wcen_des[1][$ii];
					$p_pote = $wcen_des[0][$ii];
					
					if (isset($numappo[$wcen_des[1][$ii]]))
					{
						$p_maglia = $numappo[$wcen_des[1][$ii]];					
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q6", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 7
			$elenco = count($qu7_val);
			if ($elenco > 1)
			{	
				++$counter;				
				$watt_sin = array($qu7_val,$qu7_nom);
				array_multisort($watt_sin[0], SORT_DESC, $watt_sin[1], SORT_DESC);
												
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $watt_sin[1][$ii];
					$p_pote = $watt_sin[0][$ii];
					
					if (isset($numappo[$watt_sin[1][$ii]]))
					{
						$p_maglia = $numappo[$watt_sin[1][$ii]];					
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q7", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 8
			$elenco = count($qu8_val);
			if ($elenco > 1)
			{			
				++$counter;			
				$watt_cen = array($qu8_val,$qu8_nom);
				array_multisort($watt_cen[0], SORT_DESC, $watt_cen[1], SORT_DESC);
				
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $watt_cen[1][$ii];
					$p_pote = $watt_cen[0][$ii];
					
					if (isset($numappo[$watt_cen[1][$ii]]))
					{
						$p_maglia = $numappo[$watt_cen[1][$ii]];	
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q8", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			// Q U A D R A N T E --> 9
			$elenco = count($qu9_val);
			if ($elenco > 1)
			{	
				++$counter;			
				$watt_des = array($qu9_val,$qu9_nom);
				array_multisort($watt_des[0], SORT_DESC, $watt_des[1], SORT_DESC);
				
				for ($ii = 0; $ii < $elenco; $ii++)
				{
					$p_nome = $watt_des[1][$ii];
					$p_pote = $watt_des[0][$ii];
					
					if (isset($numappo[$watt_des[1][$ii]]))
					{
						$p_maglia = $numappo[$watt_des[1][$ii]];
						$infortunio = $ar_infortunio[$p_maglia];
						my_echo($ii, $counter, "Q9", $p_nome, $p_maglia, $p_pote, $infortunio, $speciale);
					}
				}
			}
			echo "
			<tr height='50'><td>&nbsp;</td></tr>
			</table>";

		}else{
			//echo "<h1>Calcolo automatico formazione</h1>";
			echo "<form name='paginavuota'>";
			echo "<h6><br>Non esistono dati su cui effettuare calcoli.<br></h6>";
			echo "</form>";
		}

mysql_close($link);
?>





</body>
</html>
