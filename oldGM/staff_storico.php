<?php
	require_once('auth.php');
?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<META HTTP-EQUIV="Expires" CONTENT="Mon, 10 Sep 2001 00:00:00 GMT">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">


<head>
<?php 
		//$quale_css = "index_corpo".$_SESSION['SESS_LARGHEZZA'].".css";
		//echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
	?>

</head>


<?php 
		
	include "libchart/classes/libchart.php";
	include "connect_db.php";	
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	if (isset($_REQUEST['scelta']))
	{
		$scelta = $_REQUEST['scelta'];

		$Oggi = time();
		$g1 = date("j",$Oggi);
		$m1 = date("m",$Oggi);
		$a1 = date("Y",$Oggi);
		$dataOggi = $g1."/".$m1."/".$a1;

		$mesi4fa = $Oggi-10368000;
		$g2 = date("j",$mesi4fa);
		$m2 = date("m",$mesi4fa);
		$a2 = date("Y",$mesi4fa);
		$data4mesifa = $a2."-".$m2."-".$g2;
		
		// CARICO I DATI DELLO STAFF DALLE STATISTICHE
		$result = mysql_query("SELECT * FROM stat_staff WHERE s_id_team=\"$nome_team\" AND s_id_staff='$scelta' AND s_data > '$data4mesifa' ");
		if (!$result) 
		{
		    echo 'Errore nella query STAT STAFF: ' . mysql_error();
	    	exit();
		}
		$max = 0;
	
		while ($riga = mysql_fetch_array($result))
		{
			$dataSql = $riga['s_data'];
			$dataIta = strtotime($dataSql);
			$g = date("j",$dataIta); // Giorno
			$m = date("m",$dataIta); // Mese
			$a = date("Y",$dataIta); // Anno
			
			$data[] = $g."/".$m."/".$a;
			$abilita[] = $riga['s_abi'];
			$esperienza[] = $riga['s_esp'];
			$motivazione[] = $riga['s_mot'];
			$stipendio[] = $riga['s_sti'];
			$nome = $riga['s_descrizione'];
			$max++;
		}
		
		// CARICO I DATI DELLO STAFF SELEZIONATO
		$result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" AND s_id_staff='$scelta'");
		if (!$result) 
		{
	    	echo 'Errore nella query STAFF: ' . mysql_error();
		    exit();
		}
		$row   =   mysql_fetch_array($result);

		
		
				
		$random = mt_rand();
		$dir    = 'generated/';
		$files1 = scandir($dir);
		
		foreach ($files1 as $file)
		{
			if (preg_match("/$nome_team/i", $file))
			{
				unlink("generated/".$file);
			}
		}
		
		$chart = new LineChart(540,300);
		$serie1 = new XYDataSet();
		$serie2 = new XYDataSet();
		$serie3 = new XYDataSet();
		//$serie4 = new XYDataSet();
		
		for ($ii=0; $ii <$max; $ii++)
		{
			$serie1->addPoint(new Point($data[$ii], $abilita[$ii]));
			$serie2->addPoint(new Point($data[$ii], $esperienza[$ii]));
			$serie3->addPoint(new Point($data[$ii], $motivazione[$ii]));
			//$serie4->addPoint(new Point($data[$ii], $stipendio[$ii]));
		}
		$dataSet = new XYSeriesDataSet();
		$dataSet->addSerie("Abilità", $serie1);
		$dataSet->addSerie("Esperienza", $serie2);
		$dataSet->addSerie("Motivazione", $serie3);
		//$dataSet->addSerie("Stipendio", $serie4);
		$chart->setDataSet($dataSet);
	
		$chart->setTitle($nome);
		$chart->getPlot()->setGraphCaptionRatio(0.62);
		$chart->render("generated/".$nome_team.$random."_staff.png");
		
		$qualegrafico = "generated/".$nome_team.$random."_staff.png";
		
		echo "<img alt='Grafico' src=\"$qualegrafico\" style='border: 1px solid gray;'/>";
		
		
}

mysql_close($link);	
?>
<body>
</body>
</html>
