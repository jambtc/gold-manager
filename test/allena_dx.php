<?php
	if (isset($_REQUEST['ajax']))
	{
		require_once('auth.php');
		include "connect_db.php";
	?><head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/jquery-ui-1.8.17.custom.min.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script> 
	<script type="text/javascript" src="jquery/my_script_team.js"></script>
	<script type="text/javascript" src="jquery/my_tooltip_allena.js"></script>
	<style>
	
	.green_bar { background-color:#006600; border:#666666 solid 2px; font-size:12px; font-weight:bold; color:#FFFFFF; }
	
	</style>
	</head>
	<?php
	}
	$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");
	
	$im_ruoli = array("PO","DS","D","DD","CS","C","CD","AS","A","AD","XX");
	
	$ar_immpos = array_combine($im_ruoli,$im_pos);
	
	$quale_ruolo['PO'] = "(PO) Portiere";
	$quale_ruolo['DS'] = "(DS) Terzino Sinistro";
	$quale_ruolo['D']  = "(D) Difensore Centrale";
	$quale_ruolo['DD'] = "(DD) Terzino Destro";
	$quale_ruolo['CS'] = "(CS) Ala Sinistra";
	$quale_ruolo['C']  = "(C) Centrocampista";
	$quale_ruolo['CD'] = "(CD) Ala Destra";
	$quale_ruolo['AS'] = "(AS) Attaccante Sinistro";
	$quale_ruolo['A']  = "(A) Attaccante Centrale";
	$quale_ruolo['AD'] = "(AD) Attaccante Destro"; 
	$quale_ruolo['XX'] = "(XX) Jolly"; 	
	
	$result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ORDER BY s_id_staff");
	if (!$result)
	{
		echo 'Errore nella query STAFF: ' . mysql_error();
	    exit();
	}
	$id_staff[] = "";
	$effic_staff[] = "";
	$nome_staff[] = "";
	
	while   ($row   =   mysql_fetch_array($result))
	{
		$id_staff[] = $row['s_id_staff'];
		$effic_staff[] = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
		$nome_staff[] = $row['s_nome'];	
	}
	$efficienza_allenatori = array_combine($id_staff,$effic_staff);
	$nome_allenatori = array_combine($id_staff,$nome_staff);
	
	$result = mysql_query("SELECT * FROM allena_skill WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
		echo 'Errore nella query ALLENAMENTO: ' . mysql_error();
	    exit();
	}
	while   ($row   =   mysql_fetch_array($result))
	{
		$a_id_player[] = $row['id_player'];
		$a_id_allena[] = $row['id_allena'];
		$a_forma[] = $row['forma'];
		$a_fresc[] = $row['fresc'];
		$a_cond[] = $row['cond'];
		$a_po[] = $row['po'];
		$a_df[] = $row['df'];
		$a_cn[] = $row['cn'];
		$a_pa[] = $row['pa'];
		$a_rg[] = $row['rg'];
		$a_cr[] = $row['cr'];
		$a_tc[] = $row['tc'];
		$a_tr[] = $row['tr'];
		$a_talento[] = $row['talento'];
	}
	$ar_player_allenato = array_combine($a_id_player,$a_id_allena);
	
	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
		echo 'Errore nella query GIOCATORI: ' . mysql_error();
	    exit();
	}
	while   ($row   =   mysql_fetch_array($result))
	{
		$id_player[] = $row['id'];
		$nr_player[] = $row['nr'];
		$nome_player[] = $row['nome'];
		$skill_player[] = $row['skill'];
		$pos_player[] = $row['pos'];
	}
	
	$ar_player_nr = array_combine($id_player,$nr_player);
	$ar_player_nome = array_combine($id_player,$nome_player);
	$ar_player_skill = array_combine($id_player,$skill_player);
	$ar_player_pos = array_combine($id_player,$pos_player);
?>


