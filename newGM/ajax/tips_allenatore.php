<?php

require_once('../auth.php');

define('INCLUDE_CHECK',1);
require "../connect_db.php";

?><head>
	
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
	font-size: 12px;
	color: #ffffff;
	padding-bottom:20px;
}

</style>

<?php 
	
	if(!$_REQUEST['id']) die("Nessun dato trovato!");

	$quale_ruolo['all_1'] = array ("Difesa","Passaggi","Regia","Tiro");
	$quale_ruolo['all_2'] = array ("Contrasti","Cross","Tecnica","Talento");
	$quale_ruolo['all_3'] = array ("Portiere");
	$quale_ruolo['all_4'] = array ("Forma","Condizione");
	
	$id=$_REQUEST['id'];
	
?>
<div class="tooltip_player">
	<table class='mytable' border="0" cellpadding="5" cellspacing="5">
		<tr>
			<th>Caratteristiche</th>
		</tr>
		<?php 
			foreach ($quale_ruolo[$id] as $msg)
			{
				echo "<tr><td>$msg</td></tr>";
			}
		?>
	</table>
</div>

