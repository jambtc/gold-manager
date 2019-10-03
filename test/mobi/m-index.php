<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<LINK REL="SHORTCUT ICON" HREF="images/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title> Gold Manager - Sede Ufficio </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Content-Style-Type" content="text/css;">
<meta name="Author" content="Sergio Casizzone">
<meta name="Keywords" content="gold manager gioco calcio manager manageriale">
<meta name="Description" content="Gioco di calcio manageriale gratis. Ideato da Sergio Casizzone.">
<meta name="google-site-verification" content="4wrLOs_xibPALt3c2jTECfpEZkD-zYfXDTBkjbLz8BI" />

<style type='text/css'> @import 'demo.css'; </style>
<style type='text/css'> @import 'menu.css'; </style>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<script type="text/javascript" src="jquery/my_screen.js"></script>
<script type="text/javascript" src="jquery/menu.js"></script>
<script type="text/javascript" src="jquery/my_script.js"></script>
</head>

<body >
	<div style="width:100%;"  >
		<?php include("m-intestazione.php")?>
		<div >
			<?php 
				if (!isset($_REQUEST['fnz']))
				{
					include("m-corpo.php");
				}
				else
				{
					switch ($_REQUEST['fnz'])
					{
						case "statistiche":
								include("form_statistiche.php");
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
</body>
</html>
   
     



