<?php
	require_once('auth.php');

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
	$config_giorno = $row['giorno'];
	
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
		$qry = "SELECT * FROM z_iscritti WHERE squadra=\"$fuori\"";
		$result = mysql_query($qry);
		$row = mysql_fetch_array($result);
		
		$my_logo_top = $_SESSION['SESS_LOGO_TOP'];
		$my_logo_middle = $_SESSION['SESS_LOGO_MIDDLE'];
		$my_logo_bottom_left = $_SESSION['SESS_LOGO_BOTTOM_LEFT'];
		$my_logo_bottom_center = $_SESSION['SESS_LOGO_BOTTOM_CENTER'];
		$my_logo_bottom_right = $_SESSION['SESS_LOGO_BOTTOM_RIGHT'];
		
		$av_logo_top = $row['logo_top'];
		$av_logo_middle = $row['logo_middle'];
		$av_logo_bottom_left = $row['logo_bottom_left'];
		$av_logo_bottom_center = $row['logo_bottom_center'];
		$av_logo_bottom_right = $row['logo_bottom_right'];
		
	}
	else
	{
		$qry = "SELECT * FROM z_iscritti WHERE squadra=\"$casa\"";
		
		$result = mysql_query($qry);
		$row = mysql_fetch_array($result);
		
		$av_logo_top = $_SESSION['SESS_LOGO_TOP'];
		$av_logo_middle = $_SESSION['SESS_LOGO_MIDDLE'];
		$av_logo_bottom_left = $_SESSION['SESS_LOGO_BOTTOM_LEFT'];
		$av_logo_bottom_center = $_SESSION['SESS_LOGO_BOTTOM_CENTER'];
		$av_logo_bottom_right = $_SESSION['SESS_LOGO_BOTTOM_RIGHT'];
		
		$my_logo_top = $row['logo_top'];
		$my_logo_middle = $row['logo_middle'];
		$my_logo_bottom_left = $row['logo_bottom_left'];
		$my_logo_bottom_center = $row['logo_bottom_center'];
		$my_logo_bottom_right = $row['logo_bottom_right'];
	}
	$my_imm_top = 'images/scudo/scudo_top_'.$my_logo_top.'.png';
	$my_imm_middle = 'images/scudo/scudo_middle_'.$my_logo_middle.'.png';
	$my_imm_bottom_left = 'images/scudo/scudo_bottom_left_'.$my_logo_bottom_left.'.png';
	$my_imm_bottom_center = 'images/scudo/scudo_bottom_center_'.$my_logo_bottom_center.'.png';
	$my_imm_bottom_right = 'images/scudo/scudo_bottom_right_'.$my_logo_bottom_right.'.png';
	
	$av_imm_top = 'images/scudo/scudo_top_'.$av_logo_top.'.png';
	$av_imm_middle = 'images/scudo/scudo_middle_'.$av_logo_middle.'.png';
	$av_imm_bottom_left = 'images/scudo/scudo_bottom_left_'.$av_logo_bottom_left.'.png';
	$av_imm_bottom_center = 'images/scudo/scudo_bottom_center_'.$av_logo_bottom_center.'.png';
	$av_imm_bottom_right = 'images/scudo/scudo_bottom_right_'.$av_logo_bottom_right.'.png';
?>
<h1 class="h1">Sede</h1>

