<?php
	require_once('auth.php');
	include "connect_db.php";
?>
<head>
		<meta http-equiv="Content-Type" content="text/html; charset=" />
		<style type='text/css'> @import 'statistiche.css'; </style>
	
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>
	<script type="text/javascript" src="jquery/my_tooltipxxxxxxx.js"></script>
	<script type="text/javascript" src="jquery/my_script_team.js"></script>
</head>
<body style="background:trasparent; font-size:10px;"> 
<?php
	
	//	$nome_team = $_SESSION['SESS_TEAM'];
	
	/*
	$BonusFfc = "0"; 
	$checkFres = "";
	$checkForm = "";
	$checkCond = "";
	$ffcFres = 100;
	$ffcForm = 100;
	$ffcCond = 100;
	*/
	
	$formula = "Formula 2";

	//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY RIQUADRO CHE CONTERRA' IL NUMERO DI MAGLIA
	$appoggio= array('f_id_team','f_1','f_2','f_3','f_4','f_5','f_6','f_7','f_8','f_9','f_10',
							 'f_11','f_12','f_13','f_14','f_15','f_16','f_17','f_18','f_19','f_20',
							 'f_21','f_22','f_23','f_24','f_25','f_26','f_27','f_28','f_29','f_30',
							 'f_31','f_32','f_33','f_34','f_35','f_36','f_37','f_38','f_39','f_40',
							 'f_41','f_42','f_43','f_44','f_45','f_46','f_47','f_48','f_49','f_50',
							 'f_51','f_52','f_53','f_54','f_55','f_56','f_57','f_58','f_59','f_60',
							 'f_61','f_62','f_63','f_64','f_65','f_66','f_67','f_68','f_69','f_70',
							 'f_71','f_72','f_73',
							 'f_formazione');
	$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");
	
	$quale_ruolo[] = "(PO) Portiere";
	$quale_ruolo[] = "(DS) Terzino Sinistro";
	$quale_ruolo[]  = "(D) Difensore Centrale";
	$quale_ruolo[] = "(DD) Terzino Destro";
	$quale_ruolo[] = "(CS) Ala Sinistra";
	$quale_ruolo[]  = "(C) Centrocampista";
	$quale_ruolo[] = "(CD) Ala Destra";
	$quale_ruolo[] = "(AS) Attaccante Sinistro";
	$quale_ruolo[]  = "(A) Attaccante Centrale";
	$quale_ruolo[] = "(AD) Attaccante Destro"; 
	$quale_ruolo[] = "(XX) Jolly"; 

	$im_piede = array("p_destro.png","p_sinistro.png","p_ambidestro.png");

	/*
	// CARICO IL CHECK DEL BONUS FORMA, FRESCHEZZA E CONDIZIONE
	$tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\"");
	if (!$tipo_result)
	{
	    echo 'Errore nella query tattica: ' . mysql_error();
    	exit();
	}
	$row   =   mysql_fetch_array($tipo_result);
	$BonusFfc = $row['t_forma'];
	$QualeFormazione = $row['t_formazione'];
	if ($QualeFormazione == "")	{	$QualeFormazione = "Formazione 1"; 	}

	// controllo i checkbox ADESSO !!! dopo durante il while controllo i valori !!!!
	switch ($BonusFfc)
	{
		case 0:
			$checkFres = "";
			$checkForm = "";
			$checkCond = "";
			$BnFFC2 = 0;
			$BnFFC1 = 0;
			$BnFFC3 = 0;
			break;
		case 1:
			$checkFres = "";
			$checkForm = "";
			$checkCond = "checked";
			$BnFFC2 = 0;
			$BnFFC1 = 0;
			$BnFFC3 = 1;
			break;
		case 2:
			$checkFres = "";
			$checkForm = "checked";
			$checkCond = "";
			$BnFFC2 = 0;
			$BnFFC1 = 2;
			$BnFFC3 = 0;
			break;
		case 3:
			$checkFres = "";
			$checkForm = "checked";
			$checkCond = "checked";
			$BnFFC2 = 0;
			$BnFFC1 = 2;
			$BnFFC3 = 1;
			break;
		case 4:
			$checkFres = "checked";
			$checkForm = "";
			$checkCond = "";
			$BnFFC2 = 4;
			$BnFFC1 = 0;
			$BnFFC3 = 0;
			break;
		case 5:
			$checkFres = "checked";
			$checkForm = "";
			$checkCond = "checked";
			$BnFFC2 = 4;
			$BnFFC1 = 0;
			$BnFFC3 = 1;
			break;
		case 6:
			$checkFres = "checked";
			$checkForm = "checked";
			$checkCond = "";
			$BnFFC2 = 4;
			$BnFFC1 = 2;
			$BnFFC3 = 0;
			break;
		case 7:
			$checkFres = "checked";
			$checkForm = "checked";
			$checkCond = "checked";
			$BnFFC2 = 4;
			$BnFFC1 = 2;
			$BnFFC3 = 1;
			break;
	}
	*/
	
	$result = mysql_query("SELECT * FROM formazione WHERE f_id_team=\"$nome_team\" AND f_formazione=\"$QualeFormazione\"");
	if (!$result)
	{
		echo 'Errore nella query CREA ARRAY: ' . mysql_error();
		exit();
	}
	$row   =   mysql_fetch_array($result);
		
	$conta = 1;
	while ($conta < 74)
	{
		$riquadro[$conta] = $row[$appoggio[$conta]];
		$conta ++;	
	}


	// CALCOLO SULLE MAIN POSITION
	// CARICO I DATI PORTIERE DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='0' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore portiere: ' . mysql_error(); exit(); }
	$riga_po = mysql_fetch_array($calc_result);

	// CARICO I DATI DIFENSORE SINISTRO DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='10' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore difensore sinistro: ' . mysql_error(); exit(); }
	$riga_ds = mysql_fetch_array($calc_result);

	// CARICO I DATI DIFENSORE CENTRALE DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='20' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore difensore centrale: ' . mysql_error(); exit(); }
	$riga_dc = mysql_fetch_array($calc_result);

	// CARICO I DATI DIFENSORE DESTRO DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='30' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore difensore destro: ' . mysql_error(); exit(); }
	$riga_dd = mysql_fetch_array($calc_result);

	// CARICO I DATI CENTROCAMPISTA SINISTRO DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='40' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore CENTROCAMPISTA SINISTRO: ' . mysql_error(); exit(); }
	$riga_cs = mysql_fetch_array($calc_result);

	// CARICO I DATI CENTROCAMPISTA CENTRALE DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='50' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore CENTROCAMPISTA CENTRALE: ' . mysql_error(); exit(); }
	$riga_cc = mysql_fetch_array($calc_result);

	// CARICO I DATI CENTROCAMPISTA DESTRO DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='60' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore CENTROCAMPISTA DESTRO: ' . mysql_error(); exit(); }
	$riga_cd = mysql_fetch_array($calc_result);

	// CARICO I DATI ATTACCANTE SINISTRO DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='70' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore ATTACCANTE SINISTRO: ' . mysql_error(); exit(); }
	$riga_as = mysql_fetch_array($calc_result);

	// CARICO I DATI ATTACCANTE CENTRALE DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='80' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore ATTACCANTE CENTRALE: ' . mysql_error(); exit(); }
	$riga_ac = mysql_fetch_array($calc_result);

	// CARICO I DATI ATTACCANTE destro DAL CALCOLATORE
	$calc_result = mysql_query("SELECT * FROM calcolatore WHERE qu='90' AND formula=\"$formula\"");
	if (!$calc_result) { echo 'Errore nella query calcolatore ATTACCANTE DESTRO: ' . mysql_error(); exit(); }
	$riga_ad = mysql_fetch_array($calc_result);

	//VISUALIZZA I GIOCATORI
	$result = mysql_query("SELECT * FROM giocatori as g, ruoli as r WHERE g.id_team=\"$nome_team\" and g.pos=r.ruolo_desc order by r.ruolo_order");
	if (!$result) {
    	echo 'Errore nella query SELEZIONA GIOCATORI: ' . mysql_error();
	    exit();
	}
