<?php
	require_once('auth.php');

	if (isset($_REQUEST['ajax']))
	{
		include "connect_db.php";
	}
	
	$team = $_SESSION['SESS_TEAM'];
	
	$pr_res = mysql_query("SELECT * FROM zz_config WHERE 1");
	$row_res   =   mysql_fetch_array($pr_res);
	$base = $row_res['prim_base'];
	
	
	$pr_con = mysql_query("SELECT * FROM pri_investimento WHERE id_team=\"$team\" ");
	$row_con   =   mysql_fetch_array($pr_con);
	$prim_gio = $row_con['giovani'];
	$prim_ski = $row_con['skill'];
	
	
	if ($prim_gio == "") {$prim_gio = 0;}
	if ($prim_ski == "") {$prim_ski = 0;}
	
	$investim = $row_con['giovani'] * $row_con['skill'] * $base;
	
	$giovani = array(0,1,2,3,4,5);
	$skill = array(0,1,2,3);
	
?>
<h1>Gestione Primavera</h1>			

<div class='div_investimento' style=" float:left; ">	
	<span class="top-label">  
		<span class="label-txt">Investimento</span>
	</span>

	<img class='img_investimento' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_investimento'>
		<fieldset>
			<h2>Il costo attuale dell'investimento &egrave; di &euro;: <font style="color:#FF0000; font-weight:bold;"><?php echo $investim ;	?></font></h2>
			<br>
			<p style="font-weight:bold;">Con questo budget puoi osservare <?php echo $prim_gio; ?> <?php if ($prim_gio!=1){?>giocatori<?php } else {?>giocatore<?php } ?> e scoprire <?php echo $prim_ski; ?> skill.</p> 
			<hr>
			<h4>Indica quanto vuoi investire a settimana nella primavera.</h4>
			<table border='1' cellpadding='0' cellspacing='5' width='100%'>
				<tr>
					<th align="right">Giovani da osservare</th>
					<td align="left">
							<select name='giovani' id='giovani' onchange="javascript:Primavera_MostraSkill(<?php echo $base ?>);">
							
							<?php 
								//echo "<option value='$prim_gio'>$prim_gio</option>";
								foreach ($giovani as $gio)
								{
									echo "<option value='$gio'>$gio</option>";
								}
								
							?>
							</select>
					</td>
					<td rowspan="3"><a href="" onclick="Investimento_Salva(); return false;" class="button">conferma</a>
				</tr>
				<tr>
					
					<th align="right">Skill da scoprire</th>
					<td align="left">
							<div class='td_skill'>
							<select name='skill' id='skill' disabled >
							<?php 
								//echo "<option value='$prim_ski'>$prim_ski</option>";
								foreach ($skill as $ski)
								{
									$vista_ski = $ski*$prim_gio;
									echo "<option value='$vista_ski'>$vista_ski</option>";
								}
								
							?>
							</select>
							</div>
					</td>
				</tr>
				<tr>
					<th align="right">Nuovo investimento di &euro;. </th>
					<td><font style="color:#FF0000; font-weight:bold; font-size:18px;"><div id='new_invest' class='new_invest'>0</div></font></td>
					
				</tr>
					
				
				</table>		
		</fieldset>
	</div>
</div>
<script>
	allarga_maschera('investimento',45,ValAlt('motiva'));
</script>
