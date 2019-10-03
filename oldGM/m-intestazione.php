<?php
	require_once('auth.php');
?>
<head>
<?php 
	$quale_css = "css/index".$_SESSION['SESS_LARGHEZZA'].".css";
	// se non esiste il file carico index.css
	if (!file_exists($quale_css))
	{ 
    	$quale_css = "css/index1024.css";
	} 
	echo "<style type='text/css'> @import '$quale_css'; </style>";
?>
<SCRIPT type=text/javascript>
var persistclose=0 //set to 0 or 1. 1 means once the bar is manually closed, it will remain closed for browser session
var startX = 3 //set x offset of bar in pixels
var startY = 3 //set y offset of bar in pixels
var verticalpos="frombottom" //enter "fromtop" or "frombottom"

function iecompattest(){
return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
}

function get_cookie(Name) {
var search = Name + "="
var returnvalue = "";
if (document.cookie.length > 0) {
offset = document.cookie.indexOf(search)
if (offset != -1) {
offset += search.length
end = document.cookie.indexOf(";", offset);
if (end == -1) end = document.cookie.length;
returnvalue=unescape(document.cookie.substring(offset, end))
}
}
return returnvalue;
}

function closebar(){
if (persistclose)
document.cookie="remainclosed=1"
document.getElementById("topbar").style.visibility="hidden"
}

function staticbar()
{
	barheight=document.getElementById("topbar").offsetHeight
	var ns = (navigator.appName.indexOf("Netscape") != -1) || window.opera;
	var d = document;
	function ml(id)
	{
		var el=d.getElementById(id);
		if (!persistclose || persistclose && get_cookie("remainclosed")=="")
		el.style.visibility="visible"
		if(d.layers)el.style=el;
		el.sP=function(x,y){this.style.right=x+"px";this.style.top=y+"px";};
		el.x = startX;
		if (verticalpos=="fromtop")
			el.y = startY;
		else
		{
			el.y = ns ? pageYOffset + innerHeight : iecompattest().scrollTop + iecompattest().clientHeight;
			el.y -= startY;
		}
		return el;
	}
	
	window.stayTopLeft=function()
	{
		if (verticalpos=="fromtop")
		{
			var pY = ns ? pageYOffset : iecompattest().scrollTop;
			ftlObj.y += (pY + startY - ftlObj.y)/8;
		}
		else
		{
			var pY = ns ? pageYOffset + innerHeight - barheight: iecompattest().scrollTop + iecompattest().clientHeight - barheight;
			ftlObj.y += (pY - startY - ftlObj.y)/8;
		}
		ftlObj.sP(ftlObj.x, ftlObj.y);
		setTimeout("stayTopLeft()", 10);
	}
	ftlObj = ml("topbar");
	stayTopLeft();
}

if (window.addEventListener)
window.addEventListener("load", staticbar, false)
else if (window.attachEvent)
window.attachEvent("onload", staticbar)
else if (document.getElementById)
window.onload=staticbar

</script>	
</head>
<?php
	$conta = mysql_query("SELECT * FROM members WHERE 1"); 
	$res = mysql_query("SELECT visite,totali FROM contatore WHERE pagina = $pagina");
	$membri = mysql_num_rows($conta);
	$visite = mysql_fetch_assoc($res);
?>

<body>
	<div id='intest_monitor'>
		<img src="images/lavagna.png" width="100%" />	
	</div>
	<div id='intest_m-login'>
		<?php include("m-login.php") ?>
	</div>
	
	<div id='intest_logo'>
		<a href="m-index.php">
			<img src="images/gm_logo.png" border="0" title="Vai alla Home page"/>
		</a>
	</div>
	
	
	<div id='intest_barra'>
		<?php include("m-barra_navigazione.php") ?>
	</div>
	<span id='intest_online'>
		<?php include ("m-useronline.php") ?>
	</span>
		
	<div id="topbar">
		<center>
		<table bgcolor="#333333" style="color:#FFFFFF;" border="0" align="center" width='80%'>
		<tr>
			<td width="30%" align="left">
			<?php echo "Visite Totali: ".$visite['totali'].", Singole: ".$visite['visite']." - Membri: ".$membri; ?>		
			</td>
			
			<td width="40%" align="right">
				Gold Manager - Copyright (&copy;) 2010 <a href="mailto:sergio.casizzone@poste.it">Sergio Casizzone</a>, All Rights Reserved
			</td>
		</tr>
		</table>
		</center>
	</div>
	
</body>
</html>

