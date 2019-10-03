<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
<?php 
	$quale_css = "css/index_corpo".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index_corpo1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
<script type="text/javascript">
function Contratta()
{
	id_staff = document.getElementById('ws_id').value;
	tentativi = document.getElementById('ws_tenta').value;
	stipendio = document.getElementById('ws_sti').value;
	contrattazioni = 4 - tentativi;
	
	if (tentativi == 4)
	{
		probabilita = 90;
	}
	else if (tentativi == 3)
	{
		probabilita = 80;
	}
	else if (tentativi == 2)
	{
		probabilita = 70;
	}
	else if (tentativi == 1)
	{
		probabilita = 60;
	}
	else if (tentativi == 0)
	{
		return;
	}
	
	variabile = Math.round(100*Math.random());
	
	if (variabile <= probabilita) // tentativo riuscito
	{
		document.getElementById('esito_trat').innerHTML = "Positivo!";
		contrattazioni = contrattazioni +1;
	}
	else	// tentativo fallito
	{
		document.getElementById('esito_trat').innerHTML = "Negativo!";
		contrattazioni = 5;
	}
		
	//document.getElementById('td_motivazione').innerHTML = variazione;
	//document.getElementById('td_stipendio').innerHTML = "€. "+nuovo_stipendio;
  indirizzo="data_staff_contratta.php?id="+id_staff+"&contra="+contrattazioni;
  
  //window.parent.location.href=indirizzo;
  window.parent.frames['contra'].location.href=indirizzo;
 
}
function Assumi()
{
	id_staff = document.getElementById('ws_id').value;
	stipendio = document.getElementById('ws_nuovo').value;
	
	indirizzo="data_staff_assumi.php?id="+id_staff+"&stip="+stipendio;
	
	//window.parent.frames['contra'].location.href=indirizzo;
	window.top.location.href=indirizzo;
}
 
</script>


</head>
<?php 
	include "connect_db.php";
	
	$id = $_REQUEST['id'];
	
	if ($id != 0)
	{
		$result = mysql_query("SELECT * FROM staff_mercato WHERE s_id=\"$id\" ");
		if (!$result)
		{
			echo 'Errore nella query: ' . mysql_error();
			exit();
		}
	
		$row = mysql_fetch_array($result);
		$wstip = number_format($row['s_sti'],0,",",".");
		$tentativi = 4 - $row['s_contrattazioni'];
		$attivo_contra = "enabled";
		$attivo_assumi = "enabled";
		$esito = "";
		$tabella = array(0,5,11,18,23);
		$wstip_nuovo = $wstip;
		$sti_nuovo = $row['s_sti'];
		
		if ($tentativi == -1)
		{
			$attivo_contra = "disabled";
			$attivo_assumi = "disabled";
			$esito = "<h5>Rifiutata!</h5>";
			$wstip_nuovo = "0";
			$sti_nuovo = "0";
		}
		elseif ($tentativi == 0)
		{
			$attivo_contra = "disabled";
		}
		
		if ($tentativi >= 0 and $tentativi < 4)
		{
			$esito = "Positivo!";
			$sti_nuovo = round($row['s_sti']-$row['s_sti']*$tabella[$row['s_contrattazioni']]/100,0);
			$wstip_nuovo = number_format($sti_nuovo,0,",",".");
		}
		
		echo "<table border='0'>";
		echo "<tr style='color:#000000;'>";
		echo "<th align='left' width='150'>Staff</th>";
		//echo "<th align='left'>Abi.</th>";
		//echo "<th align='left'>Esp.</th>";
		//echo "<th align='left'>Mot.</th>";
		echo "<th align='left'>Carattere</th>";
		echo "<th align='left'>Filosofia</th>";
		echo "<th align='left'>Richiesta</th>";
		echo "</tr>";
			
		echo "<tr style='color:#0000ff;'>";
		echo "<th align='left'>$row[s_descrizione]</th>";
		//echo "<th align='left'>$row[s_abi]</th>";
		//echo "<th align='left'>$row[s_esp]</th>";
		//echo "<th align='left'>$row[s_mot]</th>";
		echo "<th align='left'>$row[s_car]</th>";
		echo "<th align='left'>$row[s_fil]</th>";
		echo "<th align='left'>€. $wstip</th>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
		echo "<br>";		
		
		echo "<table border='0'>";

		echo "<tr style='color:#000000;'>";
		echo "<th align='left'>Tentativi&nbsp;rimanenti:</th>";
		echo "<th align='left'>$tentativi</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan=2><input type='button' name='AVVIA' $attivo_contra value='Avvia Contrattazione' class='contra' onclick='javascript:Contratta();'/></td>";
		echo "</tr>";	
		echo "</table>";
		echo "<hr>";	

		echo "<table border='0'>";
		echo "<tr style='color:#000000;'>";
		echo "<th align='left'>Esito Trattativa:</th>";
		echo "<th align='left'><div id='esito_trat'>$esito</div></th>";
		echo "</tr>";
		
		echo "<tr style='color:#000000;'>";
		echo "<th align='left'>Nuovo&nbsp;Stipendio:</th>";
		echo "<th align='left'>€. $wstip_nuovo</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan=2><input type='button' name='ASSUMI' $attivo_assumi value='Assumi Candidato' class='contra' onclick='javascript:Assumi();'/></td>";
		echo "</tr>";	
		echo "<input type='hidden' id='ws_id' name='ws_id' value='$row[s_id]'>";
		echo "<input type='hidden' id='ws_sti' name='ws_sti' value='$row[s_sti]'>";
		echo "<input type='hidden' id='ws_nuovo' name='ws_nuovo' value='$sti_nuovo'>";
		echo "<input type='hidden' id='ws_tenta' name='ws_tenta' value='$tentativi'>";
		echo "</table>";
	}
?>
