<?php 
	header("Content-Type: application/text");
	header("Content-Disposition: attachment; filename=archivio.csv");

	require_once('auth.php');
	include "connect_db.php";	
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	//Seleziono la tabella del quale voglio effettuare l'esportazione CSV
	$tabella = "giocatori";
	
	$info = array("nr","nome","eta","skill","pos","carattere","forma","fresc","cond","esp","po","df","cn","pa","rg","cr","tc","tr","piede","tipo","qta","part","reti","gialli","rossi","stipendio","valore");

	$riga_1 = array("Num.","Nominativo","Età","Skill","Ruolo","Carattere","Forma","Freschezza","Condizione","Esperienza","Parate","Difesa","Contrasti","Passaggi","Regia","Cross","Tecnica","Tiro","Piede","Talento","Livello Talento","Partite","Reti","Gialli","Rossi","Stipendio","Valore di Mercato");

	$totcampi = count($info);
	$resdoc = mysql_query("SELECT * FROM giocatori WHERE id_team='$nome_team'");
	
	$intest = "";
	$riga = "";
	
	ob_start();
	ob_get_clean();
	
	foreach ($riga_1 as $msg)
	{
		echo $msg.";";
	}
	echo "\n";
	
	while 	($row = mysql_fetch_array($resdoc))
	{
		$riga = "";
		for ($i = 0; $i < $totcampi; $i++)
		{
			$riga .= $row[$info[$i]].";";
		}
		print "$riga\n";
	}
	
	$contenuto= ob_get_contents();
	//Pulisce il Buffer di Output
	ob_end_clean();
 
	//A questo punto creo il file CSV 
	print $contenuto;?>
