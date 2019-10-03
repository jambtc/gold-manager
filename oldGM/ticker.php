<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<?php 
		$quale_css = "index_live".$_SESSION['SESS_LARGHEZZA'].".css";
		echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
	?>

<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>

<body>
<?php
	echo "<h1>Live Ticker</h1>";
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$serie = $_SESSION['SESS_SERIE'];

	$ora = date("H",$Oggi);
	$minuti = date("i",$Oggi);
	$OraInizio = "10:00";	// da selezionare da un archivio di confiturazione che bisogna creare successivamente per gestire il campionato
	
	$c_goal=0;
	$f_goal=0;

	$immbarretta = "images/barretta.png";
	$immbianca = "images/barrettabianca.png";

	$qry = "SELECT * FROM z_calendario WHERE data<='$datasql' AND serie='$serie' ORDER BY data DESC";
	$result = mysql_query($qry);
	$row_calendario = mysql_fetch_array($result);	
	
	$ID_PARTITA = $row_calendario['id_partita'];
	
	// stabilisce se gioca in casa o fuori
	if ($row_calendario['casa'] == $nome_team)
	{
		$sq_casa = $nome_team;
		$sq_fuori = $row_calendario['fuori'];
		$c_goal = $row_calendario['gol_casa'];
		$f_goal = $row_calendario['gol_fuori'];
	}
	else
	{
		$sq_fuori = $nome_team;
		$sq_casa = $row_calendario['fuori'];
		$f_goal = $row_calendario['gol_casa'];
		$c_goal = $row_calendario['gol_fuori'];
	}
		
	
	if ($row_calendario['giocata'] == 1)
	{
		//CARICO I DATI DELLA PARTITA
		$qry = "SELECT * FROM z_partita WHERE id_partita='$ID_PARTITA'";
		$result = mysql_query($qry);
		$row_partita = mysql_fetch_array($result);
		
		if ($row_partita['sorteggio'] == 1)
		{
			$c_stanchezza = $row_partita['c_frs'];
			$f_stanchezza = $row_partita['f_frs'];
			
		}
		else
		{
			$f_stanchezza = $row_partita['c_frs'];
			$c_stanchezza = $row_partita['f_frs'];
		}
				
		//CARICO LA TELECRONACA !!!
		$qry = "SELECT * FROM z_telecronaca WHERE id_partita='$ID_PARTITA' ORDER BY prog";
		$result = mysql_query($qry);
		$row_telecronaca = mysql_fetch_array($result);	
		
		echo "<div id='ticker_svolgimento'>";
			include("ticker_svolgimento.php");
		echo "</div>";
		
		echo "<div id='ticker_formazioni'>";
			//include("ticker_formazioni.php");
			echo "<iframe src='ticker_formazioni.php?id=$ID_PARTITA' name='ticker_dx' width='100%' marginwidth='0' height='100%' align='top' allowtransparency='1' frameborder='0' scrolling='no' hspace='0' vspace='0' id='iframe' title='contenuti'>";
		echo "</div>";
		
		
		
	}
	else
	{
		// partita da giocare
		echo "partita da giocare";
		//echo "<body onLoad=\"javascript:tempo('$secondi')\">";
	}
		
	//print_r($row);
	
	
	
?>
</body>
</html>
