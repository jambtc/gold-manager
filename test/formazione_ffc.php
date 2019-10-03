<?php 
	if (isset($_REQUEST['ajax']))
	{
		require_once('auth.php');
		include "connect_db.php";
		
	}
	$nome_team = $_SESSION['SESS_TEAM'];
	$BonusFfc = "0"; 
	$checkFres = "";
	$checkForm = "";
	$checkCond = "";
	$ffcFres = 100;
	$ffcForm = 100;
	$ffcCond = 100;
	// CARICO IL CHECK DEL BONUS FORMA, FRESCHEZZA E CONDIZIONE
	$tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\"");
	if (!$tipo_result)
	{
		echo 'Errore nella query tattica: ' . mysql_error();
		exit();
	}
	$row   =   mysql_fetch_array($tipo_result);
	$BonusFfc = $row['t_forma'];
	$QualeFormazione = $row['t_formazione'];
	if ($QualeFormazione == "")	{	$QualeFormazione = "Formazione 1"; 	}

	// controllo i checkbox ADESSO !!! dopo durante il while controllo i valori !!!!
	switch ($BonusFfc)
	{
		case 0:
			$checkFres = "";
			$checkForm = "";
			$checkCond = "";
			$BnFFC2 = 0;
			$BnFFC1 = 0;
			$BnFFC3 = 0;
			break;
		case 1:
			$checkFres = "";
			$checkForm = "";
			$checkCond = "checked";
			$BnFFC2 = 0;
			$BnFFC1 = 0;
			$BnFFC3 = 1;
			break;
		case 2:
			$checkFres = "";
			$checkForm = "checked";
			$checkCond = "";
			$BnFFC2 = 0;
			$BnFFC1 = 2;
			$BnFFC3 = 0;
			break;
		case 3:
			$checkFres = "";
			$checkForm = "checked";
			$checkCond = "checked";
			$BnFFC2 = 0;
			$BnFFC1 = 2;
			$BnFFC3 = 1;
			break;
		case 4:
			$checkFres = "checked";
			$checkForm = "";
			$checkCond = "";
			$BnFFC2 = 4;
			$BnFFC1 = 0;
			$BnFFC3 = 0;
			break;
		case 5:
			$checkFres = "checked";
			$checkForm = "";
			$checkCond = "checked";
			$BnFFC2 = 4;
			$BnFFC1 = 0;
			$BnFFC3 = 1;
			break;
		case 6:
			$checkFres = "checked";
			$checkForm = "checked";
			$checkCond = "";
			$BnFFC2 = 4;
			$BnFFC1 = 2;
			$BnFFC3 = 0;
			break;
		case 7:
			$checkFres = "checked";
			$checkForm = "checked";
			$checkCond = "checked";
			$BnFFC2 = 4;
			$BnFFC1 = 2;
			$BnFFC3 = 1;
			break;
	}
?>
<fieldset style="width:98%;">
<form name='focofr' enctype="text/plain"  method="post">
<table width="100%"  border='0' cellpadding='0' cellspacing='0' style="color:#0000FF; font-weight:bold;">
<tr>
	<td width="10%">Formazione&nbsp;Attiva:</td>
	<td rowspan="2"><a href="" onclick="javascript:ResetFormazione(); return false;" class="button">reset formazione</a></td>
	<td rowspan="2">
		<div class='_ffc1'>
			<?php 
				echo "<input id='ws_ffc1' name='ws_ffc1' type='checkbox' $checkForm value='$BnFFC1' onclick='javascript:ffc(1);' class='fld_y'/>&nbsp;Forma";
			?>
		</div>
		<div class='_ffc2'>
			<?php 
				echo "<input id='ws_ffc2' name='ws_ffc2' type='checkbox' $checkFres value='$BnFFC2' onclick='javascript:ffc(2);' class='fld_y'/>&nbsp;Freschezza";
			?>
		</div>
		<div class='_ffc3'>
			<?php 
				echo "<input id='ws_ffc3' name='ws_ffc3' type='checkbox' $checkCond value='$BnFFC3' onclick='javascript:ffc(3);' class='fld_y'/>&nbsp;Condizione";
			?>
		</div>
	</td>
</tr>
<tr>
	<td align="left">
	<div class="select_form">
	<select id='ws_formazione' name='ws_formazione' onchange='javascript:CambiaFormazione();' >
		<option value='<?php echo $QualeFormazione ?>' selected='selected'><?php echo $QualeFormazione ?></option>
		<option value='<?php echo $QualeFormazione ?>'>------------------</option>
		<option value='Formazione 1'>Formazione 1</option>
		<option value='Formazione 2'>Formazione 2</option>
		<option value='Formazione 3'>Formazione 3</option>
		<option value='Formazione 4'>Formazione 4</option>
		<option value='Formazione 5'>Formazione 5</option>
	</select>
	</div>
	</td>
	
</tr>
</table>
</form>
</fieldset>
