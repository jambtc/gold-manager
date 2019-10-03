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
