<?php
	require_once('auth.php');
	include "connect_db.php";	

$nome_team = $_SESSION['SESS_TEAM'];

$maglietta = 0;
$wformazione = $_POST['ws_formazione'];

//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY RIQUADRO CHE CONTERRA' IL NUMERO DI MAGLIA
$appoggio= array('f_id_team','f_1','f_2','f_3','f_4','f_5','f_6','f_7','f_8','f_9','f_10',
							 'f_11','f_12','f_13','f_14','f_15','f_16','f_17','f_18','f_19','f_20',
							 'f_21','f_22','f_23','f_24','f_25','f_26','f_27','f_28','f_29','f_30',
							 'f_31','f_32','f_33','f_34','f_35','f_36','f_37','f_38','f_39','f_40',
							 'f_41','f_42','f_43','f_44','f_45','f_46','f_47','f_48','f_49','f_50',
							 'f_51','f_52','f_53','f_54','f_55','f_56','f_57','f_58','f_59','f_60',
							 'f_61','f_62','f_63','f_64','f_65','f_66','f_67','f_68','f_69','f_70',
							 'f_formazione');
							 



$controllo = mysql_query("SELECT * FROM formazione WHERE f_id_team=\"$nome_team\" AND f_formazione=\"$wformazione\"");
if (!$controllo) {
 			echo 'Errore nella query CONTROLLO: ' . mysql_error();
		    exit();
}
$righe=mysql_num_rows($controllo);
if ($righe > 0) { 
		$qry1 = "UPDATE formazione SET ";
		$qry2 = "";

		$conta = 1;
		while ($conta <71) {
				$qry2 = $qry2 . $appoggio[$conta] . " = 0, ";
				$conta++;
		}
		$qry2 = substr($qry2,0,-2);
		$qry = $qry1 . $qry2 . " WHERE f_id_team=\"$nome_team\" AND f_formazione=\"$wformazione\"";

		$result = mysql_query($qry);
		if (!$result) {
				echo 'Errore nella query azzera formazione: ' . mysql_error();
				exit();
		}
								
}else{
				
		$qry1 = "INSERT INTO formazione ( f_id_team,";
		$qry2 = "";
		$qry3 = "";

		$conta = 1;
		while ($conta <71) {
				$qry2 = $qry2 . $appoggio[$conta] . "," ;
				$qry3 = $qry3 .  "0," ;
				$conta++;
		}
		$qry2 = $qry2 . $appoggio[71];
		$qry3 = "\"".$nome_team."\",".$qry3 . "\"".$wformazione."\"";

		$qry = $qry1 . $qry2 . ") VALUES (" . $qry3 . " )";
		//echo $qry;		
		$result = mysql_query($qry);
		if (!$result) {
			echo 'Errore nella query inserisci azzera formazione: ' . mysql_error();
			exit();
		}
		
}


mysql_close($link);

echo "<script langage=\"Javascript\">";
echo "window.parent.frames['frame-dx'].location.href='formazione_dx.php?box=0&maglia=0&formazione=$wformazione';";
echo "</script>";

?>


<body>


</body>
</html>
