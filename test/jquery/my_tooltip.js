$(document).ready(function(){
	
	$('.my_players').simpletip({
		
		offset:[40,0],
		content:'<img src="images/ajax_load.gif" alt="loading" style="margin:10px;" />',
		onShow: function(){
			
			var param = this.getParent().find('img').attr('id');
			
			if($.browser.msie && $.browser.version=='6.0')
			{
				param = this.getParent().find('img').attr('style').match(/id=\"([^\"]+)\"/);
				param = param[1];
			}
			
			this.load('ajax/tips_players.php',{id:param}); 
		} 
	});
	
	$('.my_players img').draggable({
		containment: 'document',
		opacity: 0.6,
		revert: 'invalid',
		helper: 'clone',
		iframeFix: true ,
		cursor: 'move',
		zIndex: 100
	});
	
	overbox(); // colora di rosso i bordi
	outerbox(); // riporta ai colori originali i bordi dei box
	put_player(); // inserisce il giocatore in squadra
})

function put_player()
{
	var box = "";
	for(var ii=1; ii<74;ii++)
	{
		$( "#mettiqui_"+ii ).droppable({
				drop: 	function( e, ui )
						{
							$('.tooltip_player').hide();
							
							var param = $(ui.draggable).attr('id'); // id del giocatore
							var maglia = $(ui.draggable).attr('name'); // formazione selezionata
							
							var id_box =  $(this).attr('id'); // id del box prescelto
							box = id_box.substr(9,2); // numero id del box prescelto
							
							if($.browser.msie && $.browser.version=='6.0')
							{
								param = $(ui.draggable).attr('style').match(/id=\"([^\"]+)\"/);
								param = param[1];
								maglia = $(ui.draggable).attr('style').match(/name=\"([^\"]+)\"/);
								maglia = param[1];
							}
							id=encodeURIComponent(param);
							maglia=unescape(encodeURIComponent(maglia));
							
							// CONTROLLA SE IL GIOCATORE E' GIA' IN SQUADRA
							esegui = 1;
							if (box < 70)
							{
								control = new Array(); 
								for (ix = 1; ix <= 69; ix++)
								{
									control[ix] = document.getElementById('bt_'+ix).value;
								}
								
								for (iz = 1; iz <= 64; iz++)
								{
									if (control[iz] == maglia)
									{
										alert("Attenzione! Hai gia' in squadra questo giocatore.");
										esegui = 0;
										break;
									}
								}
								for (iz = 65; iz <= 69; iz++)
								{
									if (control[iz] == maglia)
									{
										alert("Attenzione! Hai gia' in panchina questo giocatore.");
										esegui = 0;
										break;
									}
								}
							}
							
							if (esegui != 0)
							{
								formaz = document.getElementById('ws_formazione').value;
								// carica il giocatore in squadra e visualizza la maglia col numero
								$(this).load('ajax/team_add_player.php',{box:box,id:id},
										 function(){
												$('.frm_formazione_sx').load('formazione_sx.php',{ajax:'1'});
												$('.frm_formazione_dx').load('formazione_dx.php',{ajax:'1',formazione:formaz});
								        }
								);
							}
							else
							{
								$('.frm_formazione_sx').load('formazione_sx.php',{ajax:'1'});
								$('.frm_formazione_dx').load('formazione_dx.php',{ajax:'1',formazione:formaz});
							}
						}
		});
	}
}
function overbox()
{
	$('#mettiqui_1').bind('dropover', function(event, ui) {
		 document.field.btn[0].style.border = "1px solid red";
		 document.field.btn[0].style.color  = "red";
	});
	$('#mettiqui_2').bind('dropover', function(event, ui) {
		 document.field.btn[1].style.border = "1px solid red";
		 document.field.btn[1].style.color  = "red";
	});
	$('#mettiqui_3').bind('dropover', function(event, ui) {
		 document.field.btn[2].style.border = "1px solid red";
		 document.field.btn[2].style.color  = "red";
	});
	$('#mettiqui_4').bind('dropover', function(event, ui) {
		 document.field.btn[3].style.border = "1px solid red";
		 document.field.btn[3].style.color  = "red";
	});
	$('#mettiqui_5').bind('dropover', function(event, ui) {
		 document.field.btn[4].style.border = "1px solid red";
		 document.field.btn[4].style.color  = "red";
	});
	$('#mettiqui_6').bind('dropover', function(event, ui) {
		 document.field.btn[5].style.border = "1px solid red";
		 document.field.btn[5].style.color  = "red";
	});
	$('#mettiqui_7').bind('dropover', function(event, ui) {
		 document.field.btn[6].style.border = "1px solid red";
		 document.field.btn[6].style.color  = "red";
	});
	$('#mettiqui_8').bind('dropover', function(event, ui) {
		 document.field.btn[7].style.border = "1px solid red";
		 document.field.btn[7].style.color  = "red";
	});
	$('#mettiqui_9').bind('dropover', function(event, ui) {
		 document.field.btn[8].style.border = "1px solid red";
		 document.field.btn[8].style.color  = "red";
	});
	$('#mettiqui_10').bind('dropover', function(event, ui) {
		 document.field.btn[9].style.border = "1px solid red";
		 document.field.btn[9].style.color  = "red";
	});
	$('#mettiqui_11').bind('dropover', function(event, ui) {
		 document.field.btn[10].style.border = "1px solid red";
		 document.field.btn[10].style.color  = "red";
	});
	$('#mettiqui_12').bind('dropover', function(event, ui) {
		 document.field.btn[11].style.border = "1px solid red";
		 document.field.btn[11].style.color  = "red";
	});
	$('#mettiqui_13').bind('dropover', function(event, ui) {
		 document.field.btn[12].style.border = "1px solid red";
		 document.field.btn[12].style.color  = "red";
	});
	$('#mettiqui_14').bind('dropover', function(event, ui) {
		 document.field.btn[13].style.border = "1px solid red";
		 document.field.btn[13].style.color  = "red";
	});
	$('#mettiqui_15').bind('dropover', function(event, ui) {
		 document.field.btn[14].style.border = "1px solid red";
		 document.field.btn[14].style.color  = "red";
	});
	$('#mettiqui_16').bind('dropover', function(event, ui) {
		 document.field.btn[15].style.border = "1px solid red";
		 document.field.btn[15].style.color  = "red";
	});
	$('#mettiqui_17').bind('dropover', function(event, ui) {
		 document.field.btn[16].style.border = "1px solid red";
		 document.field.btn[16].style.color  = "red";
	});
	$('#mettiqui_18').bind('dropover', function(event, ui) {
		 document.field.btn[17].style.border = "1px solid red";
		 document.field.btn[17].style.color  = "red";
	});
	$('#mettiqui_19').bind('dropover', function(event, ui) {
		 document.field.btn[18].style.border = "1px solid red";
		 document.field.btn[18].style.color  = "red";
	});
	$('#mettiqui_20').bind('dropover', function(event, ui) {
		 document.field.btn[19].style.border = "1px solid red";
		 document.field.btn[19].style.color  = "red";
	});
	$('#mettiqui_21').bind('dropover', function(event, ui) {
		 document.field.btn[20].style.border = "1px solid red";
		 document.field.btn[20].style.color  = "red";
	});
	$('#mettiqui_22').bind('dropover', function(event, ui) {
		 document.field.btn[21].style.border = "1px solid red";
		 document.field.btn[21].style.color  = "red";
	});
	$('#mettiqui_23').bind('dropover', function(event, ui) {
		 document.field.btn[22].style.border = "1px solid red";
		 document.field.btn[22].style.color  = "red";
	});
	$('#mettiqui_24').bind('dropover', function(event, ui) {
		 document.field.btn[23].style.border = "1px solid red";
		 document.field.btn[23].style.color  = "red";
	});
	$('#mettiqui_25').bind('dropover', function(event, ui) {
		 document.field.btn[24].style.border = "1px solid red";
		 document.field.btn[24].style.color  = "red";
	});
	$('#mettiqui_26').bind('dropover', function(event, ui) {
		 document.field.btn[25].style.border = "1px solid red";
		 document.field.btn[25].style.color  = "red";
	});
	$('#mettiqui_27').bind('dropover', function(event, ui) {
		 document.field.btn[26].style.border = "1px solid red";
		 document.field.btn[26].style.color  = "red";
	});
	$('#mettiqui_28').bind('dropover', function(event, ui) {
		 document.field.btn[27].style.border = "1px solid red";
		 document.field.btn[27].style.color  = "red";
	});
	$('#mettiqui_29').bind('dropover', function(event, ui) {
		 document.field.btn[28].style.border = "1px solid red";
		 document.field.btn[28].style.color  = "red";
	});
	$('#mettiqui_30').bind('dropover', function(event, ui) {
		 document.field.btn[29].style.border = "1px solid red";
		 document.field.btn[29].style.color  = "red";
	});
	$('#mettiqui_31').bind('dropover', function(event, ui) {
		 document.field.btn[30].style.border = "1px solid red";
		 document.field.btn[30].style.color  = "red";
	});
	$('#mettiqui_32').bind('dropover', function(event, ui) {
		 document.field.btn[31].style.border = "1px solid red";
		 document.field.btn[31].style.color  = "red";
	});
	$('#mettiqui_33').bind('dropover', function(event, ui) {
		 document.field.btn[32].style.border = "1px solid red";
		 document.field.btn[32].style.color  = "red";
	});
	$('#mettiqui_34').bind('dropover', function(event, ui) {
		 document.field.btn[33].style.border = "1px solid red";
		 document.field.btn[33].style.color  = "red";
	});
	$('#mettiqui_35').bind('dropover', function(event, ui) {
		 document.field.btn[34].style.border = "1px solid red";
		 document.field.btn[34].style.color  = "red";
	});
	$('#mettiqui_36').bind('dropover', function(event, ui) {
		 document.field.btn[35].style.border = "1px solid red";
		 document.field.btn[35].style.color  = "red";
	});
	$('#mettiqui_37').bind('dropover', function(event, ui) {
		 document.field.btn[36].style.border = "1px solid red";
		 document.field.btn[36].style.color  = "red";
	});
	$('#mettiqui_38').bind('dropover', function(event, ui) {
		 document.field.btn[37].style.border = "1px solid red";
		 document.field.btn[37].style.color  = "red";
	});
	$('#mettiqui_39').bind('dropover', function(event, ui) {
		 document.field.btn[38].style.border = "1px solid red";
		 document.field.btn[38].style.color  = "red";
	});
	$('#mettiqui_40').bind('dropover', function(event, ui) {
		 document.field.btn[39].style.border = "1px solid red";
		 document.field.btn[39].style.color  = "red";
	});
	$('#mettiqui_41').bind('dropover', function(event, ui) {
		 document.field.btn[40].style.border = "1px solid red";
		 document.field.btn[40].style.color  = "red";
	});
	$('#mettiqui_42').bind('dropover', function(event, ui) {
		 document.field.btn[41].style.border = "1px solid red";
		 document.field.btn[41].style.color  = "red";
	});
	$('#mettiqui_43').bind('dropover', function(event, ui) {
		 document.field.btn[42].style.border = "1px solid red";
		 document.field.btn[42].style.color  = "red";
	});
	$('#mettiqui_44').bind('dropover', function(event, ui) {
		 document.field.btn[43].style.border = "1px solid red";
		 document.field.btn[43].style.color  = "red";
	});
	$('#mettiqui_45').bind('dropover', function(event, ui) {
		 document.field.btn[44].style.border = "1px solid red";
		 document.field.btn[44].style.color  = "red";
	});
	$('#mettiqui_46').bind('dropover', function(event, ui) {
		 document.field.btn[45].style.border = "1px solid red";
		 document.field.btn[45].style.color  = "red";
	});
	$('#mettiqui_47').bind('dropover', function(event, ui) {
		 document.field.btn[46].style.border = "1px solid red";
		 document.field.btn[46].style.color  = "red";
	});
	$('#mettiqui_48').bind('dropover', function(event, ui) {
		 document.field.btn[47].style.border = "1px solid red";
		 document.field.btn[47].style.color  = "red";
	});
	$('#mettiqui_49').bind('dropover', function(event, ui) {
		 document.field.btn[48].style.border = "1px solid red";
		 document.field.btn[48].style.color  = "red";
	});
	$('#mettiqui_50').bind('dropover', function(event, ui) {
		 document.field.btn[49].style.border = "1px solid red";
		 document.field.btn[49].style.color  = "red";
	});
	$('#mettiqui_51').bind('dropover', function(event, ui) {
		 document.field.btn[50].style.border = "1px solid red";
		 document.field.btn[50].style.color  = "red";
	});
	$('#mettiqui_52').bind('dropover', function(event, ui) {
		 document.field.btn[51].style.border = "1px solid red";
		 document.field.btn[51].style.color  = "red";
	});
	$('#mettiqui_53').bind('dropover', function(event, ui) {
		 document.field.btn[52].style.border = "1px solid red";
		 document.field.btn[52].style.color  = "red";
	});
	$('#mettiqui_54').bind('dropover', function(event, ui) {
		 document.field.btn[53].style.border = "1px solid red";
		 document.field.btn[53].style.color  = "red";
	});
	$('#mettiqui_55').bind('dropover', function(event, ui) {
		 document.field.btn[54].style.border = "1px solid red";
		 document.field.btn[54].style.color  = "red";
	});
	$('#mettiqui_56').bind('dropover', function(event, ui) {
		 document.field.btn[55].style.border = "1px solid red";
		 document.field.btn[55].style.color  = "red";
	});
	$('#mettiqui_57').bind('dropover', function(event, ui) {
		 document.field.btn[56].style.border = "1px solid red";
		 document.field.btn[56].style.color  = "red";
	});
	$('#mettiqui_58').bind('dropover', function(event, ui) {
		 document.field.btn[57].style.border = "1px solid red";
		 document.field.btn[57].style.color  = "red";
	});
	$('#mettiqui_59').bind('dropover', function(event, ui) {
		 document.field.btn[58].style.border = "1px solid red";
		 document.field.btn[58].style.color  = "red";
	});
	$('#mettiqui_60').bind('dropover', function(event, ui) {
		 document.field.btn[59].style.border = "1px solid red";
		 document.field.btn[59].style.color  = "red";
	});
	$('#mettiqui_61').bind('dropover', function(event, ui) {
		 document.field.btn[60].style.border = "1px solid red";
		 document.field.btn[60].style.color  = "red";
	});
	$('#mettiqui_62').bind('dropover', function(event, ui) {
		 document.field.btn[61].style.border = "1px solid red";
		 document.field.btn[61].style.color  = "red";
	});
	$('#mettiqui_63').bind('dropover', function(event, ui) {
		 document.field.btn[62].style.border = "1px solid red";
		 document.field.btn[62].style.color  = "red";
	});
	$('#mettiqui_64').bind('dropover', function(event, ui) {
		 document.field.btn[63].style.border = "1px solid red";
		 document.field.btn[63].style.color  = "red";
	});
	$('#mettiqui_65').bind('dropover', function(event, ui) {
		 document.field.btn[64].style.border = "1px solid red";
		 document.field.btn[64].style.color  = "red";
	});
	$('#mettiqui_66').bind('dropover', function(event, ui) {
		 document.field.btn[65].style.border = "1px solid red";
		 document.field.btn[65].style.color  = "red";
	});
	$('#mettiqui_67').bind('dropover', function(event, ui) {
		 document.field.btn[66].style.border = "1px solid red";
		 document.field.btn[66].style.color  = "red";
	});
	$('#mettiqui_68').bind('dropover', function(event, ui) {
		 document.field.btn[67].style.border = "1px solid red";
		 document.field.btn[67].style.color  = "red";
	});
	$('#mettiqui_69').bind('dropover', function(event, ui) {
		 document.field.btn[68].style.border = "1px solid red";
		 document.field.btn[68].style.color  = "red";
	});
	$('#mettiqui_70').bind('dropover', function(event, ui) {
		 document.field.btn[69].style.border = "1px solid red";
		 document.field.btn[69].style.color  = "red";
	});
	$('#mettiqui_71').bind('dropover', function(event, ui) {
		 document.field.btn[70].style.border = "1px solid red";
		 document.field.btn[70].style.color  = "red";
	});
	$('#mettiqui_72').bind('dropover', function(event, ui) {
		 document.field.btn[71].style.border = "1px solid red";
		 document.field.btn[71].style.color  = "red";
	});
	$('#mettiqui_73').bind('dropover', function(event, ui) {
		 document.field.btn[72].style.border = "1px solid red";
		 document.field.btn[72].style.color  = "red";
	});
}

function outerbox()
{
	$('#mettiqui_1').bind('dropout', function(event, ui) {
		 document.field.btn[0].style.border = "0px";
		 document.field.btn[0].style.color  = "white";
	});
	$('#mettiqui_2').bind('dropout', function(event, ui) {
		 document.field.btn[1].style.border = "0px";
		 document.field.btn[1].style.color  = "white";
	});
	$('#mettiqui_3').bind('dropout', function(event, ui) {
		 document.field.btn[2].style.border = "0px";
		 document.field.btn[2].style.color  = "white";
	});
	$('#mettiqui_4').bind('dropout', function(event, ui) {
		 document.field.btn[3].style.border = "0px";
		 document.field.btn[3].style.color  = "white";
	});
	$('#mettiqui_5').bind('dropout', function(event, ui) {
		 document.field.btn[4].style.border = "0px";
		 document.field.btn[4].style.color  = "white";
	});
	$('#mettiqui_6').bind('dropout', function(event, ui) {
		 document.field.btn[5].style.border = "0px";
		 document.field.btn[5].style.color  = "white";
	});
	$('#mettiqui_7').bind('dropout', function(event, ui) {
		 document.field.btn[6].style.border = "0px";
		 document.field.btn[6].style.color  = "white";
	});
	$('#mettiqui_8').bind('dropout', function(event, ui) {
		 document.field.btn[7].style.border = "0px";
		 document.field.btn[7].style.color  = "white";
	});
	$('#mettiqui_9').bind('dropout', function(event, ui) {
		 document.field.btn[8].style.border = "0px";
		 document.field.btn[8].style.color  = "white";
	});
	$('#mettiqui_10').bind('dropout', function(event, ui) {
		 document.field.btn[9].style.border = "0px";
		 document.field.btn[9].style.color  = "white";
	});
	$('#mettiqui_11').bind('dropout', function(event, ui) {
		 document.field.btn[10].style.border = "0px";
		 document.field.btn[10].style.color  = "white";
	});
	$('#mettiqui_12').bind('dropout', function(event, ui) {
		 document.field.btn[11].style.border = "0px";
		 document.field.btn[11].style.color  = "white";
	});
	$('#mettiqui_13').bind('dropout', function(event, ui) {
		 document.field.btn[12].style.border = "0px";
		 document.field.btn[12].style.color  = "white";
	});
	$('#mettiqui_14').bind('dropout', function(event, ui) {
		 document.field.btn[13].style.border = "0px";
		 document.field.btn[13].style.color  = "white";
	});
	$('#mettiqui_15').bind('dropout', function(event, ui) {
		 document.field.btn[14].style.border = "0px";
		 document.field.btn[14].style.color  = "white";
	});
	$('#mettiqui_16').bind('dropout', function(event, ui) {
		 document.field.btn[15].style.border = "0px";
		 document.field.btn[15].style.color  = "white";
	});
	$('#mettiqui_17').bind('dropout', function(event, ui) {
		 document.field.btn[16].style.border = "0px";
		 document.field.btn[16].style.color  = "white";
	});
	$('#mettiqui_18').bind('dropout', function(event, ui) {
		 document.field.btn[17].style.border = "0px";
		 document.field.btn[17].style.color  = "white";
	});
	$('#mettiqui_19').bind('dropout', function(event, ui) {
		 document.field.btn[18].style.border = "0px";
		 document.field.btn[18].style.color  = "white";
	});
	$('#mettiqui_20').bind('dropout', function(event, ui) {
		 document.field.btn[19].style.border = "0px";
		 document.field.btn[19].style.color  = "white";
	});
	$('#mettiqui_21').bind('dropout', function(event, ui) {
		 document.field.btn[20].style.border = "0px";
		 document.field.btn[20].style.color  = "white";
	});
	$('#mettiqui_22').bind('dropout', function(event, ui) {
		 document.field.btn[21].style.border = "0px";
		 document.field.btn[21].style.color  = "white";
	});
	$('#mettiqui_23').bind('dropout', function(event, ui) {
		 document.field.btn[22].style.border = "0px";
		 document.field.btn[22].style.color  = "white";
	});
	$('#mettiqui_24').bind('dropout', function(event, ui) {
		 document.field.btn[23].style.border = "0px";
		 document.field.btn[23].style.color  = "white";
	});
	$('#mettiqui_25').bind('dropout', function(event, ui) {
		 document.field.btn[24].style.border = "0px";
		 document.field.btn[24].style.color  = "white";
	});
	$('#mettiqui_26').bind('dropout', function(event, ui) {
		 document.field.btn[25].style.border = "0px";
		 document.field.btn[25].style.color  = "white";
	});
	$('#mettiqui_27').bind('dropout', function(event, ui) {
		 document.field.btn[26].style.border = "0px";
		 document.field.btn[26].style.color  = "white";
	});
	$('#mettiqui_28').bind('dropout', function(event, ui) {
		 document.field.btn[27].style.border = "0px";
		 document.field.btn[27].style.color  = "white";
	});
	$('#mettiqui_29').bind('dropout', function(event, ui) {
		 document.field.btn[28].style.border = "0px";
		 document.field.btn[28].style.color  = "white";
	});
	$('#mettiqui_30').bind('dropout', function(event, ui) {
		 document.field.btn[29].style.border = "0px";
		 document.field.btn[29].style.color  = "white";
	});
	$('#mettiqui_31').bind('dropout', function(event, ui) {
		 document.field.btn[30].style.border = "0px";
		 document.field.btn[30].style.color  = "white";
	});
	$('#mettiqui_32').bind('dropout', function(event, ui) {
		 document.field.btn[31].style.border = "0px";
		 document.field.btn[31].style.color  = "white";
	});
	$('#mettiqui_33').bind('dropout', function(event, ui) {
		 document.field.btn[32].style.border = "0px";
		 document.field.btn[32].style.color  = "white";
	});
	$('#mettiqui_34').bind('dropout', function(event, ui) {
		 document.field.btn[33].style.border = "0px";
		 document.field.btn[33].style.color  = "white";
	});
	$('#mettiqui_35').bind('dropout', function(event, ui) {
		 document.field.btn[34].style.border = "0px";
		 document.field.btn[34].style.color  = "white";
	});
	$('#mettiqui_36').bind('dropout', function(event, ui) {
		 document.field.btn[35].style.border = "0px";
		 document.field.btn[35].style.color  = "white";
	});
	$('#mettiqui_37').bind('dropout', function(event, ui) {
		 document.field.btn[36].style.border = "0px";
		 document.field.btn[36].style.color  = "white";
	});
	$('#mettiqui_38').bind('dropout', function(event, ui) {
		 document.field.btn[37].style.border = "0px";
		 document.field.btn[37].style.color  = "white";
	});
	$('#mettiqui_39').bind('dropout', function(event, ui) {
		 document.field.btn[38].style.border = "0px";
		 document.field.btn[38].style.color  = "white";
	});
	$('#mettiqui_40').bind('dropout', function(event, ui) {
		 document.field.btn[39].style.border = "0px";
		 document.field.btn[39].style.color  = "white";
	});
	$('#mettiqui_41').bind('dropout', function(event, ui) {
		 document.field.btn[40].style.border = "0px";
		 document.field.btn[40].style.color  = "white";
	});
	$('#mettiqui_42').bind('dropout', function(event, ui) {
		 document.field.btn[41].style.border = "0px";
		 document.field.btn[41].style.color  = "white";
	});
	$('#mettiqui_43').bind('dropout', function(event, ui) {
		 document.field.btn[42].style.border = "0px";
		 document.field.btn[42].style.color  = "white";
	});
	$('#mettiqui_44').bind('dropout', function(event, ui) {
		 document.field.btn[43].style.border = "0px";
		 document.field.btn[43].style.color  = "white";
	});
	$('#mettiqui_45').bind('dropout', function(event, ui) {
		 document.field.btn[44].style.border = "0px";
		 document.field.btn[44].style.color  = "white";
	});
	$('#mettiqui_46').bind('dropout', function(event, ui) {
		 document.field.btn[45].style.border = "0px";
		 document.field.btn[45].style.color  = "white";
	});
	$('#mettiqui_47').bind('dropout', function(event, ui) {
		 document.field.btn[46].style.border = "0px";
		 document.field.btn[46].style.color  = "white";
	});
	$('#mettiqui_48').bind('dropout', function(event, ui) {
		 document.field.btn[47].style.border = "0px";
		 document.field.btn[47].style.color  = "white";
	});
	$('#mettiqui_49').bind('dropout', function(event, ui) {
		 document.field.btn[48].style.border = "0px";
		 document.field.btn[48].style.color  = "white";
	});
	$('#mettiqui_50').bind('dropout', function(event, ui) {
		 document.field.btn[49].style.border = "0px";
		 document.field.btn[49].style.color  = "white";
	});
	$('#mettiqui_51').bind('dropout', function(event, ui) {
		 document.field.btn[50].style.border = "0px";
		 document.field.btn[50].style.color  = "white";
	});
	$('#mettiqui_52').bind('dropout', function(event, ui) {
		 document.field.btn[51].style.border = "0px";
		 document.field.btn[51].style.color  = "white";
	});
	$('#mettiqui_53').bind('dropout', function(event, ui) {
		 document.field.btn[52].style.border = "0px";
		 document.field.btn[52].style.color  = "white";
	});
	$('#mettiqui_54').bind('dropout', function(event, ui) {
		 document.field.btn[53].style.border = "0px";
		 document.field.btn[53].style.color  = "white";
	});
	$('#mettiqui_55').bind('dropout', function(event, ui) {
		 document.field.btn[54].style.border = "0px";
		 document.field.btn[54].style.color  = "white";
	});
	$('#mettiqui_56').bind('dropout', function(event, ui) {
		 document.field.btn[55].style.border = "0px";
		 document.field.btn[55].style.color  = "white";
	});
	$('#mettiqui_57').bind('dropout', function(event, ui) {
		 document.field.btn[56].style.border = "0px";
		 document.field.btn[56].style.color  = "white";
	});
	$('#mettiqui_58').bind('dropout', function(event, ui) {
		 document.field.btn[57].style.border = "0px";
		 document.field.btn[57].style.color  = "white";
	});
	$('#mettiqui_59').bind('dropout', function(event, ui) {
		 document.field.btn[58].style.border = "0px";
		 document.field.btn[58].style.color  = "white";
	});
	$('#mettiqui_60').bind('dropout', function(event, ui) {
		 document.field.btn[59].style.border = "0px";
		 document.field.btn[59].style.color  = "white";
	});
	$('#mettiqui_61').bind('dropout', function(event, ui) {
		 document.field.btn[60].style.border = "0px";
		 document.field.btn[60].style.color  = "white";
	});
	$('#mettiqui_62').bind('dropout', function(event, ui) {
		 document.field.btn[61].style.border = "0px";
		 document.field.btn[61].style.color  = "white";
	});
	$('#mettiqui_63').bind('dropout', function(event, ui) {
		 document.field.btn[62].style.border = "0px";
		 document.field.btn[62].style.color  = "white";
	});
	$('#mettiqui_64').bind('dropout', function(event, ui) {
		 document.field.btn[63].style.border = "0px";
		 document.field.btn[63].style.color  = "white";
	});
	$('#mettiqui_65').bind('dropout', function(event, ui) {
		 document.field.btn[64].style.border = "0px";
		 document.field.btn[64].style.color  = "white";
	});
	$('#mettiqui_66').bind('dropout', function(event, ui) {
		 document.field.btn[65].style.border = "0px";
		 document.field.btn[65].style.color  = "white";
	});
	$('#mettiqui_67').bind('dropout', function(event, ui) {
		 document.field.btn[66].style.border = "0px";
		 document.field.btn[66].style.color  = "white";
	});
	$('#mettiqui_68').bind('dropout', function(event, ui) {
		 document.field.btn[67].style.border = "0px";
		 document.field.btn[67].style.color  = "white";
	});
	$('#mettiqui_69').bind('dropout', function(event, ui) {
		 document.field.btn[68].style.border = "0px";
		 document.field.btn[68].style.color  = "white";
	});
	$('#mettiqui_70').bind('dropout', function(event, ui) {
		 document.field.btn[69].style.border = "0px";
		 document.field.btn[69].style.color  = "white";
	});
	$('#mettiqui_71').bind('dropout', function(event, ui) {
		 document.field.btn[70].style.border = "0px";
		 document.field.btn[70].style.color  = "white";
	});
	$('#mettiqui_72').bind('dropout', function(event, ui) {
		 document.field.btn[71].style.border = "0px";
		 document.field.btn[71].style.color  = "white";
	});
	$('#mettiqui_73').bind('dropout', function(event, ui) {
		 document.field.btn[72].style.border = "0px";
		 document.field.btn[72].style.color  = "white";
	});
}




function jam_tooltip(param)
{
	indirizzo="ajax/tips_field.php?id="+param;
	window.parent.frames['box_campo'].location.href=indirizzo;	
}

function tooltip_hide()
{
	$('.tooltip_player').css({ display: 'none'});
}

