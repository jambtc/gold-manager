
<?php 
	if (isset($_REQUEST['ajax']))
	{
		require_once('auth.php');
		require "connect_db.php";
	}
	
	$user = $_SESSION['SESS_USER'];
	
	$select = "SELECT * FROM members ";
	$rest = mysql_query($select);
	while   ($row   =   mysql_fetch_array($rest))
	{
		$avatar[$row['login']] = $row['avatar'];
	}
	
	$timeoutseconds = 300; // da mettere a 300 una volta finito
	$timestamp = time(); 
	$timeout = $timestamp-$timeoutseconds;
	
	$insert = "INSERT INTO useronline VALUES ('$timestamp',\"$user\")";
	$result1 = mysql_query($insert);
	
	if (!($result1))
	{
		print "Useronline Insert Failed > ".mysql_error();
	}
	$delete = "DELETE FROM useronline WHERE timestamp<$timeout";
	$result2 = mysql_query($delete);
	
	if (!($result2))
	{
		print "Useronline Delete Failed > ".mysql_error();
	}
	
	$seleziona = "SELECT DISTINCT user FROM useronline WHERE 1";
	$result3 = mysql_query($seleziona);
	if (!($result3))
	{
		print "Useronline Select Error > ".mysql_error();
	}
	while   ($row   =   mysql_fetch_array($result3))
	{
		$utente_online[] = $row['user'];
	}
	
	
	
	
?>
<div id='chat' style="width:100%; " >
	<div class='chatbox' >
		<?php
			$clear_chat = 1000 * 60 * 24 * 30 * 6 ; // TIMEOUT DI 6 MESI PER CONSERVARE LE CHAT...
			
			$expire_time = $timestamp-$clear_chat;
			$del_chat = "DELETE FROM messaggi WHERE time<$expire_time";
			$result5 = mysql_query($del_chat);
			if (!($result5))
			{
				print "Chat Delete Failed > ".mysql_error();
			}
			$seleziona = "SELECT * FROM messaggi WHERE 1 ORDER BY time DESC";
			$result_box = mysql_query($seleziona);
			if (!($result_box))
			{
				print "Message Printing Error > ".mysql_error();
			}
			
			while ($row   =   mysql_fetch_array($result_box))
			{
				$Oggi = $row['time'];
				$g1 = date("d",$Oggi);
				$m1 = date("m",$Oggi);
				$a1 = date("Y",$Oggi);
				$h1 = date("H",$Oggi);
				$mn1 = date("i",$Oggi);
				$oraIng = $h1.":".$mn1;
				$dataIta = $g1."/".$m1."/".$a1;
				
				echo "<table class='chatrigo' border=0>";
					echo "<tr><td class='chatdata'>".$dataIta."<br>".$oraIng."</td>";
					echo "<td><div class='chatuser'>".$row['utente']."</div></td>";
					echo "<td class='chattext'>".$row['testo']."</td></tr>";
				echo "</table>";
			}
		?>
	</div>

	<div id='contachat'></div>
	<div class='chatavatars'>
		<?php
			foreach ($utente_online as $user)
			{
				echo "<table class='chatrigo' border=0>";
					echo "<tr><td class='avatar_img'><img src='images/led.png' width='20' /></td>";
					echo "<td class='avatar_name'>".$user."</td></tr>";
				echo "</table>";
			}
		?>
	</div>

<div class='chatGO'>
	<fieldset>
	<form name="chatForm" method="post" action="javascript:chatIns(document.chatForm.testo);" >
		<table widht='100%' border=0>
		<tr>
			<td width="100%"><input id='input_testo' type="text" name="testo" style="width:98%;"/></td> 
			<td><a href="" onclick="document.forms.chatForm.submit(); return false;" class="aqua" >Invia</a></td>
		</tr>
		</table>
	</form>
	</fieldset>
</div>
</div>
<script>
	cronochat(5);
</script>