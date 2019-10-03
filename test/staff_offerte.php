<?php
	require_once('auth.php');
?>
<head>
<style type="text/css">
.a_nullst{
	font-size:12px;
	font-variant:normal;
	font-weight:bold;
	text-decoration:none;
	color: #000000;
}
a.a_nullst:hover {
	font-size:12px;
	text-decoration:none;
	color: #000000;
}

a.a_nullst:visited {
	font-size:12px;
	text-decoration:none;
	color: #000000;
}

</style>
</head>

<?php 
	$Oggi = time();
	$g1 = date("d",$Oggi);
	$m1 = date("m",$Oggi);
	$a1 = date("Y",$Oggi);
	
	$dataIta = $g1."/".$m1."/".$a1;
	$separatore = "/";
	// data in formato sql  
	$split_data = explode($separatore, $dataIta);
	$dataSql = $split_data[2] . "-" . $split_data[1] . "-" . $split_data[0]; 

	$result = mysql_query("SELECT * FROM staff_mercato WHERE s_scadenza > '$dataSql' AND s_id_team=\"$nome_team\" ");
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	echo "<div class='s_listastaff' >"; 
	echo "<table border='0' width='98%' >";
	echo "<tr class='green_bar'>";
	echo "<th align=left>Candidato</th>";
	echo "<th title='Abilità'>Abi.</th>";
	echo "<th title='Esperienza'>Esp.</th>";
	echo "<th title='Motivazione'>Mot.</th>";
	echo "<th title='Stipendio'>Stipendio</th>";
	echo "<th title='Carattere'>Carattere</th>";
	echo "<th title='Filosofia'>Filosofia</th>";
	echo "<th title='Forma'>Form</th>";
	echo "<th title='Condizione'>Cond</th>";
	echo "<th title='Parate'>PO</th>";
	echo "<th title='Difesa'>DF</th>";
	echo "<th title='Contrasti'>CN</th>";
	echo "<th title='Passaggi'>PA</th>";
	echo "<th title='Regia'>RG</th>";
	echo "<th title='Cross'>CR</th>";
	echo "<th title='Tecnica'>TC</th>";
	echo "<th title='Tiro'>TR</th>";
	echo "<th title='Talento'>TAL</th>";
	echo "<th title='Scadenza della proposta' align='center'>Scadenza</th>";
	echo "<th align='left'></th>";
	echo "</tr>";

	$wstip = 0;
	$counter=0;
	while   ($row   =   mysql_fetch_array($result))
	{
		//($counter % 2 == 0) ? $class = "BlackOnWhite" : $class = "BlackOnWhite"; 
	
		$effic = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
		$wstip = number_format($row['s_sti'],0,",",".");
	
		echo "<tr title='Efficienza: $effic' id='tr_$counter' onmouseover='javascript: OverStaff($counter);' onmouseout='javascript: OutStaff($counter);' >";
		
		echo "<a class='a_nullst' href='#' onclick=\"myFrame('frm_contra','staff_contra.php',$row[s_id]);\" >";
		echo "<td>$row[s_descrizione]</td>";
		echo "<td align=center>$row[s_abi]</td>";
		echo "<td align=center>$row[s_esp]</td>";
		echo "<td align=center>$row[s_mot]</td>";
		echo "<td align=right>€.&nbsp;&nbsp;$wstip</td>";
		echo "<td align=center>$row[s_car]</td>";
		if ($row['s_id_staff'] == 1 or
			$row['s_id_staff'] == 2 or
			$row['s_id_staff'] == 5 or
			$row['s_id_staff'] == 10)
		{
			if ($row['s_id_staff'] == 1)
			{
				echo "<td>$row[s_fil]</td>";
			}
			else
			{
				echo "<td>&nbsp;</td>";
			}
			echo "<td align=center>$row[s_for]%</td>";
			echo "<td align=center>$row[s_con]%</td>";
			echo "<td align=center>$row[s_po]%</td>";
			echo "<td align=center>$row[s_df]%</td>";
			echo "<td align=center>$row[s_cn]%</td>";
			echo "<td align=center>$row[s_pa]%</td>";
			echo "<td align=center>$row[s_rg]%</td>";
			echo "<td align=center>$row[s_cr]%</td>";
			echo "<td align=center>$row[s_tc]%</td>";
			echo "<td align=center>$row[s_tr]%</td>";
			echo "<td align=center>$row[s_tal]%</td>";
		}
		else
		{
			echo "<td colspan=12>&nbsp;</td>";
		}
		
		$data = strtotime($row['s_scadenza']);
		$g1 = date("j",$data); // Giorno
		$m1 = date("m",$data); // Mese
		$a1 = date("Y",$data); // Anno
		$dataIta = $g1."/".$m1."/".$a1;
		
		echo "<td> <center>$dataIta</td>";
		echo "<td><input type='hidden' id='ws_id_$row[s_id]' name='ws_id_$row[s_id]' value='$row[s_id]'><center><a href='#' onclick='javascript:Elimina_Candidato(ws_id_$row[s_id]);'><img src='images/cancel_f2.png' width='20' border='0'/></td>";
		
		
		
		echo "</a>";
		echo "</tr>";
		$counter++;
	} // end while


echo "</table>";
echo "</div>";
?>
