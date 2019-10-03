<?php
	require_once('auth.php');
?><head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<script type="text/javascript" src="jquery/jquery-ui-1.8.17.custom.min.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script> 
	<script type="text/javascript" src="jquery/my_script_team.js"></script>
	<script type="text/javascript" src="jquery/my_tooltip.js"></script>

	<style>
	.scelto_in_campo {background-color: #006699; color:#FFFFFF;	font-family:Geneva, Arial, Helvetica, sans-serif; font-weight:normal;	} 
	.scelto_in_panchina {background-color: #999999; color:#FFFFFF;font-family:Geneva, Arial, Helvetica, sans-serif;	font-weight:normal;	}
	
	</style>
</head>





<h1 class="h1">Formazione</h1>			
<div class='div_formazione_sx' style="float:left; ">	
	<span class="top-label">  
		<span class="label-txt">Lista giocatori</span>
	</span> 
	<img class='img_formazione_sx' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_formazione_sx'>
		<fieldset>
			<div class='formazione_ffc'>
				<?php include("formazione_ffc.php"); ?>
			</div>
			<div class='frm_formazione_sx'>
				<?php include("formazione_sx.php");	?>
			</div>
		</fieldset>
	</div>
</div>
<div class='div_formazione_dx' style="float: right; ">	
	<span class="top-label">  
		<span class="label-txt">Formazione</span>
	</span> 
	<img class='img_formazione_dx' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_formazione_dx'>
		<fieldset>
			<div class='formazione_labels'>
				<?php include("formazione_label.php"); ?>
			</div>
			
			<div class='frm_formazione_dx'>
				<?php include("formazione_dx.php");	?>
			</div>
		</fieldset>
	</div>
</div>



<script>
	allarga_msk_frame_bis('formazione_sx',45,ValAlt('teamlist'));
	allarga_msk_frame_bis('formazione_dx',45,ValAlt('teamlist'));
</script>	