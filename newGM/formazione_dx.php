<?php
	if (isset($_REQUEST['ajax']))
	{
		require_once('auth.php');
		include "connect_db.php";
	?>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/jquery-ui-1.8.17.custom.min.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script> 
	<script type="text/javascript" src="jquery/my_script_team.js"></script>
	<script type="text/javascript" src="jquery/my_tooltip.js"></script>
	</head>
	<?php
	}
	
	$nome_team = $_SESSION['SESS_TEAM'];
	
	if (!isset($_REQUEST['label']) or (isset($_REQUEST['label']) and $_REQUEST['label'] == 0))
	{
		include "formazione_dx_campo.php";
	}
	elseif (isset($_REQUEST['label']) and $_REQUEST['label'] == 1)
	{
		include "formazione_dx_tattiche.php";
	}
	elseif (isset($_REQUEST['label']) and $_REQUEST['label'] == 2)
	{
 		include "formazione_dx_istruzioni.php";
	}
?>

	