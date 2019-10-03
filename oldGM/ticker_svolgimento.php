<?php
				
		//CARICO LA TELECRONACA !!!
		$qry = "SELECT * FROM z_telecronaca WHERE id_partita='$ID_PARTITA' ORDER BY prog";
		$result = mysql_query($qry);
		$row_telecronaca = mysql_fetch_array($result);	
		
		echo "<div id='svolgimento'>";
		echo "<fieldset style='width: 97%;'>";
		echo "<legend>Partita del $dataIta</legend>";
		echo "<table border='0' width='100%'>";
		echo "<tr>";
		echo "<td class='tabella1' align='right' width='40%'>$sq_casa</td>";
		echo "<td align='center'>-</td>";
		echo "<td class='tabella2' align='left' width='40%'>$sq_fuori</td></tr>";
		echo "<tr><td align='right'>";

		$corri = $c_stanchezza;
		echo "100% ";
		if ($corri >100) { $corri = 100; }
		if ($corri <0) { $corri = 0; }
			
		for ($qw=100; $qw >= 0 ; $qw--)
		{
			$nome = "img_casa_".$qw;
			if ($qw > $corri) { $immagina = $immbianca ;	} else { $immagina = $immbarretta ; }
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		echo " 0%";
	
		echo "</td>	<td>&nbsp;</td>	<td align='left'>";

		$corri = $f_stanchezza;
		echo "0% ";
		if ($corri >100) { $corri = 100; }
		for ($qw=0; $qw <= 100 ; $qw++)
		{
			$nome = "img_avversario_".$qw;
			if ($qw < $corri) { $immagina = $immbarretta ;	} else { $immagina = $immbianca ; }
			echo "<img id=$nome name=$nome src=$immagina >";
		}
		echo " 100%";
		echo "</td>	</tr>";
	
		echo "<tr class='tabella3'><td align='right'>$c_goal</td>";
		echo "<td align='center'>:</td>";
		echo "<td align='left'>$f_goal</td></tr>";

		echo "<tr class='tabella4'><td></td><td align='center'><div id='contatore'></div>Finale</td></tr>";
		echo "<tr height='50'><td>&nbsp;</td></tr>";	
		echo "</table>";	
	
		echo "<span>";
		echo "<div id='telecronaca'>";
	
		$qry = "SELECT * FROM z_telecronaca WHERE id_partita='$ID_PARTITA' ORDER BY prog ASC";
		$res = mysql_query($qry);
	
		//CARICO LA TELECRONACA
		while ($row   =   mysql_fetch_array($res))
		{
			$riga = $row['descri'];

			echo "<div id='MsgPsa'>";
			echo "<br><br>$row[min]&deg;: ";
			echo "$riga<br>";
			echo "</div>";
		}
		echo "<script>";
		echo "$('#MsgPsa').slideUp(0).delay(800).fadeIn(1200);";
		echo "</script>";
	
		echo "</div>";
		echo "</span>";		
?>

