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

<script type="text/javascript">
var asx = 0;
var adx = 0;
function cambia(arg)
{
	if (arg == 1)
	{
			asx ++ ;
			if (asx == 16) { asx = 0; }
			document.imgsx.src="images/scudo_sx"+asx+".png";
			
			document.getElementById('imglogosx').value = asx ;
	}
	else
	{
			adx ++ ;
			if (adx == 16) { adx = 0; }
			document.imgdx.src="images/scudo_dx"+adx+".png";
			document.getElementById('imglogodx').value = adx ;
	}
}

function aggiorna()
{
	arg = document.getElementById('ws_team').value ;

	indirizzo="change_team.php?team="+arg;
	window.parent.location.href=indirizzo;
}

function changepwd()
{
	indirizzo="form_change_pwd.php";
  	window.location.href=indirizzo;
}
</script>
</head>
<?php 
$nome_team = $_SESSION['SESS_TEAM'];
$privilegi = $_SESSION['SESS_PRIVILEGI'];

$result = mysql_query("SELECT * FROM members WHERE team=\"$nome_team\"");
if (!$result)
{
    echo 'Errore nella query: ' . mysql_error();
    exit();
}

$row   =   mysql_fetch_array($result);


$IdUtente = $row['login'];
$Squadra = $row['team'];
$email = $row['email'];
$serie = $row['serie'];
 
$immsx = 'images/scudo_sx'.$row['logos'].'.png';
$immdx = 'images/scudo_dx'.$row['logod'].'.png';

?>
<h1><font style="font-weight:bold; color:#00FFFF; font-size:18px; font-family:Verdana, Arial, Helvetica, sans-serif;">Impostazioni Utente</font></h1>

<div id="utente">
	<center>
	<div class="content-area"> 
		<img id="utente" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="utente">
			<fieldset id='utente'>
				<form id="loginForm" name="loginForm" method="post" action="data-profile-modif.php">
				  <table border="0" align="center" cellpadding="2" cellspacing="0" >
					<tr>
					  <th width="146" align="right">ID Utente: </th>
					  <td width="166" align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $IdUtente; ?></td>
					  
						<?php
							echo "	<td width='180' rowspan='6'><center>
									<img id='imgsx' name='imgsx' src='$immsx'  width='45'  /><img id='imgdx' name='imgdx' src='$immdx'  width='45' />
									</td>"
						?>    
						
					</tr>
					<tr >
					  <th align="right" >Squadra: </th>
					  <td align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $Squadra; ?></td>
					</tr>
					
					<tr >
					  <th align="right">Serie/Divisione: </th>
					  <td align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $serie; ?></td>
					</tr>
					<tr><td>&nbsp;</td></tr>
					<tr>
					  <th align="right" >eMail: </th>
					  <td><?php 
							echo "<input name='email' type='text' class='textfield' id='email' value='$email' style='width:350px;'/>";
							?>
					</tr>
				
					<tr><td>&nbsp;</td></tr>
					<tr> 
					
					  <td>&nbsp;</td>
					  <td align="left"><input type="submit" name="Submit" value="Salva"  class="fieldbutton"/></td>
					  <td><center>
							<input type="button" name="butplus"  id="butplus"  value="Sx" onclick='javascript:cambia(1);' class="fieldbutton"/>
							<input type="button" name="butmenus" id="butmenus" value="Dx" onclick='javascript:cambia(2);' class="fieldbutton"/>
							<input name="imglogosx" type="hidden" class="textfield" id="imglogosx" value="1"/>
							<input name="imglogodx" type="hidden" class="textfield" id="imglogodx" value="1"/>
					  </td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td align='center'><input type='button' value='&nbsp;' onclick='javascript:changepwd();' class='button_password' >&nbsp;Cambia password</td>
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
	if ($privilegi == 1) 
	{
	
	$result = mysql_query("SELECT * FROM members WHERE 1");
	while ($row   =   mysql_fetch_array($result))
	{	
		$squadra[] = $row['team'];
	}
	echo "<tr>
			<td>
				<select name=\"ws_team\" id=\"ws_team\" onchange='javascript:aggiorna();'>";
					echo "<option value=''>&nbsp;</option>";
					foreach ($squadra as $data)
					{
						echo "<option value=\"$data\">$data</option>";
					}

	echo "		</select>
			</td>
			<td>
				<a href='phpinfo.php' target='_parent' />PhpInfo</a>
			</td>
		</tr>";
	
	
	}

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
