<?php 
	session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<LINK REL="SHORTCUT ICON" HREF="images/favicon.ico">

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title> Gold Manager - Gioco di calcio manageriale </title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta http-equiv="Content-Style-Type" content="text/css;">
<meta name="Author" content="Sergio Casizzone">
<meta name="Keywords" content="gold manager gioco calcio manager manageriale">
<meta name="Description" content="Gioco di calcio manageriale gratis. Ideato da Sergio Casizzone.">

<style type='text/css'> @import 'demo.css'; </style>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<script type="text/javascript" src="jquery/my_screen.js"></script>
<script type="text/javascript" src="jquery/my_script.js"></script>
<script type="text/javascript" src="jquery/footer.js"></script>
</head>
<?php 
	$cerca_browser[] = "symbian";
	$cerca_browser[] = "symbos";
	$cerca_browser[] = "symb";
	$cerca_browser[] = "linux";
	$cerca_browser[] = "msie";
	$cerca_browser[] = "firefox";
	$cerca_browser[] = "chrome";
	$cerca_browser[] = "safari";
	$cerca_browser[] = "presto";
		
	$browser = strtolower($_SERVER['HTTP_USER_AGENT']);
		
	foreach ($cerca_browser as $cisei)
	{
		$pos = strpos($browser, $cisei);
		if ($pos)
		{
			switch ($cisei)
			{
				case "symbian":
				case "symbos":
				case "symb":
					// vai a mobi-index.php
					?>
					<script>
						if (!(confirm("Desideri entrare nel portale Mobile?")))
						{
					    	return false;
						}
						else
						{
							location.href="mobi/index.php";
						}
					</script>
					<?php 
					break;
				/*case "presto":
				case "safari":
				case "chrome":
					// vai a chrome-index
					//header("location: chro-index.php");
					echo "I Browser Safari, Opera e Chrome, al momento non sono supportati. Si prega di utilizzare Firefox oppure xplorer";
					exit;
					break;*/
				default:
					break;
			}
		}
	}
	
	// identificativo numerico della pagina
	$pagina = 1;
	
	// connessione al database MySQL
	include "connect_db.php";	
	
	// numero di visite attuali
	$Oggi = time();
	$g1 = date("j",$Oggi);
	$m1 = date("m",$Oggi);
	$a1 = date("Y",$Oggi);
	$dataIta = $g1."/".$m1."/".$a1;
	$separatore = "/";
	// data in formato sql  
	$split_data = explode($separatore, $dataIta);
	$datasql = $split_data[2] . "-" . $split_data[1] . "-" . $split_data[0]; 
	$ip = getenv("REMOTE_ADDR"); // get the ip number of the user
	
	$verif = mysql_query("SELECT * FROM contatore WHERE pagina = $pagina");
	$tcpip = mysql_query("SELECT * FROM stat_tcp WHERE tcp='$ip' AND data='$datasql' AND pagina='$pagina'");
	$num = mysql_num_rows($verif);
	$ripeti = mysql_num_rows($tcpip);
	
	if ($ripeti == 0)
	{
		mysql_query("INSERT INTO stat_tcp (data, tcp, pagina) VALUES ('$datasql', '$ip', '$pagina')");
		if ($num == 0)
		{ 
			// pagina non presente nel database
			// aggiungo la pagina nella tabella
			mysql_query("INSERT INTO contatore (pagina, visite) VALUES ($pagina, 1)");
		}
		else
		{
			$res = mysql_query("UPDATE contatore SET visite = visite + 1 WHERE pagina = $pagina"); 
		}
	} 
	else
	{
		$res = mysql_query("UPDATE contatore SET totali = totali + 1 WHERE pagina = $pagina");
	}
?>
<body onload="javascript:allarga_schermo();">

<div id="main-container" class="contenitore_principale">
	<?php include("intestazione.php")?>	
	
	<div class="no_container">
	    <div class="no_content-area">
			<div id="corpo_intestazione" class='corpo_intestazione'>
				<?php
					/*if( isset($_SESSION['SESS_USER']) )
					{
						unset($_SESSION['SESS_USER']);
						unset($_SESSION['SESS_TEAM']);
						unset($_SESSION['SESS_SERIE']);
						unset($_SESSION['SESS_SX']);
						unset($_SESSION['SESS_DX']);
						unset($_SESSION['SESS_PRIVILEGI']);
						echo "<h1>E' stata trovata una sessione già aperta.<br>Per ragioni di sicurezza devi effettuare un nuovo LOGIN!</h1>";
						exit;
					}
					else
					{*/
						if (!isset($_REQUEST['fnz']))
						{
							include("corpo.php");
						}
						else
						{
							switch ($_REQUEST['fnz'])
							{
								case "privacy":
									include("privacy.php");
									break;
								case "recpwd":
									include("form_sendpwd.php");
									break;
								case "recpwdok":
									include("form_sendpwd-ok.php");
									break;
								case "register":
									include("form_register.php");
									break;
								case "registerok":
									include("form_register-ok.php");
									break;
								case "loginfailed":
									include("login-failed.php");
									break;
								case "logout":
									include("logout.php");
									break;
								case "accessdenied":
									include("access_denied.php");
									break;
									
								default:
									$stampa_funzione = $_REQUEST['fnz'];
									echo "<h1>Manca il file per la funzione: $stampa_funzione</h1>";
									break;
							}
						}
					//}
					
					
				?>

			</div>	
    	    <div class="clear"></div>
        </div>
    </div>

	<div id="topbar" >
		<?php include "footer.php";	?>
	</div>	
</div>

			
<script>
	$('#corpo_intestazione').slideUp(0).delay(300).fadeIn(600);
</script>

</body>
</html>
