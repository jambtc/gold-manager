<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<style type='text/css'> @import 'statistiche.css'; </style>
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>
	<script type="text/javascript" src="jquery/my_tooltip.js"></script>

</head>
<body style="background:trasparent;"> 
<?php 
	include "connect_db.php";	
	$nome_team = $_SESSION['SESS_TEAM'];

	if (!isset($_REQUEST['ordina']))
	{
		$ordina = "r.ruolo_order ASC";
		$nuovo_ordine = "DESC";
	}
	else
	{
		$ordina = "g.".$_REQUEST['ordina']." ".$_REQUEST['verso'];
		$nuovo_ordine = "ASC";
		if ($_REQUEST['verso'] == "ASC")
		{
			$nuovo_ordine = "DESC";
		}
		
	}
	
	$qry = "SELECT * FROM giocatori as g, ruoli as r WHERE g.id_team=\"$nome_team\" and g.pos=r.ruolo_desc order by ".$ordina;
	
	$result = mysql_query($qry);
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
    	exit();
	}

	$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");

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
?>
<table  width='100%' border='0' style='font-family:Geneva, Arial, Helvetica, sans-serif;' >
<thead>
<tr>
	<th title='Infortunio'>
		<a class='a_black' href='#' onclick="javascript: return false;">Inf.</a>
	</th>
	<th title='Maglia'>
		<a class='a_black' href='team_list.php?ordina=nr&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Nr</a>
	</th>
	<th align='left' title='Nome'>
		<a class='a_black' href='team_list.php?ordina=nome&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Nome</a>
	</th>
	<th title='Settimane di contratto'>
		<a class='a_black' href='team_list.php?ordina=contratto&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Con.</a>
	</th>
	<th title='Età'>
		<a class='a_black' href='team_list.php?ordina=eta&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Et&agrave;</a>
	</th>
	<th title='Forza'>
		<a class='a_black' href='team_list.php?ordina=skill&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Skill</a>
	</th>
	<th title='Ruolo'>
		<a class='a_black' href='team_list.php?ordina=pos&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Pos</a>
	</th>
	<th title='Forma fisica'>
		<a class='a_black' href='team_list.php?ordina=forma&verso=<?php echo $nuovo_ordine ?>' target='team_list'>F.ma</a>
	</th>
	<th title='Freschezza'>
		<a class='a_black' href='team_list.php?ordina=fresc&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Frs.</a>
	</th>
	<th title='Condizione atletica'>
		<a class='a_black' href='team_list.php?ordina=cond&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Con.</a>
	</th>
	<th title='Esperienza'>
		<a class='a_black' href='team_list.php?ordina=esp&verso=<?php echo $nuovo_ordine ?>' target='team_list'>Esp.</a>
	</th>
	<th title='Parate'>
		<a class='a_black' href='team_list.php?ordina=po&verso=<?php echo $nuovo_ordine ?>' target='team_list'>PO</a>
	</th>
	<th title='Difesa'>
		<a class='a_black' href='team_list.php?ordina=df&verso=<?php echo $nuovo_ordine ?>' target='team_list'>DF</a>
	</th>
	<th title='Contrasti'>
		<a class='a_black' href='team_list.php?ordina=cn&verso=<?php echo $nuovo_ordine ?>' target='team_list'>CN</a>
	</th>
	<th title='Passaggi'>
		<a class='a_black' href='team_list.php?ordina=pa&verso=<?php echo $nuovo_ordine ?>' target='team_list'>PA</a>
	</th>
	<th title='Regia'>
		<a class='a_black' href='team_list.php?ordina=rg&verso=<?php echo $nuovo_ordine ?>' target='team_list'>RG</a>
	</th>
	<th title='Cross'>
		<a class='a_black' href='team_list.php?ordina=cr&verso=<?php echo $nuovo_ordine ?>' target='team_list'>CR</a>
	</th>
	<th title='Tecnica'>
		<a class='a_black' href='team_list.php?ordina=tc&verso=<?php echo $nuovo_ordine ?>' target='team_list'>TC</a>
	</th>
	<th title='Tiro'>
		<a class='a_black' href='team_list.php?ordina=tr&verso=<?php echo $nuovo_ordine ?>' target='team_list'>TR</a>
	</th>
	<th title='Piede'>
		<a class='a_black' href='#' onclick="javascript: return false;">Pd.</a>
	</th>
	<th align='left'>
		<a class='a_black' href='#' onclick="javascript: return false;">Talento</a>
	</th>
