// FUNZIONI LISTA TEAM
function Attiva_Pulsante()
{
	document.getElementById('spento').style.display = "none";
	document.getElementById('acceso').style.display = "inline";
}


function Verifica_Maglia(id_player)
{
	maglia = document.getElementById('w_nr').value;
	ruolo = document.getElementById('w_ruolo').value;
	
	
	if (maglia >0 && maglia <100)
	{
		$('.cnt_player_ifthen').load('ajax/data_team_change.php',{id:id_player,w_nr:maglia,w_ruolo:ruolo},
				function(){
					rest = document.getElementById('cnt_player_ifthen').innerHTML;
					if (rest != "VERO")
					{
						jAlert('Numero di maglia ('+rest+') già assegnato!', 'AVVISO!!!');
					}
					else
					{
						document.getElementById('cnt_player').innerHTML = "<fieldset><br /><h2><p align='center' style='color:#0000FF;'>I dati personali sono stati modificati correttamente.</p></h2></fieldset>";
					}
				}
		
		);
		
		
		
	}
	else
	{
		jAlert('\r\nIl numero di maglia deve essere compreso tra 1 e 99.', 'ATTENZIONE!!!');
	}
}



function Vendi_Giocatore(arg1,arg2,arg3,arg4,arg5)
{
	if (arg5 <=15 )
	{
		jAlert('\r\nNon puoi avere meno di 15 giocatori in squadra.', 'ATTENZIONE!!!');
		return false;
	}
				
	valore = arg2.value;
	contratto = arg3.value;
	esperienza = arg4.value;
	
	penale_banca = Math.round(valore*10/100);
	penale_contratto = Math.round(contratto * valore /100);
	penale_esperienza = Math.round((21-esperienza)*valore/100);
	video_esperienza = 21-esperienza;
	
	if (penale_esperienza < 0)
	{
		penale_esperienza = 0;
		video_esperienza = 0;
	}
	if (penale_contratto < 0)
	{
		penale_contratto = 0;
		contratto = 0;
	}
	
	prezzo = valore - penale_banca - penale_contratto - penale_esperienza;
	
	
   
	var testo = "ATTENZIONE!\r\nPer la vendita del giocatore verranno effettuate queste trattenute:\r\n\r\n- incasso BANCA CENTRALE pari al 10% :  €. "+penale_banca+"\r\n- penale sulla durata del contratto pari al "+contratto+"%:  €. "+penale_contratto+"\r\n- penale sull'esperienza del giocatore pari al "+video_esperienza+"%:  €. "+penale_esperienza+"\r\n\r\nPertanto incasserai dalla vendita:  €. "+prezzo+"\r\n\r\n\r\nConfermi la vendita del tuo giocatore al prezzo di  €. "+prezzo+" ?";
	
	jConfirm(testo, 'Richiesta Conferma', 
		function(r) 
		{
			if(r)
			{
				id_player = arg1.value;
				$('.Contenuto_Giocatore').load('ajax/data_team_vendi.php',{id:id_player,guadagno:prezzo});
				$('.Contenuto_Giocatore_right').css({display: 'none'});
				
				// EFFETTUARE L'AGGIORNAMENTO DEL NUMERO DI GIOCATORI
			}
		});
}


function Modifica_Contratto(quanto)
{
	minimo = document.getElementById('wdata0').value;
	
	if (minimo == 0)
	{
		minimo = 1;
	}
	variazione = eval(document.getElementById('wdata1').value) + eval(quanto);
	
	if (variazione <= 0)
	{
		variazione = 0;
	}
	if (variazione >= 30)
	{
		variazione = 30;
	}
	
	nuovo_contratto = eval(minimo)  + eval(variazione);
	document.getElementById('wdata1').value = variazione ; 
	
	moltipl = Math.pow(1.0475,-variazione+29);
	
	stipendio_base = document.getElementById('wdata3').value;
	valore_stipendio = stipendio_base.replace(".","");
	nuovo_stipendio = Math.round(eval(valore_stipendio * moltipl));
	
	document.getElementById('wdata2').value = nuovo_stipendio;
	document.getElementById('wdata4').value = nuovo_contratto;
	
	document.getElementById('visualizza_cont').innerHTML = nuovo_contratto;
	document.getElementById('visualizza_stip').innerHTML = "€. "+nuovo_stipendio;
	document.getElementById('puloff').style.display = "none";
	document.getElementById('pulon').style.display = "inline";
}
	
function Salva_Giocatore(id_player)
{
	var testo = "Vuoi confermare i nuovi dati del contratto?";
	
	jConfirm(testo, 'Richiesta Conferma', 
		function(r) 
		{
			if(r)
			{
				arg3 = document.getElementById('wdata4').value;
				arg4 = document.getElementById('wdata2').value;
		
				$('.cnt_player_change_contratto').load('ajax/data_team_change_contratto.php',{id:id_player,w_cont:arg3,w_stip:arg4});
		
				Nascondi_Elemento('img_conton');
				Visualizza_Elemento('img_contoff');
			}
		});
}