?>


<!-- 
<fieldset style="width:98%;">
	<table width="100%"  border='0' cellpadding='0' cellspacing='0' style="color:#0000FF; font-weight:bold;">
	<tr>
	<?php 
		echo "<td align='center' width=33>
			<div class='_ffc1'>
				<input id='ws_ffc1' name='ws_ffc1' type='checkbox' $checkForm value='$BnFFC1' onclick='javascript:ffc(1);' class='fld_y'/></div>Forma
			</td>
			<td align='center' width=33>
			<div class='_ffc2'>
				<input id='ws_ffc2' name='ws_ffc2' type='checkbox' $checkFres value='$BnFFC2' onclick='javascript:ffc(2);' class='fld_y'/></div>Freschezza
			</td>
			<td align='center' width=33>
			<div class='_ffc3'>
				<input id='ws_ffc3' name='ws_ffc3' type='checkbox' $checkCond value='$BnFFC3' onclick='javascript:ffc(3);' class='fld_y'/></div>Condizione
			</td>
			";
	?>
	</tr>
	</table>
</fieldset>
-->


<table width="99%" border="0" style="color:#000000; font-size:12px;">
<tr>
	<td width='10'>&nbsp;</td>
	<th width='10' align="right">N.</th>
	<th width='100' align='left'>Nome</th>
	<th width='25'>Skill</th>
	<th width='20'>Pos</th>
	<th width='16'>Pd.</th>
	<th width='16'><img src='images/<?php echo $im_pos[0] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[0] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[1] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[1] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[2] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[2] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[3] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[3] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[4] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[4] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[5] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[5] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[6] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[6] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[7] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[7] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[8] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[8] ?>'></th>
	<th width='16'><img src='images/<?php echo $im_pos[9] ?>' width='16' heigth='16' title='<?php echo $quale_ruolo[9] ?>'></th>
