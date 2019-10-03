<?php
	require_once('../auth.php');
	require "../connect_db.php";
	
	$seleziona = "SELECT DISTINCT user FROM useronline WHERE 1";
	$result3 = mysql_query($seleziona);
	if (!($result3))
	{
		print "Useronline Select Error > ".mysql_error();
	}
	
	while   ($row   =   mysql_fetch_array($result3))
	{
		echo "<table class='chatrigo' border=0>";
			echo "<tr><td class='avatar_img'><img src='images/led.png' width='20' /></td>";
			echo "<td class='avatar_name'>".$row['user']."</td></tr>";
		echo "</table>";
	}
?>
		
