<?php
	require_once('auth.php');
		
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
	
	$email = clean($_POST["email"]);
	
	$scudo_top = $_POST['scudo_top'];
	$scudo_middle = $_POST['scudo_middle'];
	$scudo_bottom_left = $_POST['scudo_bottom_left'];
	$scudo_bottom_center = $_POST['scudo_bottom_center'];
	$scudo_bottom_right = $_POST['scudo_bottom_right'];
	
	$avatar = $_POST['avatar'];
	
	//Input Validations
	
	if($email == '') {
		$errmsg_arr[] = 'eMail mancante';
		$errflag = true;
	}
	
	
	//If there are input validations, redirect back to the registration form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: m-index.php?fnz=setup&pg=14");
		exit();
	}

	$qry = "UPDATE members SET 	email=\"$email\", 
								logo_top = '$scudo_top',
								logo_middle = '$scudo_middle', 
								logo_bottom_left = '$scudo_bottom_left',
								logo_bottom_center = '$scudo_bottom_center',
								logo_bottom_right = '$scudo_bottom_right',
								avatar='$avatar'
							
							WHERE login=\"$login\"";
	
	$result = @mysql_query($qry);
	
	//Check whether the query was successful or not
	if($result) {
		header("location: m-index.php?fnz=setup&pg=14&mexinv=ok");
		exit();
	}else {
		die("Query failed");
	}
?>