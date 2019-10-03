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
	$logosx = $_POST['logosx'];
	$logodx = $_POST['logodx'];
	
	$data_di_nascita = $anno_utente."-".$mese_utente."-".$giorno_utente;
	
	$larghezza = $_POST['largh'];
	$altezza = $_POST['altez'];
		
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
		header("location: form_register.php?larghezza=$larghezza&altezza=$altezza");
		exit();
	}
	$Oggi = time();
	$g1 = date("j",$Oggi);
	$m1 = date("m",$Oggi);
	$a1 = date("Y",$Oggi);
	$data = mktime(0, 0, 0, $m1, $g1, $a1);
	$dataIta = $g1."/".$m1."/".$a1;
	
	$separatore = "/";
	// data in formato sql  
	$split_data = explode($separatore, $dataIta);
	$datasql = $split_data[2] . "-" . $split_data[1] . "-" . $split_data[0];
	
	
	//CARICO LA CONFIGURAZIONE
	$qry = "SELECT * FROM  zz_config WHERE id=1";
	
	$result = mysql_query($qry);
	if (!$result)
	{
   		echo 'Errore nella query: ' . mysql_error();
   		exit();
	}
	$row   =   mysql_fetch_array($result);
		
	$data = $row['data'];
	$giorno = $row['giorno'];
	$orario = $row['orario'];
	$squadre= $row['squadre'];
	
	// LA DIVISIONE QUANDO CI SI ISCRIVE E' LA 3.
	// CI SONO x DIVISIONI DI n SQUADRE - LA ASSEGNO IN ORDINE DA 1 A x, 
	// VERIFICO SE SONO PIENE (MAX = n)
	//$qry_1 = mysql_query("SELECT * FROM z_iscritti WHERE serie='3.1'");
		
	$quale_serie = 1;
	$condizione = true;
	
	while ($condizione)
	{
		$qry = "SELECT * FROM z_iscritti WHERE serie='3.".$quale_serie."'";
		
		$result = mysql_query($qry);
		if (mysql_num_rows($result) == $squadre)
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
	
	$qry = "INSERT INTO members (team, login, passwd, 
								 email, serie, logos, logod, 
								 data_iscr, budget,
								 nome, cognome, nascita, provincia ) 
						VALUES (\"$team\",\"$login\",\"".md5($_POST['password'])."\",
								\"$email\",'$serie','$logosx','$logodx',
								\"$datasql\",'250000',
								\"$nome_utente\",\"$cognome_utente\",
								'$data_di_nascita',\"$provincia_utente\")";
	$result = @mysql_query($qry);
	
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
		
		$eta = mt_rand(17,23);
		$ruolo = "PO";
		$forma = mt_rand(60,100);
		$fresc = mt_rand(60,100);
		$cond = mt_rand(60,100);
		$esp = mt_rand(0,15);
		
		$po = mt_rand(10,(20 + mt_rand(0,10)));
		$df = mt_rand(4,12);
		$cn = mt_rand(4,12);
		$pa = mt_rand(2,6);
		$rg = mt_rand(2,6);
		$cr = mt_rand(2,6);
		$tc = mt_rand(2,10);
		$tr = mt_rand(2,8);
		
		$skill = ($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/32;
		
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
		
		$stipendio = mt_rand(500,800) * (1+$skill);
		$valore = 500 * $eta * $skill;
		$valore = $valore * (1 + $esp);
		$valore = $valore * (1 + $qta);

		
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
		
		
		$eta = mt_rand(17,23);
		$ruolo = "D";
		$forma = mt_rand(60,100);
		$fresc = mt_rand(60,100);
		$cond = mt_rand(60,100);
		$esp = mt_rand(0,15);
		
		$po = mt_rand(1,4);
		$df = mt_rand(10,(20 + mt_rand(0,10)));
		$cn = mt_rand(8,(20 + mt_rand(0,10)));
		$pa = mt_rand(2,8);
		$rg = mt_rand(2,6);
		$cr = mt_rand(2,12);
		$tc = mt_rand(2,10);
		$tr = mt_rand(2,8);

		$skill = ($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/32;

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
		
		$stipendio = mt_rand(500,800) * (1+$skill);
		$valore = 500 * $eta * $skill;
		$valore = $valore * (1 + $esp);
		$valore = $valore * (1 + $qta);
		
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
		
		$eta = mt_rand(17,23);
		$ruolo = "C";
		$forma = mt_rand(60,100);
		$fresc = mt_rand(60,100);
		$cond = mt_rand(60,100);
		$esp = mt_rand(0,15);
		
		$po = mt_rand(1,4);
		$df = mt_rand(5,10);
		$cn = mt_rand(6,10);
		$pa = mt_rand(10,(15 + mt_rand(0,10)));
		$rg = mt_rand(10,(20 + mt_rand(0,10)));
		$cr = mt_rand(10,(10 + mt_rand(0,10)));
		$tc = mt_rand(2,12);
		$tr = mt_rand(2,12);
		
		$skill = ($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/32;

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
		
		$stipendio = mt_rand(500,800) * (1+$skill);
		$valore = 500 * $eta * $skill;
		$valore = $valore * (1 + $esp);
		$valore = $valore * (1 + $qta);
		
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
		$tc = mt_rand(10,(20 + mt_rand(0,10)));
		$tr = mt_rand(10,(20 + mt_rand(0,10)));
		
		$skill = ($po+$df+$cn+$pa+$rg+$cr+$tc+$tr)/32;

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
		
		$stipendio = mt_rand(500,800) * (1+$skill);
		$valore = 500 * $eta * $skill;
		$valore = $valore * (1 + $esp);
		$valore = $valore * (1 + $qta);
		
		// richiamo la funzione di inserimento dati
		inserisci($team,$id,$nr_maglia,$nome,$eta,$skill,$ruolo,$forma,$fresc,$cond,$esp,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$pd,$talento,$qta,$carattere,$stipendio,$valore);
		
		$id_giocatore ++;
	}
		
	// AGGIORNO IL CONTATORE GIOCATORI
	$result = mysql_query("UPDATE contatore SET visite = '$id_giocatore'-1 WHERE pagina = '90701'");
	
	// INSERISCO LA SQUADRA TRA QUELLE DEGLI UTENTI E NON DELLA CPU
	$result = mysql_query("INSERT INTO z_iscritti (serie,squadra,cpu) VALUES ('$serie', \"$team\",1)");
	
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
				
		header("location: register-success.php?larghezza=$larghezza&altezza=$altezza");
		exit();
	}else {
		die("Query failed");
	}
?>
