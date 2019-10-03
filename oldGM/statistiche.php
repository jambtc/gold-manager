<?php
	require_once('auth.php');

	$quale_css = "css/index".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
	
?>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
<script>
function Visualizza(arg)
{
	document.getElementById('div_stat_giocatori').style.display = "none";
	document.getElementById('div_stat_dx').style.display = "inline";
	document.getElementById('stat_titolo').innerHTML = arg;
}
function Visualizza_giocatori(arg)
{
	document.getElementById('div_stat_dx').style.display = "none";
	document.getElementById('div_stat_giocatori').style.display = "inline";
	document.getElementById('stat_titolo_gio').innerHTML = arg;
}
function Visualizza_staff(arg)
{
	document.getElementById('div_stat_staff').style.display = "inline";
	document.getElementById('div_grafico_staff').style.display = "inline";
	document.getElementById('stat_titolo_staff').innerHTML = arg;
}
</script>
</head>


<h1>Statistiche</h1>
<div id='msgpausa' style="float:left;">
<span class="top-label">
	<?php
			 
		$link = array("form_statistiche.php","form_statistiche.php?pagina=1","form_statistiche.php?pagina=2","form_statistiche.php?pagina=3");
		$titolo = array("Squadra","Giocatori","Staff","Campionato");
		
		if (!isset($_REQUEST['pagina']))
		{
			$link[0] = "#";
			$pagina = 0;
			$classe[0]="a1";
			$classe[1]="a3";
			$classe[2]="a3";
			$classe[3]="a3";
		} else {
			$link[$_REQUEST['pagina']] = "#";
			$pagina = $_REQUEST['pagina'];
			$classe[0]="a3";
			$classe[1]="a3";
			$classe[2]="a3";
			$classe[3]="a3";
			$classe[$_REQUEST['pagina']]="a1";
		}
		
	?>
	<span class="label-txt"><a class="<?php echo $classe[0]; ?>" href="<?php echo $link[0]; ?>">Squadra</a></span>
	<span class="label-txt"><a class="<?php echo $classe[1]; ?>" href="<?php echo $link[1]; ?>" >Giocatori</a></span>
	<span class="label-txt"><a class="<?php echo $classe[2]; ?>" href="<?php echo $link[2]; ?>" >Staff</a></span>
	<span class="label-txt"><a class="<?php echo $classe[3]; ?>" href="<?php echo $link[3]; ?>" >Campionato</a></span>
</span>

