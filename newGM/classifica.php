<?php
	require_once('auth.php');
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$serie = $_SESSION['SESS_SERIE'];
?>

<h1 class="h1">Classifica</h1>

<div style="float:left; width:70%;">
	<div style="display:block; width:100%; " >
		<div class='div_lista'>	
			<span class="top-label">  
				<span class="label-txt">Squadre</span>
			</span> 
			<img class='img_lista' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class="cnt_lista"> 
				<fieldset>
					<?php
							$qry = "SELECT * FROM z_classifica WHERE serie='$serie' ORDER BY punti DESC,(fatti-subiti) DESC";
							$result = mysql_query($qry);
							while ($row = mysql_fetch_array($result))
							{
								$squadra[] = $row['team'];
								$punti[] = $row['punti'];
								$vittorie[]= $row['vittorie'];
								$pareggi[]= $row['pareggi'];
								$sconfitte[] = $row['sconfitte'];
								$subiti[]= $row['subiti'];
								$fatti[]= $row['fatti'];
								
							}
							echo "<table border='0' width='100%' cellpadding='0' cellspacing='3' >";
							echo "<tr>";
							echo "<th align='left' width=20>Pos.</th>";
							echo "<th align='left'>Squadra</th>";
							echo "<th align='left' width=30>Pt.</th>";
							echo "<th align='left' width=20>V</th>";
							echo "<th align='left' width=20>P</th>";
							echo "<th align='left' width=20>S</th>";
							echo "<th align='left' width=20>GF</th>";
							echo "<th align='left' width=20>GS</th>";
							echo "</tr>";
							echo "<tr><td colspan=8><hr></td></tr>";
							
							$j = 0;
							$classifica = 0;
							foreach ($squadra as $msg)
							{
								$classifica ++;
								echo "<tr>";
								echo "<td>$classifica&deg;</td>";
								echo "<td><font color='#000000'><b>$squadra[$j]</b></font></td>";
								echo "<td><font color='#0000ff'><b>$punti[$j]</b></font></td>";
								echo "<td>$vittorie[$j]</td>";
								echo "<td>$pareggi[$j]</td>";
								echo "<td>$sconfitte[$j]</td>";
								echo "<td>$fatti[$j]</td>";
								echo "<td>$subiti[$j]</td>";
								echo "</tr>";
								$j++;
							}
							echo "</table>";
						?>
				</fieldset>
			</div>
		</div>
	</div>
	
	<div style="display:block; width:100%; margin-top:50px; " >
		<div style="float:left; width:50%; " >
			<div class='div_ultima'>	
				<span class="top-label">  
					<span class="label-txt">Ultima Giornata</span>
				</span> 
				<img class='img_ultima' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
				<div class="cnt_ultima"> 
					<fieldset>
					<?php
					$id_partita = array();
					$qry = "SELECT * FROM  zz_config WHERE id=1";
					$result = mysql_query($qry);
					if (!$result)
					{
				    	echo 'Errore nella query: ' . mysql_error();
				    	exit();
					}
					$row   =   mysql_fetch_array($result);
					//$config_data = $row['data'];
					$config_giorno = $row['giorno'];
					//$config_orario = $row['orario'];
					//$config_squadre= $row['squadre'];
					
					$oggi = time();
							
					$g1 = date("j",$oggi);
					$m1 = date("m",$oggi);
					$a1 = date("Y",$oggi);
						
					for ($x=0; $x <=7 ; $x++)
					{
						$precdate  = mktime (0,0,0,$m1,  $g1-$x,  $a1);
						if (date("w",$precdate) == $config_giorno)
						{
							$g2 = date("j",$precdate);
							$m2 = date("m",$precdate);
							$a2 = date("Y",$precdate);
								
							$dataIta = $g2."/".$m2."/".$a2;
							$dataSql = $a2."-".$m2."-".$g2;
							break;
						}
					}
					$qry = "SELECT * FROM z_calendario WHERE serie='$serie' AND data<='$dataSql' AND giocata=1";
					$qry = $qry." ORDER BY data";
					$result = mysql_query($qry);
					while ($row = mysql_fetch_array($result))
					{
						$id_partita[] = $row['id_partita'];
						$data[] = $row['data'];
						$ora[] = $row['ora_inizio'];
						$casa[]= $row['casa'];
						$fuori[]= $row['fuori'];
						$gol_casa[] = $row['gol_casa'];
						$gol_fuori[]= $row['gol_fuori'];
						$giocata[]= $row['giocata'];
					}
					echo "<table border='0' width=100%>";
					$j = 0;
					$giornata = 0;
					if (count($id_partita) >0)
					{
						foreach ($id_partita as $msg)
						{
							$giornata ++;
							echo "<tr>";
							echo "<td>$giornata&deg;</td>";
							echo "<td>";
							if ($casa[$j] == $nome_team) { echo "<b>"; }
							echo $casa[$j];
							if ($casa[$j] == $nome_team) { echo "</b>"; }
							echo "</td>";
							echo "<td>-</td>";
							echo "<td>";
							if ($fuori[$j] == $nome_team) { echo "<b>"; }
							echo $fuori[$j];
							if ($fuori[$j] == $nome_team) { echo "</b>"; }
							echo "</td>";
							echo "<td>-</td>";
							echo "<td>";
							$img1 = "images/digit/n".$gol_casa[$j].".png";
							echo "<img src=$img1 width=20 />";
							echo "</td>";
							echo "<td>:</td>";
							echo "<td>";
							$img2 = "images/digit/n".$gol_fuori[$j].".png";
							echo "<img src=$img2 width=20 />";
							echo "</td>";
							echo "</tr>";
							
							$j++;
						}
					}
					echo "</table>";
					?>
					</fieldset>
				</div>
			</div>
		</div>
		<div style="float:right; width:50%;" >
			<div class='div_prossima'>	
				<span class="top-label">  
					<span class="label-txt">Prossimo Turno</span>
				</span> 
				<img class='img_prossima' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
				<div class="cnt_prossima"> 
					<fieldset>
						<?php
					$id_partita = array();
					$qry = "SELECT * FROM  zz_config WHERE id=1";
					$result = mysql_query($qry);
					if (!$result)
					{
				    	echo 'Errore nella query: ' . mysql_error();
				    	exit();
					}
					$row   =   mysql_fetch_array($result);
					//$config_data = $row['data'];
					$config_giorno = $row['giorno'];
					//$config_orario = $row['orario'];
					//$config_squadre= $row['squadre'];
					$g1 = date("j",$oggi);
					$m1 = date("m",$oggi);
					$a1 = date("Y",$oggi);
					
					for ($x=0; $x <=7 ; $x++)
					{
						$nextdate  = mktime (0,0,0,$m1,  $g1+$x,  $a1);
						
						if (date("w",$nextdate) == $config_giorno)
						{
							$g1 = date("j",$nextdate);
							$m1 = date("m",$nextdate);
							$a1 = date("Y",$nextdate);
							
							$dataIta = $g1."/".$m1."/".$a1;
							$dataSql = $a1."-".$m1."-".$g1;
							
							$breakdate  = mktime (0,0,0,$m1,  $g1+7,  $a1);
							$g2 = date("j",$breakdate);
							$m2 = date("m",$breakdate);
							$a2 = date("Y",$breakdate);
							$dataBreak = $a2."-".$m2."-".$g2;
							break;
						}
					}
					
					$qry = "SELECT * FROM z_calendario WHERE serie='$serie'";
					$qry = $qry." AND data>='$dataSql' AND giocata=0 ";
					$qry = $qry." ORDER BY data";
					
					$result = mysql_query($qry);
					$row = mysql_fetch_array($result);
					
					$dataBreak = $row['data'];
					
					$id_partita[] = $row['id_partita'];
					$data[] = $row['data'];
					$ora[] = $row['ora_inizio'];
					$casa[]= $row['casa'];
					$fuori[]= $row['fuori'];
					$gol_casa[] = $row['gol_casa'];
					$gol_fuori[]= $row['gol_fuori'];
					$giocata[]= $row['giocata'];
					while ($row = mysql_fetch_array($result))
					{
						if ($dataBreak == $row['data'])
						{
							$id_partita[] = $row['id_partita'];
							$data[] = $row['data'];
							$ora[] = $row['ora_inizio'];
							$casa[]= $row['casa'];
							$fuori[]= $row['fuori'];
							$gol_casa[] = $row['gol_casa'];
							$gol_fuori[]= $row['gol_fuori'];
							$giocata[]= $row['giocata'];
						}
					}
					echo "<table border='0' width=100%>";
					$j = 0;
					$giornata = 0;
					if (count($id_partita) >0)
					{
						foreach ($id_partita as $msg)
						{
							$giornata ++;
							echo "<tr>";
							echo "<td>$giornata&deg;</td>";
							echo "<td>";
							if ($casa[$j] == $nome_team) { echo "<b>"; }
							echo $casa[$j];
							if ($casa[$j] == $nome_team) { echo "</b>"; }
							echo "</td>";
							echo "<td>-</td>";
							echo "<td>";
							if ($fuori[$j] == $nome_team) { echo "<b>"; }
							echo $fuori[$j];
							if ($fuori[$j] == $nome_team) { echo "</b>"; }
							echo "</td>";
							echo "<td>-</td>";
							echo "<td width=1%>";
							$img1 = "images/digit/n".$gol_casa[$j].".png";
							echo "<img src=$img1 width=20 />";
							echo "</td>";
							echo "<th>:</th>";
							echo "<td>";
							$img2 = "images/digit/n".$gol_fuori[$j].".png";
							echo "<img src=$img2 width=20 />";
							echo "</td>";
							echo "</tr>";
							
							$j++;
						}
					}
					echo "</table>";
					?>
					</fieldset>
				</div>
			</div>
		</div>
	</div>
