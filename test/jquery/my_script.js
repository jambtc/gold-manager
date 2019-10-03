function allarga_schermo(totale)
{
	// CERCA LA DIMENSIONE MAX DELLO SCHERMO
	larghezza = Math.round(window.screen.width /100 *97.5,0) ;
	altezza = Math.round(window.screen.height /100 *81,0) ;
	
	// Allarga il contenitore principale al max dello schermo
	$('.contenitore_principale').css({	width: larghezza + 'px', height: altezza + 'px'	});
}

function allarga_maschera(quale,lar,alt)
{
	qualediv = "div_"+quale;
	qualeimg = "img_"+quale;
	qualecnt = "cnt_"+quale;

	larghezza = Math.round(window.screen.width /100 * lar,0) ;
	altezza = Math.round(window.screen.height /100 * alt,0);

	div = "."+qualediv;
	img = "."+qualeimg;
	cnt = "."+qualecnt;
	
	// Allarga i contenitori al valore indicato in percentuale
	$(div).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(img).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(cnt).css({ position: 'relative',
			   	 top: -altezza + 'px', 
				 padding: '10px 10px 10px 10px', 
				 'font-size': 12 + 'px' });
}

function allarga_maskwscroll(quale,lar,alt)
{
	qualediv = "div_"+quale;
	qualeimg = "img_"+quale;
	qualecnt = "cnt_"+quale;
	qualescr = "scr_"+quale;

	larghezza = Math.round(window.screen.width /100 * lar,0) ;
	altezza = Math.round(window.screen.height /100 * alt,0);

	div = "."+qualediv;
	img = "."+qualeimg;
	cnt = "."+qualecnt;
	scr = "."+qualescr;
	
	// Allarga i contenitori al valore indicato in percentuale
	$(div).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(img).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(cnt).css({ position: 'relative',
			   	 top: -altezza + 'px', 
				 padding: '10px 10px 10px 10px', 
				 'font-size': 12 + 'px' });
	
	$(scr).css({ height: altezza-30 + 'px', 'overflow-y': 'scroll' });
}

function allarga_msk_frame(quale,lar,alt)
{
	qualediv = "div_"+quale;
	qualeimg = "img_"+quale;
	qualecnt = "cnt_"+quale;
	qualefrm = "frm_"+quale;

	larghezza = Math.round(window.screen.width /100 * lar,0) ;
	altezza = Math.round(window.screen.height /100 * alt,0);

	div = "."+qualediv;
	img = "."+qualeimg;
	cnt = "."+qualecnt;
	frm = "."+qualefrm;
	
	// Allarga i contenitori al valore indicato in percentuale
	$(div).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(img).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(cnt).css({ position: 'relative',
			   	 top: -altezza + 'px', 
				 padding: '10px 10px 10px 10px', 
				 'font-size': 12 + 'px' });
	
	$(frm).css({ width: larghezza-30  + 'px', height: altezza-30 + 'px','scrolling': 'yes'  });
}
function allarga_msk_frame_bis(quale,lar,alt)
{
	qualediv = "div_"+quale;
	qualeimg = "img_"+quale;
	qualecnt = "cnt_"+quale;
	qualefrm = "frm_"+quale;

	larghezza = Math.round(window.screen.width /100 * lar,0) ;
	altezza = Math.round(window.screen.height /100 * alt,0);

	div = "."+qualediv;
	img = "."+qualeimg;
	cnt = "."+qualecnt;
	frm = "."+qualefrm;
	
	// Allarga i contenitori al valore indicato in percentuale
	$(div).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(img).css({ width: larghezza + 'px', height: altezza + 'px'	});
	$(cnt).css({ position: 'relative',
			   	 top: -altezza + 'px', 
				 padding: '10px 10px 10px 10px', 
				 'font-size': 12 + 'px' });
	
	$(frm).css({ width: larghezza-30  + 'px', height: altezza-90 + 'px','scrolling': 'yes'  });
}

