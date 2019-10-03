<?php
	require_once('auth.php');
?><head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<script type="text/javascript" src="jquery/jquery-ui-1.8.17.custom.min.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script> 
	<script type="text/javascript" src="jquery/my_script_team.js"></script>
	<script type="text/javascript" src="jquery/my_tooltip_allena.js"></script>
	
	<link rel="stylesheet" type="text/css" href="formazione.css" media="all" />
	
	<style>
	.scelto_allenato {background-color: #006699; color:#FFFFFF;	font-family:Geneva, Arial, Helvetica, sans-serif; font-weight:normal;	} 
	.scelto_riposo {font-family:Geneva, Arial, Helvetica, sans-serif;	font-weight:normal;	}
	
	.green_bar { background-color:#006600; border:#666666 solid 2px; font-size:12px; font-weight:bold; color:#FFFFFF; }
	
	</style>
</head>





<h1 class="h1">Allenamento</h1>			
<div class='div_allena_sx' style="float:left; ">	
	<span class="top-label">  
		<span class="label-txt">Lista giocatori</span>
	</span> 
	<img class='img_allena_sx' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_allena_sx'>
		<fieldset>
			<div class='scr_allena_sx'>
				<?php include("allena_list.php");	?>
			</div>
		</fieldset>
	</div>
</div>
<div class='div_allena_dx' style="float: right; ">	
	<span class="top-label">  
		<span class="label-txt">Allenatori</span>
	</span> 
	<img class='img_allena_dx' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_allena_dx'>
		<fieldset>
			<div class='frm_allena_dx'>
				<?php include("allena_dx.php");	?>
			</div>
		</fieldset>
	</div>
</div>



<script>
	allarga_maskwscroll('allena_sx',40,ValAlt('teamlist'));
	allarga_msk_frame('allena_dx',55,ValAlt('teamlist'));
</script>	