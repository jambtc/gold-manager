<?php
	session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Golden Manager - Accesso Negato</title>
<?php
	$quale_css = "index".$_SESSION['SESS_LARGHEZZA'].".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
?>

</head>
	
<body>
	<div>
		<?php
		 	$pagina = 1;
			include("connect_db.php");	
			include("intestazione.php");
		?>
	</div>
	<div id='intest_corpo'>
		<?php include("access-denied.php")?>
	</div>
</body>
</html>
