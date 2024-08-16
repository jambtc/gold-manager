<?php
	require_once('../auth.php');
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	include "../connect_db.php";	
	
	$gio = $_REQUEST['giovani'];
	$ski = $_REQUEST['skill'];

	$verif = mysqli_query($link, "SELECT * FROM pri_investimento WHERE id_team = \"$nome_team\"");
	$esiste = mysqli_num_rows($verif);
	
	if ($esiste == 0)
	{
		$qry = "INSERT INTO pri_investimento (id_team, giovani, skill) VALUES (\"$nome_team\", '$gio', '$ski')";
	} 
	else
	{
		$qry = "UPDATE pri_investimento SET giovani = '$gio', skill = '$ski' WHERE id_team = \"$nome_team\" ";
	}
	
	$result = mysqli_query($link, $qry);
	
	if (!$result)
	{
   		echo 'Errore nella query : ' . mysqli_error();
	    exit();
	}
	mysqli_close($link);
?>
