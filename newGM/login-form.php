<div class='div_loginx'>		
	<img class='img_loginx' src="images/lavagna.png"  width="10%" height="10%" border="0" />		
	<div class='cnt_loginx'>
		<form class="loginForm" id="loginForm" name="loginForm" method="post" action="login-exec.php">
		  <table width="50%" border="0" align="left" cellpadding="5" cellspacing="5">
			<tr>
			  <td width="112"><b>ID Utente</b></td>
			  <td width="70%" align="left"><input name="login" type="text" class="fieldLogin" id="login" /></td>
			</tr>
			<tr>
			  <td><b>Password</b></td>
			  <td align="left"><input name="password" type="password" class="fieldLogin" id="password" /></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
			  <td align="left">
			  <input type="submit" name="Submit" value="Login" class="LoginButton" /> 
			  <!-- <a href="" onclick="document.forms.checkoutForm.submit(); return false;" class="button">LOGIN</a> -->
			  </td>
				
			</tr>
			<tr>
			<td  align="rigth">
			<a href='index.php?fnz=privacy' style='color:#00FF00;'>Privacy</a>
		
			</td>
		
			<td  align="left">
			<a href='index.php?fnz=recpwd' style='color:#00FF00;'>Password persa?</a>
		
			</td>
			</tr>
		  </table>
		</form>
	</div>
</div>
<script>
	allarga_maschera('loginx',19,ValAlt('login'));
</script>