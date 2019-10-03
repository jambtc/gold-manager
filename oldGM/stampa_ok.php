<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
<?php
	$quale_css = "css/index".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
</head>

<body>
<h1>Comunicato Stampa</h1>

<div id="stampa">
	<center>
	<div class="content-area"> 
		<img id="stampa" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="stampa">
			<fieldset id='stampa'>
				<h2>COMUNICATO STAMPA CORRETTAMENTE INVIATO</h2>
			</fieldset>
		</span>
	</div>
</div>
<script>
	$('#stampa').slideUp(0).delay(300).fadeIn(600);
</script>
		

</body>
</html>