</tr>


<?php
$counter = 0; 
while   ($row = mysql_fetch_array($result))
{
	switch ($row['pos'])
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
	$imm = "images/".$immpos;

	switch ($row['piede'])
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
	
	if ($row['infortunio'] == 1)
	{
		$check[$counter] = "checked";
		$infortunio[$counter] = 1;
	}
	else
	{
		$check[$counter] = "";
		$infortunio[$counter] = 0;
	}

	//ASSOCIAZIONE FATTORE FORMA, FRESCHEZZA E CONDIZIONE -> VALORE MEDIO
	switch ($BonusFfc)
	{
		case 0:
			$ffcFres = 100;
			$ffcForm = 100;
			$ffcCond = 100;
			break;
		case 1:
			$ffcFres = 100;
			$ffcForm = 100;
			$ffcCond = $row['cond'] + 20;
			break;
		case 2:
			$ffcFres = 100;
			$ffcForm = $row['forma'] + 20;
			$ffcCond = 100;
			break;
		case 3:
			$ffcFres = 100;
			$ffcForm = $row['forma'] + 20;
			$ffcCond = $row['cond'] + 20;
			break;
		case 4:
			$ffcFres = $row['fresc'] + 20;

			$ffcForm = 100;
			$ffcCond = 100;
			break;
		case 5:
			$ffcFres = $row['fresc'] + 20;
			$ffcForm = 100;
			$ffcCond = $row['cond'] + 20;
			break;
		case 6:
			$ffcFres = $row['fresc'] + 20;
			$ffcForm = $row['forma'] + 20;
			$ffcCond = 100;
			break;
		case 7:
			$ffcFres = $row['fresc'] + 20;
			$ffcForm = $row['forma'] + 20;
			$ffcCond = $row['cond'] + 20;
			break;
	}
	
	if ($ffcForm > 100) { $ffcForm = 100; }
	if ($ffcFres > 100) { $ffcFres = 100; }
	if ($ffcCond > 100) { $ffcCond = 100; }

	$controllo = 15; // valore dell'esperienza
	
	// Destro sinistro e ambidestro
	if (strtoupper($row['piede']) == "R") 
	{
			$pds = -6;
			$pdd =  6;
	}
	elseif (strtoupper($row['piede']) == "L")
	{
			$pds =  6;
			$pdd = -6;
	}
	else
	{
			$pds = 4;
			$pdd = 4;
	}
	
	$po = $riga_po['po']*$row['po'] + $riga_po['df']*$row['df'] + $riga_po['cn']*$row['cn'] ;
	$po = ($po + $riga_po['pa']*$row['pa'] + $riga_po['tc']*$row['tc'])/(101-$controllo);
	$po1 = $po / 100 * $ffcForm ;
	$po2 = $po / 100 * $ffcFres ;
	$po3 = $po / 100 * $ffcCond ;
	$po = round(($po1 + $po2 + $po3)/3,1);

	$ds = $pds + $riga_ds['df']*$row['df'] + $riga_ds['cn']*$row['cn'] + $riga_ds['pa']*$row['pa'] + $riga_ds['rg']*$row['rg'] ;
	$ds = ($ds + $riga_ds['cr']*$row['cr'] + $riga_ds['tc']*$row['tc'] + $riga_ds['tr']*$row['tr'])/(101-$controllo);
	$ds1 = $ds / 100 * $ffcForm ;
	$ds2 = $ds / 100 * $ffcFres ;
	$ds3 = $ds / 100 * $ffcCond ;
	$ds = round(($ds1 + $ds2 + $ds3)/3,1);

	$dd = $pdd + $riga_dd['df']*$row['df'] + $riga_dd['cn']*$row['cn'] + $riga_dd['pa']*$row['pa'] + $riga_dd['rg']*$row['rg'] ;
	$dd = ($dd + $riga_dd['cr']*$row['cr'] + $riga_dd['tc']*$row['tc'] + $riga_dd['tr']*$row['tr'])/(101-$controllo);
	$dd1 = $dd / 100 * $ffcForm ;
	$dd2 = $dd / 100 * $ffcFres ;
	$dd3 = $dd / 100 * $ffcCond ;
	$dd = round(($dd1 + $dd2 + $dd3)/3,1);
	
	$cs = $pds + $riga_cs['df']*$row['df'] + $riga_cs['cn']*$row['cn'] + $riga_cs['pa']*$row['pa'] + $riga_cs['rg']*$row['rg'] ;
	$cs = ($cs + $riga_cs['cr']*$row['cr'] + $riga_cs['tc']*$row['tc'] + $riga_cs['tr']*$row['tr'])/(101-$controllo);
	$cs1 = $cs / 100 * $ffcForm ;
	$cs2 = $cs / 100 * $ffcFres ;
	$cs3 = $cs / 100 * $ffcCond ;
	$cs = round(($cs1 + $cs2 + $cs3)/3,1);
	
	$cd = $pdd + $riga_cd['df']*$row['df'] + $riga_cd['cn']*$row['cn'] + $riga_cd['pa']*$row['pa'] + $riga_cd['rg']*$row['rg'] ;
	$cd = ($cd + $riga_cd['cr']*$row['cr'] + $riga_cd['tc']*$row['tc'] + $riga_cd['tr']*$row['tr'])/(101-$controllo);
	$cd1 = $cd / 100 * $ffcForm ;
	$cd2 = $cd / 100 * $ffcFres ;
	$cd3 = $cd / 100 * $ffcCond ;
	$cd = round(($cd1 + $cd2 + $cd3)/3,1);
	
	$as = $pds + $riga_as['df']*$row['df'] + $riga_as['cn']*$row['cn'] + $riga_as['pa']*$row['pa'] + $riga_as['rg']*$row['rg'] ;
	$as = ($as + $riga_as['cr']*$row['cr'] + $riga_as['tc']*$row['tc'] + $riga_as['tr']*$row['tr'])/(101-$controllo);
	$as1 = $as / 100 * $ffcForm ;
	$as2 = $as / 100 * $ffcFres ;
	$as3 = $as / 100 * $ffcCond ;
	$as = round(($as1 + $as2 + $as3)/3,1);
	
	$ad = $pdd + $riga_ad['df']*$row['df'] + $riga_ad['cn']*$row['cn'] + $riga_ad['pa']*$row['pa'] + $riga_ad['rg']*$row['rg'] ;
	$ad = ($ad + $riga_ad['cr']*$row['cr'] + $riga_ad['tc']*$row['tc'] + $riga_ad['tr']*$row['tr'])/(101-$controllo);
	$ad1 = $ad / 100 * $ffcForm ;
	$ad2 = $ad / 100 * $ffcFres ;
	$ad3 = $ad / 100 * $ffcCond ;
	$ad = round(($ad1 + $ad2 + $ad3)/3,1);
	
	$dc = 7   + $riga_dc['df']*$row['df'] + $riga_dc['cn']*$row['cn'] + $riga_dc['pa']*$row['pa'] + $riga_dc['rg']*$row['rg'] ;
	$dc = ($dc + $riga_dc['cr']*$row['cr'] + $riga_dc['tc']*$row['tc'] + $riga_dc['tr']*$row['tr'])/(101-$controllo);
	$dc1 = $dc / 100 * $ffcForm ;
	$dc2 = $dc / 100 * $ffcFres ;
	$dc3 = $dc / 100 * $ffcCond ;
	$dc = round(($dc1 + $dc2 + $dc3)/3,1);
	
	$cc = 7   + $riga_cc['df']*$row['df'] + $riga_cc['cn']*$row['cn'] + $riga_cc['pa']*$row['pa'] + $riga_cc['rg']*$row['rg'] ;
	$cc = ($cc + $riga_cc['cr']+$row['cr'] + $riga_cc['tc']*$row['tc'] + $riga_cc['tr']*$row['tr'])/(101-$controllo);
	$cc1 = $cc / 100 * $ffcForm ;

	$cc2 = $cc / 100 * $ffcFres ;
	$cc3 = $cc / 100 * $ffcCond ;
	$cc = round(($cc1 + $cc2 + $cc3)/3,1);
	
	$ac = 7   + $riga_ac['df']*$row['df'] + $riga_ac['cn']*$row['cn'] + $riga_ac['pa']*$row['pa'] + $riga_ac['rg']*$row['rg'] ;
	$ac = ($ac + $riga_ac['cr']*$row['cr'] + $riga_ac['tc']*$row['tc'] + $riga_ac['tr']*$row['tr'])/(101-$controllo);
	$ac1 = $ac / 100 * $ffcForm ;
	$ac2 = $ac / 100 * $ffcFres ;
	$ac3 = $ac / 100 * $ffcCond ;
	$ac = round(($ac1 + $ac2 + $ac3)/3,1);

	$bgcol = array("#6699FF","#CC3300","#CC3300","#CC3300","#00CC66","#00CC66","#00CC66","#CC66CC","#CC66CC","#CC66CC");
	$col = array("#000000","#000000","#000000","#000000","#000000","#000000","#000000","#000000","#000000","#000000");
	$classe = array("Portiere","Difensore","Difensore","Difensore","Centrocampo","Centrocampo","Centrocampo","Attacco","Attacco","Attacco");

	$max = 0;
	$valori = array($po,$ds,$dc,$dd,$cs,$cc,$cd,$as,$ac,$ad);
	$box = array(0,0,0,0,0,0,0,0,0,0);
	$val = array(0,0,0,0,0,0,0,0,0,0);
	$conta = 0;
	
	foreach ($valori as $punteggio)
	{
		if ($max <= $punteggio)
		{
			$max = $punteggio;
			$box[] = $conta;
			$val[] = $max;
		}
		$conta ++;
	}
	
	$number = count($box) -1;
	for ($id = $number; $id >=0; $id --)
	{
		if ($max <= $val[$id])
		{
			$bgcol[$box[$id]] = "#0000FF" ;
			$col[$box[$id]] = "#ffffff";
			$classe[$box[$id]] = "Massimo" ;	
		}
	}
	
	$class = "soloGreen"; 
	
	if (in_array($row['nr'],$riquadro))
	{
		$chiave = array_search($row['nr'],$riquadro);
		if ($chiave < 65)
		{		
			$class = "scelto_in_campo";
		}
		elseif ($chiave >= 65 and $chiave <=69) 
		{
			$class = "scelto_in_panchina";
		}
	}
	$nomecorto = explode(" ",$row['nome'],2);
	if (count($nomecorto == 1)) { $nomecorto[] = $nomecorto[0]; }

	echo "<tr id='TR_$row[id]'  class=\"$class\" >";
	
	if ($infortunio[$counter] > 1)
	{
		echo "<td><div class='nodrag'><img id='$row[id]' src='images/maglietta_azzurra.png' width='20' height='24' /></div></td>";	
		echo "<td width=10 align=right style='color:#990000;'><b>$row[nr])</b></td>";
		echo "<td width=100 style='color:#990000;'><b>$nomecorto[1]</b></td>";
	}
	echo "<td><div class='giocatori'><img id='$row[id]' name='$row[nr]' src='images/maglietta_azzurra.png' width='20' height='24' /></div></td>";
	
	echo "<td width='10' align=right><b>$row[nr])</b></td>";
	echo "<td width='16'><b>$nomecorto[1]</b></td>";

	echo "<th width='16'>$row[skill]</th>";
	echo "<td width='16'> <center><img src=$imm width='16' heigth='16' >" ."</td>";
	echo "<td width='16'> <center><img src=$immpiede width='16' heigth='16' />" ."</td>";
	
	
	echo "<td bgcolor=$bgcol[0] width='16' style='color:$col[0];'><span class=$classe[0]><center>$po</span></td>" ;
	echo "<td bgcolor=$bgcol[1] width='16' style='color:$col[1];'><span class=$classe[1]><center>$ds</span></td>";
	echo "<td bgcolor=$bgcol[2] width='16' style='color:$col[2];'><span class=$classe[2]><center>$dc</span></td>";
	echo "<td bgcolor=$bgcol[3] width='16' style='color:$col[3];'><span class=$classe[3]><center>$dd</span></td>";
	echo "<td bgcolor=$bgcol[4] width='16' style='color:$col[4];'><span class=$classe[4]><center>$cs</span></td>";
	echo "<td bgcolor=$bgcol[5] width='16' style='color:$col[5];'><span class=$classe[5]><center>$cc</span></td>";
	echo "<td bgcolor=$bgcol[6] width='16' style='color:$col[6];'><span class=$classe[6]><center>$cd</span></td>";
	echo "<td bgcolor=$bgcol[7] width='16' style='color:$col[7];'><span class=$classe[7]><center>$as</span></td>";
	echo "<td bgcolor=$bgcol[8] width='16' style='color:$col[8];'><span class=$classe[8]><center>$ac</span></td>";
	echo "<td bgcolor=$bgcol[9] width='16' style='color:$col[9];'><span class=$classe[9]><center>$ad</span></td>";
	echo "</tr>";

	$counter++; 
}
echo "<tr><td height=30></td></tr></table>	";
?>
</body>