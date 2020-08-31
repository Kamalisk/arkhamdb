(function app_card_modal(card_modal, $) {

var modal = null;

/**
 * @memberOf card_modal
 */
card_modal.display_modal = function display_modal(event, element) {
	event.preventDefault();
	$(element).qtip('destroy', true);
	fill_modal($(element).data('code'));
};

/**
 * @memberOf card_modal
 */
card_modal.typeahead = function typeahead(event, card) {
	fill_modal(card.code);
	$('#cardModal').modal('show');
};

function fill_modal (code) {
	var card = app.data.cards.findById(code),
		modal = $('#cardModal');

	if(!card) return;

	modal.data('code', code);
	modal.find('.card-modal-link').attr('href', card.url);
	modal.find('h3.modal-title').html(app.format.name(card));
	modal.find('.modal-image').html('<img class="img-responsive" src="'+card.imagesrc+'">');
	modal.find('.modal-info').html(
			'<div class="card-faction">' + app.format.faction(card) + '</div>'
			+ '<div class="card-info">' + app.format.info(card) + '</div>'
			+ '<div class="card-traits">' + app.format.traits(card) + '</div>'
			+ '<div class="card-text border-'+card.faction_code+'">' + app.format.text(card) + '</div>'
			+ (card.taboo_text ? '<div class="card-text-taboo border-'+card.faction_code+'">' + app.format.text(card, "taboo_text") + '</div>' : '')
			+ (card.taboo_xp ? '<div class="card-text-taboo border-'+card.faction_code+'">This card costs ' + card.taboo_xp + ' additional experience</div>' : '')
			+ '<div class="card-pack">' + app.format.pack(card) + '</div>'
	);
	
	var qtyelt = modal.find('.modal-qty');
	if(qtyelt && card.maxqty) {
		var qty = '';
	  	for(var i=0; i<=card.maxqty; i++) {
	  		qty += '<label class="btn btn-sm btn-default"><input type="radio" name="qty" value="'+i+'">'+i+'</label>';
	  	}
	  	qtyelt.html(qty);

	  	qtyelt.find('label').each(function (index, element) {
			if(index == card.indeck) $(element).addClass('active');
			else $(element).removeClass('active');
		});

	} else {
		if(qtyelt) qtyelt.closest('.row').remove();
	}
	var qtyelt = modal.find('.modal-ignore');
	
	if(qtyelt && card.maxqty && (card.code == "05040" || card.real_traits.indexOf('Fortune.') !== -1 || card.real_traits.indexOf('Gambit.') !== -1 ) ) {
		qtyelt.closest('.modal-deck-ignore').show();
		var qty = '';
		for(var i=0; i<=card.maxqty; i++) {
			if (card.ignore == i) {
				qty += '<label class="btn btn-sm btn-default active"><input type="radio" name="ignoreqty" value="'+i+'">'+i+'</label>';
			} else {
				qty += '<label class="btn btn-sm btn-default"><input type="radio" name="ignoreqty" value="'+i+'">'+i+'</label>';
			}
		}
		qtyelt.html(qty);
	} else {
		if(qtyelt) qtyelt.closest('.modal-deck-ignore').hide();
	}
}

$(function () {

	$('body').on({click: function (event) {
		var element = $(this);
		if(event.shiftKey || event.altKey || event.ctrlKey || event.metaKey) {
			event.stopPropagation();
			return;
		}
		card_modal.display_modal(event, element);
	}}, '.card');

})

})(app.card_modal = {}, jQuery);
