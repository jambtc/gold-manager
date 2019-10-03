<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>



</head>

<body>
<h1>Guida</h1>

<form name='elenco'  >
<fieldset style='width: 90%;'>
<div id='msgpausa'>
<span  style="color:#FFFF00; font-weight:bold;">
<table border='0' cellpadding='0' cellspacing='10' >
	
	<?php 
		if ($_SESSION['SESS_PRIVILEGI'] == 1)
		{
	?>

	<tr valign="top">
		<td><h3>Control Panel</h3></td>
		<td>
			<table border='0' cellpadding='0' cellspacing='0'>
				<tr>
					<td width="168" align="left">
						<a href="m-corpo-dx.php?scelta=17" target="corpo_dx" />
						<span class="Stile19 Stile3 Stile5"><strong>PhpInfo</strong></span>
					</td>
				</tr>
				<tr>
					<td width="168" align="left">
						<a href="calendario_crea.php" target="_" />
						<span class="Stile19 Stile3 Stile5"><strong>Crea Calendario</strong></span>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php 
		}
	?>
	
					
</table>
</span>

</fieldset>
</form>
	<script>
		$('#msgpausa').slideUp(0).delay(300).fadeIn(600);
	</script>
</div>
</body>
</html>
