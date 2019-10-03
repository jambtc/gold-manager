<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>

<script type="text/javascript">
function Vendita(arg1,arg2,arg3,arg4)
{
  	valore = arg2.value;
	contratto = arg3.value;
	esperienza = arg4.value;
	
	penale_banca = Math.round(valore*10/100);
	penale_contratto = Math.round(contratto * valore /100);
	penale_esperienza = Math.round((20-esperienza)*valore/100);
	
	//penale = penale_contratto + penale_esperienza;
	
	prezzo = valore - penale_banca - penale_contratto - penale_esperienza;
   
  	if (!(confirm("ATTENZIONE!\r\nPer la vendita del giocatore verranno effettuate queste trattenute:\r\n\r\n- incasso BANCA CENTRALE pari al 10% :  €. "+penale_banca+"\r\n- penale sulla durata del contratto          :  €. "+penale_contratto+"\r\n- penale sull'esperienza del giocatore      :  €. "+penale_esperienza+"\r\n\r\nPertanto incasserai dalla vendita :  €. "+prezzo+"\r\n\r\n\r\nConfermi la vendita al prezzo di  €. "+prezzo+" ?")))
  	{
    	return false;
	}
	else
	{
		id = arg1.value;

		indirizzo="data_giocatori_vendi.php?vendi="+id;
   		window.parent.location.href=indirizzo;
    }
}
function cambia()
{
	document.getElementById('spento').style.display = "none";
	document.getElementById('acceso').style.display = "inline";
}
function Contratto()
{
	document.getElementById('elementi_nascosti').style.display = "inline";
}


function Modifica(quanto)
{
	minimo = document.getElementById('wdata0').value;
	variazione = eval(document.getElementById('wdata1').value) + eval(quanto);
	
	if (variazione <= 0)
	{
		variazione = 0;
	}
	if (variazione >= 27)
	{
		variazione = 27;
	}
	nuovo_contratto = eval(minimo)  + eval(variazione);
	document.getElementById('wdata1').value = variazione ; 
	
	// calcolo del moltiplicatore
	if (variazione == 0)
	{
		moltipl = 3.8;
	}
	else if (variazione == 9)
	{
		moltipl = 2.8;
	}
	else if (variazione == 18)
	{
		moltipl = 1.8;
	}
	else if (variazione == 27)
	{
		moltipl = 0.8;
	}
	
	stipendio_base = document.getElementById('wdata3').value;
	valore_stipendio = stipendio_base.replace(".","");
	nuovo_stipendio = eval(valore_stipendio * moltipl);
	
	document.getElementById('wdata2').value = nuovo_stipendio;
	document.getElementById('wdata4').value = nuovo_contratto;
	
	document.getElementById('visualizza_cont').innerHTML = nuovo_contratto;
	document.getElementById('visualizza_stip').innerHTML = "€. "+nuovo_stipendio;
	document.getElementById('puloff').style.display = "none";
	document.getElementById('pulon').style.display = "inline";
	
}
	
function Salva(su_chi)
{
	arg1 = document.getElementById('w_nr').value;
	arg2 = document.getElementById('w_ruolo').value;
	arg3 = document.getElementById('wdata4').value;
	arg4 = document.getElementById('wdata2').value;
		
	indirizzo="data_giocatori_modifica.php?id="+su_chi+"&pagina=2"+"&w_nr="+arg1+"&w_ruolo="+arg2+"&w_cont="+arg3+"&w_stip="+arg4;
	
	window.parent.location.href=indirizzo;
	
}
function nascondi(arg)
	{
		document.getElementById(arg).style.display = 'none';
	}

</script>
</head>


<body>
<?php
 
$nome_team = $_SESSION['SESS_TEAM'];
$trova = $_REQUEST['id'];

if (isset($_REQUEST['err']))
{
	echo "<script>javascript:alert('ERRORE! Numero di maglia ($_REQUEST[err]) già assegnato!');</script>";
}


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
mysql_close($link);

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
$walstip = number_format(($row['stipendio']*3.8),0,",",".");

echo "<h1>";
echo "$row[nome]&nbsp;<input type='button' value='&nbsp;' onclick='javascript:location.href=\"form_giocatori.php\";' class='button_indietro'>";
echo "</h1>";

echo "<div id='MsgPausa'>";
echo "<form id='mod_players' name='mod_players' method='post' action='data_giocatori_modifica.php?id=$trova' target='_parent'>";

echo "<div style='float:left;'>"; // inizio del div float left

