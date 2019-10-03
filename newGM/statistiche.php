<?php
	require_once('auth.php');
?>
<h1 class="h1">Statistiche</h1>
<div class='div_statistiche' style=" float:left; ">	
	<span class="top-label">
		<?php
			$link = array("m-index.php?fnz=statistiche&pg=12","m-index.php?fnz=statistiche&pg=12&tabella=1","m-index.php?fnz=statistiche&pg=12&tabella=2","m-index.php?fnz=statistiche&pg=12&tabella=3");
			$titolo = array("Squadra","Giocatori","Staff","Campionato");
				
			if (!isset($_REQUEST['tabella']))
			{
				$link[0] = "#";
				$tabella = 0;
				$classe[0]="a3";
				$classe[1]="a4";
				$classe[2]="a4";
				$classe[3]="a4";
			} else {
				$link[$_REQUEST['tabella']] = "#";
				$tabella = $_REQUEST['tabella'];
				$classe[0]="a4";
				$classe[1]="a4";
				$classe[2]="a4";
				$classe[3]="a4";
				$classe[$_REQUEST['tabella']]="a3";
			}
		?>
		<span class="label-txt"><a class="<?php echo $classe[0]; ?>" href="<?php echo $link[0]; ?>">Squadra</a></span>
		<span class="label-txt"><a class="<?php echo $classe[1]; ?>" href="<?php echo $link[1]; ?>" >Giocatori</a></span>
		<span class="label-txt"><a class="<?php echo $classe[2]; ?>" href="<?php echo $link[2]; ?>" >Staff</a></span>
		<span class="label-txt"><a class="<?php echo $classe[3]; ?>" href="<?php echo $link[3]; ?>" >Campionato</a></span>
	</span>

