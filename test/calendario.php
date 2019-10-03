<?php
	require_once('auth.php');
	$nome_team = $_SESSION['SESS_TEAM'];
	$serie = $_SESSION['SESS_SERIE'];
?>

<h1 class="h1">Calendario</h1>

<div style="float:left; width:50%;">
	<div class='div_dagiocare'>	
		<span class="top-label">  
			<span class="label-txt">Elenco partite da giocare</span>
		</span> 
		<img class='img_dagiocare' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class="cnt_dagiocare"> 
			<fieldset>
					<?php
						$qry = "SELECT * FROM z_calendario WHERE serie='$serie' AND giocata=0 ORDER BY data";
						$result = mysql_query($qry);
						if (mysql_num_rows($result) >0)
						{
							while ($row = mysql_fetch_array($result))
							{
								$id_partita[] = $row['id_partita'];
								//$serie[] = $row['serie'];
								$data[] = $row['data'];
								$ora[] = $row['ora_inizio'];
								$casa[]= $row['casa'];
								$fuori[]= $row['fuori'];
								$gol_casa[] = $row['gol_casa'];
								$gol_fuori[]= $row['gol_fuori'];
								$giocata[]= $row['giocata'];
							}
							echo "<table border='0'>";
							$j = 0;
							$giornata = 0;
							foreach ($id_partita as $msg)
							{
								if ($casa[$j] == $nome_team or $fuori[$j] == $nome_team)
								{
									$giornata ++;
									echo "<tr>";
									echo "<td>".$giornata."°</td>";
									echo "<td><font color='#000000'><b>";
									echo $data[$j];
									echo "</b></font></td>";
									echo "<td>-</td>";
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
									if ($giocata[$j] == 1)
									{
										echo "<td width=1%>";
										$img1 = "images/digit/n".$gol_casa[$j].".png";
										echo "<img src=$img1 width=20 />";
										echo "</td>";
										echo "<th>:</th>";
										echo "<td>";
										$img2 = "images/digit/n".$gol_fuori[$j].".png";
										echo "<img src=$img2 width=20 />";
										echo "</td>";
									}
									else
									{
										echo "<td><b>";
										echo "&nbsp;";
										echo "</td>";
										echo "<td>:</td>";
										echo "<td>";
										echo "&nbsp;";
										echo "</b><br></td>";
									}
									echo "</tr>";
								}
								$j++;
							}
							echo "</table>";
						}
									
					?>
				
			</fieldset>
		</div>
	</div>
</div>

<div style="float: right; width:50%;">
	<div class='div_disputate'>	
		<span class="top-label">  
			<span class="label-txt">Elenco partite già disputate</span>
		</span> 
		<img class='img_disputate' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class="cnt_disputate"> 
			<fieldset>
					<?php
						unset($id_partita);
						unset($data);
						unset($ora);
						unset($casa);
						unset($fuori);
						unset($gol_casa);
						unset($gol_fuori);
						unset($giocata);
						
						$qry = "SELECT * FROM z_calendario WHERE serie='$serie' AND giocata= 1 ORDER BY data";
						$result = mysql_query($qry);
						if (mysql_num_rows($result) > 0)
						{
							while ($row = mysql_fetch_array($result))
							{
								$id_partita[] = $row['id_partita'];
								//$serie[] = $row['serie'];
								$data[] = $row['data'];
								$ora[] = $row['ora_inizio'];
								$casa[]= $row['casa'];
								$fuori[]= $row['fuori'];
								$gol_casa[] = $row['gol_casa'];
								$gol_fuori[]= $row['gol_fuori'];
								$giocata[]= $row['giocata'];
							}
							echo "<table border='0'>";
							$j = 0;
							$giornata = 0;
							foreach ($id_partita as $msg)
							{
								if ($casa[$j] == $nome_team or $fuori[$j] == $nome_team)
								{
									$giornata ++;
									echo "<tr>";
									echo "<td>".$giornata."°</td>";
									echo "<td><font color='#000000'><b>";
									echo $data[$j];
									echo "</b></font></td>";
									echo "<td>-</td>";
									echo "<td><font color='#0000ff'><b>";
									echo $casa[$j];
									echo "</td>";
									echo "<td>-</td>";
									echo "<td><font color='#0000ff'>";
									echo $fuori[$j];
									echo "</b></font>";
									echo "</td>";
									echo "<td>-</td>";
									if ($giocata[$j] == 1)
									{
										echo "<td><b>";
										echo $gol_casa[$j];
										echo "</td>";
										echo "<td>:</td>";
										echo "<td>";
										echo $gol_fuori[$j];
										echo "</b><br></td>";
									}
									else
									{
										echo "<td><b>";
										echo "&nbsp;";
										echo "</td>";
										echo "<td>:</td>";
										echo "<td>";
										echo "&nbsp;";
										echo "</b><br></td>";
									}
									echo "</tr>";
								}
								$j++;
							}
							echo "</table>";
						}
					?>
				</div>
			</fieldset>
		</span>
	</div>
</div>
<script>
	allarga_maschera('dagiocare',40,ValAlt('calendario'));
	allarga_maschera('disputate',40,ValAlt('calendario'));
</script>	

