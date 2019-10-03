<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<script type="text/javascript" src="jquery/my_screen.js"></script>
<script type="text/javascript" src="jquery/my_script.js"></script>
<script type="text/javascript" src="jquery/my_script_team.js"></script>
<script type="text/javascript" src="jquery/jquery.alerts.js"></script>
<style type='text/css'> @import 'jquery/jquery.alerts.css'; </style>

<style>
body,h1,h2,h3,p,td,quote,small,form,input,ul,li,ol,label{
	margin:0px;
	padding:0px;
	font-family:Arial, Helvetica, sans-serif;
}
.h1 {
	color:  #0000FF;
	margin: 0px 0px 5px;
	padding: 0px 0px 3px;
	font: bold 22px Verdana, Arial, Helvetica, sans-serif;
	border-bottom: 1px dashed #0000FF;
	text-align:left;
	vertical-align:middle;
}

div#h1{
	float: left;
	color: #0000FF;	
	margin: 0px 0px 5px;
	padding: 0px 0px 3px;
	font: bold 22px Verdana, Arial, Helvetica, sans-serif;
	border-bottom: 1px dashed #0000FF;
	text-align:left;
	vertical-align:middle; 
	width:100%;
}
.top-label{
	background:url(images/quadrato_rounded_chiaro.png) no-repeat;
	float:left;
	display:inline-block;
	margin-left:20px;
	position:relative;
	margin-bottom:-15px;
}

.label-txt{
	color: #0000ff;
	background:url(images/quadrato_rounded_chiaro.png) no-repeat top right;
	display:inline-block;
	font-size:12px;
	height:32px;
	margin-left:10px;
	padding:12px 15px 0px 5px;
	font-weight:bold;
}
.fld_breve{	width:30px;}


</style>



</head>
<body style="background:trasparent;"> 

