<?php
	require_once('../auth.php');
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	include "../connect_db.php";	
	
	$gio = $_REQUEST['giovani'];
	$ski = $_REQUEST['skill'];

	$qry = "UPDATE members SET  prim_gio = \"$gio\",
								prim_ski = \"$ski\"
				WHERE team = \"$nome_team\"";

	$result = mysqli_query($link, $qry);
	
	if (!$result)
	{
   		echo 'Errore nella query : ' . mysqli_error();
	    exit();
	}
	mysqli_close($link);
?>
