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
</head>

<?php
	$nome_team = $_SESSION['SESS_TEAM'];
	$nome_utente = $_SESSION['SESS_USER'];
	$serie = $_SESSION['SESS_SERIE'];
	
	$b_allenatore = 0;
	$b_viceallena = 0;
	$b_alleportie = 0;
	
	$result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ORDER BY s_id_staff");
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	
	$link = array("form_staff.php","form_staff.php?pagina=1");
	$titolo = array("Il tuo staff","Offerte personale");
		
	if (!isset($_REQUEST['pagina']))
	{
		$link[0] = "#";
		$pagina = 0;
		$classe1="a1";
		$classe2="a3";
	} else {
		$link[$_REQUEST['pagina']] = "#";
		$pagina = $_REQUEST['pagina'];
		$classe1="a3";
		$classe2="a1";
	}
	
?>
<body>
<h1><font style="font-weight:bold; color:#00FFFF; font-size:18px; font-family:Verdana, Arial, Helvetica, sans-serif;">Staff</font></h1>


<div id="corpo">
	<div style="float:left;">
		<span class="top-label">  
			<span class="label-txt"><a class="<?php echo $classe1; ?>" href="<?php echo $link[0]; ?>">Dipendenti</a></span>
			<span class="label-txt"><a class="<?php echo $classe2; ?>" href="<?php echo $link[1]; ?>" >Candidati</a></span>
		</span>
		<!-- DATI STAFF -->
		<?php 
		if (!isset($_REQUEST['pagina']))
		{
		?>
		<div class="content-area"> 
			<img id="staff" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="staff">
				<fieldset id='staff'>
					<?php include "staff_list.php"; ?>		
				</fieldset>
			</span>
		</div>
		<br />
	
		<span class="top-label">  
			<span class="label-txt">Bonus Partita</span>
		</span> 
		<div class="content-area"> 
			<img id="staff_bon" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="staff_bon">
				<fieldset id='staff_bon'>
					<?php include "staff_bonus.php"; ?>	
				</fieldset>
			</span>
		</div>
	
	</div>
	<div style="float:right;">	
		<span class="top-label">  
			<span class="label-txt">Dettagli</span>
		</span> 
		<div class="content-area"> 
			<img id="staff_motiva" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="staff_motiva">
				<fieldset id='staff_motiva'>
					<iframe src="staff_motiva.php?id=0" name="ApriFinestra" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" >
					</iframe>
				</fieldset>
			</span>
		</div>
	</div>
		
	
	<?php
	} elseif ($_REQUEST['pagina'] == 1) {
	?>
	
		<div class="content-area"> 
			<img id="staff_offerte" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="staff_offerte">
				<fieldset id='staff_offerte'>
					<div id='staff_offerte'>
						<?php include "staff_offerte.php"; ?>				
					</div>
				</fieldset>
			</span>
		</div>
	</div>
	
	<div style="float:right;">
		<span class="top-label">  
			<span class="label-txt">Contrattazione</span>
		</span> 
		<div class="content-area"> 
			<img id="staff_contra" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="staff_contra">
				<fieldset id='staff_contra'>
					<iframe src="staff_contra.php?id=0" name="contra" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" >
					</iframe>
				</fieldset>
			</span>
		</div>
	</div>
	<?php
	}
	?>

</div>

<script>
	$('#corpo').slideUp(0).delay(300).fadeIn(600);
</script>


</body>
</html>

