<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	$quale_css = "64css/dddddmenu".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "64css/menu.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>

<script type="text/javascript" src="jquery/menu.js"></script>
</head>
	
<body onload="navMenu()">
	<center>
	
	<div id="nav">
		<ul id="tabBar">
			<li>
			<a href="#" rel="menutrigger" name="campionato" target="_top">CAMPIONATO</a>
			</li>
		</ul>
		<ul id="tabBar">
			<li>
			<a href="#" rel="menutrigger" name="primavera" target="_top">PRIMAVERA</a>
			</li>
		</ul>
		<ul id="tabBar">
			<li>
			<a href="#" rel="menutrigger" name="squadra" target="_top">SQUADRA</a>
			</li>
		</ul>
		<ul id="tabBar">
			<li>
			<a href="#" rel="menutrigger" name="societa" target="_top">SOCIETA'</a>
			</li>
		</ul>
		<ul id="tabBar">
			<li>
			<a href="#" rel="menutrigger" name="ufficio" target="_top">UFFICIO</a>
			</li>
		</ul>
		
		
		
		<div id="subNav">
			<ul id="ufficioNav">
				<li><a href="m-index.php" target="_top"/>Sede</a>
				</li>	
				<li><a href="form_statistiche.php" target="_top"/>Statistiche</a>
				</li>
				<li><a href="form_stampa.php" target="_top"/>Comunicati Stampa</a>
				</li>
				<li><a href="form_impostazioni.php" target="_top"/>Impostazioni</a>
				</li>	
				<li>
					<a href="form_logout.php" target="_top" />Uscita</a> 
				</li>	
				
			</ul>
			<ul id="societaNav">
				<li><a href="form_stadio.php" target="_top" />Stadio</a>
				</li>	
				<li><a href="form_staff.php" target="_top" />Staff</a>
				</li>	
				<li><a href="form_primavera.php" target="_top" />Gestione Primavera</a>
				</li>	
				<li><a href="form_sponsor.php" target="_top" />Sponsor</a>
				</li>	
				<li><a href="form_banca.php" target="_top" />Banca</a>
				</li>	
					
			</ul>
			<ul id="squadraNav">
				<li><a href="form_giocatori.php" target="_top"/>Giocatori</a>
				</li>
				<li><a href="form_allenamento.php" target="_top"/>Allenamento</a>
				</li>
				<li><a href="form_formazione.php" target="_top"/>Formazione</a>
				</li>
				<li><a href="form_mercato.php" target="_top"/>Calcio&nbsp;Mercato</a>
				</li>		
			</ul>
			<ul id="primaveraNav">
				<li><a href="form_pr_giocatori.php" target="_top"/>Giocatori</a>
				</li>
				<li><a href="form_pr_allenamento.php" target="_top"/>Allenamento</a>
				</li>
				<li><a href="form_pr_formazione.php" target="_top"/>Formazione</a>
				</li>
				<li><a href="form_pr_classifica.php" target="_top"/>Classifica</a>
				</li>	
				<li><a href="form_pr_calendario.php" target="_top"/>Calendario</a>
				</li>		
			</ul>
			<ul id="campionatoNav">
				<li><a href="form_classifica.php" target="_top"/>Classifica</a>
				</li>	
				<li><a href="form_calendario.php" target="_top"/>Calendario</a>
				</li>	
				<li><a href="form_live.php" target="_top"/>Partite</a>
				</li>	
				<li><a href="form_1vs1.php" target="_top"/>Sfida 1 vs 1</a>
				</li>	
			</ul>
	
		</div>
	</div>
	</center>

	
	
</body>
</html>