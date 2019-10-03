<?php
	require_once('../auth.php');
	include "../connect_db.php";	

	$id = $_REQUEST['id'];
	$guadagno = $_REQUEST['guadagno'];
	$nome_team = $_SESSION['SESS_TEAM'];
	$serie = $_SESSION['SESS_SERIE'];

	// carica prima i dati del giocatore che verrà immesso sul mercato...
	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" AND id=\"$id\"");
	if (!$result)
	{
    	echo 'Errore nella query giocatori: ' . mysql_error();
	    exit();
	}
	$row   =   mysql_fetch_array($result);
	
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
	
	$result = mysql_query($qry);
	if (!$result)
	{
		echo 'Errore nella query INSERT MERCATO: ' . mysql_error();
		exit();
	}		
	
	//CANCELLO IL GIOCATORE DALLA SQUADRA
	$result = mysql_query("DELETE FROM giocatori WHERE id_team=\"$nome_team\" AND id='$id' LIMIT 1");
	if (!$result)
	{
    	echo 'Errore nella query delete giocatori: ' . mysql_error();
	    exit();
	}
	
	// Elimino le statistiche
	$qry = "DELETE FROM stat_giocatori WHERE id_team = \"$nome_team\" AND id = \"$id\"";
	
	$result = mysql_query($qry);
	if (!$result)
	{
		echo 'Errore nella query STAT GIOCATORI: ' . mysql_error();
		exit();
	}
		
	//AGGIORNO IL BUDGET DEL MANAGER
	
	
	$qry = "UPDATE members SET budget=budget + $guadagno WHERE team = \"$nome_team\"";
	$result = mysql_query($qry);
	if (!$result)
	{
		echo 'Errore nella query Aggiorna Budget: ' . mysql_error();
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
	$result = mysql_query($qry);
	if (!$result)
	{
		echo 'Errore nella query iNSERISCI News Serie: ' . mysql_error();
		exit();
	}
	
	$denaro = number_format($guadagno,0,",",".");
	$qry = "INSERT INTO notiziario (team,data,notizia) VALUES";
	$qry = $qry." (\"$nome_team\",'$dataSql','Hai venduto \"$nome\" ricavando €. $denaro!')";
	$result = mysql_query($qry);
	if (!$result)
	{
		echo 'Errore nella query inserisci News squadra: ' . mysql_error();
		exit();
	}	
?>
<div class='div_player'>	
	<span class="top-label">  
		<span class="label-txt">Comunicazione</span>
	</span> 
	<img class='img_player' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_player'>
		<fieldset>
			<h2><p align="center" style='color:#0000FF;'>Il tuo giocatore &egrave; stato venduto ed immesso sul mercato correttamente.</p></h2>
		</fieldset>
	</div>
</div>
<script>
		allarga_maschera('player',40,ValAlt('player'));
</script>	