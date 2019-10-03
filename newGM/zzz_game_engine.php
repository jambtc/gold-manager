<?php
	require_once('auth.php');
?>
<title>Gold Manager - Motore di GIOCO</title>
<head>
<style type='text/css'> @import 'zzz_engine.css'; </style>
<script type="text/javascript">
function cronometro(n)
{
	conta = eval(n)-1;
	
	document.getElementById('contatore').innerHTML = conta;
			
    if (n > 1)
	{
		setTimeout("cronometro(conta)",1000); //aspetto x secondi (1000 = 1sec.) e richiamo la funzione//5000
	}
	else
	{
		window.location.href="zzz_game_engine.php";
	}
}
</script>
</head>
<body onLoad="javascript:cronometro(61);">
	<fieldset>
		<legend><h1 class="h1">Game Engine</h1></legend>
		<div>Prossimo Aggiornamento tra: <div id='contatore'></div></div>

		<?php 
		include "connect_db.php";	
		
		$giorni = array("Domenica","Lunedi","Martedi","Mercoledi","Giovedi","Venerdi","Sabato");
		
		// SELEZIONA L'ORARIO E LA DATA AL MOMENTO DELL'AGGIORNAMENTO DELLA PAGINE
		$Oggi = time();
		$g1 = date("d",$Oggi);
		$m1 = date("m",$Oggi);
		$a1 = date("Y",$Oggi);
		$hor = date("H",$Oggi);
		$min = date("i",$Oggi);
	
		$oraAdesso = $hor.":".$min;
	
		$dataIta = $g1."/".$m1."/".$a1;
		$separatore = "/";
		// data in formato sql  
		$split_data = explode($separatore, $dataIta);
		$dataSql = $split_data[2] . "-" . $split_data[1] . "-" . $split_data[0]; 
		
		// LEGGO LA CONFIGURAZIONE
		$qry = "SELECT * FROM  zz_config WHERE id=1";
		
		$result = mysql_query($qry);
		if (!$result)
		{
			echo 'Errore nella query: ' . mysql_error();
			exit();
		}
		$row   =   mysql_fetch_array($result);
		
		$dataAvvio = $row['data'];
		$giornoAvvio = $row['giorno'];
		$orarioAvvio = $row['orario'];
		$squadre_serie = $row['squadre'];
		$primavera_base = $row['prim_base'];
		$ultima_serie = $row['ultima_serie'];
		$giorno_allenamento = $row['giorno_allenamento'];
		
		echo "Adesso sono le ore: ".$oraAdesso." del giorno ".$dataIta; 
		echo "CONFIGURAZIONE: Inizio Campionato: ".$dataAvvio.", Giorno: ".$giorni[$giornoAvvio].", Orario inizio: ".$orarioAvvio.", Squadre per Serie: ".$squadre_serie.", Base Primavera €: ".$primavera_base.", Ultima Serie: ".$ultima_serie;
		?> 
	</fieldset>
	
	<fieldset>
		<legend><h1 class="h1">Game Status</h1></legend>
		<?php 
		$qry = "SELECT * FROM z_calendario WHERE data>='$dataSql' AND giocata=0";
		$qry = $qry." ORDER BY data";
	
		$result = mysql_query($qry);
		$conta = 0;
		
		while ($row = mysql_fetch_array($result))
		{
			$cal_id[] = $row['id_partita'];
			$cal_serie[] = $row['serie'];
			$cal_data[] = $row['data'];
			$cal_ora[] = $row['ora_inizio'];
			$cal_casa[] = $row['casa'];
			$cal_fuori[] = $row['fuori'];
			$conta ++;
		}
		// break è il numero dopo cui si deve fermare il conteggio delle prossime partite da giocare
		$break = $conta / (($squadre_serie-1)*2); 
		// visto che ho ordinato per data l'array [0] contiene la prima data utile pertanto è quella con cui si deve giocare la prossima partita!!!
		$data_prossima = $cal_data[0]; 
		?>
		Prossime Partite da Giocare (per un totale di n°<?php echo $break; ?>) il: <?php echo $data_prossima; ?>
	</fieldset>
	<fieldset>
		<legend><h1 class="h1">Game development</h1></legend>
		<fieldset>
		<legend>Disputa Partite</legend>
		<?php 
		// VERIFICA DEL GIOCO DELLE PARTITE
		if ($break == 0)
		{
			echo "LE PARTITE DI OGGI SONO GI&Aacute; STATE GIOCATE!!";
		}
		else
		{
			if ($dataSql == $data_prossima and $oraAdesso < $orarioAvvio)
			{
				echo "LE PARTITE SI GIOCANO OGGI ".$data_prossima;
				echo " e precisamente alle ore ".$orarioAvvio;
			}
			elseif ($dataSql == $data_prossima and $oraAdesso >= $orarioAvvio)
			{
				echo "EVVAI A GIOCARE LE PARTITE !!!!!!!!!!!!!";
			}
			else 
			{
				$mancano = round((strtotime($data_prossima) - strtotime($dataSql)) /60/60/24);
				echo "NON SI GIOCA OGGI. MANCANO ".$mancano." GIORNI ALL'INIZIO.";
			}
		
		}
		?>
		</fieldset>
		<fieldset>
		<legend>Allenamento Settimanale</legend>
		<?php 
		// VERIFICA DELL'ALLENAMENTO SETTIMANALE
		if (date("w",strtotime($dataSql)) == ($giorno_allenamento+1))
		{
			if ($oraAdesso > "00:00:01" and $oraAdesso < "01:00:00")
			{
				echo "CI STIAMO ALLENANDO...";
			}
			else
			{
				echo "ALLENAMENTO CONCLUSO";
			}
		}
		else
		{
			echo "OGGI NON CI ALLENIAMO.";
		}
		?>
		</fieldset>
		<fieldset>
		<legend>Allenamento Giornaliero</legend>
		<?php
		// VERIFICA ALLENAMENTO GIORNALIERO
		if ($oraAdesso < "01:00:00")
		{
			$festeggia = date("z",$Oggi)/365*120;
			echo "STIAMO FACENDO L'AGGIORNAMENTO GIORNALIERO...<br>Giorno dell'anno: ".date("z",$Oggi)."<br>Giorno dell'anno Scambiato: ".$festeggia;
						
			// SELEZIONO SOLO I FISIOTERAPISTI
			$qry = "SELECT * FROM staff WHERE s_id_staff=3";
			$result = mysql_query($qry);
			if (!$result)
			{
				echo 'Errore nella query di selezione fISIOTERAPISTI: ' . mysql_error();
				exit();
			}
			// CREO ARRAY PER CIASCUNA SQUADRA CON L'EFFICIENZA DELLO STAFF - FISIOTERAPISTA
			while ($row = mysql_fetch_array($result))
			{
				$fisio_team[] = $row['s_id_team'];
				$fisio_effic[] = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
				$fisio_addestra[] = $row['s_addestramento'];
			}
			$ar_fisioterapista = array_combine($fisio_team,$fisio_effic);
			$ar_addestramento = array_combine($fisio_team,$fisio_addestra);

			// SELEZIONO I GIOCATORI
			$qry = "SELECT * FROM giocatori WHERE data_allenamento < '$dataSql'";
			$result = mysql_query($qry);
			$totale = mysql_num_rows($result);
			if (!$result)
			{
				echo 'Errore nella query di selezione GIOCATORI: ' . mysql_error();
				exit();
			}
			// SE SONO STATI TROVATI GIOCATORI DA AGGIORNARE...
			// (questa modifica mi serve nel caso dovessi fare una sequenza di 2000 giocatori per volta ad esempio)
			
			if ($totale != 0)
			{
				while ($row = mysql_fetch_array($result))
				{
					$giocatore_id[] = $row['id'];
					$giocatore_fresc[] = $row['fresc'];
					$giocatore_team[] = $row['id_team'];
					$giocatore_nascita[] = strtotime($row['nascita']);
				}
				$ar_freschezza = array_combine($giocatore_id,$giocatore_fresc);
				$ar_compleanno = array_combine($giocatore_id,$giocatore_nascita);
				$ar_team = array_combine($giocatore_id,$giocatore_team);
							
				foreach ($giocatore_id as $xyz)
				{
					// CONVERTO LA DATA DI NASCITA DEL GIOCATORE IN UNA EQUAZIONE A 4 MESI (120 GIORNI)
					$compleanno = date("z",$ar_compleanno[$xyz])/365*120;
					
					// SE IL FISIOTERAPISTA ESISTE...
					if (in_array($ar_team[$xyz],$fisio_team))
					{
						$distrazione = 1; // distrazione da addestramento
						if ($ar_addestramento[$ar_team[$xyz]] < 15)
						{
							$distrazione = 0.93;
						}
						$freschezza =  $ar_freschezza[$xyz] + $ar_fisioterapista[$ar_team[$xyz]]/100*10*$distrazione;
					}
					else // ALTRIMENTI
					{
						$freschezza = $ar_freschezza[$xyz] +1 ;
					}
					$freschezza = round($freschezza,0);
					if ($freschezza > 100)	{ $freschezza = 100; }
	
					$qry = "UPDATE giocatori SET data_allenamento = '$dataSql', fresc = '$freschezza' ";
					
					if ($compleanno == $festeggia)
					{
						// COMPLEANNO DEL GIOCATORE
						$qry .= ", eta=eta+1 ";
					}
					$qry .= "WHERE id='$xyz'";
					
					$result = mysql_query($qry);
					if (!$result)
					{
						echo 'Errore nella query di UPDATE GIOCATORI: ' . mysql_error();
						exit();
					}
				}
				echo "<br>AGGIORNAMENTO CONCLUSO CON ESITO POSITIVO";
			}
			else
			{
				echo "<br>AGGIORNAMENTO GIORNALIERO GI&Aacute; CONCLUSO. IL PROSSIMO AGGIORNAMENTO AVVERR&Aacute; TRA ".("25:00:00"-$oraAdesso)." ORE";
			}
		}
		else
		{
			echo "IN ATTESA... IL PROSSIMO AGGIORNAMENTO AVVERR&Aacute; TRA ".("25:00:00"-$oraAdesso)." ORE";
		}
		?>
		</fieldset>
	
	
		<?php
		// VERIFICA AGGIORNAMENTO CALCIO MERCATO
		
		
		// VERIFICA AGGIORNAMENTO STAFF SUL MERCATO
		
		
		?>
	</fieldset>
</body>




