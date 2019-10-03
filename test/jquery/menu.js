jQuery.fn.center = function () {
            this.css("left", ( $(window).width() - this.width() ) / 2 + $(window).scrollLeft() + "px");
            return this;
        }

        //document ready
        $(function(){
            $('#dashboard').center();
        });
        
        //window resize
        $(window).resize(function() {
            $('#dashboard').center();
        });
		
		$(document).ready(function(){
			$('.dash').simpletip({
				
				offset:[-160,60],
				content:'<img src="images/ajax_load.gif" alt="loading" style="margin:10px;" />',
				onShow: function(){
					
					var param = this.getParent().find('a').attr('id');
					
					if($.browser.msie && $.browser.version=='6.0')
					{
						param = this.getParent().find('a').attr('style').match(/id=\"([^\"]+)\"/);
						param = param[1];
					}
					
					this.load('m-barra_navigazione_tips.php',{id:param}); 
				} 
			});
			
		});




function hideItems()
{
	var list = document.getElementById("subNav").getElementsByTagName("ul");
	for(i=0;i<list.length;i++) {
		list[i].style.display="none";
	}
}

function navMenu()
{
	if (!document.getElementsByTagName){ return; }
	var anchors = document.getElementsByTagName('a');
	
	for (var i=0; i<anchors.length; i++){
		var anchor = anchors[i];
			
		var relAttribute = String(anchor.getAttribute('rel'));

		if (anchor.getAttribute('href') && (relAttribute.toLowerCase().match('menutrigger'))){
			anchor.onclick = function() { 
				var nameAttribute = this.getAttribute('name') + "Nav";
				var thismenu = document.getElementById(nameAttribute);
				hideItems();
				thismenu.style.display="inline";
				return false;
			}
		}
	}
}

