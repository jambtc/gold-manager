<?php
	$conta = mysql_query("SELECT * FROM members WHERE 1"); 
	$res = mysql_query("SELECT visite,totali FROM contatore WHERE pagina = $pagina");
	$membri = mysql_num_rows($conta);
	$visite = mysql_fetch_assoc($res);
?>
	<table style="color:#FFFFFF;" border="0" align="center" width='90%'>
		<tr>
		<td width="50%" align="left">
		<?php 
			echo "Visite Totali: ".$visite['totali'].", Singole: ".$visite['visite']." - Membri: ".$membri; 
		?>	
		</td>
		<td width="50%" align="right">
			Gold Manager - Copyright (&copy;) 2012 <a id='a2' href="mailto:sergio.casizzone@gmail.com">Sergio Casizzone</a>, All Rights Reserved
		</td>
		</tr>
	</table>	