<div class="div_allena">
	<fieldset>
		<legend class="legenda">Allenatore</legend>
			<div>
				<div class="div_sx_all">
					<span id='all_1' onmouseout='javascript:tooltip_hide();'>
						<?php 
							if (isset($nome_allenatori[1])) 
							{ 
								echo $nome_allenatori[1]; 
							}
							else
							{
								echo "- - -";
							}
						?>
					</span>
				</div>
				<div class="div_dx_all">
					<div class="div_barra_up"><?php if (isset($efficienza_allenatori[1])) { echo $efficienza_allenatori[1]; } ?>&nbsp;%</div>
					<div class="div_barra_down">
						<?php
						 	if (isset($efficienza_allenatori[1])) 
							{
								for ($x=0; $x<=75; $x++)
								{
									if ($x <= ($efficienza_allenatori[1]*.75))
									{
										echo "<img src='images/barretta_blu.png' />";
									}
									else
									{
										echo "<img src='images/barretta_bianca.png' />";
									}
								}
							}
						?>
					</div>
				</div>
			</div>
			<div class="contenuto_allena" id="contenuto_allena_1">
				<table border="0" width="100%">
				<tr class='green_bar'>
					<td width='1%' align="center">Nr</td>
					<td width='20%' align='left'>Nome</td>
					<td width='2%'>Skill</td>
					<td width='2%' title='Ruolo'>Pos</td>
				</tr>
				<?php
				$max_righe = 0;
				foreach ($id_player as $my_id)
				{
					if (isset($ar_player_allenato[$my_id]))
					{
						if ($ar_player_allenato[$my_id] == 1) //ALLENATORE
						{
							$max_righe ++;
							$img_ruolo = "images/".$ar_immpos[$ar_player_pos[$my_id]];
							$titolo = $quale_ruolo[$ar_player_pos[$my_id]];
							echo "<tr>";
							echo "<td><input name='btn' class='brdgioca' value='$ar_player_nr[$my_id]' type='button' onclick='javascript:delete_training($my_id,1);' title='Clicca per togliere il giocatore'></td>";
							echo "<td>$ar_player_nome[$my_id]</td>";
							echo "<td align=center>$ar_player_skill[$my_id]</td>";
							echo "<td><img src=$img_ruolo width='18' heigth='16' border='0' title='$titolo'/></td>";
							echo "<input value='$my_id' type='hidden' id='tab_allena_1_$max_righe' >";
							echo "</tr>";
						}
					}
				}
				echo "</table>";
				$max_righe ++;
				for ($fine = $max_righe; $fine <= 10; $fine++)
				{
					echo "<table border=0 width='100%'>";
					echo "<tr>";
					echo "<td><input value='-1' type='hidden' id='tab_allena_1_$fine' >&nbsp;</td>";
					echo "</tr>";
					echo "</table>";
				}
			?>
			</div>
			<div class="contenuto_allena_bottom"></div>
	</fieldset>
