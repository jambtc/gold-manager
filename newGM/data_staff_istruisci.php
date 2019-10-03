<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<link rel="stylesheet" type="text/css" href="statistiche.css" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>
</head>
<?php
	include "connect_db.php";	
	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];
	
	$result = mysql_query("UPDATE staff SET s_addestramento = 14 WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$controllo\"");
	
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	$rs2 = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$id\"");
	$row = mysql_fetch_array($rs2);
	
	$dipendente = $row['s_descrizione'];
	$wstip_15 = number_format(($row['s_sti']*15),0,",",".");
	
	//MESSAGGIO NELLE NEWS DELLA DIRIGENZA
	$oggi = time();
	$g1 = date("j",$oggi);
	$m1 = date("m",$oggi);
	$a1 = date("Y",$oggi);
	$dataSql = $a1."-".$m1."-".$g1;
		
	$msg = "Hai iniziato l'addestramento del tuo <b>$dipendente</b>, pagandolo <b>€. $wstip_15</b>. Non sarà al massimo della condizione per 2 settimane.";
							
	$qry = "INSERT INTO notiziario (team, data, notizia) VALUES (\"$nome_team\", '$dataSql', \"$msg\")";
	mysql_query($qry);
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! aggiorna budget
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! messaggio serie
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			
	mysql_close($link);
?>
<h4>Salvataggio effettuato correttamente.</h4>
	<table>
		<tr>
			<td>
				<a href="" onclick="window.top.location.href='m-index.php?fnz=staff&pg=22'; return false;" class="button">aggiorna pagina</a>
			</td>
		</tr>
	</table>
