<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
</head>
<?php 
	$nome_team = $_SESSION['SESS_TEAM'];
	$w_id = $_REQUEST['id']; 
	
	$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");
	$im_piede = array("p_destro.png","p_sinistro.png","p_ambidestro.png");
	$quale_piede['L'] = "Sinistro";
	$quale_piede['R'] = "Destro";
	$quale_piede['LR'] = "Ambidestro";
	
	if (isset($_REQUEST['target']))
	{
		include "connect_db.php";
	}
	// CARICO I DATI DEL GIOCATORE CLICCATO
	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND id='$w_id'");
	if (!$result) {
		echo 'Errore nella query giocatori: ' . mysql_error();
		exit();
	}
	$row   =   mysql_fetch_array($result) ;
	
	$wmaglia = $row['nr'];
	$po = $row['po'];
	$df = $row['df'];
	$cn = $row['cn'];
	$pa = $row['pa'];
	$rg = $row['rg'];
	$cr = $row['cr'];
	$tc = $row['tc'];
	$tr = $row['tr'];
	$esp = $row['esp'];
	$piede = strtoupper($row['piede']);
	$posizione = $row['pos'];
	$nome = $row['nome'];
	$eta = $row['eta'];
	$skill = $row['skill'];
	$forma = $row['forma'];
	$fresch = $row['fresc'];
	$cond = $row['cond'];
	
	//$controllo = $esp/2 - ($skill*10);
	$controllo = 15;
	if ($controllo >90) {$controllo = 90;}
	if ($controllo <10) {$controllo = 10;}
	
	$formula = "Formula 2";
	
	// CARICO I DATI DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE formula=\"$formula\" ORDER BY ord");
	if (!$calc_result) { echo 'Errore nella query calcolatore totale: ' . mysql_error(); exit(); }
	
	$max = 0;
	$ii = 1; 
	while   ($riga   =   mysql_fetch_array($calc_result)) 
	{
		// CONTROLLA TUTTI I CASI DELLE CASELLE
		switch ($ii)
		{ 
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
				if ($piede == "R")  {$pdd = -6;}
				elseif ($piede == "L") {$pdd = 6;}
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
				if ($piede == "R")  {$pdd = 4;}
				elseif ($piede == "L") {$pdd = 4;}
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
				if ($piede == "R")  {$pdd = 6;}
				elseif ($piede == "L") {$pdd = -6;}
				else{$pdd = 4;}
				break;
			//q0	
			case 64:
				$pdd = 4;
				break;
		} // end switch
		
		$riquadro[$ii] = $pdd + $po*$riga['po'] + $df*$riga['df'] + $cn*$riga['cn'] + $pa*$riga['pa'] + $rg*$riga['rg'] + $cr*$riga['cr'];
		$riquadro[$ii] = round(($riquadro[$ii] + $tc*$riga['tc'] + $tr*$riga['tr'])/(101-$controllo),1) ;
		
		if ($max <= $riquadro[$ii])
		{ 
			$max = $riquadro[$ii] ; 
			$box[] = $ii; 
			$val[] = $max; 
		}
		$class[$ii] = "normal"; //valore normale
		$ii ++ ;
	}
	
	$number = count($box);
	for ($id = $number-1; $id >= 0; $id --)
	{ 
		if ($max <= $val[$id]) 
		{ 
			$class[$box[$id]] = "max"; 
		}
	}
	
	echo "<h1>";
	echo "Calcolatore ";
	
	if (isset($_REQUEST['target']))
	{
		echo "<input type='button' value='&nbsp;' onclick='javascript:location.href=\"formazione_sx.php?box=0\";' class='button_indietro'>";
	}
	else
	{	
		echo "<input type='button' value='&nbsp;' onclick='javascript:location.href=\"form_giocatori.php\";' class='button_indietro'>";

	}
	echo "</h1>";

	//VISUALIZZAZIONE DATI A SCHERMO
	//echo "<table  width='500' border='0' cellspacing=''>";
	echo "<table  width='50%' border='1' cellspacing='0' >";
	echo "<tr style='font-size: 14px; background-color:#009000;	color:#FFFF00; font-family:Geneva, Arial, Helvetica, sans-serif; '>";
	echo "<th height='10' width='20' scope='col'><span class='Stile8'>Nr</span></th>";
	echo "    <th width='30%' scope='col' align='left'><span class='Stile8'>Nome</span></th>";
	echo "    <th width='20' scope='col'><span class='Stile8'>Et&agrave;</span></th>";
	echo "    <th width='30' scope='col'><span class='Stile8'>Skill</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>Pos</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>F.ma</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>Frs.</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>Con.</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>Esp.</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>PO</span></th>";
		echo "    <th width='25' scope='col'><span class='Stile8'>DF</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>CN</span></th>";
		echo "    <th width='25' scope='col'><span class='Stile8'>PA</span></th>";
		echo "    <th width='25' scope='col'><span class='Stile8'>RG</span></th>";
		echo "    <th width='25' scope='col'><span class='Stile8'>CR</span></th>";
		echo "    <th width='25' scope='col'><span class='Stile8'>TC</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>TR</span></th>";
	echo "    <th width='25' scope='col'><span class='Stile8'>Pd.</span></th>";
	echo "</tr>";
	
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
	
	switch ($piede) {
		case "R":
			$immpiede = $im_piede[0];
			break;
		case "L":
			$immpiede = $im_piede[1];
			break;
		default:
			$immpiede = $im_piede[2];
					
	}
	$immpiede = "images/".$immpiede;
		
	echo "<tr style='font-size: 12px; background-color:#009000;	color:#ffffff; font-family:Geneva, Arial, Helvetica, sans-serif; '>";
	
	echo "<td height='16'>$wmaglia" ."</td>";
	echo "<td> $nome" ."</td>";
	echo "<td> <center>$eta" ."</td>";
	echo "<td> <center>$skill" ."</td>";
	echo "<td>";
	
	echo "<center><img src=$immpos  /> ";
	
	echo "</td>";
	echo "<td> <center>$forma" ."</td>";
	echo "<td> <center>$fresch" ."</td>";
	echo "<td> <center>$cond" ."</td>";
	echo "<td> <center>$esp" ."</td>";
	echo "<td> <center>$po" ."</td>";
		echo "<td> <center>$df" ."</td>";
		echo "<td> <center>$cn" ."</td>";
		echo "<td> <center>$pa" ."</td>";
		echo "<td> <center>$rg" ."</td>";
		echo "<td> <center>$cr" ."</td>";
		echo "<td> <center>$tc" ."</td>";
	echo "<td> <center>$tr" ."</td>";
	echo "<td> <center><img src=$immpiede width='16' heigth='16' />" ."</td>";
	
	echo "</tr>";
	echo "</table>";




	echo "<form name='field' border='0'>";
	echo "<div id='campo_calcolatore'><img id='campo_calcolatore' src='images/in_campo.png' alt='' border='0'></div>";
	
	echo "
	<span id='briga1' >
	 <table border='0' cellpadding='0' cellspacing='3'>
	 <tr>
	 <td><input name='btn' class=\"$class[1]\" value=$riquadro[1]  type='button' ></td>
	 <td><input name='btn' class=\"$class[2]\" value=$riquadro[2]  type='button' ></td>
	 <td><input name='btn' class=\"$class[3]\" value=$riquadro[3]  type='button' ></td>
	 <td><input name='btn' class=\"$class[4]\" value=$riquadro[4]  type='button' ></td>
	 <td><input name='btn' class=\"$class[5]\" value=$riquadro[5]  type='button'  ></td>
	 <td><input name='btn' class=\"$class[6]\" value=$riquadro[6]  type='button'  ></td>
	 <td><input name='btn' class=\"$class[7]\" value=$riquadro[7]  type='button'  ></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='briga2' > 
	 <table border='0' cellpadding='0' cellspacing='4'>
	 <tr>
	 <td><input name='btn' class=\"$class[8]\" value=$riquadro[8]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[9]\" value=$riquadro[9]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[10]\" value=$riquadro[10]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[11]\" value=$riquadro[11]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[12]\" value=$riquadro[12]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[13]\" value=$riquadro[13]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[14]\" value=$riquadro[14]  type='button'   ></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='briga3' > 
	 <table border='0' cellpadding='0' cellspacing='5'>
	 <tr>
	 <td><input name='btn' class=\"$class[15]\" value=$riquadro[15]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[16]\" value=$riquadro[16]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[17]\" value=$riquadro[17]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[18]\" value=$riquadro[18]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[19]\" value=$riquadro[19]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[20]\" value=$riquadro[20]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[21]\" value=$riquadro[21]  type='button'   ></td>
	 </tr>
	 </table>
	 </span>
	";
	echo "
	<span id='briga4' > 
	 <table border='0' cellpadding='0' cellspacing='6'>
	 <tr>
	 <td><input name='btn' class=\"$class[22]\"' value=$riquadro[22]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[23]\"' value=$riquadro[23]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[24]\"' value=$riquadro[24]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[25]\"' value=$riquadro[25]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[26]\"' value=$riquadro[26]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[27]\"' value=$riquadro[27]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[28]\"' value=$riquadro[28]  type='button'   ></td>
	 </tr>
	 </table>
	 </span>
	";
	echo "
	<span id='briga5' > 
	 <table border='0' cellpadding='0' cellspacing='7'>
	 <tr>
	 <td><input name='btn' class=\"$class[29]\"' value=$riquadro[29]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[30]\"' value=$riquadro[30]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[31]\"' value=$riquadro[31]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[32]\"' value=$riquadro[32]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[33]\"' value=$riquadro[33]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[34]\"' value=$riquadro[34]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[35]\"' value=$riquadro[35]  type='button'   ></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='briga6' > 
	 <table border='0' cellpadding='0' cellspacing='8'>
	 <tr>
	 <td><input name='btn' class=\"$class[36]\"' value=$riquadro[36]  type='button'  ></td>
	 <td><input name='btn' class=\"$class[37]\"' value=$riquadro[37]  type='button'  ></td>
	 <td><input name='btn' class=\"$class[38]\"' value=$riquadro[38]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[39]\"' value=$riquadro[39]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[40]\"' value=$riquadro[40]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[41]\"' value=$riquadro[41]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[42]\"' value=$riquadro[42]  type='button'   ></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='briga7' > 
	 <table border='0' cellpadding='0' cellspacing='9'>
	 <tr>
	 <td><input name='btn' class=\"$class[43]\"' value=$riquadro[43]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[44]\"' value=$riquadro[44]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[45]\"' value=$riquadro[45]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[46]\"' value=$riquadro[46]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[47]\"' value=$riquadro[47]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[48]\"' value=$riquadro[48]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[49]\"' value=$riquadro[49]  type='button'   ></td>
	 </table>
	</span>
	";
	echo "
	<span id='briga8' > 
	 <table border='0' cellpadding='0' cellspacing='10'>
	 <tr>
	 <td><input name='btn' class=\"$class[50]\"' value=$riquadro[50]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[51]\"' value=$riquadro[51]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[52]\"' value=$riquadro[52]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[53]\"' value=$riquadro[53]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[54]\"' value=$riquadro[54]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[55]\"' value=$riquadro[55]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[56]\"' value=$riquadro[56]  type='button'   ></td>
	 </tr>
	 </table>
	</span>
	";
	echo "
	<span id='briga9' > 
	 <table border='0' cellpadding='0' cellspacing='11'>
	 <tr>
	 <td><input name='btn' class=\"$class[57]\"' value=$riquadro[57]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[58]\"' value=$riquadro[58]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[59]\"' value=$riquadro[59]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[60]\"' value=$riquadro[60]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[61]\"' value=$riquadro[61]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[62]\"' value=$riquadro[62]  type='button'   ></td>
	 <td><input name='btn' class=\"$class[63]\"' value=$riquadro[63]  type='button'   ></td>
	 </tr>
	 </table>
	</span>
	";
	
	echo "
	<span id='briga10' > 
	 <table border='0' cellpadding='0' cellspacing='0'>
	 <tr>
	 <td><input name='btn' class=\"$class[64]\"' value=$riquadro[64]  type='button'   ></td>
	 </tr>
	 </table>
	</span>
	";

mysql_close($link);
?>

