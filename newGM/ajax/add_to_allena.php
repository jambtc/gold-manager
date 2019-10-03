<?php
	require_once('../auth.php');
	require "../connect_db.php";

	if(!$_REQUEST['id']) die("ID NON TROVATO!");

	$id = $_REQUEST['id'];
	$id_allena = $_REQUEST['id_allena'];
	$nome_team = $_SESSION['SESS_TEAM'];

	$brdclasse = array("","brdgioca","brdgioca","brdportiere","brdgioca");
	
	$im_pos = array("rpo.png","rds.png","rdc.png","rdd.png","rcs.png","rcc.png","rcd.png","ras.png","rac.png","rad.png","rxx.png");
	
	$im_ruoli = array("PO","DS","D","DD","CS","C","CD","AS","A","AD","XX");
	
	$ar_immpos = array_combine($im_ruoli,$im_pos);
	
	$quale_ruolo['PO'] = "(PO) Portiere";
	$quale_ruolo['DS'] = "(DS) Terzino Sinistro";
	$quale_ruolo['D']  = "(D) Difensore Centrale";
	$quale_ruolo['DD'] = "(DD) Terzino Destro";
	$quale_ruolo['CS'] = "(CS) Ala Sinistra";
	$quale_ruolo['C']  = "(C) Centrocampista";
	$quale_ruolo['CD'] = "(CD) Ala Destra";
	$quale_ruolo['AS'] = "(AS) Attaccante Sinistro";
	$quale_ruolo['A']  = "(A) Attaccante Centrale";
	$quale_ruolo['AD'] = "(AD) Attaccante Destro"; 
	$quale_ruolo['XX'] = "(XX) Jolly"; 	

	
	// DATA ODIERNA 
	$rOggi = time();
	$rg1 = date("j",$rOggi);
	$rm1 = date("m",$rOggi);
	$ra1 = date("Y",$rOggi);
	$rora = date("H",$rOggi); // ora di iscrizione
	$rmin = date("i",$rOggi); // minuti di iscrizione

	$rdatasql = $ra1 . "-" . $rm1 . "-" . $rg1;
	
	if (!isset($_REQUEST['del']))
	{
		// AGGIORNO IL DATABASE DELL'ALLENAMENTO SE DEVO INSERIRE, ALTRIMENTI CANCELLO 
		$qry = "INSERT INTO allena_skill (id_team, id_player,id_allena,data) VALUES (\"$nome_team\",'$id','$id_allena','$rdatasql')";
	
	}
	else
	{
		$qry = "DELETE FROM allena_skill WHERE id_player = '$id' LIMIT 1";
	}
	$result = mysql_query($qry);
	if (!$result)
	{
		echo 'Errore nella query ALLENAMENTO: ' . mysql_error();
    	exit();
	}

	//******************** RIcarico come se fosse la pagina
	$result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ORDER BY s_id_staff");
	if (!$result)
	{
		echo 'Errore nella query STAFF: ' . mysql_error();
	    exit();
	}
	while   ($row   =   mysql_fetch_array($result))
	{
		$id_staff[] = $row['s_id_staff'];
		$effic_staff[] = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
		$nome_staff[] = $row['s_nome'];	
	}
	$efficienza_allenatori = array_combine($id_staff,$effic_staff);
	$nome_allenatori = array_combine($id_staff,$nome_staff);
	
	$result = mysql_query("SELECT * FROM allena_skill WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
		echo 'Errore nella query SELECT ALLENAMENTO: ' . mysql_error();
	    exit();
	}
	while   ($row   =   mysql_fetch_array($result))
	{
		$a_id_player[] = $row['id_player'];
		$a_id_allena[] = $row['id_allena'];
		$a_forma[] = $row['forma'];
		$a_fresc[] = $row['fresc'];
		$a_cond[] = $row['cond'];
		$a_po[] = $row['po'];
		$a_df[] = $row['df'];
		$a_cn[] = $row['cn'];
		$a_pa[] = $row['pa'];
		$a_rg[] = $row['rg'];
		$a_cr[] = $row['cr'];
		$a_tc[] = $row['tc'];
		$a_tr[] = $row['tr'];
		$a_talento[] = $row['talento'];
	}
	$ar_player_allenato = array_combine($a_id_player,$a_id_allena);
	
	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
		echo 'Errore nella query GIOCATORI: ' . mysql_error();
	    exit();
	}
	while   ($row   =   mysql_fetch_array($result))
	{
		$id_player[] = $row['id'];
		$nr_player[] = $row['nr'];
		$nome_player[] = $row['nome'];
		$skill_player[] = $row['skill'];
		$pos_player[] = $row['pos'];
	}
	
	$ar_player_nr = array_combine($id_player,$nr_player);
	$ar_player_nome = array_combine($id_player,$nome_player);
	$ar_player_skill = array_combine($id_player,$skill_player);
	$ar_player_pos = array_combine($id_player,$pos_player);
//***************************fine carico

//**VISUALIZZO I NUOVI DATI AGGIORNATI
?>
<table border="0" width="100%">
	<tr class='green_bar'>
		<td width='1%' align="center">Nr</td>
		<td width='20%' align='left'>Nome</td>
		<td width='2%'>Skill</td>
		<td width='2%' title='Ruolo'>Pos</td>
	</tr>
<?php 
	$max_righe = 0;
	foreach ($id_player as $my_id)
	{
		if (isset($ar_player_allenato[$my_id]))
		{
			$box = $id_allena ;
			if ($id_allena == 5) { $box = 3; }
			if ($id_allena == 10) { $box = 4; }
			if ($ar_player_allenato[$my_id] == $id_allena) //DIPEDNE DA BOX
			{
				$max_righe ++;
				$new_tabella = $box."_".$max_righe;
				$img_ruolo = "images/".$ar_immpos[$ar_player_pos[$my_id]];
				$titolo = $quale_ruolo[$ar_player_pos[$my_id]];
				echo "<tr>";
				echo "<td><input name='btn' class=$brdclasse[$box] value='$ar_player_nr[$my_id]' type='button' onclick='javascript:delete_training($my_id,$box);' title='Clicca per togliere il giocatore'></td>";
				echo "<td>$ar_player_nome[$my_id]</td>";
				echo "<td align=center>$ar_player_skill[$my_id]</td>";
				echo "<td><img src=$img_ruolo width='18' heigth='16' border='0' title='$titolo'/></td>";
				echo "<input value='$my_id' type='hidden' id='tab_allena_$new_tabella' >";
				echo "</tr>";
			}
		}
	}
	echo "</table>";
	$max_righe ++;
	for ($fine = $max_righe; $fine <= 10; $fine++)
	{
		$new_tabella = $box."_".$fine;
		echo "<table border=0 width='100%'>";
		echo "<tr>";
		echo "<td><input value='-1' type='hidden' id='tab_allena_$new_tabella' >&nbsp;</td>";
		echo "</tr>";
		echo "</table>";
	}
?>
