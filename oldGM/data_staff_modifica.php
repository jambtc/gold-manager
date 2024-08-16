<?php
	require_once('auth.php');
	include "connect_db.php";	
	
	$id = $_REQUEST['id'];
	$mot = $_REQUEST['mot'];
	$sti = $_REQUEST['sti'];
	$nome_team = $_SESSION['SESS_TEAM'];


	

	$result = mysqli_query($link, "UPDATE staff SET s_mot=\"$mot\",s_sti=\"$sti\"
							WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$id\"");
	
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysqli_error();
	    exit();
	}
	
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
	$verif = mysqli_query($link, $qry);
	
	$num = mysqli_num_rows($verif);

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
	
	$result = mysqli_query($link, $qry);
	if (!$result) {
		echo 'Errore nella query: ' . mysqli_error();
		exit();
	}
*/
	
		
	mysqli_close($link);
	header("location: form_staff.php");


?>


<body>


</body>
</html>
