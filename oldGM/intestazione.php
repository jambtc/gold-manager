<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	//$quale_css = "css/index".$larghezza.".css";
	// se non esiste il file carico index.css
	//if (!file_exists($quale_css))
	//{ 
    	$quale_css = "css/index1024.css";
	//} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>

</head>
<?php
	$conta = mysql_query("SELECT * FROM members WHERE 1"); 
	$res = mysql_query("SELECT visite,totali FROM contatore WHERE pagina = $pagina");
	$membri = mysql_num_rows($conta);
	$visite = mysql_fetch_assoc($res);
?>
	
<body>
	<center>
		<!-- <img src="images/goldmanager_testo.png" width="35%" height="35%" border="0" /> -->
	</center>
	
	<div id='intest_monitor'>
		<img id="lavagna" src="images/lavagna.png"  />	
	</div>
	<div id='intest_login'>
		<?php include("login-form.php") ?>
	</div>
	
	<div id='intest_logo'>
		<a href="index.php">
			<img id="gm_logo" src="images/gm_logo.png" border="0" title="Vai alla Home page"/>
		</a>
	</div>
	
	<div id="topbar">
		<center>
		<table bgcolor="#333333" style="color:#FFFFFF;" border="0" align="center" width='80%'>
		<tr>
			<td width="30%" align="left">
			<?php echo "Visite Totali: ".$visite['totali'].", Singole: ".$visite['visite']." - Membri: ".$membri; ?>		
			</td>
			
			<td width="40%" align="right">
				Gold Manager - Copyright (&copy;) 2010 <a href="mailto:sergio.casizzone@poste.it">Sergio Casizzone</a>, All Rights Reserved
			</td>
		</tr>
		</table>
		</center>
	</div>
	
</body>
</html>

