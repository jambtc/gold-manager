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
	
	put_player_allena(); // inserisce il giocatore in squadra
	
	$('.div_sx_all').simpletip({
		
		offset:[0,0],
		content:'<img src="images/ajax_load.gif" alt="loading" style="margin:10px;" />',
		onShow: function(){
			
			var param = this.getParent().find('span').attr('id');
			
			if($.browser.msie && $.browser.version=='6.0')
			{
				param = this.getParent().find('span').attr('style').match(/id=\"([^\"]+)\"/);
				param = param[1];
			}
			
			this.load('ajax/tips_allenatore.php',{id:param}); 
		} 
	});
	
	
	
})

function tooltip_hide()
{
	$('.tooltip_player').css({ display: 'none'});
}

function put_player_allena()
{
	var box = "";
	for(var ii=1; ii<=4;ii++)
	{
		$( "#contenuto_allena_"+ii ).droppable({
				drop: 	function( e, ui )
						{
							tooltip_hide();
							
							var param = $(ui.draggable).attr('id'); // id del giocatore
							
							var id_box =  $(this).attr('id'); // id del box prescelto
							box = id_box.substr(17,2); // numero id del divbox prescelto
							
							if($.browser.msie && $.browser.version=='6.0')
							{
								param = $(ui.draggable).attr('style').match(/id=\"([^\"]+)\"/);
								param = param[1];
							}
							id=encodeURIComponent(param);
							
							var id_allena = box;
							if (box == 3) { id_allena = 5; }
							if (box == 4) { id_allena = 10; }
							
							// carica il giocatore nel box prescelto di allenamento e aggiorna la pagina a sx
							$(this).load('ajax/add_to_allena.php',{id_allena:id_allena,id:id},
								 function(){
										$('.scr_allena_sx').load('allena_list.php',{ajax:'1'});
						        }
							);
						}
		});
	}
}

function delete_training(id,box)
{
	var id_allena = box;
	if (box == 3) { id_allena = 5; }
	if (box == 4) { id_allena = 10; }
	$( "#contenuto_allena_"+box).load('ajax/add_to_allena.php',{id_allena:id_allena,id:id,del:'1'},
			function(){
				$('.scr_allena_sx').load('allena_list.php',{ajax:'1'});
	        }
	);
}

