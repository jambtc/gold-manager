<?php
	require_once('auth.php');
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<title> Golden Manager - Pannello di controllo</title>

<head>

<script type="text/javascript">
function crea()
{
	arg1 = document.getElementById('nomesquadra').value ;
	
	if (arg1 == "")
	{
		alert("Inserire il nome della squadra");
		return false;
	}

	indirizzo="crea_squadre.php?team="+arg1;
	window.location.href=indirizzo;
}

</script>

</head>

<?php 
	include "connect_db.php";	
	
	$giorni = array("Domenica","Lunedi","Martedi","Mercoledi","Giovedi","Venerdi","Sabato");
	
	if (isset($_REQUEST['salva']))
	{
		$data = $_POST['datastart'];
		$giorno = $_POST['giorno'];
		$orario = $_POST['orariostart'];
		$squadre= $_POST['squadre'];
		
		$qry = "UPDATE zz_config SET data='$data',
									giorno='$giorno',
									orario='$orario',
									squadre='$squadre' WHERE id=1";
	
		$result = mysql_query($qry);
		if (!$result)
		{
    		echo 'Errore nella query: ' . mysql_error();
    		exit();
		}
	}
	else
	{
		$qry = "SELECT * FROM  zz_config WHERE id=1";
	
		$result = mysql_query($qry);
		if (!$result)
		{
    		echo 'Errore nella query: ' . mysql_error();
    		exit();
		}
		$row   =   mysql_fetch_array($result);
		
		$data = $row['data'];
		$giorno = $row['giorno'];
		$orario = $row['orario'];
		$squadre= $row['squadre'];
	}
	
?>

<body>
	<div>
		<li>
			<a href="phpinfo.php" target="_blank" />PhpInfo</a>
		</li>
		<li>
			<a href="serie_verifica.php" target="_blank" />Verifica Squadre di tutte le serie</a>
		</li>
		<li>
			<a href="crea_calendario.php" target="_blank" />Crea&nbsp;Calendario</a>
		</li>
		<li>
			<table>
				<tr>
					<td>Crea&nbsp;Squadre</td>
					<td><input id="nomesquadra" name="nomesquadra" type="text" /></td>
					<td><input type="button" value="Crea" onclick="javascript:crea();"/></td>
				</tr>
			</table>
						
		</li>
		<li>
			<form id='importa' name='importa' method='post'  enctype='multipart/form-data' action='importa_cognomi.php'>
			<table border='0' cellspacing='0' >
    			<tr>
			    	<th>Carica file cognomi e nomi</th>
					<td><input type='file' name='nomefile' value='Scegli...'   class='fieldbutton' style='width:100%;'></td>
				
					<td>&nbsp;</td>
					<td><input type='submit' name='Submit' value='Carica file'  class='fieldbutton' ></td>
		    	</tr>
			</table>
			</form>
		</li>
		<li>
			<a href="form_logout.php?lar=1024&alt=768" target='_top'/>Uscita</a> 
		</li>
	</div>
	<div>
		<hr />
		<form action="m-pannello.php?salva=1" method="post" >
		<table>
		<tr>
			<th>Data Inizio Campionato</th>
			<td><input name="datastart" type="date" value="<?php echo $data ?>" /></td>
		</tr>
		<tr>
			<th>Giorno in cui si disputano partite</th>
			<td>
				<select name='giorno'>
					<?php 
						echo "<option value='$giorno' selected='selected'>$giorni[$giorno]</option>
							  <option value='$giorno'><hr></option>";
			   					$x=0;
								foreach ($giorni as $iter_day)
							  	{
									$video_ruolo = $giorni[$x];
									echo "<option value='$x'>$video_ruolo</option>";
									$x++;
								}
					?>
			    </select>
				
				
			</td>
		</tr>
		<tr>
			<th>Orario di inizio delle partite</th>
			<td><input name="orariostart" type="text" value="<?php echo $orario ?>" /></td>
		</tr>
		<tr>
			<th>Numero di squadre per divisione</th>
			<td><input name="squadre" type="text" value="<?php echo $squadre ?>" /></td>
		</tr>
		<tr>
			<th>&nbsp;</th>
			<td><input type="submit" value="Salva" /></td>
		</tr>
		
		</table>
		</form>
	</div>
</body>
</html>
     
     



