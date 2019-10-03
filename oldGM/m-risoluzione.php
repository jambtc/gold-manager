<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	
<script type="text/javascript">
function getBrowserHeight()
{
	alt = window.screen.height;
	larg = window.screen.width;
	
	indirizzo = "m-risoluzione.php?altezza="+alt+"&larghezza="+larg;

	window.location.href=indirizzo;
}
</script>	
	
</head>
<?php
	if (!isset($_GET['altezza']))
	{
		echo "<script>getBrowserHeight();</script>";
	}
	else
	{
	/*echo	$_SESSION['SESS_TEAM']."<br>".
			$_SESSION['SESS_USER']."<br>".
			$_SESSION['SESS_SERIE']."<br>".
			$_SESSION['SESS_SX']."<br>".
			$_SESSION['SESS_DX']."<br>".
			$_SESSION['SESS_PRIVILEGI']."<br>".
			$_SESSION['SESS_TIPO'];*/
				
		//Start session
		session_start();
		
		
		$_SESSION['SESS_ALTEZZA'] = $_GET['altezza'];
		$_SESSION['SESS_LARGHEZZA'] = $_GET['larghezza'];
	
		//echo 	$_SESSION['SESS_ALTEZZA']."<br>".$_SESSION['SESS_LARGHEZZA'];
		
		session_write_close();
		header("location: m-index.php");
	}
	
?>

<body>
</body>
</html>