<!-- STATISTICHE SQUADRA -->
	<?php 
		if (!isset($_REQUEST['tabella']))
		{
	?>
		<img class='img_statistiche' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_statistiche'>
			<fieldset>
			<table border='0' cellpadding='0' cellspacing='5'>
				<tr>
					<td height="25">
					<a class="a5" href="statistiche_dx.php?stat=1" target="stat_dx" onclick="javascript:Visualizza('Rosa');"/>Rosa</td>
				</tr>	
				<tr>
					<td height="25">
					<a class="a5"  href="statistiche_dx.php?stat=14" target="stat_dx" onclick="javascript:Visualizza('Miglior tattica allenata');"/>Tattiche</td>
				</tr>
				<tr>
					<td height="25">
					<a class="a5" href="statistiche_dx.php?stat=2" target="stat_dx" onclick="javascript:Visualizza('Migliori skill e goleador');"/>Forza giocatori</td>
				</tr>
				<tr>
					<td height="25">
					<a class="a5" href="statistiche_dx.php?stat=3" target="stat_dx" onclick="javascript:Visualizza('Valore di Mercato e Stipendi');"/>Valore giocatori</td>
				</tr>
				<tr>
					<td height="25">
					<a class="a5"  href="statistiche_dx.php?stat=6" target="stat_dx" onclick="javascript:Visualizza('Risultati');"/>Risultati</td>
				</tr>
				<tr>
					<td height="25">
					<a class="a5"  href="statistiche_dx.php?stat=7" target="stat_dx" onclick="javascript:Visualizza('Bilancio');"/>Bilancio</td>
				</tr>
			</table>
			</fieldset>
		</div>
			
		<script>
			allarga_maschera('statistiche',40,ValAlt('statistiche_squadra'));
		</script>

<?php
} elseif ($_REQUEST['tabella'] == 1) {
?>
		<img class='img_statistiche' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_statistiche'>
			<fieldset>
				<table border='0' cellpadding='0' cellspacing='5'>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=8" target="stat_dx" onclick="javascript:Visualizza('Migliori giocatori per abilità');"/>Top Abilità</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=9" target="stat_dx" onclick="javascript:Visualizza('Migliori giocatori per ruolo');"/>Top Ruoli</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=10" target="stat_dx" onclick="javascript:Visualizza('Miglior rigorista');"/>Top Rigoristi</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=11" target="stat_dx" onclick="javascript:Visualizza('Miglior calciatore di punizioni');"/>Top Punizioni</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=12" target="stat_dx" onclick="javascript:Visualizza('Miglior calciatore di calci d&acute;angolo');"/>Top Calci d&acute;angolo</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=13" target="stat_dx" onclick="javascript:Visualizza('Miglior capitano');"/>Top Capitani</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=40" target="stat_dx" onclick="javascript:Visualizza('Lista Giocatori');"/>Singolo Giocatore</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=4" target="stat_dx" onclick="javascript:Visualizza('Preparazione atletica');"/>Preparazione Atletica</td>
					</tr>
					<tr>
						<td height="25">
						<a class="a5" href="statistiche_dx.php?stat=5" target="stat_dx" onclick="javascript:Visualizza('Abilità personali');"/>Abilità personali</td>
					</tr>
			</table>
			</fieldset>
		</div>
		<script>
			allarga_maschera('statistiche',40,ValAlt('statistiche_giocatori'));
		</script>
<?php
}  elseif ($_REQUEST['tabella'] == 2) {
?>
	<img class='img_statistiche' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_statistiche'>
			<fieldset>
			<table border='0' cellpadding='0' cellspacing='5'>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a5" href="statistiche_dx.php?stat=41" target="stat_dx" onclick="javascript:Visualizza_staff('Personale Staff');"/>Personale Staff
					</a>				
					</td>
				</tr>	
			</table>
			</fieldset>
		</div>
		<script>
			allarga_maschera('statistiche',40,ValAlt('statistiche_staff'));
		</script>
<?php
} elseif ($_REQUEST['tabella'] == 3) {
?>
	<img class='img_statistiche' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
		<div class='cnt_statistiche'>
			<fieldset>
			<table border='0' cellpadding='0' cellspacing='5'>
				<tr>
					<td height="25">
						Gol Fatti					
					</td>
				</tr>	
				<tr>
					<td height="25">
						Gol Subiti					
					</td>
				</tr>
				<tr>
					<td height="25">
						Miglior realizzatore					
					</td>
				</tr>		
				<tr>
					<td height="25">
						Vittorie consecutive					
					</td>
				</tr>	
		</table>
		</fieldset>
	</div>
	<script>
			allarga_maschera('statistiche',40,ValAlt('statistiche_squadra'));
	</script>
<?php
} 
?>
</div>


<!-- ELEMENTI NASCOSTI CHE APPAIONO AL MOMENTO DEL CLICK -->
<div class='div_stat_dx' id='div_stat_dx' style="float:right; display:none;" >	
	<span class="top-label">  
		<span id="stat_titolo" class="label-txt">titolo</span>
	</span> 			
	<img class='img_stat_dx' src="images/quadrato_rounded.png"  width="10%" height="10%" border="0" /> 
	<div class='cnt_stat_dx'>
		<fieldset>
			<iframe src="statistiche_dx.php?stat=0" class="frm_stat_dx" name="stat_dx" width="10%" marginwidth="0" height="10%" align="top" allowtransparency="1" frameborder="0" scrolling="yes" hspace="0" vspace="0"></iframe>
		</fieldset>
	</div>
</div>	

<div class='div_stat_staff' id='div_stat_staff' style="float: inherit; display:none ; margin-top:130px;" >
	<span class="top-label">
		<span class="label-txt">Grafico Staff</span>
	</span>
	
	<img class="img_stat_staff" src="images/quadrato_rounded.png" width="10%" height="10%" border="0" />
	<div class="cnt_stat_staff"> 
		<fieldset>
			<iframe src="storico_staff.php" class="frm_stat_staff" name="grafico_staff" width="10%" marginwidth="0" height="10%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
			</iframe>	
		</fieldset>
	</div>
</div>

<script>
	allarga_msk_frame('stat_dx',50,ValAlt('statistiche_frame'));
	allarga_msk_frame('stat_staff',43,ValAlt('statistiche_frame_staff'));
</script>
