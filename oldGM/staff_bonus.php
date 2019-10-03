<?php
require_once('auth.php');
echo "<br>";
echo "<table  border='0' align='center'>";
echo "<tr>";
echo "<td align='right'>Allenatore:&nbsp;</td>";
printf("<th>%+.5s%% </th>",$b_allenatore);
//echo "</tr>";
//echo "<tr>";
echo "<td align='right'>Vice&nbsp;Allenatore:&nbsp;</td>";
printf("<th>%+.5s%% </th>",$b_viceallena);
//echo "</tr>";
//echo "<tr>";
echo "<td align='right'>Allenatore&nbsp;Portieri:&nbsp;</td>";
printf("<th>%+.5s%% </th>",$b_alleportie);
echo "</tr>";
echo "</tr>";
echo "</table>";
?>
