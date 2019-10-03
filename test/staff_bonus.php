<?php
	require_once('auth.php');
?>
<table border='0' align='left' style="font-size:16px; font-weight:bold;" >
<tr>
	<td align='right'>Allenatore:&nbsp;</td>
	<?php printf("<th>%+.5s%% </th>",$b_allenatore); ?>
</tr>
<tr>
	<?php echo "<td align='right'>Vice&nbsp;Allenatore:&nbsp;</td>";
	printf("<th>%+.5s%% </th>",$b_viceallena); ?>
</tr>
<tr>
	<?php echo "<td align='right'>Allenatore&nbsp;Portieri:&nbsp;</td>";
	printf("<th>%+.5s%% </th>",$b_alleportie); ?>
</tr>
</tr>
</table>

