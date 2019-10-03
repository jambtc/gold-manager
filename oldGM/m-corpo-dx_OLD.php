<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php 
	$quale_css = "index_corpo".$_SESSION['SESS_LARGHEZZA'].".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
	?>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	
</head>
<body>

<?php 
	if (isset($_REQUEST['scelta']))  { 	$a = $_REQUEST['scelta'];	}
	else{ 	$a = 0; }

	if ($a == 0) {
		echo "<h1>&nbsp;</h1>";
		echo "<form name='paginavuota'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h6><br>Nessuna scelta effettuata.<br></h6>";
		echo "</fieldset>";
		echo "</form>";
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
	}
	
	if ($a == 1){
		echo "<h1>&nbsp;</h1>";
		echo "<form name='Giocatori'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Appena registrato, hai un pò di lavoro da fare. Inserisci i tuoi giocatori e aggiorna le informazioni a loro relative. Ricordati che è necessario indicare il <strong>Numero di maglia</strong> del giocatore. Il parametro &quot;Skill&quot; nella sezione &quot;DATI PERSONALI&quot; equivale alla Forza del giocatore; inserendo questo parametro devi mettere il segno decimale (es. 1.2 oppure 0.8). Il parametro &quot;Livello Talento&quot; nella sezione &quot;ABILITA&acute; PERSONALI&quot; indica la quantità del Talento del giocatore (es. inserisci 2 per un giocatore con talento 2). Torna su questa schermata settimanalmente per aggiornare i parametri, per eliminare un giocatore venduto o per aggiungerne uno acquistato. Se sei un utente Premium di GoalUnited, premi il tasto importa CSV, per importare tutti i dati dei tuoi giocatori. Cliccando sul numero di maglietta del giocatore accederai direttamente al calcolatore di posizione, mentre cliccando sul suo nome potrai visualizzarne i parametri, modificarli oppure eliminarlo dalla squadra. <br>La colonna 'INF.' serve ad indicare se il giocatore è infortunato oppure squalificato. In tal modo nella sezione 'Formazione' non sarà possibile inserirlo in squadra, e il 'Calcolatore Automatico' considererà il giocatore non utilizzabile. <br>Con il pulsante &quot;Esporta&quot; puoi salvare i tuoi giocatori in un fiole .CSV da leggere con Excel, oppure per avere un backup.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
	}
	
	if ($a == 2){
		echo "<h1>&nbsp;</h1>";
		echo "<form name='Allenamento'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Imposta l'allenamento SKILL e TATTICHE così come fai in GoalUnited. Se il valore degli allenamenti differiscono o li stai inserendo per la prima volta, usa le freccette < e > per diminuire o aumentarne di un punto percentuale il valore. Le freccette << e >> diminuiscono ed aumentano più velocemente i valori da impostare. Ogni lunedi, i valori degli allenamenti vengono aggiornati in automatico così come accade in GU. I valori che avrete inserito in questa sezione influenzano il calcolo automatico della formazione. Quindi fate attenzione ad immettere dei valori che più si accostano al reale.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
	}
	if ($a == 3){
		echo "<h1>&nbsp;</h1>";
		echo "<form name='Staff'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Inserisci le caratteristiche del tuo staff per tenere sotto controllo  il valore della loro efficienza (che dovrebbe tendere a 100), in base alla quale &egrave; visibile il relativo BONUS che si avr&agrave; durante la partita, oltre alla spesa settimanale. Ciascun membro dello staff ha le seguenti caratteristiche: <a href='m-corpo_staff_spiega.php?scelta=1' target='corpo_spiega' >Allenatore</a>, <a href='m-corpo_staff_spiega.php?scelta=2' target='corpo_spiega' >Vice allenatore</a>, <a href='m-corpo_staff_spiega.php?scelta=3' target='corpo_spiega' >Fisioterapista</a>, <a href='m-corpo_staff_spiega.php?scelta=4' target='corpo_spiega' >Psicologo sportivo</a>, <a href='m-corpo_staff_spiega.php?scelta=5' target='corpo_spiega' >Allenatore portieri</a>, <a href='m-corpo_staff_spiega.php?scelta=6' target='corpo_spiega' >Allenatore primavera</a>, <a href='m-corpo_staff_spiega.php?scelta=7' target='corpo_spiega' >Addetto stampa</a>, <a href='m-corpo_staff_spiega.php?scelta=8' target='corpo_spiega' >Commercialista</a>, <a href='m-corpo_staff_spiega.php?scelta=9' target='corpo_spiega'>Medico</a>, <a href='m-corpo_staff_spiega.php?scelta=10' target='corpo_spiega' >Preparatore atletico</a>.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";		
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		echo "<br/>";
		echo "<div id='m_corpo_staff'>";
		echo "<iframe src='m-corpo_staff_spiega.php' name='corpo_spiega' width='100%' marginwidth='0' height='100%' align='top' allowtransparency='1' frameborder='0' scrolling='no' hspace='0' vspace='0' title='contenuti'  >";
		echo "</iframe>";
		echo "</div>";

	}
	
	if ($a == 4) {
		echo "<h1>&nbsp;</h1>";
		echo "<form name='Formazione'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Verifica la Forza di ciascun giocatore nella parte di campo a lui congeniale. Spuntando la casella di controllo &quot;Condizione Atletica Generale&quot; verrà visualizzata la forza di ciascun giocatore condizionata dai rispettivi valori di Forma, Freschezza e Condizione. Inserisci il giocatore nelle caselle di campo delineate dalla riga rossa. E' sufficiente cliccare sulla casella di campo e quindi sul nome giocatore da inserire. Cliccando, invece sul numero di mamglietta, accederai al calcolatore di posizione del singolo giocatore. In basso a destra vengono visualizzati i  valori di Portiere, Difesa, Centrocampo e Attacco. Per visualizzare i BONUS che danno in partita gli allenatori, selezionare la casella di controllo relativa; per quanto riguarda le tattiche &egrave disponibile un menù da cui scegliere la propria tattica e visualizzarne, quindi, la rispettiva influenza nei diversi quadranti del campo di gioco. Inoltre &egrave; visibile in percentuale la Forza della squadra, la Forma e la Forza della Panchina. E' possibile salvare fino a 5 formazioni predefinite.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
	}
	
	if ($a == 5) {
		echo "<h1>&nbsp;</h1>";
		echo "<form name='Statistiche'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Controlla i dati statistici della tua squadra. Controlla i dati della tua rosa, la forza e il valore dei migliori giocatori. Puoi verificare i valori medi e totali della Preparazione Atletica, delle Abilit&agrave; Personali, del Bilancio e dei risultati ottenuti divisi ciascuno per Portiere, Difesa, Centrocampo e Attacco.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
	}
	
	if ($a == 6) {
		echo "<h1>&nbsp;</h1>";
		echo "<form name='Calcolatore'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Inserisci i dati del giocatore da verificare. Il Calcolatore è attivo anche attraverso Gestione Giocatori e Gestione Formazione, basta cliccare sul numero di maglia del giocatore. Premendo sul tasto &quot;Calcola Formazione&quot; verrà attivato il calcolo per cercare la migliore formazione disponibile con la visualizzazione dei relativi parametri. E' possibile, inoltre, modificare la visualizzazione degli altri parametri selezionando la formazione, la tattica, la marcatura, il bonus allenatore e la migliore condizione fisica dei giocatori. E&acute;,inoltre, disponibile la visualizzazione della vostra formazione IDEALE, oltra a quella allenata da voi. E&acute; necessario, in questo caso, aver impostato i dati nella sezione Allenamento Tattiche.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
	}
	
	if ($a == 7) {
		echo "<h1>&nbsp;</h1>";
		echo "<form name='Guestbook'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Inserisci i tuoi commenti sul gioco oppure comunica con gli altri utenti di GoalUnited Analysis.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
	}
	
		if ($a == 8)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Clicca su Sede per tornare alla Home page.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
	
		if ($a == 9)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Modifica il dati del tuo profilo come la serie, la password, l'e-mail e i colori della tua squadra.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
		
		if ($a == 10)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Clicca sull'immagine per uscire da GoalUnited Analysis.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}

		if ($a == 11)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Guarda la classifica del campionato GU Analysis.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}

		if ($a == 12)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Guarda il calendario del campionato GU Analysis.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
	
		if ($a == 13)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Guarda la partita in diretta, oppure il resoconto se è stata già giocata.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}

		if ($a == 14)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Sfida un membro di GoalUnited Analysis.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
		if ($a == 15)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Iscriviti al Campionato di GU Analysis.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
		if ($a == 16)
		{
		echo "<h1>&nbsp;</h1>";
		echo "<form name='sede'>";
		echo "<fieldset style='width: 94%;'>";
		echo "<div id='MsgPausa'>";
		echo "<h3><br>";
		echo "<div align='justify'><span class='Stile22 Stile4'>Il calcolatore mostra la formazione migliore utilizzando i tuoi giocatori. Puoi visualizzare anche la formazione tipo in base ai parametri che hai inserito nella sezione 'Allenamento'. Nel riquadro di sinistra hai la lista dei vari quadranti e i relativi giocatori che possono ricoprirli. I giocatori vengono posizionati nei quadranti di destra e di sinistra in base al loro piede e in base al ruolo che ricoprono. (Es. Centrocampista Sinistro: viene inserito solo nei quadranti di sinistra). Se nella sezione 'Giocatori' hai inserito 'XX' come ruolo, questo viene interpretato come jolly, pertanto questo giocatore può ricoprire ogni quadrante.</span></div>";
		echo"<br></h3>";
		echo "</fieldset>";
		echo "</form>";	
		echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
		if ($a == 17)
		{
			echo "<div id='phpingo' style='width:800px;height:600px;overflow-y: scroll; '>";
			
			echo "risoluzione: ".$_SESSION['SESS_LARGHEZZA']." x ".$_SESSION['SESS_ALTEZZA'];
			
			phpinfo();
			echo "</div>";
		}
?>
<body>
</body>
</html>
