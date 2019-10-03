<?php
	require_once('auth.php');
	if (isset($_REQUEST['ajax']))
	{
		include "connect_db.php";
		echo "<style type='text/css'> @import 'demo.css'; </style>";
			//	<style type='text/css'> @import 'menu.css'; </style>";
		echo "<h1>".$_SESSION['ERR_MAGLIA']."</h1>";
	}
	if( isset($_SESSION['ERR_MAGLIA']))
	{
		echo "<script>javascript:alert(\"ERRORE! Numero di maglia ($_SESSION[ERR_MAGLIA]) gia' assegnato!\");</script>";
		unset($_SESSION['ERR_MAGLIA']);
	}
	
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
	
	$moltipl = pow((1.05),-0+29);
	$walstip = number_format(($row['stipendio']*$moltipl),0,",",".");
?>
<div id="h1" style="background-color:#999999;" >
	<div style="float: right;">
		<?php echo "$row[nome]&nbsp;";?>
	</div>
	<div style="float: right;">
		<a href='#' onclick="location.href='m-index.php?fnz=team&pg=31';" class='indietro'></a>
	</div>
</div>


<!-- <div style='float:left;' class='Contenuto_Giocatore'> --> <!-- // inizio del div float left --> 
<div style="background-color:#FF0000;">
<form id='mod_players' name='mod_players' method='post' action='' >
	<div class='div_player'>	
		<span class="top-label">  
			<span class="label-txt">Dati Personali</span>
		</span> 
		<img class='img_player' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_player'>
			<fieldset>
				<table width='100%' border='0'>
					<tr style='font-size: 14px; color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>
						<th style='background-color:#009000;' width='10' align='left'>Maglia</td>	
						<th style='background-color:#009000;' width='10' align='left'>Et&aacute; </td>
						<th style='background-color:#009000;' width='10' align='left'>Skill </td>
						<th style='background-color:#009000;' width='40' align='left'>Carattere </td>
						<th style='background-color:#009000;' width='10' align='left'>Ruolo </td>
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
							<a href='#' onClick="javascript:Verifica_Maglia(w_nr,<?php echo $trova ?>); return false;">
								<img id='acceso' src='images/salva_acceso.png' border='0' width='45' style='display:none;'>
							</a>
						</td>
					 </tr>
				</table>
			</fieldset>
		</div>
	</div>
	<script>
		allarga_maschera('player',40,ValAlt('player'));
	</script>	
	


	<div class='div_player_contratto' style="margin-top:50px;">	
		<span class="top-label">  
			<span class="label-txt">Dati Contratto</span>
		</span> 
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
						<a href='#'/>
							<img id='img_conton' src='images/contratto.png' border='0' width='50' onclick="javascript:Visualizza_Elemento('elementi_nascosti');" style="display:none;"/> 
						</a>
					</td>
					<td align='center'  width='43'>
						<a href='#'/>
							<img id='img_strettaon' src='images/stretta-di-mano.png' border='0' width='45' onclick='javascript:Vendi_Giocatore(w_id,w_valo,w_contr,w_esper,<?php echo $totale ?>);'/>
						</a>
					</td>
				</tr>
				</table>

			</fieldset>
		</div>
	</div>
	
	<script>
		allarga_maschera('player_contratto',40,ValAlt('player')+1);
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
	<span id='elementi_nascosti' style='display: none;'>
		<div class='div_player_change_contratto' style="margin-top:50px;">	
			<span class="top-label">  
				<span class="label-txt">Dati Contratto</span>
			</span> 
			<img class='img_player_change_contratto' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_player_change_contratto'>
				<fieldset>
					<table width='100%' border='0' style='color:#0000FF;'> 
					<tr style='background-color:#009000; color:#FFFF00;'>
						<th>Nuova Durata</th>
						<th>Nuovo Stipendio</th>
					</tr>
					<tr style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>
						<th id='visualizza_cont'><?php echo 5+$row['contratto'] ?></th>
						<th id='visualizza_stip'>€. <?php echo $walstip ?></th>
						<input id='wdata0' name='wdata0' type='hidden' value="<?php echo 5+$row['contratto'] ?>">	
						<input id='wdata1' name='wdata1' type='hidden' value='0'>	
						<input id='wdata2' name='wdata2' type='hidden' value='<?php echo $walstip ?>'>
						<input id='wdata3' name='wdata3' type='hidden' value='<?php echo $wstip ?>'>
						<input id='wdata4' name='wdata4' type='hidden' value='0'>
						<td width='80'>
							<center>
								<a href='#' onclick="javascript:Modifica_Contratto(5);">
									<img src='images/4Plus.gif' border='0' width='30' >
								</a>
							</center>
						</td>
						<td width='80'>
							<center>
								<a href='#' onclick="javascript:Modifica_Contratto(-5);">
									<img src='images/4Minus.gif' border='0' width='30' >
								</a>
							</center>
						</td>
						<td width='80' align="center">
							<img id='puloff' src='images/salva_spento.png' border='0' width='45'/>
							<a href='#' onClick="javascript:Salva_Giocatore(<?php echo $trova ?>); return false;">
								<img id='pulon' src='images/salva_acceso.png' border='0' width='45' style='display:none;'>
							</a>
						</td>
					</tr>
					</table>
				</fieldset>
			</div>
		</div>
	</span>
	
				
	<script>
		allarga_maschera('player_change_contratto',40,ValAlt('player'));
	</script>
