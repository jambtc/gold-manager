<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>GoalUnited Analysis - by Sergio Casizzone</title>
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
		<h1>Registrazione avvenuta correttamente</h1>
			<p>
				<a href="index.php" style="color:#00FF00;">Clicca qui</a>
					<span class="Stile1">per tornare alla Homepage.</span>
			</p>
	</div>
	
	
	
</body>
</html>
