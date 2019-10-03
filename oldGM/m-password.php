<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
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
<?php 
$nome_user = $_SESSION['SESS_USER'];
$nome_team = $_SESSION['SESS_TEAM'];
$serie = $_SESSION['SESS_SERIE'];

$Passwd = "";
$cPwd = "";
 
?>
<h1>Cambio Password&nbsp;<input type='button' value='&nbsp;' onclick="javascript:location.href='form_impostazioni.php';" class='button_indietro'></h1>

<div id="utente">
<center>
<div class="content-area"> 
	<img id="utente" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="utente">
		<fieldset id='utente'>
			<form id="loginForm" name="loginForm" method="post" action="data-modif-password.php">
  				<table border="0" align="center" cellpadding="2" cellspacing="0">
    			<tr>
      				<th width="146" align="right">ID Utente: </th>
      				<td width="166" align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $nome_user; ?></td>
				</tr>
				<tr >
			      <th align="right" >Squadra: </th>
				  <td align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $nome_team; ?></td>
			    </tr>
				<tr >
			      <th align="right" >Serie/Divisione: </th>
			      <td align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $serie; ?></td>
			    </tr>
    			<tr>
			      <th align="right">Password: </th>
			      <td align="left"><input name="password" type="password" class="textfield" id="password" /></td>
			    </tr>
			    <tr>
			      <th align="right">Conferma Password: </th>
			      <td align="left"><input name="cpassword" type="password" class="textfield" id="cpassword" /></td>
			    </tr>
			    <tr>
			      <td>&nbsp;</td>
			      <td align="left"><input type="submit" name="Submit" value="Salva"  class="fieldbutton"/></td>
	  
			    </tr>
				</table>
			</form>
		</fieldset>
	</span>
</div>
</div>
	<script>
		$('#utente').slideUp(0).delay(300).fadeIn(600);
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
