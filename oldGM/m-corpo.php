<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	$quale_css = "css/index".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
</head>

<?php
	$nome_team = $_SESSION['SESS_TEAM'];
	$nome_utente = $_SESSION['SESS_USER'];
	$serie = $_SESSION['SESS_SERIE'];
		
	$qry = "SELECT * FROM  zz_config WHERE id=1";
	$result = mysql_query($qry);
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
    	exit();
	}
	$row   =   mysql_fetch_array($result);
	//$config_data = $row['data'];
	$config_giorno = $row['giorno'];
	//$config_orario = $row['orario'];
	//$config_squadre= $row['squadre'];
	
	$oggi = time();
		
	$g1 = date("j",$oggi);
	$m1 = date("m",$oggi);
	$a1 = date("Y",$oggi);
	
	for ($x=0; $x <=7 ; $x++)
	{
		$nextdate  = mktime (0,0,0,$m1,  $g1+$x,  $a1);
		
		if (date("w",$nextdate) == $config_giorno)
		{
			$g1 = date("j",$nextdate);
			$m1 = date("m",$nextdate);
			$a1 = date("Y",$nextdate);
			
			$dataIta = $g1."/".$m1."/".$a1;
			$dataSql = $a1."-".$m1."-".$g1;
			break;
		}
	}
	
	$qry = "SELECT * FROM z_calendario WHERE serie='$serie' AND data>='$dataSql' AND giocata=0";
	$qry = $qry." ORDER BY data";
	
	$result = mysql_query($qry);
	
	while ($row = mysql_fetch_array($result))
	{
		if ($row['casa'] == $nome_team or $row['fuori'] == $nome_team)
		{
			break;
		}
	}

	$id_partita = $row['id_partita'];
	$dataSql = $row['data'];
	$ora = substr($row['ora_inizio'],0,-3);
	$casa= $row['casa'];
	$fuori= $row['fuori'];
	$giocata= $row['giocata'];
	
	$data = strtotime($dataSql);
	$g1 = date("j",$data); // Giorno
	$m1 = date("m",$data); // Mese
	$a1 = date("Y",$data); // Anno
	$dataIta = $g1."/".$m1."/".$a1;
	
	if ($casa == $nome_team)
	{
		$qry = "SELECT * FROM members WHERE team=\"$fuori\"";
		$result = mysql_query($qry);
		$row = mysql_fetch_array($result);
		
		$my_logos = $_SESSION['SESS_SX'];
		$my_logod = $_SESSION['SESS_DX'];
		$av_logos = $row['logos'];
		$av_logod = $row['logod'];
	}
	else
	{
		$qry = "SELECT * FROM members WHERE team=\"$casa\"";
		
		$result = mysql_query($qry);
		$row = mysql_fetch_array($result);
		
		$av_logos = $_SESSION['SESS_SX'];
		$av_logod = $_SESSION['SESS_DX'];
		$my_logos = $row['logos'];
		$my_logod = $row['logod'];
	}
	$my_immsx = 'images/scudo_sx'.$my_logos.'.png';
	$my_immdx = 'images/scudo_dx'.$my_logod.'.png';
	$av_immsx = 'images/scudo_sx'.$av_logos.'.png';
	$av_immdx = 'images/scudo_dx'.$av_logod.'.png';
?>
	
<body>
<h1><font style="font-weight:bold; color:#00FFFF; font-size:18px; font-family:Verdana, Arial, Helvetica, sans-serif;">Sede</font></h1>


<div id="corpo">
<div style="float:left;">

<span class="top-label">  
	<span class="label-txt">Prossimo Incontro</span>
