<?php
	require_once('auth.php');
?>
<!-- <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<link rel="stylesheet" type="text/css" href="statistiche.css" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>
</head>
-->
<?php 
	
	
	if (!isset($_REQUEST['id']))
	{
		$id = 0;
	}
	else
	{
		include "connect_db.php";	
		$id = $_REQUEST['id'];
	}
	
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
		$tentativi = $row['s_contrattazioni'];
		$attivo_contra = "javascript:Contratta_Candidato(ws_id); return false;";
		$attivo_assumi = "javascript:Assumi_Candidato(ws_id); return false;";
		$btn_contra = "button";
		$btn_assumi = "button";
		$esito = "";
		$tabella = array(0,5,11,18,23);
		$wstip_nuovo = $wstip;
		$sti_nuovo = $row['s_sti'];
		
		if ($tentativi == -1)
		{
			$attivo_contra = "javascript: return false;";
			$attivo_assumi = "javascript: return false;";
			$btn_contra = "buttongray";
			$btn_assumi = "buttongray";
			$esito = "<img src='images/negativo.png' border='0' />";
			$wstip_nuovo = "0";
			$sti_nuovo = "0";
		}
		elseif ($tentativi == 0)
		{
			$attivo_contra = "javascript: return false;";
			$btn_contra = "buttongray";
		}
		
		if ($tentativi >= 0 and $tentativi < 100)
		{
			$esito = "<img src='images/positivo.png' border='0' />";
			$sti_nuovo = round($row['s_sti']-$row['s_sti']*$tabella[$row['s_contrattazioni']]/100,0);
			$wstip_nuovo = number_format($sti_nuovo,0,",",".");
		}
		echo "<input type='hidden' id='ws_id' name='ws_id' value='$row[s_id]'>";
		echo "<input type='hidden' id='ws_sti' name='ws_sti' value='$row[s_sti]'>";
		echo "<input type='hidden' id='ws_nuovo' name='ws_nuovo' value='$sti_nuovo'>";
		echo "<input type='hidden' id='ws_tenta' name='ws_tenta' value='$tentativi'>";
		
		
		echo "<table border='0' width='100%' style='font-size: 16px;'>";
		echo "<tr style='color:#000000;'>";
		echo "<th align='left' width='20%'>Staff</th>";
		echo "<th align='left' widt='20%'>Nome</th>";
		echo "<th align='center'>Abi.</th>";
		echo "<th align='center'>Esp.</th>";
		echo "<th align='center'>Mot.</th>";
		echo "<th align='left'>Carattere</th>";
		echo "<th align='left'>Filosofia</th>";
		echo "<th align='center'>Richiesta</th>";
		echo "</tr>";
			
		echo "<tr style='color:#0000ff;'>";
		$descri = str_replace(chr(32),"&nbsp",$row['s_descrizione']);
		echo "<th align='left' style='color:#0f00f0'>$descri</th>";
		echo "<th align='left'>$row[s_nome]</th>";
		echo "<th align='center'>$row[s_abi]</th>";
		echo "<th align='center'>$row[s_esp]</th>";
		echo "<th align='center'>$row[s_mot]</th>";
		echo "<th align='left'>$row[s_car]</th>";
		if ($row['s_id_staff'] == 1)
		{
			echo "<th align='left'>$row[s_fil]</th>";
		}
		else
		{
			echo "<td>&nbsp;</td>";
		}
		echo "<th align='center'>&euro;. $wstip</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan=8><hr></td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<table border='0' width='100%' style='font-size: 16px;'>";
		echo "<tr style='color:#000000;'>";
		echo "<th align='center' >Form</th>";
		echo "<th align='center' >Cond</th>";
		echo "<th align='center'>PO</th>";
		echo "<th align='center'>DF</th>";
		echo "<th align='center'>CN</th>";
		echo "<th align='center'>PA</th>";
		echo "<th align='center'>RG</th>";
		echo "<th align='center'>CR</th>";
		echo "<th align='center'>TC</th>";
		echo "<th align='center'>TR</th>";
		echo "<th align='center'>TAL</th>";
		echo "</tr>";
			
		echo "<tr style='color:#0000ff;'>";
		echo "<th align='center'>$row[s_for]%</th>";
		echo "<th align='center'>$row[s_con]%</th>";
		echo "<th align='center'>$row[s_po]%</th>";
		echo "<th align='center'>$row[s_df]%</th>";
		echo "<th align='center'>$row[s_cn]%</th>";
		echo "<th align='center'>$row[s_pa]%</th>";
		echo "<th align='center'>$row[s_rg]%</th>";
		echo "<th align='center'>$row[s_cr]%</th>";
		echo "<th align='center'>$row[s_tc]%</th>";
		echo "<th align='center'>$row[s_tr]%</th>";
		echo "<th align='center'>$row[s_tal]%</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan=11><hr></td>";
		echo "</tr>";
		echo "</table>";
		?>
		<div style="float:left; width:50%;">
		<?php 
			echo "<table border='0' width='100%' style='font-size: 16px;'>";
			echo "<tr>";
			echo "<th align='left' width='10%'>Tentativi&nbsp;rimanenti:</th>";
			echo "<th>0%&nbsp;";
			for ($x=0; $x<=50; $x++)
			{
				if ($x <= ($row['s_contrattazioni']*.5))
				{
					echo "<img src='images/barretta_blu.png' width='4' />";
				}
				else
				{
					echo "<img src='images/barretta_bianca.png' />";
				}
			}
			echo "&nbsp;100%</th>";
			echo "</tr>";
			echo "</table>";
		?>
		</div>
		<div style="float:right; width:50%; ">
		<?php 
			echo "<table border='0' width='100%' style='font-size: 16px;'>";
			echo "<tr>";
			echo "<a href='' onclick='$attivo_contra' class='$btn_contra'>contrattazione</a>";
			echo "</tr>";
			echo "</table>";
		?>
		</div>
		<?php 
		echo "<table border='0' width='100%' style='font-size: 16px;'>";
		echo "<tr style='color:#000000;'>";
		echo "<tr>";
		echo "<td colspan=8><hr></td>";
		echo "</tr>";
		echo "</table>";
		
		/*
		echo "<table border='0' width='100%'>";
		echo "<tr style='color:#000000;'>";
		echo "<th align='left'>Tentativi&nbsp;rimanenti:</th>";
		echo "<th align='left'>$tentativi</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td colspan=8><hr></td>";
		echo "<td>";
		
		echo "<a href='' onclick='$attivo_contra' class='$btn_contra'>contrattazione</a>";
		
		echo"</td>";
		echo "</tr>";	
		echo "</table>";

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
		echo "<td colspan=2>";

		echo "<a href='' onclick='$attivo_assumi' class='$btn_assumi'>assumi candidato</a>";
		
		echo "</td>";
		echo "</tr>";	
		echo "<input type='hidden' id='ws_id' name='ws_id' value='$row[s_id]'>";
		echo "<input type='hidden' id='ws_sti' name='ws_sti' value='$row[s_sti]'>";
		echo "<input type='hidden' id='ws_nuovo' name='ws_nuovo' value='$sti_nuovo'>";
		echo "<input type='hidden' id='ws_tenta' name='ws_tenta' value='$tentativi'>";
		echo "</table>";
		*/
	}
?>
