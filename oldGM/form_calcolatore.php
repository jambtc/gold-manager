<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
<title> Golden Manager - Modifica giocatori </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="Content-Style-Type" content="text/css;">
	<meta name="Author" content="Sergio Casizzone">
	<meta name="Keywords" content="goal united goalunited analysis team">
	<meta name="Description" content="Analisi e statistiche per giocare a Goalunited. by Sergio Casizzone.">
	
</head>

<body>
	<div>
		<?php 
			$pagina = 31;
			include("connect_db.php");	
			include("m-intestazione.php")
		?>
	</div>
	<div id='intest_corpo'>
		<?php include("team_calcolatore.php")?>
	</div>
</body>
</html>