<?php
	require_once('auth.php');
	include "connect_db.php";
	
 	$nome_team = $_SESSION['SESS_TEAM'];
	$trova = $_REQUEST['id'];
	
	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	$totale = mysql_num_rows($result);
	
	
	$quale_piede['L'] = "Sinistro";
	$quale_piede['R'] = "Destro";
	$quale_piede['LR'] = "Ambidestro";
	
	$im_tal = array("t_nessuno.png","t_colpoditesta.png","t_rigori.png","t_calciodangolo.png","t_calcidipunizione.png","t_dribbling.png","t_velocita.png","t_resistenza.png","t_potenzadeltiro.png","t_cross.png","t_creativita.png","t_fiutodelgoal.png","t_pararigori.png","t_riflessifelini.png","t_visionedigioco.png");
	
	$im_piede = array("p_destro.png","p_sinistro.png","p_ambidestro.png");
		
	$quale_ruolo['PO'] = "(PO) Portiere";
	$quale_ruolo['DS'] = "(DS) Terzino Sinistro";
	$quale_ruolo['D']  = "(D) Difensore Centrale";
	$quale_ruolo['DD'] = "(DD) Terzino Destro";
	$quale_ruolo['CS'] = "(CS) Ala Sinistra";
	$quale_ruolo['C']  = "(C) Centrocampista";
	$quale_ruolo['CD'] = "(CD) Ala Destra";
	$quale_ruolo['AS'] = "(AS) Attaccante Sinistro";
	$quale_ruolo['A']  = "(A) Attaccante Centrale";
	$quale_ruolo['AD'] = "(AD) Attaccante Destro"; 
	$quale_ruolo['XX'] = "(XX) Jolly"; 

	//CARICO TABELLA RUOLI
	$ruoli_result = mysql_query("SELECT * FROM ruoli ORDER BY ruolo_order ASC");
	if (!$ruoli_result) {
		echo 'Errore nella query ruoli: ' . mysql_error();
		exit();
	}
	$conta = 0;
	while   ($row   =   mysql_fetch_array($ruoli_result)) {
		$ruolo[$conta] = $row['ruolo_desc'];
		$conta++;
	}
	// CREO ELENCO DEI CARATTERI DEL GIOCATORE
	$caratteri_result = mysql_query("SELECT * FROM caratteri WHERE id_carattere='giocatore' ORDER BY descrizione");
	if (!$caratteri_result) {
		echo 'Errore nella query caratteri: ' . mysql_error();
		exit();
	}
	$conta = 0;
	while   ($row   =   mysql_fetch_array($caratteri_result)) {
		$caratteri[$conta] = $row['descrizione'];
		$conta++;
	}
	//CARICO TABELLA TALENTI
	$talenti_result = mysql_query("SELECT * FROM talenti WHERE 1");
	if (!$talenti_result) {
		echo 'Errore nella query talenti: ' . mysql_error();
		exit();
	}
	$conta = 0;
	while   ($row   =   mysql_fetch_array($talenti_result)) {
		$talento[$conta] = $row['tal_descrizione'];
		$conta++;
	}
	
	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND id=\"$trova\"");
	if (!$result) {
		echo 'Errore nella query giocatori: ' . mysql_error();
		exit();
	}
	$row   =   mysql_fetch_array($result);
	
	$mostra_ruolo = $quale_ruolo[$row['pos']];
	
	switch ($row['talento'])
	{
		case "Nessuno":
			$immtal = $im_tal[0];
			break;
		case "Colpo di testa":
			$immtal = $im_tal[1];
			break;
		case "Rigori":
			$immtal = $im_tal[2];
			break;
		case "Calcio d&acute;angolo":
			$immtal = $im_tal[3];
			break;
		case "Calci di punizione":
			$immtal = $im_tal[4];
			break;
		case "Dribbling":
			$immtal = $im_tal[5];
			break;
		case "Velocità":
			$immtal = $im_tal[6];
			break;
		case "Resistenza":
			$immtal = $im_tal[7];
			break;
		case "Potenza del tiro":
			$immtal = $im_tal[8];
			break;
		case "Cross":
			$immtal = $im_tal[9];
			break;
		case "Creatività":
			$immtal = $im_tal[10];
			break;
		case "Fiuto del goal":
			$immtal = $im_tal[11];
			break;
		case "Pararigori":
			$immtal = $im_tal[12];
			break;
		case "Riflessi felini":
			$immtal = $im_tal[13];
			break;
		case "Visione di gioco":
			$immtal = $im_tal[14];
			break;
		default:
			$immtal = $im_tal[0];
	}
	$immtal = "images/".$immtal;
		
	switch ($row['piede'])
	{
		case "R":
			$immpiede = $im_piede[0];
			break;
		case "L":
			$immpiede = $im_piede[1];
			break;
		case "LR":
			$immpiede = $im_piede[2];
			break;
		default:
			$immpiede = $im_piede[0];
	}
	$immpiede = "images/".$immpiede;		

	$wstip = number_format($row['stipendio'],0,",",".");
	$wvalo = number_format($row['valore'],0,",",".");
	
	$moltipl = pow((1.0475),-0+29);
	$walstip = number_format(($row['stipendio']*$moltipl),0,",",".");
?>
<div id="h1" >
	<div style="float:left; width:4%;" >
		<a href='#' onclick="location.href='team_list.php';" target="_self" title='Indietro'>
			<img id='arret' src="images/indietro.png" border="0" width="100%" onmouseover="arret.src='images/indietro_big.png';" onmouseout="arret.src='images/indietro.png';"/>
		</a>
	</div>
	<div style="float:left; ">
		<?php echo "$row[nome]&nbsp;";?>
	</div>
</div>


