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
	
	$id = $_REQUEST['id'];
	$mot = $_REQUEST['mot'];
	$sti = $_REQUEST['sti'];
	$nome_team = $_SESSION['SESS_TEAM'];

	$result = mysql_query("UPDATE staff SET s_mot=\"$mot\",s_sti=\"$sti\"
							WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$id\"");
	
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	
	$rs2 = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$id\"");
	$row = mysql_fetch_array($rs2);
	
	$dipendente = $row['s_descrizione'];
	/*
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! STATISTICHE
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	$Oggi = time();
	$g1 = date("j",$Oggi);
	$m1 = date("m",$Oggi);
	$a1 = date("Y",$Oggi);
	$dataIta = $g1."/".$m1."/".$a1;

	$separatore = "/";
	// data in formato sql  
	$split_data = explode($separatore, $dataIta);
	$datasql = $split_data[2] . "-" . $split_data[1] . "-" . $split_data[0];

	// numero di modifiche giornaliere
	$qry = "SELECT * FROM stat_staff WHERE s_data = '$datasql' AND s_id_team = \"$nome_team\" AND s_id_staff = '$controllo'";
	$verif = mysql_query($qry);
	
	$num = mysql_num_rows($verif);

	if ($num == 0){ 
		// se non presente nel database
		// aggiungo la riga nella tabella
		$qry = "INSERT INTO stat_staff
				(s_data,s_id_team,s_id_staff,s_descrizione,s_abi,s_esp,s_mot,s_sti)
				VALUES 	
				('$datasql',\"$nome_team\",'$controllo','$wdescrizione','$wabi','$wesp','$wmot','$wsti')";
	}
	else
	{
		$qry = "UPDATE stat_staff SET
				s_descrizione = '$wdescrizione',
				s_abi = '$wabi',
				s_esp = '$wesp',
				s_mot = '$wmot',
				s_sti = '$wsti'
				WHERE 	s_data = '$datasql'
				AND		s_id_team = \"$nome_team\"
				AND		s_id_staff = '$controllo'";
	}
	
	$result = mysql_query($qry);
	if (!$result) {
		echo 'Errore nella query: ' . mysql_error();
		exit();
	}
*/
	
	//MESSAGGIO NELLE NEWS DELLA DIRIGENZA
	$oggi = time();
	$g1 = date("j",$oggi);
	$m1 = date("m",$oggi);
	$a1 = date("Y",$oggi);
	$dataSql = $a1."-".$m1."-".$g1;
		
	$msg = "Hai dato un aumento di stipendio al tuo <b>$dipendente</b>. Ora pagherai ogni settimana <b>€. $sti</b>.";
							
	$qry = "INSERT INTO notiziario (team, data, notizia) VALUES (\"$nome_team\", '$dataSql', \"$msg\")";
	mysql_query($qry);
	
	
	
	
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
	
		
