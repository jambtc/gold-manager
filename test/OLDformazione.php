<?php
	require_once('auth.php');
?>
<head>
	<script type="text/javascript" src="jquery/my_script_team.js"></script>
</head>


<h1 class="h1">Formazione</h1>			


<div class='div_formazione_sx' style="float:left; ">	
	<span class="top-label">  
		<span class="label-txt">Lista giocatori</span>
	</span> 
	<img class='img_formazione_sx' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_formazione_sx'>
		<fieldset>
			<iframe class="frm_formazione_sx" src="formazione_sx.php" name="formazione_sx" width="10%" marginwidth="0" height="10%" align="top" allowtransparency="1" frameborder="0" scrolling="yes" hspace="0" vspace="0">
			</iframe>
		</fieldset>
	</div>
</div>

<?php 
//VISUALIZZAZIONE DATI A SCHERMO
	if (!isset($_REQUEST['pagina']))
	{
			$aclasse[0]="a2";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
			$tabella = 0;
	
	} else {
			$aclasse[0]="a3";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
			$aclasse[$_REQUEST['pagina']]="a2";
			$tabella = $_REQUEST['pagina'];
	}
?>
<div class='div_formazione_dx' style="float: right; ">	
		<span class="top-label">  
			<span class="label-txt">
				<a class="<?php echo $aclasse[0]; ?>" href="#" onclick="javascript:SelectTabella(0);">In Campo</a>
			</span>
			<span class="label-txt">
				<a class="<?php echo $aclasse[1]; ?>" href="#" onclick="javascript:SelectTabella(1);">Tattiche</a>
			</span>
			<span class="label-txt">
				<a class="<?php echo $aclasse[2]; ?>" href="#" onclick="javascript:SelectTabella(2);">Istruzioni</a>
			</span>
		</span>
	</span> 
	<img class='img_formazione_dx' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_formazione_dx'>
		<fieldset>
			<iframe class="frm_formazione_dx" src="formazione_dx.php" name="formazione_dx" width="10%" marginwidth="0" height="10%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
			</iframe>
			<?php //include 'formazione_dx.php' ?>
		</fieldset>
	</div>
</div>

	

<script>
	allarga_msk_frame('formazione_sx',45,ValAlt('teamlist')+10);
	allarga_msk_frame('formazione_dx',50,ValAlt('teamlist')+2);
</script>	
