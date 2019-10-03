<?php
	require_once('auth.php');
?>
<head>
<script type="text/javascript">
 <!--
  function Elimina(arg1)
  {
   
  if (!(confirm("Elimino il candidato?")))
  {
    return false;
  }
  else
  {
  id_staff=arg1.value;

  indirizzo="data_staff_elimina_candidato.php?delete="+id_staff;
  
  window.parent.location.href=indirizzo;
  
  }
}
 //-->
</script>
</head>

<?php 
	$result = mysql_query("SELECT * FROM staff_mercato WHERE s_id_team=\"$nome_team\" ");
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	echo "<table border='0'>";
	echo "<tr>";
	echo "<th ></th>";
	echo "<th >Abi.</th>";
	echo "<th >Esp.</th>";
	echo "<th >Mot.</th>";
	echo "<th >Stip.</th>";
	//echo "<th align='left'>Carattere</th>";
	//echo "<th align='left'>Filosofia</th>";
	echo "<th align='left'>Scadenza</th>";
	echo "<th align='left'></th>";
	echo "</tr>";

	$wstip = 0;
	while   ($row   =   mysql_fetch_array($result))
	{

	//($counter % 2 == 0) ? $class = "BGreen" : $class = "LGreen"; 
	//($counter % 2 == 0) ? $stl = "filter:alpha(opacity=90); -moz-opacity:0.9; opacity:0.9;  width:100%; height:auto; height:100%;" : $stl = "filter:alpha(opacity=70); -moz-opacity:0.7; opacity:0.7;  width:100%; height:auto; height:100%;"; 
	
		$effic = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
		$wstip = number_format($row['s_sti'],0,",",".");
	
	
	echo "<tr title='Efficienza: $effic'> ";
	//echo "<td ><a href='#' onclick='javascript:Elimina(ws_id_$row[s_id_staff]);'>$row[s_descrizione]</a></td>";
	echo "<td ><a class='a1' href='staff_contra.php?id=$row[s_id]' target='contra'>$row[s_descrizione]</a></td>";
	echo "<td> <center>$row[s_abi]" ."</td>";
	echo "<td> <center>$row[s_esp]" ."</td>";
	echo "<td> <center>$row[s_mot]" ."</td>";
	echo "<td><justify>€.  $wstip</td>";
	//echo "<td> 		   $row[s_car]" ."</td>";
	//echo "<td> 		   $row[s_fil]" ."</td>";
	echo "<td> <center>$row[s_scadenza]" ."</td>";
	echo "<td><input type='hidden' id='ws_id_$row[s_id]' name='ws_id_$row[s_id]' value='$row[s_id]'><center><a href='#' onclick='javascript:Elimina(ws_id_$row[s_id]);'><img src='images/cancel_f2.png' width='20' border='0'/></td>";
	echo "</tr>";
	
	
} // end while


echo "</table>";


?>
<body>
</body>
</html>
