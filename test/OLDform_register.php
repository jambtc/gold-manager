<?php 
	$mesi = array("","Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre");
	$giorni = array("",1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31);
	
	$province[] = "";
	$pr_result = mysql_query("SELECT * FROM province WHERE 1");
	while   ($row   =   mysql_fetch_array($pr_result))
	{
		$province[] = $row['descrizione'];
	}

?>
<h1 class="h1">Registrazione utente</h1>
<center>
<div class='div_register'>				
	<img class='img_register' src="images/quadrato_rounded_chiaro.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_register'>
		<fieldset>
			<form id="RegisterForm" name="RegisterForm" method="post" action="data_register.php">
			  <table style="color:#000000;" border="0" align="center" cellpadding="0" cellspacing="5" >
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
					<td width='150' rowspan='5'>
						<center>
					<?php
						//$immsx = 'images/scudo_sx0.png';
						//$immdx = 'images/scudo_dx0.png';
						
						/*echo "	<td width='150' rowspan='5'><center>
								<img id='sx' name='sx' src='$immsx' width='45' /><img id='dx' name='dx' src='$immdx' width='45' />
								</td>";*/
					?>   
						<div class='scudo'>
							<a href="#" onclick="javascript:Change_Scudo("scudo_top");" title="Clicca per cambiare colore">
								<div class='scudo_top'></div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo("scudo_middle");" title="Clicca per cambiare colore">
								<div class='scudo_middle'></div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo("scudo_bottom_left");" title="Clicca per cambiare colore">
								<div class='scudo_bottom_left'></div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo("scudo_bottom_center");" title="Clicca per cambiare colore">
								<div class='scudo_bottom_center'></div>
							</a>
							<a href="#" onclick="javascript:Change_Scudo("scudo_bottom_right");" title="Clicca per cambiare colore">
								<div class='scudo_bottom_right'></div>
							</a>
						</div>
						</center>
						<input name="scudo_top" type="hidden" class="textfield" id="scudo_top" value="0"/>
						<input name="scudo_top" type="hidden" class="textfield" id="scudo_middle" value="0"/>
						<input name="scudo_top" type="hidden" class="textfield" id="scudo_bottom_left" value="0"/>
						<input name="scudo_top" type="hidden" class="textfield" id="scudo_bottom_center" value="0"/>
						<input name="scudo_top" type="hidden" class="textfield" id="scudo_bottom_right" value="0"/>
					</td> 
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
				<tr>
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
				  <td rowspan="2"><!-- <center>
						<input type="button" name="butplus"  id="butplus"  value="Sx" onclick='javascript:cambia_immagine(1);' class="fieldbutton"/>
						<input type="button" name="butmenus" id="butmenus" value="Dx" onclick='javascript:cambia_immagine(2);' class="fieldbutton"/>
						<input name="logosx" type="hidden" class="textfield" id="logosx" value="0"/>
						<input name="logodx" type="hidden" class="textfield" id="logodx" value="0"/>-->
				  <a href="#" onclick="document.forms.RegisterForm.submit(); return false;" class="button">invia i dati</a>
				  
				  </td>
				</tr>
				<tr>
				  <th align="right">Conferma Password </th>
				  <td align="left"><input name="cpassword" type="password" class="textfield" id="cpassword" title="Inserisci nuovamente la password prescelta per conferma" /></td>
				</tr>
				<tr>
				  
				  <td colspan="2" align="left"><!-- <a href="#" onclick="document.forms.RegisterForm.submit(); return false;" class="button">invia i dati</a>--></td>
				  
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
</center>
<script>
	allarga_maschera('register',70,ValAlt('register')+<?php echo $righe_in_piu ?>);
</script>


