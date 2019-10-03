<?php

require_once('../auth.php');

define('INCLUDE_CHECK',1);
require "../connect_db.php";

?>
<head>
	
<style>
.tooltip_player{
	position: absolute;
	top: 0;
	left: 0;
	z-index: 3;
	text-align:left;
	width:300px;
	
	background-color: #666666 ;
	
	border:2px solid  #333333; 
	/*border:2px solid  #0000FF;*/
	color:#fcfcfc;

	padding:10px 10px 10px 10px;
	
	-moz-border-radius:12px;
	-khtml-border-radius: 12px;
	-webkit-border-radius: 12px;
	border-radius:12px;
}

.mytable th{
	font-size: 10px;
	color: #FFFF00;
	text-align:left;
	border-bottom:1px solid #fcfcfc;
}
.mytable td{
	font-size: 14px;
	color: #ffffff;
	padding-bottom:20px;
}

</style>

<?php 

$nome_team = $_SESSION['SESS_TEAM'];

if(!$_REQUEST['id']) die("Nessun dato trovato!");

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
	
	
$id=$_REQUEST['id'];

$row=mysql_fetch_assoc(mysql_query("SELECT * FROM giocatori WHERE id='$id' "));

if(!$row) die("Nessun dato trovato!");


/*echo '
	<div class="tooltip_player">
	<strong>'.$row['nome'].'</strong>'.' '.$quale_ruolo[$row['pos']].'

	<p class="descr">Carattere: '.$row['carattere'].', Piede: '.$quale_piede[$row['piede']].'</p>

	<strong>Talento: '.$row['talento'].'</strong><br>
	<small><font style="color:#0000ff">Trascina la maglietta in una zona di campo per farlo giocare.</font></small>
	</div>
	';*/
?>
<div class="tooltip_player">
	<table class='mytable' border="0" cellpadding="0" cellspacing="5">
		<tr>
			<th>Nome</th><th>Ruolo</th>
		</tr>
		<tr>
			<td><strong><?php echo $row['nome'] ?></strong></td>
			<td><?php echo $quale_ruolo[$row['pos']] ?></td>
		</tr>
		<tr>
			<th>Carattere</th><th>Talento</th>
		</tr>
		<tr>
			<td><?php echo $row['carattere'] ?></td>
			<td><?php echo $row['talento'] ?></td>
		</tr>
		<tr>
			<th colspan="2">
				<font style="color:#00FF00; ">Trascina la maglietta in una zona di campo per farlo giocare</font>
			</th>
		</tr>
	</table>
</div>

