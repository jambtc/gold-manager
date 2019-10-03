<?php
	//Start session
	session_start();
	
	//Include database connection details
	require_once('config.php');
	
	//Array to store validation errors
	$errmsg_arr = array();
	
	//Validation error flag
	$errflag = false;
	
	//Connect to mysql server
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(!$link) {
		die('Failed to connect to server: ' . mysql_error());
	}
	
	//Select database
	$db = mysql_select_db(DB_DATABASE);
	if(!$db) {
		die("Unable to select database");
	}
	
	//Function to sanitize values received from the form. Prevents SQL injection
	function clean($str) {
		$str = @trim($str);
		if(get_magic_quotes_gpc()) {
			$str = stripslashes($str);
		}
		return mysql_real_escape_string($str);
	}
		
	//Sanitize the POST values
	
	$nome_utente = clean($_POST["nome"]);
	$cognome_utente = clean($_POST["cognome"]);
	$giorno_utente = clean($_POST["giorno"]);
	$mese_utente = clean($_POST["mese"]);
	$anno_utente = clean($_POST["anno"]);
	$provincia_utente = clean($_POST["provincia"]);
	
	$login = clean($_POST["login"]);
	$team = clean($_POST["team"]);
	$email = clean($_POST["email"]);
	$password = clean($_POST["password"]);
	$cpassword = clean($_POST["cpassword"]);
	
	$scudo_top = $_POST['scudo_top'];
	$scudo_middle = $_POST['scudo_middle'];
	$scudo_bottom_left = $_POST['scudo_bottom_left'];
	$scudo_bottom_center = $_POST['scudo_bottom_center'];
	$scudo_bottom_right = $_POST['scudo_bottom_right'];
	
	$data_di_nascita = $anno_utente."-".$mese_utente."-".$giorno_utente;
	
	//Input Validations
	if($nome_utente == '') {
		$errmsg_arr[] = 'Nome mancante';
		$errflag = true;
	}
	if($cognome_utente == '') {
		$errmsg_arr[] = 'Cognome mancante';
		$errflag = true;
	}
	if($data_di_nascita == '') {
		$errmsg_arr[] = 'Data di nascita mancante';
		$errflag = true;
	}
	if(!checkdate($mese_utente,$giorno_utente,$anno_utente)) {
		$errmsg_arr[] = 'Data di nascita errata';
		$errflag = true;
	}
	if($provincia_utente == '') {
		$errmsg_arr[] = 'Provincia mancante';
		$errflag = true;
	}
	if($login == '') {
		$errmsg_arr[] = 'ID Utente mancante';
		$errflag = true;
	}
	if($team == '') {
		$errmsg_arr[] = 'Squadra mancante';
		$errflag = true;
	}
	if($email == '') {
		$errmsg_arr[] = 'eMail mancante';
		$errflag = true;
	}
	if($password == '') {
		$errmsg_arr[] = 'Password mancante';
		$errflag = true;
	}
	if($cpassword == '') {
		$errmsg_arr[] = 'Password di conferma mancante';
		$errflag = true;
	}
	if( strcmp($password, $cpassword) != 0 ) {
		$errmsg_arr[] = 'Le Password non coincidono';
		$errflag = true;
	}
	
	//Check for duplicate login ID
	if($login != '') {
		$qry = "SELECT * FROM members WHERE login=\"$login\"";
		$result = mysql_query($qry);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$errmsg_arr[] = 'ID utente già registrato';
				$errflag = true;
			}
			@mysql_free_result($result);
		}
		else {
			die("Query failed");
		}
	}
	//Check for duplicate team
	if($login != '') {
		$qry = "SELECT * FROM members WHERE team=\"$team\"";
		$result = mysql_query($qry);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$errmsg_arr[] = 'Nome squadra già utilizzato';
				$errflag = true;
			}
			@mysql_free_result($result);
		}
		else {
			die("Query failed");
		}
	}
	
	//If there are input validations, redirect back to the registration form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: index.php?fnz=register");
		exit();
	}
	
	
	// DATA ODIERNA DI REGISTRAZIONE
	$rOggi = time();
	$rg1 = date("j",$rOggi);
	$rm1 = date("m",$rOggi);
	$ra1 = date("Y",$rOggi);
	$rora = date("H",$rOggi); // ora di iscrizione
	$rmin = date("i",$rOggi); // minuti di iscrizione

	$rdatasql = $ra1 . "-" . $rm1 . "-" . $rg1;
	
	//CARICO LA CONFIGURAZIONE
	$qry = "SELECT * FROM  zz_config WHERE id=1";
	$result = mysql_query($qry);
	if (!$result)
	{
   		echo 'Errore nel caricamento della configurazione: ' . mysql_error();
   		exit();
	}
	$row   =   mysql_fetch_array($result);
	$zz_data = $row['data'];
	$zz_giorno = $row['giorno'];
	$zz_orario = $row['orario'];
	$zz_squadre= $row['squadre'];
	$zz_ultima_serie= $row['ultima_serie'];
	
	//CARICO LE SQUADRE ISCRITTE ALLE VARIE SERIE
	$qry = "SELECT * FROM z_iscritti ORDER BY serie";
	$result = mysql_query($qry);
	$z_quale_serie = array();
	$z_team_in_serie = array();
	while ($row = mysql_fetch_array($result))
	{
		$z_squadre[]= $row['squadra'];
		$z_serie[] = $row['serie'];
		$z_cpu[] = $row['cpu'];
		if (!in_array($row['serie'],$z_quale_serie))
		{
			$z_quale_serie[] = $row['serie'];
			$z_team_in_serie[$row['serie']] = 1;
		}
		else
		{
			$z_team_in_serie[$row['serie']] ++;
		}
	}

	// comincio la ricerca di squadre cpu all'iterno di tutte le serie
	// se la trovo esco dal ciclo ed assegno la nuova squadra
	// ALTRIMENTI DEVO CREARE UNA NUOVA SERIE ED ASSEGNARE LA PRIMA SQUADRA A QUELLA NUOVA SERIE
	
	$esco = false;
	
	$x = 0;
	$stop = count($z_squadre);
	
	//ricerca la prima squadra libera tra tutte le serie 
	while ($x < $stop)
	{
		// confronta la squadra libera...
		if ($z_cpu[$x] == 0)
		{
			$quale = $z_serie[$x];
			$msg = $z_squadre[$x];
			$esco = true;
			break;
		}
		$x++;
	}
	
	// QUINDI, SE HO TROVATO UNA SQUADRA LIBERA...
	if ($esco == true)
	{
		// INSERISCO LA SQUADRA TRA QUELLE DEGLI UTENTI E NON DELLA CPU
		$qry = "UPDATE z_iscritti 	SET 	cpu = 1,
											squadra = \"$team\"
									WHERE 	serie=\"$quale\" AND squadra = \"$msg\"";
									
		$result = @mysql_query($qry);
		if (!$result)
		{
   			echo "Errore durante il salvataggio della squadra: ". mysql_error();
	   		exit();
		}
		
		// AGGIORNO IL CALENDARIO DELLE PARTITE CON LA NUOVA SQUADRA UTENTE
		$qrya = "UPDATE z_calendario	SET 	casa = \"$team\"
									WHERE 	serie=\"$quale\" AND casa = \"$msg\"";
									
		$qryb = "UPDATE z_calendario	SET 	fuori = \"$team\"
									WHERE 	serie=\"$quale\" AND fuori = \"$msg\"";								
									
		$resulta = @mysql_query($qrya);
		$resultb = @mysql_query($qryb);
		if (!$resulta or !$resultb)
		{
   			echo "Errore durante il salvataggio del Calendario: ". mysql_error();
	   		exit();
		}
		
		// AGGIORNO LA CLASSIFICA CON LA NUOVA SQUADRA UTENTE
		$qry = "UPDATE z_classifica	SET 	team = \"$team\"
									WHERE 	serie=\"$quale\" AND team = \"$msg\"";
									
		$result = @mysql_query($qry);
		if (!$result )
		{
   			echo "Errore durante il salvataggio della Classifica: ". mysql_error();
	   		exit();
		}
	
		// AGGIORNO I GIOCATORI CON IL NOME DELLA NUOVA SQUADRA CHE SARANNO ASSEGNATI PERTANTO AL NUOVO MEMBRO ISCRITTO
		$qry = "UPDATE giocatori SET id_team = \"$team\" WHERE id_team = \"$msg\"";
		
		$result = @mysql_query($qry);
		if (!$result)
		{
   			echo "Errore durante il salvataggio dei giocatori: ". mysql_error();
	   		exit();
		}
		
		
		//INSERISCO  IL NOME UTENTE NEI MEMBRI 
		$qry = "INSERT INTO members (team, login, passwd, 
									 email, serie, 
									 logo_top, logo_middle,logo_bottom_left,logo_bottom_center,logo_bottom_right, 
									 data_iscr, budget,
									 nome, cognome, nascita, provincia, avatar ) 
							VALUES (\"$team\",\"$login\",\"".md5($_POST['password'])."\",
									\"$email\",'$quale',
									'$scudo_top','$scudo_middle',
									'$scudo_bottom_left','$scudo_bottom_center','$scudo_bottom_right',
									\"$rdatasql\",'250000',
									\"$nome_utente\",\"$cognome_utente\",
									'$data_di_nascita',\"$provincia_utente\",'0')";
	
		$result = @mysql_query($qry);
		//Check whether the query was successful or not
		if($result)
		{
			$admin_mail ="no-reply@goldmanager.org";
			$subject ="Nuova Iscrizione";
			$message ="Nome utente: $login \r\nNome team: $team";
			$headers ="From:admin\r\n";
			
			@mail($admin_mail, $subject, $message, $headers);
			
			$subject ="BENVENUTO su Gold Manager!";
			$message ="Ciao $login, e benvenuto! \r\nPer qualsiasi informazione o richiesta di aiuto puoi rivolgerti a questo indirizzo: supporto@goldmanager.org\r\n \r\nSaluti \r\nSergio Casizzone";
			$headers ="From:Supporto\r\n";
			@mail($email, $subject, $message, $headers);
					
			header("location: index.php?fnz=registerok");
			exit();
		}
		else
		{
			echo "Errore durante il salvataggio dei dati utente: ". mysql_error();
			die("Query failed");
			exit;
		}
	}
	else
	{
		// SE SI GIUNGE QUI...
		// SONO PIENE TUTTE LE SERIE, PERTANTO BISOGNA CREARE UNA NUOVA SERIE DAL MINIMO
		// E AGGIUNGERE 9 SQUADRE + 1, QUELLA DEL NUOVO UTENTE ISCRITTO
		
		
		//Funzione di inserimento dati nella tabella giocatori
		function inserisci($team,$id,$nr_maglia,$nome,$eta,$nascita,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore)
		{
			$qry = "INSERT INTO giocatori (id,id_team,nr,nome,eta,nascita,skill,pos,forma,fresc,cond,esp,
						po,df,cn,pa,rg,cr,tc,tr,
						piede,talento,qta,
						stipendio,valore,carattere,contratto) 			
					VALUES 
					('$id',\"$team\",\"$nr_maglia\",\"$nome\",\"$eta\",\"$nascita\",\"$skill\",
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
			//echo $team." - (".$nr_maglia.") ".$nome." ".$skill." ".$eta." ".$nascita." ".$ruolo." ".$forma." ".$fresc." ".$cond." ".$esp." ".$po." ".$df." ".$cn." ".$pa." ".$rg." ".$cr." ".$tc." ".$tr." ".$pd." ".$talento." ".$carattere." ".$stipendio." ".$valore."<br>";
		}
		// FINE FUNZIONE
		
		
		// LA DIVISIONE QUANDO CI SI ISCRIVE E' LA 3.
		// la serie da creare è data dall'ultima serie + 1
		$zz_ultima_serie ++;
		$serie = "3.".$zz_ultima_serie;
		
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
		
		// inserisco i giorni massimi in un mese
		$nascita_mese_max = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
				
		//*********************************************************************************
		//*********************************************************************************
		//*** COMINCIO IL CICLO DELLE 9 SQUADRE + 1 ***************************************
		//*********************************************************************************
		//*********************************************************************************
		$lista_team[] = $team; // il primo della lista è sempre il team da inserire
		$umano = 1; // inizializzo l'essere umano a 1. appena inserito lo porto a zero e tutte le altre squadre 
					// saranno quindi cpu = 0
		
		//inizio un ciclo per creare l'array delle squadre da creare, in base al numero di squadre inserito
		//nella configurazione (quallo che indica quante squadre ci sono in una serie)
		for ($conta=2; $conta <= $zz_squadre; $conta++)
		{
			$lista_team[] = "CPU_".$serie."_".$conta;
		}
		
		foreach ($lista_team as $ins_squadra)
		{
			//CREA I DUE PORTIERI
			for ($x=1; $x<=12; $x+=11)
			{
				$nr_maglia = $x;
				$id = $id_giocatore;
				$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
				
				//$c1_skill = mt_rand(0,1);
				//$c2_skill = mt_rand(4,8);
				//$skill = $c1_skill.".".$c2_skill;
				
				$eta = mt_rand(17,23);
				
				$compleanno_mese = mt_rand(1,12);
				$compleanno_giorno = mt_rand(1,$nascita_mese_max[$compleanno_mese]);
				$compleanno = "2000-".$compleanno_mese."-".$compleanno_giorno;
				
				$ruolo = "PO";
				$forma = mt_rand(60,100);
				$fresc = mt_rand(60,100);
				$cond = mt_rand(60,100);
				$esp = mt_rand(0,15);
				
				$po = mt_rand(8,15);
				$df = mt_rand(6,10);
				$cn = mt_rand(4,8);
				$pa = mt_rand(2,5);
				$rg = mt_rand(1,5);
				$cr = mt_rand(1,4);
				$tc = mt_rand(1,4);
				$tr = mt_rand(1,4);
				
				$skill = round((($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/38+$esp/32),1);
				
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
				inserisci($ins_squadra,$id,$nr_maglia,$nome,$eta,$compleanno,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
				
				$id_giocatore ++;
			}
			//CREA I 5 DIFENSORI
			for ($x=2; $x<=6; $x++)
			{
				$nr_maglia = $x;
				$id = $id_giocatore;
				$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
				
				//$c1_skill = mt_rand(0,1);
				//$c2_skill = mt_rand(4,8);
				//$skill = $c1_skill.".".$c2_skill;
				
				$eta = mt_rand(17,23);
				
				$compleanno_mese = mt_rand(1,12);
				$compleanno_giorno = mt_rand(1,$nascita_mese_max[$compleanno_mese]);
				$compleanno = "2000-".$compleanno_mese."-".$compleanno_giorno;
				
				$ruolo = "D";
				$forma = mt_rand(60,100);
				$fresc = mt_rand(60,100);
				$cond = mt_rand(60,100);
				$esp = mt_rand(0,15);
				
				$po = mt_rand(1,2);
				$df = mt_rand(10,15);
				$cn = mt_rand(7,14);
				$pa = mt_rand(2,4);
				$rg = mt_rand(1,3);
				$cr = mt_rand(1,9);
				$tc = mt_rand(1,4);
				$tr = mt_rand(1,4);
				
				$skill = round((($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/38+$esp/32),1);
				
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
				inserisci($ins_squadra,$id,$nr_maglia,$nome,$eta,$compleanno,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
				
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
				
				$eta = mt_rand(17,23);
				
				$compleanno_mese = mt_rand(1,12);
				$compleanno_giorno = mt_rand(1,$nascita_mese_max[$compleanno_mese]);
				$compleanno = "2000-".$compleanno_mese."-".$compleanno_giorno;
				
				$ruolo = "C";
				$forma = mt_rand(60,100);
				$fresc = mt_rand(60,100);
				$cond = mt_rand(60,100);
				$esp = mt_rand(0,15);
				
				$po = mt_rand(0,1);
				$df = mt_rand(1,2);
				$cn = mt_rand(1,3);
				$pa = mt_rand(7,13);
				$rg = mt_rand(7,14);
				$cr = mt_rand(6,10);
				$tc = mt_rand(1,6);
				$tr = mt_rand(1,6);
				
				$skill = round((($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/38+$esp/32),1);
				
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
				inserisci($ins_squadra,$id,$nr_maglia,$nome,$eta,$compleanno,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
				
				$id_giocatore ++;
			}
			//CREA I 5 ATTACCANTI
			for ($x=14; $x<=18; $x++)
			{
				$nr_maglia = $x;
				$id = $id_giocatore;
				$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
				
				$eta = mt_rand(17,23);
				
				$compleanno_mese = mt_rand(1,12);
				$compleanno_giorno = mt_rand(1,$nascita_mese_max[$compleanno_mese]);
				$compleanno = "2000-".$compleanno_mese."-".$compleanno_giorno;
				
				$ruolo = "A";
				$forma = mt_rand(60,100);
				$fresc = mt_rand(60,100);
				$cond = mt_rand(60,100);
				$esp = mt_rand(0,15);
				
				$po = mt_rand(0,1);
				$df = mt_rand(1,2);
				$cn = mt_rand(1,3);
				$pa = mt_rand(1,6);
				$rg = mt_rand(1,6);
				$cr = mt_rand(6,14);
				$tc = mt_rand(7,13);
				$tr = mt_rand(7,10);
				
				$skill = round((($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/38+$esp/32),1);
				
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
				inserisci($ins_squadra,$id,$nr_maglia,$nome,$eta,$compleanno,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
				
				$id_giocatore ++;
			}
			// INSERISCO LA SQUADRA 
			$col_top = mt_rand(0,17); // colori random degli stemmi
			$col_middle = mt_rand(0,17);
			$col_bottom_l = mt_rand(0,17);
			$col_bottom_c = mt_rand(0,17);
			$col_bottom_r = mt_rand(0,17);
			
			$result = mysql_query("INSERT INTO z_iscritti (serie,squadra,cpu,
											logo_top, logo_middle,logo_bottom_left,logo_bottom_center,logo_bottom_right, ) 
											VALUES ('$serie', \"$ins_squadra\",'$umano',
											'$col_top','$col_middle',
											'$col_bottom_l','$col_bottom_c','$col_bottom_r' )");
			// azzero il valore di umano dopo il primo inserimento.
			if ($umano == 1)	{	$umano = 0; }
		}// fine ciclo for per squadte in $lista_team
			
		// AGGIORNO IL CONTATORE GIOCATORI
		$result = mysql_query("UPDATE contatore SET visite = '$id_giocatore'-1 WHERE pagina = '90701'");
		
		//aggiorno la CONFIGURAZIONE della serie
		$qry = "UPDATE zz_config SET ultima_serie = ultima_serie +1 WHERE id=1";
		$result = mysql_query($qry);
		if (!$result)
		{
			echo "Errore nell'aggiornamento della configurazione: " . mysql_error();
			exit();
		}
	
		//********************************
		// creo il calendario e la classifica a 0 punti
		// CREO IL CALENDARIO  DELLa ultima serie creata
		$x = 0;
		$y = 1;
		
		unset($squadre);
		unset($casa);
		unset($trasferta);
		unset($ritorno);
						
		// RILEGGO IL DATABASE PERCHE ADESSO E' STATA INSERITA UN NUOVO ELENCO DI SQUADRE
		// CON LA NUOVA SERIE
		$qry = "SELECT * FROM z_iscritti WHERE serie='$serie'";
		
		$result = mysql_query($qry);
		while ($row = mysql_fetch_array($result))
		{
			$squadre[]= $row['squadra'];
			//CREO CLASSIFICA CON DATI A ZERO
			$qry_cl = "INSERT INTO z_classifica (serie,team) VALUES (\"$serie\",\"$row[squadra]\")";
			@mysql_query($qry_cl);
		}
		// mischio le squadre...
		shuffle($squadre);
					
		$oraInizio = $zz_orario; // ora inizio delle partite
		$AvvioCampionato = $zz_data; // il campionato inizia il ...>
		$dayGioco = $zz_giorno; // si gioca il ....
		$secondi_al_giorno = 86400; //ci sono 86400 secondi in un giorno
		
		$data_inizio = strtotime($AvvioCampionato);
							
		$g1 = date("j",$data_inizio);
		$m1 = date("m",$data_inizio);
		$a1 = date("Y",$data_inizio);
		$dataIta = $g1."/".$m1."/".$a1;
		$dataSql = $a1."-".$m1."-".$g1;
					
		$numero_squadre = count($squadre);
		$giornate = $numero_squadre - 1;
						
		/* crea gli array per le due liste in casa e fuori */
		for ($i = 0; $i < $numero_squadre /2; $i++)
		{
			$casa[$i] = $squadre[$i]; 
			$trasferta[$i] = $squadre[$numero_squadre - 1 - $i]; 
		}
						
		for ($i = 0; $i < $giornate; $i++)
		{
			echo "<br>Giornata ",$i+1," del ".$dataIta."<br>";
			/* alterna le partite in casa e fuori */
			if ($i % 2 == 0) 
			{
				for ($j = 0; $j < $numero_squadre /2 ; $j++)
				{
					echo $j+1," ".$trasferta[$j]." - ".$casa[$j]."<br>"; 
					$qry = "INSERT INTO z_calendario (serie,data,ora_inizio,fuori,casa) VALUES (\"$serie\",'$dataSql','$oraInizio',\"$casa[$j]\",\"$trasferta[$j]\")";
					@mysql_query($qry);
					//Inserisco in array in modo da poter sviluppare il RITORNO
					$ritorno[] = $serie.";".$oraInizio.";".$casa[$j].";".$trasferta[$j];
				}
			}
			else 
			{
				for ($j = 0; $j < $numero_squadre /2 ; $j++) 
				{
					 echo $j+1," ".$casa[$j]." - ".$trasferta[$j]."<br>"; 
					 $qry = "INSERT INTO z_calendario (serie,data,ora_inizio,fuori,casa) VALUES (\"$serie\",'$dataSql','$oraInizio',\"$trasferta[$j]\",\"$casa[$j]\")";
					 @mysql_query($qry);
					 //Inserisco in array in modo da poter sviluppare il RITORNO
					$ritorno[] = $serie.";".$oraInizio.";".$trasferta[$j].";".$casa[$j];
				}
			}
			// Ruota gli elementi delle liste, tenendo fisso il primo elemento
			// Salva l'elemento fisso
			$pivot = $casa[0];
					 
			/* sposta in avanti gli elementi di "trasferta" inserendo 
			all'inizio l'elemento casa[1] e salva l'elemento uscente in "riporto" */
			array_unshift($trasferta,$casa[1]);
			$riporto = array_pop($trasferta);
					 
			/* sposta a sinistra gli elementi di "casa" inserendo all'ultimo 
			   posto l'elemento "riporto" */
			$casa[] = $riporto;
			array_shift($casa);
						  
			// ripristina l'elemento fisso
			$casa[0] = $pivot ;
							
			$nextdate  = mktime (0,0,0,$m1,  $g1+7,  $a1); 
							
			$data_inizio = $nextdate;
							
			$g1 = date("j",$data_inizio);
			$m1 = date("m",$data_inizio);
			$a1 = date("Y",$data_inizio);
							
			$dataIta = $g1."/".$m1."/".$a1;
			$dataSql = $a1."-".$m1."-".$g1;
		} 
		//CREO LE PARTITE DI RITORNO
		for ($y=0; $y<count($ritorno); $y++)
		{
			echo "<br>Giornata ",$i+1," del ".$dataIta."<br>";
							
			for ($j = 0; $j < $numero_squadre /2 ; $j++) 
			{
				$riga = $ritorno[$y+$j];
				$elementi = explode(";",$riga);
						
				$serie = $elementi[0];
				$data = $dataSql;
				$oraInizio = $elementi[1];
				$squadra1 = $elementi[2];
				$squadra2 = $elementi[3];
								
				echo $j+1," ".$squadra1." - ".$squadra2."<br>"; 
				$qry = "INSERT INTO z_calendario (serie,data,ora_inizio,casa,fuori) VALUES (\"$serie\",'$dataSql','$oraInizio',\"$squadra1\",\"$squadra2\")";
				@mysql_query($qry);	
			}
			$y = $y+$j-1;
			$nextdate  = mktime (0,0,0,$m1,  $g1+7,  $a1); 
			$data_inizio = $nextdate;
							
			$g1 = date("j",$data_inizio);
			$m1 = date("m",$data_inizio);
			$a1 = date("Y",$data_inizio);
							
			$dataIta = $g1."/".$m1."/".$a1;
			$dataSql = $a1."-".$m1."-".$g1;
							
			$i++;
		}
		
		//********************************
		// verifico a che punto della stagione mi trovo e devo giocare le partite
		// già superate...ed aggiornare la classifica....
		if ($rdatasql > $zz_data)
		{
			
		}
		else
		{
			// se la data è uguale 
			if ($rdatasql == $zz_data)
			{
				// verifico l'orario d iiscrizione
				if ($rora > zz_orario)
				{
					echo "Impossibile effettuare l'iscrizione adesso.";
					exit;
				}
				
			}
		}
		//INSERISCO FINALMENTE IL NOME UTENTE NEI MEMBRI 
		$qry = "INSERT INTO members (team, login, passwd, 
										 email, serie,
										  logo_top, logo_middle,logo_bottom_left,logo_bottom_center,logo_bottom_right, 
										 data_iscr, budget,
										 nome, cognome, nascita, provincia, avatar ) 
								VALUES (\"$team\",\"$login\",\"".md5($_POST['password'])."\",
										\"$email\",'$serie',
										'$scudo_top','$scudo_middle',
										'$scudo_bottom_left','$scudo_bottom_center','$scudo_bottom_right',
										\"$rdatasql\",'250000',
										\"$nome_utente\",\"$cognome_utente\",
										'$data_di_nascita',\"$provincia_utente\",'0')";
		
		$result = @mysql_query($qry);
		//Check whether the query was successful or not
		if($result)
		{
			$admin_mail ="no-reply@goldmanager.org";
			$subject ="Nuova Iscrizione";
			$message ="Nome utente: $login \r\nNome team: $team";
			$headers ="From:admin\r\n";
			
			@mail($admin_mail, $subject, $message, $headers);
				
			$subject ="BENVENUTO su Gold Manager!";
			$message ="Ciao $login, e benvenuto! \r\nPer qualsiasi informazione o richiesta di aiuto puoi rivolgerti a questo indirizzo: supporto@goldmanager.org\r\n \r\nSaluti \r\nSergio Casizzone";
			$headers ="From:Supporto\r\n";
			@mail($email, $subject, $message, $headers);
			header("location: index.php?fnz=registerok");
			exit();
		}
		else
		{
			echo "Errore durante il salvataggio dei dati utente: ". mysql_error();
			die("Query failed");
			exit;
		}
	}
?>
