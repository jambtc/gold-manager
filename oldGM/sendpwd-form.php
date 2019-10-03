<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Ripristino password</title>
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<?php 
	$quale_css = "index".$larghezza.".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
?>
</head>
<body>
<h1>Ripristino password</h1>


<div id="ripristino">
<center>
<div class="content-area"> 
	<img id="ripristino" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="ripristino">
		<fieldset id='ripristino'>
			<form name="pwdform" id="pwdform" method="post" action="sendpassword.php">
			<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0"  >
				<tr>
					<th align="right">ID Utente</th>
					<td width="80%" align="left"><input type="text" id="username" class="textfield" name="username"  /></td>
				</tr>
				<tr>
					<th  align="right">eMail </th>
					<td align="left"><input type="text" id="email" class="textfield" name="email" style="width:350px;"/></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					 <td align="left"><input type="submit" name="Submit" value="Invia" class='fieldbutton'/></td>
					 <input name="largh" type="hidden" class="textfield" id="largh" value="<?php echo $larghezza ?>"/>
						<input name="altez" type="hidden" class="textfield" id="altez" value="<?php echo $altezza ?>"/>
					
				</tr>
			</table>
			</form>
		</fieldset>
	</span>
</div>
</div>
<script>
	$('#ripristino').slideUp(0).delay(300).fadeIn(600);
</script>
			

<?php
	if( isset($_SESSION['ERRMSG_ARR']) && is_array($_SESSION['ERRMSG_ARR']) && count($_SESSION['ERRMSG_ARR']) >0 ) {
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