var scudo_top = 0;
var scudo_middle = 0;
var scudo_bottom_left = 0;
var scudo_bottom_center = 0;
var scudo_bottom_right = 0;
function Change_Scudo(nome)
{
	div = "."+nome;
	if (nome == 'scudo_top')
	{
		scudo_top ++;
		if (scudo_top > 17)
		{
			scudo_top = 0;
		}
		url = "url(images/scudo/"+nome+"_"+scudo_top+".png)";
		document.getElementById(nome).value = scudo_top ;
	}
	if (nome == 'scudo_middle')
	{
		scudo_middle ++;
		if (scudo_middle > 17)
		{
			scudo_middle = 0;
		}
		url = "url(images/scudo/"+nome+"_"+scudo_middle+".png)";
		document.getElementById(nome).value = scudo_middle ;
	}
	if (nome == 'scudo_bottom_left')
	{
		scudo_bottom_left ++;
		if (scudo_bottom_left > 17)
		{
			scudo_bottom_left = 0;
		}
		url = "url(images/scudo/"+nome+"_"+scudo_bottom_left+".png)";
		document.getElementById(nome).value = scudo_bottom_left ;
	}
	if (nome == 'scudo_bottom_center')
	{
		scudo_bottom_center ++;
		if (scudo_bottom_center > 17)
		{
			scudo_bottom_center = 0;
		}
		url = "url(images/scudo/"+nome+"_"+scudo_bottom_center+".png)";
		document.getElementById(nome).value = scudo_bottom_center ;
	}
	if (nome == 'scudo_bottom_right')
	{
		scudo_bottom_right ++;
		if (scudo_bottom_right > 17)
		{
			scudo_bottom_right = 0;
		}
		url = "url(images/scudo/"+nome+"_"+scudo_bottom_right+".png)";
		document.getElementById(nome).value = scudo_bottom_right ;
	}
	$(div).css ({'background-image':  url });
}


function Visualizza(arg)
{
	//document.getElementById('div_stat_giocatori').style.display = "none";
	document.getElementById('div_stat_dx').style.display = "inline";
	document.getElementById('stat_titolo').innerHTML = arg;
}
function Visualizza_staff(arg)
{
	document.getElementById('div_stat_staff').style.display = "block";
	document.getElementById('div_stat_dx').style.display = "inline";
	document.getElementById('stat_titolo').innerHTML = arg;
}
function Nascondi_Elemento(arg)
{
	document.getElementById(arg).style.display = 'none';
}
function Visualizza_Elemento(arg)
{
	document.getElementById(arg).style.display = 'inline';
}


function CambiaGrafico(arg)
{
	arg3 = document.getElementById('ws_random').value ;	
	
	nfile = arg3+"_grafico_"+arg;
	indirizzo="grafico.php?atl="+nfile;
	
	window.frames['grafico'].location.href=indirizzo;
}
function CambiaGraficoPlayer(arg)
{
 	arg2 = document.getElementById('ws_team').value ;
	arg3 = document.getElementById('ws_random').value ;
	
	document.graf.src="generated/"+arg2+arg3+"_giocatore"+arg+".png";
}	

function winpiccola(theURL,winName,features)
{
	//alert(theURL+" "+winName+" "+features);
	window.open(theURL,'',features);
}

function Over(quale)
{
	document.getElementById('tr_'+quale).style.background = '#0033ff';
}
function Out(quale)
{
	(quale % 2 == 0) ? vecchio = "#009000" : vecchio = "#009966"; 
	document.getElementById('tr_'+quale).style.background = vecchio;
}

function OverStaff(quale)
{
	document.getElementById('tr_'+quale).style.background = '#ffffff';
}
function OutStaff(quale)
{
	document.getElementById('tr_'+quale).style.background = '';
}


function myFrame(target,lnk,id)
{
	if (document.getElementById(target).style.display == 'none')
	{
		document.getElementById(target).style.display = 'inline-block';
		
	}
	//target = "."+target;
	
	$("."+target).load(lnk,{id:id});
}







