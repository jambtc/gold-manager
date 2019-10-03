<?php
	require_once('auth.php');
	include "connect_db.php";	
	
	$team = $_REQUEST['team'];

	//Funzione di inserimento dati nella tabella giocatori
	function inserisci($team,$id,$nr_maglia,$nome,$eta,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore)
	{
		$qry = "INSERT INTO giocatori (id,id_team,nr,nome,eta,skill,pos,forma,fresc,cond,esp,
					po,df,cn,pa,rg,cr,tc,tr,
					piede,talento,qta,
					stipendio,valore,carattere,contratto) 			
				VALUES 
				('$id',\"$team\",\"$nr_maglia\",\"$nome\",\"$eta\",\"$skill\",
				\"$ruolo\",\"$forma\",\"$fresc\",\"$cond\",\"$esp\",	
				\"$po\",\"$df\",\"$cn\",\"$pa\",\"$rg\",\"$cr\",\"$tc\",\"$tr\",
				\"$pd\",\"$talento\",\"$qta\",
				\"$stipendio\",\"$valore\",\"$carattere\",9)";
				
		$result = mysql_query($qry);
		if (!$result)
		{
			echo 'Errore nella fase di creazione giocatori: ' . mysql_error();
			echo '<br>la query è:<br>'.$qry;
		    exit();
		}
		echo $nr_maglia.") ".$nome." ".$skill." ".$eta." ".$ruolo." ".$forma." ".$fresc." ".$cond." ".$esp." ".$po." ".$df." ".$cn." ".$pa." ".$rg." ".$cr." ".$tc." ".$tr." ".$pd." ".$talento." ".$carattere." ".$stipendio." ".$valore."<br>";
	}
	// FINE FUNZIONE
	
	
		
	echo "<h1>Creazione Giocatori di $team</h1>";
	echo "<div id='ticker_svolgimento'>";
	

	//CARICO LA CONFIGURAZIONE
	$qry = "SELECT * FROM  zz_config WHERE id=1";
	
	$result = mysql_query($qry);
	if (!$result)
	{
   		echo 'Errore nella query: ' . mysql_error();
   		exit();
	}
	$row   =   mysql_fetch_array($result);
		
	$config_data = $row['data'];
	$config_giorno = $row['giorno'];
	$config_orario = $row['orario'];
	$config_squadre= $row['squadre'];
	
	// LA DIVISIONE QUANDO CI SI ISCRIVE E' LA 3.
	// CI SONO x DIVISIONI DI n SQUADRE - LA ASSEGNO IN ORDINE DA 1 A x, 
	// VERIFICO SE SONO PIENE (MAX = n)
		
	$quale_serie = 1;
	$condizione = true;
	
	while ($condizione)
	{
		$qry = "SELECT * FROM z_iscritti WHERE serie='3.".$quale_serie."'";
		
		$result = mysql_query($qry);
		if (mysql_num_rows($result) == $config_squadre)
		{
			$quale_serie ++;
			continue; 
		}
		else
		{
			$condizione = false;
			break;
		}
	}
	$serie = "3.".$quale_serie;
	
	// seleziono i nomi propri
	$qry_nomi = mysql_query("SELECT * FROM nomi");
	while ($row = mysql_fetch_array($qry_nomi))
	{
		$nomi_nome[] = $row['nome'];
	}
	$nomi_ultimo = count($nomi_nome)-1;

	//seleziono i cognomi di persona
	$qry_cognomi = mysql_query("SELECT * FROM cognomi");
	while ($row = mysql_fetch_array($qry_cognomi))
	{
		$cognomi_nome[] = $row['cognome'];
	}
	$cognomi_ultimo = count($cognomi_nome)-1;
	
	// conto l'id dei giocatori
	$qry_contatore = mysql_query("SELECT * FROM contatore WHERE pagina = '90701'");
	$row = mysql_fetch_array($qry_contatore);
	$id_giocatore = $row['visite'] +1;
	
	// creo array contenente il valore dei piedi
	$ar_piedi = array("LR","R","L","R");
	
	//CARICO TABELLA TALENTI
	$talenti_result = mysql_query("SELECT * FROM talenti WHERE 1");
	if (!$talenti_result) 
	{
    	echo 'Errore nella query talenti: ' . mysql_error();
	    exit();
	}
	
	while   ($row   =   mysql_fetch_array($talenti_result))
	{
		$lista_talenti[] = $row['tal_descrizione'];
	}
	//CARICO TABELLA CARATTERI GIOCATORI
	$caratteri_result = mysql_query("SELECT * FROM caratteri WHERE id_carattere='giocatore'");
	if (!$caratteri_result) 
	{
    	echo 'Errore nella query caratteri: ' . mysql_error();
	    exit();
	}
	
	while   ($row   =   mysql_fetch_array($caratteri_result))
	{
		$lista_caratteri[] = $row['descrizione'];
	}
	
	
	//CREA I DUE PORTIERI
	for ($x=1; $x<=12; $x+=11)
	{
		$nr_maglia = $x;
		$id = $id_giocatore;
		$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
		
		$c1_skill = mt_rand(0,1);
		$c2_skill = mt_rand(4,8);
		$skill = $c1_skill.".".$c2_skill;
		
		$eta = mt_rand(17,23);
		$ruolo = "PO";
		$forma = mt_rand(60,100);
		$fresc = mt_rand(60,100);
		$cond = mt_rand(60,100);
		$esp = mt_rand(0,15);
		
		$po = mt_rand(10,(16*$skill));
		$df = mt_rand(4,12);
		$cn = mt_rand(4,12);
		$pa = mt_rand(2,6);
		$rg = mt_rand(2,6);
		$cr = mt_rand(2,6);
		$tc = mt_rand(2,10);
		$tr = mt_rand(2,8);
		
		$c1_piede = mt_rand(0,2);
		$c2_piede = mt_rand(0,1);
		$pd = $ar_piedi[$c1_piede+$c2_piede];
		
		$c1_talento = mt_rand(0,10);
		if ($c1_talento == 10)
		{
			$talento = $lista_talenti[mt_rand(12,14)];
			$qta = mt_rand(1,3);
		}
		else
		{
			$talento = $lista_talenti[0];
			$qta = 0;
		}
		$c1_caratteri = count($lista_caratteri)-1;
		$carattere = $lista_caratteri[mt_rand(0,$c1_caratteri)];
		
		$stipendio = 800 * (1+$skill);
		$valore = $stipendio * (1+5*$skill) * (2+$qta);
		
		// richiamo la funzione di inserimento dati
		inserisci($team,$id,$nr_maglia,$nome,$eta,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
		
		$id_giocatore ++;
	}
	//CREA I 5 DIFENSORI
	for ($x=2; $x<=6; $x++)
	{
		$nr_maglia = $x;
		$id = $id_giocatore;
		$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
		
		$c1_skill = mt_rand(0,1);
		$c2_skill = mt_rand(4,8);
		$skill = $c1_skill.".".$c2_skill;
		
		$eta = mt_rand(17,23);
		$ruolo = "D";
		$forma = mt_rand(60,100);
		$fresc = mt_rand(60,100);
		$cond = mt_rand(60,100);
		$esp = mt_rand(0,15);
		
		$po = mt_rand(1,4);
		$df = mt_rand(10,16*$skill);
		$cn = mt_rand(8,13*$skill);
		$pa = mt_rand(2,8);
		$rg = mt_rand(2,6);
		$cr = mt_rand(2,12);
		$tc = mt_rand(2,10);
		$tr = mt_rand(2,8);
		
		$c1_piede = mt_rand(0,2);
		$c2_piede = mt_rand(0,1);
		$pd = $ar_piedi[$c1_piede+$c2_piede];
		
		$c1_talento = mt_rand(0,10);
		if ($c1_talento == 10)
		{
			$talento = $lista_talenti[mt_rand(1,11)];
			$qta = mt_rand(1,3);
		}
		else
		{
			$talento = $lista_talenti[0];
			$qta = 0;
		}
		$c1_caratteri = count($lista_caratteri)-1;
		$carattere = $lista_caratteri[mt_rand(0,$c1_caratteri)];
		
		$stipendio = 800 * (1+$skill);
		$valore = $stipendio * (1+5*$skill) * (2+$qta);
		
		// richiamo la funzione di inserimento dati
		inserisci($team,$id,$nr_maglia,$nome,$eta,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
		
		$id_giocatore ++;
	}
	//CREA I 6 CENTROCAMPISTI
	for ($x=7; $x<=12; $x++)
	{
		
		if ($x == 12)
		{
			$nr_maglia = 13;
		}
		else
		{
			$nr_maglia = $x;
		}
				
		$id = $id_giocatore;
		$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
		
		$c1_skill = mt_rand(0,1);
		$c2_skill = mt_rand(4,8);
		$skill = $c1_skill.".".$c2_skill;
		
		$eta = mt_rand(17,23);
		$ruolo = "C";
		$forma = mt_rand(60,100);
		$fresc = mt_rand(60,100);
		$cond = mt_rand(60,100);
		$esp = mt_rand(0,15);
		
		$po = mt_rand(1,4);
		$df = mt_rand(5,10);
		$cn = mt_rand(6,10);
		$pa = mt_rand(10,14*$skill);
		$rg = mt_rand(10,16*$skill);
		$cr = mt_rand(10,13*$skill);
		$tc = mt_rand(2,12);
		$tr = mt_rand(2,12);
		
		$c1_piede = mt_rand(0,2);
		$c2_piede = mt_rand(0,1);
		$pd = $ar_piedi[$c1_piede+$c2_piede];
		
		$c1_talento = mt_rand(0,10);
		if ($c1_talento == 10)
		{
			$talento = $lista_talenti[mt_rand(1,11)];
			$qta = mt_rand(1,3);
		}
		else
		{
			$talento = $lista_talenti[0];
			$qta = 0;
		}
		$c1_caratteri = count($lista_caratteri)-1;
		$carattere = $lista_caratteri[mt_rand(0,$c1_caratteri)];
		
		$stipendio = 800 * (1+$skill);
		$valore = $stipendio * (1+5*$skill) * (2+$qta);
		
		// richiamo la funzione di inserimento dati
		inserisci($team,$id,$nr_maglia,$nome,$eta,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
		
		$id_giocatore ++;
	}
	//CREA I 5 ATTACCANTI
	for ($x=14; $x<=18; $x++)
	{
		$nr_maglia = $x;
		$id = $id_giocatore;
		$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
		
		$c1_skill = mt_rand(0,1);
		$c2_skill = mt_rand(4,8);
		$skill = $c1_skill.".".$c2_skill;
		
		$eta = mt_rand(17,23);
		$ruolo = "A";
		$forma = mt_rand(60,100);
		$fresc = mt_rand(60,100);
		$cond = mt_rand(60,100);
		$esp = mt_rand(0,15);
		
		$po = mt_rand(1,4);
		$df = mt_rand(4,10);
		$cn = mt_rand(5,10);
		$pa = mt_rand(6,10);
		$rg = mt_rand(7,10);
		$cr = mt_rand(4,12);
		$tc = mt_rand(10,16*$skill);
		$tr = mt_rand(10,16*$skill);
		
		$c1_piede = mt_rand(0,2);
		$c2_piede = mt_rand(0,1);
		$pd = $ar_piedi[$c1_piede+$c2_piede];
		
		$c1_talento = mt_rand(0,10);
		if ($c1_talento == 10)
		{
			$talento = $lista_talenti[mt_rand(1,11)];
			$qta = mt_rand(1,3);
		}
		else
		{
			$talento = $lista_talenti[0];
			$qta = 0;
		}
		$c1_caratteri = count($lista_caratteri)-1;
		$carattere = $lista_caratteri[mt_rand(0,$c1_caratteri)];
		
		$stipendio = 800 * (1+$skill);
		$valore = $stipendio * (1+5*$skill) * (2+$qta);
		
		// richiamo la funzione di inserimento dati
		inserisci($team,$id,$nr_maglia,$nome,$eta,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
		
		$id_giocatore ++;
	}
		
	// AGGIORNO IL CONTATORE GIOCATORI
	$result = mysql_query("UPDATE contatore SET visite = '$id_giocatore'-1 WHERE pagina = '90701'");
	
	// INSERISCO LA SQUADRA TRA QUELLE DELLE CPU
	$cols = mt_rand(0,15);
	$cold = mt_rand(0,15);
	
	$result = mysql_query("INSERT INTO z_iscritti (serie,squadra,cpu,logos,logod) 
									VALUES ('$serie', \"$team\",0,'$cols','$cold')");
	
	
	echo "</div>";
?>
</body>
</html>
