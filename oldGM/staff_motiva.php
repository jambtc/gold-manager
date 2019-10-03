<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />

<script type="text/javascript">
function Aumenta(quanto)
{
	variazione = eval(document.getElementById('ws_mot').value) + eval(quanto);
	
	if (variazione <= 100)
	{
		stipendio = document.getElementById('ws_sti').value;
		aumento = eval(stipendio)/100 * eval(quanto);
		
		document.getElementById('puloff').style.display = "none";
		document.getElementById('pulon').style.display = "inline";
		
		nuovo_stipendio = Math.round(eval(aumento)  + eval(stipendio));
		
		document.getElementById('ws_mot').value = variazione ; 
		document.getElementById('ws_sti').value = nuovo_stipendio ; 
		
		document.getElementById('td_motivazione').innerHTML = variazione;
		document.getElementById('td_stipendio').innerHTML = "€. "+nuovo_stipendio;
	}
	
}
	
function Salva()
{
	id = document.getElementById('ws_id').value;
	sti = document.getElementById('ws_sti').value;
	mot = document.getElementById('ws_mot').value;
			
	indirizzo="data_staff_modifica.php?id="+id+"&sti="+sti+"&mot="+mot;
	
	window.top.location.href=indirizzo;
	
}
function nascondi(arg)
{
	document.getElementById(arg).style.display = 'none';
}
function Istruisci(arg1)
{
  	id_staff=arg1.value;
	indirizzo="data_staff_istruisci.php?id="+id_staff;
	window.top.location.href=indirizzo;
}
function Licenzia(arg1)
{
  id_staff=arg1.value;
   
  if (!(confirm("ATTENZIONE stai per licenziare un elemento del tuo staff. Perderai tutti i suoi dati relativi alle statistiche! Continuo?")))
  {
    return false;
  }
  else
  {
	  indirizzo="data_staff_elimina.php?id="+id_staff;
	  window.top.location.href=indirizzo;
  }
}
</script>

<?php 
	$quale_css = "css/index_corpo".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index_corpo1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
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
	
	$id = $_REQUEST['id'];
	
	$alink = array("staff_motiva.php?id=$id","staff_motiva.php?id=$id&pagina=1","staff_motiva.php?id=$id&pagina=2");
	if (!isset($_REQUEST['pagina']))
	{
			$alink[0] = "#";
			$apagina = 0;
			$aclasse[0]="a2";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
	} else {
			$alink[$_REQUEST['pagina']] = "#";
			$apagina = $_REQUEST['pagina'];
			$aclasse[0]="a3";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
			$aclasse[$_REQUEST['pagina']]="a2";
	}
	?>
	
	<span class="top-label">  
		<span class="label-txt"><a class="<?php echo $aclasse[0]; ?>" href="<?php echo $alink[0]; ?>"  target="ApriFinestra">Motiva</a></span>
		<span class="label-txt"><a class="<?php echo $aclasse[1]; ?>" href="<?php echo $alink[1]; ?>"  target="ApriFinestra">Addestra</a></span>
		<span class="label-txt"><a class="<?php echo $aclasse[2]; ?>" href="<?php echo $alink[2]; ?>"  target="ApriFinestra">Licenzia</a></span>
	</span>
	
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
		echo "<th align='left'>€. $wstip</th>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
					
		if (!isset($_REQUEST['pagina']))
		{
			if ($id != 0)
			{
				if ($row['s_addestramento'] == 15)
				{
		?>
					<h4>Vuoi offrire un aumento di stipendio al <font color="#FF0000"><?php echo $row['s_descrizione'];?></font>? Così facendo aumenterai la sua motivazione.</h4>
					<HR />
					<table border="0" cellpadding="0" style="color:#000000;">
					<tr>
						<td>Aumenta lo stipendio:</td>
						<td>
							<a href="#" onclick="javascript:Aumenta(5);">
								<img src='images/4Plus.gif' border='0' width='30'>
							</a>
						</td>
					</tr>
					<tr>
						<td>Nuovo stipendio: </td>
						<td><div id="td_stipendio">€. <?php echo $row['s_sti']; ?></div></td>
					</tr>
					<tr>
						<td>Motivazione:</td>
						<td><div id="td_motivazione"><?php echo $row['s_mot']; ?></div></td>
					
						<td>
							<img id='puloff' border='0' width='33' src="images/salva_spento.png"/>
								<a href="#" onclick="javascript:Salva();">
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
						<script>nascondi('pulon');</script>
					</tr>
					</table>
		<?php
				}
				else
				{
		?>
					<h4>Il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font> sta seguendo un corso di aggiornamento. Al momento il suo rendimento non è ottimale. Mancano <?php echo $row['s_addestramento']; ?> <?php if ($row['s_addestramento'] > 1) {echo "giorni";}else{echo "giorno";} ?> per tornare alla piena operatività.<br /></h4>
		<?php 
				} 
			}
		}
		elseif ($_REQUEST['pagina'] == 1)
		{
			if ($id != 0)
			{
				if ($row['s_addestramento'] == 15)
				{
					if ($row['s_sti']*15 <= $budget)
					{
		?>
						<h4>Vuoi addestrare il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font> per €. <?php echo $wstip_15; ?>? Per 2 settimane non renderà al meglio.</h4>
						<HR />
						<input type="hidden" name="ws_id" value="<?php echo $id; ?>" />
						<input type="button" name="uscita" value="Si"  class="fieldbutton" onclick="javascript:Istruisci(ws_id);"/>
						<input type="button" name="uscita" value="No"  class="fieldbutton" onclick="javascript:window.top.location.href='form_staff.php';"/>		
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
					<h4>Il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font> sta già seguendo un corso di aggiornamento. Devi attendere la prossima stagione per un'altro corso.</h4>
		<?php 	
				}
			} 
		}
		elseif ($_REQUEST['pagina'] == 2)
		{
			if ($id != 0)
			{ 
		?>
			<h4>Vuoi licenziare il tuo dipendente <font color="#FF0000"><?php echo $row['s_descrizione'];?></font>?</h4>
			<HR />
			<input type="hidden" name="ws_id" value="<?php echo $id; ?>" />
			<input type="button" name="uscita" value="Si"  class="fieldbutton" onclick="javascript:Licenzia(ws_id);"/>
			<input type="button" name="uscita" value="No"  class="fieldbutton" onclick="javascript:window.top.location.href='form_staff.php';"/>	
		<?php 
			}
		}
		?>
		</span>
	</div>