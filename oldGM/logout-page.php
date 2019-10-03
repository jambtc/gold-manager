<?php
	//Start session
	session_start();
	//Unset the variables stored in session
	unset($_SESSION['SESS_USER']);
	unset($_SESSION['SESS_TEAM']);
	session_write_close();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Gold Manager - Gioco di calcio manageriale</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Content-Style-Type" content="text/css;">
<meta name="Author" content="Sergio Casizzone">
<meta name="Keywords" content="gold manager gioco calcio manager manageriale">
<meta name="Description" content="Gioco di calcio manageriale gratis. Ideato da Sergio Casizzone.">
<?php 
	$quale_css = "css/index".$_REQUEST['lar'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
</head>
	
<body>
	
	<div>
		<?php 
			$larghezza = $_REQUEST['lar'];
			$altezza = $_REQUEST['alt'];

			$pagina = 1;
			include "connect_db.php";	
			include("intestazione.php")
		?>
	</div>
	<div id='intest_corpo'>
		<h1>Logout </h1>
		<h2 align="center" >Ti sei disconnesso correttamente.</h2>
	</div>
	
	
	
</body>
</html>
<?php 
//Unset the variables stored in session
	/*unset($_SESSION['SESS_USER']);
	unset($_SESSION['SESS_TEAM']);
	session_write_close();*/
?>
