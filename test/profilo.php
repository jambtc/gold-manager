<head>
	<style type="text/css">

	#avatar_menu li { 
		list-style: none; 
		display: block; 
		float: left;
		text-align:center;
		vertical-align:middle; 
	} 
</style>
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
$avatar = $row['avatar'];

$checked_avatar = array("","","","");
$checked_avatar[$avatar] = "checked";

$sc_top 		= "images/scudo/scudo_top_".$_SESSION['SESS_LOGO_TOP'].".png";
$sc_middle 		= "images/scudo/scudo_middle_".$_SESSION['SESS_LOGO_MIDDLE'].".png";
$sc_bottom_left = "images/scudo/scudo_bottom_left_".$_SESSION['SESS_LOGO_BOTTOM_LEFT'].".png";
$sc_bottom_center = "images/scudo/scudo_bottom_center_".$_SESSION['SESS_LOGO_BOTTOM_CENTER'].".png";
$sc_bottom_right = "images/scudo/scudo_bottom_right_".$_SESSION['SESS_LOGO_BOTTOM_RIGHT'].".png";


$Passwd = "";
$cPwd = "";

?>
<div id="h1">
	<div style="float: right; margin-left:20px;">
		<?php 
		if (!isset($_REQUEST['mexinv']))
		{
		?>
		Impostazioni Utente
		<?php 
		}else if ($_REQUEST['mexinv'] == "pwd") {
		?>
		Cambio Password
		<?php 
		}else{
		?>
		Indietro
		<?php 
		}
		?>
	</div>
	<div style="float: right;">
		<?php 
		if (isset($_REQUEST['mexinv']))
		{
		?>
		<a href='#' onclick="location.href='m-index.php?fnz=setup&pg=14';">
			<img id='arret' src="images/indietro.png" border="0" width="25%" onmouseover="arret.src='images/indietro_big.png';" onmouseout="arret.src='images/indietro.png';"/>
		</a>
		<?php 
		}
		?>
	</div>
</div>
<center>
	<?php 
		if (!isset($_REQUEST['mexinv']))
		{
	?>
	<div class='div_stampa'>				
		<img class='img_stampa' src="images/quadrato_rounded.png" width="10%" height="10%" border="0" /> 
			<div class='cnt_stampa'>
				<fieldset>
				<form id="emailForm" name="emailForm" method="post" action="data_profilo_update.php">
				  <table border="0" align="center" cellpadding="0" cellspacing="5" >
					<tr>
					  <th width="146" align="right">ID Utente: </th>
					  <td width="166" align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $IdUtente; ?></td>
					  
						<td width='150' rowspan='7'>
						<center>
						<div class='scudo'>
							<a href="#" onclick="javascript:Change_Scudo('scudo_top');" title="Clicca per cambiare colore">
								<div class='scudo_top'>
									<?php //echo "<img src='$sc_top' border='0' width='99%'  />"; ?>
								</div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo('scudo_middle');" title="Clicca per cambiare colore">
								<div class='scudo_middle'>
									<?php //echo "<img src='$sc_middle' border='0' width='99%'  />"; ?>
								</div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo('scudo_bottom_left');" title="Clicca per cambiare colore">
								<div class='scudo_bottom_left'>
									<?php //echo "<img src='$sc_bottom_left' border='0' width='99%'  />"; ?>
								</div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo('scudo_bottom_center');" title="Clicca per cambiare colore">
								<div class='scudo_bottom_center'>
									<?php //echo "<img src='$sc_bottom_center' border='0' width='99%'  />"; ?>
								</div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo('scudo_bottom_right');" title="Clicca per cambiare colore">
								<div class='scudo_bottom_right'>
									<?php //echo "<img src='$sc_bottom_right' border='0' width='99%'  />"; ?>
								</div>
							</a>
						</div>
						</center>
						<input name="scudo_top" type="hidden" id="scudo_top" value="<?php echo $row['logos']; ?>"/>
						<input name="scudo_middle" type="hidden" id="scudo_middle" value="<?php echo $row['logos']; ?>"/>
						<input name="scudo_bottom_left" type="hidden" id="scudo_bottom_left" value="<?php echo $row['logos']; ?>"/>
						<input name="scudo_bottom_center" type="hidden" id="scudo_bottom_center" value="<?php echo $row['logos']; ?>"/>
						<input name="scudo_bottom_right" type="hidden" id="scudo_bottom_right" value="<?php echo $row['logos']; ?>"/>
					</td>   
						
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
					<th align="right">Avatar:</th>
					<td>
					<ul id="avatar_menu"> 
						<li>
							<input type="radio" name="avatar" value="0" <?php echo $checked_avatar[0]; ?>/>
							<img src="avatar/avatar_0.png" width="70" />
						</li>
						<li>
							<input type="radio" name="avatar" value="1" <?php echo $checked_avatar[1]; ?>/>
							<img src="avatar/avatar_1.png" width="70" />
						</li>
						<li>
							<input type="radio" name="avatar" value="2" <?php echo $checked_avatar[2]; ?>/>
							<img src="avatar/avatar_2.png" width="70" />
						</li>
						<li>
							<input type="radio" name="avatar" value="3" <?php echo $checked_avatar[3]; ?>/>
							<img src="avatar/avatar_3.png" width="70" />
						</li>
					</ul>
					</td>
										
					<tr> 
					  <td colspan="2" align="left"><a href="" onclick="document.forms.emailForm.submit(); return false;" class="button">salva</a></td>
					 
						<td><a href="" onclick="window.location.href='m-index.php?fnz=setup&pg=14&mexinv=pwd'; return false;" class="button">cambia password</a></td>
					</tr>
					<tr height="20">
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						
					</tr>
					
				  </table>
				</form>
			</fieldset>
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
							<a href='phpinfo.php' target='_blank' />PhpInfo</a>
						</td>
					</tr>";
				
				
				}
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
	<script>
		allarga_maschera('stampa',70,ValAlt('profilo')+<?php echo $righe_in_piu ?>);
	</script>
	<?php
		}
		else if ($_REQUEST['mexinv'] == "ok")
		{
	?>
		<div class='div_stampa'>				
			<img class='img_stampa' src="images/quadrato_rounded.png" width="10%" height="10%" border="0" /> 
			<div class='cnt_stampa'>
				<fieldset>	
					<h2>MODIFICA AVVENUTA CORRETTAMENTE</h2>
				</fieldset>
			</div>
		</div>
		<script>
			allarga_maschera('stampa',70,ValAlt('profilo')/2.5);
		</script>
		
	<?php
		}
		else
		{
	?>
		<div class='div_stampa'>				
			<img class='img_stampa' src="images/quadrato_rounded.png" width="10%" height="10%" border="0" /> 
			<div class='cnt_stampa'>
				<fieldset>	
					<form id="loginForm" name="loginForm" method="post" action="data_pwd_update.php">
		  				<table border="0" align="center" cellpadding="0" cellspacing="5">
    					<tr>
      						<th width="146" align="right">ID Utente: </th>
		      				<td width="166" align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $IdUtente; ?></td>
						</tr>
						<tr >
					      <th align="right" >Squadra: </th>
						  <td align="left" style="font-size:14px; font-weight:bold; color:#006600;"><?php echo $Squadra; ?></td>
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
					      <td colspan="2" align="left"><a href="" onclick="document.forms.loginForm.submit(); return false;" class="button">salva</a></td>
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
	<script>
		allarga_maschera('stampa',70,ValAlt('profilo')+<?php echo $righe_in_piu ?>);
	</script>
	<?php
		}
	?>
	
