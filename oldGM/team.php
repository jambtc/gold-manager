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

	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}

	$totale = mysql_num_rows($result);
?>
<body>
<h1><font style="font-weight:bold; color:#00FFFF; font-size:18px; font-family:Verdana, Arial, Helvetica, sans-serif;">Giocatori&nbsp;(<?php echo $totale ?>)</font></h1>

<div id="corpo">
	<div style="float: left;">
		<div class="content-area"> 
			<img id="giocatori" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="giocatori">
				<fieldset id='giocatori'>
					<div id='giocatori'>
						<iframe src="team_list.php" name="team_list" width="100%" marginwidth="0" height="155%" align="top" allowtransparency="1" frameborder="0" scrolling="no" >
						</iframe>
					</div>
				</fieldset>
			</span>
		</div>
	</div>
</div>
<script>
	$('#corpo').slideUp(0).delay(300).fadeIn(600);
</script>
</body>
</html>