echo "<span class='top-label'>";
echo "	<span class='label-txt'>Dati Personali</span>";
echo "</span> ";
echo "<div class='content-area'>";
echo "	<img id='dati_personali' src='images/quadrato_rounded_chiaro.png' width='100%' height='100%' />";
echo "	<span id='dati_personali'>";
echo "		<fieldset id='dati_personali'>";
echo "			<table width='100%' border='0'>";
echo "			<tr style='font-size: 14px; color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>
					<th  style='background-color:#009000;' width='10' align='left'>Maglia</td>	
					<th style='background-color:#009000;' width='10' align='left'>Età </td>
					<th style='background-color:#009000;' width='10' align='left'>Skill </td>
					<th style='background-color:#009000;' width='40' align='left'>Carattere </td>
					<th style='background-color:#009000;' width='10' align='left'>Ruolo </td>
					<td align='center' rowspan='2' width='43'><img id='spento' src='images/salva_spento.png' border='0' width='45'/><input id='acceso' name='acceso' type='image' border='0' width='45' src='images/salva_acceso.png' style='display: none;'>
					</td>
			 </tr>";

echo "<tr style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>
			<td><b><input type='text' id=\"w_nr\" name=\"w_nr\" value=\"$row[nr]\" class='fld_breve' onfocus='javascript:cambia();'/></b></td>
			<td>$row[eta]</td>
			<td>$row[skill]</td>
			<td>$row[carattere]</td>
			<td>
			<select id='w_ruolo' name='w_ruolo'  class='fld_lungo' onchange='javascript:cambia();'>
			   <option value='$row[pos]' selected='selected'>$mostra_ruolo</option>
			   <option value='$row[pos]'>----</option>";
			   foreach ($ruolo as $iter_ruolo)
			   {
					$video_ruolo = $quale_ruolo[$iter_ruolo];
					echo "<option value='$iter_ruolo'>$video_ruolo</option>";
			   }
echo "	    </select>
			</td>
			
			
	 </tr>";
echo "</table>";
echo "		</fieldset>";
echo "	</span>";
echo "</div>";

echo "<br>";

echo "<span class='top-label'>";
echo "	<span class='label-txt'>Dati Contratto</span>";
echo "</span> ";
echo "<div class='content-area'>";
echo "	<img id='dati_personali' src='images/quadrato_rounded_chiaro.png' width='100%' height='100%' />";
echo "	<span id='dati_personali'>";
echo "		<fieldset id='dati_personali'>";
echo "			<table width='100%' border='0'>";
echo "			<tr style='font-size: 14px; color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>
      				<th style='background-color:#009000;' width='100'>Durata</td>	
					<th style='background-color:#009000;' width='100'>Stipendio</td>	
					<th style='background-color:#009000;' width='140'>Valore</td>
					<td align='center' rowspan='2' width='48'><a href='#'/><img id='img_contoff' src='images/contrattobn.png' border='0' width='50' /><img id='img_conton' src='images/contratto.png' border='0' width='50' onclick='javascript:Contratto(w_id);'/></a>
					</td>
					<input type='hidden' id='w_valo' value='$row[valore]' />
					<input type='hidden' id='w_id' value='$row[id]' />
					<input type='hidden' id='w_contr' value='$row[contratto]' />
					<input type='hidden' id='w_esper' value='$row[esp]' />
					<td align='center' rowspan='2' width='48'><a href='#'/><img src='images/stretta-di-mano.png' border='0' width='50' onclick='javascript:Vendita(w_id,w_valo,w_contr,w_esper);'/></a>
					</td>
				</tr>";

echo "			<tr style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>
					<td align='center'>$row[contratto]</td>	
					<td align='center'>€. $wstip</td>	
					<td align='center'>€. $wvalo</td>
					
				</tr>";
echo "			</table>
	  		</fieldset>
		</span>
	</div>";


echo "<span id='elementi_nascosti' style='display: none;'>";
	echo "<br>";
	echo "<span class='top-label'>";
	echo "	<span class='label-txt'>Modifica Contratto</span>";
	echo "</span> ";
	echo "<div class='content-area'>";
	echo "	<img id='elementi_nascosti' src='images/quadrato_rounded_chiaro.png' width='100%' height='100%' />";
	echo "	<span id='elemento_nascosto'>";
	echo "		<fieldset id='elementi_nascosti'>";
	echo "			<table width='80%' border='0' style='color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '> ";
	echo "			<tr style='background-color:#009000;	color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>
						<th>Durata</th>
						<th>Stipendio</th>
					</tr>
					
					<tr>
						<td align='center' id='visualizza_cont'>$row[contratto]</td>
						<td align='center' id='visualizza_stip'>€. $walstip</td>
					<input id='wdata0' name='wdata0' type='hidden' value='$row[contratto]'>	
					<input id='wdata1' name='wdata1' type='hidden' value='0'>	
					<input id='wdata2' name='wdata2' type='hidden' value='$walstip'>
					<input id='wdata3' name='wdata3' type='hidden' value='$wstip'>
					<input id='wdata4' name='wdata4' type='hidden' value='0'>
						<td width='80'>
							<center>
								<a href='#' onclick=\"javascript:Modifica(9);\">
									<img src='images/4Plus.gif' border='0' width='30' >
								</a>
						</td>
						<td width='80'>
							<center>
								<a href='#' onclick=\"javascript:Modifica(-9);\">
									<img src='images/4Minus.gif' border='0' width='30' >
								</a>
						</td>
						<td width='80'>
						<center>
						<img id='puloff' name='puloff' border='0' width='35' src='images/salva_spento.png' > 						
						<a href='#' onclick=\"javascript:Salva($trova);\">
							<img id='pulon' name='pulon' border='0' width='33' src='images/salva_acceso.png' > 
						</a>
						</center>
						</td>
					</tr>
					";
	
	echo "<script>nascondi('pulon');</script>";					
	if ($row['contratto'] <= 5)
	{
		echo "<script>nascondi('img_contoff');</script>";
	}
	else
	{
		echo "<script>nascondi('img_conton');</script>";
	}
	
	 
	echo "			</table>
				</fieldset>
			</span>";
	echo "</div>";
