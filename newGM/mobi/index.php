<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<LINK REL="SHORTCUT ICON" HREF="images/favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Gold Manager - Gioco di calcio manageriale</title>
<style type='text/css'>@import 'demo.css'; 
</style>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<script type="text/javascript" src="jquery/my_screen.js"></script>
<script type="text/javascript" src="jquery/my_script.js"></script>
</head>
	
<body>
	<div align="center">
		<a href="index.php">
			<img src="images/gm_logo.png" border="0"/>
		</a>
	</div>	
	
	<div align="center">
		<?php
					if (!isset($_REQUEST['fnz']))
					{
						include("login-form.php");
					}
					else
					{
						switch ($_REQUEST['fnz'])
						{
							
							
							case "loginfailed":
								include("login-failed.php");
								break;
								
							default:
								$stampa_funzione = $_REQUEST['fnz'];
								echo "<h1>Manca il file per la funzione: $stampa_funzione</h1>";
								break;
						}
					}
					
					
				?>
	</div>
	
</body>
</html>

