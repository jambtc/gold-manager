<?php
	session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<LINK REL="SHORTCUT ICON" HREF="images/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title> Gold Manager - Gioco di calcio manageriale </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Content-Style-Type" content="text/css;">
<meta name="Author" content="Sergio Casizzone">
<meta name="Keywords" content="gold manager gioco calcio manager manageriale">
<meta name="Description" content="Gioco di calcio manageriale gratis. Ideato da Sergio Casizzone.">
<meta name="google-site-verification" content="4wrLOs_xibPALt3c2jTECfpEZkD-zYfXDTBkjbLz8BI" />

<style type='text/css'> @import 'css/index1024.css'; </style>

<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<script type="text/javascript" src="jquery/my_script.js"></script>
<script type="text/javascript" src="jquery/footer.js"></script>
</head>
<?php
	$cerca_browser[] = "symbian";
	$cerca_browser[] = "symbos";
	$cerca_browser[] = "symb";
	$cerca_browser[] = "linux";
	$cerca_browser[] = "msie";
	$cerca_browser[] = "firefox";
	$cerca_browser[] = "chrome";
	$cerca_browser[] = "safari";
	$cerca_browser[] = "presto";
	
	
	$browser = strtolower($_SERVER['HTTP_USER_AGENT']);
	
	foreach ($cerca_browser as $cisei)
	{
		$pos = strpos($browser, $cisei);
		if ($pos)
		{
			switch ($cisei)
			{
				case "symbian":
				case "symbos":
				case "symb":
					// vai a mobi-index.php
					header("location: mobi-index.php");
					break;
				/*case "presto":
				case "safari":
				case "chrome":
					// vai a chrome-index
					//header("location: chro-index.php");
					echo "I Browser Safari, Opera e Chrome, al momento non sono supportati. Si prega di utilizzare Firefox oppure Explorer";
					exit;
					break;*/
				default:
					break;
			}
		}
	}
	//$larghezza = $_GET['larghezza'];
	//$altezza = $_GET['altezza'];
	// identificativo numerico della pagina
	$pagina = 1;
	
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
	
	<body onload="javascript:AllargaSchermo();">
	<div class="contenitore_principale">
		<div>
			<?php include("intestazione.php")?>
		</div>
		<div id='intest_corpo'>
			<?php 
				if( isset($_SESSION['SESS_USER']) )
				{
					unset($_SESSION['SESS_USER']);
					echo "E' stata trovata una sessione già aperta. Per ragioni di sicurezza devi effettuare un nuovo LOGIN!";
					exit;
				} else {
					include("corpo.php");
				}
			?>
		</div>
	</div>
	</body>
</html>