</div>
<div class="div_allena">
	<fieldset>
		<legend class="legenda">Vice Allenatore</legend>
			<div>
				<div class="div_sx_all">
					<span id='all_2' onmouseout='javascript:tooltip_hide();'>
						<?php 
							if (isset($nome_allenatori[2])) 
							{ 
								echo $nome_allenatori[2]; 
							} 
							else
							{
								echo "- - -";
							}
							?>
					</span>
				</div>
				<div class="div_dx_all">
					<div class="div_barra_up"><?php if (isset($efficienza_allenatori[2])) { echo $efficienza_allenatori[2]; } ?>&nbsp;%</div>
					<div class="div_barra_down">
						<?php
						 	if (isset($efficienza_allenatori[2])) 
							{
								for ($x=0; $x<=75; $x++)
								{
									if ($x <= ($efficienza_allenatori[2]*.75))
									{
										echo "<img src='images/barretta_blu.png' />";
									}
									else
									{
										echo "<img src='images/barretta_bianca.png' />";
									}
								}
							}
						?>
					</div>
				</div>
			</div>
			<div class="contenuto_allena" id="contenuto_allena_2">
				<table border="0" width="100%">
				<tr class='green_bar'>
					<td width='1%' align="center">Nr</td>
					<td width='20%' align='left'>Nome</td>
					<td width='2%'>Skill</td>
					<td width='2%' title='Ruolo'>Pos</td>
				</tr>
				<?php
				$max_righe = 0;
				foreach ($id_player as $my_id)
				{
					if (isset($ar_player_allenato[$my_id]))
					{
						if ($ar_player_allenato[$my_id] == 2) //VICE ALLENATORE
						{
							$max_righe ++;
							$img_ruolo = "images/".$ar_immpos[$ar_player_pos[$my_id]];
							$titolo = $quale_ruolo[$ar_player_pos[$my_id]];
							echo "<tr>";
							echo "<td><input name='btn' class='brdgioca' value='$ar_player_nr[$my_id]' type='button' onclick='javascript:delete_training($my_id,2);' title='Clicca per togliere il giocatore'></td>";
							echo "<td>$ar_player_nome[$my_id]</td>";
							echo "<td align=center>$ar_player_skill[$my_id]</td>";
							echo "<td><img src=$img_ruolo width='18' heigth='16' border='0' title='$titolo'/></td>";
							echo "<input value='$my_id' type='hidden' id='tab_allena_2_$max_righe' >";
							echo "</tr>";
						}
					}
				}
				echo "</table>";
				$max_righe ++;
				for ($fine = $max_righe; $fine <= 10; $fine++)
				{
					echo "<table border=0 width='100%'>";
					echo "<tr>";
					echo "<td><input value='-1' type='hidden' id='tab_allena_2_$fine' >&nbsp;</td>";
					echo "</tr>";
					echo "</table>";
				}
			?>
			</div>
			<div class="contenuto_allena_bottom"></div>
	</fieldset>
</div>
<div class="div_allena">
	<fieldset>
		<legend class="legenda">Allenatore Portieri</legend>
			<div>
				<div class="div_sx_all">
					<span id='all_3' onmouseout='javascript:tooltip_hide();'>
						<?php 
							if (isset($nome_allenatori[5])) 
							{ 
								echo $nome_allenatori[5]; 
							} 
							else
							{
								echo "- - -";
							}
						?>
					</span>
				</div>
				<div class="div_dx_all">
					<div class="div_barra_up"><?php if (isset($efficienza_allenatori[5])) { echo $efficienza_allenatori[5]; } ?>&nbsp;%</div>
					<div class="div_barra_down">
						<?php
						 	if (isset($efficienza_allenatori[5])) 
							{
								for ($x=0; $x<=75; $x++)
								{
									if ($x <= ($efficienza_allenatori[5]*.75))
									{
										echo "<img src='images/barretta_blu.png' />";
									}
									else
									{
										echo "<img src='images/barretta_bianca.png' />";
									}
								}
							}
						?>
					</div>
				</div>
			</div>
			<div class="contenuto_allena" id="contenuto_allena_3">
				<table border="0" width="100%">
				<tr class='green_bar'>
					<td width='1%' align="center">Nr</td>
					<td width='20%' align='left'>Nome</td>
					<td width='2%'>Skill</td>
					<td width='2%' title='Ruolo'>Pos</td>
				</tr>
				<?php
				$max_righe = 0;
				foreach ($id_player as $my_id)
				{
					if (isset($ar_player_allenato[$my_id]))
					{
						if ($ar_player_allenato[$my_id] == 5) //ALLENATORE portieri
						{
							$max_righe ++;
							$img_ruolo = "images/".$ar_immpos[$ar_player_pos[$my_id]];
							$titolo = $quale_ruolo[$ar_player_pos[$my_id]];
							echo "<tr>";
							echo "<td><input name='btn' class='brdportiere' value='$ar_player_nr[$my_id]' type='button' onclick='javascript:delete_training($my_id,3);' title='Clicca per togliere il giocatore'></td>";
							echo "<td>$ar_player_nome[$my_id]</td>";
							echo "<td align=center>$ar_player_skill[$my_id]</td>";
							echo "<td><img src=$img_ruolo width='18' heigth='16' border='0' title='$titolo'/></td>";
							echo "<input value='$my_id' type='hidden' id='tab_allena_3_$max_righe' >";
							echo "</tr>";
						}
					}
				}
				echo "</table>";
				$max_righe ++;
				for ($fine = $max_righe; $fine <= 10; $fine++)
				{
					echo "<table border=0 width='100%'>";
					echo "<tr>";
					echo "<td><input value='-1' type='hidden' id='tab_allena_3_$fine' >&nbsp;</td>";
					echo "</tr>";
					echo "</table>";
				}
			?>
			</div>
			<div class="contenuto_allena_bottom"></div>
	</fieldset>
