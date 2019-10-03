<?php
	if (isset($_REQUEST['ajax']))
	{
		require_once('auth.php');
		include "connect_db.php";
	?><head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/jquery-ui-1.8.17.custom.min.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script> 
	<script type="text/javascript" src="jquery/my_script_team.js"></script>
	<script type="text/javascript" src="jquery/my_tooltip_allena.js"></script>
	</head>
	<?php
	}
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	$formula = "Formula 2";

	$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");
	
	$im_piede = array("p_destro.png","p_sinistro.png","p_ambidestro.png");

	$im_tal = array("t_nessuno.png","t_colpoditesta.png","t_rigori.png","t_calciodangolo.png","t_calcidipunizione.png","t_dribbling.png","t_velocita.png","t_resistenza.png","t_potenzadeltiro.png","t_cross.png","t_creativita.png","t_fiutodelgoal.png","t_pararigori.png","t_riflessifelini.png","t_visionedigioco.png");

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
	
	
	$a_id_player[] = "";
	$a_id_allena[] = "";
	
	$result = mysql_query("SELECT * FROM allena_skill WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
		echo 'Errore nella query ALLENAMENTO: ' . mysql_error();
	    exit();
	}
	while   ($row   =   mysql_fetch_array($result))
	{
		$a_id_player[] = $row['id_player'];
		$a_id_allena[] = $row['id_allena'];
		$a_forma[] = $row['forma'];
		$a_fresc[] = $row['fresc'];
		$a_cond[] = $row['cond'];
		$a_po[] = $row['po'];
		$a_df[] = $row['df'];
		$a_cn[] = $row['cn'];
		$a_pa[] = $row['pa'];
		$a_rg[] = $row['rg'];
		$a_cr[] = $row['cr'];
		$a_tc[] = $row['tc'];
		$a_tr[] = $row['tr'];
	}
	$ar_player_allenato = array_combine($a_id_player,$a_id_allena);
	
	//CARICA I GIOCATORI
	$result = mysql_query("SELECT * FROM giocatori as g, ruoli as r WHERE g.id_team=\"$nome_team\" and g.pos=r.ruolo_desc order by r.ruolo_order");
	if (!$result) {
    	echo 'Errore nella query SELEZIONA GIOCATORI: ' . mysql_error();
	    exit();
	}
?>
<table width="99%" border="0" style="color:#000000; font-size:12px;">
<tr class='green_bar'>
	<td width='1%'>Inf.</td>
	<td width='1%' align="right">Nr</td>
	<td width='20%' align='left'>Nome</td>
	<td width='1%'>Et&agrave;</td>
	<td width='2%'>Skill</td>
	<td width='2%' title='Ruolo'>Pos</td>
	<td width='2%' title='Piede'>Pd.</td>
	<td width='2%' title='Forma fisica'>F.ma</td>
	<td width='2%' title='Freschezza'>Frs.</td>
	<td width='2%' title='Condizione atletica'>Con.</td>
	<td width='10%' >Talento</td>
</tr>
<?php
$counter = 0; 
while   ($row = mysql_fetch_array($result))
{
	switch ($row['pos'])
	{
		case "PO":
			$immpos = $im_pos[0];
			break;
		case "DS":
			$immpos = $im_pos[1];
			break;
		case "D":
			$immpos = $im_pos[2];
			break;
		case "DD":
			$immpos = $im_pos[3];
			break;
		case "CS":
			$immpos = $im_pos[4];
			break;
		case "C":
			$immpos = $im_pos[5];
			break;
		case "CD":
			$immpos = $im_pos[6];
			break;
		case "AS":
			$immpos = $im_pos[7];
			break;
		case "A":
			$immpos = $im_pos[8];
			break;
		case "AD":
			$immpos = $im_pos[9];
			break;
		default:
			$immpos = $im_pos[10];
	}
	$immpos = "images/".$immpos;
		
		$t_ruolo = $row['pos'];

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
				$t_piede = "Destro";
				break;
			case "L":
				$immpiede = $im_piede[1];
				$t_piede = "Sinistro";
				break;
			case "LR":
				$immpiede = $im_piede[2];
				$t_piede = "Ambidestro";
				break;
			default:
				$immpiede = $im_piede[0];
				$t_piede = "Destro";			
		}
		$immpiede = "images/".$immpiede;
		
		if ($row['infortunio'] > 0)
		{
			$imm_inf = "images/crocerossa.gif";
			$giorni_inf = $row['infortunio'];
		}

		
	if ($row['infortunio'] == 1)
	{
		$check[$counter] = "checked";
		$infortunio[$counter] = 1;
	}
	else
	{
		$check[$counter] = "";
		$infortunio[$counter] = 0;
	}

	$class = "scelto_riposo"; 
	
	echo "<tr id='TR_$row[id]'  class=\"$class\" >";
	
	if ($infortunio[$counter] > 1)
	{
		echo "<td><div class='nodrag'><img id='$row[id]' src='images/maglietta_azzurra.png' width='20' height='24' /></div></td>";	
		echo "<td align=right style='color:#990000;'><b>$row[nr])</b></td>";
		echo "<td style='color:#990000;'><b>$row[nome]</b></td>";
	}
	//print_r($ar_player_allenato);
	
	if (isset($ar_player_allenato[$row['id']])) 
	{
		echo "<td></td>";
	}
	else
	{
		echo "<td><div class='my_players'><img id='$row[id]' name='$row[nr]' src='images/maglietta_azzurra.png' width='20' height='24' onmouseout='javascript:tooltip_hide();' /></div></td>";
	}
	
	echo "<td align=right><b>$row[nr])</b></td>";
	echo "<td><b>$row[nome]</b></td>";
	echo "<th>$row[eta]</th>";
	echo "<th>$row[skill]</th>";
	echo "<td><img src=$immpos width='18' heigth='16' border='0' title='$quale_ruolo[$t_ruolo]'/></td>";
	echo "<td><center><img src=$immpiede width='18' heigth='16' border='0' title='$t_piede'/></td>";
	echo "<td> <center>$row[forma]" ."</td>";
	echo "<td> <center>$row[fresc]" ."</td>";
	echo "<td> <center>$row[cond]" ."</td>";
	echo "<td>";

		for ($qw=1; $qw <=$row['qta']; $qw++)
		{
			echo"<img src=$immtal  border='0' title='$row[talento]'/>";
		}
		echo "</td>";
	echo "</tr>";

	$counter++; 
}
echo "<tr><td height=30></td></tr></table>	";
?>
