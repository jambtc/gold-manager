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
		//header("location: index.php");
		header("location: index.php?fnz=loginfailed");
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
				$_SESSION['SESS_PRIVILEGI'] = $member['privilegi'];
				
				$_SESSION['SESS_LOGO_TOP'] = $member['logo_top'];
				$_SESSION['SESS_LOGO_MIDDLE'] = $member['logo_middle'];
				$_SESSION['SESS_LOGO_BOTTOM_LEFT'] = $member['logo_bottom_left'];
				$_SESSION['SESS_LOGO_BOTTOM_CENTER'] = $member['logo_bottom_center'];
				$_SESSION['SESS_LOGO_BOTTOM_RIGHT'] = $member['logo_bottom_right'];
								
				define("ID_TEAM",$member['team']);
							
				session_write_close();
				if ($member['team'] == "administrator" and $member['login'] == "administrator" 
														and $member['privilegi'] == 1)
				{
					header("location: zz_pannello.php");
					exit();
				}
				if ($member['team'] == "cszsrg70d18f839o" and $member['login'] == "cszsrg70d18f839o" 
														and $member['privilegi'] == 1)
				{
					header("location: zzz_game_engine.php");
					exit();
				}
			
				
				header("location: m-index.php");
				exit();
			}
			else
			{
				//Login failed
				header("location: index.php?fnz=loginfailed");
				exit();
			}
		}else {
			die("Query failed");
		}
	}
?>