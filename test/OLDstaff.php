<?php
	require_once('auth.php');
	
	$nome_team = $_SESSION['SESS_TEAM'];
	$nome_utente = $_SESSION['SESS_USER'];
	$serie = $_SESSION['SESS_SERIE'];
	
	$b_allenatore = 0;
	$b_viceallena = 0;
	$b_alleportie = 0;
	
	$result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ORDER BY s_id_staff");
	if (!$result)
	{
    	echo 'Errore nella query: ' . mysql_error();
	    exit();
	}
	
	$link = array("m-index.php?fnz=staff&pg=22","m-index.php?fnz=staff&tabella=1&pg=22");
	$titolo = array("Il tuo staff","Offerte personale");
		
	if (!isset($_REQUEST['tabella']))
	{
		$link[0] = "#";
		$tabella = 0;
		$classe[0]="a3";
		$classe[1]="a4";
	} else {
		$link[$_REQUEST['tabella']] = "#";
		$tabella = $_REQUEST['tabella'];
		$classe[0]="a4";
		$classe[1]="a3";
	}
	
?>
<h1 class='h1'>Staff</h1>

<div class='div_staff' style=" float:left; ">	
	<span class="top-label">
		<span class="label-txt"><a class="<?php echo $classe[0]; ?>" href="<?php echo $link[0]; ?>">Dipendenti</a></span>
		<span class="label-txt"><a class="<?php echo $classe[1]; ?>" href="<?php echo $link[1]; ?>" >Candidati</a></span>
	</span>
	<!-- DATI STAFF -->
	<?php 
	if (!isset($_REQUEST['tabella']))
	{
	?>
		<div class='div_staff_dipendenti'>	
			<img class='img_staff_dipendenti' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_staff_dipendenti'>
				<fieldset>
					<div class='scr_staff_dipendenti' >
						<?php include "staff_list.php"; ?>		
					</div>
				</fieldset>
			</div>
		</div>
		<br />
		<br />
		<br />
		
		<div class='div_staff_bonus'>	
			<span class="top-label">  
				<span class="label-txt">Bonus Partita</span>
			</span> 
			<img class='img_bonus' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
			<div class='cnt_bonus'>
				<fieldset>
					<?php include "staff_bonus.php"; ?>	
				</fieldset>
			</div>
		</div>
		<script>
			allarga_maschera('staff_dipendenti',50,ValAlt('staff'));
		</script>
		<script>
			allarga_maschera('bonus',50,ValAlt('bonus'));
		</script>
</div>
		
<div class='div_motiva' style="float:right;">	
	<span class="top-label">  
		<span class="label-txt">Dettagli</span>
	</span> 
	<img class='img_motiva' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_motiva'>
		<fieldset>
			<iframe class="frm_motiva" src="staff_motiva.php?id=0" name="ApriFin" width="10%" marginwidth="0" height="10%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
			</iframe>
		</fieldset>
	</div>
</div>
<script>
	allarga_msk_frame('motiva',45,ValAlt('motiva'));
</script>	
	
	<?php
	} else {
	?>
	<div class='div_staff_dipendenti'>	
		<img class='img_staff_dipendenti' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_staff_dipendenti'>
			<fieldset>
				<div class='scr_staff_dipendenti' >
					<?php  include "staff_offerte.php"; ?>				
				</div>
			</fieldset>
		</div>
	</div>
	<script>
		allarga_maskwscroll('staff_dipendenti',50,ValAlt('staff'));
	</script>
</div>
	
<div class='div_contra' style="float:right;">	
	<span class="top-label">  
		<span class="label-txt">Contrattazione</span>
	</span> 
	<img class='img_contra' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_contra'>
		<fieldset>
			<iframe class="frm_contra" src="staff_contra.php?id=0" name="contra" width="10%" marginwidth="0" height="10%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
			</iframe>
		</fieldset>
	</div>
</div>

<script>
	allarga_msk_frame('contra',45,ValAlt('motiva'));
</script>
<?php
	}
?>
<script>
	allarga_maskwscroll('staff',50,ValAlt('staff'));
</script>


