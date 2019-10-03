<?php
	require_once('../auth.php');
	include "../connect_db.php";	
	
	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];

	$wcont = $_REQUEST['w_cont'];
	$wstip = $_REQUEST['w_stip'];
	
	
	$qry_change = "UPDATE giocatori SET 	contratto='$wcont',
											stipendio='$wstip' 
											WHERE id_team=\"$nome_team\" AND id='$controllo'";
	$res_change = mysql_query($qry_change);	
	
	if (!$res_change)
	{
		echo $qry_change;
		echo '<br>Errore nella query GIOCATORI: ' . mysql_error();
		exit();
	}
?>
<fieldset>
	<br />
	<h2><p align="center" style="color:#0000FF;">Il contratto &egrave; stato modificato correttamente.</p></h2>
	<br />
</fieldset>