</span> 
<div class="content-area"> 
	<img id="sede_prossimo" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="sede_prossimo">
		<fieldset id='sede_prossimo'>
		<table border="0" align="center" cellspacing="5">
			
			<tr>
				<td align="center">			
				<?php 
						echo "<img src='$my_immsx' width='45' /><img src='$my_immdx' width='45' />"; 
					?>
					<br />
					<span id='sede_casa'><?php echo $casa; ?></span>
				</td>
				<td align="center">
					<span id='sede_orario'><?php echo $dataIta."<br />".$ora; ?></span>
				</td>
				<td align="center">
					<?php 
						echo "<img src='$av_immsx' width='45' /><img src='$av_immdx' width='45' />"; 
					?>
					<br />	
					<span id='sede_fuori'><?php echo $fuori; ?></span>
				</td>
			</tr>
		</table>
		</fieldset>
	</span>
</div>
<br />
<span class="top-label">  
	<span class="label-txt">Notizie dalla dirigenza</span>
</span> 
<div class="content-area"> 
	<img id="sede_notizie" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="sede_notizie">
		<fieldset id='sede_notizie'>
			<div id='sede_notizie' >
				<?php
					$num = 0;
					$qry = "SELECT * FROM notiziario WHERE team=\"$nome_team\" ORDER BY id DESC";
					$res = mysql_query($qry);
					$num = mysql_num_rows($res);
	
					if ($num == 0)
					{	
						$oggi = time();
		
						$g1 = date("j",$oggi);
						$m1 = date("m",$oggi);
						$a1 = date("Y",$oggi);
						$dataSql = $a1."-".$m1."-".$g1;
	
						$msg = "Benvenuto $nome_utente. Ora sei il nuovo manager della squadra $nome_team.<br>Ricordati che la Dirigenza ha puntato tutto su di te per portare la squadra nella massima serie.<br>Ti è stato affidato un budget di €. 250.000 da far fruttare e 18 giocatori che dovrai allenare al meglio delle tue possibilità.<br>Ora è tutto nelle tue mani. In bocca al lupo.";
						
						$qry = "INSERT INTO notiziario (team, data, notizia) VALUES (\"$nome_team\", '$dataSql', \"$msg\")";
						mysql_query($qry);
					}
					$qry = "SELECT * FROM notiziario WHERE team=\"$nome_team\" ORDER BY id DESC";
					$res = mysql_query($qry);
					while ($row   =   mysql_fetch_array($res))
					{
						$riga = $row['notizia'];
						$dataSql = $row['data'];
						
						$dataIta = strtotime($dataSql);
						$g1 = date("j",$dataIta); // Giorno
						$m1 = date("m",$dataIta); // Mese
						$a1 = date("Y",$dataIta); // Anno
						$dataIta = $g1."/".$m1."/".$a1;

						echo "<font color='#000000'><b>$dataIta</b></font><br>";
						echo "$riga<br><br>";
					}

								
				?>
			</div>
		</fieldset>
	</span>
</div>
</div>

<div style="float:right;">
<span class="top-label">  
	<span class="label-txt">Notizie dalla Serie</span>
</span> 
<div class="content-area"> 
	<img id="sede_serie" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="sede_serie">
		<fieldset id='sede_serie'>
			<div id='sede_serie' >
				<?php
					$num = 0;
					$qry = "SELECT * FROM notizie_serie WHERE g_serie=\"$serie\" ORDER BY g_id DESC";
					$res = mysql_query($qry);
	
					while ($row   =   mysql_fetch_array($res))
					{
						$dachi = $row["g_team"];
						$titolo = $row["g_titolo"];
						$riga = $row["g_testo"];
						$dataSql = $row['g_data'];
						
						$dataIta = strtotime($dataSql);
						$g1 = date("j",$dataIta); // Giorno
						$m1 = date("m",$dataIta); // Mese
						$a1 = date("Y",$dataIta); // Anno
						$dataIta = $g1."/".$m1."/".$a1;

						echo "<font color='#000000'><b>$dataIta</b></font>";
						echo " <font color='#006600'><b>$dachi</b></font> ha dichiarato:";
						echo "<br><font color='#ffffff'>$titolo</font>";
						echo "<br>$riga<br><br>";
					}
								
				?>
			</div>
		</fieldset>
	</span>
</div>
</div>
</div>
<script>
	$('#corpo').slideUp(0).delay(300).fadeIn(600);
</script>
	
	
</body>
</html>