function AumentaStipendio(quanto)
{
	variazione = eval(document.getElementById('ws_mot').value) + eval(quanto);
	
	if (variazione <= 100)
	{
		stipendio = document.getElementById('ws_sti').value;
		
		aumento = eval(stipendio)/100 * eval(quanto);
		
		document.getElementById('puloff').style.display = "none";
		document.getElementById('pulon').style.display = "inline";
		
		nuovo_stipendio = Math.round(eval(aumento)  + eval(stipendio));
		
		document.getElementById('ws_mot').value = variazione ; 
		document.getElementById('ws_sti').value = nuovo_stipendio ; 
		
		document.getElementById('td_mot').innerHTML = variazione;
		document.getElementById('td_sti').innerHTML = "€. "+nuovo_stipendio;
	}
	return false;
}
function Istruisci_Staff(arg1)
{
	id_staff=arg1.value;
	
	if (!(confirm("Vuoi iniziare un corso di addestramento per il tuo staff?")))
	{
    	return false;
	}
	else
	{
		indirizzo="data_staff_istruisci.php?id="+id_staff;
		window.location.href=indirizzo;
	}
}
function Licenzia_Staff(arg1)
{
  id_staff=arg1.value;
   
  if (!(confirm("ATTENZIONE stai per licenziare un elemento del tuo staff. Perderai tutti i suoi dati relativi alle statistiche! Continuo?")))
  {
    return false;
  }
  else
  {
	  indirizzo="data_staff_elimina.php?id="+id_staff;
	  window.location.href=indirizzo;
  }
}
function Salva_Staff()
{
	id = document.getElementById('ws_id').value;
	sti = document.getElementById('ws_sti').value;
	mot = document.getElementById('ws_mot').value;
			
	if (!(confirm("Vuoi confermare il nuovo stipendio?")))
	{
    	return false;
	}
	else
	{
		indirizzo="data_staff_modifica.php?id="+id+"&sti="+sti+"&mot="+mot;
		window.location.href=indirizzo;
	}
}
function Elimina_Candidato(arg1)
{
  if (!(confirm("Elimino il candidato?")))
  {
    return false;
  }
  else
  {
  	id_staff=arg1.value;
	indirizzo="data_staff_elimina_candidato.php?delete="+id_staff;
  
  	window.parent.location.href=indirizzo;
  }
}
function Contratta_Candidato()
{
	if (!(confirm("Avvio la contrattazione?")))
	{
    	return false;
	}
	else
	{
		id_staff = document.getElementById('ws_id').value;
		tentativi = document.getElementById('ws_tenta').value;
		stipendio = document.getElementById('ws_sti').value;
		
		contrattazioni = 4 - tentativi;
		
		if (tentativi == 4)
		{
			probabilita = 90;
		}
		else if (tentativi == 3)
		{
			probabilita = 80;
		}
		else if (tentativi == 2)
		{
			probabilita = 70;
		}
		else if (tentativi == 1)
		{
			probabilita = 60;
		}
		else if (tentativi == 0)
		{
			return;
		}
		
		variabile = Math.round(100*Math.random());
		
		if (variabile <= probabilita) // tentativo riuscito
		{
			alert("ok");
			//document.getElementById('esito_trat').innerHTML = "Positivo!";
			//contrattazioni = contrattazioni +1;
		}
		else	// tentativo fallito
		{
			alert("male");
			//document.getElementById('esito_trat').innerHTML = "Negativo!";
			//contrattazioni = 5;
		}
			
		//document.getElementById('td_motivazione').innerHTML = variazione;
		//document.getElementById('td_stipendio').innerHTML = "€. "+nuovo_stipendio;
		//indirizzo="data_staff_contratta.php?id="+id_staff+"&contra="+contrattazioni;
	  
		//window.parent.location.href=indirizzo;
		//window.parent.frames['contra'].location.href=indirizzo;
	}
 
}
function Assumi_Candidato()
{
	if (!(confirm("Assumo il candidato?")))
	{
    	return false;
	}
	else
	{
		id_staff = document.getElementById('ws_id').value;
		stipendio = document.getElementById('ws_nuovo').value;
	
		indirizzo="data_staff_assumi.php?id="+id_staff+"&stip="+stipendio;
	
		//window.parent.frames['contra'].location.href=indirizzo;
		window.top.location.href=indirizzo;
	}
}
function Investimento_Salva()
{
	var gio = document.getElementById('giovani').value;
	var ski = document.getElementById('skill').value;
	$('.new_invest').load('ajax/data_primavera_aggiorna.php',{giovani:gio,skill:ski},
			 function(){
				$('.corpo_intestazione').load('pri_investimento.php?ajax=1');
			}
	);
}
function Primavera_MostraSkill(arg)
{
	var gio = document.getElementById('giovani').value;
	$('.td_skill').load('ajax/data_primavera_skill.php',{giovani:gio,base:arg});
}
function Primavera_Invest(arg)
{
	var gio = document.getElementById('giovani').value;
	var ski = document.getElementById('skill').value;
	
	esito = gio * ski * arg;

	document.getElementById('new_invest').innerHTML = esito;
}


function chatIns(arg)
{
	var testo = arg.value;
	$('.chatbox').load('ajax/chat_ins.php',{testo:testo});
	document.getElementById('input_testo').value = "";
}

function cronochat(n)
{
	conta = eval(n)-1;
	
	document.getElementById('contachat').innerHTML = conta;
			
    if (n > 1)
	{
		setTimeout("cronochat(conta)",1000); //aspetto x secondi (1000 = 1sec.) e richiamo la funzione//5000
	}
	else
	{
		$('.chatbox').load('ajax/chat_ins.php?testo=');
		$('.chatavatars').load('ajax/chat_avatar.php?testo=');
		
	}
}