<div style='float:left; width:55%;' class='Contenuto_Giocatore'> <!-- // inizio del div float left --> 
<form id='mod_players' name='mod_players' method='post' action='' >
	<div class='div_player'>	
		<span class="top-label">  
			<span class="label-txt">Dati Personali</span>
		</span> 
		<img class='img_player' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_player' id='cnt_player'>
		<div style="display:none;" id='cnt_player_ifthen' class="cnt_player_ifthen">VERO</div>
			<fieldset>
				<table width='100%' border='0'>
					<tr style='font-size: 14px; color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>
						<th style='background-color:#009000;' width='10' align='left'>Maglia</th>	
						<th style='background-color:#009000;' width='10' align='left'>Et&aacute; </th>
						<th style='background-color:#009000;' width='10' align='left'>Skill </th>
						<th style='background-color:#009000;' width='40' align='left'>Carattere </th>
						<th style='background-color:#009000;' width='10' align='left'>Ruolo </th>
				  </tr>
					<tr style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; font-weight:bold; '>
						<td><b>
							<input type='text' id='w_nr' name='w_nr' value="<?php echo $row['nr'] ?>" class='fld_breve' onfocus='javascript:Attiva_Pulsante();'/>
						</b></td>
						<td><?php echo $row['eta'] ?></td>
						<td><?php echo $row['skill'] ?></td>
						<td><?php echo $row['carattere'] ?></td>
						<td>
							<select id='w_ruolo' name='w_ruolo'  class='fld_lungo' onchange='javascript:Attiva_Pulsante();'>
								<option value='<?php echo $row['pos'] ?>' selected='selected'><?php echo $mostra_ruolo ?></option>
								<option value='<?php echo $row['pos'] ?>'>----</option>
							   	<?php 
								   	foreach ($ruolo as $iter_ruolo)
								   	{
										$video_ruolo = $quale_ruolo[$iter_ruolo];
										echo "<option value='$iter_ruolo'>$video_ruolo</option>";
									}
								?>
							</select>
						</td>
						<td align='center' width='43'>
							<img id='spento' src='images/salva_spento.png' border='0' width='45'/>
							<a href='#' onClick="javascript:Verifica_Maglia(<?php echo $trova ?>); return false;">
								<img id='acceso' src='images/salva_acceso.png' border='0' width='45' title="Conferma i DATI PERSONALI" style='display:none;'  onmouseover="mod_players.acceso.src='images/salva_acceso_big.png';" onmouseout="mod_players.acceso.src='images/salva_acceso.png';">
							</a>
						</td>
				  </tr>
				</table>
			</fieldset>
		</div>
	</div>
	
	<div class='div_player_contratto' style="margin-top:50px;">	
		<span class="top-label">  
			<span class="label-txt">Dati Contratto</span>		</span> 
		<img class='img_player_contratto' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_player_contratto'>
			<fieldset>
				<table width='100%' border='0'>
				<tr style='font-size: 14px; color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>
      				<th style='background-color:#009000;' width='100'>Durata</th>	
					<th style='background-color:#009000;' width='100'>Stipendio</th>	
					<th style='background-color:#009000;' width='140'>Valore</th>
					
					<input type='hidden' id='w_valo' value='<?php echo $row['valore'] ?>' />
					<input type='hidden' id='w_id' value='<?php echo $row['id'] ?>' />
					<input type='hidden' id='w_contr' value='<?php echo $row['contratto'] ?>' />
					<input type='hidden' id='w_esper' value='<?php echo $row['esp'] ?>' />
					
				</tr>

				<tr style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>
					<th><?php echo $row['contratto'] ?></th>	
					<th>&euro;. <?php echo $wstip ?></th>	
					<th>&euro;. <?php echo $wvalo ?></th>
					<td align='center'  width='43'>
						<img id='img_contoff' src='images/contrattobn.png' border='0' width='50' />
						<a href='#' onclick="Visualizza_Elemento('elementi_nascosti');">
							<img id="img_conton" src="images/contratto.png" border="0" width="50"  onmouseover="mod_players.img_conton.src='images/contratto_big.png';" onmouseout="mod_players.img_conton.src='images/contratto.png';" /> 
							
						</a>
					</td>
					<td align='center'  width='43'>
						<a href='#' onclick='javascript:Vendi_Giocatore(mod_players.w_id,
																		mod_players.w_valo,
																		mod_players.w_contr,
																		mod_players.w_esper,
																		<?php echo $totale ?>);'/>
							<img id='img_strettaon' src='images/strettadimano.png' border='0' width='60'  onmouseover="mod_players.img_strettaon.src='images/strettadimano_big.png';" onmouseout="mod_players.img_strettaon.src='images/strettadimano.png';"/>
						</a>
					</td>
				</tr>
				</table>

			</fieldset>
		</div>
	</div>
	
	<span id='elementi_nascosti' style='display: none;'>
		<div class='div_player_change_contratto' style="margin-top:50px;">	
			<span class="top-label">  
				<span class="label-txt">Modifica dati contrattuali</span>			</span> 
			<img class='img_player_change_contratto' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_player_change_contratto'>
				<fieldset>
					<table width='100%' border='0' style='color:#0000FF;'> 
					<tr style='background-color:#009000; color:#FFFF00;'>
						<th>Nuova Durata</th>
						<th>Nuovo Stipendio</th>
					</tr>
					<?php $aumento_valore = 6; ?>
					<tr style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>
						<th id='visualizza_cont'><?php echo $aumento_valore+$row['contratto'] ?></th>
						<th id='visualizza_stip'>€. <?php echo $walstip ?></th>
						<input id='wdata0' name='wdata0' type='hidden' value="<?php echo $aumento_valore+$row['contratto'] ?>">	
						<input id='wdata1' name='wdata1' type='hidden' value='0'>	
						<input id='wdata2' name='wdata2' type='hidden' value='<?php echo $walstip ?>'>
						<input id='wdata3' name='wdata3' type='hidden' value='<?php echo $wstip ?>'>
						<input id='wdata4' name='wdata4' type='hidden' value='0'>
						<td width='80'>
							<center>
								<a href='#' onclick="javascript:Modifica_Contratto(<?php echo $aumento_valore ?>);">
									<img id='plus' src='images/plus.png' border='0' width='35' onmouseover="mod_players.plus.src='images/plus_big.png';" onmouseout="mod_players.plus.src='images/plus.png';">
								</a>
							</center>
						</td>
						<td width='80'>
							<center>
								<a href='#' onclick="javascript:Modifica_Contratto(<?php echo -$aumento_valore ?>);">
									<img id='minus' src='images/minus.png' border='0' width='35'  onmouseover="mod_players.minus.src='images/minus_big.png';" onmouseout="mod_players.minus.src='images/minus.png';">
								</a>
							</center>
						</td>
						<td width='80' align="center">
							<img id='puloff' src='images/salva_spento.png' border='0' width='45'/>
							<a href='#' onClick="javascript:Salva_Giocatore(<?php echo $trova ?>); return false;">
								<img id='pulon' src='images/salva_acceso.png' border='0' width='45' style='display:none;' title="Conferma i NUOVI DATI DEL CONTRATTO" style='display:none;'  onmouseover="mod_players.pulon.src='images/salva_acceso_big.png';" onmouseout="mod_players.pulon.src='images/salva_acceso.png';">
							</a>
						</td>
					</tr>
					</table>
				</fieldset>
			</div>
	</div>
	</span>