</div>



<div style="float: right; width:30%; ">
	<div class='div_marcatori'>	
		<span class="top-label">  
			<span class="label-txt">Marcatori</span>
		</span> 
		<img class='img_marcatori' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class="cnt_marcatori"> 
			<fieldset>
				<?php
						$m_squadra = array();
						$qry = "SELECT * FROM z_marcatori WHERE serie='$serie' ORDER BY gol DESC";
						$result = mysql_query($qry);
						while ($row = mysql_fetch_array($result))
						{
							$m_squadra[] = $row['team'];
							$m_nome[] = $row['nome'];
							$m_fatti[]= $row['fatti'];
							
						}
						echo "<table border='0' width='100%' cellpadding='0' cellspacing='3' >";
						echo "<tr>";
						echo "<th align='left' width=20>Pos.</th>";
						echo "<th align='left'>Nome</th>";
						echo "<th align='left'>Squadra</th>";
						echo "<th align='left' width=20>Gol</th>";
						echo "</tr>";
						echo "<tr><td colspan=4><hr></td></tr>";
						
						$j = 0;
						$classifica = 0;
						if (count($m_squadra) >0)
						{
							foreach ($m_squadra as $msg)
							{
								$classifica ++;
								echo "<tr>";
								echo "<td>$classifica&deg;</td>";
								echo "<td><font color='#000000'><b>$m_nome[$j]</b></font></td>";
								echo "<td><font color='#0000ff'><b>$m_squadra[$j]</b></font></td>";
								echo "<td>$m_fatti[$j]</td>";
								echo "</tr>";
								$j++;
							}
						}
						echo "</table>";
					?>
			</fieldset>
		</div>
	</div>
</div>

<script>
	allarga_maschera('lista',40,ValAlt('classifica'));
	allarga_maschera('ultima',30,ValAlt('classifica')/1.7);
	allarga_maschera('prossima',30,ValAlt('classifica')/1.7);
	allarga_maschera('marcatori',28,ValAlt('classifica')*1.7);
</script>	


