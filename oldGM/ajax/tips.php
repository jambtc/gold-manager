<?php

require_once('../auth.php');

define('INCLUDE_CHECK',1);
require "../connect_db.php";

$nome_team = $_SESSION['SESS_TEAM'];

if(!$_POST['id']) die("Nessun dato trovato!");

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
	
	$quale_piede['R'] = "Destro";
	$quale_piede['L'] = "Sinistro";
	$quale_piede['LR'] = "Ambidestro";
	

$id=mysql_real_escape_string(end(explode('/',$_POST['id'])));

$row=mysql_fetch_assoc(mysql_query("SELECT * FROM giocatori WHERE id='$id' "));

if(!$row) die("Nessun dato trovato!");

echo '<strong>'.$row['nome'].'</strong>'.' '.$quale_ruolo[$row['pos']].'

<p class="descr">Carattere: '.$row['carattere'].', Piede: '.$quale_piede[$row['piede']].'</p>

<strong>Talento: '.$row['talento'].'</strong><br>
<small>Trascina la maglietta in una zona di campo per farlo giocare.</small>';
?>
