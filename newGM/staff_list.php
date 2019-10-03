<?php
	require_once('auth.php');
?>
<head>
<style type="text/css">

.a_null{
	color: #ffffff;
	font-size:14px;
	font-variant:normal;
	font-weight:bold;
	text-decoration:none;
}
a.a_null:hover {
	color: #ffffff;
	font-size:14px;
	text-decoration:none;
}
a.a_null:visited {
	color: #ffffff;
	font-size:14px;
	text-decoration:none;
	
}
</style>
</head>

<?php
	echo "<div id='s_listagiocatori' >"; 
	echo "<table border='0' width='97%'>";
	echo "<tr>";
	echo "<th ></th>";
	echo "<th title='Abilità'>Abi.</th>";
	echo "<th title='Esperienza'>Esp.</th>";
	echo "<th title='Motivazione'>Mot.</th>";
	echo "<th title='Stipendio'>Stip.</th>";
	echo "<th align='left'>Carattere</th>";
	echo "<th align='left'>Filosofia</th>";
	echo "<th title='Giorni mancanti alla fine dell&acute;addestramento'>Add.</th>";
	echo "</tr>";
	
	$counter = 1; 
	$spesa = 0;
	$wstip = 0;
	while   ($row   =   mysql_fetch_array($result))
	{
	
		($counter % 2 == 0) ? $class = "BGreen" : $class = "LGreen"; 
		
		$effic = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
		$wstip = number_format($row['s_sti'],0,",",".");
		
		echo "<tr class=\"$class\" title='Efficienza: $effic' id='tr_$counter' onmouseover='javascript: Over($counter);' onmouseout='javascript: Out($counter);'>";
		
		echo "<a class='a_null' href='staff_motiva.php?id=$row[s_id_staff]' target='ApriFin' >";
		
		echo "<td >$row[s_descrizione]</td>";
		echo "<td> <center>$row[s_abi]</td>";
		echo "<td> <center>$row[s_esp]</td>";
		echo "<td> <center>$row[s_mot]</td>";
		echo "<td><justify>€.  $wstip</td>";
		echo "<td> 		   $row[s_car]</td>";
		echo "<td> 		   $row[s_fil]</td>";
		if ($row['s_addestramento'] == 15)
		{
			echo "<td>&nbsp;</td>";
		}
		else
		{
			echo "<td>$row[s_addestramento]</td>";
		}
		echo "<input type='hidden' id='ws_id_$row[s_id_staff]' name='ws_id_$row[s_id_staff]' value='$row[s_id_staff]'>";
		
		echo "</a>";
		echo "</tr>";
		
		switch ($row['s_descrizione'])
		{
			case "Allenatore":
				$b_allenatore = 0.3 * $effic ;
					break;
				
			case "Vice Allenatore":
				$b_viceallena = $effic / 30.3 ;
					break;
			
			case "Allenatore Portieri":
				$b_alleportie = 0.035 * $effic ;
					break;
		}// end switch
		
		$counter++; 
		$spesa = $spesa + $row['s_sti'];
	} // end while
	$spesa = number_format($spesa,0,",",".");
	
	echo "<tr >";
	echo "<th colspan='4' align='right'>Spesa settimanale</th>";
	echo "<th><center>€.&nbsp;$spesa</th>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
?>
