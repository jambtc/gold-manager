<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php 
	$quale_css = "css/index_corpo".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index_corpo1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>

<script type="text/javascript">
function check(arg)
{
 	arg2 = document.getElementById('ws_team').value ;
	arg3 = document.getElementById('ws_random').value ;
	
	document.graf.src="generated/"+arg2+arg3+"_giocatore"+arg+".png";
}		

</script>
</head>


<?php 

	include "libchart/classes/libchart.php";
	include "connect_db.php";	
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
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
	
	if (isset($_REQUEST['scelta']))
	{
		$scelta = $_REQUEST['scelta'];
	
		$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");
	
		$im_piede = array("p_destro.png","p_sinistro.png","p_ambidestro.png");
		$quale_grafico = "generated/".$nome_team.$random."_giocatore1.png";

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
		

		// CARICO I DATI DEL GIOCATORE DALLE STATISTICHE
		$result = mysql_query("SELECT * FROM stat_giocatori WHERE id_team=\"$nome_team\" AND id='$scelta' AND s_data > '$data4mesifa' ORDER BY s_data");
		if (!$result) {
		    echo 'Errore nella query STAT GIOCATORI: ' . mysql_error();
		    exit();
		}
		$max = 0;
		$esiste = mysql_num_rows($result);
		
		if ($esiste != 0)
		{
			while ($riga = mysql_fetch_array($result))
			{
				$dataSql = $riga['s_data'];
				$dataIta = strtotime($dataSql);
				$g = date("j",$dataIta); // Giorno
				$m = date("m",$dataIta); // Mese
				$a = date("Y",$dataIta); // Anno
				
				$data[] = $g."/".$m."/".$a;
				
				$skill[] = $riga['skill'];
				$forma[] = $riga['forma'];
				$fresc[] = $riga['fresc'];
				$condi[] = $riga['cond'];
				$esper[] = $riga['esp'];
				$po[] = $riga['po'];
				$df[] = $riga['df'];
				$cn[] = $riga['cn'];
				$pa[] = $riga['pa'];
				$rg[] = $riga['rg'];
				$cr[] = $riga['cr'];
				$tc[] = $riga['tc'];
				$tr[] = $riga['tr'];
				$part[] = $riga['part'];
				$reti[] = $riga['reti'];
				$gialli[] = $riga['gialli'];
				$rossi[] = $riga['rossi'];
				$stipendio[] = $riga['stipendio'];
				$valore[] = $riga['valore'];
				
				$max++;
			}
			
			// CARICO I DATI DEL GIOCATORE SELEZIONATO
			$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND id='$scelta'");
			if (!$result) {
				echo 'Errore nella query GIOCATORI: ' . mysql_error();
				exit();
			}
			$row   =   mysql_fetch_array($result);
	
			$wposizione = $row['pos'];
			$wpiede = strtoupper($row['piede']);
			$wpo = $row['po'];
			$wdf = $row['df'];
			$wcn = $row['cn'];
			$wpa = $row['pa'];
			$wrg = $row['rg'];
			$wcr = $row['cr'];
			$wtc = $row['tc'];
			$wtr = $row['tr'];
			$wesp = $row['esp'];
			$wnome = $row['nome'];
			$weta = $row['eta'];
			$wskill = $row['skill'];
			$wforma = $row['forma'];
			$wfresc = $row['fresc'];
			$wcond = $row['cond'];
			
			$wstip = number_format($row['stipendio'],0,",",".");
			$wvalo = number_format($row['valore'],0,",",".");
	
			/*//VISUALIZZAZIONE DATI A SCHEZMO
			echo "<fieldset style='width: 97%;'>";
			echo "<table  width='100%' border='0' style='font-size: 11px; background-color:#009000;	color:#FFFF00;
	font-family:Geneva, Arial, Helvetica, sans-serif;'>";
			echo "<tr>";
			echo "<th height='30' width='20' scope='col'><span class='Stile8'>Nr</span></th>";
			echo "    <th width='25%' scope='col' align='left'><span class='Stile8'>Nome</span></th>";
			echo "    <th width='20' scope='col'><span class='Stile8'>Et&agrave;</span></th>";
			echo "    <th width='30' scope='col'><span class='Stile8'>Skill</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>Pos</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>F.ma</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>Frs.</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>Con.</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>Esp.</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>PO</span></th>";
				echo "    <th width='25' scope='col'><span class='Stile8'>DF</span></th>";
				echo "    <th width='25' scope='col'><span class='Stile8'>CN</span></th>";
				echo "    <th width='25' scope='col'><span class='Stile8'>PA</span></th>";
				echo "    <th width='25' scope='col'><span class='Stile8'>RG</span></th>";
				echo "    <th width='25' scope='col'><span class='Stile8'>CR</span></th>";
				echo "    <th width='25' scope='col'><span class='Stile8'>TC</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>TR</span></th>";
			echo "    <th width='25' scope='col'><span class='Stile8'>Pd.</span></th>";
			echo "</tr>";
			
				switch ($wposizione)
				{
					case "PO":
						$immpos = $im_pos[0];
						break;
					case "DS":
						$immpos = $im_pos[1];
						break;
					case "D":
						$immpos = $im_pos[2];
						break;
					case "DD":
						$immpos = $im_pos[3];
						break;
					case "CS":
						$immpos = $im_pos[4];
						break;
					case "C":
						$immpos = $im_pos[5];
						break;
					case "CD":
						$immpos = $im_pos[6];
						break;
					case "AS":
						$immpos = $im_pos[7];
						break;
					case "A":
						$immpos = $im_pos[8];
						break;
					case "AD":
						$immpos = $im_pos[9];
						break;
					default:
						$immpos = $im_pos[10];
				}
			$immpos = "images/".$immpos;
			
			switch ($wpiede) {
				case "R":
					$immpiede = $im_piede[0];
					break;
				case "L":
					$immpiede = $im_piede[1];
					break;
				default:
					$immpiede = $im_piede[2];
							
			}
			$immpiede = "images/".$immpiede;
				
			echo "<tr>";
			echo "<td height='16'> $scelta" ."</td>";
			echo "<td> $wnome" ."</td>";
			echo "<td> <center>$weta" ."</td>";
			echo "<td> <center>$wskill" ."</td>";
			echo "<td>";
			
			echo "<center><img src=$immpos  /> ";
			
			echo "</td>";
			echo "<td> <center>$wforma" ."</td>";
			echo "<td> <center>$wfresc" ."</td>";
			echo "<td> <center>$wcond" ."</td>";
			echo "<td> <center>$wesp" ."</td>";
			echo "<td> <center>$wpo" ."</td>";
				echo "<td> <center>$wdf" ."</td>";
				echo "<td> <center>$wcn" ."</td>";
				echo "<td> <center>$wpa" ."</td>";
				echo "<td> <center>$wrg" ."</td>";
				echo "<td> <center>$wcr" ."</td>";
				echo "<td> <center>$wtc" ."</td>";
			echo "<td> <center>$wtr" ."</td>";
			echo "<td> <center><img src=$immpiede width='16' heigth='16' />" ."</td>";
			echo "</tr>";
			
			echo "</table>
				  </fieldset>";*/
				
			$chart1 = new LineChart();
			$chart2 = new LineChart();
			$chart3 = new LineChart();
			$chart4 = new LineChart();
			$chart5 = new LineChart();
	
			$serie1 = new XYDataSet();
			$serie2 = new XYDataSet();
			$serie3 = new XYDataSet();
			$serie4 = new XYDataSet();
			$serie5 = new XYDataSet();
			$serie6 = new XYDataSet();
			$serie7 = new XYDataSet();
			$serie8 = new XYDataSet();
			$serie9 = new XYDataSet();
			$serie10 = new XYDataSet();
			$serie11 = new XYDataSet();
			$serie12 = new XYDataSet();
			$serie13 = new XYDataSet();
			$serie14 = new XYDataSet();
			$serie15 = new XYDataSet();
			$serie16 = new XYDataSet();
			$serie17 = new XYDataSet();
			
			for ($ii=0; $ii <$max; $ii++)
			{
				$serie1->addPoint(new Point($data[$ii], $skill[$ii]));
				
				$serie2->addPoint(new Point($data[$ii], $forma[$ii]));
				$serie3->addPoint(new Point($data[$ii], $fresc[$ii]));
				$serie4->addPoint(new Point($data[$ii], $condi[$ii]));
				$serie5->addPoint(new Point($data[$ii], $esper[$ii]));
				
				$serie6->addPoint(new Point($data[$ii], $po[$ii]));
				$serie8->addPoint(new Point($data[$ii], $cn[$ii]));
				$serie9->addPoint(new Point($data[$ii], $pa[$ii]));
				$serie10->addPoint(new Point($data[$ii], $rg[$ii]));
				$serie13->addPoint(new Point($data[$ii], $tr[$ii]));
				$serie7->addPoint(new Point($data[$ii], $df[$ii]));
				$serie11->addPoint(new Point($data[$ii], $cr[$ii]));
				$serie12->addPoint(new Point($data[$ii], $tc[$ii]));
				
				$serie14->addPoint(new Point($data[$ii], $part[$ii]));
				$serie15->addPoint(new Point($data[$ii], $reti[$ii]));
				
				$serie16->addPoint(new Point($data[$ii], $gialli[$ii]));
				$serie17->addPoint(new Point($data[$ii], $rossi[$ii]));
			}
			$dataSet1 = new XYSeriesDataSet();
			$dataSet2 = new XYSeriesDataSet();
			$dataSet3 = new XYSeriesDataSet();
			$dataSet4 = new XYSeriesDataSet();
			$dataSet5 = new XYSeriesDataSet();
	
			$dataSet1->addSerie("Skill", $serie1);
			
			$dataSet2->addSerie("Forma", $serie2);
			$dataSet2->addSerie("Freschezza", $serie3);
			$dataSet2->addSerie("Condizione", $serie4);
			$dataSet2->addSerie("Esperienza", $serie5);
			
			$dataSet3->addSerie("Parate", $serie6);
				$dataSet3->addSerie("Difesa", $serie7);
				$dataSet3->addSerie("Contrasti",$serie8);
				$dataSet3->addSerie("Passaggi", $serie9);
				$dataSet3->addSerie("Regia", $serie10);
				$dataSet3->addSerie("Cross", $serie11);
				$dataSet3->addSerie("Tecnica", $serie12);
				$dataSet3->addSerie("Tiro", $serie13);
			
			$dataSet4->addSerie("Partite", $serie14);
			$dataSet4->addSerie("Reti", $serie15);
			
			$dataSet5->addSerie("Gialli", $serie16);
			$dataSet5->addSerie("Rossi", $serie17);		
			
			$chart1->setDataSet($dataSet1);
			$chart2->setDataSet($dataSet2);
			$chart3->setDataSet($dataSet3);
			$chart4->setDataSet($dataSet4);
			$chart5->setDataSet($dataSet5);
			
			$chart1->setTitle("Skill");
			$chart2->setTitle("Preparazione Atletica");
			$chart3->setTitle("Abilità Personali");
			$chart4->setTitle("Realizzazioni");
			$chart5->setTitle("Cartellini");
	
	
			$chart1->getPlot()->setGraphCaptionRatio(0.62);
			$chart2->getPlot()->setGraphCaptionRatio(0.62);
			$chart3->getPlot()->setGraphCaptionRatio(0.62);
			$chart4->getPlot()->setGraphCaptionRatio(0.62);
			$chart5->getPlot()->setGraphCaptionRatio(0.62);
			
			$chart1->render("generated/".$nome_team.$random."_giocatore4.png");
			$chart2->render("generated/".$nome_team.$random."_giocatore1.png");
			$chart3->render("generated/".$nome_team.$random."_giocatore2.png");
			$chart4->render("generated/".$nome_team.$random."_giocatore3.png");
			$chart5->render("generated/".$nome_team.$random."_giocatore5.png");
	
			echo "<table border='0' cellpadding='0' cellspacing='10' style='color:#000000; font-weight:bold;' >";
			echo "<tr><td>$wnome</td></tr>";
			echo "<td align='center'><input id='gio1' name='gio1' value='1' type='radio' onclick='javascript:check(1);' checked></td>";
			echo "<td align='center'><input id='gio1' name='gio1' value='2' type='radio' onclick='javascript:check(2);'></td>";
			echo "<td align='center'><input id='gio1' name='gio1' value='3' type='radio' onclick='javascript:check(3);'></td>";
			echo "<td align='center'><input id='gio1' name='gio1' value='4' type='radio' onclick='javascript:check(4);'></td>";
			echo "<td align='center'><input id='gio1' name='gio1' value='5' type='radio' onclick='javascript:check(5);'></td>";
			echo "</tr>";
						
			echo "<tr>";
			echo "<td>Preparazione Atletica</td>";
			echo "<td>Abilità&nbsp;Personali</td>";
			echo "<td>Statistiche&nbsp;di&nbsp;gioco</td>";
			echo "<td>Skill</td>";
			echo "<td>Cartellini";
			echo "<input id=\"ws_team\" name=\"ws_team\" value=\"$nome_team\" type='hidden'/>";		
			echo "<input id=\"ws_random\" name=\"ws_random\" value=\"$random\" type='hidden'/>";		
			echo "</td>";
	
			echo "</tr>";
			
			echo "<img id='graf' name='graf' src=\"$quale_grafico\" style='border: 1px solid gray;'/>";
			
		}
		else
		{
			echo "<h4>Non esistono dati su cui effettuare statistiche.</h4>";
		}
	}


mysql_close($link);	
?>
<body>
</body>
</html>
