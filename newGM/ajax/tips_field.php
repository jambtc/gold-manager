<?php

require_once('../auth.php');

define('INCLUDE_CHECK',1);
require "../connect_db.php";

?>
<head>
	
<style>
#campo_calcolatore{
	width:288px;
	height:290px;
}
div#campo_calcolatore{
	width:100%;
	height:100%;
	margin-top:20px;
}
div#sposta_span{
	position:relative;
	top:-310px;
	left:-35px;
	
	

}

input.normal { /* BOX CON I VALORI MINORI DEL CALCOLO POSIZIONE*/
 width : 30px;
 height: 30px;
 background: none;
 color: #FFFF00;
 font-family: Verdana, Arial, Helvetica, sans-serif;
 font-size: 9px;
 border: 0 solid;
 padding-left:5px;
 
}
input.max { /* BOX CON I VALORI MAX DEL CALCOLO POSIZIONE*/
 width : 30px;
 height: 30px;
 background: #006600;
 color: #FFFF00;
 font-family: Verdana, Arial, Helvetica, sans-serif;
 font-size: 9px;
 font-weight:bold;
 text-align:left;
 border: 1px solid red ;
 padding-left:5px;
 }
 span#briga1 {
	position: absolute;
	top:9px;
	left:67px;
} 
span#briga2 {
	position: absolute;
	top:35px;
	left:63px;
} 
span#briga3 {
	position: absolute;
	top:62px;
	left:59px;
} 
span#briga4 {
	position: absolute;
	top:90px;
	left:55px;
} 
span#briga5 {
	position: absolute;
	top:119px;
	left:51px;
} 
span#briga6 {
	position: absolute;
	top:148px;
	left:47px;
} 
span#briga7 {
	position: absolute;
	top:180px;
	left:43px;
} 
span#briga8 {
	position: absolute;
	top:211px;
	left:39px;
} 
span#briga9 {
	position: absolute;
	top:243px;
	left:35px;
}  
span#briga10 {
	position: absolute;
	top:285px;
	left:165px;
	
}
 </style>	
	
	
	
</head>
<?php
	if (!isset($_REQUEST['id']))
	{
		echo "<div id='campo_calcolatore'>
				<div >
					<img id='campo_calcolatore' src='../images/in_campo.png' alt='' border='0'>
				</div>
			  </div>";
	}
	else
	{
		$w_id = $_REQUEST['id']; 
		$nome_team = $_SESSION['SESS_TEAM'];
		$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");
		$im_piede = array("p_destro.png","p_sinistro.png","p_ambidestro.png");
		$quale_piede['L'] = "Sinistro";
		$quale_piede['R'] = "Destro";
		$quale_piede['LR'] = "Ambidestro";
		
		//I DATI DEL GIOCATORE CLICCATO
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND id='$w_id'");
		if (!$result) {
			echo 'Errore nella query giocatori: ' . mysql_error();
			exit();
		}
		$row   =   mysql_fetch_array($result) ;
	
		if(!$row) die("Nessun dato trovato!");
	
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
		
	
		//VISUALIZZAZIONE DATI A SCHERMO
	
		echo "<div id='campo_calcolatore'>
				<div >
					<img id='campo_calcolatore' src='../images/in_campo.png' alt='' border='0'>
				</div>";
		
		echo "
		<div id='sposta_span' >
		<span id='briga1' >
		 <table border='0' cellpadding='0' cellspacing='2'>
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
		 <table border='0' cellpadding='0' cellspacing='3'>
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
		 <table border='0' cellpadding='0' cellspacing='4'>
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
		 <table border='0' cellpadding='0' cellspacing='5'>
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
		 <table border='0' cellpadding='0' cellspacing='6'>
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
		 <table border='0' cellpadding='0' cellspacing='7'>
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
		 <table border='0' cellpadding='0' cellspacing='8'>
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
		 <table border='0' cellpadding='0' cellspacing='9'>
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
		 <table border='0' cellpadding='0' cellspacing='10'>
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
		</div>
		</div>
		";
	}
?>
