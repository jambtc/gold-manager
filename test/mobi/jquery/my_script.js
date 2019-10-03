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


var asx = 0;
var adx = 0;
function cambia_immagine(arg)
{
	if (arg == 1)
	{
			asx ++ ;
			if (asx == 16) { asx = 0; }
			document.sx.src="images/scudo_sx"+asx+".png";
			document.getElementById('logosx').value = asx ;
	}
	else
	{
			adx ++ ;
			if (adx == 16) { adx = 0; }
			document.dx.src="images/scudo_dx"+adx+".png";
			document.getElementById('logodx').value = adx ;
	}
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
