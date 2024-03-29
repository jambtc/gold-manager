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
	$login = clean($_POST['login']);
	$password = clean($_POST['password']);
	
	$larghezza = $_POST['larghezza'];
	$altezza = $_POST['altezza'];

	//Input Validations
	if($login == '') {
		$errmsg_arr[] = 'ID Utente mancante';
		$errflag = true;
	}
	if($password == '') {
		$errmsg_arr[] = 'Password mancante';
		$errflag = true;
	}
	
	//If there are input validations, redirect back to the login form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: index.php");
		exit();
	}
	else
	{
		//Create query
		$qry="SELECT * FROM members WHERE login=\"$login\" AND passwd=\"".md5($_POST['password'])."\"";
		$result=mysql_query($qry);
		
		//Check whether the query was successful or not
		if($result) {
			if(mysql_num_rows($result) == 1)
			{
				//Login Successful
				session_regenerate_id();
				$member = mysql_fetch_assoc($result);
				$_SESSION['SESS_TEAM'] = $member['team'];
				$_SESSION['SESS_USER'] = $member['login'];
				$_SESSION['SESS_SERIE'] = $member['serie'];
				$_SESSION['SESS_SX'] = $member['logos'];
				$_SESSION['SESS_DX'] = $member['logod'];
				$_SESSION['SESS_PRIVILEGI'] = $member['privilegi'];
								
				define("ID_TEAM",$member['team']);
							
				session_write_close();
				if ($member['team'] == "administrator" and $member['login'] == "administrator" 
														and $member['privilegi'] == 1)
				{
					header("location: m-pannello.php");
					exit();
				}
			
				
				header("location: m-risoluzione.php");
				exit();
			}
			else
			{
				//Login failed
				header("location: login-failed-page.php?larghezza=$larghezza&altezza=$altezza");
				exit();
			}
		}else {
			die("Query failed");
		}
	}
?>