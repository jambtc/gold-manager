<?php 
	$user = $_SESSION['SESS_USER'];
	
	$timeoutseconds = 300;
	$timestamp = time(); 
	$timeout = $timestamp-$timeoutseconds;
	
	$insert = "INSERT INTO useronline VALUES ('$timestamp',\"$user\")";
	$result1 = mysql_query($insert);
	
	if (!($result1))
	{
		print "Useronline Insert Failed > ".mysql_error();
	}
	$delete = "DELETE FROM useronline WHERE timestamp<$timeout";
	$result2 = mysql_query($delete);
	
	if (!($result2))
	{
		print "Useronline Delete Failed > ".mysql_error();
	}
	
	$seleziona = "SELECT DISTINCT user FROM useronline WHERE 1";
	$result3 = mysql_query($seleziona);
	
	if (!($result3))
	{
		print "Useronline Select Error > ".mysql_error();
	}
	
	echo "<fieldset style='width=97%;'>";
	echo "<font style='font-family:Geneva, Arial, Helvetica, sans-serif;' color='#FFFF00' size='1'><b>Utenti on line</b><br>";
	
	while   ($row   =   mysql_fetch_array($result3))
	{
		echo "<font color='#00FF00' size='1'>$row[user]<br>";
	}
	echo "</fieldset>";
?>
