<?php
	require_once('../auth.php');
	include "../connect_db.php";	

	$nome_team = $_SESSION["SESS_TEAM"];
	$formazione = $_REQUEST['form'];
	
	
	$qry = "DELETE FROM formazione WHERE f_id_team = \"$nome_team\" AND f_formazione = \"$formazione\"	";

	//echo $qry;
	$result = mysql_query($qry);
	
	if (!$result)
	{
    	echo 'Errore nella query RESET FORMAZIONE: ' . mysql_error();
	    exit();
	}
	
	mysql_close($link);
?>
<select id='ws_formazione' name='ws_formazione' onchange='javascript:CambiaFormazione();' >
		<option value='<?php echo $formazione ?>' selected='selected'><?php echo $formazione ?></option>
		<option value='<?php echo $formazione ?>'>------------------</option>
		<option value='Formazione 1'>Formazione 1</option>
		<option value='Formazione 2'>Formazione 2</option>
		<option value='Formazione 3'>Formazione 3</option>
		<option value='Formazione 4'>Formazione 4</option>
		<option value='Formazione 5'>Formazione 5</option>
</select>
