<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

</head>
<body>

<form id="loginForm" name="loginForm" method="post" action="login-exec.php">
  <table width="210" border="0" align="center" cellpadding="2" cellspacing="0">
    <tr>
      <td width="112"><b>ID Utente</b></td>
      <td width="70%" align="left"><input name="login" type="text" class="fieldlogin" id="login" /></td>
    </tr>
    <tr>
      <td><b>Password</b></td>
      <td align="left"><input name="password" type="password" class="fieldlogin" id="password" /></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td align="left"><input type="submit" name="Submit" value="Login" class="fieldbutton" /></td>
	  <?php //echo "<input name='altezza' type='hidden'  id='altezza' value='$altezza' />";
			//echo "<input name='larghezza' type='hidden'  id='larghezza' value='$larghezza' />";
		?>
	  
    </tr>
	<tr>
	<td  align="rigth">
	<?php echo "<a href='form_privacy.php' style='color:#00FF00;'>Privacy</a>";
	?>
	</td>

	<td  align="left">
	<?php echo "<a href='form_ripristinopwd.php' style='color:#00FF00;'>Password persa?</a>";
	?>
	</td>
	</tr>
  </table>
</form>

</body>
</html>
