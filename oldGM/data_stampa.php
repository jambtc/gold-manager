<?php
	require_once('auth.php');
	include "connect_db.php";	

	//Array to store validation errors
	$errmsg_arr = array();
	
	//Validation error flag
	$errflag = false;
	
	//Function to sanitize values received from the form. Prevents SQL injection
	function clean($str) {
		$str = @trim($str);
		if(get_magic_quotes_gpc()) {
			$str = stripslashes($str);
		}
		return mysql_real_escape_string($str);
	}
	
	//Sanitize the POST values
	$titolo = clean($_POST['titolo']);
	$testo = clean($_POST['testo']);
	
	//Input Validations
	if($titolo == '') 
	{
		$errmsg_arr[] = 'Titolo non inserito';
		$errflag = true;
	}
	if($testo == '') 
	{
		$errmsg_arr[] = 'Testo non inserito';
		$errflag = true;
	}
	
	//If there are input validations, redirect back to the login form
	if($errflag)
	{
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: form_stampa.php");
		exit();
	}
	
	$titolo = strtoupper(str_replace("\"","&quot;",$_POST["titolo"]));
	$messaggio = str_replace("\"","&quot;",$_POST["testo"]);
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$nome_utente = $_SESSION['SESS_USER'];
	$serie = $_SESSION['SESS_SERIE'];
		
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
		
	$qry = "INSERT INTO notizie_serie (g_serie,g_team,g_data,g_titolo,g_testo) 
						VALUES (\"$serie\",\"$nome_team\",\"$datasql\",\"$titolo\",\"$messaggio\")";
		
		$result = @mysql_query($qry);
		if (!$result)
		{
				echo 'Errore nella query Salvataggio messaggio: ' . mysql_error();
				exit();
		}
		
		$admin_mail ="stampa@goldmanager.org";
		$subject ="Oggetto: $titolo";
		$message ="$messaggio";
		$headers ="From: $nome_utente\r\n";
			
		@mail($admin_mail, $subject, $message, $headers);



	header("location: form_stampa_ok.php");

?>