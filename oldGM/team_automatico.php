<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
<?php 
	$quale_css = "index".$_SESSION['SESS_LARGHEZZA'].".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
?>

</head>

<body>
<div id="auto_pleasewait"><img src="images/pleasewait.gif" /></div>
			
<?php 

if (isset($_REQUEST['box']))
{
	$posto= $_REQUEST['box'];
	$giocatore= $_REQUEST['maglia'];
	$wformazione = $_REQUEST['formazione'];
}
else
{
	$posto = 0;
	$giocatore = 0;
	$wformazione = "Formazione 1";
}

//echo $altezza.$stile_sx;

echo "<h1>";
echo "<table width='350' border='0' cellpadding='0' cellspacing='0'>";
echo "<tr>";
echo "<td>Calcolo Automatico Formazione</td>";
echo "</tr>";
echo "</table>";
echo "</h1>";

	echo "<div style=\"$stile_sx\">";
	echo "<iframe src='team_auto_sx.php'  width='$larg_sx' height='$altezzasx' allowtransparency='1' name='calc-sx' frameborder='0' scrolling='1' >		
		</iframe>";
	echo "</div>";	
	
	echo"<div style=\"$stile_dx\"> 
		<iframe src='team_auto_dx.php'  width='$larg_dx' height='$altezzadx' allowtransparency='1' name='calc-dx' scrolling='no' frameborder='0'>
		</iframe>
	  </div>";

?>
</body>
</html>
