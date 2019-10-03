<?php
	$id=substr($_POST['id'],1,1);
?>
<style type="text/css">
#SecondMenu { 
} 

#SecondMenu li { 
	list-style: none; 
	display: block; 
	float: left; 
	} 
</style>

<div id="left_shelf"></div>
<div id="shelf">
	<ul id="SecondMenu"> 
		<?php 
		switch ($id)
		{
			case 1:
		?>
			<li><a href="m-index.php"><img src="icons/sede.png" alt="Scrivania" title="Leggi i documenti sulla scrivania" /></a></li>
			<li><a href="m-index.php?fnz=statistiche&pg=12"><img src="icons/stat.png" alt="Statistiche" title="Controlla le Statistiche" /></a></li>
			<li><a href="m-index.php?fnz=stampa&pg=13"><img src="icons/news.png" alt="Comunicati Stampa" title="Annuncia un Comunicato Stampa" /></a></li>
			<li><a href="m-index.php?fnz=setup&pg=14"><img src="icons/tools.png" alt="Impostazioni" title="Impostazioni" /></a></li>
			<li><a href="m-index.php?fnz=logout&pg=1"><img src="icons/uscita.png" alt="Uscita" title="Uscita dal gioco" /></a></li>
		<?php 
			break;
			case 2:
		?>
			<li><a href="#"><img src="icons/stadio.png" alt="Stadio" title="Gestisci lo Stadio" /></a></li>
			<li><a href="m-index.php?fnz=staff&pg=22"><img src="icons/staff.png" alt="Staff" title="Verifica lo Staff" /></a></li>
			<li><a href="#"><img src="icons/sponsor.png" alt="Sponsor" title="Controlla i tuoi Sponsor" /></a></li>
			<li><a href="#"><img src="icons/mercato.png" alt="Calcio Mercato" title="Calcio Mercato" /></a></li>
			<li><a href="#"><img src="icons/banca.png" alt="Banca" title="Vai in Banca" /></a></li>
			<li><a href="#"><img src="icons/bilancio.png" alt="Bilancio" title="Bilancio societario" /></a></li>
		<?php 
			break;
			case 3:
		?>
			<li><a href="m-index.php?fnz=team&pg=31"><img src="icons/giocatore.png" alt="Giocatori" title="I tuoi giocatori" /></a></li>
			<li><a href="m-index.php?fnz=allena&pg=32"><img src="icons/allenamento.png" alt="Allenamento" title="Gestisci l'allenamento" /></a></li>
			<li><a href="m-index.php?fnz=formazione&pg=33"><img src="icons/formazione.png" alt="Formazione" title="Prepara la Formazione tipo" /></a></li>
			<li><a href="#"><img src="icons/primavera.png" alt="Primavera" title="Primavera" /></a></li>
			
		<?php 
			break;
			case 4:
		?>
			<li><a href="m-index.php?fnz=classifica&pg=41"><img src="icons/classifica.png" alt="Classifica" title="Classifica" /></a></li>
			<li><a href="m-index.php?fnz=calendario&pg=42"><img src="icons/calendario.png" alt="Calendario" title="Calendario" /></a></li>
			<li><a href="#"><img src="icons/partita.png" alt="Partita" title="Ultima partita disputata" /></a></li>
			<li><a href="#"><img src="icons/sfida.png" alt="Sfida 1 vs 1" title="Sfida 1 vs 1" /></a></li>	
		
		
		<?php 
			break;
		}
		?>
	</ul> 
</div>
<div id="right_shelf"></div>


