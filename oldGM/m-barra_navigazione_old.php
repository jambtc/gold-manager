<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	$quale_css = "index".$_SESSION['SESS_LARGHEZZA'].".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
	
	echo "<style type='text/css'> @import '64css/menu1024.css'; </style>";

?>

<script type="text/javascript" src="jquery/menu.js"></script>
</head>
	
<body>
	<center>
	
	<div style="float:left;">
		<a href="form_guestbook.php" target="_top"><img src="images/badge_blu_stampa.png" border="0" title="Comunicato Stampa"/></a>
	</div>
		
		<ul id="tabBar">
			<li><a href="#" rel="menutrigger" name="societa" target="_top">Società</a>
			</li>
		</ul>
		<ul id="tabBar">
			<li><a href="#" rel="menutrigger" name="serie" target="_top">Serie</a>
			</li>
		</ul>
		<ul id="tabBar">
			<li><a href="#" rel="menutrigger" name="squadra">Squadra</a>
			</li>
		</ul>
		<ul id="tabBar">
			<li><a href="#" rel="menutrigger" name="sede" target="_top">Club House</a>
			</li>
		</ul>
		
		
		
		<div id="subNav">
			<ul id="sedeNav">
				<li><a href="m-index.php" target="_top"/>Sede</a>
				</li>	
				<li><a href="form_impostazioni.php" target="_top"/>Impostazioni</a>
				</li>	
				<li>
					<?php
						$largh = $_SESSION['SESS_LARGHEZZA'];
						$altez = $_SESSION['SESS_ALTEZZA'];
						$pagina = "logout-page.php?l=$largh&a=$altez";
					?>
					<a href="<?php echo $pagina; ?>" target='_top'/>Uscita</a> 

				</li>	
				
			</ul>
			<ul id="squadraNav">
				<li><a href="form_giocatori.php" target="_top"/>Giocatori</a>
				</li>
				<li><a href="form_allenamento.php" target="_top"/>Allenamento</a>
				</li>
				<li><a href="form_formazione.php" target="_top"/>Formazione</a>
				</li>
				<li><a href="form_automatico.php" target="_top"/>F.Automatica</a>
				</li>	
				<li><a href="form_calcolatore.php" target="_top"/>Calcolatore</a>
				</li>		
			</ul>
			
			<ul id="societaNav">
				<li><a href="form_staff.php" target="_top" />Staff</a>
				</li>	
				<li><a href="form_statistiche.php" target="_top"/>Statistiche</a>
				</li>	
			</ul>
	
	
			<ul id="serieNav">
				<li><a href="form_classifica.php" target="_top"/>Classifica</a>
				</li>	
				<li><a href="form_calendario.php" target="_top"/>Calendario</a>
				</li>	
				<li><a href="form_live.php" target="_top"/>Live&nbsp;Ticker</a>
				</li>	
				<li><a href="form_1vs1.php" target="_top"/>1vs1</a>
				</li>	
			</ul>
	
		</div>
	</div>
	</center>

	
	
</body>
</html>