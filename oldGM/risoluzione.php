<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<script type="text/javascript">
function getBrowserHeight()
{
	arg = window.screen.height;
	window.location.href="form_giocatori.php?altezza="+arg;		
}
</script>	
</head>

<?php 

	$file = $_GET['file'];
	
	echo "<script>getBrowserHeight($file);</script>";
	}
	else
	{
		$height = $_GET['altezza'];
		
		if ($height <=600 )
		{
			$altezza = 270;
		}
		elseif ($height > 600 and $height <= 720)
		{
			$altezza = 370;
		}
		
		
	}
?>
</html>