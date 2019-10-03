<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<?php 
		$quale_css = "index".$_SESSION['SESS_LARGHEZZA'].".css";
		echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
	?>

<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>

<body>
<?php
	$nome_team = $_SESSION['SESS_TEAM'];
	$serie = $_SESSION['SESS_SERIE'];
?>

<h1>Calendario</h1>

<div style="float:left;">
	<span class="top-label">  
		<span class="label-txt">Elenco partite da giocare</span>
	</span> 
	<div class="content-area"> 
		<img id="calend_serie" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="calend_serie">
			<fieldset id='calend_serie'>
				<div id='calend_serie' >
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

<div style="float:right;">
<span class="top-label">  
	<span class="label-txt">Elenco partite già disputate</span>
</span> 
<div class="content-area"> 
	<img id="calend_serie" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="calend_serie2">
		<fieldset id='calend_serie'>
			<div id='calend_serie' >
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
	
</body>
</html>
