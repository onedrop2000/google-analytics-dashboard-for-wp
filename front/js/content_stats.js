jQuery(document).ready(function(){
 
	jQuery('#gadwp-content').hide();
 
	jQuery('#gadwp-title').click(function(){
		jQuery('#gadwp-content').slideToggle();
		jQuery('#gadwp-title #gadwp-arrow').html( jQuery('#gadwp-title #gadwp-arrow').html() == '▲' ? '▼' : '▲');
    });
	
	jQuery("#gadwp-title a").click(function(event) {
		 event.preventDefault(); 
	});
});

jQuery(window).resize(function(){
	if(typeof ga_dash_drawpagevisits == "function" && typeof gadash_pagevisits!=='undefined' && !jQuery.isNumeric(gadash_pagevisits)){
		ga_dash_drawpagevisits(gadash_pagevisits);
	}
	if(typeof ga_dash_drawpagesearches == "function" && typeof gadash_pagesearches!=='undefined' && !jQuery.isNumeric(gadash_pagesearches)){
		ga_dash_drawpagesearches(gadash_pagesearches);
	}
	if(typeof ga_dash_drawfwidgetvisits == "function" && typeof gadash_widgetvisits!=='undefined' && !jQuery.isNumeric(gadash_widgetvisits)){
		ga_dash_drawfwidgetvisits(gadash_widgetvisits);
	}
});