<!-- STATISTICHE SQUADRA -->
<?php 
if (!isset($_REQUEST['pagina']))
{
?>
<div class="content-area"> 
	<img id="stat_squadra" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="stat_squadra">
		<fieldset id='stat_squadra'>
			<table border='0' cellpadding='0' cellspacing='5'>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=1" target="stat_dx" onclick="javascript:Visualizza('Rosa');"/>Rosa</td>
				</tr>	
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2"  href="statistiche_dx.php?stat=14" target="stat_dx" onclick="javascript:Visualizza('Miglior tattica allenata');"/>Tattiche</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=2" target="stat_dx" onclick="javascript:Visualizza('Migliori skill e goleador');"/>Forza giocatori</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=3" target="stat_dx" onclick="javascript:Visualizza('Valore di Mercato e Stipendi');"/>Valore giocatori</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2"  href="statistiche_dx.php?stat=6" target="stat_dx" onclick="javascript:Visualizza('Risultati');"/>Risultati</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2"  href="statistiche_dx.php?stat=7" target="stat_dx" onclick="javascript:Visualizza('Bilancio');"/>Bilancio</td>
				</tr>
		
		</table>
		</fieldset>
	</span>
</div>

<?php
} elseif ($_REQUEST['pagina'] == 1) {
?>
<div class="content-area"> 
	<img id="stat_giocatori" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="stat_giocatori">
		<fieldset id='stat_giocatori'>
			<table border='0' cellpadding='0' cellspacing='5'>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=8" target="stat_dx" onclick="javascript:Visualizza('Migliori giocatori per abilità');"/>Top Abilità</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=9" target="stat_dx" onclick="javascript:Visualizza('Migliori giocatori per ruolo');"/>Top Ruoli</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=10" target="stat_dx" onclick="javascript:Visualizza('Miglior rigorista');"/>Top Rigoristi</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=11" target="stat_dx" onclick="javascript:Visualizza('Miglior calciatore di punizioni');"/>Top Punizioni</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=12" target="stat_dx" onclick="javascript:Visualizza('Miglior calciatore di calci d&acute;angolo');"/>Top Calci d&acute;angolo</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=13" target="stat_dx" onclick="javascript:Visualizza('Miglior capitano');"/>Top Capitani</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=40" target="stat_dx_gio" onclick="javascript:Visualizza_giocatori('Lista Giocatori');"/>Singolo Giocatore</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=4" target="stat_dx" onclick="javascript:Visualizza('Preparazione atletica');"/>Preparazione Atletica</td>
				</tr>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=5" target="stat_dx" onclick="javascript:Visualizza('Abilità personali');"/>Abilità personali</td>
				</tr>
		</table>
		</fieldset>
	</span>
</div>
<?php
} elseif ($_REQUEST['pagina'] == 2) {
?>
<div class="content-area"> 
	<img id="stat_staff" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="stat_staff">
		<fieldset id='stat_staff'>
			<table border='0' cellpadding='0' cellspacing='5'>
				<tr>
					<?php echo "<td height=25>"; ?>
					<a class="a2" href="statistiche_dx.php?stat=41" target="stat_graf" onclick="javascript:Visualizza_staff('Personale staff');"/>Personale Staff
					</a>				
					</td>
				</tr>	
		
		</table>
		</fieldset>
	</span>
</div>
<?php
} elseif ($_REQUEST['pagina'] == 3) {
?>
<div class="content-area"> 
	<img id="stat_squadra" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
	<span id="stat_squadra">
		<fieldset id='stat_squadra'>
			<table border='0' cellpadding='0' cellspacing='5'>
				<tr>
					<?php echo "<td height=25>"; ?>
								
					</td>
				</tr>	
		
		</table>
		</fieldset>
	</span>
</div>

<?php
} 
?>
	<script>
		$('#msgpausa').slideUp(0).delay(300).fadeIn(600);
	</script>
</div>



<!-- ELEMENTI NASCOSTI CHE APPAIONO AL MOMENTO DEL CLICK -->
<div id='div_stat_dx' style='display: none ; float:right;' >
	<span class="top-label">
		<span id="stat_titolo" class="label-txt">titolo</span>
	</span>
	
	<div class="content-area"> 
		<img id="stat_dx" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="stat_dx">
			<fieldset id='stat_dx'>
				<iframe src="statistiche_dx.php?stat=0" name="stat_dx" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
		</iframe>
			</fieldset>
		</span>
	</div>
</div>

<div id='div_stat_giocatori' style='display: none ; float:right;' >
	<span class="top-label">
		<span id="stat_titolo_gio" class="label-txt">titolo</span>
	</span>
	
	<div class="content-area"> 
		<img id="stat_dx_gio" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="stat_dx_gio">
			<fieldset id='stat_dx_gio'>
				<iframe src="statistiche_dx.php?stat=0" name="stat_dx_gio" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
				</iframe>	
			</fieldset>
		</span>
	</div>
</div>

<div id='div_stat_staff' style='display: none ; float: inherit;' >
	<span class="top-label">
		<span id="stat_titolo_staff" class="label-txt">titolo</span>
	</span>
	
	<div class="content-area"> 
		<img id="stat_dx_staff" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="stat_dx_staff">
			<fieldset id='stat_dx_staff'>
				<iframe src="staff_storico.php" name="stat_graf" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
				</iframe>	
			</fieldset>
		</span>
	</div>
</div>

<div id='div_grafico_staff' style='display: none; float:right;' >
	<span class="top-label">
		<span id="stat_titolo_gio" class="label-txt">Grafico Staff</span>
	</span>
	
	<div class="content-area"> 
		<img id="stat_dx_gio" src="images/quadrato_rounded_chiaro.png" width="100%" height="100%" />
		<span id="stat_dx_gio">
			<fieldset id='stat_dx_gio'>
				<iframe src="staff_storico.php" name="grafico_staff" width="100%" marginwidth="0" height="100%" align="top" allowtransparency="1" frameborder="0" scrolling="no" hspace="0" vspace="0">
				</iframe>	
			</fieldset>
		</span>
	</div>
</div>
