<?php
	require_once('auth.php');
	
	//VISUALIZZAZIONE DATI A SCHERMO
	if (!isset($_REQUEST['label']))
	{
			$aclasse[0]="button";
			$aclasse[1]="button_unselected";
			$aclasse[2]="button_unselected";
			$label = 0;
	
	} else {
			$aclasse[0]="button_unselected";
			$aclasse[1]="button_unselected";
			$aclasse[2]="button_unselected";
			$aclasse[$_REQUEST['label']]="button";
			$label = $_REQUEST['label'];
	}
	
	?>
	<fieldset>
	<table border="0" width="100%">
	<tr>
		<td><a class="<?php echo $aclasse[0]; ?>" href="#" onclick="javascript:LabelFormazione(0);">In Campo</a></td>
		<td><a class="<?php echo $aclasse[1]; ?>" href="#" onclick="javascript:LabelFormazione(1);">Tattiche</a></td>
		<td><a class="<?php echo $aclasse[2]; ?>" href="#" onclick="javascript:LabelFormazione(2);">Istruzioni</a></td>
	</tr>
	</table>
	</fieldset>
