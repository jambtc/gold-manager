<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
</head>

<?php
	$nome_team = $_SESSION['SESS_TEAM'];
	$nome_utente = $_SESSION['SESS_USER'];
	$serie = $_SESSION['SESS_SERIE'];
	
	$link = array("form_allenamento.php","form_allenamento.php?pagina=1");
	$titolo = array("Tattiche squadra","Skill giocatori");
		
	if (!isset($_REQUEST['pagina']))
	{
		$link[0] = "#";
		$pagina = 0;
		$classe1="a1";
		$classe2="a3";
	} else {
		$link[$_REQUEST['pagina']] = "#";
		$pagina = $_REQUEST['pagina'];
		$classe1="a3";
		$classe2="a1";
	}
	
?>
<body>
<h1><font style="font-weight:bold; color:#00FFFF; font-size:18px; font-family:Verdana, Arial, Helvetica, sans-serif;">Allenamento</font></h1>


<div id="corpo">
	<div style="float:left;">
		<span class="top-label">  
			<span class="label-txt"><a class="<?php echo $classe1; ?>" href="<?php echo $link[0]; ?>"><?php echo $titolo[0]; ?></a></span>
			<span class="label-txt"><a class="<?php echo $classe2; ?>" href="<?php echo $link[1]; ?>" ><?php echo $titolo[1]; ?></a></span>
		</span>
		<!-- DATI TATTICHE -->
		<?php 
		if (!isset($_REQUEST['pagina']))
		{
		?>
		<div class="content-area"> 
			<img id="allena" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="allena">
				<fieldset id='allena'>
					<iframe src="allena_tattiche.php" name="allenamento" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" >
					</iframe>
					<?php //include "allena_tattiche.php"; ?>		
				</fieldset>
			</span>
		</div>
		
	
	</div>
	
	<?php
	} elseif ($_REQUEST['pagina'] == 1) {
	?>
	
		<div class="content-area"> 
			<img id="allena" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="allena">
				<fieldset id='allena'>
					<iframe src="allena_skill.php" name="allenamento" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" >
					</iframe>
					<?php //include "allena_skill.php"; ?>				
				</fieldset>
			</span>
		</div>
	</div>
	
	
	<?php
	}
	?>

</div>

<script>
	$('#corpo').slideUp(0).delay(300).fadeIn(600);
</script>

