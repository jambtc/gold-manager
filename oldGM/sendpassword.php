<?php
// genera una stringa casuale della lunghezza desiderata     
function rand_string($len, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')  
{  
     $string = '';  
     for ($i = 0; $i < $len; $i++)  
     {  
         $pos = rand(0, strlen($chars)-1);  
         $string .= $chars{$pos};  
     }  
     return $string;  
}  
//Function to sanitize values received from the form. Prevents SQL injection
function clean($str) 
{
	$str = @trim($str);
	if(get_magic_quotes_gpc()) {
		$str = stripslashes($str);
	}
	return mysqli_real_escape_string($str);
}

//Start session
	session_start();
	
	//Include database connection details
	require_once('config.php');
	
	//Array to store validation errors
	$errmsg_arr = array();
	
	//Validation error flag
	$errflag = false;
	
	//Connect to mysql server
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(!$link) {
		die('Failed to connect to server: ' . mysqli_error());
	}
	
	//Select database
	$db = mysqli_select_db(DB_DATABASE);
	if(!$db) {
		die("Unable to select database");
	}
	
	//Sanitize the POST values
	$login = clean($_POST["username"]);
	$email = clean($_POST["email"]);
		
	/*echo $login." ";
	echo $email;
	exit;*/
	
	//Input Validations
	if($login == '') {
		$errmsg_arr[] = 'ID Utente mancante';
		$errflag = true;
	}
	if($email == '') {
		$errmsg_arr[] = 'eMail dove inviare la nuova password mancante';
		$errflag = true;
	}

	$query = "SELECT * FROM members WHERE login = \"".$_POST['username']."\"";

	$result = mysqli_query($link, $query);
	$row = mysqli_fetch_array($result);
	$user_id = $row['login'];
	$user_mail = $row['email'];
	
	$larghezza = $_POST['largh'];
	$altezza = $_POST['altez'];

	
	if($user_id != $_POST["username"])
	{
		$errmsg_arr[] = 'Nessuna corrispondenza trovata nel database';
		$errflag = true;
	}
	if($row['email'] != $_POST["email"]) 
	{
		$errmsg_arr[] = "Il nome utente non � associato all'indirizzo mail specificato";
		$errflag = true;

	} 	
	
		
	//If there are input validations, redirect back to the registration form
	if($errflag == 1) 
	{
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: form_ripristinopwd.php?larghezza=$larghezza&altezza=$altezza");
		exit();
	}
	else
	{
	
		$random_string = rand_string(10);
		$invio_password = $random_string;
		$sql = "UPDATE members SET passwd = \"".md5($random_string)."\" WHERE login =\"".$user_id."\"  LIMIT 1";
		$result = mysqli_query($link, $sql);
		$headers ="From:administrator\r\n";
		$subject ="Aggiornamento password";
		$message ="La tua nuova password �: $invio_password";
	
		@mail($user_mail, $subject, $message, $headers);
		if($result) 
		{
			header("location: sendpwd-ok.php?larghezza=$larghezza&altezza=$altezza");
			exit();
		}
	
	//header("location: sendpwd-ok.php");
			//exit();
	}

?>




</body>
</html>
