<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	$quale_css = "css/index_allena".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index_allena1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
<script type="text/javascript">
var taken = 0;

function take(which)
{
	if (which >= 0)
	{
		document.field.group[which-1].checked = true;

		document.field.btn[taken].style.border = "1px solid white";
		document.field.btn[taken].style.color  = "white";
		
		taken = which-1;

		document.field.btn[taken].style.border = "1px solid red";
		document.field.btn[taken].style.color  = "red";
	}
 	indirizzo="data_allenamento_aggiorna.php?box="+which+"&pagina=1";
  	//window.location.href=indirizzo;
	window.parent.frames['allenamento'].location.href=indirizzo;
}
</script>
</head>

<body>
<?php 
	include "connect_db.php";	
	$nome_team = $_SESSION['SESS_TEAM'];
	if (isset($_REQUEST['maxbox']))
		{
			$maxbox = $_REQUEST['maxbox'];
		}
		else
		{
			$maxbox = 0;
		}

	// parametri relativi alle tattiche
	$wspagina = 1;
	// !!!!!!!!!!!!!!!!!!!!!!

	//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY 
	$appoggio= array('a_id_team','a_data',
				'sk_forma','sk_cond','sk_po','sk_df','sk_cn','sk_pa','sk_rg','sk_cr','sk_tc','sk_tr','sk_forma_val','sk_cond_val','sk_po_val','sk_df_val','sk_cn_val','sk_pa_val','sk_rg_val','sk_cr_val','sk_tc_val','sk_tr_val');

	//$immbarretta = "images/barretta.png";
	//$immvuota = "images/barrettavuota.png";

	$allenamento_result = mysql_query("SELECT * FROM allena_skill WHERE a_id_team=\"$nome_team\"");
	if (!$allenamento_result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	$row = mysql_fetch_array($allenamento_result);

	$conta = 2;
	while ($conta <= 11)
	{
		$ind = 1;
		while ($ind <=10)
		{
			$riga = 10 * ($conta-2) + $ind;
			
			if ($row[$appoggio[$conta]] >= $ind)
			{
				$riquadro[$riga] = 1;
				switch ($ind)
				{
					case 1:
					case 2:
					case 3:
						$class[$riga] = "skillgreenscuro";
						break;
					case 4:
						$class[$riga] = "skillgreen";
						break;
					case 5:
					case 6:
						$class[$riga] = "skillyellow";
						break;
					case 7:
					case 8:
						$class[$riga] = "skillorange";
						break;
					case 9:
					case 10:
						$class[$riga] = "skillred";
						break;
				}
			}
			else
			{
				$riquadro[$riga] = 0;
				$class[$riga] = "skilltrasparent";
			}
			$ind ++;
		}
		$conta ++;
	}
	$forma = round($row['sk_forma_val']/326*100,1);
	$condizione = round($row['sk_cond_val']/326*100,1);
	$parate = round($row['sk_po_val']/326*100,1);
	$difesa = round($row['sk_df_val']/326*100,1);
	$contrasti = round($row['sk_cn_val']/326*100,1);
	$passaggi = round($row['sk_pa_val']/326*100,1);
	$regia = round($row['sk_rg_val']/326*100,1);
	$cross = round($row['sk_cr_val']/326*100,1);
	$tecnica = round($row['sk_tc_val']/326*100,1);
	$tiro = round($row['sk_tr_val']/326*100,1);

echo "
	<form name='field'>
	
	 <table border='0' cellpadding='0' cellspacing='3' style='color:#000000;font-weight:bold;		font-family:Geneva, Arial, Helvetica, sans-serif;font-size: 14px;'>
	 <tr>
	 <td width='200'>Forma</td>
	 <td><input name='btn' class=\"$class[1]\" value=$riquadro[1]  type='button' onclick='take(\"1\");'></td>
	 <td><input name='btn' class=\"$class[2]\" value=$riquadro[2]  type='button' onclick='take(\"2\");'></td>
	 <td><input name='btn' class=\"$class[3]\" value=$riquadro[3]  type='button' onclick='take(\"3\");'></td>
	 <td><input name='btn' class=\"$class[4]\" value=$riquadro[4]  type='button' onclick='take(\"4\");'></td>
	 <td><input name='btn' class=\"$class[5]\" value=$riquadro[5]  type='button' onclick='take(\"5\");'></td>
	 <td><input name='btn' class=\"$class[6]\" value=$riquadro[6]  type='button' onclick='take(\"6\");'></td>
	 <td><input name='btn' class=\"$class[7]\" value=$riquadro[7]  type='button' onclick='take(\"7\");'></td>
	 <td><input name='btn' class=\"$class[8]\" value=$riquadro[8]  type='button' onclick='take(\"8\");'></td>
	 <td><input name='btn' class=\"$class[9]\" value=$riquadro[9]  type='button' onclick='take(\"9\");'></td>
	 <td><input name='btn' class=\"$class[10]\" value=$riquadro[10]  type='button' onclick='take(\"10\");'></td>
	 
	<td width='100'>$forma %</td>
	
	 </tr>
	 ";
	 /*echo "<tr><td height='6'></td><td colspan='10'>";
		
		$corri = $row['sk_forma_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_forma_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
	 echo "</td></tr>";*/
	 echo"
	 <tr>
	 <td>Cond. Fisica</td>
	 <td><input name='btn' class=\"$class[11]\" value=$riquadro[11]  type='button' onclick='take(\"11\");'></td>
	 <td><input name='btn' class=\"$class[12]\" value=$riquadro[12]  type='button' onclick='take(\"12\");'></td>
	 <td><input name='btn' class=\"$class[13]\" value=$riquadro[13]  type='button' onclick='take(\"13\");'></td>
	 <td><input name='btn' class=\"$class[14]\" value=$riquadro[14]  type='button' onclick='take(\"14\");'></td>
	 <td><input name='btn' class=\"$class[15]\" value=$riquadro[15]  type='button' onclick='take(\"15\");'></td>
	 <td><input name='btn' class=\"$class[16]\" value=$riquadro[16]  type='button' onclick='take(\"16\");'></td>
	 <td><input name='btn' class=\"$class[17]\" value=$riquadro[17]  type='button' onclick='take(\"17\");'></td>
	 <td><input name='btn' class=\"$class[18]\" value=$riquadro[18]  type='button' onclick='take(\"18\");'></td>
	 <td><input name='btn' class=\"$class[19]\" value=$riquadro[19]  type='button' onclick='take(\"19\");'></td>
	 <td><input name='btn' class=\"$class[20]\" value=$riquadro[20]  type='button' onclick='take(\"20\");'></td>
	 
	 <td width='100'>$condizione %</td>
	</tr>
	 ";
	 /*echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_cond_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_condizione_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo"
	 <tr>
	 <td>Parate</td>
	 <td><input name='btn' class=\"$class[21]\" value=$riquadro[21]  type='button' onclick='take(\"21\");'></td>
	 <td><input name='btn' class=\"$class[22]\" value=$riquadro[22]  type='button' onclick='take(\"22\");'></td>
	 <td><input name='btn' class=\"$class[23]\" value=$riquadro[23]  type='button' onclick='take(\"23\");'></td>
	 <td><input name='btn' class=\"$class[24]\" value=$riquadro[24]  type='button' onclick='take(\"24\");'></td>
	 <td><input name='btn' class=\"$class[25]\" value=$riquadro[25]  type='button' onclick='take(\"25\");'></td>
	 <td><input name='btn' class=\"$class[26]\" value=$riquadro[26]  type='button' onclick='take(\"26\");'></td>
	 <td><input name='btn' class=\"$class[27]\" value=$riquadro[27]  type='button' onclick='take(\"27\");'></td>
	 <td><input name='btn' class=\"$class[28]\" value=$riquadro[28]  type='button' onclick='take(\"28\");'></td>
	 <td><input name='btn' class=\"$class[29]\" value=$riquadro[29]  type='button' onclick='take(\"29\");'></td>
	 <td><input name='btn' class=\"$class[30]\" value=$riquadro[30]  type='button' onclick='take(\"30\");'></td>
	 
	<td width='100'>$parate %</td>
	 
	 </tr>
	 ";
	/* echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_po_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_parate_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo"
	
	 
	 <tr>
	 <td>Difesa</td>
	 <td><input name='btn' class=\"$class[31]\" value=$riquadro[31]  type='button' onclick='take(\"31\");'></td>
	 <td><input name='btn' class=\"$class[32]\" value=$riquadro[32]  type='button' onclick='take(\"32\");'></td>
	 <td><input name='btn' class=\"$class[33]\" value=$riquadro[33]  type='button' onclick='take(\"33\");'></td>
	 <td><input name='btn' class=\"$class[34]\" value=$riquadro[34]  type='button' onclick='take(\"34\");'></td>
	 <td><input name='btn' class=\"$class[35]\" value=$riquadro[35]  type='button' onclick='take(\"35\");'></td>
	 <td><input name='btn' class=\"$class[36]\" value=$riquadro[36]  type='button' onclick='take(\"36\");'></td>
	 <td><input name='btn' class=\"$class[37]\" value=$riquadro[37]  type='button' onclick='take(\"37\");'></td>
	 <td><input name='btn' class=\"$class[38]\" value=$riquadro[38]  type='button' onclick='take(\"38\");'></td>
	 <td><input name='btn' class=\"$class[39]\" value=$riquadro[39]  type='button' onclick='take(\"39\");'></td>
	 <td><input name='btn' class=\"$class[40]\" value=$riquadro[40]  type='button' onclick='take(\"40\");'></td>
	 
	<td width='100'>$difesa %</td>

	 </tr>
	 ";
	/* echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_df_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_difesa_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo"
	
	
	 
	 <tr>
	 <td>Contrasti</td>
	 <td><input name='btn' class=\"$class[41]\" value=$riquadro[41]  type='button' onclick='take(\"41\");'></td>
	 <td><input name='btn' class=\"$class[42]\" value=$riquadro[42]  type='button' onclick='take(\"42\");'></td>
	 <td><input name='btn' class=\"$class[43]\" value=$riquadro[43]  type='button' onclick='take(\"43\");'></td>
	 <td><input name='btn' class=\"$class[44]\" value=$riquadro[44]  type='button' onclick='take(\"44\");'></td>
	 <td><input name='btn' class=\"$class[45]\" value=$riquadro[45]  type='button' onclick='take(\"45\");'></td>
	 <td><input name='btn' class=\"$class[46]\" value=$riquadro[46]  type='button' onclick='take(\"46\");'></td>
	 <td><input name='btn' class=\"$class[47]\" value=$riquadro[47]  type='button' onclick='take(\"47\");'></td>
	 <td><input name='btn' class=\"$class[48]\" value=$riquadro[48]  type='button' onclick='take(\"48\");'></td>
	 <td><input name='btn' class=\"$class[49]\" value=$riquadro[49]  type='button' onclick='take(\"49\");'></td>
	 <td><input name='btn' class=\"$class[50]\" value=$riquadro[50]  type='button' onclick='take(\"50\");'></td>
	 
	<td width='100'>$contrasti %</td>

	 </tr>
	 ";
	/* echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_cn_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_contrasti_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo" 
	
	
	 <tr>
	 <td>Passaggi</td>
	 <td><input name='btn' class=\"$class[51]\" value=$riquadro[51]  type='button' onclick='take(\"51\");'></td>
	 <td><input name='btn' class=\"$class[52]\" value=$riquadro[52]  type='button' onclick='take(\"52\");'></td>
	 <td><input name='btn' class=\"$class[53]\" value=$riquadro[53]  type='button' onclick='take(\"53\");'></td>
	 <td><input name='btn' class=\"$class[54]\" value=$riquadro[54]  type='button' onclick='take(\"54\");'></td>
	 <td><input name='btn' class=\"$class[55]\" value=$riquadro[55]  type='button' onclick='take(\"55\");'></td>
	 <td><input name='btn' class=\"$class[56]\" value=$riquadro[56]  type='button' onclick='take(\"56\");'></td>
	 <td><input name='btn' class=\"$class[57]\" value=$riquadro[57]  type='button' onclick='take(\"57\");'></td>
	 <td><input name='btn' class=\"$class[58]\" value=$riquadro[58]  type='button' onclick='take(\"58\");'></td>
	 <td><input name='btn' class=\"$class[59]\" value=$riquadro[59]  type='button' onclick='take(\"59\");'></td>
	 <td><input name='btn' class=\"$class[60]\" value=$riquadro[60]  type='button' onclick='take(\"60\");'></td>
	 
	<td width='100'>$passaggi %</td>

	 </tr>
	 ";
	 /*echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_pa_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_passaggi_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo" 

	
	
	 <tr>
	 <td>Regia</td>
	 <td><input name='btn' class=\"$class[61]\" value=$riquadro[61]  type='button' onclick='take(\"61\");'></td>
	 <td><input name='btn' class=\"$class[62]\" value=$riquadro[62]  type='button' onclick='take(\"62\");'></td>
	 <td><input name='btn' class=\"$class[63]\" value=$riquadro[63]  type='button' onclick='take(\"63\");'></td>
	 <td><input name='btn' class=\"$class[64]\" value=$riquadro[64]  type='button' onclick='take(\"64\");'></td>
	 <td><input name='btn' class=\"$class[65]\" value=$riquadro[65]  type='button' onclick='take(\"65\");'></td>
	 <td><input name='btn' class=\"$class[66]\" value=$riquadro[66]  type='button' onclick='take(\"66\");'></td>
	 <td><input name='btn' class=\"$class[67]\" value=$riquadro[67]  type='button' onclick='take(\"67\");'></td>
	 <td><input name='btn' class=\"$class[68]\" value=$riquadro[68]  type='button' onclick='take(\"68\");'></td>
	 <td><input name='btn' class=\"$class[69]\" value=$riquadro[69]  type='button' onclick='take(\"69\");'></td>
	 <td><input name='btn' class=\"$class[70]\" value=$riquadro[70]  type='button' onclick='take(\"70\");'></td>
	 
	<td width='100'>$regia %</td>

	 </tr>
	";
	 /*echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_rg_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_regia_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo"
	
	
	 <tr>
	 <td>Cross</td>
	 <td><input name='btn' class=\"$class[71]\" value=$riquadro[71]  type='button' onclick='take(\"71\");'></td>
	 <td><input name='btn' class=\"$class[72]\" value=$riquadro[72]  type='button' onclick='take(\"72\");'></td>
	 <td><input name='btn' class=\"$class[73]\" value=$riquadro[73]  type='button' onclick='take(\"73\");'></td>
	 <td><input name='btn' class=\"$class[74]\" value=$riquadro[74]  type='button' onclick='take(\"74\");'></td>
	 <td><input name='btn' class=\"$class[75]\" value=$riquadro[75]  type='button' onclick='take(\"75\");'></td>
	 <td><input name='btn' class=\"$class[76]\" value=$riquadro[76]  type='button' onclick='take(\"76\");'></td>
	 <td><input name='btn' class=\"$class[77]\" value=$riquadro[77]  type='button' onclick='take(\"77\");'></td>
	 <td><input name='btn' class=\"$class[78]\" value=$riquadro[78]  type='button' onclick='take(\"78\");'></td>
	 <td><input name='btn' class=\"$class[79]\" value=$riquadro[79]  type='button' onclick='take(\"79\");'></td>
	 <td><input name='btn' class=\"$class[80]\" value=$riquadro[80]  type='button' onclick='take(\"80\");'></td>
	 
	<td width='100'>$cross %</td>

	 </tr>
	  ";
	 /*echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_cr_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_cross_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo"
	
	
	
	 <tr>
	 <td>Tecnica</td>
	 <td><input name='btn' class=\"$class[81]\" value=$riquadro[81]  type='button' onclick='take(\"81\");'></td>
	 <td><input name='btn' class=\"$class[82]\" value=$riquadro[82]  type='button' onclick='take(\"82\");'></td>
	 <td><input name='btn' class=\"$class[83]\" value=$riquadro[83]  type='button' onclick='take(\"83\");'></td>
	 <td><input name='btn' class=\"$class[84]\" value=$riquadro[84]  type='button' onclick='take(\"84\");'></td>
	 <td><input name='btn' class=\"$class[85]\" value=$riquadro[85]  type='button' onclick='take(\"85\");'></td>
	 <td><input name='btn' class=\"$class[86]\" value=$riquadro[86]  type='button' onclick='take(\"86\");'></td>
	 <td><input name='btn' class=\"$class[87]\" value=$riquadro[87]  type='button' onclick='take(\"87\");'></td>
	 <td><input name='btn' class=\"$class[88]\" value=$riquadro[88]  type='button' onclick='take(\"88\");'></td>
	 <td><input name='btn' class=\"$class[89]\" value=$riquadro[89]  type='button' onclick='take(\"89\");'></td>
	 <td><input name='btn' class=\"$class[90]\" value=$riquadro[90]  type='button' onclick='take(\"90\");'></td>
	 
	<td width='100'>$tecnica %</td>

	 </tr>
	 ";
	 /*echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_tc_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_tecnica_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 echo" 
	
	
	
	 <tr>
	 <td>Tiro</td>
	 <td><input name='btn' class=\"$class[91]\" value=$riquadro[91]  type='button' onclick='take(\"91\");'></td>
	 <td><input name='btn' class=\"$class[92]\" value=$riquadro[92]  type='button' onclick='take(\"92\");'></td>
	 <td><input name='btn' class=\"$class[93]\" value=$riquadro[93]  type='button' onclick='take(\"93\");'></td>
	 <td><input name='btn' class=\"$class[94]\" value=$riquadro[94]  type='button' onclick='take(\"94\");'></td>
	 <td><input name='btn' class=\"$class[95]\" value=$riquadro[95]  type='button' onclick='take(\"95\");'></td>
	 <td><input name='btn' class=\"$class[96]\" value=$riquadro[96]  type='button' onclick='take(\"96\");'></td>
	 <td><input name='btn' class=\"$class[97]\" value=$riquadro[97]  type='button' onclick='take(\"97\");'></td>
	 <td><input name='btn' class=\"$class[98]\" value=$riquadro[98]  type='button' onclick='take(\"98\");'></td>
	 <td><input name='btn' class=\"$class[99]\" value=$riquadro[99]  type='button' onclick='take(\"99\");'></td>
	 <td><input name='btn' class=\"$class[100]\" value=$riquadro[100]  type='button' onclick='take(\"100\");'></td>
	 
	<td width='100'>$tiro %</td>
	
	</tr>
	  ";
	 /*echo "<tr><td height='6'></td><td colspan='10'>";
		$corri = $row['sk_tr_val'] ;
		if ($corri >326) { $corri = 326; }
		for ($qw=0; $qw <= 326 ; $qw++)
		{
			$nome = "img_tiro_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immvuota ; }
			
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		
	 echo "</td></tr>";*/
	 
	 //echo "<td colspan='16'>&nbsp;</td>";
	 
	 // NON TOCCARE QUESTI DATI !!!!
	 echo"</table>";
	 if ($maxbox > 15)
		{
			echo "<h5>ERRORE:<br> Non puoi utilizzare più di 15 coupon alla settimana!</h5>";
		}
		

	
// non mettere /form prima !!!!!
?>

<div style="display:none ; border: 1px dashed black;"> <!-- turn it into display: none;-->
<table border="0" cellpadding="0" cellspacing="0" width="50%">

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
 </tr>
 <tr align="center" valign="middle">
 <td><input name="group" value="11"  type="radio"></td>
 <td><input name="group" value="12"  type="radio"></td>
 <td><input name="group" value="13"  type="radio"></td>
 <td><input name="group" value="14"  type="radio"></td>
 <td><input name="group" value="15"  type="radio"></td>
 <td><input name="group" value="16"  type="radio"></td>
 <td><input name="group" value="17"  type="radio"></td>
 <td><input name="group" value="18"  type="radio"></td>
 <td><input name="group" value="19"  type="radio"></td>
 <td><input name="group" value="20" type="radio"></td>
 </tr>
  <tr align="center" valign="middle">
 <td><input name="group" value="21"  type="radio"></td>
 <td><input name="group" value="22"  type="radio"></td>
 <td><input name="group" value="23"  type="radio"></td>
 <td><input name="group" value="24"  type="radio"></td>
 <td><input name="group" value="25"  type="radio"></td>
 <td><input name="group" value="26"  type="radio"></td>
 <td><input name="group" value="27"  type="radio"></td>
 <td><input name="group" value="28"  type="radio"></td>
 <td><input name="group" value="29"  type="radio"></td>
 <td><input name="group" value="30" type="radio"></td>
 </tr>
  <tr align="center" valign="middle">
 <td><input name="group" value="31"  type="radio"></td>
 <td><input name="group" value="32"  type="radio"></td>
 <td><input name="group" value="33"  type="radio"></td>
 <td><input name="group" value="34"  type="radio"></td>
 <td><input name="group" value="35"  type="radio"></td>
 <td><input name="group" value="36"  type="radio"></td>
 <td><input name="group" value="37"  type="radio"></td>
 <td><input name="group" value="38"  type="radio"></td>
 <td><input name="group" value="39"  type="radio"></td>
 <td><input name="group" value="40" type="radio"></td>
 </tr>
 
  <tr align="center" valign="middle">
 <td><input name="group" value="41"  type="radio"></td>
 <td><input name="group" value="42"  type="radio"></td>
 <td><input name="group" value="43"  type="radio"></td>
 <td><input name="group" value="44"  type="radio"></td>
 <td><input name="group" value="45"  type="radio"></td>
 <td><input name="group" value="46"  type="radio"></td>
 <td><input name="group" value="47"  type="radio"></td>
 <td><input name="group" value="48"  type="radio"></td>
 <td><input name="group" value="49"  type="radio"></td>
 <td><input name="group" value="50" type="radio"></td>
 </tr>
   <tr align="center" valign="middle">
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
 </tr>
  <tr align="center" valign="middle">
 <td><input name="group" value="61"  type="radio"></td>
 <td><input name="group" value="62"  type="radio"></td>
 <td><input name="group" value="63"  type="radio"></td>
 <td><input name="group" value="64"  type="radio"></td>
 <td><input name="group" value="65"  type="radio"></td>
 <td><input name="group" value="66"  type="radio"></td>
 <td><input name="group" value="67"  type="radio"></td>
 <td><input name="group" value="68"  type="radio"></td>
 <td><input name="group" value="69"  type="radio"></td>
 <td><input name="group" value="70" type="radio"></td>
 </tr>
  <tr align="center" valign="middle">
 <td><input name="group" value="71"  type="radio"></td>
 <td><input name="group" value="72"  type="radio"></td>
 <td><input name="group" value="73"  type="radio"></td>
 <td><input name="group" value="74"  type="radio"></td>
 <td><input name="group" value="75"  type="radio"></td>
 <td><input name="group" value="76"  type="radio"></td>
 <td><input name="group" value="77"  type="radio"></td>
 <td><input name="group" value="78"  type="radio"></td>
 <td><input name="group" value="79"  type="radio"></td>
 <td><input name="group" value="80" type="radio"></td>
 </tr>
  <tr align="center" valign="middle">
 <td><input name="group" value="81"  type="radio"></td>
 <td><input name="group" value="82"  type="radio"></td>
 <td><input name="group" value="83"  type="radio"></td>
 <td><input name="group" value="84"  type="radio"></td>
 <td><input name="group" value="85"  type="radio"></td>
 <td><input name="group" value="86"  type="radio"></td>
 <td><input name="group" value="87"  type="radio"></td>
 <td><input name="group" value="88"  type="radio"></td>
 <td><input name="group" value="89"  type="radio"></td>
 <td><input name="group" value="90" type="radio"></td>
 </tr>
  <tr align="center" valign="middle">
 <td><input name="group" value="91"  type="radio"></td>
 <td><input name="group" value="92"  type="radio"></td>
 <td><input name="group" value="93"  type="radio"></td>
 <td><input name="group" value="94"  type="radio"></td>
 <td><input name="group" value="95"  type="radio"></td>
 <td><input name="group" value="96"  type="radio"></td>
 <td><input name="group" value="97"  type="radio"></td>
 <td><input name="group" value="98"  type="radio"></td>
 <td><input name="group" value="99"  type="radio"></td>
 <td><input name="group" value="100" type="radio"></td>
 </tr>
 

 </table>
</div>



</form>




</body>
</html>
