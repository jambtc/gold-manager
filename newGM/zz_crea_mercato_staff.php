<?php
	require_once('auth.php');
	include "connect_db.php";	
	

	//Funzione di inserimento dati nella tabella staff_mercato
	function inserisci($id,$team,$scadenza,$id_staff,$descrizione,$nome,$abi,$esp,$mot,$sti,$car,$fil,
				$contr,$for,$con,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$tal)
	{
		$qry = "INSERT INTO staff_mercato (s_id,s_id_team, s_scadenza,s_id_staff,s_descrizione,s_nome,
				s_abi,s_esp,s_mot,s_sti,s_car,s_fil,s_contrattazioni,
				s_for,s_con,s_po,s_df,s_cn,s_pa,s_rg,s_cr,s_tc,s_tr,s_tal) 			
				VALUES 
				('$id',\"$team\",'$scadenza','$id_staff',\"$descrizione\",\"$nome\",
				'$abi','$esp','$mot','$sti',\"$car\",\"$fil\",
				'$contr','$for','$con','$po','$df','$cn','$pa','$rg','$cr','$tc','$tr','$tal')";
				
		$result = mysql_query($qry);
		if (!$result)
		{
			echo 'Errore nella fase di creazione Mercato STAFF: ' . mysql_error();
			echo '<br>la query è:<br>'.$qry;
		    exit();
		}
		echo $id.") ".$team." ".$scadenza." ".$id_staff." ".$descrizione." ".$nome." ".$abi." ".$esp." ".$mot." ".$sti." ".$car." ".$fil." ".$contr." ".$for." ".$con." ".$po." ".$df." ".$cn." ".$pa." ".$rg." ".$cr." ".$tc." ".$tr." ".$tal."<br>";
	}
	// FINE FUNZIONE
	
	
		
	echo "<h1>Creazione MERCATO STAFF</h1>";
	echo "<div id='ticker_svolgimento'>";
	

	/*//CARICO BONUS STAFF
	$qry = "SELECT * FROM  bonus_staff WHERE 1";
	$result = mysql_query($qry);
	if (!$result)
	{
   		echo 'Errore nella query SELECT BONUS_STAFF: ' . mysql_error();
   		exit();
	}
	while ($row   =   mysql_fetch_array($result))
	{
		$bonus_staff_id[] = $row['id_allenatore'];
		$bonus_staff_forma[] = $row['forma'];
		$bonus_staff_cond[] = $row['cond'];
		$bonus_staff_po[] = $row['po'];
		$bonus_staff_df[] = $row['df'];
		$bonus_staff_cn[] = $row['cn'];
		$bonus_staff_pa[] = $row['pa'];
		$bonus_staff_rg[] = $row['rg'];
		$bonus_staff_cr[] = $row['cr'];
		$bonus_staff_tc[] = $row['tc'];
		$bonus_staff_tr[] = $row['tr'];
		$bonus_staff_tal[] = $row['talento'];
	}
	$ar_bonus_staff['forma'] = array_combine($bonus_staff_id,$bonus_staff_forma);
	$ar_bonus_staff['cond'] = array_combine($bonus_staff_id,$bonus_staff_cond);
	$ar_bonus_staff['po'] = array_combine($bonus_staff_id,$bonus_staff_po);
	$ar_bonus_staff['df'] = array_combine($bonus_staff_id,$bonus_staff_df);
	$ar_bonus_staff['cn'] = array_combine($bonus_staff_id,$bonus_staff_cn);
	$ar_bonus_staff['pa'] = array_combine($bonus_staff_id,$bonus_staff_pa);
	$ar_bonus_staff['rg'] = array_combine($bonus_staff_id,$bonus_staff_rg);
	$ar_bonus_staff['cr'] = array_combine($bonus_staff_id,$bonus_staff_cr);
	$ar_bonus_staff['tc'] = array_combine($bonus_staff_id,$bonus_staff_tc);
	$ar_bonus_staff['tr'] = array_combine($bonus_staff_id,$bonus_staff_tr);
	$ar_bonus_staff['tal'] = array_combine($bonus_staff_id,$bonus_staff_tal);
	
	*/
	
	//CARICO LISTA STAFF
	$qry = "SELECT * FROM  staff_lista WHERE 1";
	$result = mysql_query($qry);
	if (!$result)
	{
   		echo 'Errore nella query SELECT STAFF_LISTA: ' . mysql_error();
   		exit();
	}
	while ($row   =   mysql_fetch_array($result))
	{
		$staff_id[] = $row['staff_id'];
		$staff_lista[] = $row['staff_descrizione'];
	}
	$ar_lista_staff = array_combine($staff_id,$staff_lista);
	$staff_ultimo = count($staff_id);
	
	//CARICO FILOSOFIA
	$qry = "SELECT * FROM  bonus_filosofia WHERE 1";
	$result = mysql_query($qry);
	if (!$result)
	{
   		echo 'Errore nella query SELECT BONUS_FILOSOFIA: ' . mysql_error();
   		exit();
	}
	while ($row   =   mysql_fetch_array($result))
	{
		$lista_filosofia[] = $row['descrizione'];
	}
	$filosofia_ultimo = count($lista_filosofia)-1;
	
	//CARICO TABELLA CARATTERI 
	$qry = "SELECT * FROM caratteri WHERE id_carattere='allenatore'";
	$result = mysql_query($qry);
	if (!$result) 
	{
    	echo 'Errore nella query caratteri: ' . mysql_error();
	    exit();
	}
	while   ($row   =   mysql_fetch_array($result))
	{
		$lista_caratteri[] = $row['descrizione'];
	}
	$caratteri_ultimo = count($lista_caratteri)-1;
	
	// seleziono i nomi propri
	$qry_nomi = mysql_query("SELECT * FROM nomi");
	while ($row = mysql_fetch_array($qry_nomi))
	{
		$nomi_nome[] = $row['nome'];
	}
	$nomi_ultimo = count($nomi_nome)-1;

	//seleziono i cognomi di persona
	$qry_cognomi = mysql_query("SELECT * FROM cognomi");
	while ($row = mysql_fetch_array($qry_cognomi))
	{
		$cognomi_nome[] = $row['cognome'];
	}
	$cognomi_ultimo = count($cognomi_nome)-1;
	
	// CARICO LE SQUADRE CON CPU=1
	$qry = "SELECT * FROM z_iscritti WHERE cpu=1";
	$result = mysql_query($qry);
	while ($row = mysql_fetch_array($result))
	{
		$nome_squadra[] = $row['squadra'];
	}
	
	// conto l'id deLLO STAFF
	$qry_contatore = mysql_query("SELECT * FROM contatore WHERE pagina = '90222'");
	$row = mysql_fetch_array($qry_contatore);
	$id = $row['visite'] +1;
	
	// SELEZIONA LA DATA 
	$Oggi = time();
	$giorno = 60*60*24;
	
	//CREO 30 STAFF CASUALI X OGNI SQUADRA CON SCADENZA DA 10 A 30 GIORNI
	foreach ($nome_squadra as $squadra)
	{
		for ($x=0; $x <=29; $x++)
		{
			$random = mt_rand(10,30);
			$scadenza = $Oggi+$giorno*$random;
			
			$g1 = date("d",$scadenza);
			$m1 = date("m",$scadenza);
			$a1 = date("Y",$scadenza);
	
			$dataSql = $a1."-".$m1."-".$g1;
			
			$id_staff = mt_rand(1,$staff_ultimo);
			$descrizione = $ar_lista_staff[$id_staff];
			$nome = $nomi_nome[mt_rand(0,$nomi_ultimo)]." ".$cognomi_nome[mt_rand(0,$cognomi_ultimo)];
			$abi = mt_rand(2,15);
			$esp = mt_rand(2,20);
			$mot = mt_rand(60,99);
			$sti = mt_rand(500,10000);
			$car = $lista_caratteri[mt_rand(0,$caratteri_ultimo)];
			$fil = $lista_filosofia[mt_rand(0,$filosofia_ultimo)];
			$contr = 15;
			/*
			
			$for = mt_rand(1,$ar_bonus_staff['forma'][$id_staff]);
			$con = mt_rand(1,$ar_bonus_staff['cond'][$id_staff]);
			$po = mt_rand(1,$ar_bonus_staff['po'][$id_staff]);
			$df = mt_rand(1,$ar_bonus_staff['df'][$id_staff]);
			$cn = mt_rand(1,$ar_bonus_staff['cn'][$id_staff]);
			$pa = mt_rand(1,$ar_bonus_staff['pa'][$id_staff]);
			$rg = mt_rand(1,$ar_bonus_staff['rg'][$id_staff]);
			$cr = mt_rand(1,$ar_bonus_staff['cr'][$id_staff]);
			$tc = mt_rand(1,$ar_bonus_staff['tc'][$id_staff]);
			$tr = mt_rand(1,$ar_bonus_staff['tr'][$id_staff]);
			$tal = mt_rand(1,$ar_bonus_staff['tal'][$id_staff]);
			*/
			$for = mt_rand(1,100);
			$con = mt_rand(1,100);
			$po = mt_rand(1,100);
			$df = mt_rand(1,100);
			$cn = mt_rand(1,100);
			$pa = mt_rand(1,100);
			$rg = mt_rand(1,100);
			$cr = mt_rand(1,100);
			$tc = mt_rand(1,100);
			$tr = mt_rand(1,100);
			$tal = mt_rand(1,100);
			
			
			// richiamo la funzione di inserimento dati
			inserisci($id,$squadra,$dataSql,$id_staff,$descrizione,$nome,$abi,$esp,$mot,$sti,$car,$fil,$contr,$for,$con,$po,$df,$cn,$pa,$rg,$cr,$tc,$tr,$tal);
		
			$id ++;
		}
	}
	// AGGIORNO IL CONTATORE STAFF
	$result = mysql_query("UPDATE contatore SET visite = '$id'-1 WHERE pagina = '90222'");
	
	echo "</div>";
?>
</body>
</html>
