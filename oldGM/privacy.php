<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<?php 
	$quale_css = "index".$larghezza.".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
?>

</head>
<body>
<h1>Garanzia di riservatezza</h1>

<div id="privacy">
<center>
<div class="content-area"> 
	<img id="privacy" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="privacy">
		<fieldset id='privacy'>
			<form name="pwdform" id="pwdform" method="post" action="sendpassword.php" >
				<table width="100%" border="0" align="center" cellpadding="2" cellspacing="0" >
				<tr>
					<td align="left"><p><h2>IMPEGNO DI RISERVATEZZA NEL TRATTAMENTO DEI DATI</h2></p>
					  <p align="justify">
					  Il trattamento dei dati personali che La riguardano viene svolto nel rispetto di quanto stabilito dalla L. 675/96 sulla tutela dei dati personali. Il trattamento dei dati, di cui Le garantiamo la massima riservatezza, è effettuato al fine di poter utilizzare i servizi offerti dal nostro sito internet. 
						
				  I suoi dati non saranno comunicati o diffusi a terzi e per essi la S.V. potrà richiedere, in qualsiasi momento, la modifica o la cancellazione, contattando l'amministratore del sito.</p></td>
				</tr>
				<tr>
					<td align="left" ><h3 style="color:#0000FF;">Sergio Casizzone</h3>
					</td>
				</tr>
				</table>
			</form>
		</fieldset>
	</span>
</div>
</div>
<script>
			$('#privacy').slideUp(0).delay(300).fadeIn(600);
</script>
			



</body>
</html>
