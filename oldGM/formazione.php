<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
<?php 
	$quale_css1 = "css/index".$_SESSION['SESS_LARGHEZZA'].".css";
	$quale_css2 = "css/index_formazione".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css1 = "css/index1024.css";
		$quale_css2 = "css/index_formazione1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css1'; </style>";
	echo "<style type='text/css'> @import '$quale_css2'; </style>";
?>
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/jquery-ui-1.7.3.custom.min.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>
</head>

<body>
<h1><font style="font-weight:bold; color:#00FFFF; font-size:18px; font-family:Verdana, Arial, Helvetica, sans-serif;">Formazione</font></h1>			

<div id="corpo">
	<div style="float: left;">
		<div class="content-area"> 
			<img id="formaz_sx" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="formaz_sx">
				<fieldset id='formaz_sx'>
					<div id='formaz_sx' class="form_sx">
						<?php include 'formazione_sx.php' ?>
					</div>
				</fieldset>
			</span>
		</div>
	</div>

	<div style="float: right;">
		
		<div class="content-area"> 
			<img id="formaz_dx" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="formaz_dx">
				<fieldset id='formaz_dx' class="form_dx">
					<?php include 'formazione_dx.php' ?>
				</fieldset>
			</span>
		</div>
	</div>
</div>
<script>
	$('#corpo').slideUp(0).delay(300).fadeIn(600);
</script>
</body>
</html>