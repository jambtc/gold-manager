<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<LINK REL="SHORTCUT ICON" HREF="images/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Gold Manager - Gioco di calcio manageriale</title>
<script type="text/javascript">
function getBrowserHeight()
{
	alt = window.screen.height;
	larg = window.screen.width;
	
	indirizzo = "mobi-index.php?larghezza="+larg+"&altezza="+alt;

	window.location.href=indirizzo;
}
</script>	
<?php 
    $quale_css = "css/index-mobi.css";
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
</head>
<?php
	if (!isset($_GET['altezza']))
	{
		echo "<script>getBrowserHeight();</script>";
	}
	else
	{	
		$larghezza = $_GET['larghezza'];
		$altezza = $_GET['altezza'];

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
	}
	?>
	
	<body>
	
	<div  align="center">
		<a href="index.php">
			<img src="images/gm_logo.png" border="0"/>
		</a>
	</div>
	
	<div align="center">
		<img src="images/lavagna.png" width="230" />	
	</div>
	<div id='intest_login' >
		<?php include("login-form.php") ?>
	</div>
	
	</body>
</html>

