<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
<?php
	$quale_css = "css/index".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
</head>

<body>
<h1>Comunicato Stampa</h1>

<div id="stampa">
	<center>
	<div class="content-area"> 
		<img id="stampa" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="stampa">
			<fieldset id='stampa'>
				<form id="stampa" name="stampa" method="post" action="data_stampa.php">
				<table border="0" align="center" cellpadding="2" cellspacing="0" >
					<tr>
						<th align="left">Titolo</th>
					</tr>
					<tr>
						<td align="left"><input name="titolo" type="text" class="texttitolo" id="titolo" /></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<th  align="left">Messaggio</th>
					</tr>
					<tr>
						<td ><TEXTAREA name="testo" ROWS="10" COLS="80" id="testo" class="areainput" >Scrivi il tuo messaggio</TEXTAREA></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td><input type="submit" name="Submit" value="Invia comunicato" class="fieldbutton" /></td>
					</tr>
				</table>
				</form>
			</fieldset>
		</span>
	</div>
</div>
<script>
	$('#stampa').slideUp(0).delay(300).fadeIn(600);
</script>
<?php
	if( isset($_SESSION['ERRMSG_ARR']) && is_array($_SESSION['ERRMSG_ARR']) && count($_SESSION['ERRMSG_ARR']) >0 )
	{
		echo '<ul class="err">';
		foreach($_SESSION['ERRMSG_ARR'] as $msg) {
			echo '<li>',$msg,'</li>'; 
		}
		echo '</ul>';
		unset($_SESSION['ERRMSG_ARR']);
	}
?>			

</body>
</html>
