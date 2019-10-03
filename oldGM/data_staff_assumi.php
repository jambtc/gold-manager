<?php
	require_once('auth.php');

	$controllo = $_REQUEST['id'];
	$nome_team = $_SESSION['SESS_TEAM'];
	$stipendio = $_REQUEST['stip'];

	include "connect_db.php";	

	//SELEZIONO IL CANDIDATO DAL MERCATO
	$result1 = mysql_query("SELECT * FROM staff_mercato WHERE s_id=\"$controllo\" ");
	if (!$result1)
	{
		echo 'Errore nella query: ' . mysql_error();
		exit();
	}
	$row = mysql_fetch_array($result1);
	$id_staff = $row['s_id_staff'];
	$descri = $row['s_descrizione'];
	$abi = $row['s_abi'];
	$mot = $row['s_mot'];
	$esp = $row['s_esp'];
	//$stip = $row['s_sti'];
	$car = $row['s_car'];
	$fil = $row['s_fil'];
	
	//VERIFICO SE ESISTE UN DIPENDENTE DELLO STESSO TIPO
	$result2 = mysql_query("SELECT * FROM staff WHERE s_id_staff=\"$id_staff\" AND s_id_team=\"$nome_team\" ");
	if (!$result2)
	{
		echo 'Errore nella query: ' . mysql_error();
		exit();
	}
	$num = mysql_num_rows($result2);
	// se non esiste inserisco il nuovo
	if ($num == 0)
	{
		$qry = "INSERT INTO staff (s_id_team,s_id_staff,s_descrizione,
									s_abi,s_esp,s_mot,
									s_sti,s_car,s_fil,
									s_addestramento) 
							VALUES (\"$nome_team\",$id_staff,\"$descri\",
									$abi,$esp,$mot,
									$stipendio,\"$car\",\"$fil\",
									15)";
	}
	//altrimenti aggiorno quello già esistente
	else
	{
		$qry = "UPDATE staff SET	s_abi=$abi,
									s_esp=$esp,
									s_mot=$mot,
									s_sti=$stipendio,
									s_car=\"$car\",
									s_fil=\"$fil\",
									s_addestramento=15
				WHERE s_id_staff=\"$id_staff\" AND s_id_team=\"$nome_team\" ";
	}
	$result = mysql_query($qry);
	if (!$result)
	{
    	echo 'Errore nella query STAFF : ' . mysql_error();
	    exit();
	}
	
	// ELIMINO IL CANDIDATO
	$qry = "DELETE FROM staff_mercato WHERE s_id_team=\"$nome_team\" AND s_id=$controllo LIMIT 1";
	$result = mysql_query($qry);
	
	if (!$result)
	{
    	echo 'Errore nella query STAFF Mercato: ' . mysql_error();
	    exit();
	}
	
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
	$qry = "SELECT * FROM stat_staff WHERE s_data = '$datasql' AND s_id_team = \"$nome_team\" AND s_id_staff = '$id_staff'";
	$verif = mysql_query($qry);
	
	$num = mysql_num_rows($verif);

	if ($num == 0){ 
		// se non presente nel database
		// aggiungo la riga nella tabella
		$qry = "INSERT INTO stat_staff
				(s_data,s_id_team,s_id_staff,s_descrizione,s_abi,s_esp,s_mot,s_sti)
				VALUES 	
				('$datasql',\"$nome_team\",'$id_staff','$descri','$abi','$esp','$mot','$stipendio')";
	}
	else
	{
		$qry = "UPDATE stat_staff SET
				s_abi = '$abi',
				s_esp = '$esp',
				s_mot = '$mot',
				s_sti = '$stipendio'
				WHERE 	s_data = '$datasql'
				AND		s_id_team = \"$nome_team\"
				AND		s_id_staff = '$id_staff'";
	}
	
	$result = mysql_query($qry);
	if (!$result) {
		echo 'Errore nella query aggiornamento statistiche STAFF: ' . mysql_error();
		exit();
	}
	
	mysql_close($link);
	header("location: form_staff.php");
?>