</form>
</div>
<div style='float: right; width:45%;' class='Contenuto_Giocatore_right'> <!-- // inizio del div float right --> 
	<div class='div_player_statistici' style="float:left;">	
		<span class="top-label">  
			<span class="label-txt">Dati Statistici</span>		</span> 
		<img class='img_player_statistici' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_player_statistici'>
			<fieldset>
				<table border='0' width="80%">
					<tr>
						<th>&nbsp;</th>
						<th>Stagione</th>
						<th>Totale</th>
					</tr>
					<tr>
						<th align='right'>Partite</th>
						<td align='center'><?php echo $row['part'] ?></td>
						<td align='center'><?php echo $row['part2'] ?></td>
					</tr>
					<tr>
						<th align='right'>Reti</th>
						<td align='center'><?php echo $row['reti'] ?></td>	
						<td align='center'><?php echo $row['reti2'] ?></td>
					</tr>
					<tr>
						<th align='right'>Ammonizioni</th>
						<td align='center'><?php echo $row['gialli'] ?></td>
						<td align='center'><?php echo $row['gialli2'] ?></td>
					</tr>
					<tr>
						<th align='right'>Espulsioni</th>	
						<td align='center'><?php echo $row['rossi'] ?></td>	
						<td align='center'><?php echo $row['rossi2'] ?></td>	
					</tr>
				</table>
			</fieldset>
		</div>
  </div>
	<div class='div_player_personali' style="float:right; margin-left:10px; margin-right:5px; ">	
		<span class="top-label">  
			<span class="label-txt">Abilità Personali</span>
		</span> 
		<img class='img_player_personali' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_player_personali'>
			<fieldset>
				<table border='0' align="center" > 
					<tr>	<th align='right'>Forma		</th>	<td><?php echo $row['forma']	?></td>	</tr>
					<tr>	<th align='right'>Freschezza</th>	<td><?php echo $row['fresc']	?></td>	</tr>
					<tr>	<th align='right'>Condizione</th>	<td><?php echo $row['cond']	?></td>	</tr>
					<tr>	<th align='right'>Esperienza</th>	<td><?php echo $row['esp']	?></td>	</tr>
					<tr>	<th colspan='2'><hr>		</th>	</tr>
					<tr>	<th align='right'>Parate	</th>	<td><?php echo $row['po']	?></td>	</tr>
					<tr>	<th align='right'>Difesa	</th>	<td><?php echo $row['df'] ?></td>	</tr>
					<tr>	<th align='right'>Contrasti	</th>	<td><?php echo $row['cn']	?></td>	</tr>
					<tr>	<th align='right'>Passaggi	</th>	<td><?php echo $row['pa']	?></td>	</tr>
					<tr>	<th align='right'>Regia		</th>	<td><?php echo $row['rg']	?></td>	</tr>	
					<tr>	<th align='right'>Cross		</th>	<td><?php echo $row['cr']	?></td>	</tr>
					<tr>	<th align='right'>Tecnica	</th>	<td><?php echo $row['tc'] ?></td>	</tr>
					<tr>	<th align='right'>Tiro		</th>	<td><?php echo $row['tr']	?></td>	</tr>
					<tr>	
						<th align='right'>Piede		</th>
						<td>
							<?php echo "<img src=$immpiede width='18' heigth='16' border='0'/>"; ?>
						</td>
					</tr>		
					<tr>
						<th align='right'>Talento</th>
						<td>
						<?php 
						for ($qw=1; $qw <=$row['qta']; $qw++) 
						{
							echo "<img src=$immtal  border='0'/>";
						}
						?>
						</td>
					</tr>
				</table>
			</fieldset>
		</div>
  </div>
</div>
<script>
		allarga_maschera('player',35,ValAlt('player'));
		allarga_maschera('player_contratto',35,ValAlt('player')+1);
		allarga_maschera('player_change_contratto',35,ValAlt('player')+1);
		allarga_maschera('player_statistici',16,ValAlt('player')+2);
		allarga_maschera('player_personali',13,ValAlt('player')+20);
</script>	

<?php 
	if ($row['contratto'] <= 5)
	{
		echo "<script>Nascondi_Elemento('img_contoff');</script>";
		echo "<script>Visualizza_Elemento('img_conton');</script>";
	}
	else
	{
		echo "<script>Nascondi_Elemento('img_conton');</script>";
		echo "<script>Visualizza_Elemento('img_contoff');</script>";
	}
	
?>
</body>