echo "</span>";
	

echo "</div>"; //FINE DEL DIV FLOAT LEFT


echo "<div style='float:right;'>"; // inizio del div float right
	
echo "<span class='top-label'>";
echo "	<span class='label-txt'>Abilità Personali</span>";
echo "</span> ";
echo "<div class='content-area'>";
echo "	<img id='dati_abilita' src='images/quadrato_rounded_chiaro.png' width='100%' height='100%' />";
echo "	<span id='dati_abilita'>";
echo "		<fieldset id='dati_abilita'>";
echo "			<table width='100%' border='0' style='font-size: 14px; color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '>";
echo "			<tr>	<th align='right'>Forma</th>		<td>$row[forma]</td>	</tr>
				<tr>	<th align='right'>Freschezza</th>	<td>$row[fresc]</td>	</tr>
				<tr>	<th align='right'>Condizione</th>	<td>$row[cond]</td>		</tr>
				<tr>	<th align='right'>Esperienza</th>		<td>$row[esp]</td>		</tr>
				<tr>	<th colspan='2'><hr>			</td>		</tr>
				<tr>	<th align='right'>Parate</th>		<td>$row[po]</td>		</tr>
				<tr>	<th align='right'>Difesa</th>		<td>$row[df]</td>		</tr>
				<tr>	<th align='right'>Contrasti</th>	<td>$row[cn]</td>		</tr>
				<tr>	<th align='right'>Passaggi</th>	<td>$row[pa]</td>		</tr>
				<tr>	<th align='right'>Regia</th>		<td>$row[rg]</td>		</tr>	
				<tr>	<th align='right'>Cross</th>		<td>$row[cr]</td>		</tr>
				<tr>	<th align='right'>Tecnica</th>	<td>$row[tc]</td>		</tr>
				<tr>	<th align='right'>Tiro</th>		<td>$row[tr]</td>		</tr>
				<tr>	<th align='right'>Piede</th>		<td><img src=$immpiede width='18' heigth='16' border='0'/></td></tr>		
				<tr>	<th align='right'>Talento</th>	<td>";
				for ($qw=1; $qw <=$row['qta']; $qw++) 
				{
					echo"<img src=$immtal  border='0'/>";
				}
				echo"</td>";
				echo"
				</tr>";

echo "			</table>
			</fieldset>
		</span>
	</div>";
	
echo "</div>";	//fine del div float right
		
echo "<span id='elementi_dati_statistici'>";
echo "<span class='top-label'>";
echo "	<span class='label-txt'>Dati Statistici</span>";
echo "</span> ";
echo "<div class='content-area'>";
echo "	<img id='dati_statistici' src='images/quadrato_rounded_chiaro.png' width='100%' height='100%' />";
echo "	<span id='dati_statistici'>";
echo "		<fieldset id='dati_statistici'>";
echo "			<table width='80%' border='0' style='color:#0000FF; font-family:Geneva, Arial, Helvetica, sans-serif; '> ";
echo "			<tr >
					<th>&nbsp;</th>
					<th style='background-color:#009000;	color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>Stagione</th>
					<th style='background-color:#009000;	color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>Totale</th>
				</tr>
				
				<tr>	<th align='right'>Partite</th>	<td align='center'>$row[part]</td>		<td align='center'>$row[part2]</td>	</tr>
				<tr>	<th align='right'>Reti</th>		<td align='center'>$row[reti]</td>		<td align='center'>$row[reti2]</td>	</tr>
				<tr>	<th align='right'>Ammonizioni</th><td align='center'>$row[gialli]</td>	<td align='center'>$row[gialli2]</td>	</tr>
				<tr>	<th align='right'>Espulsioni</th>	<td align='center'>$row[rossi]</td>	<td align='center'>$row[rossi2]</td>	</tr>";
echo "			</table>
			</fieldset>
		</span>";
echo "</div>";
echo "</span>";




echo "</form>";


echo "<script>";
echo "$('#MsgPausa').slideUp(0).delay(400).fadeIn(400);";
echo "</script>";
echo "</div>";

?>



</body>
</html>