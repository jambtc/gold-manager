<?php
	require_once('auth.php');
	$id = $_REQUEST['id'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<link rel="stylesheet" type="text/css" href="statistiche.css" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>

</head>

<?php 
	include "connect_db.php";
	$nome_team = $_SESSION['SESS_TEAM'];
	//carico il budget
	$qry = "SELECT * FROM members WHERE team=\"$nome_team\"";
	$result = mysql_query($qry);
		
	if($result)
	{
		$member = mysql_fetch_assoc($result);
		$budget = $member['budget'];
	}
	
	$alink = array("staff_motiva.php?id=$id","staff_motiva.php?id=$id&tpfrm=1","staff_motiva.php?id=$id&tpfrm=2");
	if (!isset($_REQUEST['tpfrm']))
	{
			$alink[0] = "#";
			$apagina = 0;
			$aclasse[0]="a2";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
	} else {
			$alink[$_REQUEST['tpfrm']] = "#";
			$apagina = $_REQUEST['tpfrm'];
			$aclasse[0]="a3";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
			$aclasse[$_REQUEST['tpfrm']]="a2";
	}
	?>
	
<body style="background:trasparent;"> 

	<?php 
		if ($id != 0)
		{
	?>
	
	<table border="0" width="100%">
	<tr>
		<td><a href="" onclick="location.href='<?php echo $alink[0];?>'; return false;" class="button" target="ApriFin">stipendio</a>
		</td>
		<td><a href="" onclick="location.href='<?php echo $alink[1];?>'; return false;" class="button" target="ApriFin">addestramento</a>
		</td>
		<td><a href="" onclick="location.href='<?php echo $alink[2];?>'; return false;" class="button" target="ApriFin">esonero</a>
		</td>
	</tr>
	</table>
	
	<div class="content-area"> 
		<span id="motiva"> 
		
		<?php 
		$result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" AND s_id_staff=\"$id\" ");
		if (!$result)
		{
			echo 'Errore nella query: ' . mysql_error();
			exit();
		}
				
		$row = mysql_fetch_array($result);
		$wstip = number_format($row['s_sti'],0,",",".");
		$wstip_15 = number_format(($row['s_sti']*15),0,",",".");
		
		if ($wstip == 0) {$wstip = "";}
		
		echo "<table border='0'>";
		echo "<tr style='color:#000000;'>";
		echo "<th align='left' width='150'>Staff</th>";
		echo "<th align='left'>Motivazione</th>";
		echo "<th align='left'>Stipendio</th>";
		echo "</tr>";
		echo "<tr style='color:#0000ff;'>";
		echo "<th align='left'>$row[s_descrizione]</th>";
		echo "<th align='left'>$row[s_mot]</th>";
		echo "<th align='left'>�. $wstip</th>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
					
		if (!isset($_REQUEST['tpfrm']))
		{
			if ($id != 0)
			{
				if ($row['s_addestramento'] == 15)
				{
		?>
					<h4>Vuoi offrire un aumento di stipendio al tuo <font color="#FF0000"><?php echo $row['s_descrizione'];?></font>? Cos� facendo aumenterai la sua motivazione.</h4>
					<HR />
					<table border="0" cellpadding="0" cellspacing="5" style="color:#000000;">
					<tr>
						<th rowspan="2" align="right"><a href="" onclick="javascript:AumentaStipendio(5); return false;" class="button">Aumenta stipendio</a></th>
						<td>&nbsp;</td>
						<th align="right">Nuovo stipendio:</th>
						<td><div class="td_stipendio" id='td_sti'>�. <?php echo $row['s_sti']; ?></div></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<th align="right">Motivazione:</th>
						<td><div class="td_stipendio" id='td_mot'><?php echo $row['s_mot']; ?></div></td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						
					
						<td>
							<img id='puloff' border='0' width='33' src="images/salva_spento.png"/>
								<a href="#" onclick="javascript:Salva_Staff();">
									<img id='pulon' name='pulon' border='0' width='33' src='images/salva_acceso.png' title="Conferma l'aumento dello stipendio" > 
								</a>
						</td>
						<td>
						<input type="hidden" id="ws_id" value="<?php echo $id; ?>" />
						<input type="hidden" id="ws_mot" value="<?php echo $row['s_mot']; ?>" />
						</td>
						<td>
						<input type="hidden" id="ws_sti" value="<?php echo $row['s_sti']; ?>" />
						</td>
						<script>Nascondi_Elemento('pulon');</script>
					</tr>
					</table>
		<?php
				}
				else
				{
		?>
					<h4>Il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font> sta seguendo un corso di aggiornamento. Al momento il suo rendimento non � ottimale. Mancano <?php echo $row['s_addestramento']; ?> <?php if ($row['s_addestramento'] > 1) {echo "giorni";}else{echo "giorno";} ?> per tornare alla piena operativit�.<br /></h4>
		<?php 
				} 
			}
		}
		elseif ($_REQUEST['tpfrm'] == 1)
		{
			if ($id != 0)
			{
				if ($row['s_addestramento'] == 15)
				{
					if ($row['s_sti']*15 <= $budget)
					{
		?>
						<h4>Vuoi addestrare il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font> per �. <?php echo $wstip_15; ?>? Per 2 settimane non render� al meglio.</h4>
						<HR />
						<input type="hidden" name="ws_id" value="<?php echo $id; ?>" />
						
						<!-- <input type="button" name="uscita" value="Si"  class="fieldbutton" onclick="javascript:Istruisci(ws_id);"/>
						<input type="button" name="uscita" value="No"  class="fieldbutton" onclick="javascript:window.top.location.href='form_staff.php';"/> -->
						<table align="center">
						<tr>
							<td>
								<a href="" onclick="javascript:Istruisci_Staff(ws_id); return false;" class="button">si</a>
							</td>
						</tr>
						</table>		
		<?php		
					}
					else
					{
		?>
						<h4>Non hai credito sufficiente per iscrivere ad un corso di addestramento il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font>.</h4>
		
		<?php
					}
				}
				else
				{
		?>
					<h4>Il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font> sta gi� seguendo un corso di aggiornamento. Devi attendere la prossima stagione per un'altro corso.</h4>
		<?php 	
				}
			} 
		}
		elseif ($_REQUEST['tpfrm'] == 2)
		{
			if ($id != 0)
			{ 
		?>
			<h4>Vuoi licenziare il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font>?</h4>
			<HR />
			<input type="hidden" name="ws_id" value="<?php echo $id; ?>" />
			<!-- <input type="button" name="uscita" value="Si"  class="fieldbutton" onclick="javascript:Licenzia(ws_id);"/>
			<input type="button" name="uscita" value="No"  class="fieldbutton" onclick="javascript:window.top.location.href='form_staff.php';"/>	-->
			<table align="center">
			<tr>
				<td>
					<a href="" onclick="javascript:Licenzia_Staff(ws_id); return false;" class="button">si</a>
				</td>
			</tr>
		</table>	
		<?php 
			}
		}
		?>
		</span>
	</div>
	
<?php 
	}
?>
	
