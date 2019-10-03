<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />


<body>
<?php
$nome_team = $_SESSION['SESS_TEAM'];
$a = $_REQUEST['atl'];

$a1 = "generated/".$nome_team.$a."t.png";
$a2 = "generated/".$nome_team.$a."m.png";

echo "
  
<table border='0' cellpadding='0' cellspacing='0' >
<tr align='center' valign='middle' >
	<td>
	<img alt='grafico'  src=\"$a1\" style='border: 1px solid gray;'/>
	</td>
</tr>
<tr align='center' valign='middle' >
	<td>
	<img alt='grafico'  src=\"$a2\" style='border: 1px solid gray;'/>
	</td>
</tr>
</table>

";
?>
</body>
</html>
