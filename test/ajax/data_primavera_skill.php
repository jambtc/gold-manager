<?php 
	$base = $_REQUEST['base'];
	$prim_gio = $_REQUEST['giovani'];

	$skill = array(0,1,2,3);
?>

<select name='skill' id='skill' onchange="javascript:Primavera_Invest(<?php echo $base ?>);">
<?php 
	//echo "<option value='0'>0</option>";
	foreach ($skill as $ski)
	{
		$vista_ski = $ski*$prim_gio;
		echo "<option value='$vista_ski'>$vista_ski</option>";
	}
?>
</select>
