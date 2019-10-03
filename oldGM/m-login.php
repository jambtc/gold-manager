<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
</head>
	
<body>

<?php 
	 
	$sx = "images/scudo_sx".$_SESSION['SESS_SX'].".png";
	$dx = "images/scudo_dx".$_SESSION['SESS_DX'].".png";
	$team = $_SESSION['SESS_TEAM'];
	$div = $_SESSION['SESS_SERIE'];
	$budget = "ERR!!";
	
	$qry = "SELECT * FROM members WHERE team=\"$team\"";
	$result = mysql_query($qry);
		
	if($result)
	{
		$member = mysql_fetch_assoc($result);
		$budget = $member['budget'];
	}
		
	/*
	$mesi = array(1=>'gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre');
	$mnum = array(1=>'01','02','03','04','05','06','07','08','09','10','11','12');
	$giorni = array('domenica','lunedì','martedì','mercoledì','giovedì','venerdì','sabato');
	list($sett,$giorno,$mese,$anno) = explode('-',date('w-d-n-Y'));
	$oggi = $giorni[$sett].' '.$giorno.' '.$mesi[$mese].' '.$anno;
	*/

?>
<div id='login_benvenuto'>
	<?php 
		echo "Ciao ".$_SESSION['SESS_USER'];
	?>
	<hr />
</div>

<div >
	<span>
		<?php 
			echo "<img id='sx' name='sx' src='$sx' width='25' /><img id='dx' name='dx' src='$dx'  width='25' />";
		?>
	</span>
</div>
<!-- 
<div id='login_stellina_sx'>
	<img id='stsx' name='stsx' src='images/stellina.gif' width='80%' height='80%'/>
</div>
<div id='login_stellina_dx'>
	<img id='stdx' name='stdx' src='images/stellina.gif' width='80%' height='80%'/>
</div>
-->

<div id='login_team'>
	Team<br />
	<?php 
		echo "<span id='login_team'>";
			$team = str_replace(chr(32),"&nbsp;",$team);
			echo "$team"; 
		echo "</span>";
		?>
</div>
<div id='login_serie'>
	Serie<br />
	<?php 
		echo "<span id='login_serie'>";
			echo "$div"; 
		echo "</span>";
		?>
</div>
<div id='login_data'>
	<?php
		$budget = number_format($budget,0,",",".");
 
		echo "€. ".$budget;
	?>
</div>
</body>
</html>
