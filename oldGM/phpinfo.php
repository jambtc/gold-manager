<?php
	require_once('auth.php');
	echo "<center>Risoluzione schermo: ".$_SESSION['SESS_LARGHEZZA']." x ".$_SESSION['SESS_ALTEZZA'];
	echo "<br>";
	phpinfo();
?>