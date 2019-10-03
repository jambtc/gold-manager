<?php
	require_once('auth.php');
	include("connect_db.php");	

	echo "<h1>Creazione Calendario</h1>";
	echo "<div id='ticker_svolgimento'>";

	//CARICO LA CONFIGURAZIONE
	$qry = "SELECT * FROM  zz_config WHERE id=1";
	
	$result = mysql_query($qry);
	if (!$result)
	{
   		echo 'Errore nella query: ' . mysql_error();
   		exit();
	}
	$row   =   mysql_fetch_array($result);
		
	$config_data = $row['data'];
	$config_giorno = $row['giorno'];
	$config_orario = $row['orario'];
	$config_squadre= $row['squadre'];
	
	// CARICO GLI ISCRITTI DI TUTTE LE SERIE
	$qry = "SELECT * FROM z_iscritti ORDER BY serie";
	$result = mysql_query($qry);
	$quale_serie = array();
	while ($row = mysql_fetch_array($result))
	{
		$isc_squadre[]= $row['squadra'];
		$isc_serie[] = $row['serie'];
		//$isc_cpu[] = $row['cpu'];
		if (!in_array($row['serie'],$quale_serie))
		{
			$quale_serie[] = $row['serie'];
		}
	}
	
	// CREO IL CALENDARIO SOLO DELLE SERIE COMPLETE
	foreach ($quale_serie as $tipo)
	{
		$x = 0;
		$y = 1;
		foreach ($isc_squadre as $msg)
		{
			if ($isc_serie[$x] == $tipo)
			{
				$y ++;	
			
				if (($y-1) == $config_squadre)
				{
					unset($squadre);
					unset($casa);
					unset($trasferta);
					unset($ritorno);
					
					echo "<br><h1>Calendario della serie: ".$tipo."</h1><br>";
					mysql_query("DELETE FROM z_calendario WHERE serie='$tipo'");
					mysql_query("DELETE FROM z_classifica WHERE serie='$tipo'");

					$qry = "SELECT * FROM z_iscritti WHERE serie='$tipo'";
					$result = mysql_query($qry);
					while ($row = mysql_fetch_array($result))
					{
						$squadre[]= $row['squadra'];
						//CREO CLASSIFICA CON DATI A ZERO
						$qry_cl = "INSERT INTO z_classifica (serie,team) VALUES (\"$tipo\",\"$row[squadra]\")";
						@mysql_query($qry_cl);
					}
					shuffle($squadre);
					
					$oraInizio = $config_orario; // ora inizio delle partite
					$AvvioCampionato = $config_data; // il campionato inizia il 27 novembre 2010
					$dayGioco = $config_giorno; // si gioca il sabato
					$secondi_al_giorno = 86400; //ci sono 86400 secondi in un giorno
	
					$data_inizio = strtotime($AvvioCampionato);
						
					$g1 = date("j",$data_inizio);
					$m1 = date("m",$data_inizio);
					$a1 = date("Y",$data_inizio);
					$dataIta = $g1."/".$m1."/".$a1;
					$dataSql = $a1."-".$m1."-".$g1;
				
					$numero_squadre = count($squadre);
					$giornate = $numero_squadre - 1;
					
					/* crea gli array per le due liste in casa e fuori */
					for ($i = 0; $i < $numero_squadre /2; $i++)
					{
						$casa[$i] = $squadre[$i]; 
						$trasferta[$i] = $squadre[$numero_squadre - 1 - $i]; 
					}
					
					for ($i = 0; $i < $giornate; $i++)
					{
						echo "<br>Giornata ",$i+1," del ".$dataIta."<br>";
						/* alterna le partite in casa e fuori */
						if ($i % 2 == 0) 
						{
							for ($j = 0; $j < $numero_squadre /2 ; $j++)
							{
								echo $j+1," ".$trasferta[$j]." - ".$casa[$j]."<br>"; 
								$qry = "INSERT INTO z_calendario (serie,data,ora_inizio,fuori,casa) VALUES (\"$tipo\",'$dataSql','$oraInizio',\"$casa[$j]\",\"$trasferta[$j]\")";
								@mysql_query($qry);
								//Inserisco in array in modo da poter sviluppare il RITORNO
								$ritorno[] = $tipo.";".$oraInizio.";".$casa[$j].";".$trasferta[$j];
							}
						}
						else 
						{
							for ($j = 0; $j < $numero_squadre /2 ; $j++) 
							{
								 echo $j+1," ".$casa[$j]." - ".$trasferta[$j]."<br>"; 
								 $qry = "INSERT INTO z_calendario (serie,data,ora_inizio,fuori,casa) VALUES (\"$tipo\",'$dataSql','$oraInizio',\"$trasferta[$j]\",\"$casa[$j]\")";
								 @mysql_query($qry);
								 //Inserisco in array in modo da poter sviluppare il RITORNO
								$ritorno[] = $tipo.";".$oraInizio.";".$trasferta[$j].";".$casa[$j];
							}
						}
					  // Ruota gli elementi delle liste, tenendo fisso il primo elemento
						// Salva l'elemento fisso
						$pivot = $casa[0];
				 
						/* sposta in avanti gli elementi di "trasferta" inserendo 
						   all'inizio l'elemento casa[1] e salva l'elemento uscente in "riporto" */
						array_unshift($trasferta,$casa[1]);
						$riporto = array_pop($trasferta);
				 
						/* sposta a sinistra gli elementi di "casa" inserendo all'ultimo 
						   posto l'elemento "riporto" */
						$casa[] = $riporto;
						array_shift($casa);
					  
						// ripristina l'elemento fisso
						$casa[0] = $pivot ;
						
						$nextdate  = mktime (0,0,0,$m1,  $g1+7,  $a1); 
						
						$data_inizio = $nextdate;
						
						$g1 = date("j",$data_inizio);
						$m1 = date("m",$data_inizio);
						$a1 = date("Y",$data_inizio);
						
						$dataIta = $g1."/".$m1."/".$a1;
						$dataSql = $a1."-".$m1."-".$g1;
					} 
					//CREO LE PARTITE DI RITORNO
					for ($y=0; $y<count($ritorno); $y++)
					{
						echo "<br>Giornata ",$i+1," del ".$dataIta."<br>";
						
						for ($j = 0; $j < $numero_squadre /2 ; $j++) 
						{
							$riga = $ritorno[$y+$j];
							$elementi = explode(";",$riga);
						
							$serie = $elementi[0];
							$data = $dataSql;
							$oraInizio = $elementi[1];
							$squadra1 = $elementi[2];
							$squadra2 = $elementi[3];
							
							echo $j+1," ".$squadra1." - ".$squadra2."<br>"; 
							$qry = "INSERT INTO z_calendario (serie,data,ora_inizio,casa,fuori) VALUES (\"$tipo\",'$dataSql','$oraInizio',\"$squadra1\",\"$squadra2\")";
							@mysql_query($qry);	
						}
						$y = $y+$j-1;
						$nextdate  = mktime (0,0,0,$m1,  $g1+7,  $a1); 
						$data_inizio = $nextdate;
						
						$g1 = date("j",$data_inizio);
						$m1 = date("m",$data_inizio);
						$a1 = date("Y",$data_inizio);
						
						$dataIta = $g1."/".$m1."/".$a1;
						$dataSql = $a1."-".$m1."-".$g1;
						
						$i++;
					}
				}
			}
			$x ++;
		}
	}
	
	echo "</div>";
?>
</body>
</html>
