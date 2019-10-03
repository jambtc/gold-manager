<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=" />
<?php 
	if (isset($_REQUEST['connect']))
	{
		include "connect_db.php";
	}
	$quale_css1 = "css/index".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css1))
	{ 
    	$quale_css1 = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css1'; </style>";
	
	$team = $_SESSION['SESS_TEAM'];
	
	$pr_res = mysql_query("SELECT * FROM zz_config WHERE 1");
	$row_res   =   mysql_fetch_array($pr_res);
	$base = $row_res['prim_base'];
	
	$pr_con = mysql_query("SELECT * FROM members WHERE team=\"$team\" ");
	$row_con   =   mysql_fetch_array($pr_con);
	$prim_gio = $row_con['prim_gio'];
	$prim_ski = $row_con['prim_ski'];
	
	$investim = $row_con['prim_gio'] * $row_con['prim_ski'] * $base;
	
?>
	<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
	<script type="text/javascript" src="jquery/jquery-ui-1.7.3.custom.min.js"></script>
	<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script>
	<script type="text/javascript" src="jquery/my_script.js"></script>
</head>

<body>
<h1><font style="font-weight:bold; color:#00FFFF; font-size:18px; font-family:Verdana, Arial, Helvetica, sans-serif;">Gestione Primavera</font></h1>			

<div id="corpo">
	<div style="float: left;">
		<span class="top-label">  
			<span class="label-txt">Investimento</span>
		</span>
		<div class="content-area"> 
			<img id="inv_prima" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
			<span id="inv_prima">
				<fieldset id='inv_prima'>
					<?php
					$giovani = array(0,1,2,3,4,5);
					$skill = array(0,1,2,3);
					?>
					<table border='1' cellpadding='0' cellspacing='3' width='100%'>
					<tr>
						<th align="right">Giovani da osservare</th>
							<td align="left">
								<select name='giovani' id='giovani' class='fld_y' onchange="javascript:InvestPrimavera();">
								
								<?php 
									echo "<option value='$prim_gio'>$prim_gio</option>";
									foreach ($giovani as $gio)
									{
										echo "<option value='$gio'>$gio</option>";
									}
									echo "</select>";
								?>
							</td>
						<th align="right">Skill da scoprire</th>
							<td align="left">
								<select name='skill' id='skill' class='fld_y' onchange="javascript:InvestPrimavera();">
								<?php 
									echo "<option value='$prim_ski'>$prim_ski</option>";
									foreach ($skill as $ski)
									{
										echo "<option value='$ski'>$ski</option>";
									}
									echo "</select>";
								?>
							</td>
					</tr>
						
					<tr>
						<th colspan="4" height="50" align="center">Costo settimanale: &nbsp;<?php echo $investim ;	?></th>
							
								
					</tr>
					</table>
				</fieldset>
			</span>
		</div>
	</div>
	<div class="invest_prima">
	</div>

	
</div>
<script>
	$('#corpo').slideUp(0).delay(300).fadeIn(600);
</script>
</body>
</html>