</div>
<div class="div_allena">
	<fieldset>
		<legend class="legenda">Preparatore Atletico</legend>
			<div>
				<div class="div_sx_all">
					<span id='all_4' onmouseout='javascript:tooltip_hide();'>
						<?php 
							if (isset($nome_allenatori[10])) 
							{ 
								echo $nome_allenatori[10]; 
							}
							else
							{
								echo "- - -";
							}
						 ?>
					</span>
				</div>
				<div class="div_dx_all">
					<div class="div_barra_up"><?php if (isset($efficienza_allenatori[10])) { echo $efficienza_allenatori[10]; } ?>&nbsp;%</div>
					<div class="div_barra_down">
						<?php
						 	if (isset($efficienza_allenatori[10])) 
							{
								for ($x=0; $x<=75; $x++)
								{
									if ($x <= ($efficienza_allenatori[10]*.75))
									{
										echo "<img src='images/barretta_blu.png' />";
									}
									else
									{
										echo "<img src='images/barretta_bianca.png' />";
									}
								}
							}
						?>
					</div>
				</div>
			</div>
			<div class="contenuto_allena" id="contenuto_allena_4">
				<table border="0" width="100%">
				<tr class='green_bar'>
					<td width='1%' align="center">Nr</td>
					<td width='20%' align='left'>Nome</td>
					<td width='2%'>Skill</td>
					<td width='2%' title='Ruolo'>Pos</td>
				</tr>
				<?php
				$max_righe = 0;
				foreach ($id_player as $my_id)
				{
					if (isset($ar_player_allenato[$my_id]))
					{
						if ($ar_player_allenato[$my_id] == 10) // PREPARATORE ATLETICO
						{
							$max_righe ++;
							$img_ruolo = "images/".$ar_immpos[$ar_player_pos[$my_id]];
							$titolo = $quale_ruolo[$ar_player_pos[$my_id]];
							echo "<tr>";
							echo "<td><input name='btn' class='brdgioca' value='$ar_player_nr[$my_id]' type='button' onclick='javascript:delete_training($my_id,4);' title='Clicca per togliere il giocatore'></td>";
							echo "<td>$ar_player_nome[$my_id]</td>";
							echo "<td align=center>$ar_player_skill[$my_id]</td>";
							echo "<td><img src=$img_ruolo width='18' heigth='16' border='0' title='$titolo'/></td>";
							echo "<input value='$my_id' type='hidden' id='tab_allena_4_$max_righe' >";
							echo "</tr>";
						}
					}
				}
				echo "</table>";
				$max_righe ++;
				for ($fine = $max_righe; $fine <= 10; $fine++)
				{
					echo "<table border=0 width='100%'>";
					echo "<tr>";
					echo "<td><input value='-1' type='hidden' id='tab_allena_4_$fine' >&nbsp;</td>";
					echo "</tr>";
					echo "</table>";
				}
			?>
			</div>
			<div class="contenuto_allena_bottom"></div>
	</fieldset>
</div>