</div>  <!-- //FINE DEL DIV FLOAT LEFT -->


<!-- <div style="float:right;">  <!-- INIZIO DEL DIV FLOAT RIGHT -->
<div style="background-color:#FFFF00;">

	<div class='div_player_abilita' >	
		<span class="top-label">  
			<span class="label-txt">Abilit&aacute; Personali</span>
		</span> 
		<img class='img_player_abilita' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_player_abilita'>
			<fieldset>
				<table width='100%' border='0' style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>
				<tr>	<th align='right'>Forma</th>		<td><?php echo $row['forma'] ?></td>	</tr>
				<tr>	<th align='right'>Freschezza</th>	<td><?php echo $row['fresc'] ?></td>	</tr>
				<tr>	<th align='right'>Condizione</th>	<td><?php echo $row['cond'] ?></td>		</tr>
				<tr>	<th align='right'>Esperienza</th>		<td><?php echo $row['esp'] ?></td>		</tr>
				<tr>	<th colspan='2'><hr>			</td>		</tr>
				<tr>	<th align='right'>Parate</th>		<td><?php echo $row['po'] ?></td>		</tr>
				<tr>	<th align='right'>Difesa</th>		<td><?php echo $row['df'] ?></td>		</tr>
				<tr>	<th align='right'>Contrasti</th>	<td><?php echo $row['cn'] ?></td>		</tr>
				<tr>	<th align='right'>Passaggi</th>	<td><?php echo $row['pa'] ?></td>		</tr>
				<tr>	<th align='right'>Regia</th>		<td><?php echo $row['rg'] ?></td>		</tr>	
				<tr>	<th align='right'>Cross</th>		<td><?php echo $row['cr'] ?></td>		</tr>
				<tr>	<th align='right'>Tecnica</th>	<td><?php echo $row['tc'] ?></td>		</tr>
				<tr>	<th align='right'>Tiro</th>		<td><?php echo $row['tr'] ?></td>		</tr>
				<tr>	<th align='right'>Piede</th>		<td><img src=<?php echo $immpiede ?> width='18' heigth='16' border='0'/></td></tr>		
				<tr>	<th align='right'>Talento</th>	<td>
				<?php 
					for ($qw=1; $qw <=$row['qta']; $qw++) 
					{
						echo"<img src=$immtal  border='0'/>";
					}
				?>
				</td>

				</tr>
				
				</table>
			</fieldset>
		</div>
	</div>
	<script>
		allarga_maschera('player_abilita',16,ValAlt('player_abilita'));
	</script>
</div> <!-- //FINE DEL DIV FLOAT RIGHT -->

<span style="position:absolute; margin-left:50px; background-color:#00FF00;">
	<div class='div_player_elementi' >	
		<span class="top-label">  
			<span class="label-txt">Elementi Statistici</span>
		</span> 
		<img class='img_player_elementi' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_player_elementi'>
			<fieldset>
				<table width="100%" border='0' style='color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>
					<tr>
						<th>&nbsp;</th>
						<th style='background-color:#009000; color:#FFFF00; font-family:Geneva,Arial, Helvetica, sans-serif; '>Stagione</th>
					<th style='background-color:#009000; color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>Totale</th>
					</tr>
				
					<tr>
						<th align='right'>Partite</th>
						<td align='center'><?php $row['part'] ?></td>
						<td align='center'><?php $row['part2'] ?></td>
					</tr>
					<tr>
						<th align='right'>Reti</th>
						<td align='center'><?php $row['reti'] ?></td>
						<td align='center'><?php $row['reti2'] ?></td>
					</tr>
					<tr>
						<th align='right'>Ammonizioni</th>
						<td align='center'><?php $row['gialli'] ?></td>
						<td align='center'><?php $row['gialli2'] ?></td>
					</tr>
					<tr>	
						<th align='right'>Espulsioni</th>	
						<td align='center'><?php $row['rossi'] ?></td>
						<td align='center'><?php $row['rossi2'] ?></td>
					</tr>
				</table>
			</fieldset>
		</div>
	</div>
	<script>
		allarga_maschera('player_elementi',26,ValAlt('player_abilita')/2.5);
	</script>
</span>


</body>
</html>