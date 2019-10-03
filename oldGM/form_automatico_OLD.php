<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title> Golden Manager - Calcolatore Automatico Formazione </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="Content-Style-Type" content="text/css;">
	<meta name="Author" content="Sergio Casizzone">
	<meta name="Keywords" content="goal united goalunited analysis team">
	<meta name="Description" content="Analisi e statistiche per giocare a Goalunited. by Sergio Casizzone.">
</head>
<?php
	$height = $_SESSION['SESS_ALTEZZA'];
	$width = $_SESSION['SESS_LARGHEZZA'];
		
		if ($height <=600 )
		{
			$altezzasx = 1200;
			$altezzadx = 650;
			$larg_sx = 600;
			$larg_dx = 620;
			$stile_sx = "float:left; width:350px; height:300px; overflow: scroll; ";
			$stile_dx = "float:right; width:365px; height:300px; overflow-x: scroll; ";
		}
		elseif ($height > 600 and $height <= 720)
		{
			$altezzasx = 1200;
			$altezzadx = 650;
			$larg_sx = 650;
			$larg_dx = 620;
			$stile_sx = "float:left; width:640px; height:380px; overflow-y: scroll; ";
			$stile_dx = "float:right; width:500px; height:380px; ";		

		}
		elseif ($height > 720 and $height <= 768)
		{
			$altezzasx = 1200;
			$altezzadx = 420;
			$larg_sx = 380;
			$larg_dx = 610;
			$stile_sx = "float:left; width:400px; height:420px; overflow-y: scroll; ";
			$stile_dx = "float:left; width:570px; height:420px; ";		
		}
		elseif ($height > 768 and $height <= 800)
		{
			$altezza = 650;
			$altezzasx = 1800;
			$altezzadx = 650;
			$larg_sx = 470;
			$larg_dx = 770;
			$stile_sx = "float:left; width:490px; height:445px; overflow-y: scroll; ";
			$stile_dx = "float:left; width:600px; height:445px; ";		
		}
		elseif ($height > 800 and $height <= 864)
		{
			$altezzasx = 1800;
			$altezzadx = 650;
			$larg_sx = 470;
			$larg_dx = 570;
			$stile_sx = "float:left; width:490px; height:445px; overflow-y: scroll; ";
			$stile_dx = "float:left; width:490px; height:445px; ";		
		}
		elseif ($height > 864 and $height <= 900)
		{
			$altezza = 650;
			$larg_sx = 620;
			$larg_dx = 620;
			$stile_sx = "float:left; width:640px; height:500px; overflow-y: scroll; ";
			$stile_dx = "float:right; width:500px; height:500px;  ";		
		}
		elseif ($height > 900 and $height <= 960)
		{
			$altezza = 650;
			$larg_sx = 620;
			$larg_dx = 620;
			$stile_sx = "float:left; width:640px; height:560px; overflow-y: scroll; ";
			$stile_dx = "float:right; width:500px; height:560px; ";			
		}
		elseif ($height > 960 and $height <= 1024)
		{
			$altezzasx = 1024;
			$altezzadx = 650;
			$larg_sx = 480;
			$larg_dx = 600;
			$stile_sx = "float:left; width:500px; height:620px; overflow-y: scroll; ";
			$stile_dx = "float:left; width:640px; height:620px; ";		
		}
		elseif ($height > 1024 and $height <= 1050)
		{
			$altezzasx = 1024;
			$altezzadx = 650;
			$larg_sx = 470;
			$larg_dx = 790;
			$stile_sx = "float:left; width:490px; height:620px; overflow-y: scroll; ";
			$stile_dx = "float:left; width:660px; height:620px; ";	
			//ok	
		}
		elseif ($height > 1050 )
		{
			$altezza = 650;
			$larg_sx = 620;
			$larg_dx = 620;
			$stile_sx = "float:left; width:680px; height:670px; ";
			$stile_dx = "float:left; width:500px; height:670px; ";			
		}
	// identificativo numerico della pagina
	$pagina = 1970;

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
?>

<body>
	<div>
		<?php include("m-intestazione.php")?>
	</div>
	<div id='intest_corpo'>
		<?php include("team_automatico.php")?>
	</div>
</body>
</html>