</tr>
</thead>
<tbody>
<tr>
<?php 
	$counter = 0; 
	while   ($row   =   mysql_fetch_array($result))
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
				

		($counter % 2 == 0) ? $class = "BGreen" : $class = "LGreen"; 
		($counter % 2 == 0) ? $stl = "filter:alpha(opacity=90); -moz-opacity:0.9; opacity:0.9;  width:100%; height:auto; height:100%;" : $stl = "filter:alpha(opacity=70); -moz-opacity:0.7; opacity:0.7;  width:100%; height:auto; height:100%;"; 

		
		echo "<tr class=\"$class\" style=\"$stl\" id='tr_$counter' onmouseover='javascript: Over($counter);' onmouseout='javascript: Out($counter);'>";
		echo "<td width='30'><center>";
		if ($row['infortunio'] > 0)
		{
			echo "<img src=$imm_inf width='18' heigth='16' border='0' title='Settimane necessarie alla guarigione'/>$giorni_inf";
		}
		echo "</center></td>";
		
		//echo "<td align='center'> <a class='a_null' href='team_calcolatore.php?id=$row[id]' target='_parent'>" . "$row[nr]" ."</td>";
		echo "<td align='center'><b>$row[nr]</b></td>";
		//echo "<td width=22%><div class='player'><a id='$row[id]' class='a_null' href='m-index.php?fnz=team_change&pg=31&id=$row[id]' target='_parent'>$row[nome]</a></div></td>";
		echo "<td width=20%><a id='$row[id]' class='a_null' href='team_change.php?id=$row[id]' target='team_list' onmouseover='javascript:jam_tooltip($row[id]);' title='Clicca per modificare i dettagli del giocatore'>$row[nome]</a></td>";
		echo "<td width=40> <center>$row[contratto]" ."</td>";
		echo "<td width=40> <center>$row[eta]" ."</td>";
		echo "<td width=40> <center>$row[skill]" ."</td>";
		echo "<td>";
		
		echo "<center><img src=$immpos width='18' heigth='16' border='0' title='$quale_ruolo[$t_ruolo]'/> ";
		
		echo "</td>";
		echo "<td width=48> <center>$row[forma]" ."</td>";
		echo "<td width=48> <center>$row[fresc]" ."</td>";
		echo "<td width=48> <center>$row[cond]" ."</td>";
		echo "<td width=48> <center>$row[esp]" ."</td>";
		echo "<td width=39> <center>$row[po]" ."</td>";
		echo "<td width=39> <center>$row[df]" ."</td>";
		echo "<td width=39> <center>$row[cn]" ."</td>";
		echo "<td width=39> <center>$row[pa]" ."</td>";
		echo "<td width=39> <center>$row[rg]" ."</td>";
		echo "<td width=39> <center>$row[cr]" ."</td>";
		echo "<td width=39> <center>$row[tc]" ."</td>";
		echo "<td width=39> <center>$row[tr]" ."</td>";
		echo "<td><center><img src=$immpiede width='18' heigth='16' border='0' title='$t_piede'/></td>";
		echo "<td width=70>";

		for ($qw=1; $qw <=$row['qta']; $qw++)
		{
			echo"<img src=$immtal  border='0' title='$row[talento]'/>";
		}
		echo "</td>";
		echo "</tr>";
	
		$counter++; 
	}
	echo "<tr height='50'><td>&nbsp;</td></tr></tbody></table>";
	mysql_close($link);
?>
	


</body>
</html>