<div id="sede_left" >
		<div class='div_prossimo'>	
			<span class="top-label">  
				<span class="label-txt">Prossimo Incontro</span>
			</span> 			
			<img class='img_prossimo' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_prossimo'>
				<fieldset>
					<table border="0" align="center" cellspacing="5">
						<tr>
							<td align="center">			
								<div>
									<?php echo "<img src='images/scudo/scudo_base.png' border='0' width='177'  />"; ?>
									<div class='my_login_scudo_top'>
										<?php echo "<img src='$my_imm_top' border='0' width='136'  />"; ?>
									</div>
									<div class='my_login_scudo_middle'>
										<?php echo "<img src='$my_imm_middle' border='0' width='141'  />"; ?>
									</div>
									<div class='my_login_scudo_bottom_left'>
										<?php echo "<img src='$my_imm_bottom_left' border='0' width='32'  />"; ?>
									</div>
									<div class='my_login_scudo_bottom_center'>
										<?php echo "<img src='$my_imm_bottom_center' border='0' width='115'  />"; ?>
									</div>
									<div class='my_login_scudo_bottom_right'>
										<?php echo "<img src='$my_imm_bottom_right' border='0' width='90'  />"; ?>
									</div>
								</div>
							</td>
							<td align="center">
								<span id='sede_orario'><?php echo $dataIta."<br />".$ora; ?></span>
							</td>
							<td align="center">
								<div >
									<?php echo "<img src='images/scudo/scudo_base.png' border='0' width='177'  />"; ?>
									<div class='my_login_scudo_top'>
										<?php echo "<img src='$av_imm_top' border='0' width='136'  />"; ?>
									</div>
									<div class='my_login_scudo_middle'>
										<?php echo "<img src='$av_imm_middle' border='0' width='141'  />"; ?>
									</div>
									<div class='my_login_scudo_bottom_left'>
										<?php echo "<img src='$av_imm_bottom_left' border='0' width='32'  />"; ?>
									</div>
									<div class='my_login_scudo_bottom_center'>
										<?php echo "<img src='$av_imm_bottom_center' border='0' width='115'  />"; ?>
									</div>
									<div class='my_login_scudo_bottom_right'>
										<?php echo "<img src='$av_imm_bottom_right' border='0' width='90'  />"; ?>
									</div>
								</div>
							</td>
						</tr>
						<tr><td colspan="3"><hr /></td></tr>
						<tr>
							<td align="right">			
								<span id='sede_casa'><?php echo $casa; ?></span>
							</td>
							<td align="center"><span id='sede_orario'>vs</span>
							</td>
							<td align="left">
								<span id='sede_fuori'><?php echo $fuori; ?></span>
							</td>
						</tr>
					</table>
				</fieldset>
			</div>
		</div>	
		<div class='div_dirigenza'>	
			<span class="top-label">  
				<span class="label-txt">Notizie dalla dirigenza</span>
			</span> 			
			<img class='img_dirigenza' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_dirigenza'>
				<fieldset>
				<div class='scr_dirigenza' >
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
							echo "<b>$riga</b><br><br>";
						}
					?>
				</div>
				</fieldset>
			</div>
		</div>
</div>
<div id="sede_right">
		<div class='div_serie'>	
			<span class="top-label">  
				<span class="label-txt">Notizie dalla Serie</span>
			</span> 
			<img class='img_serie' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_serie'>
				<fieldset>
					<div class='scr_serie' >
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
		
								echo "<font color='#000000'><b>$dataIta</b></font></br>";
								//echo "<font color='#006600'><b>Ecco l'ultimo comunicato stampa di $dachi</b></font></br>";
								echo "<b><font color='#006600'>$titolo</font></b>";
								echo "<br><b>$riga</b><br><br>";
							}
						?>
					</div>
				</fieldset>
			</div>
		</div>
		<div class='div_online'>	
			<span class="top-label">  
				<span class="label-txt">Chatta con gli utenti</span>
			</span> 
			<img class='img_online' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_online'>
				<fieldset>
					<div class='scr_online' >
						<?php	include ("m-useronline.php")?>
					</div>
				</fieldset>
			</div>
		</div>
</div>




<script>
	allarga_maschera('prossimo',40,ValAlt('prossimo')+10);
	allarga_maskwscroll('dirigenza',40,ValAlt('prossimo')+10);
	allarga_maskwscroll('serie',45,ValAlt('prossimo')+10);
	allarga_msk_frame('online',45,ValAlt('prossimo')+10);
	
</script>
