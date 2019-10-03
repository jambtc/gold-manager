<?php
	require_once('auth.php');
	include("connect_db.php");	
	
	echo "<h1>Verifica squadre di tutte le serie</h1>";
	echo "<div>";

	$qry = "SELECT * FROM z_iscritti ORDER BY serie";
	$result = mysql_query($qry);
	$quale_serie = array();
	while ($row = mysql_fetch_array($result))
	{
		$squadre[]= $row['squadra'];
		$serie[] = $row['serie'];
		$cpu[] = $row['cpu'];
		if (!in_array($row['serie'],$quale_serie))
		{
			$quale_serie[] = $row['serie'];
		}
	}
	
	foreach ($quale_serie as $tipo)
	{
		echo "<br><br>Serie: $tipo";
		
		$x = 0;
		$y = 1;
		foreach ($squadre as $msg)
		{
			if ($serie[$x] == $tipo)
			{
				if ($cpu[$x] == 1)
				{
					$utente = " Utente";
				}
				else
				{
					$utente = " Cpu";
				}
				echo "<br>".$y.") ".$msg.$utente;
				$y ++;	
			}
			$x ++;	
		}
	}
	echo "</div>";
?>
</body>
</html>
