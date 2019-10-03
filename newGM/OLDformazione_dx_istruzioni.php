<?php
require_once('auth.php');

$disable_sos = "";
$disable_reg = "";

$lista_impegno = array("impegno al 50%","impegno al 75%","impegno al 100%","impegno al 125%","impegno al 150%");
$lista_fuorigioco = array("fuorigioco SI","fuorigioco NO");

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

// CREO ARRAY DEI MINUTI
for ($x=1; $x <91; $x++)
{
	$minuti[] = $x;
}
// CREO ELENCO DELLE CONDIZIONI
$condi_result = mysql_query("SELECT * FROM condizioni WHERE 1 ORDER BY id");
if (!$condi_result) {
    echo 'Errore nella query Condizioni: ' . mysql_error();
    exit();
}
while   ($row   =   mysql_fetch_array($condi_result))
{
	$Cond_id[] = $row['id'];
	$Cond_desc[] = $row['descrizione'];
}

// CREO ELENCO DELLE TATTICHE E MARCATURE
$tattica_result = mysql_query("SELECT * FROM bonus_tattica WHERE 1 ORDER BY t_id");
if (!$tattica_result) {
    echo 'Errore nella query bonus tattica: ' . mysql_error();
    exit();
}
$conta = 1;
while   ($row   =   mysql_fetch_array($tattica_result))
{
	$ListaTattica[$conta] = $row['t_descrizione'];
	$conta++;
}
$listaRegole = array_merge($ListaTattica, $lista_impegno, $lista_fuorigioco);

// CARICO LA FORMAZIONE SU CUI AGIRE
$tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\" ");
if (!$tipo_result) {
    echo 'Errore nella query tattica: ' . mysql_error();
    exit();
}
$row   =   mysql_fetch_array($tipo_result);
$QualeFormazione = $row['t_formazione']; 

if ($QualeFormazione == "")
{
	$QualeFormazione = "Formazione 1";
}
//CARICO LA FORMAZIONE SELEZIONATA DEL TEAM
$formazione_result = mysql_query("SELECT * FROM formazione WHERE f_id_team=\"$nome_team\" AND f_formazione=\"$QualeFormazione\" ");
if (!$formazione_result) {
		echo 'Errore nella query: ' . mysql_error();
		exit();
}
$row   =   mysql_fetch_array($formazione_result);
$conta = 1;
while ($conta <70)
{
	$riquadro[$conta] = $row[$appoggio[$conta]];
	if ($riquadro[$conta] == "")
	{
		$riquadro[$conta] = 0 ;
	}
	$conta ++;
}

//CARICA I DATI DEI GIOCATORI IN CAMPO: MAGLIA, NOME, e quelli in panchina
$conta = 1;
foreach ($riquadro as $riga)
{
	if ($riga != 0)
	{
		$ricerca = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND nr=\"$riga\" ");
		if (!$ricerca)
		{
			echo 'Errore nella query RICERCA DATI GIOCATORI: ' . mysql_error();
			exit();
		}
		$rig   =   mysql_fetch_array($ricerca);
		
		if ($conta <65)
		{
			$chi_esce_nr[] = $rig['nr'];
			$chi_esce_desc[] = $rig['nr'].") ".$rig['nome']." ".$quale_ruolo[$rig['pos']];
		}
		else
		{
			$chi_entra_nr[] = $rig['nr'];
			$chi_entra_desc[] = $rig['nr'].") ".$rig['nome']." ".$quale_ruolo[$rig['pos']];
		}
	}
	$conta++;
}
// CARICO LE ISTRUZIONI
$istruzioni_result = mysql_query("SELECT * FROM istruzioni WHERE id_team=\"$nome_team\" ORDER BY min");
if (!$istruzioni_result) {
    echo 'Errore nella query istruzioni: ' . mysql_error();
    exit();
}

$regola_sos = 0;
$regola_reg = 0;
while   ($row   =   mysql_fetch_array($istruzioni_result))
{
	$id_regola[] = $row['id'];
	$tipologia[] = $row['tipologia'];
	
	$min[] = $row['min'];
	$condizione[] = $row['condizione'];
	$entra[] = $row['entra'];
	$esce[] = $row['esce'];
	
	$quale_regola[] = $row['regola'];
	//CONTROLLO CHE CI SIANO MAX 3 SOSTITUZIONI E MAX 5 REGOLE TOTALI
	if ($row['tipologia'] == 0)
	{
		$regola_sos ++;
	}
	$regola_reg ++;
}

