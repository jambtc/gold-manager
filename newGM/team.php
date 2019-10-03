<?php
	require_once('auth.php');

	$nome_team = $_SESSION['SESS_TEAM'];
	$nome_utente = $_SESSION['SESS_USER'];
	$serie = $_SESSION['SESS_SERIE'];

	$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\" ");
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}

	$totale = mysql_num_rows($result);
?>
<h1 class="h1">Giocatori&nbsp;(<span id='totale_giocatori'><?php echo $totale ?></span>)</h1>

<div class='div_teamlist' style="float:left;">	
	<span class="top-label">  
		<span class="label-txt">Lista giocatori</span>
	</span> 
	<img class='img_teamlist' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_teamlist'>
		<fieldset>
			<iframe class="frm_teamlist" src="team_list.php" name="team_list" width="10%" marginwidth="0" height="10%" align="top" allowtransparency="1" frameborder="0" scrolling="yes" hspace="0" vspace="0">
			</iframe>
		</fieldset>
	</div>
</div>

<div class='div_player_posizione' style="float:left; margin-left:10px;">	
	<span class="top-label">  
		<span class="label-txt">Calcolatore di Posizione</span>
	</span> 
	<img class='img_player_posizione' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_player_posizione'>
		<fieldset>
			<iframe class="frm_player_posizione" src="ajax/tips_field.php" name="box_campo" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
			</iframe>
		</fieldset>
	</div>
</div>


<script>
	allarga_msk_frame('teamlist',70,ValAlt('teamlist'));
	allarga_msk_frame('player_posizione',25,ValAlt('player_posizione'));
</script>	

