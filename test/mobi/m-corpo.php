<?php
	require_once('../connect_db.php');
	
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
	
?>
	
<h3>Prossimo incontro</h3>
<table width="100%" border="0" align="center" cellspacing="5" bgcolor="#CCCCCC">
<tr>
	<td align="center">			
		<span id='sede_casa'><?php echo $casa; ?></span>
	</td>
	<td align="center">
		<span id='sede_orario'><?php echo $dataIta."<br />".$ora; ?></span>
	</td>
	<td align="center">
		<span id='sede_fuori'><?php echo $fuori; ?></span>
	</td>
</tr>
</table>
		
