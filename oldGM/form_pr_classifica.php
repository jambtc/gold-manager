<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title> Gold Manager - Classifica Primavera </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Content-Style-Type" content="text/css;">
<meta name="Author" content="Sergio Casizzone">
<meta name="Keywords" content="gold manager gioco calcio manager manageriale">
<meta name="Description" content="Gioco di calcio manageriale gratis. Ideato da Sergio Casizzone.">
</head>
<?php
// identificativo numerico della pagina
$pagina = 44;

	// connessione al database MySQL
	include "connect_db.php";	
	
	// numero di visite attuali
	$Oggi = time();
	$g1 = date("j",$Oggi);
	$m1 = date("m",$Oggi);
	$a1 = date("Y",$Oggi);
	$dataIta = $g1."/".$m1."/".$a1;
	$separatore = "/";
	// data in formato sql  
	$split_data = explode($separatore, $dataIta);
	$datasql = $split_data[2] . "-" . $split_data[1] . "-" . $split_data[0]; 
	$ip = getenv("REMOTE_ADDR"); // get the ip number of the user
	
	$verif = mysql_query("SELECT * FROM contatore WHERE pagina = $pagina");
	$tcpip = mysql_query("SELECT * FROM stat_tcp WHERE tcp='$ip' AND data='$datasql' AND pagina='$pagina'");
	$num = mysql_num_rows($verif);
	$ripeti = mysql_num_rows($tcpip);
	
	if ($ripeti == 0)
	{
		mysql_query("INSERT INTO stat_tcp (data, tcp, pagina) VALUES ('$datasql', '$ip', '$pagina')");
		if ($num == 0)
		{ 
			// pagina non presente nel database
			// aggiungo la pagina nella tabella
			mysql_query("INSERT INTO contatore (pagina, visite) VALUES ($pagina, 1)");
		}
		else
		{
			$res = mysql_query("UPDATE contatore SET visite = visite + 1 WHERE pagina = $pagina"); 
		}
	} 
	else
	{
		$res = mysql_query("UPDATE contatore SET totali = totali + 1 WHERE pagina = $pagina");
	}
?>

<body>
	<div>
		<?php include("m-intestazione.php")?>
	</div>
	<div id='intest_corpo'>
		<?php include("wip.php")?>
	</div>
</body>
</html>
