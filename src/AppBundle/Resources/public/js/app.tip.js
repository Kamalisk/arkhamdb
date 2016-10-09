(function app_tip(tip, $) {

var cards_zoom_regexp = /card\/(\d\d\d\d\d)$/,
	mode = 'text',
	hide_event = 'mouseout';

function display_card_on_element(card, element, event) {
	var content;
	if(mode == 'text') {
		var image = card.imagesrc ? '<div class="card-thumbnail card-thumbnail-3x card-thumbnail-'+card.type_code+'" style="background-image:url('+card.imagesrc+')"></div>' : "";		
		content = image
		+ '<h4 class="card-name">' + app.format.name(card) + '</h4>'
		+ '<div class="card-faction">' + app.format.faction(card) + '</div>'
		+ '<div><span class="card-type">'+card.type_name+((card.type_code == "agenda" || card.type_code == "act") ? '. Stage '+card.stage : '')+(card.slot ? '. '+card.slot : "")+(card.subtype_name ? '. '+card.subtype_name : "")+'</span></div>'
		+ '<div class="card-traits">' + app.format.traits(card) + '</div>'
		+ '<div class="card-info">' + app.format.info(card) + '</div>'
		;
		
		if (card.type_code == "agenda" || card.type_code == "act"){
			content += '<div class="card-flavor">' + card.flavor + '</div><div class="card-text border-'+card.faction_code+'">' + app.format.text(card) + '</div>' 			
		} else if (card.type_code == "location"){
			content += '<div class="card-flavor">' + card.back_flavor + '</div>';
			if (card.back_text){
				content += '<div class="card-text">' + card.back_text + '</div>';
			}
		}else {
			content += '<div class="card-text border-'+card.faction_code+'">' + app.format.text(card) + '</div>'
			if (card.double_sided){
				content += '<hr />';
				if (card.back_flavor){
					//content += '<div class="card-flavor">' + card.back_flavor + '</div>';
				}
				if (card.back_text){
					content += '<div class="card-text border-'+card.faction_code+'">' + app.format.back_text(card) + '</div>';
				}
				content += '</div>';
			}
		}

		
		content += '<div class="card-pack">' + app.format.pack(card) + '</div>';
	
	}
	else {
		content = card.imagesrc ? '<img src="'+card.imagesrc+'">' : "";
	}

	var qtip = {
		content : {
			text : content
		},
		style : {
			classes : 'card-content qtip-bootstrap qtip-thronesdb qtip-thronesdb-' + mode
		},
		position : {
			my : mode == 'text' ? 'center left' : 'top left',
			at : mode == 'text' ? 'center right' : 'bottom right',
			viewport : $(window)
		},
		show : {
			event : event.type,
			ready : true,
			solo : true
		},
		hide: {
			event: hide_event
		}
	};
	
	$(element).qtip(qtip, event);
}

/**
 * @memberOf tip
 * @param event
 */
tip.display = function display(event) {	
	var code = $(this).data('code');
	var card = app.data.cards.findById(code);

	if (!card) return;
	display_card_on_element(card, this, event);
};

/**
 * @memberOf tip
 * @param event
 */
tip.guess = function guess(event) {
	if($(this).hasClass('no-popup')) return;
	var href = $(this).get(0).href;
	if(href && href.match(cards_zoom_regexp)) {
		var code = RegExp.$1;
		var generated_url = Routing.generate('cards_zoom', {card_code:code}, true);
		var card = app.data.cards.findById(code);
		if(card && href === generated_url) {
			display_card_on_element(card, this, event);
		}
	}
}

tip.set_mode = function set_mode(opt_mode) {
	if(opt_mode == 'text' || opt_mode == 'image') {
		mode = opt_mode;
	}
}

tip.set_hide_event = function set_hide_event(opt_hide_event) {
	if(opt_hide_event == 'mouseout' || opt_hide_event == 'unfocus') {
		hide_event = opt_hide_event;
	}
}

$(document).on('start.app', function () {
	$('body').on({
		mouseover : tip.display
	}, 'a.card-tip');

	$('body').on({
		mouseover : tip.guess
	}, 'a:not(.card-tip)');
});

})(app.tip = {}, jQuery);
