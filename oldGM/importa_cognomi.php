<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>


<?php 
		
	//Include database connection details
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
	
	
	if (isset($_FILES['nomefile']) && is_uploaded_file($_FILES['nomefile']['tmp_name'])){
		
   		if($_FILES['nomefile']['error']==0){
      		move_uploaded_file($_FILES['nomefile']['tmp_name'],"upload/".$_FILES['nomefile']['name']);
	   		//Sanitize the POST values
			$wfile = "upload/".clean($_FILES['nomefile']['name']);
		}
		
		switch ($_FILES['nomefile']['error']){
			case 1:
				$errmsg_arr[] = 'il file caricato supera le dimensioni definite dalla direttiva upload_max_filesize definita in php.ini';
				$errflag = true;
				break;
			case 2:
				$errmsg_arr[] = 'il file caricato supera le dimensioni definite dalla direttiva MAX_FILE_SIZE specificata nella form';
				$errflag = true;
				break;
			case 3:
				$errmsg_arr[] = 'il file non è stato caricato completamente';
				$errflag = true;
				break;
			case 4:
				$errmsg_arr[] = 'il file non è stato caricato';
				$errflag = true;
				break;
			case 6:
				$errmsg_arr[] = 'directory temporanea dove caricare i file mancante';
				$errflag = true;
				break;
			case 7:
				$errmsg_arr[] = 'errore di scrittura sul disco';
				$errflag = true;
				break;
			case 8:
				$errmsg_arr[] = 'il caricamento del file è stato bloccato a causa dell\'estensione file non accettata';
				$errflag = true;
				break;
		}
	
	}else{
		$errmsg_arr[] = 'File non caricato!!!';
		$errflag = true;
	}
	
	//Input Validations
	if($wfile == '') {
		$errmsg_arr[] = 'Nome file mancante';
		$errflag = true;
	}
	
	if(substr($wfile,-4,4) != '.csv') {
		$errmsg_arr[] = 'Il file non è di tipo .csv';
		$errflag = true;
	}
	
	//If there are input validations, redirect back to the registration form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: m-pannello.php");
		exit();
	}
	// seleziono i nomi propri
	$qry_nomi = mysql_query("SELECT * FROM nomi");
	while ($row = mysql_fetch_array($qry_nomi))
	{
		$vecchi_nomi[] = trim($row['nome']);
	}

	//seleziono i cognomi di persona
	$qry_cognomi = mysql_query("SELECT * FROM cognomi");
	while ($row = mysql_fetch_array($qry_cognomi))
	{
		$vecchi_cognomi[] = trim($row['cognome']);
	}
	
	$nome = "";
	$cmp = array( 'id','cognome');

	$row = 0;
	$handle = fopen($wfile,"r");
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE)
	{
		$import[] = $data;

	}
	fclose($handle);
	unlink($wfile); //cancello il file temporaneo
	
	$new_totale = count($import);

	for ($ii = 0; $ii < $new_totale; $ii++)
	{
		$nome = "";
		$inizio = 1;
		$nominativo = str_word_count($import[$ii][0],1);
		
		$cognome = $nominativo[0];
		if ($cognome == "DA" or $cognome == "DE" or $cognome == "DI" or
				$cognome == "DAL" or $cognome == "DEL" or
				$cognome == "DALLA" or $cognome == "DELLA" or 
				$cognome == "DELLI" or $cognome == "DELLE" or 
				$cognome == "LA" or $cognome == "LE" or
				$cognome == "LI" or $cognome == "LO")
		{
			$cognome = $nominativo[0]." ".$nominativo[1];
			$inizio = 2;
		}
				
		for ($yy = $inizio; $yy < count($nominativo); $yy++)
		{
			$nome = $nome.$nominativo[$yy]." ";
		}
		$cognome = ucwords(strtolower(trim($cognome)));
		$nome = ucwords(strtolower(trim($nome)));
		
		$qry_cognome = "INSERT IGNORE INTO cognomi (cognome) VALUES (\"$cognome\")";
		$qry_nome = "INSERT IGNORE INTO nomi (nome) VALUES (\"$nome\")";
		
		if (!in_array($cognome,$vecchi_cognomi))
		{
			$vecchi_cognomi[] = $cognome;
			$res_cognome = @mysql_query($qry_cognome);
			if (!$res_cognome)
			{
				echo 'Errore nella query COGNOME: ' . mysql_error();
				exit();
			}
		}
		if(substr($nome,-1,1) != "a")
		{
			if (!in_array($nome,$vecchi_nomi))
			{	
				$vecchi_nomi[] = $nome;
				$res_nome = @mysql_query($qry_nome);
				if (!$res_nome)
				{
					echo 'Errore nella query NOME: ' . mysql_error();
					exit();
				}
			}
		}
	}	
	header("location: m-pannello.php");
?>