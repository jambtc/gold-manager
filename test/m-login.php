<?php 
	require_once('auth.php');
	
	$sc_top 		= "images/scudo/scudo_top_".$_SESSION['SESS_LOGO_TOP'].".png";
	$sc_middle 		= "images/scudo/scudo_middle_".$_SESSION['SESS_LOGO_MIDDLE'].".png";
	$sc_bottom_left = "images/scudo/scudo_bottom_left_".$_SESSION['SESS_LOGO_BOTTOM_LEFT'].".png";
	$sc_bottom_center = "images/scudo/scudo_bottom_center_".$_SESSION['SESS_LOGO_BOTTOM_CENTER'].".png";
	$sc_bottom_right = "images/scudo/scudo_bottom_right_".$_SESSION['SESS_LOGO_BOTTOM_RIGHT'].".png";
	
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
?>

<div class='div_login'>		
	<div style="float:left;">
		<div >
			<?php echo "<img src='images/scudo/scudo_base.png' border='0' width='110'  />"; ?>
			<div class='login_scudo_top'>
				<?php echo "<img src='$sc_top' border='0' width='86'  />"; ?>
			</div>
			<div class='login_scudo_middle'>
				<?php echo "<img src='$sc_middle' border='0' width='90'  />"; ?>
			</div>
			<div class='login_scudo_bottom_left'>
				<?php echo "<img src='$sc_bottom_left' border='0' width='21'  />"; ?>
			</div>
			<div class='login_scudo_bottom_center'>
				<?php echo "<img src='$sc_bottom_center' border='0' width='70'  />"; ?>
			</div>
			<div class='login_scudo_bottom_right'>
				<?php echo "<img src='$sc_bottom_right' border='0' width='56'  />"; ?>
			</div>
		</div>
	</div>
	<div id='login_team'>
		Team<br />
		<span id='login_team'>
			<?php
				$team = str_replace(chr(32),"&nbsp;",$team);
				echo "$team";
			?>
		</span>
	
		<div id='login_serie'>
			Serie<br />
			<span id='login_serie'>
				<?php  echo "$div"; ?>
			</span>
		</div>
		<div id='login_budget'>
			<?php
				$budget = number_format($budget,0,",",".");
		 
				echo "€. ".$budget;
			?>
		</div>
	</div>
</div>
