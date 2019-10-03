<?php
	require_once('../auth.php');
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	include "../connect_db.php";	
	
	$gio = $_REQUEST['giovani'];
	$ski = $_REQUEST['skill'];

	$verif = mysql_query("SELECT * FROM pri_investimento WHERE id_team = \"$nome_team\"");
	$esiste = mysql_num_rows($verif);
	
	if ($esiste == 0)
	{
		$qry = "INSERT INTO pri_investimento (id_team, giovani, skill) VALUES (\"$nome_team\", '$gio', '$ski')";
	} 
	else
	{
		$qry = "UPDATE pri_investimento SET giovani = '$gio', skill = '$ski' WHERE id_team = \"$nome_team\" ";
	}
	
	$result = mysql_query($qry);
	
	if (!$result)
	{
   		echo 'Errore nella query : ' . mysql_error();
	    exit();
	}
	mysql_close($link);
?>
