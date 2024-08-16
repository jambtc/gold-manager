<?php
	require_once('../auth.php');
	require "../connect_db.php";
	
	$testo = $_REQUEST['testo'];
	$utente = $_SESSION['SESS_USER'];
	$timestamp = time(); 
	
	if ($testo != "")
	{
		$qry = "insert into messaggi set utente = \"$utente\", testo = \"$testo\", time = '$timestamp'";
		
		$result = mysqli_query($link, $qry);
		if (!($result))
		{
			print "Chat Insert error > ".mysqli_error();
			exit;
		}
	}
	$select = "SELECT * FROM members ";
	$rest = mysqli_query($link, $select);
	while   ($row   =   mysqli_fetch_array($rest))
	{
		$avatar[$row['login']] = $row['avatar'];
	}
	$seleziona = "SELECT * FROM messaggi WHERE 1 ORDER BY time DESC";
	$result_box = mysqli_query($link, $seleziona);
	if (!($result_box))
	{
		print "Message Printing Error > ".mysqli_error();
	}
	while ($row   =   mysqli_fetch_array($result_box))
	{
		$Oggi = $row['time'];
		$g1 = date("d",$Oggi);
		$m1 = date("m",$Oggi);
		$a1 = date("Y",$Oggi);
		$h1 = date("H",$Oggi);
		$mn1 = date("i",$Oggi);
		$oraIng = $h1.":".$mn1;
		$dataIta = $g1."/".$m1."/".$a1;
				
		echo "<table class='chatrigo' border=0>";
			echo "<tr><td class='chatdata'>".$dataIta."<br>".$oraIng."</td>";
			echo "<td><div class='chatuser'>".$row['utente']."</div></td>";
			echo "<td class='chattext'>".$row['testo']."</td></tr>";
		echo "</table>";
	}
?>
<script>
	cronochat(5);
</script>	
		
