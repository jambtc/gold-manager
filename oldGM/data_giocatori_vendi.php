<?php
	require_once('auth.php');

	include "connect_db.php";	
	$id = $_REQUEST['vendi'];
	$nome_team = $_SESSION['SESS_TEAM'];
	$serie = $_SESSION['SESS_SERIE'];

	// carica prima i dati del giocatore che verr� immesso sul mercato...
	$result = mysqli_query($link, "SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND id=\"$id\"");
	if (!$result)
	{
    	echo 'Errore nella query giocatori: ' . mysqli_error();
	    exit();
	}
	$row   =   mysqli_fetch_array($result);
	
	$nome = $row['nome'];
	$eta = $row['eta'];
	$skill = $row['skill']; 
	$pos = $row['pos'];
	$forma = $row['forma'];
	$fresc = $row['fresc'];
	$cond = $row['cond'];
	$esp = $row['esp'];
	$po = $row['po'];
	$df = $row['df'];
	$cn = $row['cn'];
	$pa = $row['pa'];
	$rg = $row['rg'];
	$cr = $row['cr'];
	$tc = $row['tc'];
	$tr = $row['tr'];
	$piede = $row['piede'];
	$talento = $row['talento'];
	$qta = $row['qta'];
	$part = $row['part'];
	$reti = $row['reti'];
	$gialli = $row['gialli'];
	$rossi = $row['rossi'];
	$stipendio = $row['stipendio'];
	$valore = $row['valore'];
	$part2 = $row['part2'];
	$reti2 = $row['reti2'];
	$gialli2 = $row['gialli2'];
	$rossi2 = $row['rossi2'];
	$carattere = $row['carattere'];
	$infortunio = $row['infortunio'];
	$contratto = $row['contratto'];
	
	// inserisco il giocatore nel MERCATO senza indicare il contratto
	$qry = "INSERT INTO mercato (nome,eta,skill,pos,forma,fresc,cond,esp,
			po,df,cn,pa,rg,cr,tc,tr,piede,talento,qta,
			part,reti,gialli,rossi,
			stipendio,valore,part2,reti2,gialli2,rossi2,carattere,infortunio)
			VALUES (\"$nome\",'$eta','$skill','$pos','$forma','$fresc','$cond','$esp',
			'$po','$df','$cn','$pa','$rg','$cr','$tc','$tr','$piede','$talento','$qta',
			'$part','$reti','$gialli','$rossi','$stipendio','$valore',
			'$part2','$reti2','$gialli2','$rossi2',
			'$carattere','$infortunio')";
	
	$result = mysqli_query($link, $qry);
	if (!$result)
	{
		echo 'Errore nella query INSERT MERCATO: ' . mysqli_error();
		exit();
	}		
	
	//CANCELLO IL GIOCATORE DALLA SQUADRA
	$result = mysqli_query($link, "DELETE FROM giocatori WHERE id_team=\"$nome_team\" AND id='$id' LIMIT 1");
	if (!$result)
	{
    	echo 'Errore nella query delete giocatori: ' . mysqli_error();
	    exit();
	}
	
	// Elimino le statistiche
	$qry = "DELETE FROM stat_giocatori WHERE id_team = \"$nome_team\" AND id = \"$id\"";
	
	$result = mysqli_query($link, $qry);
	if (!$result)
	{
		echo 'Errore nella query STAT GIOCATORI: ' . mysqli_error();
		exit();
	}
		
	//AGGIORNO IL BUDGET DEL MANAGER
	$penale_contratto = $contratto * $valore /100;
	$penale_esperienza = (20-$esp) * $valore /100;
	
	$svaluta = $valore - $valore/100*10 - round($penale_contratto) - round($penale_esperienza);
	
	$qry = "UPDATE members SET budget=budget + $svaluta WHERE team = \"$nome_team\"";
	$result = mysqli_query($link, $qry);
	if (!$result)
	{
		echo 'Errore nella query Aggiorna Budget: ' . mysqli_error();
		exit();
	}
	
	// INSERISCO LA NOTIZIA NELLE NEWS... !!!!!!!!!!!!!1
	//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	$oggi = time();
		
	$g1 = date("j",$oggi);
	$m1 = date("m",$oggi);
	$a1 = date("Y",$oggi);
	$dataSql = $a1."-".$m1."-".$g1;
	
	$qry = "INSERT INTO notizie_serie (g_team,g_serie,g_data,g_titolo,g_testo) VALUES";
	$qry = $qry." (\"$nome_team\",'$serie','$dataSql','Calcio Mercato','\"$nome_team\" ha venduto \"$nome\"!')";
	$result = mysqli_query($link, $qry);
	if (!$result)
	{
		echo 'Errore nella query iNSERISCI News Serie: ' . mysqli_error();
		exit();
	}
	
	$denaro = number_format($svaluta,0,",",".");
	$qry = "INSERT INTO notiziario (team,data,notizia) VALUES";
	$qry = $qry." (\"$nome_team\",'$dataSql','Hai venduto \"$nome\" ricavando �. $denaro!')";
	$result = mysqli_query($link, $qry);
	if (!$result)
	{
		echo 'Errore nella query inserisci News squadra: ' . mysqli_error();
		exit();
	}	
	mysqli_close($link);
	
	//header("location: form_giocatori.php");
	echo "<script>window.location.href='form_giocatori.php';</script>";

?>
