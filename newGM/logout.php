<h1 class='h1'>Uscita</h1>
<center>
<?php 
	if (!isset($_REQUEST['mexinv']))
	{
?>
	<div class='div_privacy'>				
		<img class='img_privacy' src="images/quadrato_rounded_chiaro.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_privacy'>
				<fieldset>
				<h2>VUOI USCIRE DAL GIOCO ?</h2>
					
					<table>
					<tr>
					<td>
						<a href="" onclick="location.href='index.php?fnz=logout&pg=1&mexinv=ok'; return false;" class="button">si</a>
					</td>
					<td>
						<a href="" onclick="location.href='m-index.php'; return false;" class="button">no</a>				
					</td>
					</tr>
					</table>
				</fieldset>
			</div>
	</div>
<?php 
	}
	else
	{
	//Start session
	//session_start();
	//Unset the variables stored in session
	unset($_SESSION['SESS_USER']);
	unset($_SESSION['SESS_TEAM']);
	unset($_SESSION['SESS_SERIE']); 
	unset($_SESSION['SESS_SX']);
	unset($_SESSION['SESS_DX']);
	unset($_SESSION['SESS_PRIVILEGI']);
	session_write_close();
?>
	<div class='div_privacy'>				
		<img class='img_privacy' src="images/quadrato_rounded_chiaro.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_privacy'>
				<fieldset>
				<h2>TI SEI DISCONNESSO CORRETTAMENTE</h2>
				</fieldset>
			</div>
	</div>
<?php 
	}
?>

</center>
<script>
	allarga_maschera('privacy',60,ValAlt('logout'));
</script>
