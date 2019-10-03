<?php
	require_once('../auth.php');
	include "../connect_db.php";
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$tipologia = $_REQUEST['tip'];
	$formaz = $_REQUEST['formaz'];
	
	if ($tipologia == 0)
	{
		$min = $_REQUEST['minu'];
		$cond = $_REQUEST['cond'];
		$entra = $_REQUEST['entra'];
		$esce = $_REQUEST['esce'];
	
		$qry = "INSERT INTO istruzioni (id_team, formazione, tipologia, min, condizione, entra, esce) 
					VALUES (\"$nome_team\",\"$formaz\",'$tipologia','$min','$cond','$entra','$esce')";
		$result = mysql_query($qry);
		
		if (!$result)
		{
			echo 'Errore nella query inserimento SOSTITUZIONE: ' . mysql_error();
			exit();
		}
	}
	else
	{
		$min = $_REQUEST['minu'];
		$cond = $_REQUEST['cond'];
		$regola = $_REQUEST['rego'];
	
		$qry = "INSERT INTO istruzioni (id_team, formazione, tipologia, min, condizione, regola) 
					VALUES (\"$nome_team\",\"$formaz\",'$tipologia','$min','$cond','$regola')";
		$result = mysql_query($qry);
		
		if (!$result)
		{
			echo 'Errore nella query inserimento REGOLA: ' . mysql_error();
			exit();
		}
	}

//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY RIQUADRO CHE CONTERRA' IL NUMERO DI MAGLIA
$appoggio= array('f_id_team','f_1','f_2','f_3','f_4','f_5','f_6','f_7','f_8','f_9','f_10',
					     'f_11','f_12','f_13','f_14','f_15','f_16','f_17','f_18','f_19','f_20',
						 'f_21','f_22','f_23','f_24','f_25','f_26','f_27','f_28','f_29','f_30',
						 'f_31','f_32','f_33','f_34','f_35','f_36','f_37','f_38','f_39','f_40',
						 'f_41','f_42','f_43','f_44','f_45','f_46','f_47','f_48','f_49','f_50',
						 'f_51','f_52','f_53','f_54','f_55','f_56','f_57','f_58','f_59','f_60',
						 'f_61','f_62','f_63','f_64','f_65','f_66','f_67','f_68','f_69','f_70',
						 'f_71','f_72','f_73',
						 'f_formazione');


$disable_sos = "";
$disable_reg = "";

$lista_impegno = array("Impegno al 50%","Impegno al 75%","Impegno al 100%","Impegno al 125%","Impegno al 150%");
$lista_fuorigioco = array("Fuorigioco SI","Fuorigioco NO");

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


// CARICO LE ISTRUZIONI
$istruzioni_result = mysql_query("SELECT * FROM istruzioni WHERE id_team=\"$nome_team\" AND formazione=\"$QualeFormazione\" ORDER BY min");
if (!$istruzioni_result) {
    echo 'Errore nella query istruzioni: ' . mysql_error();
    exit();
}

$regola_sos = 0;
$regola_reg = 0;
while   ($row   =   mysql_fetch_array($istruzioni_result))
{
	$id_regola[] = $row['id'];
	$tipo_regola[] = $row['tipologia'];
	
	$ar_min[] = $row['min'];
	$condizione[] = $row['condizione'];
	$ar_entra[] = $row['entra'];
	$ar_esce[] = $row['esce'];
	
	$quale_regola[] = $row['regola'];
	//CONTROLLO CHE CI SIANO MAX 3 SOSTITUZIONI E MAX 5 REGOLE TOTALI
	if ($row['tipologia'] == 0)
	{
		$regola_sos ++;
	}
	$regola_reg ++;
}
if (!isset($ar_esce)){$ar_esce[]="";}
if (!isset($ar_entra)){$ar_entra[]="";}


if ($regola_sos >= 3)
{
	$disable_sos = "disabled";
}
if ($regola_reg >= 5)
{
	$disable_sos = "disabled";
	$disable_reg = "disabled";
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
			if (!in_array($rig['nr'],$ar_esce))
			{
				$chi_esce_nr[] = $rig['nr'];
				$chi_esce_desc[] = $rig['nr'].") ".$rig['nome']." ".$quale_ruolo[$rig['pos']];
			}
		}
		else
		{
			if (!in_array($rig['nr'],$ar_entra))
			{
				$chi_entra_nr[] = $rig['nr'];
				$chi_entra_desc[] = $rig['nr'].") ".$rig['nome']." ".$quale_ruolo[$rig['pos']];
			}
		}
	}
	$conta++;
}
?>
		<?php if ($disable_sos == "disabled") { ?>
			<script>
			$('.button_sostituzione').html("<a class='button_disabled' href='#' >sostituzione</a>");
			</script>
		<?php 	}	else	{	?>
			<script>
			$('.button_sostituzione').html("<a class='button' href='#' onclick='javascript:ViewHideDIV(\"nuova_sostituzione\",\"nuova_regola\");'>sostituzione</a>");
			</script>
		<?php } 
		if ($disable_reg == "disabled") { ?>
			<script>
			$('.button_regola').html("<a class='button_disabled' href='#' >regola</a>");
			</script>
		<?php 	}	else	{	?>
			<script>
			$('.button_regola').html("<a class='button' href='#' onclick='javascript:ViewHideDIV(\"nuova_regola\",\"nuova_sostituzione\");'>regola</a>");
			</script>
		<?php } 


		if (isset($tipo_regola) && is_array($tipo_regola))
		{
			echo "<table border='0' cellpadding='0' cellspacing='2' width='100%'>";
			$cc = 0;
			foreach ($tipo_regola as $tipo)
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
						echo "<td align='center'>$ar_min[$cc]</td><td>$mostra_condizione</td><td align='center'>
						<input name='btn' class='brdgioca' value=$ar_entra[$cc] type='button'></td>
								<td align='center'>
								<input name='btn' class='brdgioca' value=$ar_esce[$cc] type='button'></td>";
					echo "</tr>";
					echo "<tr><td colspan='6'><hr></td></tr>";
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
						echo "<td align='center'>$ar_min[$cc]</td><td>$mostra_condizione</td><td>$mostra_regola</td>";
					echo "</tr>";
					echo "<tr><td colspan='6'><hr></td></tr>";
				}
				$cc++;
			}
			echo "</table>";
		}
	?>
<script>
	document.getElementById('nuova_sostituzione').style.display = "none";
	document.getElementById('nuova_regola').style.display = "none";
</script>