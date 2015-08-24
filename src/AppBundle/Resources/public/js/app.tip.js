(function app_tip(tip, $) {

var cards_zoom_regexp = /card\/(\d\d\d\d\d)$/,
	mode = 'text';

function display_card_on_element(card, element, event) {
	var content;
	if(mode == 'text') {
		var image = card.imagesrc ? '<div class="card-thumbnail card-thumbnail-'+(card.type_code === 'plot' ? 4 : 3)+'x card-thumbnail-'+card.type_code+'" style="background-image:url('+card.imagesrc+')"></div>' : "";

		content = image
		+ '<h4 class="card-name">' + app.format.name(card) + '</h4>'
		+ '<div class="card-info">' + app.format.info(card) + '</div>'
		+ '<div class="card-traits">' + app.format.traits(card) + '</div>'
		+ '<div class="card-text">' + app.format.text(card) + '</div>'
		+ '<span class="card-pack pull-right" style="clear:right">' + app.format.pack(card) + '</span>'
		+ '<span class="card-faction">' + app.format.faction(card) + '</span>';
	}
	else {
		content = card.imagesrc ? '<img src="'+card.imagesrc+'">' : "";
	}

	$(element).qtip(
			{
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
				}/*,
				hide : {
					event: 'unfocus'
				}*/
			}, event);
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
	var href = $(this).attr('href');
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

$(document).on('start.app', function () {

	if(!Modernizr.touch) {
		$('body').on({
			mouseover : tip.display
		}, 'a.card-tip');

		$('body').on({
			mouseover : tip.guess
		}, 'a:not(.card-tip)');
	}

});

})(app.tip = {}, jQuery);
