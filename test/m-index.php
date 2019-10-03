<?php
	require_once('auth.php');
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

<style type='text/css'> @import 'demo.css'; </style>

<link rel="stylesheet" type="text/css" href="dashboard.css" media="all" />
<link rel="stylesheet" type="text/css" href="theme.default/default.css" media="all" />

<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script>
<script type="text/javascript" src="jquery/my_screen.js"></script>
<script type="text/javascript" src="jquery/menu.js"></script>
<script type="text/javascript" src="jquery/my_script.js"></script>
<script type="text/javascript" src="jquery/footer.js"></script>

 
</head>
<?php
// identificativo numerico della pagina
	$pagina = 10;
	if (isset($_REQUEST['pg']))
	{
		$pagina = $_REQUEST['pg'];
	}

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

<body onload="javascript:allarga_schermo();navMenu();">
	<div id="main-container" class="contenitore_principale" >
		<div id="header" >
			<?php include("m-intestazione.php")?>
		</div>
		<div id="corpo_interno" class="corpo_interno">
			<?php 
				if (!isset($_REQUEST['fnz']))
				{
					include("m-corpo.php");
				}
				else
				{
					switch ($_REQUEST['fnz'])
					{
						case "wip":
								include("wip.php");
								break;
						case "statistiche":
								include("statistiche.php"); // pagina 12
								break;
						case "stampa":
								include("stampa.php"); // pagina 13
								break;
						case "setup":
								include("profilo.php"); // pagina 14
								break;	
						case "logout":
								include("logout.php"); // pagina 1 == index
								break;	
								
						case "staff":
								include("staff.php");
								break;	
								
						case "team":
								include("team.php");
								break;	
						case "allena":
								include("allena.php");
								break;			
						case "formazione":
								include("formazione.php");
								break;	
								
						case "classifica":
								include("classifica.php");
								break;			
									
						case "calendario":
								include("calendario.php");
								break;
								
						case "pri_investimento":
								include("pri_investimento.php");
								break;			
			
						default:
								$stampa_funzione = $_REQUEST['fnz'];
								echo "<h1>Manca il file per la funzione: $stampa_funzione</h1>";
								break;
					}
				}
			?>
		</div>
		
	</div>
	<script>
		$('#corpo_interno').slideUp(0).delay(300).fadeIn(600);
	</script>
	<div id="topbar">
			<?php include "footer.php";	?>
	</div>	
</body>
</html>
   
     



