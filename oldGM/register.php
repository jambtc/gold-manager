<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<?php 
	$quale_css = "css/index".$larghezza.".css";
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
	
	$mesi = array("","Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre");
	$giorni = array("",1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31);
	
	$province[] = "";
	$pr_result = mysql_query("SELECT * FROM province WHERE 1");
	while   ($row   =   mysql_fetch_array($pr_result))
	{
		$province[] = $row['descrizione'];
	}

?>

<script type="text/javascript">
var asx = 0;
var adx = 0;
function cambia(arg)
{
	if (arg == 1)
	{
			asx ++ ;
			if (asx == 16) { asx = 0; }
			document.sx.src="images/scudo_sx"+asx+".png";
			document.getElementById('logosx').value = asx ;
	}
	else
	{
			adx ++ ;
			if (adx == 16) { adx = 0; }
			document.dx.src="images/scudo_dx"+adx+".png";
			document.getElementById('logodx').value = adx ;
	}
}
</script>
</head>


<body>
<h1>Registrazione utente</h1>



<div id="register">
<center>
<div class="content-area"> 
	<img id="register" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="register">
		<fieldset id='register'>
			<form id="loginForm" name="loginForm" method="post" action="register-exec.php">
			  <table border="0" align="center" cellpadding="2" cellspacing="0" >
				<tr>
					<th align="right" >Nome</th>
					<td align="left"><input name="nome" type="text" class="textfield" id="nome" title="Il tuo nome" /></td>
				</tr>
				<tr >
					<th align="right" >Cognome </th>
					<td align="left"><input name="cognome" type="text" class="textfield" id="cognome" title="Il tuo cognome" /></td>
				</tr>
				<tr >
					<th align="right" >Data di nascita</th>
					<td align="left">
						<table>
							<tr>
								<td>
									<select id="giorno" name="giorno" style="width:40px;" title="Il giorno di nascita" >
									<?php 
									foreach ($giorni as $giorno)
									   {
											echo "<option value='$giorno'>$giorno</option>";
									   }
									?>
									</select>
								</td>
								<td><select id="mese" name="mese" title="Il mese di nascita" >
									<?php 
									$xy = 0;
									foreach ($mesi as $mese)
									   {
											echo "<option value='$xy'>$mese</option>";
											$xy ++;
									   }
									?>
									</select>
								</td>
								<td><input name="anno" type="text" class="textfield" id="anno" style="width:40px;" title="L'anno di nascita" /></td>
							</tr>
						</table>
					</td>
					<?php
						$immsx = 'images/scudo_sx0.png';
						$immdx = 'images/scudo_dx0.png';
						
						echo "	<td width='150' rowspan='5'><center>
								<img id='sx' name='sx' src='$immsx' width='45' /><img id='dx' name='dx' src='$immdx' width='45' />
								</td>"
					?>    
					</tr>
					<tr >
						<th align="right" >Provincia di nascita</th>
						<td align="left"><select id="provincia" name="provincia" title="La provincia di nascita" >
						<?php 
							foreach ($province as $provincia)
							{
								echo "<option value='$provincia'>$provincia</option>";
							}
						?>
						</select>
					</td>
				</tr>
				<th width="146" align="right">ID Utente</th>
				<td  align="left"><input name="login" type="text" class="textfield" id="login" title="Inserisci il tuo Identificativo per collegarti al sito" /></td>
				</tr>
				
				<tr >
				  <th align="right" >Nome Squadra </th>
				  <td align="left"><input name="team" type="text" class="textfield" id="team" title="Scegli il nome della tua squadra" /></td>
				</tr>
				<tr >
				  <th align="right" >eMail </th>
				  <td align="left"><input name="email" type="text" class="textfield" id="email" style="width:350px;" title="Inserisci la tua email" /></td>
				</tr>
				
				<tr>
				  <th align="right">Password</th>
				  <td align="left"><input name="password" type="password" class="textfield" id="password" title="Inserisci una password" /></td>
				  <td><center>
						<input type="button" name="butplus"  id="butplus"  value="Sx" onclick='javascript:cambia(1);' class="fieldbutton"/>
						<input type="button" name="butmenus" id="butmenus" value="Dx" onclick='javascript:cambia(2);' class="fieldbutton"/>
						<input name="logosx" type="hidden" class="textfield" id="logosx" value="0"/>
						<input name="logodx" type="hidden" class="textfield" id="logodx" value="0"/>
						<input name="largh" type="hidden" class="textfield" id="largh" value="<?php echo $larghezza ?>"/>
						<input name="altez" type="hidden" class="textfield" id="altez" value="<?php echo $altezza ?>"/>
				  </td>
				</tr>
				<tr>
				  <th align="right">Conferma Password </th>
				  <td align="left"><input name="cpassword" type="password" class="textfield" id="cpassword" title="Inserisci nuovamente la password prescelta per conferma" /></td>
				</tr>
				<tr>
				  <td>&nbsp;</td>
				  <td align="left"><input type="submit" name="Submit" value="Registrati"  class="fieldbutton"/></td>
				  
				</tr>
			  </table>
			</form>
		</fieldset>
	</span>
</div>
</div>
<script>
		$('#register').slideUp(0).delay(300).fadeIn(600);
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
