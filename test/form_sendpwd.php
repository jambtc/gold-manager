<h1 class="h1">Ripristino password</h1>
<center>
	<div class='div_sendpwd'>				
		<img class='img_sendpwd' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_sendpwd'>
			<fieldset>
				<form name="pwdform" id="pwdform" method="post" action="data_sendpassword.php">
				<table style="color:#000000;" width="100%" border="0" align="center" cellpadding="5" cellspacing="5"  >
					<tr>
						<th align="right">ID Utente</th>
						<td width="80%" align="left"><input type="text" id="username" class="textfield" name="username"  /></td>
					</tr>
					<tr>
						<th  align="right">eMail </th>
						<td align="left"><input type="text" id="email" class="textfield" name="email" style="width:350px;"/></td>
					</tr>
					<tr>
						<td colspan="2" align="left"><a href="" onclick="location.href='data_sendpassword.php'; return false;" class="button">invia i dati</a></td></tr>
				</table>
				</form>
			</fieldset>
			<?php
				$righe_in_piu = 0;
				if( isset($_SESSION['ERRMSG_ARR']) && is_array($_SESSION['ERRMSG_ARR']) && count($_SESSION['ERRMSG_ARR']) >0 ) {
					echo '<ul class="err">';
					foreach($_SESSION['ERRMSG_ARR'] as $msg) {
						echo '<li>',$msg,'</li>'; 
						$righe_in_piu ++;
					}
					echo '</ul>';
					unset($_SESSION['ERRMSG_ARR']);
					$righe_in_piu = $righe_in_piu * 2;
				}
			?>

		</div>
	</div>
</center>
<script>
	allarga_maschera('sendpwd',48,ValAlt('sendpwd')+<?php echo $righe_in_piu ?>);
</script>
			