// FUNZIONI FORMAZIONE
function ResetFormazione()
{
	$('.frm_formazione_sx').html("<img src='images/ajax_load.gif' alt='loading' style='margin:10px;'/>");
	$('.frm_formazione_dx').html("<img src='images/ajax_load.gif' alt='loading' style='margin:10px;'/>");
	
	formaz = document.getElementById('ws_formazione').value;
	
	$('.select_form').load('ajax/data_formazione_reset.php',{form:formaz});
	$('.frm_formazione_sx').load('formazione_sx.php',{ajax:'1'});
	$('.frm_formazione_dx').load('formazione_dx.php',{ajax:'1',formazione:formaz});
}

function CambiaFormazione()
{
	$('.frm_formazione_sx').html("<img src='images/ajax_load.gif' alt='loading' style='margin:10px;'/>");
	$('.frm_formazione_dx').html("<img src='images/ajax_load.gif' alt='loading' style='margin:10px;'/>");
	$('.formazione_labels').html("<img src='images/ajax_load.gif' alt='loading' style='margin:10px;'/>");
	
	formaz = document.getElementById('ws_formazione').value;
	
	$('.select_form').load('ajax/data_ffc_change.php',{form:formaz});
	$('.frm_formazione_sx').load('formazione_sx.php',{ajax:'1'});
	$('.formazione_labels').load('formazione_label.php',{label:'0'});
	$('.frm_formazione_dx').load('formazione_dx.php',{ajax:'1',formazione:formaz});
}

function ffc(arg)
{
	var rest1 =	document.getElementById('ws_ffc1').value;
	var rest2 =	document.getElementById('ws_ffc2').value;
	var rest3 =	document.getElementById('ws_ffc3').value;
	var rest_tot = 0;	
		
	if (arg == 1)
	{
		if (document.getElementById('ws_ffc1').checked)
		{
			rest1 = 2;
		}
		else
		{
			rest1 = 0;
		}
	}	
		
	if (arg == 2)
	{
		if (document.getElementById('ws_ffc2').checked)
		{
			rest2 = 4;
		}
		else
		{
			rest2 = 0;
		}
	}	

	if (arg == 3)
	{
		if (document.getElementById('ws_ffc3').checked)
		{
			rest3 = 1;
		}
		else
		{
			rest3 = 0;
		}
	}	
	document.getElementById('ws_ffc1').value = rest1;
	document.getElementById('ws_ffc2').value = rest2;
	document.getElementById('ws_ffc3').value = rest3;
	
	rest_tot = parseInt(rest1) + parseInt(rest2) + parseInt(rest3);	
	
	$('._ffc'+arg).load('ajax/data_ffc.php',{box:arg,forma:rest_tot},
			function(){
				 $('.frm_formazione_sx').load('formazione_sx.php?ajax=1');
			}
	);
	
}

function LabelFormazione(arg)
{
	$('.frm_formazione_dx').html("<img src='images/ajax_load.gif' alt='loading' style='margin:10px;'/>");
	$('.formazione_labels').load('formazione_label.php',{label:arg},
			function(){
				$('.frm_formazione_dx').load('formazione_dx.php?ajax=1&label='+arg);
			}
	);
}

function svuota(box)
{
	if (document.getElementById('bt_'+box).value == 0)
	{
		return false;
	}
	else
	{
		formaz = document.getElementById('ws_formazione').value;
		$('.cl_'+box).load('ajax/team_add_player.php',{box:box,id:"-1"},
				 function(){
						$('.frm_formazione_sx').load('formazione_sx.php',{ajax:'1'});
						$('.frm_formazione_dx').load('formazione_dx.php',{ajax:'1',formazione:formaz});
		        }
		);
	}
}

function Agg_tattiche(arg)
{
	tat = document.getElementById('ws_TipoTattica').value;
	imp = document.getElementById('ws_impegno').value;
	mar = document.getElementById('ws_TipoMarcatura').value;
	fuo = document.getElementById('ws_fuorigioco').value;
	
	$('.ch_tattica').load('ajax/data_formaz_tattica_update.php',{tatt:tat,impe:imp,marc:mar,fuor:fuo}	);
	
}

function ViewHideDIV(arg1,arg2)
{
	document.getElementById(arg1).style.display = "inline";
	document.getElementById(arg2).style.display = "none";
}
/*
		
		$(".form_regole").css({
			height: 170 + 'px',
			'overflow-y': 'scroll',
		});

	}
	else
	{
		document.getElementById('arg').style.display = "none";
		document.getElementById('nuova_regola').style.display = "inline";
		
		$(".form_regole").css({
			height: 190 + 'px',
			'overflow-y': 'scroll',
		});
	}
		
}*/


function insRegola(arg,formaz)
{
	if (arg == 0)
	{
		//var pag = 2;
		var minu = document.getElementById('minuti').value;
		var cond = document.getElementById('condizioni').value;
		var entra = document.getElementById('entra').value;
		var esce = document.getElementById('esce').value;
		
		$('.form_regole').load('ajax/data_regola_insert.php',{tip:'0',minu:minu,cond:cond,entra:entra,esce:esce,formaz:formaz});
	}
	else
	{
		//var pag = 2;
		var minu = document.getElementById('minuti2').value;
		var cond = document.getElementById('condizioni2').value;
		var rego = document.getElementById('regola').value;
		
		$('.form_regole').load('ajax/data_regola_insert.php',{tip:'1',minu:minu,cond:cond,rego:rego,formaz:formaz});
	}
}

function delRegola(arg)
{
	$('.form_regole').load('ajax/data_regola_elimina.php',{id:arg});
}

