<?php
	require_once('auth.php');
	include "connect_db.php";	
	
	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];

	
	if (!isset($_REQUEST['pagina']))
	{
		$wnr   = $_POST['w_nr'];
		$wruolo = $_POST['w_ruolo'];
		$wcont = $_POST['wdata0'];
		$wstip = str_replace ( "," , "." , str_replace ( "." , "" , $_POST['wdata3'] ) );
	}
	else
	{
		$wnr   = $_REQUEST['w_nr'];
		$wruolo = $_REQUEST['w_ruolo'];
		$wcont = $_REQUEST['w_cont'];
		$wstip = $_REQUEST['w_stip'];
	}
	
	$qry_cerca = "SELECT * FROM giocatori WHERE id_team=\"$nome_team\"";
	$res_cerca = mysql_query($qry_cerca);	
	
	while ($row = mysql_fetch_array($res_cerca))
	{
		$player_id[] = $row['id'];
		$player_nr[] = $row['nr'];
		$player_name[] = $row['nome'];
	}
	
	$verifica = array_combine($player_nr,$player_id);
	
	if (!in_array($wnr,$player_nr)) // se la maglia non ce l'ha nessun giocatore in squadra
	{
		// CAMBIA NUMERO DI MAGLIA E AGGIORNA RUOLO
		$qry_change = "UPDATE giocatori SET nr='$wnr', 
											pos='$wruolo',
											contratto='$wcont',
											stipendio='$wstip'											
											WHERE id_team=\"$nome_team\" AND id='$controllo'";
	}
	else
	{
		if ($controllo != $verifica[$wnr]) // se l'id è diverso vuol dire che un altro giocatore ha
		{									// questo numero
			//ERRORE! TORNA ALLA PAGINA PRECEDENTE!
			header("location: form_giocatori_modifica.php?id=$controllo&err=$wnr");
		}
		else
		{
			$qry_change = "UPDATE giocatori SET pos='$wruolo',
											contratto='$wcont',
											stipendio='$wstip'											 											WHERE id_team=\"$nome_team\" AND id='$controllo'";
		}
	}
	$res_change = mysql_query($qry_change);	
	


	
	if (!$res_change)
	{
		echo $qry_change;
		echo '<br>Errore nella query GIOCATORI: ' . mysql_error();
		exit();
	}
	
	mysql_close($link);
	header("location: form_giocatori.php");
?>


<body>


</body>
</html>
