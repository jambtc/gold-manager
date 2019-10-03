<?php
	require_once('auth.php');
?>
<h1 class='h1'>Comunicato Stampa</h1>
<center>
	<?php 
		if (!isset($_REQUEST['mexinv']))
		{
	?>
			<div class='div_stampa'>				
				<img class='img_stampa' src="images/quadrato_rounded.png" width="10%" height="10%" border="0" /> 
				<div class='cnt_stampa'>
					<fieldset>
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
								<td ><TEXTAREA name="testo" ROWS="10" COLS="50%" id="testo" class="areainput" >Scrivi il tuo messaggio</TEXTAREA></td>
							</tr>
							<tr>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td><a href="" onclick="document.forms.stampa.submit(); return false;" class="button">invia comunicato</a></td>
							</tr>
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
	<?php
		}
		else
		{ 
	?>
		<div class='div_stampa'>				
			<img class='img_stampa' src="images/quadrato_rounded.png" width="10%" height="10%" border="0" /> 
			<div class='cnt_stampa'>
				<fieldset>	
					<h2>COMUNICATO STAMPA INVIATO CORRETTAMENTE</h2>
				</fieldset>
			</div>
		</div>
	<?php
		}
	?>
</center>

<script>
	/*allarga_maschera('stampa',50,eval(ValAlt('stampa')+ <?php echo $righe_in_piu ?>));*/
	
	allarga_maschera('stampa',50,ValAlt('stampa'));
</script>	

