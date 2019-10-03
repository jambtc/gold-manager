<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<link href="index_p.css" rel="stylesheet" type="text/css" />
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
	
	//Sanitize the POST values
	
	$login = $_SESSION["SESS_USER"];
	$team = $_SESSION["SESS_TEAM"];
	
	//$serie = clean($_POST["serie"]);
	//$email = clean($_POST["email"]);
	$password = clean($_POST["password"]);
	$cpassword = clean($_POST["cpassword"]);
	//$logosx = $_POST['logosx'];
	//$logodx = $_POST['logodx'];
	
	//Input Validations
	/*if($serie == '') {
		$errmsg_arr[] = 'Serie/Divisione mancante';
		$errflag = true;
	}
	if($email == '') {
		$errmsg_arr[] = 'eMail mancante';
		$errflag = true;
	}*/
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
	
	
	//If there are input validations, redirect back to the registration form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: form_change_pwd.php");
		exit();
	}

	//Create UPDATE query
	$qry = "UPDATE members SET passwd=\"".md5($_POST['password'])."\" WHERE login=\"$login\"";
	
	$result = @mysql_query($qry);
	
	//Check whether the query was successful or not
	if($result) {
		header("location: m-profile-modif-ok.php");
		exit();
	}else {
		die("Query failed");
	}
?>