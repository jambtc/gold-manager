<?php
	require_once('auth.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php 
	$quale_css = "index".$_SESSION['SESS_LARGHEZZA'].".css";
	echo "<style type='text/css'> @import 'css/$quale_css'; </style>";
?>
<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>


<script type="text/javascript">
function aggiorna(cosa) {
	
	arg1 = document.getElementById('ws_TipoFormazione').value ;
	arg3 = document.getElementById('ws_TipoTattica').value ;
	arg4 = document.getElementById('ws_TipoMarcatura').value ;
	
	arg2 = document.getElementById('ws_bonus').value ;
	arg5 = document.getElementById('ws_ffc').value ;	
	arg6 = document.getElementById('ws_ideale').value ;
	
	if (cosa == 2)
	{
		if (arg2 == "SI") { arg2 = "NO" ; }else{arg2 = "SI" ; }
	}
	
	if (cosa == 5)
	{
		if (arg5 == "SI") { arg5 = "NO" ; }else{arg5 = "SI" ; }
	}
	
	if (cosa == 6)
	{
		if (arg6 == "Checked") { arg6 = "" ; }else{arg6 = "Checked" ; }
	}
	
	
	indirizzo="team_auto_dx.php?formazione="+arg1+"&checkBn="+arg2+"&tattica="+arg3+"&marcatura="+arg4+"&checkAt="+arg5+"&ideale="+arg6;
	window.parent.frames['calc-dx'].location.href=indirizzo ;
}

function saveForm()
{
	formazione = document.getElementById('ws_formazione').value ;
	tattica = document.getElementById('ws_TipoTattica').value ;
	marcatura = document.getElementById('ws_TipoMarcatura').value ;
	riquadro = "";
	
	for (ii = 1; ii <= 64; ii++)
	{
		riquadro = riquadro + document.getElementById('ws_riq_'+ii).value +";";
	}
	indirizzo = "data_team_auto_salva.php?formazione="+formazione+"&riquadro="+riquadro+"&marcatura="+marcatura+"&tattica="+tattica;
	
	window.parent.frames['calc-dx'].location.href=indirizzo ;		

}
</script>
</head>

<body >


<?php 
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!! ASSEGNA LE POSIZIONI DEI GIOCATORI IN CAMPO !!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
function AssegnaPosizioni($formazione)
{
	switch ($formazione)
	{
		case "3-2-2-3 a":
			return array(64,51,53,55,32,39,23,27,9,11,13); 
			break;
		case "3-2-2-3 b":	
			return array(64,51,53,55,38,40,23,27,9,11,13); 
			break;
		case "3-2-2-3 c":	
			return array(64,51,53,55,29,35,24,26,9,11,13); 
			break;
		case "3-2-2-3 d":	
			return array(64,51,53,55,31,33,23,27,9,11,13); 
			break;
		case "3-2-2-3 e":	
			return array(64,51,53,55,32,39,23,27,11,10,12); 
			break;
		case "3-2-2-3 f":	
			return array(64,51,53,55,38,40,23,27,11,10,12); 
			break;
		case "3-2-2-3 g":	
			return array(64,51,53,55,29,35,24,26,11,10,12); 
			break;
		case "3-2-2-3 h":	
			return array(64,51,53,55,31,33,23,27,11,10,12); 
			break;
		case "3-2-2-3 i":	
			return array(64,51,53,55,32,39,23,27,10,4,12); 
			break;
		case "3-2-2-3 j":	
			return array(64,51,53,55,38,40,23,27,10,4,12); 
			break;
		case "3-2-2-3 k":	
			return array(64,51,53,55,29,35,24,26,10,4,12); 
			break;
		case "3-2-2-3 l":	
			return array(64,51,53,55,31,33,23,27,10,4,12); 
			break;
		case "3-3-1-3 a":	
			return array(64,51,53,55,39,25,29,35,9,11,13); 
			break;
		case "3-3-1-3 b":	
			return array(64,51,53,55,39,25,30,34,9,11,13); 
			break;
		case "3-3-1-3 c":	
			return array(64,51,53,55,37,41,32,25,9,11,13); 
			break;
		case "3-3-1-3 d":	
			return array(64,51,53,55,32,25,29,35,9,11,13); 
			break;
		case "3-3-1-3 e":	
			return array(64,51,53,55,32,25,30,34,9,11,13); 
			break;
		case "3-3-1-3 f":	
			return array(64,51,53,55,39,25,29,35,11,10,12); 
			break;
		case "3-3-1-3 g":	
			return array(64,51,53,55,39,25,30,34,11,10,12); 
			break;
		case "3-3-1-3 h":	
			return array(64,51,53,55,37,41,32,25,11,10,12); 
			break;
		case "3-3-1-3 i":	
			return array(64,51,53,55,32,25,29,35,11,10,12); 
			break;
		case "3-3-1-3 j":	
			return array(64,51,53,55,32,25,30,34,11,10,12); 
			break;
		case "3-3-1-3 k":	
			return array(64,51,53,55,32,39,29,35,10,4,12); 
			break;
		case "3-3-1-3 l":	
			return array(64,51,53,55,39,25,30,34,10,4,12); 
			break;
		case "3-3-1-3 m":	
			return array(64,51,53,55,37,41,32,25,10,4,12); 
			break;
		case "3-3-1-3 n":	
			return array(64,51,53,55,32,25,29,35,10,4,12); 
			break;
		case "3-3-1-3 o":	
			return array(64,51,53,55,32,25,30,34,10,4,12); 
			break;
		case "3-3-2-2 a":	
			return array(64,51,53,55,39,24,26,29,35,10,12); 
			break;
		case "3-3-2-2 b":	
			return array(64,51,53,55,39,30,34,24,26,10,12); 
			break;
		case "3-3-2-2 c":	
			return array(64,51,53,55,32,38,40,23,27,10,12); 
			break;
		case "3-4-1-2 a":	
			return array(64,51,53,55,39,32,25,29,35,10,12); 
			break;
		case "3-4-1-2 b":	
			return array(64,51,53,55,32,39,25,30,34,10,12); 
			break;
		case "3-4-1-2 c":	
			return array(64,51,53,55,38,40,25,29,35,10,12); 
			break;
		case "3-4-1-2 d":	
			return array(64,51,53,55,38,40,25,30,34,10,12); 
			break;
		case "3-4-2-1 a":	
			return array(64,50,53,46,56,32,24,26,29,35,11); 
			break;
		case "3-4-2-1 b":	
			return array(64,51,53,60,55,32,24,26,29,35,11); 
			break;
		case "3-4-2-1 c":	
			return array(64,51,53,55,38,40,17,19,29,35,11); 
			break;
		case "3-4-2-1 d":	
			return array(64,50,53,45,47,56,30,34,24,26,11); 
			break;
		case "3-4-3 a":	
			return array(64,51,53,55,32,39,29,35,9,11,13); 
			break;
		case "3-4-3 b":	
			return array(64,51,53,55,32,39,30,34,9,11,13); 
			break;
		case "3-4-3 c":	
			return array(64,51,53,55,38,40,29,35,9,11,13); 
			break;
		case "3-4-3 d":	
			return array(64,51,53,55,31,33,29,35,9,11,13); 
			break;
		case "3-4-3 e":	
			return array(64,51,53,55,31,33,30,34,9,11,13); 
			break;
		case "3-4-3 f":	
			return array(64,51,53,55,32,39,29,35,11,10,12); 
			break;
		case "3-4-3 g":	
			return array(64,51,53,55,32,39,30,34,11,10,12); 
			break;
		case "3-4-3 h":	
			return array(64,51,53,55,38,40,29,35,11,10,12); 
			break;
		case "3-4-3 i":	
			return array(64,51,53,55,31,33,29,35,11,10,12); 
			break;
		case "3-4-3 j":	
			return array(64,51,53,55,31,33,30,34,11,10,12); 
			break;
		case "3-4-3 k":	
			return array(64,51,53,55,32,39,29,35,10,4,12); 
			break;
		case "3-4-3 l":	
			return array(64,51,53,55,32,39,30,34,10,4,12); 
			break;
		case "3-4-3 m":	
			return array(64,51,53,55,38,40,29,35,10,4,12); 
			break;
		case "3-4-3 n":	
			return array(64,51,53,55,31,33,29,35,10,4,12); 
			break;
		case "3-4-3 o":	
			return array(64,51,53,55,31,33,30,34,10,4,12); 
			break;
		case "3-5-1-1 a":	
			return array(64,50,53,46,56,31,33,25,29,35,11); 
			break;
		case "3-5-1-1 b":	
			return array(64,50,53,39,56,31,33,4,29,35,11); 
			break;
		case "3-5-1-1 c":	
			return array(64,51,53,55,38,40,32,29,35,18,11); 
			break;
		case "3-5-1-1 d":	
			return array(64,51,53,45,47,55,32,25,29,35,11); 
			break;
		case "3-5-2 a":	
			return array(64,51,53,55,39,24,26,29,35,10,12); 
			break;
		case "3-5-2 b":	
			return array(64,51,53,55,39,24,26,23,27,10,12); 
			break;
		case "3-5-2 c":	
			return array(64,51,53,55,32,38,40,29,35,10,12); 
			break;
		case "3-5-2 d":	
			return array(64,51,53,55,32,38,40,30,34,10,12); 
			break;
		case "3-5-2 e":
			return array(64,51,53,55,29,32,31,33,35,12,10);
			break;	
		case "3-6-1 a":	
			return array(64,50,53,46,56,24,25,26,29,35,11); 
			break;
		case "3-6-1 b":	
			return array(64,51,53,45,47,55,24,26,29,35,11); 
			break;
		case "3-6-1 c":	
			return array(64,50,53,45,47,56,31,33,29,35,11); 
			break;
		case "4-1-2-3 a":	
			return array(64,43,52,54,49,32,24,26,11,10,12); 
			break;
		case "4-1-2-3 b":	
			return array(64,43,52,54,49,39,24,26,11,10,12); 
			break;
		case "4-1-2-3 c":	
			return array(64,43,52,54,49,25,31,33,11,10,12); 
			break;
		case "4-1-2-3 d":	
			return array(64,43,52,54,49,38,40,25,11,10,12); 
			break;
		case "4-1-2-3 e":	
			return array(64,43,52,54,49,32,31,33,11,10,12); 
			break;
		case "4-1-2-3 f":	
			return array(64,43,52,54,49,32,24,26,9,11,13); 
			break;
		case "4-1-2-3 g":	
			return array(64,43,52,54,49,39,24,26,9,11,13); 
			break;
		case "4-1-2-3 h":	
			return array(64,43,52,54,49,25,31,33,9,11,13);  
			break;
		case "4-1-2-3 i":	
			return array(64,43,52,54,49,38,40,25,9,11,13); 
			break;
		case "4-1-2-3 j":	
			return array(64,43,52,54,49,32,31,33,9,11,13); 
			break;
		case "4-1-2-3 k":	
			return array(64,43,52,54,49,32,24,26,11,3,5); 
			break;
		case "4-1-2-3 l":	
			return array(64,43,52,54,49,39,24,26,11,3,5); 
			break;
		case "4-1-2-3 m":	
			return array(64,43,52,54,49,25,31,33,11,3,5);  
			break;
		case "4-1-2-3 n":	
			return array(64,43,52,54,49,38,40,25,11,3,5);  
			break;
		case "4-1-2-3 o":	
			return array(64,43,52,54,49,32,31,33,11,3,5); 
			break;
		case "4-2-1-3 a":	
			return array(64,43,52,54,49,32,25,39,11,10,12); 
			break;
		case "4-2-1-3 b":	
			return array(64,43,52,54,49,38,40,25,11,10,12); 
			break;
		case "4-2-1-3 c":	
			return array(64,43,52,54,49,25,29,35,11,10,12); 
			break;
		case "4-2-1-3 d":	
			return array(64,43,52,54,49,31,33,25,11,10,12); 
			break;
		case "4-2-1-3 e":	
			return array(64,43,52,54,49,39,32,25,16,11,20); 
			break;
		case "4-2-1-3 f":	
			return array(64,43,52,54,49,38,40,25,2,11,6); 
			break;
		case "4-2-1-3 g":	
			return array(64,43,52,54,49,25,29,35,8,11,14); 
			break;
		case "4-2-1-3 h":	
			return array(64,43,52,54,49,31,33,25,2,11,6); 
			break;
		case "4-2-2-2 a":	
			return array(64,43,52,54,49,39,32,23,27,10,12); 
			break;
		case "4-2-2-2 b":	
			return array(64,43,52,54,49,38,40,23,27,10,12); 
			break;
		case "4-2-2-2 c":	
			return array(64,43,52,54,49,32,25,22,28,10,12); 
			break;
		case "4-2-2-2 d":	
			return array(64,43,52,54,49,39,25,37,41,10,12); 
			break;
		case "4-3-1-2 a":	
			return array(64,43,52,54,49,39,25,29,35,10,12); 
			break;
		case "4-3-1-2 b":	
			return array(64,43,52,54,49,39,28,30,34,10,12); 
			break;
		case "4-3-1-2 c":	
			return array(64,43,52,54,49,38,40,25,18,10,12); 
			break;
		case "4-3-1-2 d":	
			return array(64,43,52,54,49,31,33,22,28,10,12); 
			break;
		case "4-3-2-1 a":	
			return array(64,43,52,54,49,39,29,35,24,26,11); 
			break;
		case "4-3-2-1 b":	
			return array(64,43,52,54,49,39,31,33,23,27,11); 
			break;
		case "4-3-2-1 c":	
			return array(64,43,52,54,49,38,40,32,23,27,11); 
			break;
		case "4-3-2-1 d":	
			return array(64,43,52,54,49,39,24,26,29,35,11); 
			break;
		case "4-3-3 a":	
			return array(64,43,52,54,49,32,22,28,11,10,12); 
			break;
		case "4-3-3 b":	
			return array(64,43,52,54,49,39,23,27,11,10,12); 
			break;
		case "4-3-3 c":	
			return array(64,43,52,54,49,32,38,40,11,10,12); 
			break;
		case "4-3-3 d":	
			return array(64,43,52,54,49,39,22,28,11,10,12); 
			break;
		case "4-3-3 e":	
			return array(64,43,52,54,49,31,32,33,11,10,12); 
			break;
		case "4-3-3 f":	
			return array(64,43,52,54,49,32,22,28,9,11,13); 
			break;
		case "4-3-3 g":	
			return array(64,43,52,54,49,39,23,27,11,3,5); 
			break;
		case "4-3-3 h":	
			return array(64,43,52,54,49,32,38,40,9,11,13); 
			break;
		case "4-3-3 i":	
			return array(64,43,52,54,49,39,22,28,16,11,20); 
			break;
		case "4-3-3 j":	
			return array(64,43,52,54,49,31,32,33,9,11,13); 
			break;
		case "4-4-1-1 a":	
			return array(64,43,52,54,49,39,32,25,29,35,11); 
			break;
		case "4-4-1-1 b":	
			return array(64,43,52,54,49,39,32,25,22,28,11); 
			break;
		case "4-4-1-1 c":	
			return array(64,43,52,54,49,38,40,25,29,35,11); 
			break;
		case "4-4-1-1 d":	
			return array(64,43,52,54,49,38,40,25,30,34,11); 
			break;
		case "4-4-1-1 e":	
			return array(64,43,52,54,49,38,39,40,22,28,11); 
			break;
		case "4-4-2 a":	
			return array(64,43,52,54,49,39,32,22,28,10,12); 
			break;
		case "4-4-2 b":	
			return array(64,43,52,54,49,39,32,29,35,10,12); 
			break;
		case "4-4-2 c":	
			return array(64,43,52,54,49,38,40,22,28,10,12); 
			break;
		case "4-4-2 d":	
			return array(64,43,52,54,49,24,26,29,35,10,12); 
			break;
		case "4-4-2 e":	
			return array(64,43,52,54,49,31,33,23,27,10,12); 
			break;
		case "4-4-2 f":	
			return array(64,43,52,54,49,31,33,29,35,10,12); 
			break;
		case "4-5-1 a":	
			return array(64,43,52,54,49,39,31,33,22,28,11); 
			break;
		case "4-5-1 b":	
			return array(64,43,52,54,49,39,24,26,22,28,11); 
			break;
		case "4-5-1 c":	
			return array(64,43,52,54,49,38,40,25,22,28,11); 
			break;
		case "4-5-1 d":	
			return array(64,43,52,54,49,38,40,32,30,34,11); 
			break;
		case "4-5-1 e":	
			return array(64,43,52,54,49,38,39,40,23,27,11); 
			break;
		case "4-5-1 f":
			return array(64,50,52,54,56,29,32,31,33,35,11);
			break;
		case "5-1-2-2 a":	
			return array(64,43,52,53,54,49,39,23,27,10,12); 
			break;
		case "5-1-2-2 b":	
			return array(64,43,52,53,54,49,32,22,28,11,4); 
			break;
		case "5-1-2-2 c":	
			return array(64,50,52,53,54,56,39,23,27,10,12); 
			break;
		case "5-1-2-2 d":	
			return array(64,50,52,53,54,56,32,22,28,11,4); 
			break;
		case "5-1-2-2 e":	
			return array(64,50,60,52,54,56,39,23,27,10,12); 
			break;
		case "5-1-2-2 f":	
			return array(64,50,60,52,54,56,32,22,28,11,4); 
			break;
		case "5-2-1-2 a":	
			return array(64,43,52,53,54,49,39,29,35,11,4); 
			break;
		case "5-2-1-2 b":	
			return array(64,43,52,53,54,49,37,41,32,10,12); 
			break;
		case "5-2-1-2 c":	
			return array(64,43,52,53,54,49,22,25,28,10,12); 
			break;
		case "5-2-1-2 d":	
			return array(64,43,52,53,54,49,37,41,25,10,12); 
			break;
		case "5-2-1-2 e":	
			return array(64,43,52,53,54,49,37,41,25,11,4); 
			break;
		case "5-2-1-2 f":	
			return array(64,43,52,53,54,49,36,42,32,10,12); 
			break;
		case "5-2-1-2 g":	
			return array(64,43,52,53,54,49,36,42,32,11,4); 
			break;
		case "5-2-1-2 h":	
			return array(64,50,52,53,54,56,39,29,35,11,4); 
			break;
		case "5-2-1-2 i":	
			return array(64,50,52,53,54,56,37,41,32,10,12); 
			break;
		case "5-2-1-2 j":	
			return array(64,50,52,53,54,56,22,25,28,10,12); 
			break;
		case "5-2-1-2 k":	
			return array(64,50,52,53,54,56,37,41,25,10,12); 
			break;
		case "5-2-1-2 l":	
			return array(64,50,52,53,54,56,37,41,25,11,4); 
			break;
		case "5-2-1-2 m":	
			return array(64,50,52,53,54,56,36,42,32,10,12); 
			break;
		case "5-2-1-2 n":	
			return array(64,50,52,53,54,56,36,42,32,11,4); 
			break;
		case "5-2-1-2 o":	
			return array(64,50,60,52,54,56,39,29,35,11,4); 
			break;
		case "5-2-1-2 p":	
			return array(64,50,60,52,54,56,37,41,32,10,12); 
			break;
		case "5-2-1-2 q":	
			return array(64,50,60,52,54,56,22,25,28,10,12); 
			break;
		case "5-2-1-2 r":	
			return array(64,50,60,52,54,56,37,41,25,10,12); 
			break;
		case "5-2-1-2 s":	
			return array(64,50,60,52,54,56,37,41,25,11,4); 
			break;
		case "5-2-1-2 t":	
			return array(64,50,60,52,54,56,36,42,32,10,12); 
			break;
		case "5-2-1-2 u":	
			return array(64,50,60,52,54,56,36,42,32,11,4); 
			break;
		case "5-2-2-1 a":	
			return array(64,43,52,53,54,49,32,25,23,27,11); 
			break;
		case "5-2-2-1 b":	
			return array(64,43,52,53,54,49,31,33,23,27,11); 
			break;
		case "5-2-2-1 c":	
			return array(64,43,52,53,54,49,32,25,23,27,11); 
			break;
		case "5-2-2-1 d":	
			return array(64,43,52,53,54,49,31,33,23,27,11); 
			break;
		case "5-2-2-1 e":	
			return array(64,50,52,53,54,56,32,25,23,27,11); 
			break;
		case "5-2-2-1 f":	
			return array(64,50,52,53,54,56,31,33,23,27,11); 
			break;
		case "5-2-2-1 g":	
			return array(64,50,52,53,54,56,32,25,23,27,11); 
			break;
		case "5-2-2-1 h":	
			return array(64,50,52,53,54,56,31,33,23,27,11); 
			break;
		case "5-2-2-1 i":	
			return array(64,50,60,52,54,56,32,25,23,27,11); 
			break;
		case "5-2-2-1 j":	
			return array(64,50,60,52,54,56,31,33,23,27,11); 
			break;
		case "5-2-2-1 k":	
			return array(64,50,60,52,54,56,32,25,23,27,11); 
			break;
		case "5-2-2-1 l":	
			return array(64,50,60,52,54,56,31,33,23,27,11); 
			break;
		case "5-3-1-1 a":	
			return array(64,43,52,53,54,49,32,25,29,35,11); 
			break;
		case "5-3-1-1 b":	
			return array(64,43,52,53,54,49,32,39,30,34,11); 
			break;
		case "5-3-1-1 c":	
			return array(64,43,52,53,54,49,32,25,37,41,11); 
			break;
		case "5-3-1-1 d":	
			return array(64,43,52,53,54,49,32,25,29,35,11); 
			break;
		case "5-3-1-1 e":	
			return array(64,43,52,53,54,49,32,39,30,34,11); 
			break;
		case "5-3-1-1 f":	
			return array(64,43,52,53,54,49,32,25,37,41,11); 
			break;
		case "5-3-1-1 g":	
			return array(64,50,52,53,54,56,32,25,29,35,11); 
			break;
		case "5-3-1-1 h":	
			return array(64,50,52,53,54,56,32,39,30,34,11); 
			break;
		case "5-3-1-1 i":	
			return array(64,50,52,53,54,56,32,25,37,41,11); 
			break;
		case "5-3-1-1 j":	
			return array(64,51,52,53,54,55,32,25,29,35,11); 
			break;
		case "5-3-1-1 k":	
			return array(64,50,52,53,54,56,32,39,30,34,11); 
			break;
		case "5-3-1-1 l":	
			return array(64,50,52,53,54,56,32,25,37,41,11); 
			break;
		case "5-3-1-1 m":	
			return array(64,50,60,52,54,56,32,25,29,35,11); 
			break;
		case "5-3-1-1 n":	
			return array(64,50,60,52,54,56,32,39,30,34,11); 
			break;
		case "5-3-1-1 o":	
			return array(64,50,60,52,54,56,32,25,37,41,11); 
			break;
		case "5-3-1-1 p":	
			return array(64,50,60,52,54,56,32,25,29,35,11); 
			break;
		case "5-3-1-1 q":	
			return array(64,50,60,52,54,56,32,39,30,34,11); 
			break;
		case "5-3-1-1 r":	
			return array(64,50,60,52,54,56,32,25,37,41,11); 
			break;
		case "5-3-2 a":	
			return array(64,43,52,53,54,49,39,29,35,10,12); 
			break;
		case "5-3-2 b":	
			return array(64,43,52,53,54,49,32,30,34,10,12); 
			break;
		case "5-3-2 c":	
			return array(64,43,52,53,54,49,37,41,32,11,4); 
			break;
		case "5-3-2 d":	
			return array(64,43,52,53,54,49,32,23,27,10,12); 
			break;
		case "5-3-2 e":	
			return array(64,43,52,53,54,49,25,31,33,10,12); 
			break;
		case "5-3-2 f":	
			return array(64,43,52,53,54,49,25,31,33,11,4); 
			break;
		case "5-3-2 g":	
			return array(64,43,52,53,54,49,32,30,34,11,4); 
			break;
		case "5-3-2 h":	
			return array(64,50,52,53,54,56,39,29,35,10,12); 
			break;
		case "5-3-2 i":	
			return array(64,50,52,53,54,56,32,30,34,10,12); 
			break;
		case "5-3-2 j":	
			return array(64,50,52,53,54,56,37,41,32,11,4); 
			break;
		case "5-3-2 k":	
			return array(64,50,52,53,54,56,32,23,27,10,12); 
			break;
		case "5-3-2 l":	
			return array(64,50,52,53,54,56,32,31,33,10,12); 
			break;
		case "5-3-2 m":	
			return array(64,50,52,53,54,56,32,31,33,11,4); 
			break;
		case "5-3-2 n":	
			return array(64,50,52,53,54,56,32,30,34,11,4); 
			break;
		case "5-3-2 o":	
			return array(64,50,60,52,54,56,39,29,35,10,12); 
			break;
		case "5-3-2 p":	
			return array(64,50,60,52,54,56,32,30,34,10,12); 
			break;
		case "5-3-2 q":	
			return array(64,50,60,52,54,56,37,41,32,11,4); 
			break;
		case "5-3-2 r":	
			return array(64,50,60,52,54,56,32,23,27,10,12); 
			break;
		case "5-3-2 s":	
			return array(64,50,60,52,54,56,32,31,33,10,12); 
			break;
		case "5-3-2 t":	
			return array(64,50,60,52,54,56,32,31,33,11,4); 
			break;
		case "5-3-2 u":	
			return array(64,50,60,52,54,56,32,30,34,11,4); 
			break;
		case "5-4-1 a":	
			return array(64,43,52,53,54,49,32,25,22,28,11); 
			break;
		case "5-4-1 b":	
			return array(64,43,52,53,54,49,32,39,23,27,11); 
			break;
		case "5-4-1 c":	
			return array(64,43,52,53,54,49,31,33,22,28,11); 
			break;
		case "5-4-1 d":	
			return array(64,43,52,53,54,49,38,40,30,34,11); 
			break;
		case "5-4-1 e":	
			return array(64,43,52,53,54,49,32,25,22,28,11); 
			break;
		case "5-4-1 f":	
			return array(64,43,52,53,54,49,32,39,23,27,11); 
			break;
		case "5-4-1 g":	
			return array(64,43,52,53,54,49,31,33,22,28,11); 
			break;
		case "5-4-1 h":	
			return array(64,43,52,53,54,49,38,40,30,34,11); 
			break;
		case "5-4-1 i":	
			return array(64,50,52,53,54,56,32,25,22,28,11); 
			break;
		case "5-4-1 j":	
			return array(64,50,52,53,54,56,32,39,23,27,11); 
			break;
		case "5-4-1 k":	
			return array(64,50,52,53,54,56,31,33,22,28,11); 
			break;
		case "5-4-1 l":	
			return array(64,50,52,53,54,56,38,40,30,34,11); 
			break;
		case "5-4-1 m":	
			return array(64,50,52,53,54,56,32,25,22,28,11); 
			break;
		case "5-4-1 n":	
			return array(64,50,52,53,54,56,32,39,23,27,11); 
			break;
		case "5-4-1 o":	
			return array(64,50,52,53,54,56,31,33,22,28,11); 
			break;
		case "5-4-1 p":	
			return array(64,50,52,53,54,56,38,40,30,34,11); 
			break;
		case "5-4-1 q":	
			return array(64,50,60,52,54,56,32,25,22,28,11); 
			break;
		case "5-4-1 r":	
			return array(64,50,60,52,54,56,32,39,23,27,11); 
			break;
		case "5-4-1 s":	
			return array(64,50,60,52,54,56,31,33,22,28,11); 
			break;
		case "5-4-1 t":	
			return array(64,50,60,52,54,56,38,40,30,34,11); 
			break;
		case "5-4-1 u":	
			return array(64,50,60,52,54,56,32,25,22,28,11); 
			break;
		case "5-4-1 v":	
			return array(64,50,60,52,54,56,32,39,23,27,11); 
			break;
		case "5-4-1 w":	
			return array(64,50,60,52,54,56,31,33,22,28,11); 
			break;

	
	}
}
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

include "connect_db.php";	
$nome_team = $_SESSION['SESS_TEAM'];

$result = mysql_query("SELECT * FROM giocatori WHERE id_team=\"$nome_team\"");

		if (!$result) {
		    echo 'Errore nella query: ' . mysql_error();
		    exit();
		}
		$totale = mysql_num_rows($result);
		if ($totale != 0)
		{
			while ($rig = mysql_fetch_array($result))
			{
				$nome[] = $rig['nome'];
				$nr[] = $rig['nr'];
				$wskill[] = $rig['skill'];
				$wforma[] = $rig['forma'];
				$wfresc[] = $rig['fresc'];
				$wcond[] = $rig['cond'];
				$wpo[] = $rig['po'];
				$wdf[] = $rig['df'];
				$wcn[] = $rig['cn'];
				$wpa[] = $rig['pa'];
				$wrg[] = $rig['rg'];
				$wcr[] = $rig['cr'];
				$wtc[] = $rig['tc'];
				$wtr[] = $rig['tr'];
				$wes[] = $rig['esp'];
				$wruolo[] = $rig['pos'];
				$wpd[] = strtoupper($rig['piede']);
				$wcarattere[] = $rig['carattere'];
				$winfortunio[] = $rig['infortunio'];
			}
			// TOTALE GIOCATORI
			$totgio = mysql_num_rows($result);
			$formula = "Formula 2";
			$scelta_formazione = 0;
			$ffcFres = 100;
			$ffcForm = 100;
			$ffcCond = 100;

			$formTattica[0] = 0;
			$formTattica[1] = 0;
			$formTattica[2] = 0;

			if (isset($_REQUEST['formazione']))	{$formazione = $_REQUEST['formazione'];$scelta_formazione = 1;}
			
			if (isset($_REQUEST['checkAt']))	{$BonusFfc = $_REQUEST['checkAt'];}	else	{$BonusFfc = "NO";}
			if (isset($_REQUEST['ideale']))		{$Ideale = $_REQUEST['ideale'];}	else	{$Ideale = "Checked";}
			
			if ($BonusFfc == "SI")	{$checkAt = "Checked";}	else	{$checkAt = "";}
			
			$qu0_val[0] = 0;
			$qu1_val[0] = 0;
			$qu2_val[0] = 0;
			$qu3_val[0] = 0;
			$qu4_val[0] = 0;
			$qu5_val[0] = 0;
			$qu6_val[0] = 0;
			$qu7_val[0] = 0;
			$qu8_val[0] = 0;
			$qu9_val[0] = 0;			
			
			$qu0_nom[0] = "";
			$qu1_nom[0] = "";
			$qu2_nom[0] = "";
			$qu3_nom[0] = "";
			$qu4_nom[0] = "";
			$qu5_nom[0] = "";
			$qu6_nom[0] = "";
			$qu7_nom[0] = "";
			$qu8_nom[0] = "";
			$qu9_nom[0] = "";
			
						
			$ListaFormazione = array("X","3-2-2-3 a","3-2-2-3 b","3-2-2-3 c","3-2-2-3 d","3-2-2-3 e","3-2-2-3 f","3-2-2-3 g","3-2-2-3 h","3-2-2-3 i","3-2-2-3 j","3-2-2-3 k","3-2-2-3 l","3-3-1-3 a","3-3-1-3 b","3-3-1-3 c","3-3-1-3 d","3-3-1-3 e","3-3-1-3 f","3-3-1-3 g","3-3-1-3 h","3-3-1-3 i","3-3-1-3 j","3-3-1-3 k","3-3-1-3 l","3-3-1-3 m","3-3-1-3 n","3-3-1-3 o","3-3-2-2 a","3-3-2-2 b","3-3-2-2 c","3-4-1-2 a","3-4-1-2 b","3-4-1-2 c","3-4-1-2 d","3-4-2-1 a","3-4-2-1 b","3-4-2-1 c","3-4-2-1 d","3-4-3 a","3-4-3 b","3-4-3 c","3-4-3 d","3-4-3 e","3-4-3 f","3-4-3 g","3-4-3 h","3-4-3 i","3-4-3 j","3-4-3 k","3-4-3 l","3-4-3 m","3-4-3 n","3-4-3 o","3-5-1-1 a","3-5-1-1 b","3-5-1-1 c","3-5-1-1 d","3-5-2 a","3-5-2 b","3-5-2 c","3-5-2 d","3-5-2 e","3-6-1 a","3-6-1 b","3-6-1 c","4-1-2-3 a","4-1-2-3 b","4-1-2-3 c","4-1-2-3 d","4-1-2-3 e","4-1-2-3 f","4-1-2-3 g","4-1-2-3 h","4-1-2-3 i","4-1-2-3 j","4-1-2-3 k","4-1-2-3 l","4-1-2-3 m","4-1-2-3 n","4-1-2-3 o","4-2-1-3 a","4-2-1-3 b","4-2-1-3 c","4-2-1-3 d","4-2-1-3 e","4-2-1-3 f","4-2-1-3 g","4-2-1-3 h","4-2-2-2 a","4-2-2-2 b","4-2-2-2 c","4-2-2-2 d","4-3-1-2 a","4-3-1-2 b","4-3-1-2 c","4-3-1-2 d","4-3-2-1 a","4-3-2-1 b","4-3-2-1 c","4-3-2-1 d","4-3-3 a","4-3-3 b","4-3-3 c","4-3-3 d","4-3-3 e","4-3-3 f","4-3-3 g","4-3-3 h","4-3-3 i","4-3-3 j","4-4-1-1 a","4-4-1-1 b","4-4-1-1 c","4-4-1-1 d","4-4-1-1 e","4-4-2 a","4-4-2 b","4-4-2 c","4-4-2 d","4-4-2 e","4-4-2 f","4-5-1 a","4-5-1 b","4-5-1 c","4-5-1 d","4-5-1 e","4-5-1 f","5-1-2-2 a","5-1-2-2 b","5-1-2-2 c","5-1-2-2 d","5-1-2-2 e","5-1-2-2 f","5-2-1-2 a","5-2-1-2 b","5-2-1-2 c","5-2-1-2 d","5-2-1-2 e","5-2-1-2 f","5-2-1-2 g","5-2-1-2 h","5-2-1-2 i","5-2-1-2 j","5-2-1-2 k","5-2-1-2 l","5-2-1-2 m","5-2-1-2 n","5-2-1-2 o","5-2-1-2 p","5-2-1-2 q","5-2-1-2 r","5-2-1-2 s","5-2-1-2 t","5-2-1-2 u","5-2-2-1 a","5-2-2-1 b","5-2-2-1 c","5-2-2-1 d","5-2-2-1 e","5-2-2-1 f","5-2-2-1 g","5-2-2-1 h","5-2-2-1 i","5-2-2-1 j","5-2-2-1 k","5-2-2-1 l","5-3-1-1 a","5-3-1-1 b","5-3-1-1 c","5-3-1-1 d","5-3-1-1 e","5-3-1-1 f","5-3-1-1 g","5-3-1-1 h","5-3-1-1 i","5-3-1-1 j","5-3-1-1 k","5-3-1-1 l","5-3-1-1 m","5-3-1-1 n","5-3-1-1 o","5-3-1-1 p","5-3-1-1 q","5-3-1-1 r","5-3-2 a","5-3-2 b","5-3-2 c","5-3-2 d","5-3-2 e","5-3-2 f","5-3-2 g","5-3-2 h","5-3-2 i","5-3-2 j","5-3-2 k","5-3-2 l","5-3-2 m","5-3-2 n","5-3-2 o","5-3-2 p","5-3-2 q","5-3-2 r","5-3-2 s","5-3-2 t","5-3-2 u","5-4-1 a","5-4-1 b","5-4-1 c","5-4-1 d","5-4-1 e","5-4-1 f","5-4-1 g","5-4-1 h","5-4-1 i","5-4-1 j","5-4-1 k","5-4-1 l","5-4-1 m","5-4-1 n","5-4-1 o","5-4-1 p","5-4-1 q","5-4-1 r","5-4-1 s","5-4-1 t","5-4-1 u","5-4-1 v","5-4-1 w");
			
			
			// CREO ELENCO DELLE TATTICHE E MARCATURE
			$tattica_result = mysql_query("SELECT * FROM bonus_tattica WHERE 1 ORDER BY t_id");
			if (!$tattica_result)
			{
				echo 'Errore nella query bonus tattica: ' . mysql_error();
				exit();
			}
			$conta = 1;
			while   ($row   =   mysql_fetch_array($tattica_result))
			{
				$ListaTattica[$conta] = $row['t_descrizione'];
				
				//array contenente bonus tattiche e marcature
				$Bonus[$ListaTattica[$conta]] = array($row['t_id'],$row['q1'],$row['q2'],$row['q3'],$row['q4'],$row['q5'],
													  $row['q6'],$row['q7'],$row['q8'],$row['q9']);
				$conta++;
			}
			if (isset($_REQUEST['tattica']))
			{
				$TipoTattica = $_REQUEST['tattica'];
				$TipoMarcatura = $_REQUEST['marcatura'];
			}
			else
			{
				// CARICO LA TATTICA E LA MARCATURA DEL TEAM
				$tipo_result = mysql_query("SELECT * FROM tattica WHERE t_id_team=\"$nome_team\"");
				if (!$tipo_result) {
					echo 'Errore nella query tattica: ' . mysql_error();
					exit();
				}
				$row   =   mysql_fetch_array($tipo_result);
				$TipoTattica = $row['t_tattica'];
				$TipoMarcatura = $row['t_marcatura'];
				$TipoBonus = $row['t_bonus'];
			}	
			// CARICO L'EFFICIENZA DEGLI ALLENATORI
			$all_result = mysql_query("SELECT * FROM staff WHERE s_id_team=\"$nome_team\" ");
			if (!$all_result)
			{
				echo 'Errore nella query allenatore: ' . mysql_error();
				exit();
			}
			
			while ($row   =   mysql_fetch_array($all_result))
			{
				$effic = (0.9 * $row['s_abi'] * $row['s_mot']) / 100 + $row['s_esp']/8;
				switch ($row['s_descrizione'])
				{
					case "Allenatore":
						$b_allenatore = 0.3 * $effic ;
						$filosofia = $row['s_fil'];
						break;
					case "Vice Allenatore":
						$b_viceallena = $effic / 30.3 ;
						break;
					case "Allenatore Portieri":
						$b_alleportie = 0.035 * $effic ;
						break;
				}
			}
			if (!isset($TipoTattica))		{$TipoTattica = "Nessuna";}
			if (!isset($TipoMarcatura))		{$TipoMarcatura = "Marcatura a uomo";}
			if (isset($_REQUEST['checkBn']))	{$TipoBonus = $_REQUEST['checkBn'];}	else	{$TipoBonus = "NO";}
			if ($TipoBonus == "SI")	{$checkBn = "Checked";}	else	{$checkBn = "";}

			if (!isset($filosofia))		{$filosofia = "bilanciato";}
			if (!isset($b_allenatore))	{$b_allenatore = 0;}
			if (!isset($b_viceallena))	{$b_viceallena = 0;}
			if (!isset($b_alleportie))	{$b_alleportie = 0;}
			
			// CARICO TABELLA BONUS ALLENATORE
			$all_result = mysql_query("SELECT * FROM bonus_allenatore WHERE b_descrizione = \"$filosofia\"");
			if (!$all_result)
			{
				echo 'Errore nella query bonus allenatore: ' . mysql_error();
				exit();
			}
			$row = mysql_fetch_array($all_result);
			$bonus_allenatore = array($row['b_dif'],$row['b_cen'],$row['b_att']);
			
			// CARICO L'ALLENAMENTO DELLE TATTICHE 
			$result_allena = mysql_query("SELECT * FROM allena_tattiche WHERE a_id_team=\"$nome_team\"");
			if (!$result_allena)
			{
				echo 'Errore nella query allena tattiche: ' . mysql_error();
				exit();
			}
			$riga_tattica = mysql_fetch_array($result_allena) ;
			if (count($riga_tattica != 0))
			{	
				$aggiorna["Nessuna"] = 70;  // abbassata da 80  a 70 perchè mi sembrava + corretto così... (da valutare ancora)
				$aggiorna["Pressing"] = round($riga_tattica['ta_press_val']/327*100,1);
				$aggiorna["Contropiede"] = round($riga_tattica['ta_contr_val']/327*100,1);
				$aggiorna["Possesso palla"] = round($riga_tattica['ta_poss_val']/327*100,1);
				$aggiorna["Pallone lungo"] = round($riga_tattica['ta_pall_val']/327*100,1);
				$aggiorna["Gioco sulle fasce"] = round($riga_tattica['ta_gioc_val']/327*100,1);
				$aggiorna["Catenaccio"] = round($riga_tattica['ta_cate_val']/327*100,1);
			}
			//creao array contenente numeri da 1 a 64
			for ($xx = 1; $xx < 65; $xx++)
			{
				$box_numeri[] = $xx;
			}
			
			// LISTA ORDINATA PER I MIGLIORI valori
			$conta = 0;
			while ($conta < $totgio) 
			{
				if ($winfortunio[$conta] < 1)
				{
					$controllo = 15; // valore dell'esperienza
					if ($BonusFfc == "SI")
					{ 
						$ffcFres = $wfresc[$conta] + 20;
						$ffcForm = $wforma[$conta] + 20;
						$ffcCond = $wcond[$conta] + 20;
					}
	
					if ($ffcForm > 100) { $ffcForm = 100; }
					if ($ffcFres > 100) { $ffcFres = 100; }
					if ($ffcCond > 100) { $ffcCond = 100; }
					
					// CARICO I DATI DAL CALCOLATORE
					$calc_result = mysql_query("SELECT * FROM calcolatore WHERE formula=\"$formula\" ORDER BY ord");
					if (!$calc_result) { echo 'Errore nella query calcolatore: ' . mysql_error(); exit(); }
					
					unset($box);
					unset($val);
					
					$max = 0;
					$ii = 1; 
					while   ($riga   =   mysql_fetch_array($calc_result)) //step sulla tabella calcolatore
					{
						// CONTROLLA TUTTI I CASI DELLE CASELLE DESTRA CENTRO E SINISTRA, PER IL CALCOLO DEL PIEDE r, l E lr
						switch ($ii)
						{ 
							//qsinistro
							case 1:
							case 2:
							case 8:
							case 9:
							case 15:
							case 16:
							case 22:
							case 23:
							case 29:
							case 30:
							case 36:
							case 37:
							case 43:
							case 44:
							case 50:
							case 51:
							case 57:
							case 58:
								if ($wpd[$conta] == "R")  {$pdd = -6;}
								elseif ($wpd[$conta] == "L") {$pdd = 6;}
								else{$pdd = 4;}
								break;
							//qcentrale
							case 3:
							case 4:
							case 5:
							case 10:
							case 11:
							case 12:
							case 17:
							case 18:
							case 19:
							case 24:
							case 25:
							case 26:
							case 31:
							case 32:
							case 33:
							case 38:
							case 39:
							case 40:
							case 45:
							case 46:
							case 47:
							case 52:
							case 53:
							case 54:
							case 59:
							case 60:				
							case 61:
								if ($wpd[$conta] == "R")  {$pdd = 4;}
								elseif ($wpd[$conta] == "L") {$pdd = 4;}
								else{$pdd = 7;}
								break;
							//qdestro
							case 6:
							case 7:
							case 13:
							case 14:
							case 20:
							case 21:
							case 27:
							case 28:
							case 34:
							case 35:
							case 41:
							case 42:
							case 48:
							case 49:
							case 55:
							case 56:
							case 62:
							case 63:
								if ($wpd[$conta] == "R")  {$pdd = 6;}
								elseif ($wpd[$conta] == "L") {$pdd = -6;}
								else{$pdd = 4;}
								break;
								
							//q0	
							case 64:
								$pdd = 4;
								break;
						} // end switch
						
				$riquadro[$ii] = $pdd + $wpo[$conta]*$riga['po'] + $wdf[$conta]*$riga['df'] + $wcn[$conta]*$riga['cn'];
				$riquadro[$ii] = $riquadro[$ii] + $wpa[$conta]*$riga['pa'] + $wrg[$conta]*$riga['rg'] + $wcr[$conta]*$riga['cr'];
				$riquadro[$ii] = $riquadro[$ii] + $wtc[$conta]*$riga['tc'] + $wtr[$conta]*$riga['tr'];
						$riquadro[$ii] = $riquadro[$ii] / (101-$controllo) ;
						
						$riq1 = $riquadro[$ii] / 100 * $ffcForm ;
						$riq2 = $riquadro[$ii] / 100 * $ffcFres ;
						$riq3 = $riquadro[$ii] / 100 * $ffcCond ;
						$riquadro[$ii] = round(($riq1 + $riq2 + $riq3)/3,1);
						
						$ii ++ ;
					} // end while controllo su calcolatore
					
								
				
					$wgiocatore = array($riquadro,$box_numeri);
					// ASSEGNO I VALORI OTTENUTI AD UN ARRAY, COSì DA NON RIPETERE LA QUERY SUL DATABASE DEL CALCOLATORE 
					// E VELOCIZZARE LE OPERAZIONI DI CALCOLO
					$valore_giocatore[$nr[$conta]] = $riquadro;
					
					array_multisort($wgiocatore[0], SORT_DESC, $wgiocatore[1], SORT_DESC);
					// controlla i migliori 10 piazzamenti in campo di ciascun giocatore
					for ($ix = 0; $ix <=10; $ix ++)
					{
						//echo $nome[$conta]." ".$wgiocatore[1][$ix]." ".$wgiocatore[0][$ix];
						// CONTROLLA TUTTI I CASI DELLE CASELLE
						switch ($wgiocatore[1][$ix])
						{
							case 1:
							case 2:
							case 8:
							case 9:
							case 15:
							case 16:
								 //attacco sinistro Q7
								 if (!in_array($nome[$conta],$qu7_nom))
								 {
										if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu7_val[] = $wgiocatore[0][$ix];
											$qu7_nom[] = $nome[$conta];
										}
								 }
								break;
							case 3:
							case 4:
							case 5:
							case 10:
							case 11:
							case 12:
							case 17:
							case 18:
							case 19:
								if (!in_array($nome[$conta],$qu8_nom))
								{
									$qu8_val[] = $wgiocatore[0][$ix];
									$qu8_nom[] = $nome[$conta]; // attacco centrale
								}
								break;
							case 6:
							case 7:
							case 13:
							case 14:
							case 20:
							case 21:
								// attacco destro Q9
									if (!in_array($nome[$conta],$qu9_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu9_val[] = $wgiocatore[0][$ix];
											$qu9_nom[] = $nome[$conta]; 
										}
									}
								break;
							case 22:
							case 23:
							case 29:
							case 30:
							case 36:
							case 37:
								//centrocampo sinistra Q4
									if (!in_array($nome[$conta],$qu4_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu4_val[] = $wgiocatore[0][$ix];
											$qu4_nom[] = $nome[$conta]; 
										}
									}
								break;
							case 24:
							case 25:
							case 26:
							case 31:
							case 32:
							case 33:
							case 38:
							case 39:
							case 40:
								if (!in_array($nome[$conta],$qu5_nom))
								{
									$qu5_val[] = $wgiocatore[0][$ix];
									$qu5_nom[] = $nome[$conta]; //centrocampo centrale
								}
								break;
							case 27:
							case 28:
							case 34:
							case 35:
							case 41:
							case 42:
								if (!in_array($nome[$conta],$qu6_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu6_val[] = $wgiocatore[0][$ix];
											$qu6_nom[] = $nome[$conta]; //centrocampo destra Q6
										}
									}
								break;
							case 43:
							case 44:
							case 50:
							case 51:
							case 57:
							case 58:
								if (!in_array($nome[$conta],$qu1_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "S" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu1_val[] = $wgiocatore[0][$ix];
											$qu1_nom[] = $nome[$conta]; //difesa sinistra Q1
										}
									}
								break;
							case 45:
							case 46:
							case 47:
							case 52:
							case 53:
							case 54:
							case 59:
							case 60:				
							case 61:
								if (!in_array($nome[$conta],$qu2_nom))
								{
									$qu2_val[] = $wgiocatore[0][$ix];
									$qu2_nom[] = $nome[$conta]; //difesa centrale
								}
								break;
							case 48:
							case 49:
							case 55:
							case 56:
							case 62:
							case 63:
								if (!in_array($nome[$conta],$qu3_nom))
									{
										if (substr($wruolo[$conta],-1,1) == "D" or substr($wruolo[$conta],-1,1) == "X")
										{
											$qu3_val[] = $wgiocatore[0][$ix];
											$qu3_nom[] = $nome[$conta]; //difesa destra Q3
										}
									}
								break;
							case 64:
								if (!in_array($nome[$conta],$qu0_nom))
								{
									$qu0_val[] = $wgiocatore[0][$ix];
									$qu0_nom[] = $nome[$conta]; // portiere
								}
								break;
							
						} // end switch
					}//end for
				}//end if - relativo agli infortuni
				
				$conta ++;
			} // end while
			//
			
			// creo array contenente i nomi e i valore relativi ai giocatori
			$a_numr = array_combine($nome, $nr);
			$a_frza = array_combine($nome, $wskill);
			$a_frma = array_combine($nome, $wforma);
			$a_frsc = array_combine($nome, $wfresc);
			$a_cond = array_combine($nome, $wcond);
			$a_cara = array_combine($nome, $wcarattere);
			
			// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			// !!! INIZIA IL CICLO SU TUTTE LE FORMAZIONI POSSIBILI !!!!!!
			// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			
			// for TIPO MARCATURA
			for ($ancora = 8; $ancora <=9; $ancora ++)
			{
				$CercaMarcatura = $ListaTattica[$ancora];
				// for tipo TATTICA
				for ($evvai = 1; $evvai <=7; $evvai ++)
				{
					$CercaTattica = $ListaTattica[$evvai];
					// FOR EACH ARRAY 4-4-2, 4-3-3 ECC. ECC.
					foreach ($ListaFormazione as $iter_formazione) {
						if ($iter_formazione != "X")
						{
							for ($ii = 1; $ii <65 ; $ii ++) 
							{
								$riquadro[$ii] = 0;
							}
							$Portiere = 0;
							$Difesa = 0;
							$Centrocampo = 0;
							$Attacco = 0;
							
							$ds = 0;
							$dc = 0;
							$dd = 0;
							$cs = 0;
							$cc = 0;
							$cd = 0;
							$as = 0;
							$ac = 0;
							$ad = 0;
							
							$Forza = 0;
							$Forma = 0;
							$Freschezza = 0;
							$Condizione = 0;
							
							unset($ac_numr);
							unset($ac_frza);
							unset($ac_frma);
							unset($ac_frsc);
							unset($ac_cond);
							unset($ac_valo);			
										
							unset($cc_numr);
							unset($cc_frza);
							unset($cc_frma);
							unset($cc_frsc);
							unset($cc_cond);
							unset($cc_valo);
				
							unset($dc_numr);
							unset($dc_frza);
							unset($dc_frma);
							unset($dc_frsc);
							unset($dc_cond);
							unset($dc_valo);
							
							$w_ac = 0;
							$w_cc = 0;
							$w_dc = 0;
							
							$as_numr = 0;
							$as_frza = 0;
							$as_frma = 0;
							$as_frsc = 0;
							$as_cond = 0;
							unset($as_valo);
							
							$ad_numr = 0;
							$ad_frza = 0;
							$ad_frma = 0;
							$ad_frsc = 0;
							$ad_cond = 0;
							unset($ad_valo);
							
							$cs_numr = 0;
							$cs_frza = 0;
							$cs_frma = 0;
							$cs_frsc = 0;
							$cs_cond = 0;
							unset($cs_valo);
							
							$cd_numr = 0;
							$cd_frza = 0;
							$cd_frma = 0;
							$cd_frsc = 0;
							$cd_cond = 0;
							unset($cd_valo);
							
							$ds_numr = 0;
							$ds_frza = 0;
							$ds_frma = 0;
							$ds_frsc = 0;
							$ds_cond = 0;
							unset($ds_valo);
				
							$dd_numr = 0;
							$dd_frza = 0;
							$dd_frma = 0;
							$dd_frsc = 0;
							$dd_cond = 0;
							unset($dd_valo);
							
							//$in_campo = array(); //array vuoto per i giocatori che andranno in campo	
							$in_carat = array(); //array vuoto per valutare i caratteri dei giocatori... 
																
							$a_list = AssegnaPosizioni($iter_formazione);
							
							/////////////////////////////////////////////////////////
							foreach ($a_list as $numero)
							{
								// CONTROLLA TUTTI I CASI DELLE CASELLE
								switch ($numero)
								{
									case 1:
									case 2:
									case 8:
									case 9:
									case 15:
									case 16:
										$quanti = count($qu7_val) -1 ;
										$quale = 0;			
										if (count($qu7_val) > 1)
										{			
											$watt_sin = array($qu7_val,$qu7_nom);
											array_multisort($watt_sin[0], SORT_DESC, $watt_sin[1], SORT_DESC);
											
											while ($quale <= $quanti)
											{
												if ($watt_sin[1][$quale] != "")
												{
													if (in_array($a_numr[$watt_sin[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$as_numr = $a_numr[$watt_sin[1][$quale]];
													$as_frza = $a_frza[$watt_sin[1][$quale]];
													$as_frma = $a_frma[$watt_sin[1][$quale]];
													$as_frsc = $a_frsc[$watt_sin[1][$quale]];
													$as_cond = $a_cond[$watt_sin[1][$quale]];
													$as_carat = $a_cara[$watt_sin[1][$quale]];
													
													$as_valo = $valore_giocatore[$as_numr][$numero];
												}
												break;
											}				
										}
										if (isset($as_valo))
										{
											$as = $as + $as_valo;
											$Forza = $Forza + $as_frza;
											$Forma = $Forma + $as_frma;
											$Freschezza = $Freschezza + $as_frsc;
											$Condizione = $Condizione + $as_cond;
											$riquadro[$numero] = $as_numr;
											if (!in_array($as_carat,$in_carat)) { $in_carat[] = $as_carat;	}
										}
										break;
									case 3:
									case 4:
									case 5:
									case 10:
									case 11:
									case 12:
									case 17:
									case 18:
									case 19:
										$quanti = count($qu8_val) -1 ;
										$quale = 0;			
										if (count($qu8_val) > 1)
										{			
											$watt_cen = array($qu8_val,$qu8_nom);
											array_multisort($watt_cen[0], SORT_DESC, $watt_cen[1], SORT_DESC);
											
											while ($quale <= $quanti)
											{
												if ($watt_cen[1][$quale] != "")
												{
													if (in_array($a_numr[$watt_cen[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$ac_numr[] = $a_numr[$watt_cen[1][$quale]];
													$ac_frza[] = $a_frza[$watt_cen[1][$quale]];
													$ac_frma[] = $a_frma[$watt_cen[1][$quale]];
													$ac_frsc[] = $a_frsc[$watt_cen[1][$quale]];
													$ac_cond[] = $a_cond[$watt_cen[1][$quale]];
													$ac_carat[] = $a_cara[$watt_cen[1][$quale]];
													
													$ac_valo[] = $valore_giocatore[$ac_numr[$w_ac]][$numero];
												}
												break;
											}
										}
										if (isset($ac_valo[$w_ac]))
										{
											$ac = $ac + $ac_valo[$w_ac];
											$Forza = $Forza + $ac_frza[$w_ac];
											$Forma = $Forma + $ac_frma[$w_ac];
											$Freschezza = $Freschezza + $ac_frsc[$w_ac];
											$Condizione = $Condizione + $ac_cond[$w_ac];
											$riquadro[$numero] = $ac_numr[$w_ac];
											if (!in_array($ac_carat[$w_ac],$in_carat)) { $in_carat[] = $ac_carat[$w_ac]; }
											$w_ac++;
										}
										break;
									case 6:
									case 7:
									case 13:
									case 14:
									case 20:
									case 21:
										$quanti = count($qu9_val) -1 ;
										$quale = 0;			
										
										if (count($qu9_val) > 1)
										{			
											$watt_des = array($qu9_val,$qu9_nom);
											array_multisort($watt_des[0], SORT_DESC, $watt_des[1], SORT_DESC);
											
											while ($quale <= $quanti)
											{
												if ($watt_des[1][$quale] != "")
												{
													if (in_array($a_numr[$watt_des[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$ad_numr = $a_numr[$watt_des[1][$quale]];
													$ad_frza = $a_frza[$watt_des[1][$quale]];
													$ad_frma = $a_frma[$watt_des[1][$quale]];
													$ad_frsc = $a_frsc[$watt_des[1][$quale]];
													$ad_cond = $a_cond[$watt_des[1][$quale]];
													$ad_carat = $a_cara[$watt_des[1][$quale]];
													
													$ad_valo = $valore_giocatore[$ad_numr][$numero];
												}
												break;
											}
										}
										if (isset($ad_valo))
										{
											$ad = $ad + $ad_valo;
											$Forza = $Forza + $ad_frza;
											$Forma = $Forma + $ad_frma;
											$Freschezza = $Freschezza + $ad_frsc;
											$Condizione = $Condizione + $ad_cond;
											$riquadro[$numero] = $ad_numr;
											if (!in_array($ad_carat,$in_carat)) { $in_carat[] = $ad_carat;	}
										}
										break;
									case 22:
									case 23:
									case 29:
									case 30:
									case 36:
									case 37:
										$quanti = count($qu4_val) -1 ;
										$quale = 0;			
										if (count($qu4_val) > 1)
										{
											$wcen_sin = array($qu4_val,$qu4_nom);
											array_multisort($wcen_sin[0], SORT_DESC, $wcen_sin[1], SORT_DESC);
							
											while ($quale <= $quanti)
											{
												if ($wcen_sin[1][$quale] != "")
												{
													if (in_array($a_numr[$wcen_sin[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$cs_numr = $a_numr[$wcen_sin[1][$quale]];
													$cs_frza = $a_frza[$wcen_sin[1][$quale]];
													$cs_frma = $a_frma[$wcen_sin[1][$quale]];
													$cs_frsc = $a_frsc[$wcen_sin[1][$quale]];
													$cs_cond = $a_cond[$wcen_sin[1][$quale]];
													$cs_carat = $a_cara[$wcen_sin[1][$quale]];
													
													$cs_valo = $valore_giocatore[$cs_numr][$numero];
												}
												break;
											}
										}
										if (isset($cs_valo))
										{
											$cs = $cs + $cs_valo;
											
											$Forza = $Forza + $cs_frza;
											$Forma = $Forma + $cs_frma;
											$Freschezza = $Freschezza + $cs_frsc;
											$Condizione = $Condizione + $cs_cond;
											$riquadro[$numero] = $cs_numr;
											if (!in_array($cs_carat,$in_carat)) { $in_carat[] = $cs_carat;	}
										}
										break;
									case 24:
									case 25:
									case 26:
									case 31:
									case 32:
									case 33:
									case 38:
									case 39:
									case 40:
										$quanti = count($qu5_val) -1 ;
										$quale = 0;	
										
										if (count($qu5_val) > 1)
										{			
											$wcen_cen = array($qu5_val,$qu5_nom);
											array_multisort($wcen_cen[0], SORT_DESC, $wcen_cen[1], SORT_DESC);
							
											while ($quale <= $quanti)
											{
												if ($wcen_cen[1][$quale] != "")
												{
													if (in_array($a_numr[$wcen_cen[1][$quale]],$riquadro))
													{
														
														$quale ++;
														continue;
													}
													
													$cc_numr[] = $a_numr[$wcen_cen[1][$quale]];
													$cc_frza[] = $a_frza[$wcen_cen[1][$quale]];
													$cc_frma[] = $a_frma[$wcen_cen[1][$quale]];
													$cc_frsc[] = $a_frsc[$wcen_cen[1][$quale]];
													$cc_cond[] = $a_cond[$wcen_cen[1][$quale]];
													$cc_carat[] = $a_cara[$wcen_cen[1][$quale]];
													
													$cc_valo[] = $valore_giocatore[$cc_numr[$w_cc]][$numero];
												}
												break;
											}
										}
										if (isset($cc_valo[$w_cc]))
										{
											$cc = $cc + $cc_valo[$w_cc];
											
											$Forza = $Forza + $cc_frza[$w_cc];
											$Forma = $Forma + $cc_frma[$w_cc];
											$Freschezza = $Freschezza + $cc_frsc[$w_cc];
											$Condizione = $Condizione + $cc_cond[$w_cc];
											$riquadro[$numero] = $cc_numr[$w_cc];
											if (!in_array($cc_carat[$w_cc],$in_carat)) { $in_carat[] = $cc_carat[$w_cc];	}
											$w_cc++;
										}
										break;
									case 27:
									case 28:
									case 34:
									case 35:
									case 41:
									case 42:
										$quanti = count($qu6_val) -1 ;
										$quale = 0;			
										if (count($qu6_val) > 1)
										{			
											$wcen_des = array($qu6_val,$qu6_nom);
											array_multisort($wcen_des[0], SORT_DESC, $wcen_des[1], SORT_DESC);
							
											while ($quale <= $quanti)
											{
												if ($wcen_des[1][$quale] != "")
												{
													if (in_array($a_numr[$wcen_des[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$cd_numr = $a_numr[$wcen_des[1][$quale]];
													$cd_frza = $a_frza[$wcen_des[1][$quale]];
													$cd_frma = $a_frma[$wcen_des[1][$quale]];
													$cd_frsc = $a_frsc[$wcen_des[1][$quale]];
													$cd_cond = $a_cond[$wcen_des[1][$quale]];
													$cd_carat = $a_cara[$wcen_des[1][$quale]];
													
													$cd_valo = $valore_giocatore[$cd_numr][$numero];
												}
												break;
											}				
										}
										if (isset($cd_valo))
										{
											$cd = $cd + $cd_valo;
											
											$Forza = $Forza + $cd_frza;
											$Forma = $Forma + $cd_frma;
											$Freschezza = $Freschezza + $cd_frsc;
											$Condizione = $Condizione + $cd_cond;
											$riquadro[$numero] = $cd_numr;
											if (!in_array($cd_carat,$in_carat)) { $in_carat[] = $cd_carat;	}
										}
										break;
									case 43:
									case 44:
									case 50:
									case 51:
									case 57:
									case 58:
										$quanti = count($qu1_val) -1 ;
										$quale = 0;			
										if (count($qu1_val) > 1)
										{			
											$wdif_sin = array($qu1_val,$qu1_nom);
											array_multisort($wdif_sin[0], SORT_DESC, $wdif_sin[1], SORT_DESC);
											
											while ($quale <= $quanti)
											{
												if ($wdif_sin[1][$quale] != "")
												{
													if (in_array($a_numr[$wdif_sin[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$ds_numr = $a_numr[$wdif_sin[1][$quale]];
													$ds_frza = $a_frza[$wdif_sin[1][$quale]];
													$ds_frma = $a_frma[$wdif_sin[1][$quale]];
													$ds_frsc = $a_frsc[$wdif_sin[1][$quale]];
													$ds_cond = $a_cond[$wdif_sin[1][$quale]];
													$ds_carat = $a_cara[$wdif_sin[1][$quale]];
													
													$ds_valo = $valore_giocatore[$ds_numr][$numero];
												}
												break;
											}
										}

										if (isset($ds_valo))
										{
											$ds = $ds + $ds_valo;
											
											$Forza = $Forza + $ds_frza;
											$Forma = $Forma + $ds_frma;
											$Freschezza = $Freschezza + $ds_frsc;
											$Condizione = $Condizione + $ds_cond;
											$riquadro[$numero] = $ds_numr;
											if (!in_array($ds_carat,$in_carat)) { $in_carat[] = $ds_carat;	}
										}
										break;
									case 45:
									case 46:
									case 47:
									case 52:
									case 53:
									case 54:
									case 59:
									case 60:				
									case 61:
										$quanti = count($qu2_val) -1 ;
										$quale = 0;		
										if (count($qu2_val) > 1)
										{
											$wdif_cen = array($qu2_val,$qu2_nom);
											array_multisort($wdif_cen[0], SORT_DESC, $wdif_cen[1], SORT_DESC);
											
											while ($quale <= $quanti)
											{
												if ($wdif_cen[1][$quale] != "")
												{
													if (in_array($a_numr[$wdif_cen[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$dc_numr[] = $a_numr[$wdif_cen[1][$quale]];
													$dc_frza[] = $a_frza[$wdif_cen[1][$quale]];
													$dc_frma[] = $a_frma[$wdif_cen[1][$quale]];
													$dc_frsc[] = $a_frsc[$wdif_cen[1][$quale]];
													$dc_cond[] = $a_cond[$wdif_cen[1][$quale]];
													$dc_carat[] = $a_cara[$wdif_cen[1][$quale]];
													
													$dc_valo[] = $valore_giocatore[$dc_numr[$w_dc]][$numero];
												}
												break;
											}
										}
										if (isset($dc_valo[$w_dc]))
										{						
											$dc = $dc + $dc_valo[$w_dc];
											
											$Forza = $Forza + $dc_frza[$w_dc];
											$Forma = $Forma + $dc_frma[$w_dc];
											$Freschezza = $Freschezza + $dc_frsc[$w_dc];
											$Condizione = $Condizione + $dc_cond[$w_dc];
											$riquadro[$numero] = $dc_numr[$w_dc];
											if (!in_array($dc_carat[$w_dc],$in_carat)) { $in_carat[] = $dc_carat[$w_dc];	}
											$w_dc++;
										}
										break;
									case 48:
									case 49:
									case 55:
									case 56:
									case 62:
									case 63:
										$quanti = count($qu3_val) -1 ;
										$quale = 0;	
										if (count($qu3_val) > 1)
										{
											$wdif_des = array($qu3_val,$qu3_nom);
											array_multisort($wdif_des[0], SORT_DESC, $wdif_des[1], SORT_DESC);
											while ($quale <= $quanti)
											{
												if ($wdif_des[1][$quale] != "")
												{
													if (in_array($a_numr[$wdif_des[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$dd_numr = $a_numr[$wdif_des[1][$quale]];
													$dd_frza = $a_frza[$wdif_des[1][$quale]];
													$dd_frma = $a_frma[$wdif_des[1][$quale]];
													$dd_frsc = $a_frsc[$wdif_des[1][$quale]];
													$dd_cond = $a_cond[$wdif_des[1][$quale]];
													$dd_carat = $a_cara[$wdif_des[1][$quale]];
													
													$dd_valo = $valore_giocatore[$dd_numr][$numero];
												}
												break;
											}
										}

										if (isset($dd_valo))
										{
											$dd = $dd + $dd_valo;
											$Forza = $Forza + $dd_frza;
											$Forma = $Forma + $dd_frma;
											$Freschezza = $Freschezza + $dd_frsc;
											$Condizione = $Condizione + $dd_cond;
											$riquadro[$numero] = $dd_numr;
											if (!in_array($dd_carat,$in_carat)) { $in_carat[] = $dd_carat;	}
										}
										break;
									case 64:
										$quanti = count($qu0_val) -1 ;
										$quale = 0;			
										if (count($qu0_val) > 1)
										{
											$wportiere = array($qu0_val,$qu0_nom);
											array_multisort($wportiere[0], SORT_DESC, $wportiere[1], SORT_DESC);
											
											while ($quale <= $quanti)
											{
												if ($wportiere[1][$quale] != "")
												{
													if (in_array($a_numr[$wportiere[1][$quale]],$riquadro))
													{
														$quale ++;
														continue;
													}
													$po_numr = $a_numr[$wportiere[1][$quale]];
													$po_frza = $a_frza[$wportiere[1][$quale]];
													$po_frma = $a_frma[$wportiere[1][$quale]];
													$po_frsc = $a_frsc[$wportiere[1][$quale]];
													$po_cond = $a_cond[$wportiere[1][$quale]];
													$po_valo = $wportiere[0][$quale];
													$po_carat = $a_cara[$wportiere[1][$quale]];
												}
												break;
											}
										}
										
										if (isset($po_valo))
										{
											$Portiere = $po_valo;
											$Forza = $Forza + $po_frza;
											$Forma = $Forma + $po_frma;
											$Freschezza = $Freschezza + $po_frsc;
											$Condizione = $Condizione + $po_cond;
											$in_carat[] = $po_carat;
										}	
								} // end switch
								
							} // end foreach
							
							
							//assegnazione valori tattiche
							$as = $as + $as*$Bonus[$CercaTattica][7]/100;
							$ac = $ac + $ac*$Bonus[$CercaTattica][8]/100;
							$ad = $ad + $ad*$Bonus[$CercaTattica][9]/100;
							$cs = $cs + $cs*$Bonus[$CercaTattica][4]/100;
							$cc = $cc + $cc*$Bonus[$CercaTattica][5]/100;
							$cd = $cd + $cd*$Bonus[$CercaTattica][6]/100;
							$ds = $ds + $ds*$Bonus[$CercaTattica][1]/100;
							$dc = $dc + $dc*$Bonus[$CercaTattica][2]/100;
							$dd = $dd + $dd*$Bonus[$CercaTattica][3]/100;
							
							//assegnazione valori marcatura
							$cs = $cs + $cs*$Bonus[$CercaMarcatura][4]/100;
							$cd = $cd + $cd*$Bonus[$CercaMarcatura][6]/100;
							$ds = $ds + $ds*$Bonus[$CercaMarcatura][1]/100;
							$dd = $dd + $dd*$Bonus[$CercaMarcatura][3]/100;
							
							$Difesa = round($ds + $dc + $dd,1);
							$Centrocampo = round($cs + $cc + $cd,1);
							$Attacco = round($as + $ac + $ad,1);

						if ($TipoBonus == "SI")
						{
							$Portiere=round($Portiere + $Portiere * ($b_viceallena + $b_alleportie) / 100,1);
							$Difesa = round($Difesa + $Difesa * ($b_viceallena+$b_allenatore*$bonus_allenatore[0]/100)/ 100,1);
					 $Centrocampo=round($Centrocampo+$Centrocampo*($b_viceallena+$b_allenatore*$bonus_allenatore[1]/100)/100,1);
							$Attacco=round($Attacco+$Attacco*($b_viceallena+ $b_allenatore*$bonus_allenatore[2]/100)/100,1);
						}
							// VALORI PER PERCENTUALE ALLENAMENTO TATTICHE
							if ($Ideale != "Checked")
							{
								$Difesa = round($Difesa*$aggiorna[$CercaTattica]/100,1);
								$Centrocampo = round($Centrocampo*$aggiorna[$CercaTattica]/100,1);
								$Attacco = round($Attacco*$aggiorna[$CercaTattica]/100,1);
							}
							
							$Malus = 0;
							if (count($in_carat) < 5 ) 
							{ 
								$Malus = round(($Portiere + $Difesa + $Centrocampo + $Attacco)/100*5,1);
							} 
							
							$ForzaSquadra[] = $Portiere + $Difesa + $Centrocampo + $Attacco - $Malus;
							$FormazioneMigliore[] = $iter_formazione;
							$TatticaMigliore[] = $CercaTattica;
							$MarcaturaMigliore[] = $CercaMarcatura;
						}	//end if != "X"
					}	// end foreach 4-4-2, 4-3-3, ECC. 
				}	//end for TIPO TATTICA
			}	//end for TIPO MARCATURA
			//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			//!!!!!!!!! F I N E        R I C E R C A !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			$wformazionemigliore = array($ForzaSquadra,$FormazioneMigliore);
			$wtatticamigliore = array($ForzaSquadra,$TatticaMigliore);
			$wmarcaturamigliore = array($ForzaSquadra,$MarcaturaMigliore);
				
			array_multisort($wformazionemigliore[0], SORT_DESC, $wformazionemigliore[1], SORT_DESC);
			array_multisort($wtatticamigliore[0], SORT_DESC, $wtatticamigliore[1], SORT_DESC);
			array_multisort($wmarcaturamigliore[0], SORT_DESC, $wmarcaturamigliore[1], SORT_DESC);

			if ($scelta_formazione != 1)
			{
				$formazione = $wformazionemigliore[1][0];
				$TipoTattica = $wtatticamigliore[1][0];
				$TipoMarcatura = $wmarcaturamigliore[1][0];
			}
			
			$a_list = AssegnaPosizioni($formazione); 
			
			for ($ii = 1; $ii <65 ; $ii ++) 
			{
				$riquadro[$ii] = 0;
			}

			$Portiere = 0;
			$Difesa = 0;
			$Centrocampo = 0;
			$Attacco = 0;
			
			$ds = 0;
			$dc = 0;
			$dd = 0;
			$cs = 0;
			$cc = 0;
			$cd = 0;
			$as = 0;
			$ac = 0;
			$ad = 0;
							
			$Forza = 0;
			$Forma = 0;
			$Freschezza = 0;
			$Condizione = 0;
			$in_carat = array();

			unset($ac_numr);
			unset($ac_frza);
			unset($ac_frma);
			unset($ac_frsc);
			unset($ac_cond);
			unset($ac_valo);			
						
			unset($cc_numr);
			unset($cc_frza);
			unset($cc_frma);
			unset($cc_frsc);
			unset($cc_cond);
			unset($cc_valo);

			unset($dc_numr);
			unset($dc_frza);
			unset($dc_frma);
			unset($dc_frsc);
			unset($dc_cond);
			unset($dc_valo);
			
			$w_ac = 0;
			$w_cc = 0;
			$w_dc = 0;
			
			$as_numr = 0;
			$as_frza = 0;
			$as_frma = 0;
			$as_frsc = 0;
			$as_cond = 0;
			unset($as_valo);
			
			$ad_numr = 0;
			$ad_frza = 0;
			$ad_frma = 0;
			$ad_frsc = 0;
			$ad_cond = 0;
			unset($ad_valo);
			
			$cs_numr = 0;
			$cs_frza = 0;
			$cs_frma = 0;
			$cs_frsc = 0;
			$cs_cond = 0;
			unset($cs_valo);
			
			$cd_numr = 0;
			$cd_frza = 0;
			$cd_frma = 0;
			$cd_frsc = 0;
			$cd_cond = 0;
			unset($cd_valo);
			
			$ds_numr = 0;
			$ds_frza = 0;
			$ds_frma = 0;
			$ds_frsc = 0;
			$ds_cond = 0;
			unset($ds_valo);

			$dd_numr = 0;
			$dd_frza = 0;
			$dd_frma = 0;
			$dd_frsc = 0;
			$dd_cond = 0;
			unset($dd_valo);
			
			foreach ($a_list as $numero)
			{
				// CONTROLLA TUTTI I CASI DELLE CASELLE
				switch ($numero)
				{
					case 1:
					case 2:
					case 8:
					case 9:
					case 15:
					case 16:
						$quanti = count($qu7_val) -1 ;
						$quale = 0;			
						if (count($qu7_val) > 1)
						{			
							$watt_sin = array($qu7_val,$qu7_nom);
							array_multisort($watt_sin[0], SORT_DESC, $watt_sin[1], SORT_DESC);
							
							while ($quale <= $quanti)
							{
								if ($watt_sin[1][$quale] != "")
								{
									if (in_array($a_numr[$watt_sin[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$as_numr = $a_numr[$watt_sin[1][$quale]];
									$as_frza = $a_frza[$watt_sin[1][$quale]];
									$as_frma = $a_frma[$watt_sin[1][$quale]];
									$as_frsc = $a_frsc[$watt_sin[1][$quale]];
									$as_cond = $a_cond[$watt_sin[1][$quale]];
									$as_carat = $a_cara[$watt_sin[1][$quale]];
									
									$as_valo = $valore_giocatore[$as_numr][$numero];//$watt_sin[0][$quale];
								}
								break;
							}				
						}
						if (isset($as_valo))
						{
							$formTattica[0] ++;
							$as = $as + $as_valo;
							$Forza = $Forza + $as_frza;
							$Forma = $Forma + $as_frma;
							$Freschezza = $Freschezza + $as_frsc;
							$Condizione = $Condizione + $as_cond;
							$riquadro[$numero] = $as_numr;
							if (!in_array($as_carat,$in_carat)) { $in_carat[] = $as_carat;	}
						}
						break;
					case 3:
					case 4:
					case 5:
					case 10:
					case 11:
					case 12:
					case 17:
					case 18:
					case 19:
						$quanti = count($qu8_val) -1 ;
						$quale = 0;			
						if (count($qu8_val) > 1)
						{			
							$watt_cen = array($qu8_val,$qu8_nom);
							array_multisort($watt_cen[0], SORT_DESC, $watt_cen[1], SORT_DESC);
							
							while ($quale <= $quanti)
							{
								if ($watt_cen[1][$quale] != "")
								{
									if (in_array($a_numr[$watt_cen[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$ac_numr[] = $a_numr[$watt_cen[1][$quale]];
									$ac_frza[] = $a_frza[$watt_cen[1][$quale]];
									$ac_frma[] = $a_frma[$watt_cen[1][$quale]];
									$ac_frsc[] = $a_frsc[$watt_cen[1][$quale]];
									$ac_cond[] = $a_cond[$watt_cen[1][$quale]];
									$ac_carat[] = $a_cara[$watt_cen[1][$quale]];
									
									$ac_valo[] = $valore_giocatore[$ac_numr[$w_ac]][$numero];//$watt_cen[0][$quale];
								}
								break;
							}
						}
						if (isset($ac_valo[$w_ac]))
						{
							$formTattica[0] ++;
							$ac = $ac + $ac_valo[$w_ac];
							
							$Forza = $Forza + $ac_frza[$w_ac];
							$Forma = $Forma + $ac_frma[$w_ac];
							$Freschezza = $Freschezza + $ac_frsc[$w_ac];
							$Condizione = $Condizione + $ac_cond[$w_ac];
							$riquadro[$numero] = $ac_numr[$w_ac];
							if (!in_array($ac_carat[$w_ac],$in_carat)) { $in_carat[] = $ac_carat[$w_ac]; }
							$w_ac++;
						}
						break;
					case 6:
					case 7:
					case 13:
					case 14:
					case 20:
					case 21:
						$quanti = count($qu9_val) -1 ;
						$quale = 0;			
						if (count($qu9_val) > 1)
						{			
							$watt_des = array($qu9_val,$qu9_nom);
							array_multisort($watt_des[0], SORT_DESC, $watt_des[1], SORT_DESC);
							
							while ($quale <= $quanti)
							{
								if ($watt_des[1][$quale] != "")
								{
									if (in_array($a_numr[$watt_des[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$ad_numr = $a_numr[$watt_des[1][$quale]];
									$ad_frza = $a_frza[$watt_des[1][$quale]];
									$ad_frma = $a_frma[$watt_des[1][$quale]];
									$ad_frsc = $a_frsc[$watt_des[1][$quale]];
									$ad_cond = $a_cond[$watt_des[1][$quale]];
									$ad_carat = $a_cara[$watt_des[1][$quale]];
									
									$ad_valo = $valore_giocatore[$ad_numr][$numero];//$watt_des[0][$quale];
								}
								break;
							}
						}
						if (isset($ad_valo))
						{
							$formTattica[0] ++;
							$ad = $ad + $ad_valo;
							
							$Forza = $Forza + $ad_frza;
							$Forma = $Forma + $ad_frma;
							$Freschezza = $Freschezza + $ad_frsc;
							$Condizione = $Condizione + $ad_cond;
							$riquadro[$numero] = $ad_numr;
							if (!in_array($ad_carat,$in_carat)) { $in_carat[] = $ad_carat;	}
						}
						break;
					case 22:
					case 23:
					case 29:
					case 30:
					case 36:
					case 37:
						$quanti = count($qu4_val) -1 ;
						$quale = 0;			
						if (count($qu4_val) > 1)
						{
							$wcen_sin = array($qu4_val,$qu4_nom);
							array_multisort($wcen_sin[0], SORT_DESC, $wcen_sin[1], SORT_DESC);
			
							while ($quale <= $quanti)
							{
								if ($wcen_sin[1][$quale] != "")
								{
									if (in_array($a_numr[$wcen_sin[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$cs_numr = $a_numr[$wcen_sin[1][$quale]];
									$cs_frza = $a_frza[$wcen_sin[1][$quale]];
									$cs_frma = $a_frma[$wcen_sin[1][$quale]];
									$cs_frsc = $a_frsc[$wcen_sin[1][$quale]];
									$cs_cond = $a_cond[$wcen_sin[1][$quale]];
									$cs_carat = $a_cara[$wcen_sin[1][$quale]];
									
									$cs_valo = $valore_giocatore[$cs_numr][$numero];//$wcen_sin[0][$quale];
								}
								break;
							}
						}
						if (isset($cs_valo))
						{
							$formTattica[1] ++;
							$cs = $cs + $cs_valo;
							
							$Forza = $Forza + $cs_frza;
							$Forma = $Forma + $cs_frma;
							$Freschezza = $Freschezza + $cs_frsc;
							$Condizione = $Condizione + $cs_cond;
							$riquadro[$numero] = $cs_numr;
							if (!in_array($cs_carat,$in_carat)) { $in_carat[] = $cs_carat;	}
						}
						break;
					case 24:
					case 25:
					case 26:
					case 31:
					case 32:
					case 33:
					case 38:
					case 39:
					case 40:
						$quanti = count($qu5_val) -1 ;
						$quale = 0;	
						
						if (count($qu5_val) > 1)
						{			
							$wcen_cen = array($qu5_val,$qu5_nom);
							array_multisort($wcen_cen[0], SORT_DESC, $wcen_cen[1], SORT_DESC);
			
							while ($quale <= $quanti)
							{
								if ($wcen_cen[1][$quale] != "")
								{
									if (in_array($a_numr[$wcen_cen[1][$quale]],$riquadro))
									{
										
										$quale ++;
										continue;
									}
									
									$cc_numr[] = $a_numr[$wcen_cen[1][$quale]];
									$cc_frza[] = $a_frza[$wcen_cen[1][$quale]];
									$cc_frma[] = $a_frma[$wcen_cen[1][$quale]];
									$cc_frsc[] = $a_frsc[$wcen_cen[1][$quale]];
									$cc_cond[] = $a_cond[$wcen_cen[1][$quale]];
									$cc_carat[] = $a_cara[$wcen_cen[1][$quale]];
									
									$cc_valo[] = $valore_giocatore[$cc_numr[$w_cc]][$numero];
								}
								break;
							}
						}
						if (isset($cc_valo[$w_cc]))
						{
							$formTattica[1] ++;						
							$cc = $cc + $cc_valo[$w_cc];
							
							$Forza = $Forza + $cc_frza[$w_cc];
							$Forma = $Forma + $cc_frma[$w_cc];
							$Freschezza = $Freschezza + $cc_frsc[$w_cc];
							$Condizione = $Condizione + $cc_cond[$w_cc];
							$riquadro[$numero] = $cc_numr[$w_cc];
							if (!in_array($cc_carat[$w_cc],$in_carat)) { $in_carat[] = $cc_carat[$w_cc];	}
							$w_cc++;
						}
						break;
					case 27:
					case 28:
					case 34:
					case 35:
					case 41:
					case 42:
						$quanti = count($qu6_val) -1 ;
						$quale = 0;			
						if (count($qu6_val) > 1)
						{			
							$wcen_des = array($qu6_val,$qu6_nom);
							array_multisort($wcen_des[0], SORT_DESC, $wcen_des[1], SORT_DESC);
			
							while ($quale <= $quanti)
							{
								if ($wcen_des[1][$quale] != "")
								{
									if (in_array($a_numr[$wcen_des[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$cd_numr = $a_numr[$wcen_des[1][$quale]];
									$cd_frza = $a_frza[$wcen_des[1][$quale]];
									$cd_frma = $a_frma[$wcen_des[1][$quale]];
									$cd_frsc = $a_frsc[$wcen_des[1][$quale]];
									$cd_cond = $a_cond[$wcen_des[1][$quale]];
									$cd_carat = $a_cara[$wcen_des[1][$quale]];
									
									$cd_valo = $valore_giocatore[$cd_numr][$numero];
								}
								break;
							}				
						}
						if (isset($cd_valo))
						{
							$formTattica[1] ++;
							$cd = $cd + $cd_valo;

							$Forza = $Forza + $cd_frza;
							$Forma = $Forma + $cd_frma;
							$Freschezza = $Freschezza + $cd_frsc;
							$Condizione = $Condizione + $cd_cond;
							$riquadro[$numero] = $cd_numr;
							if (!in_array($cd_carat,$in_carat)) { $in_carat[] = $cd_carat;	}
						}
						break;
					case 43:
					case 44:
					case 50:
					case 51:
					case 57:
					case 58:
						$quanti = count($qu1_val) -1 ;
						$quale = 0;			
						if (count($qu1_val) > 1)
						{			
							$wdif_sin = array($qu1_val,$qu1_nom);
							array_multisort($wdif_sin[0], SORT_DESC, $wdif_sin[1], SORT_DESC);
							
							while ($quale <= $quanti)
							{
								if ($wdif_sin[1][$quale] != "")
								{
									if (in_array($a_numr[$wdif_sin[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$ds_numr = $a_numr[$wdif_sin[1][$quale]];
									$ds_frza = $a_frza[$wdif_sin[1][$quale]];
									$ds_frma = $a_frma[$wdif_sin[1][$quale]];
									$ds_frsc = $a_frsc[$wdif_sin[1][$quale]];
									$ds_cond = $a_cond[$wdif_sin[1][$quale]];
									$ds_carat = $a_cara[$wdif_sin[1][$quale]];
									
									$ds_valo = $valore_giocatore[$ds_numr][$numero];
								}
								break;
							}
						}
						if (isset($ds_valo))
						{
							$formTattica[2] ++;
							$ds = $ds + $ds_valo;
							$Forza = $Forza + $ds_frza;
							$Forma = $Forma + $ds_frma;
							$Freschezza = $Freschezza + $ds_frsc;
							$Condizione = $Condizione + $ds_cond;
							$riquadro[$numero] = $ds_numr;
							if (!in_array($ds_carat,$in_carat)) { $in_carat[] = $ds_carat;	}
						}
						break;
					case 45:
					case 46:
					case 47:
					case 52:
					case 53:
					case 54:
					case 59:
					case 60:				
					case 61:
						$quanti = count($qu2_val) -1 ;
						$quale = 0;		
						if (count($qu2_val) > 1)
						{
							$wdif_cen = array($qu2_val,$qu2_nom);
							array_multisort($wdif_cen[0], SORT_DESC, $wdif_cen[1], SORT_DESC);
							
							while ($quale <= $quanti)
							{
								if ($wdif_cen[1][$quale] != "")
								{
									if (in_array($a_numr[$wdif_cen[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$dc_numr[] = $a_numr[$wdif_cen[1][$quale]];
									$dc_frza[] = $a_frza[$wdif_cen[1][$quale]];
									$dc_frma[] = $a_frma[$wdif_cen[1][$quale]];
									$dc_frsc[] = $a_frsc[$wdif_cen[1][$quale]];
									$dc_cond[] = $a_cond[$wdif_cen[1][$quale]];
									$dc_carat[] = $a_cara[$wdif_cen[1][$quale]];
									
									$dc_valo[] = $valore_giocatore[$dc_numr[$w_dc]][$numero];
								}
								break;
							}
						}
						if (isset($dc_valo[$w_dc]))
						{	
							$formTattica[2] ++;					
							$dc = $dc + $dc_valo[$w_dc];
							$Forza = $Forza + $dc_frza[$w_dc];
							$Forma = $Forma + $dc_frma[$w_dc];
							$Freschezza = $Freschezza + $dc_frsc[$w_dc];
							$Condizione = $Condizione + $dc_cond[$w_dc];
							$riquadro[$numero] = $dc_numr[$w_dc];
							if (!in_array($dc_carat[$w_dc],$in_carat)) { $in_carat[] = $dc_carat[$w_dc];	}
							$w_dc++;
						}
						break;
					case 48:
					case 49:
					case 55:
					case 56:
					case 62:
					case 63:
						$quanti = count($qu3_val) -1 ;
						$quale = 0;	
						if (count($qu3_val) > 1)
						{
							$wdif_des = array($qu3_val,$qu3_nom);
							array_multisort($wdif_des[0], SORT_DESC, $wdif_des[1], SORT_DESC);
							while ($quale <= $quanti)
							{
								if ($wdif_des[1][$quale] != "")
								{
									if (in_array($a_numr[$wdif_des[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$dd_numr = $a_numr[$wdif_des[1][$quale]];
									$dd_frza = $a_frza[$wdif_des[1][$quale]];
									$dd_frma = $a_frma[$wdif_des[1][$quale]];
									$dd_frsc = $a_frsc[$wdif_des[1][$quale]];
									$dd_cond = $a_cond[$wdif_des[1][$quale]];
									$dd_carat = $a_cara[$wdif_des[1][$quale]];
									
									$dd_valo = $valore_giocatore[$dd_numr][$numero];
								}
								break;
							}
						}
						if (isset($dd_valo))
						{
							$formTattica[2] ++;
							$dd = $dd + $dd_valo;
							$Forza = $Forza + $dd_frza;
							$Forma = $Forma + $dd_frma;
							$Freschezza = $Freschezza + $dd_frsc;
							$Condizione = $Condizione + $dd_cond;
							$riquadro[$numero] = $dd_numr;
							if (!in_array($dd_carat,$in_carat)) { $in_carat[] = $dd_carat;	}
						}
						break;
					case 64:
						$quanti = count($qu0_val) -1 ;
						$quale = 0;			
						if (count($qu0_val) > 1)
						{
							$wportiere = array($qu0_val,$qu0_nom);
							array_multisort($wportiere[0], SORT_DESC, $wportiere[1], SORT_DESC);
							
							while ($quale <= $quanti)
							{
								if ($wportiere[1][$quale] != "")
								{
									if (in_array($a_numr[$wportiere[1][$quale]],$riquadro))
									{
										$quale ++;
										continue;
									}
									$po_numr = $a_numr[$wportiere[1][$quale]];
									$po_frza = $a_frza[$wportiere[1][$quale]];
									$po_frma = $a_frma[$wportiere[1][$quale]];
									$po_frsc = $a_frsc[$wportiere[1][$quale]];
									$po_cond = $a_cond[$wportiere[1][$quale]];
									$po_valo = $wportiere[0][$quale];
									$po_carat = $a_cara[$wportiere[1][$quale]];
								}
								break;
							}
						}
						
						if (isset($po_valo))
						{
							$Portiere = $po_valo;
							$Forza = $Forza + $po_frza;
							$Forma = $Forma + $po_frma;
							$Freschezza = $Freschezza + $po_frsc;
							$Condizione = $Condizione + $po_cond;
							$in_carat[] = $po_carat;
						}
						break;
				} // end switch
			} // end foreach
			
			//assegnazione valori tattiche
			$as = $as + $as*$Bonus[$TipoTattica][7]/100;
			$ac = $ac + $ac*$Bonus[$TipoTattica][8]/100;
			$ad = $ad + $ad*$Bonus[$TipoTattica][9]/100;
			$cs = $cs + $cs*$Bonus[$TipoTattica][4]/100;
			$cc = $cc + $cc*$Bonus[$TipoTattica][5]/100;
			$cd = $cd + $cd*$Bonus[$TipoTattica][6]/100;
			$ds = $ds + $ds*$Bonus[$TipoTattica][1]/100;
			$dc = $dc + $dc*$Bonus[$TipoTattica][2]/100;
			$dd = $dd + $dd*$Bonus[$TipoTattica][3]/100;
			
			//assegnazione valori marcatura
			$cs = $cs + $cs*$Bonus[$TipoMarcatura][4]/100;
			$cd = $cd + $cd*$Bonus[$TipoMarcatura][6]/100;
			$ds = $ds + $ds*$Bonus[$TipoMarcatura][1]/100;
			$dd = $dd + $dd*$Bonus[$TipoMarcatura][3]/100;
			
			$Difesa = round($ds + $dc + $dd,1);
			$Centrocampo = round($cs + $cc + $cd,1);
			$Attacco = round($as + $ac + $ad,1);
			
			$Skill = intval($Forza / (1+$formTattica[0]+$formTattica[1]+$formTattica[2]));
			$Forma = intval($Forma / (1+$formTattica[0]+$formTattica[1]+$formTattica[2]));
			$Freschezza = intval($Freschezza / (1+$formTattica[0]+$formTattica[1]+$formTattica[2]));
			$Condizione = intval($Condizione / (1+$formTattica[0]+$formTattica[1]+$formTattica[2]));
			
			if ($TipoBonus == "SI") {
				$Portiere = round($Portiere  + $Portiere    * ($b_viceallena + $b_alleportie) / 100,1);
				$Difesa   = round($Difesa    + $Difesa      * ($b_viceallena + $b_allenatore*$bonus_allenatore[0]/100) / 100,1);
				$Centrocampo=round($Centrocampo+$Centrocampo *($b_viceallena + $b_allenatore*$bonus_allenatore[1]/100) / 100,1);
				$Attacco  = round($Attacco   + $Attacco     * ($b_viceallena + $b_allenatore*$bonus_allenatore[2]/100) / 100,1);
			}
			// VALORI PER PERCENTUALE ALLENAMENTO TATTICHE
			if ($Ideale != "Checked")
			{
				$Difesa = round($Difesa*$aggiorna[$TipoTattica]/100,1);
				$Centrocampo = round($Centrocampo*$aggiorna[$TipoTattica]/100,1);
				$Attacco = round($Attacco*$aggiorna[$TipoTattica]/100,1);
			}
			
			$Malus = 0;
			$qu_carat = count($in_carat);
			
			if ($qu_carat < 5 )
			{ 
				$Malus = round(($Portiere + $Difesa + $Centrocampo + $Attacco)/100*5,1);
			} 
			
			$StampaForza = $Portiere + $Difesa + $Centrocampo + $Attacco - $Malus;

			for ($ii = 1; $ii <=64 ; $ii ++) 
			{
				if ($riquadro[$ii] != 0)
				{
					$class[$ii] = "brdgioca"; //giocatore in campo
				}
				else
				{
					$class[$ii] = "board"; //valore normale
				}
			}
			if (isset($po_numr))
			{
				$riquadro[64] = $po_numr;
				$class[64] = "brdportiere"; //portiere sempre in campo se esiste....
			}
			
			echo "<form name='valore'>";
			echo "<span id='bonus'>";
			echo "<fieldset width='50%'>";
			echo "<table border='0' cellpadding='0' cellspacing='0' style='font-size:10px;'>
					<tr>
					<td>Formazione</td>
					<td>&nbsp;</td>
					<td>
					<select name='ws_TipoFormazione' id='ws_TipoFormazione' onchange='javascript:aggiorna(1);' class='fld_y'>
						<option value='$formazione' selected='selected'>$formazione</option>
						<option value='$formazione'>----------</option>";
						
			$xy = 0;
			foreach ($ListaFormazione as $iter_formazione)
			{
				if ($iter_formazione != "X")	
				{
					echo "<option value='$ListaFormazione[$xy]'>$xy) $ListaFormazione[$xy]</option>";
				}
				$xy ++;
			}
			echo "	</select>
					</td>
					<td>&nbsp;</td>";
					
			echo "	<td >
				<input id='ws_bonus' name='ws_bonus' type='checkbox' $checkBn value='$TipoBonus' onclick='javascript:aggiorna(2);' />Bonus Allenatore
					</td>			
					</tr>";
		
			echo "<tr>
					<td>Tattica</td>
					<td>&nbsp;</td>
					<td>
					<select name='ws_TipoTattica' id='ws_TipoTattica' onchange='javascript:aggiorna(3);' class='fld_y'>
						<option value='$TipoTattica' selected='selected'>$TipoTattica</option>
						<option value='$TipoTattica'>---------------</option>
						<option value='$ListaTattica[1]'>$ListaTattica[1]</option>
						<option value='$ListaTattica[2]'>$ListaTattica[2]</option>
						<option value='$ListaTattica[3]'>$ListaTattica[3]</option>
						<option value='$ListaTattica[4]'>$ListaTattica[4]</option>
						<option value='$ListaTattica[5]'>$ListaTattica[5]</option>
						<option value='$ListaTattica[6]'>$ListaTattica[6]</option>
						<option value='$ListaTattica[7]'>$ListaTattica[7]</option>
					</select>
					</td>
					<td>&nbsp;</td>";
					
			echo "	<td>";
			echo "<input id='ws_ffc' name='ws_ffc' type='checkbox' $checkAt value='$BonusFfc' onclick='javascript:aggiorna(5);'/>Condizione atletica generale";
			echo "	</td>
				</tr>";
		
			echo "
			<tr>
				<td>Marcatura</td>
				<td>&nbsp;</td>
				<td>
				<select name='ws_TipoMarcatura' id='ws_TipoMarcatura' onchange='javascript:aggiorna(4);' class='fld_y'>
					<option value='$TipoMarcatura' selected='selected'>$TipoMarcatura</option>
					<option value='$TipoMarcatura'>--------------------------</option>
					<option value='$ListaTattica[8]'>$ListaTattica[8]</option>
					<option value='$ListaTattica[9]'>$ListaTattica[9]</option>
				</select>
				</td>
				<td>&nbsp;</td>";
			
			echo "	<td>";
			echo "<input id='ws_ideale' name='ws_ideale' type='checkbox' $Ideale value='$Ideale' onclick='javascript:aggiorna(6);'/>Formazione Ideale";
			echo "	</td>			
				</tr>";
			echo "</tr></table></fieldset>";
			echo "</span>";
			
			
			echo "<span id='campo_automatico' style='color:#FFFF00; font-weight:bold;'>";
			echo "<form name='field'>";
			//echo "<span id='campo2' ><img src='images/in_campo.png' width='308' height='320' alt='' border='0'></span>";
			echo "<div id='campo_automatico'>
					<img src='images/in_campo.png' id='campo_automatico' border='0'>
				</div>";
			
			
			echo "
			<span id='briga1' >
			 <table border='0' cellpadding='0' cellspacing='3'>
			 <tr>
			 <td><input name='btn' class=\"$class[1]\" value=$riquadro[1]  type='button' ></td>
			 <td><input name='btn' class=\"$class[2]\" value=$riquadro[2]  type='button' ></td>
			 <td><input name='btn' class=\"$class[3]\" value=$riquadro[3]  type='button' ></td>
			 <td><input name='btn' class=\"$class[4]\" value=$riquadro[4]  type='button' ></td>
			 <td><input name='btn' class=\"$class[5]\" value=$riquadro[5]  type='button'  ></td>
			 <td><input name='btn' class=\"$class[6]\" value=$riquadro[6]  type='button'  ></td>
			 <td><input name='btn' class=\"$class[7]\" value=$riquadro[7]  type='button'  ></td>
			 </tr>
			 </table>
			</span>
			";
			echo "
			<span id='briga2' > 
			 <table border='0' cellpadding='0' cellspacing='4'>
			 <tr>
			 <td><input name='btn' class=\"$class[8]\" value=$riquadro[8]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[9]\" value=$riquadro[9]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[10]\" value=$riquadro[10]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[11]\" value=$riquadro[11]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[12]\" value=$riquadro[12]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[13]\" value=$riquadro[13]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[14]\" value=$riquadro[14]  type='button'   ></td>
			 </tr>
			 </table>
			</span>
			";
			echo "
			<span id='briga3' > 
			 <table border='0' cellpadding='0' cellspacing='5'>
			 <tr>
			 <td><input name='btn' class=\"$class[15]\" value=$riquadro[15]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[16]\" value=$riquadro[16]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[17]\" value=$riquadro[17]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[18]\" value=$riquadro[18]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[19]\" value=$riquadro[19]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[20]\" value=$riquadro[20]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[21]\" value=$riquadro[21]  type='button'   ></td>
			 </tr>
			 </table>
			 </span>

			";
			echo "
			<span id='briga4' > 
			 <table border='0' cellpadding='0' cellspacing='6'>
			 <tr>
			 <td><input name='btn' class=\"$class[22]\"' value=$riquadro[22]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[23]\"' value=$riquadro[23]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[24]\"' value=$riquadro[24]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[25]\"' value=$riquadro[25]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[26]\"' value=$riquadro[26]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[27]\"' value=$riquadro[27]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[28]\"' value=$riquadro[28]  type='button'   ></td>
			 </tr>
			 </table>
			 </span>
			";
			echo "
			<span id='briga5' > 
			 <table border='0' cellpadding='0' cellspacing='7'>
			 <tr>
			 <td><input name='btn' class=\"$class[29]\"' value=$riquadro[29]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[30]\"' value=$riquadro[30]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[31]\"' value=$riquadro[31]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[32]\"' value=$riquadro[32]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[33]\"' value=$riquadro[33]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[34]\"' value=$riquadro[34]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[35]\"' value=$riquadro[35]  type='button'   ></td>
			 </tr>
			 </table>
			</span>
			";
			echo "
			<span id='briga6' > 
			 <table border='0' cellpadding='0' cellspacing='8'>
			 <tr>
			 <td><input name='btn' class=\"$class[36]\"' value=$riquadro[36]  type='button'  ></td>
			 <td><input name='btn' class=\"$class[37]\"' value=$riquadro[37]  type='button'  ></td>
			 <td><input name='btn' class=\"$class[38]\"' value=$riquadro[38]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[39]\"' value=$riquadro[39]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[40]\"' value=$riquadro[40]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[41]\"' value=$riquadro[41]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[42]\"' value=$riquadro[42]  type='button'   ></td>
			 </tr>
			 </table>
			</span>
			";
			echo "
			<span id='briga7' > 
			 <table border='0' cellpadding='0' cellspacing='9'>
			 <tr>
			 <td><input name='btn' class=\"$class[43]\"' value=$riquadro[43]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[44]\"' value=$riquadro[44]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[45]\"' value=$riquadro[45]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[46]\"' value=$riquadro[46]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[47]\"' value=$riquadro[47]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[48]\"' value=$riquadro[48]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[49]\"' value=$riquadro[49]  type='button'   ></td>
			 </table>
			</span>
			";
			echo "
			<span id='briga8' > 
			 <table border='0' cellpadding='0' cellspacing='10'>
			 <tr>
			 <td><input name='btn' class=\"$class[50]\"' value=$riquadro[50]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[51]\"' value=$riquadro[51]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[52]\"' value=$riquadro[52]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[53]\"' value=$riquadro[53]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[54]\"' value=$riquadro[54]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[55]\"' value=$riquadro[55]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[56]\"' value=$riquadro[56]  type='button'   ></td>
			 </tr>
			 </table>
			</span>
			";
			echo "
			<span id='briga9' > 
			 <table border='0' cellpadding='0' cellspacing='11'>
			 <tr>
			 <td><input name='btn' class=\"$class[57]\"' value=$riquadro[57]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[58]\"' value=$riquadro[58]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[59]\"' value=$riquadro[59]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[60]\"' value=$riquadro[60]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[61]\"' value=$riquadro[61]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[62]\"' value=$riquadro[62]  type='button'   ></td>
			 <td><input name='btn' class=\"$class[63]\"' value=$riquadro[63]  type='button'   ></td>
			 </tr>
			 </table>
			</span>
			";
			
			echo "
			<span id='briga10' > 
			 <table border='0' cellpadding='0' cellspacing='0'>
			 <tr>
			 <td><input name='btn' class=\"$class[64]\"' value=$riquadro[64]  type='button'   ></td>
			 </tr>
			 </table>
			</span>
			</span>
			</form>
			";
			
			// DATI STATISTICI SQUADRA
			echo "<div id='tattica_automatico'>
					<fieldset width='97%'>
					<table id='table_automatico' cellpadding='0' cellspacing='5'>
					<tr>
						<th align='right'>Giocatori:</th>";
			
						$schierati = $formTattica[0]+$formTattica[1]+$formTattica[2] + 1;
			
						if ($schierati == 11){
							echo"<td><center>$schierati</td>";
						}
						else
						{
							echo"<td><center><font color='#ff0000'>$schierati</td>";
						}	
			echo "</tr>
					<tr>";
					echo"<th align='right'>Caratteri:</th>";
						if ($qu_carat >= 5)
						{
							echo"<td><center>$qu_carat</td>";
						}
						else
						{
							echo"<td ><center><font color='#ff0000'>$qu_carat</td>";
						}	
						echo"
					</tr>
					<tr>
						<th align='right'>Forza:</th><td>$Forza</td>
						
					</tr>
					</table>
					</fieldset>
				</div>";
			
			// PERCENTUALE TATTICHE ALLENATE
			echo "<span id='percentuale_auto'>";
			
			$result = mysql_query("SELECT * FROM allena_tattiche WHERE a_id_team=\"$nome_team\"");
			if (!$result)
			{
				echo 'Errore nella query: ' . mysql_error();
				exit();
			}
			$rig = mysql_fetch_array($result) ;
			
			$righi[1] = -10;
			$righi[2] = $rig['ta_press_val'];
			$righi[3] = $rig['ta_contr_val'];
			$righi[4] = $rig['ta_poss_val'];
			$righi[5] = $rig['ta_pall_val'];
			$righi[6] = $rig['ta_gioc_val'];
			$righi[7] = $rig['ta_cate_val'];
			$righi[8] = -10;
			$righi[9] = -10;			
			
			$a_tattica = array($righi,$ListaTattica);
			array_multisort($a_tattica[0], SORT_DESC, $a_tattica[1], SORT_DESC);
			
			echo "<fieldset style='width: 97%;'>";
			echo "<span  style='color:#FFFF00; font-weight:normal;'>";
			
			echo "<table id='percentuale_auto_table' cellpadding='0' cellspacing='0' >";
			for ($ind=0; $ind < 6; $ind++)
			{
				$num = 1 + $ind;
				$wnome = $a_tattica[1][$ind];
				$wskill = round($a_tattica[0][$ind]/327*100,1);
					
				echo "<tr>
						<td>$num&deg;</td>
						<td>$wnome</td>
						<td><center>$wskill %</td>
					  </tr>";
			}
			echo "</table>";
			echo "</span>";
			echo "</fieldset>";
			echo "</span>";
			
			// ELENCO ORDINATO DELLE FORMAZIONI
			echo "<div id='elencoformazione_auto'>";
			echo "<fieldset style='width: 92%;'>";
			echo "<table>";
			echo "<tr>";
			echo "	<th align='left'>Pos.</th>";
			echo "	<th align='left'>Formaz.</th>";
			echo "	<th align='left'>Tattica</th>";
			echo "	<th align='left'>Marc.</th>";
			echo "	<th align='left'>Val.</th>";
			echo "</tr>";
			echo "<tr><th colspan='5'><hr></th></tr>";
			for ($xy = 0; $xy <3192 ; $xy ++)
			{
				$frmz = $wformazionemigliore[1][$xy];
				$tttc = $wtatticamigliore[1][$xy];
				$mrct = $wmarcaturamigliore[1][$xy];
				$vlr  = $wformazionemigliore[0][$xy];
				$posizione = $xy+1;
				if ($mrct == "Marcatura a uomo")	{$mrct = "Uomo";}else{$mrct="Zona";}
				
				$frmz = str_replace(chr(32),"&nbsp;",$frmz);
				//$tttc = str_replace(chr(32),"&nbsp;",$tttc);
				
				echo "	<tr>
							<td>$posizione)</td>
							<td>$frmz</td>
							<td>$tttc</td>
							<td><center>$mrct</td>
							<td><center>$vlr</td>
						</tr>";
			}
			echo"</table>";
			echo "</fieldset>";
			echo "</div>";

			
			/*
			for ($xy = 0; $xy <3192 ; $xy ++)
			{
				$frmz = $wformazionemigliore[1][$xy];
				$tttc = $wtatticamigliore[1][$xy];
				$mrct = $wmarcaturamigliore[1][$xy];
				$vlr  = $wformazionemigliore[0][$xy];
				$posizione = $xy+1;
				if ($mrct == "Marcatura a uomo")	{$mrct = "Uomo";}else{$mrct="Zona";}
				
				echo "	<tr>
							<td>$posizione)</td>
							<td height='16'>$frmz</td>
							<td>$tttc</td>
							<td><center>$mrct</td>
							<td><center>$vlr</td>
						</tr>";
			}*/
			
			//INFORMAZIONI PORTIERE, DIFESA, CENTR. E ATTACCO
			echo "<span id='portiere_auto'>
					<table border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td>Portiere</td>
					</tr>
					<tr>
						<td><center>$Portiere</td>
					</tr>
					</table>
				</span>";

			echo "<span id='difesa_auto'>
					<table border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td>Difesa</td>
					</tr>
					<tr>
						<td><center>$Difesa</td>
					</tr>
					</table>
				</span>";

			echo "<span id='centrocampo_auto'>
					<table border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td>Centrocampo</td>
					</tr>
					<tr>
						<td><center>$Centrocampo</td>
					</tr>
					</table>
				</span>";

			echo "<span id='attacco_auto'>
					<table border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td>Attacco</td>
					</tr>
					<tr>
						<td><center>$Attacco</td>
					</tr>
					</table>
				</span>";
			
			echo "<span id='totale_auto'>
					<table border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td>Totale</td>
					</tr>
					<tr>
						<td><center>$StampaForza</td>
					</tr>
					</table>
				</span>";
			
			
			//PULSANTE PER SALVATAGGIO SQUADRA IN FORMAZIONE
			for ($ii = 1; $ii <=64 ; $ii ++) 
			{
				echo "<input type='hidden' id='ws_riq_$ii' value='$riquadro[$ii]'>";
			}
								
			echo "<span id='invio_auto'>
			<table border='0' cellpadding='0' cellspacing='5'>
			<tr>
				<td><input type='button' id='saveform' value='Salva squadra in' onclick='javascript:saveForm($riquadro);' class='fieldbutton'>
				</td>
				
				<td>
					<select id='ws_formazione' name='ws_formazione' class='fld_y'>
						<option value='Formazione 1'>Formazione 1</option>
						<option value='Formazione 2'>Formazione 2</option>
						<option value='Formazione 3'>Formazione 3</option>
						<option value='Formazione 4'>Formazione 4</option>
						<option value='Formazione 5'>Formazione 5</option>
					</select>
				</td>
			</tr>
			</table>
			</span>
 			";
			
			
			
			

			
			echo "</span>";
			echo "</form>";
		}
		else
		{
			echo "<form name='paginavuota'>";
			echo "<h6><br>Non esistono dati su cui effettuare calcoli.<br></h6>";
			echo "</form>";
		}
		
mysql_close($link);
?>





</body>
</html>
