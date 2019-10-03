<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<link rel="stylesheet" type="text/css" href="statistiche.css" />

	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>

</head>
<body style="background:trasparent;"> 
<?php
	require_once('auth.php');
	
	function DisegnaGrafico($x,$y,$array,$titolo,$file,$giocatori)
	{
			$chart = new PieChart($x,$y);
			$dataSet = new XYDataSet();
			$dataSet->addPoint(new Point("Portieri", intval($array[0]/$giocatori)));
			$dataSet->addPoint(new Point("Difensori", intval($array[1]/$giocatori)));
			$dataSet->addPoint(new Point("Centrocampisti", intval($array[2]/$giocatori)));
			$dataSet->addPoint(new Point("Attaccanti", intval($array[3]/$giocatori)));
			$chart->setDataSet($dataSet);
			$chart->setTitle($titolo);
			$chart->render($file);
	}

	include "libchart/classes/libchart.php";
	include "connect_db.php";	
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$a = $_REQUEST['stat'];
	$xx= 340;
	$yy= 180;
	
	if ($a == 0)
	{
		exit;
	}
	
	
	if ($a == 1)
	{
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" order by skill ");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0) {
			while ($row = mysql_fetch_array($result)){
				$nome[] = $row['nome'];
				$forza[] = $row['skill'];
				$valore[] = $row['valore'];
				$stipendio[] = $row['stipendio'];
				$eta[] = $row['eta'];
				$forma[] = $row['forma'];
				$fresc[] = $row['fresc'];
				$condi[] = $row['cond'];
			}
			// TOTALE GIOCATORI
			$totgio = mysql_num_rows($result);
					
			// GIOCATORE PIU' COSTOSO
			$wcos = array($valore,$nome);
			array_multisort($wcos[0], SORT_DESC, $wcos[1], SORT_DESC);
			$gnome = $wcos[1][0];
			$gcos = number_format($wcos[0][0],0,",",".");
			
			// FORZA MIGLIORI 11 / 15 / ROSA / VALORE ROSA
			$for11 = 0;
			$for15 = 0;
			$fortot = 0;
			$cazzo = 0;
			$spesa = 0;
			$etamedia = 0;
			$formamedia = 0;
			$frescmedia = 0;
			$condimedia = 0;
			
			$minchia = array($forza,$nome);
			array_multisort($minchia[0], SORT_DESC, $minchia[1], SORT_DESC);
			
			for ($ind=0; $ind < $totgio; $ind++){
					
					$fortot = $fortot + $forza[$ind];
					$cazzo = $cazzo + $valore[$ind];
					$spesa = $spesa + $stipendio[$ind];
					$etamedia = $etamedia + $eta[$ind];
					$formamedia = $formamedia + $forma[$ind];
					$frescmedia = $frescmedia + $fresc[$ind];
					$condimedia = $condimedia + $condi[$ind];
									
					if ($ind <11){
						$for11 = $for11 + $minchia[0][$ind];
					}
					if ($ind <15){
						$for15 = $for15 + $minchia[0][$ind];
					}
			}
			$etamedia = intval($etamedia/$totgio);
			$formamedia = intval($formamedia/$totgio);
			$frescmedia = intval($frescmedia/$totgio);
			$condimedia = intval($condimedia/$totgio);
			$scazzo = number_format($cazzo,0,",",".");
			$spesa = number_format($spesa,0,",",".");
			
			// GIOCATORE PIU' VECCHIO
			$wgio = array($eta,$nome);
			array_multisort($wgio[0], SORT_DESC, $wgio[1], SORT_DESC);
			$wvecchio = $wgio[1][0];
			$wetavecc = $wgio[0][0];
			
			// GIOCATORE PIU' GIOVANE
			array_multisort($wgio[0], SORT_ASC, $wgio[1], SORT_ASC);
			$wgiovane = $wgio[1][0];
			$wetagiov = $wgio[0][0];
			
			echo "<div id='MsgPausa'>";
			echo "<span id='s_rosa'>";
			
			echo "<table border='0' cellpadding='0' cellspacing='14' >";
			echo "<tr>
					<th height='15' align='right'>Numero di giocatori</th>
					<th align='left'>$totgio</th>
					
				  </tr>";
		
			echo "<tr>
					<th height='15' align='right'>Forza (migliori 11/15/rosa)</th>
					<th align='left'>$for11 / $for15 / $fortot</th>
				  </tr>";
						
			echo "<tr>
					<th height='15' align='right'>Valore rosa</th>
					<th align='left'>€. $scazzo</th>
				  </tr>";
				  
			echo "<tr>
					<th height='15' align='right'>Stipendi da pagare</th>
					<th align='left'>€. $spesa</th>
				  </tr>";
	
			echo "<tr>
					<th height='15' align='right'>Giocatore più costoso</th>
					<th align='left'>€. $gcos</th>
					<th align='left'>$gnome</th>
				  </tr>";
	
			echo "<tr>
					<th height='15' align='right'>Giocatore più giovane</th>
					<th align='left'>$wetagiov Anni</th>
					<th align='left'>$wgiovane</th>
				  </tr>";
				 
			echo "<tr>
					<th height='15' align='right'>Giocatore più vecchio</th>
					<th align='left'>$wetavecc Anni</th>
					<th align='left'>$wvecchio</th>
				  </tr>";

			echo "<tr>
					<th height='15' align='right'>Età media</th>
					<th align='left'>$etamedia Anni</th>
					<th align='left'>&nbsp;</th>
				  </tr>";
			echo "<tr>
					<th height='15' align='right'>Forma media</th>
					<th align='left'>$formamedia %</th>
					<th align='left'>&nbsp;</th>
				  </tr>";
			echo "<tr>
					<th height='15' align='right'>Freschezza media</th>
					<th align='left'>$frescmedia %</th>
					<th align='left'>&nbsp;</th>
				  </tr>";
			echo "<tr>
					<th height='15' align='right'>Condizione media</th>
					<th align='left'>$condimedia %</th>
					<th align='left'>&nbsp;</th>
				  </tr>";
				  
			
			echo "</table>";
			echo "</span>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
		else
		{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";

		}
	}
	
	if ($a == 2)
	{
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0) {
			while ($row = mysql_fetch_array($result)){
				$nome[] = $row['nome'];
				$forza[] = $row['skill'];
				$goal[] = $row['reti'];
				$partite[] = $row['part'];
			}
			// TOTALE GIOCATORI
			$totgio = mysql_num_rows($result);
			
			// LISTA ORDINATA PER I MIGLIORI SKILL
			$wcos = array($forza,$nome);
			array_multisort($wcos[0], SORT_DESC, $wcos[1], SORT_DESC);
			
			$wreti = array($goal,$nome);
			array_multisort($wreti[0], SORT_DESC, $wreti[1], SORT_DESC);
			
			$wmedia = array($goal,$partite);
			array_multisort($wmedia[0], SORT_DESC, $wmedia[1], SORT_DESC);
			
			echo "<div id='MsgPausa'>";
			echo "<span  id='s_rosa'>";
			
			echo "<table border='0' cellpadding='0' cellspacing='0' >";
			echo "<th width='20' height='30' align='left'>Pos.</th>
				  <th width='200' height='30' align='left'>Nome</th>
				  <th width='20' height='30' align='left'>Skill</th>
				  <th>&nbsp;</th>
				  ";
			echo "<th width='20' height='30' align='left'>Pos.</th>
				  <th width='200' height='30' align='left'>Nome</th>
				  <th width='20' height='30' align='left'>Goal</th>
				  <th width='20' height='30' align='left'>&nbsp;</th>
				  <th width='20' height='30' align='left'>Media</th>
				  ";
			echo "<tr>
					<td width=45% colspan='3'><hr></td>
					<td width=10% colspan='1'>&nbsp;</td>
					<td width=45% colspan='5'><hr></td>
				  </tr>";
		
				for ($ind=0; $ind < $totgio; $ind++){
					$num = 1 + $ind;
					$wnome = $wcos[1][$ind];
					$wskill = $wcos[0][$ind];
					$wnome2 = $wreti[1][$ind];
					$wskill2 = $wreti[0][$ind];
					if ($wmedia[1][$ind] != 0)
					{
						$wskill3 = round($wskill2/$wmedia[1][$ind],2);
					}
					else
					{
						$wskill3 = 0;
					}
						echo "<tr>
								<th align='left'>$num&deg;</th>
								<th align='left'>$wnome</th>
								<th align='left'>$wskill</th>
								<td>&nbsp;</td>
								<th align='left'>$num&deg;</th>
								<th align='left'>$wnome2</th>
								<th >$wskill2</th>
								<th align='left'>&nbsp;</th>
								<th >$wskill3</th>
							  </tr>";
				}
			echo "</table>";
			echo "</span>";
			echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}else{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
	}
	if ($a == 3){
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0) {
			while ($row = mysql_fetch_array($result)){
				$nome[] = $row['nome'];
				$valore[] = $row['valore'];
				$stip[] = $row['stipendio'];
			}
			// TOTALE GIOCATORI
			$totgio = mysql_num_rows($result);
			
			// LISTA ORDINATA PER I MIGLIORI valori
			$wcos = array($valore,$nome);
			array_multisort($wcos[0], SORT_DESC, $wcos[1], SORT_DESC);
			
			$wsalario = array($stip,$nome);
			array_multisort($wsalario[0], SORT_DESC, $wsalario[1], SORT_DESC);
			
			
			echo "<div id='MsgPausa'>";
			echo "<span  id='s_rosa'>";
			echo "<table border='0' cellpadding='0' cellspacing='0' >";
			echo "<th width='20' height='30' align='left'>Pos.</th>
				  <th width='200' height='30' align='left'>Nome</th>
				  <th width='100' height='30' align='left'>Valore</th>
				  ";
			echo "<th>&nbsp;</th>";
			echo "<th width='20' height='30' align='left'>Pos.</th>
				  <th width='200' height='30' align='left'>Nome</th>
				  <th width='100' height='30' align='left'>Stipendio</th>
				  ";
			echo "<tr>
					<td width=45% colspan='3'><hr></td>
					<td width=10% colspan='1'>&nbsp;</td>
					<td width=45% colspan='3'><hr></td>
				  </tr>";

				for ($ind=0; $ind < $totgio; $ind++){
					$num = 1 + $ind;
					$wnome = $wcos[1][$ind];
					$wvalore = number_format($wcos[0][$ind],0,",",".");
					
					$wnome2 = $wsalario[1][$ind];
					$wvalore2 = number_format($wsalario[0][$ind],0,",",".");
						echo "<tr>
								<th>$num&deg;</th>
								<th align='left'>$wnome</th>
								<th align='right'>€. $wvalore</th>
								<td>&nbsp;</td>
								<th>$num&deg;</th>
								<th align='left'>$wnome2</th>
								<th align='right'>€. $wvalore2</th>
								
								</tr>";
				}
			echo "</table>";
			echo "</span>";
			echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}else{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		}
	}
	
	if ($a == 4)
	{
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
		$abilita = array(0,0,0,0);
		$forma = array(0,0,0,0);
		$freschezza = array(0,0,0,0);
		$esperienza = array(0,0,0,0);
		$condizione = array(0,0,0,0);
		
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query select Giocatori: ' . mysql_error();
		    exit();
		}

		// TOTALE GIOCATORI
		$totgio = mysql_num_rows($result);
		if ($totgio != 0)
		{
			$conta = 0;
			while   ($row   =   mysql_fetch_array($result))
			{
				switch ($row['pos'])
				{
					case "PO":
						$rr = 0;
						break;
					case "DD":
					case "D":
					case "DS":
						$rr = 1;
						break;
					case "CD":
					case "C":
					case "CS":
						$rr = 2;
						break;
					case "AD":
					case "A":
					case "AS":
						$rr = 3;
						break;
				}
				$abilita[$rr] = $abilita[$rr] + $row['skill'];
				$forma[$rr] = $forma[$rr] + $row['forma'];
				$freschezza[$rr] = $freschezza[$rr] + $row['fresc'];
				$esperienza[$rr] = $esperienza[$rr] + $row['esp'];
				$condizione[$rr] = $condizione[$rr] + $row['cond'];
				$conta ++;
			} // end while
					
			DisegnaGrafico($xx,$yy,$abilita,"Abilità - totale","generated/".$nome_team.$random."_grafico_1t.png",1);
			DisegnaGrafico($xx,$yy,$forma,"Forma - totale","generated/".$nome_team.$random."_grafico_2t.png",1);
			DisegnaGrafico($xx,$yy,$freschezza,"Freschezza - totale","generated/".$nome_team.$random."_grafico_3t.png",1);
			DisegnaGrafico($xx,$yy,$condizione,"Condizione - totale","generated/".$nome_team.$random."_grafico_4t.png",1);
			DisegnaGrafico($xx,$yy,$esperienza,"Esperienza - totale","generated/".$nome_team.$random."_grafico_5t.png",1);
			
			
			DisegnaGrafico($xx,$yy,$forma,"Forma - media","generated/".$nome_team.$random."_grafico_2m.png",$totgio);
			DisegnaGrafico($xx,$yy,$freschezza,"Freschezza - media","generated/".$nome_team.$random."_grafico_3m.png",$totgio);
			DisegnaGrafico($xx,$yy,$condizione,"Condizione - media","generated/".$nome_team.$random."_grafico_4m.png",$totgio);
			DisegnaGrafico($xx,$yy,$esperienza,"Esperienza - media","generated/".$nome_team.$random."_grafico_5m.png",$totgio);
			DisegnaGrafico($xx,$yy,$abilita,"Abilità - media","generated/".$nome_team.$random."_grafico_1m.png",$totgio/100);
			
			$atletica = $random."_grafico_1";
			
			echo "<div id='MsgPausa'>";
			echo "<span  id='s_rosa'>";
			echo "<table border='0' cellpadding='3' cellspacing='3' >
				  <tr>
					<td width='200'  align='left' valign='middle'>
						<table border='0' cellpadding='0' cellspacing='0' >
						<tr><th align='left'>
							<input name='atletica' value='1' type='radio' onclick='javascript:CambiaGrafico(1);' checked>Abilità
						</th></tr>
						
						<tr><th align='left'>
							<input name='atletica' value='2' type='radio' onclick='javascript:CambiaGrafico(2);'>Forma
						</th></tr>
						<tr><th align='left'>
								<input name='atletica' value='3' type='radio' onclick='javascript:CambiaGrafico(3);'>Freschezza
						</th></tr>
						<tr><th align='left'>
								<input name='atletica' value='4' type='radio' onclick='javascript:CambiaGrafico(4);'>Condizione
						</th></tr>
						<tr><th align='left'>
								<input name='atletica' value='5' type='radio' onclick='javascript:CambiaGrafico(5);'>Esperienza
						</th></tr>
						</table>
					</td>
					<td rowspan='2'>
						<iframe  src=\"grafico.php?atl=$atletica\" height='400' width='400' allowtransparency='1' name='grafico' frameborder='0'  scrolling='no'></iframe>
					</td>
				</tr>
				<input id='ws_random' name='ws_random' value='$random' type='hidden'/>
			";
			echo "</table>";
			echo "</span>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
		else
		{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}
	
	if ($a == 5)
	{
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
		$parate = array(0,0,0,0);
		$difesa = array(0,0,0,0);
		$contrasti = array(0,0,0,0);
		$passaggi = array(0,0,0,0);
		$regia = array(0,0,0,0);
		$cross = array(0,0,0,0);
		$tecnica = array(0,0,0,0);
		$tiro = array(0,0,0,0);
		
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		// TOTALE GIOCATORI
		$totgio = mysql_num_rows($result);
		if ($totgio != 0) {
			$conta = 0;
			while   ($row   =   mysql_fetch_array($result))
			{
				switch ($row['pos'])
				{
					case "PO":
						$rr = 0;
						break;
					case "DD":
					case "D":
					case "DS":
						$rr = 1;
						break;
					case "CD":
					case "C":
					case "CS":
						$rr = 2;
						break;
					case "AD":
					case "A":
					case "AS":
						$rr = 3;
						break;
				}
				
				$parate[$rr] = $parate[$rr] + $row['po'];
				$difesa[$rr] = $difesa[$rr] + $row['df'];
				$cross[$rr] = $cross[$rr] + $row['cr'];
				$tecnica[$rr] = $tecnica[$rr] + $row['tc'];
						
				$contrasti[$rr] = $contrasti[$rr] + $row['cn'];
				$passaggi[$rr] = $passaggi[$rr] + $row['pa'];
				$regia[$rr] = $regia[$rr] + $row['rg'];
				$tiro[$rr] = $tiro[$rr] + $row['tr'];
				$conta ++;
			} // end while
			DisegnaGrafico($xx,$yy,$parate,"Parate - totale","generated/".$nome_team.$random."_grafico_6t.png",1);
			DisegnaGrafico($xx,$yy,$contrasti,"Contrasti - totale","generated/".$nome_team.$random."_grafico_8t.png",1);
			DisegnaGrafico($xx,$yy,$passaggi,"Passaggi - totale","generated/".$nome_team.$random."_grafico_9t.png",1);
			DisegnaGrafico($xx,$yy,$regia,"Regia - totale","generated/".$nome_team.$random."_grafico_10t.png",1);
			DisegnaGrafico($xx,$yy,$tiro,"Tiro - totale","generated/".$nome_team.$random."_grafico_13t.png",1);
			DisegnaGrafico($xx,$yy,$parate,"Parate - media","generated/".$nome_team.$random."_grafico_6m.png",$totgio);
			DisegnaGrafico($xx,$yy,$contrasti,"Contrasti - media","generated/".$nome_team.$random."_grafico_8m.png",$totgio);
			DisegnaGrafico($xx,$yy,$passaggi,"Passaggi - media","generated/".$nome_team.$random."_grafico_9m.png",$totgio);
			DisegnaGrafico($xx,$yy,$regia,"Regia - media","generated/".$nome_team.$random."_grafico_10m.png",$totgio);
			DisegnaGrafico($xx,$yy,$tiro,"Tiro - media","generated/".$nome_team.$random."_grafico_13m.png",$totgio);
			DisegnaGrafico($xx,$yy,$difesa,"Difesa - totale","generated/".$nome_team.$random."_grafico_7t.png",1);
			DisegnaGrafico($xx,$yy,$cross,"Cross - totale","generated/".$nome_team.$random."_grafico_11t.png",1);
			DisegnaGrafico($xx,$yy,$tecnica,"Tecnica - totale","generated/".$nome_team.$random."_grafico_12t.png",1);
			DisegnaGrafico($xx,$yy,$difesa,"Difesa - media","generated/".$nome_team.$random."_grafico_7m.png",$totgio);
			DisegnaGrafico($xx,$yy,$cross,"Cross - media","generated/".$nome_team.$random."_grafico_11m.png",$totgio);
			DisegnaGrafico($xx,$yy,$tecnica,"Tecnica - media","generated/".$nome_team.$random."_grafico_12m.png",$totgio);
			
			$atletica = $random."_grafico_6";
			
			echo "<div id='MsgPausa'>";
			
			echo "<span  id='s_rosa'>";
			echo "<table border='0' cellpadding='3' cellspacing='3' >";
			echo "<tr>";
			echo "<td width='200'  align='left' valign='middle'>";
			echo "<table border='0' cellpadding='0' cellspacing='0' >";
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='6'  type='radio' onclick='javascript:CambiaGrafico(6);' checked>Parate";
			echo "</th></tr>";
			
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='7'  type='radio' onclick='javascript:CambiaGrafico(7);'>Difesa";
			echo "</th></tr>";
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='8'  type='radio' onclick='javascript:CambiaGrafico(8);'>Contrasti";
			echo "</th></tr>";
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='9'  type='radio' onclick='javascript:CambiaGrafico(9);'>Passaggi";
			echo "</th></tr>";
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='10'  type='radio' onclick='javascript:CambiaGrafico(10);'>Regia";
			echo "</th></tr>";
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='11'  type='radio' onclick='javascript:CambiaGrafico(11);'>Cross";
			echo "</th></tr>";
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='12'  type='radio' onclick='javascript:CambiaGrafico(12);'>Tecnica";
			echo "</th></tr>";
			echo "<tr><th align='left'>";
			echo "<input id='abilita' name='abilita' value='13'  type='radio' onclick='javascript:CambiaGrafico(13);'>Tiro";
			echo "</th>
					<input id='ws_random' name='ws_random' value='$random' type='hidden'/>
			</tr>";
			
			echo "</table>
					</td>
					<td rowspan='2'>
						<iframe  src=\"grafico.php?atl=$atletica\" height='400' width='400' allowtransparency='1' name='grafico' frameborder='0' scrolling='no'></iframe>
					</td>
				</tr>
			";
			echo "</table>";
			echo "</span>";
			echo "</form>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
		else
		{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";

			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}

	}
	
	if ($a == 6)
	{
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
		
		$partite = array(0,0,0,0);
		$reti = array(0,0,0,0);
		$gialli = array(0,0,0,0);
		$rossi = array(0,0,0,0);
		
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		// TOTALE GIOCATORI
		$totgio = mysql_num_rows($result);
		if ($totgio !=0) {
			$conta = 0;
			while   ($row   =   mysql_fetch_array($result))
			{
				switch ($row['pos'])
				{
					case "PO":
						$rr = 0;
						break;
					case "DD":
					case "D":
					case "DS":
						$rr = 1;
						break;
					case "CD":
					case "C":
					case "CS":
						$rr = 2;
						break;
					case "AD":
					case "A":
					case "AS":
						$rr = 3;
						break;
				}
			
				$partite[$rr] = $partite[$rr] + $row['part'];
				$reti[$rr] = $reti[$rr] + $row['reti'];
				$gialli[$rr] = $gialli[$rr] + $row['gialli'];
				$rossi[$rr] = $rossi[$rr] + $row['rossi'];
				$conta ++;
			} // end while
					
			DisegnaGrafico($xx,$yy,$partite,"Partite giocate - totale","generated/".$nome_team.$random."_grafico_14t.png",1);
			DisegnaGrafico($xx,$yy,$reti,"Goal fatti - totale","generated/".$nome_team.$random."_grafico_15t.png",1);
			DisegnaGrafico($xx,$yy,$gialli,"Cartellini gialli - totale","generated/".$nome_team.$random."_grafico_16t.png",1);
			DisegnaGrafico($xx,$yy,$rossi,"Cartellini rossi - totale","generated/".$nome_team.$random."_grafico_17t.png",1);
			
			DisegnaGrafico($xx,$yy,$partite,"Partite giocate - media","generated/".$nome_team.$random."_grafico_14m.png",$totgio);
			DisegnaGrafico($xx,$yy,$reti,"Goal fatti - media","generated/".$nome_team.$random."_grafico_15m.png",$totgio);
			DisegnaGrafico($xx,$yy,$gialli,"Cartellini gialli - media","generated/".$nome_team.$random."_grafico_16m.png",$totgio);
			DisegnaGrafico($xx,$yy,$rossi,"Cartellini rossi - media","generated/".$nome_team.$random."_grafico_17m.png",$totgio);
			
			$atletica = $random."_grafico_14";
			
			echo "<div id='MsgPausa'>";
			echo "<span  id='s_rosa'>";
			echo "<table border='0' cellpadding='0' cellspacing='0' >
				  <tr>
					<td width='200'  align='left' valign='middle'>
						<table border='0' cellpadding='0' cellspacing='0' >
						<tr ><th align='left'>
						<input id='risultati' name='risultati' value='14'  type='radio' onclick='javascript:CambiaGrafico(14);' checked>Partite
							</th></tr>
						<tr><th align='left'>
							<input id='risultati' name='risultati' value='15'  type='radio' onclick='javascript:CambiaGrafico(15);'>Goal
						</th></tr>
						<tr><th align='left'>
							<input id='risultati' name='risultati' value='16'  type='radio' onclick='javascript:CambiaGrafico(16);'>Ammonizioni
						</th></tr>
						<tr><th align='left'>
						<input id='risultati' name='risultati' value='17'  type='radio' onclick='javascript:CambiaGrafico(17);'>Espulsioni
						</th></tr>
						<input id='ws_random' name='ws_random' value='$random' type='hidden'/>
						
						</table>
					</td>
					<td rowspan='2'>
						<iframe  src=\"grafico.php?atl=$atletica\" height='400' width='400' allowtransparency='1' name='grafico' frameborder='0' scrolling='no'></iframe>
					</td>
				</tr>
			";
			echo "</table>";
			echo "</span>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		} else {
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}
		
	if ($a == 7)
	{
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
		
		$stipendio = array(0,0,0,0);
		$valore = array(0,0,0,0);
		
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		// TOTALE GIOCATORI
		$totgio = mysql_num_rows($result);
		if ($totgio != 0) {
			$conta = 0;
			while   ($row   =   mysql_fetch_array($result))
			{
				switch ($row['pos'])
				{
					case "PO":
						$rr = 0;
						break;
					case "DD":
					case "D":
					case "DS":
						$rr = 1;
						break;
					case "CD":
					case "C":
					case "CS":
						$rr = 2;
						break;
					case "AD":
					case "A":
					case "AS":
						$rr = 3;
						break;
				}
				$stipendio[$rr] = $stipendio[$rr] + $row['stipendio'];
				$valore[$rr] = $valore[$rr] + $row['valore'];
				$conta ++;
			} // end while
					
			DisegnaGrafico($xx,$yy,$stipendio,"Stipendio giocatori - totale","generated/".$nome_team.$random."_grafico_18t.png",1);
			DisegnaGrafico($xx,$yy,$valore,"Valore di mercato - totale","generated/".$nome_team.$random."_grafico_19t.png",1);		
	
			DisegnaGrafico($xx,$yy,$stipendio,"Stipendio giocatori - media","generated/".$nome_team.$random."_grafico_18m.png",$totgio);
			DisegnaGrafico($xx,$yy,$valore,"Valore di mercato - media","generated/".$nome_team.$random."_grafico_19m.png",$totgio);		
			
			$atletica = $random."_grafico_18";
			
			echo "<div id='MsgPausa'>";
			echo "<span  id='s_rosa'>";
			echo "<table border='0' cellpadding='0' cellspacing='0' >
				  <tr>
					<td width='200'  align='left' valign='middle'>
						<table border='0' cellpadding='0' cellspacing='0' >
						<tr><th align='left'>
							<input id='bilancio' name='bilancio' value='1'  type='radio' onclick='javascript:CambiaGrafico(18);' checked>Stipendio
							</th></tr>
						<tr><th align='left'>
						<input id='bilancio' name='bilancio' value='2'  type='radio' onclick='javascript:CambiaGrafico(19);'>Valore
						</th></tr>
						<input id='ws_random' name='ws_random' value='$random' type='hidden'/>
						</table>
					</td>
					<td rowspan='2'>
						<iframe  src=\"grafico.php?atl=$atletica\" height='400' width='400' allowtransparency='1' name='grafico' frameborder='0' scrolling='no'></iframe>
					</td>
				</tr>
			";
			echo "</table>";
			echo "</span>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}else{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}
	
	if ($a == 8){
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0) {
			while ($rig = mysql_fetch_array($result)){
				$nome[] = $rig['nome'];
				$wskill[] = $rig['skill'];
				$wpo[] = $rig['po'];
				$wdf[] = $rig['df'];
				$wcn[] = $rig['cn'];
				$wpa[] = $rig['pa'];
				$wrg[] = $rig['rg'];
				$wcr[] = $rig['cr'];
				$wtc[] = $rig['tc'];
				$wtr[] = $rig['tr'];
				$wes[] = $rig['esp'];
				
			}
			// TOTALE GIOCATORI
			$totgio = mysql_num_rows($result);
			
			// LISTA ORDINATA PER I MIGLIORI valori
			$wportiere = array($wpo,$nome);
			array_multisort($wportiere[0], SORT_DESC, $wportiere[1], SORT_DESC);
			$xpo = $wportiere[1][0];
			$xpo2 = $wportiere[0][0];
			
			$wdifensore = array($wdf,$nome);
			array_multisort($wdifensore[0], SORT_DESC, $wdifensore[1], SORT_DESC);
			$xdf = $wdifensore[1][0];
			$xdf2 = $wdifensore[0][0];
									
			$wcontrasti = array($wcn,$nome);
			array_multisort($wcontrasti[0], SORT_DESC, $wcontrasti[1], SORT_DESC);
			$xcn = $wcontrasti[1][0];
			$xcn2 = $wcontrasti[0][0];
			
			$wpassaggi = array($wpa,$nome);
			array_multisort($wpassaggi[0], SORT_DESC, $wpassaggi[1], SORT_DESC);
			$xpa = $wpassaggi[1][0];
			$xpa2 = $wpassaggi[0][0];
			
			$wregia = array($wrg,$nome);
			array_multisort($wregia[0], SORT_DESC, $wregia[1], SORT_DESC);
			$xrg = $wregia[1][0];
			$xrg2 = $wregia[0][0];
			
			$wcross = array($wcr,$nome);
			array_multisort($wcross[0], SORT_DESC, $wcross[1], SORT_DESC);
			$xcr = $wcross[1][0];
			$xcr2 = $wcross[0][0];
		
			$wtecnica = array($wtc,$nome);
			array_multisort($wtecnica[0], SORT_DESC, $wtecnica[1], SORT_DESC);
			$xtc = $wtecnica[1][0];
			$xtc2 = $wtecnica[0][0];
			
			$wtiro = array($wtr,$nome);
			array_multisort($wtiro[0], SORT_DESC, $wtiro[1], SORT_DESC);
			$xtr = $wtiro[1][0];
			$xtr2 = $wtiro[0][0];
			
			$wesperienza = array($wes,$nome);
			array_multisort($wesperienza[0], SORT_DESC, $wesperienza[1], SORT_DESC);
			$xes = $wesperienza[1][0];
			$xes2 = $wesperienza[0][0];

			
			echo "<div id='MsgPausa'>";
			echo "<span id='s_rosa' style='font-weight:bold;'>";
			
			echo "<table border='0' cellpadding='0' cellspacing='3' >";
			echo "<tr><th  align='left'>Ruolo</th><th align='left'>Nome</th><th align='right'>Valore</th>";
			echo "<tr><td colspan='3'><hr></td></tr>";
			echo "<tr><th width='100' height='30' align='left'>Portiere</th><td>$xpo</td><td align='right'>$xpo2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Difesa</th><td>$xdf</td><td align='right'>$xdf2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Contrasti</th><td>$xcn</td><td align='right'>$xcn2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Passaggi</th><td>$xpa</td><td align='right'>$xpa2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Regia</th><td>$xrg</td><td align='right'>$xrg2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Cross</th><td>$xcr</td><td align='right'>$xcr2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Tecnica</th><td>$xtc</td><td align='right'>$xtc2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Tiro</th><td>$xtr</td><td align='right'>$xtr2</td></tr>";
			echo "<tr><th width='20' height='30' align='left'>Esperienza</th><td>$xes</td><td align='right'>$xes2</td></tr>";
		
			echo "</table>";
			echo "</span>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}else{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}	
	
	if ($a == 9){
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result) {
		    echo 'Errore nella query select Giocatori: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0) {
			while ($rig = mysql_fetch_array($result)){
				$nome[] = $rig['nome'];
				$nr[] = $rig['nr'];
				$wskill[] = $rig['skill'];
				$wpo[] = $rig['po'];
				$wdf[] = $rig['df'];
				$wcn[] = $rig['cn'];
				$wpa[] = $rig['pa'];
				$wrg[] = $rig['rg'];
				$wcr[] = $rig['cr'];
				$wtc[] = $rig['tc'];
				$wtr[] = $rig['tr'];
				$wes[] = $rig['esp'];
				$wpd[] = strtoupper($rig['piede']);
				$wruolo[] = $rig['pos'];
				
			}
			// TOTALE GIOCATORI
			$totgio = mysql_num_rows($result);
			$formula = "Formula 2";
			$xpor = "";
			$xdfs = "";
			$xdfc = "";
			$xdfd = "";
			$xces = "";
			$xcec = "";
			$xced = "";
			$xats = "";
			$xatc = "";
			$xatd = "";
			$qu0_val[0] = 0;
			$qu1_val[0] = 0;
			$qu2_val[0] = 0;
			$qu3_val[0] = 0;
			$qu4_val[0] = 0;
			$qu5_val[0] = 0;
			$qu6_val[0] = 0;
			$qu7_val[0] = 0;
			$qu8_val[0] = 0;
			$qu9_val[0] = 0;			
			$qu0_nom[0] = "";
			$qu1_nom[0] = "";
			$qu2_nom[0] = "";
			$qu3_nom[0] = "";
			$qu4_nom[0] = "";
			$qu5_nom[0] = "";
			$qu6_nom[0] = "";
			$qu7_nom[0] = "";
			$qu8_nom[0] = "";
			$qu9_nom[0] = "";
			
			//creao array contenente numeri da 1 a 64
			for ($xx = 1; $xx < 65; $xx++)
			{
				$box_numeri[] = $xx;
			}
				
			// LISTA ORDINATA PER I MIGLIORI valori
			$conta = 0;
			while ($conta < $totgio) 
			{
					$controllo = 15; // valore dell'esperienza	
					
					// CARICO I DATI DAL CALCOLATORE
					$calc_result = mysql_query("SELECT * FROM calcolatore WHERE formula='$formula' ORDER BY ord");
					if (!$calc_result) { echo 'Errore nella query calcolatore: ' . mysql_error(); exit(); }
					
					$max = 0;
					$ii = 1; 
					while   ($riga   =   mysql_fetch_array($calc_result)) 
					{
						// CONTROLLA TUTTI I CASI DELLE CASELLE
						switch ($ii) { 
							//qsinistro
							case 1:
							case 2:
							case 8:
							case 9:
							case 15:
							case 16:
							case 22:
							case 23:
							case 29:
							case 30:
							case 36:
							case 37:
							case 43:
							case 44:
							case 50:
							case 51:
							case 57:
							case 58:
								if ($wpd[$conta] == "R")  {$pdd = -6;}
								elseif ($wpd[$conta] == "L") {$pdd = 6;}
								else{$pdd = 4;}
								break;
							//qcentrale
							case 3:
							case 4:
							case 5:
							case 10:
							case 11:
							case 12:
							case 17:
							case 18:
							case 19:
							case 24:
							case 25:
							case 26:
							case 31:
							case 32:
							case 33:
							case 38:
							case 39:
							case 40:
							case 45:
							case 46:
							case 47:
							case 52:
							case 53:
							case 54:
							case 59:
							case 60:				
							case 61:
								if ($wpd[$conta] == "R")  {$pdd = 4;}
								elseif ($wpd[$conta] == "L") {$pdd = 4;}
								else{$pdd = 7;}
								break;
							//qdestro
							case 6:
							case 7:
							case 13:
							case 14:
							case 20:
							case 21:
							case 27:
							case 28:
							case 34:
							case 35:
							case 41:
							case 42:
							case 48:
							case 49:
							case 55:
							case 56:
							case 62:
							case 63:
								if ($wpd[$conta] == "R")  {$pdd = 6;}
								elseif ($wpd[$conta] == "L") {$pdd = -6;}
								else{$pdd = 4;}
								break;
								
							//q0	
							case 64:
								$pdd = 4;
								break;
						} // end switch
						
				$riquadro[$ii] = $pdd + $wpo[$conta]*$riga['po'] + $wdf[$conta]*$riga['df'] + $wcn[$conta]*$riga['cn'];
				$riquadro[$ii] = $riquadro[$ii] + $wpa[$conta]*$riga['pa'] + $wrg[$conta]*$riga['rg'] + $wcr[$conta]*$riga['cr'];
				$riquadro[$ii] = $riquadro[$ii] + $wtc[$conta]*$riga['tc'] + $wtr[$conta]*$riga['tr'];
						$riquadro[$ii] = $riquadro[$ii] / (101-$controllo) ;
						$riquadro[$ii] = round($riquadro[$ii],1);		
						
						/*if ($max <= $riquadro[$ii]) 
						{ 
							$max = $riquadro[$ii] ; 
							$box = $ii;  
						}*/
						
						$ii ++ ;
					}// end controllo sul calcolatore
					
					$wgiocatore = array($riquadro,$box_numeri);
					
					$valore_giocatore[$nr[$conta]] = $riquadro;
					
					array_multisort($wgiocatore[0], SORT_DESC, $wgiocatore[1], SORT_DESC);
					// controlla i migliori 10 piazzamenti in campo di ciascun giocatore
					for ($ix = 0; $ix <=10; $ix ++)
					{
						// CONTROLLA TUTTI I CASI DELLE CASELLE
						$contaci = 0;
						switch ($wgiocatore[1][$ix])
						{
							case 1:
							case 2:
							case 8:
							case 9:
							case 15:
							case 16:
								//ATTACCO SINISTRO Q7
								if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
								{
									$qu7_val[] = $wgiocatore[0][$ix];
									$qu7_nom[] = $nome[$conta];
								}
								break;
							case 3:
							case 4:
							case 5:
							case 10:
							case 11:
							case 12:
							case 17:
							case 18:
							case 19:
								$qu8_val[] = $wgiocatore[0][$ix];
								$qu8_nom[] = $nome[$conta]; // attacco centrale
								break;
							case 6:
							case 7:
							case 13:
							case 14:
							case 20:
							case 21:
								// ATTACCO DESTRO Q9
								if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
								{
									$qu9_val[] = $wgiocatore[0][$ix];
									$qu9_nom[] = $nome[$conta]; 
								}
								break;
							case 22:
							case 23:
							case 29:
							case 30:
							case 36:
							case 37:
								if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
								{
									$qu4_val[] =$wgiocatore[0][$ix];
									$qu4_nom[] = $nome[$conta]; //centrocampo sinistra
								}
								break;
							case 24:
							case 25:
							case 26:
							case 31:
							case 32:
							case 33:
							case 38:
							case 39:
							case 40:
								$qu5_val[] = $wgiocatore[0][$ix];
								$qu5_nom[] = $nome[$conta]; //centrocampo centrale
								break;
							case 27:
							case 28:
							case 34:
							case 35:
							case 41:
							case 42:
								if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
								{
									$qu6_val[] = $wgiocatore[0][$ix];
									$qu6_nom[] = $nome[$conta]; //centrocampo destra
								}
								break;
							case 43:
							case 44:
							case 50:
							case 51:
							case 57:
							case 58:
								if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
								{
									$qu1_val[] = $wgiocatore[0][$ix];
									$qu1_nom[] = $nome[$conta]; //difesa sinistra
								}
								break;
							case 45:
							case 46:
							case 47:
							case 52:
							case 53:
							case 54:
							case 59:
							case 60:				
							case 61:
								$qu2_val[] = $wgiocatore[0][$ix];
								$qu2_nom[] = $nome[$conta]; //difesa centrale
								break;
							case 48:
							case 49:
							case 55:
							case 56:
							case 62:
							case 63:
								if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
								{
									$qu3_val[] = $wgiocatore[0][$ix];
									$qu3_nom[] = $nome[$conta]; //difesa destra
								}
								break;
							case 64:
								$qu0_val[] = $wgiocatore[0][$ix];
								$qu0_nom[] = $nome[$conta]; // portiere
								break;
							
						} // end switch
					}
					$conta ++;
					
			} // end while
			
			
			if (count($qu0_val) > 1)
			{
				$wportiere = array($qu0_val,$qu0_nom);
				array_multisort($wportiere[0], SORT_DESC, $wportiere[1], SORT_DESC);
				$xpor = $wportiere[1][0];
			}
			if (count($qu1_val) > 1)
			{			
				$wdif_sin = array($qu1_val,$qu1_nom);
				array_multisort($wdif_sin[0], SORT_DESC, $wdif_sin[1], SORT_DESC);
				$xdfs = $wdif_sin[1][0];
			}
			if (count($qu2_val) > 1)
			{
				$wdif_cen = array($qu2_val,$qu2_nom);
				array_multisort($wdif_cen[0], SORT_DESC, $wdif_cen[1], SORT_DESC);
				$xdfc = $wdif_cen[1][0];
			}
			if (count($qu3_val) > 1)
			{
				$wdif_des = array($qu3_val,$qu3_nom);
				array_multisort($wdif_des[0], SORT_DESC, $wdif_des[1], SORT_DESC);
				$xdfd = $wdif_des[1][0];
			}
			if (count($qu4_val) > 1)
			{
				$wcen_sin = array($qu4_val,$qu4_nom);
				array_multisort($wcen_sin[0], SORT_DESC, $wcen_sin[1], SORT_DESC);
				$xces = $wcen_sin[1][0];
			}
			if (count($qu5_val) > 1)
			{			
				$wcen_cen = array($qu5_val,$qu5_nom);
				array_multisort($wcen_cen[0], SORT_DESC, $wcen_cen[1], SORT_DESC);
				$xcec = $wcen_cen[1][0];
			}
			if (count($qu6_val) > 1)
			{			
				$wcen_des = array($qu6_val,$qu6_nom);
				array_multisort($wcen_des[0], SORT_DESC, $wcen_des[1], SORT_DESC);
				$xced = $wcen_des[1][0];
			}
			if (count($qu7_val) > 1)
			{			
				$watt_sin = array($qu7_val,$qu7_nom);
				array_multisort($watt_sin[0], SORT_DESC, $watt_sin[1], SORT_DESC);
				$xats = $watt_sin[1][0];
			}
			if (count($qu8_val) > 1)
			{			
				$watt_cen = array($qu8_val,$qu8_nom);
				array_multisort($watt_cen[0], SORT_DESC, $watt_cen[1], SORT_DESC);
				$xatc = $watt_cen[1][0];
			}
			if (count($qu9_val) > 1)
			{			
				$watt_des = array($qu9_val,$qu9_nom);
				array_multisort($watt_des[0], SORT_DESC, $watt_des[1], SORT_DESC);
				$xatd = $watt_des[1][0];
			}
			echo "<div id='MsgPausa'>";
			echo "<span id='s_rosa' style='font-weight:bold;'>";
			
			echo "<table border='0' cellpadding='0' cellspacing='5' style='color:#ffffff;' >";
			echo "<tr>";
			echo "<th bgcolor='#006600' width='100'><h4>Q7</h4><center>$xats</th>";
			echo "<th bgcolor='#006600' width='100'><h4>Q8</h4><center>$xatc</th>";
			echo "<th bgcolor='#006600' width='100'><h4>Q9</h4><center>$xatd</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<th bgcolor='#006600' width='100' ><h4>Q4</h4><center>$xces</th>";
			echo "<th bgcolor='#006600' width='100' ><h4>Q5</h4><center>$xcec</th>"; 
			echo "<th bgcolor='#006600' width='100' ><h4>Q6</h4><center>$xced</th>";
			echo "</tr>";
			
			echo "<tr>";
			echo "<th bgcolor='#006600' width='100'><h4>Q1</h4><center>$xdfs</th>";
			echo "<th bgcolor='#006600' width='100'><h4>Q2</h4><center>$xdfc</th>";
			echo "<th bgcolor='#006600' width='100'><h4>Q3</h4><center>$xdfd</th>";
			echo "</tr>";
			
			echo "<tr>";
			echo "<td width='100'></td>";
			echo "<th bgcolor='#006600' width='100'><h4>Q0</h4><center>$xpor</th>";
			echo "<td width='100'></td>";
			echo "</tr>";
			
			echo "</table>";
			echo "</span>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}else{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}

	
	if ($a == 10 or $a == 11 or $a == 12 or $a == 13)
	{
		$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");
		if (!$result)
		{
			echo 'Errore nella query: ' . mysql_error();
			exit();
		}
	
		$totale = mysql_num_rows($result);
		if ($totale != 0)
		{
			$conta = 0;
			while ($rig = mysql_fetch_array($result))
			{
				$nome[] = $rig['nome'];
				$wskill[] = $rig['skill'];
				$wtc[] = $rig['tc'];
				$wtr[] = $rig['tr'];
				$wcr[] = $rig['cr'];
				$wtalento[] = $rig['talento'];
				$wqta[] = $rig['qta'];
				$wcarattere[] = $rig['carattere'];
				$wes[] = $rig['esp'];
				
				switch ($a)
				{
					case 10:	// calcia rigori
						$quale = "rigorista";
						switch ($wcarattere[$conta])
						{
							case "razionale":
								$conteggio_rigori = ($wskill[$conta]+$wtc[$conta]*1.3+$wtr[$conta])+20;
								break;
							case "emotivo":
								$conteggio_rigori = ($wskill[$conta]+$wtc[$conta]+$wtr[$conta])-20;		
								break;
							default:
								$conteggio_rigori = ($wskill[$conta]+$wtc[$conta]*1.2+$wtr[$conta]);			
						}
						if ($wtalento[$conta] == "Rigori")
						{
							$conteggio_rigori = $conteggio_rigori * (2*$wqta[$conta]);		
						}
						$rigori[] = $conteggio_rigori;
						break;
					
					case 11: 	// calcia punizioni
						$quale = "calciatore di punizioni";
						if ($wtalento[$conta] == "Calci di punizione")
						{
							$conteggio_punizioni = ($wtc[$conta]*1.3+$wtr[$conta])* (2*$wqta[$conta]);
						}
						else
						{
							$conteggio_punizioni = ($wtc[$conta]*1.3+$wtr[$conta]);
						}
						$punizioni[] = $conteggio_punizioni;
						break;
					
					case 12:	// calcia angoli
						$quale = "battitore di calci d'angolo";
						if ($wtalento[$conta] == "Calcio d´angolo")
						{	
							$conteggio_angoli = ($wtc[$conta]*1.3+$wcr[$conta])*(2*$wqta[$conta]);
						}
						else
						{
							$conteggio_angoli = ($wtc[$conta]*1.3+$wcr[$conta]);
						}
						$angoli[] = $conteggio_angoli;
						break;
					
					case 13:	// capitano
						$quale = "capitano";
						switch ($wcarattere[$conta])
						{
							case "carismatico":
								$conteggio_capitano = ($wskill[$conta])*25;
								break;
							case "popolare":
								$conteggio_capitano = ($wskill[$conta])*10;
								break;
							case "introverso":
								$conteggio_capitano = ($wskill[$conta]);
								break;
							default:
								$conteggio_capitano = ($wskill[$conta])*5;
						}
						$conteggio_capitano = $conteggio_capitano + round(($wes[$conta]/100*15),1);	
						$capitani[] = $conteggio_capitano;
						break;
				}	// end switch
				$conta ++;	
			} //end while
			
			// LISTA ORDINATA PER I MIGLIORI 
			switch ($a)
			{
				case 10:
					$wrigori = array($rigori,$nome);
					array_multisort($wrigori[0], SORT_DESC, $wrigori[1], SORT_DESC);
					break;
				case 11:
					$wpunizioni = array($punizioni,$nome);
					array_multisort($wpunizioni[0], SORT_DESC, $wpunizioni[1], SORT_DESC);
					break;
				case 12:
					$wangoli = array($angoli,$nome);
					array_multisort($wangoli[0], SORT_DESC, $wangoli[1], SORT_DESC);
					break;
				case 13:
					$wcapitani = array($capitani,$nome);
					array_multisort($wcapitani[0], SORT_DESC, $wcapitani[1], SORT_DESC);
					break;
			}
			echo "<div id='MsgPausa'>";
			echo "<span  id='s_rosa' style='font-weight:bold;'>";
			
			echo "<table border='0' cellpadding='0' cellspacing='0' >";
			echo "<th width='50' height='30' align='left'>Pos.</th>
				  <th width='200' height='30' align='left'>Nome</th>
				  <th width='20' height='30' align='left'>Valore</th>
				  ";
			echo "<tr>
					<td width=100% colspan='3'><hr></td>
				  </tr>";
		
				for ($ind=0; $ind < $totale; $ind++){
					$num = 1 + $ind;
					switch ($a)
					{
						case 10:
							$wnome = $wrigori[1][$ind];
							$wskill = $wrigori[0][$ind];
							break;
						case 11:
							$wnome = $wpunizioni[1][$ind];
							$wskill = $wpunizioni[0][$ind];
							break;
						case 12:
							$wnome = $wangoli[1][$ind];
							$wskill = $wangoli[0][$ind];
							break;
						case 13:
							$wnome = $wcapitani[1][$ind];
							$wskill = $wcapitani[0][$ind];
							break;
					}
					echo "<tr>
							<td>$num&deg;</td>
							<td>$wnome</td>
							<td><center>$wskill</td>
						  </tr>";
				}
			echo "</table>";
			echo "</span>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
		else
		{
			echo "<div id='MsgPausa'>";
			echo "<legend>Statistiche</legend>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}	
	
	if ($a == 14)
	{
		$result = mysql_query("SELECT * FROM allena_tattiche WHERE a_id_team=\"$nome_team\"");
		if (!$result)
		{
			echo 'Errore nella query: ' . mysql_error();
			exit();
		}
		$rig = mysql_fetch_array($result) ;
		$totale = mysql_num_rows($result);
		if ($totale != 0)
		{
			// CREO ELENCO DELLE TATTICHE E MARCATURE
			$tattica_result = mysql_query("SELECT * FROM bonus_tattica WHERE 1 ORDER BY t_id");
			if (!$tattica_result) {
		    echo 'Errore nella query bonus tattica: ' . mysql_error();
		    exit();
			}
			$conta = 0;
			while   ($row   =   mysql_fetch_array($tattica_result))
			{
				$ListaTattica[$conta] = $row['t_descrizione'];
				$conta++;
			}
			$aggiorna[] = -10;
			$aggiorna[] = $rig['ta_press_val'];
			$aggiorna[] = $rig['ta_contr_val'];
			$aggiorna[] = $rig['ta_poss_val'];
			$aggiorna[] = $rig['ta_pall_val'];
			$aggiorna[] = $rig['ta_gioc_val'];
			$aggiorna[] = $rig['ta_cate_val'];
			$aggiorna[] = -10;
			$aggiorna[] = -10;
			
			// LISTA ORDINATA PER I MIGLIORI 
			$a_tattica = array($aggiorna,$ListaTattica);
			array_multisort($a_tattica[0], SORT_DESC, $a_tattica[1], SORT_DESC);
			
			echo "<div id='MsgPausa'>";
			echo "<span id='s_rosa'>";
			
			echo "<table border='0' cellpadding='0' cellspacing='2' >";
			echo "<th width='20' height='30'>Pos.</th>
				  <th width='200' height='30' align='left'>Tattica</th>
				  <th width='40' height='30'>Valore</th>
				  ";
			echo "<tr>
					<td width=100% colspan='3'><hr></td>
				  </tr>";
		
				for ($ind=0; $ind < 6; $ind++)
				{
					$num = 1 + $ind;
					$wnome = $a_tattica[1][$ind];
					$wskill = round($a_tattica[0][$ind]/327*100,1);
					
					echo "<tr>
							<th>$num&deg;</th>
							<th align='left'>$wnome</th>
							<th><center>$wskill %</th>
						  </tr>";
				}
			echo "</table>";
			echo "</span>";
			echo "<script>";
		echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
		echo "</script>";
		echo "</div>";
		} else {
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}
	
	if ($a == 41) // statistiche staff
	{
		$result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ORDER BY s_id_staff");
		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0)
		{
			while ($row = mysql_fetch_array($result))
			{
				$nome[] = $row['s_descrizione'];
				$id[] = $row['s_id_staff'];
				$abi[] = $row['s_abi'];
				$mot[] = $row['s_mot'];
				$esp[] = $row['s_esp'];
				$sti[] = $row['s_sti'];
				$car[] = $row['s_car'];
				$fil[] = $row['s_fil'];
			}
			// TOTALE GIOCATORI
			$totstaff = mysql_num_rows($result);
					
			echo "<div id='MsgPausa'>";
			echo "<div  id='s_listagiocatori'>";
			
			echo "<table  width='100%' border='0'";
			echo "<tr>";
			echo "	  <th width='180' align='left'>Nome</th>";
			echo "    <th width='25'>Abi.</th>";
			echo "    <th width='25'>Esp.</th>";
			echo "    <th width='25'>Mot.</th>";
			echo "    <th width='25'>Stip.</th>";
			echo "    <th width='130' align='left'>Caratt.</th>";
			echo "    <th width='130' align='left'>Filos.</th>";
			echo "    <th width='25'>Effic.</th>";
			echo "</tr>";
			
			$conta = 0;
			foreach ($nome as $elenco)
			{
				echo "<tr>";
				echo "<td><a class='a1' href='storico_staff.php?scelta=$id[$conta]' target='grafico_staff' >$elenco</a></td>";
				echo "<td>$abi[$conta]</td>";
				echo "<td>$esp[$conta]</td>";
				echo "<td>$mot[$conta]</td>";
				$efficienza = (0.9 * $abi[$conta] * $mot[$conta]) / 100 + $esp[$conta]/8;
				$stipendio = intval(round(number_format($sti[$conta],0,",","."),1));
				echo "<td>$stipendio</td>";
				echo "<td>$car[$conta]</td>";
				echo "<td>$fil[$conta]</td>";
				echo "<td>$efficienza</td>";
				echo "</tr>";
				$conta ++;
			}
			echo "</table>";
			echo "</div>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
			
			/*
			echo "</div>";
			echo "<div id='div_stat_grafici' style='display:  ; >";
			echo "	<span class='top-label'>";
			echo "		<span class='label-txt'>Grafici Staff</span>";
			echo "	</span>";
				
			echo "	<div class='content-area'>";
			echo "		<img id='s_staff_grafici' src='images/quadrato_rounded_chiaro.png' width='100%' height='100%' />";
			echo "			<span id='s_staff_grafici'>";
			echo "				<fieldset id='s_staff_grafici'>";
			echo "					<iframe src='staff_storico.php' name='stat_grafico' width='100%' marginwidth='0' align='top' allowtransparency='1' frameborder='0' scrolling='no'>";
			echo "					</iframe>";
			echo "				</fieldset>";
			echo "			</span>";
			echo "	</div>";
			echo "</div>";	*/		
		}
		else
		{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}	
	
	if ($a == 40) // statistiche giocatori
	{
		$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");

		$im_tal = array("t_nessuno.png","t_colpoditesta.png","t_rigori.png","t_calciodangolo.png","t_calcidipunizione.png","t_dribbling.png","t_velocita.png","t_resistenza.png","t_potenzadeltiro.png","t_cross.png","t_creativita.png","t_fiutodelgoal.png","t_pararigori.png","t_riflessifelini.png","t_visionedigioco.png");

		$im_piede = array("p_destro.png","p_sinistro.png","p_ambidestro.png");
		
		$qry = "SELECT * FROM giocatori as g, ruoli as r WHERE g.id_team=\"$nome_team\" and g.pos=r.ruolo_desc ORDER BY r.ruolo_order";
	
		$result = mysql_query($qry);
		
		if (!$result) 
		{
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0)
		{
			while ($row = mysql_fetch_array($result))
			{
				$id[] = $row['id'];
				$nr[] = $row['nr'];
				$nome[] = $row['nome'];
				$skill[] = $row['skill'];
				$pos[] = $row['pos'];
				$po[] = $row['po'];
				$df[] = $row['df'];
				$cn[] = $row['cn'];
				$pa[] = $row['pa'];
				$rg[] = $row['rg'];
				$cr[] = $row['cr'];
				$tc[] = $row['tc'];
				$tr[] = $row['tr'];
				$pd[] = $row['piede'];
				$tal[] = $row['talento'];
				$qta[] = $row['qta'];
			}
			// TOTALE GIOCATORI
			$totgiocatori = mysql_num_rows($result);
					
			echo "<div id='MsgPausa'>";
			echo "<div  id='s_listagiocatori'>";
			
			echo "<table  width='100%' border='0'";
			echo "<tr>";
			echo "	  <th width='22'>Nr</th>";
			echo "	  <th width='180' align='left'>Nome</th>";
			echo "    <th width='30'>Skill</th>";
			echo "    <th width='25'>Pos</th>";
			echo "    <th width='25'>PO</th>";
			echo "    <th width='25'>DF</th>";
			echo "    <th width='25'>CN</th>";
			echo "    <th width='25'>PA</th>";
			echo "    <th width='25'>RG</th>";
			echo "    <th width='25'>CR</th>";
			echo "    <th width='25'>TC</th>";
			echo "    <th width='25'>TR</th>";
			echo "    <th width='25'>Pd.</th>";
			echo "    <th width='65' align='left'>Talento</th>";
			echo "</tr>";
			
			$conta = 0;
			foreach ($nome as $elenco)
			{
				switch ($pos[$conta])
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
	
				switch ($tal[$conta])
				{
					case "Nessuno":
						$immtal = $im_tal[0];
						break;
					case "Colpo di testa":
						$immtal = $im_tal[1];
						break;
					case "Rigori":
						$immtal = $im_tal[2];
						break;
					case "Calcio d&acute;angolo":
						$immtal = $im_tal[3];
						break;
					case "Calci di punizione":
						$immtal = $im_tal[4];
						break;
					case "Dribbling":
						$immtal = $im_tal[5];
						break;
					case "Velocità":
						$immtal = $im_tal[6];
						break;
					case "Resistenza":
						$immtal = $im_tal[7];
						break;
					case "Potenza del tiro":
						$immtal = $im_tal[8];
						break;
					case "Cross":
						$immtal = $im_tal[9];
						break;
					case "Creatività":
						$immtal = $im_tal[10];
						break;
					case "Fiuto del goal":
						$immtal = $im_tal[11];
						break;
					case "Pararigori":
						$immtal = $im_tal[12];
						break;
					case "Riflessi felini":
						$immtal = $im_tal[13];
						break;
					case "Visione di gioco":
						$immtal = $im_tal[14];
						break;
					default:
						$immtal = $im_tal[0];
				}
				$immtal = "images/".$immtal;
		
				switch ($pd[$conta])
				{
					case "R":
						$immpiede = $im_piede[0];
						break;
					case "L":
						$immpiede = $im_piede[1];
						break;
					case "LR":
						$immpiede = $im_piede[2];
						break;
					default:
						$immpiede = $im_piede[0];
				}
				$immpiede = "images/".$immpiede;
						
				echo "<tr>";
				echo "<td align='right'><b>$nr[$conta]&nbsp;</b></td>";
				//echo "<td><a class='a1' href='giocatori_storico.php?scelta=$id[$conta]' target='stat_giocatori'>$elenco</a></td>";
				echo "<td><a class='a1' href='#' onClick =\"winpiccola('storico_giocatori.php?scelta=$id[$conta]','$elenco','width=610,height=350')\">$elenco</a></td>";
					
				
				//echo "<td><a class='a1' href='#' target='stat_giocatori'>$elenco</a></td>";
				
				echo "<td width=45>$skill[$conta]</td>";
				echo "<td><center><img src=$immpos width='18' heigth='16'  border='0'/></td>";
				echo "<td width=39>$po[$conta]</td>";
				echo "<td width=39>$df[$conta]</td>";
				echo "<td width=39>$cn[$conta]</td>";
				echo "<td width=39>$pa[$conta]</td>";
				echo "<td width=39>$rg[$conta]</td>";
				echo "<td width=39>$cr[$conta]</td>";
				echo "<td width=39>$tc[$conta]</td>";
				echo "<td width=39>$tr[$conta]</td>";
				echo "<td><img src=$immpiede width='18' heigth='16' border='0'/></td>";
				echo "<td>";
				for ($qw=1; $qw <=$qta[$conta]; $qw++)
				{
					echo "<img src=$immtal  border='0' title='$row[talento]'/>";
				}
				echo "</td>";		
				echo "</tr>";
				$conta ++;
			}
			echo "</table>";
			echo "</div>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
			/*echo "<div id='s_grafico_giocatori' style='display: ;'>";
			echo "ciao";
			echo "<iframe src='giocatori_storico.php' name='stat_giocatori' width='100%' marginwidth='0' height='600' align='top' allowtransparency='1' frameborder='0' scrolling='no' >";
			echo "</iframe>";
			echo "</div>";*/
		}
		else
		{
			echo "<div id='MsgPausa'>";
			echo "<h6><br>Non esistono dati su cui effettuare statistiche.<br></h6>";
			echo "<script>";
			echo "$('#MsgPausa').slideUp(0).delay(300).fadeIn(600);";
			echo "</script>";
			echo "</div>";
		}
	}	
		
?>
</body>
</html>