if ($regola_sos == 3)
{
	$disable_sos = "disabled";
}
if ($regola_reg == 5)
{
	$disable_sos = "disabled";
	$disable_reg = "disabled";
}
?>
<div id='form_istruzioni'>
	
	<input type='button' id='sost' value='Sostituzione' <?php echo $disable_sos ?> onclick="javascript:viewDIV('0');" class='fieldbutton'  >
	<input type='button' id='rego' value='Regola' <?php echo $disable_reg ?> onclick="javascript:viewDIV('1');" class='fieldbutton'  >

	<hr />
	<div id='nuova_sostituzione' style="display:none;">
		<p style="background-color:#006600; color: #CCCCCC;">Inserisci Sostituzione</p>
		<table border='0' cellpadding='0' cellspacing='3' width='100%'>
		<tr>
			<th align="left">Minuto</th>
				<td>
					<?php 
						echo "<select name='minuti' id='minuti' class='fld_y'>";
						foreach ($minuti as $minuto)
						{
							echo "<option value='$minuto'>$minuto</option>";
						}
						echo "</select>";
					?>
				</td>
		</tr>
		<tr>
			<th align="left">Condizione</th>
				<td>
					<?php 
						$x = 0;
						echo "<select name='condizioni' id='condizioni' class='fld_y'>";
						foreach ($Cond_desc as $msg)
						{
							echo "<option value='$Cond_id[$x]'>$msg</option>";
							$x ++;
						}
						echo "</select>";
					?>
				</td>
		</tr>
		<tr>
			<th align="left">Entra</th>
				<td>
					<?php 
						$x = 0;
						echo "<select name='entra' id='entra' class='fld_y'>";
						foreach ($chi_entra_desc as $msg)
						{
							echo "<option value='$chi_entra_nr[$x]'>$msg</option>";
							$x ++;
						}
						echo "</select>";
					?>
				</td>
		</tr>
		<tr>
			<th align="left">Esce</th>
				<td>
					<?php 
						$x = 0;
						echo "<select name='esce' id='esce' class='fld_y'>";
						foreach ($chi_esce_desc as $msg)
						{
							echo "<option value='$chi_esce_nr[$x]'>$msg</option>";
							$x ++;
						}
						echo "</select>";
					?>
				</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<th align="left">
			<input type='button' id='conf' value='Conferma' onclick="javascript:insRegola(0);" class='fieldbutton'  >
			<div class='ins_reg' style="display:;"></div>
			</th>
		</tr>
		</table>
		<hr />
	</div>
	
	<div id='nuova_regola' style="display:none;">
		<p style="background-color:#006600; color: #CCCCCC;">Inserisci Regola</p>
		<table border='0' cellpadding='0' cellspacing='3' width='100%'>
		<tr>
			<th align="left">Minuto</th>
				<td>
					<?php 
						echo "<select name='minuti2' id='minuti2' class='fld_y'>";
						foreach ($minuti as $minuto)
						{
							echo "<option value='$minuto'>$minuto</option>";
						}
						echo "</select>";
					?>
				</td>
		</tr>
		<tr>
			<th align="left">Condizione</th>
				<td>
					<?php 
						$x = 0;
						echo "<select name='condizioni2' id='condizioni2' class='fld_y'>";
						foreach ($Cond_desc as $msg)
						{
							echo "<option value='$Cond_id[$x]'>$msg</option>";
							$x ++;
						}
						echo "</select>";
					?>
				</td>
		</tr>
		<tr>
			<th align="left">Regola</th>
				<td>
					<?php 
						$x = 0;
						echo "<select name='regola' id='regola' class='fld_y'>";
						foreach ($listaRegole as $msg)
						{
							echo "<option value='$x'>$msg</option>";
							$x ++;
						}
						echo "</select>";
					?>
				</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
			<th align="left">
			<input type='button' id='conf' value='Conferma' onclick="javascript:insRegola(1);" class='fieldbutton'  >
			</th>
		</tr>
		</table>
		<hr />
	</div>
	
	<div id='form_regole' class='form_regole'>
	<?php
		if (isset($tipologia) && is_array($tipologia))
		{
			echo "<table border='0' cellpadding='0' cellspacing='2' width='100%'>";
			$cc = 0;
			foreach ($tipologia as $tipo)
			{
				$mostra_condizione = $Cond_desc[$condizione[$cc]];
				$mostra_regola = $listaRegole[$quale_regola[$cc]];
				if ($tipo == 0)
				{
					echo "<tr>";
						echo "<th>". ($cc+1) .")</th>";
						echo "<th>Min.</th><th align='left'>Condizione</th><th>Entra</th><th>Esce</th>";
						echo "<td align='center' rowspan='2'><a href='#' onclick='javascript:delRegola($id_regola[$cc]);' ><img src='images/cancel_f2.png' border='0' width='20' title='Elimina la regola'></a></td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td></td>";
						echo "<td>$min[$cc]</td><td>$mostra_condizione</td><td align='center'>$entra[$cc]</td><td align='center'>$esce[$cc]</td>";
					echo "</tr>";
					echo "<tr><td>&nbsp;</td></tr>";
				}
				else
				{
					echo "<tr>";
						echo "<th>". ($cc+1) .")</th>";
						echo "<th>Min.</th><th align='left'>Condizione</th><th colspan='2' align='left'>Nuova Regola</th>";
						echo "<td align='center' rowspan='2'><a href='#' onclick='javascript:delRegola($id_regola[$cc]);' ><img src='images/cancel_f2.png' border='0' width='20' title='Elimina la regola'></a></td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td></td>";
						echo "<td>$min[$cc]</td><td>$mostra_condizione</td><td>$mostra_regola</td>";
					echo "</tr>";
					echo "<tr><td>&nbsp;</td></tr>";
				}
				$cc++;
			}
			echo "</table>";
		}
	?>
	</div>
</div>



	
<?php 	
echo "</div>";
?>



