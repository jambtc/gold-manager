<?php
	session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Gold Manager - Ripristino password</title>
<link href="index.css" rel="stylesheet" type="text/css" />
</head>
	
<body>
	
	<div>
		<?php
		 	$larghezza = $_GET['larghezza'];
			$altezza = $_GET['altezza'];
			
			$pagina = 1;
			include("connect_db.php");	
			include("intestazione.php");
		?>
	</div>
	<div id='intest_corpo'>
		<?php include("sendpwd-form.php")?>
	</div>
	
	
	
</body>
</html>

