app.data_loaded.add(function() {
	for (var i = 0; i < Decklist.cards.length; i++) {
		var slot = Decklist.cards[i];
		app.data.cards({
			code : slot.card_code
		}).update({
			indeck : parseInt(slot.qty, 10)
		});
	}
	update_deck();
});

function update_cardsearch_result() {
	$('#card_search_results').empty();
	var query = app.smart_filter.get_query();
	if ($.isEmptyObject(query))
		return;
	var tabindex = 2;
	app.data.cards.apply(window, query).order("name intl").each(
			function(record) {
				$('#card_search_results').append(
						'<tr><td><span class="icon icon-' + record.faction_code
								+ ' ' + record.faction_code
								+ '"></td><td><a tabindex="'
								+ (tabindex++)
								+ '" href="'
								+ Routing.generate('cards_zoom', {card_code:record.code})
								+ '" class="card" data-code="' + record.code
								+ '">' + record.name
								+ '</a></td><td class="small">'
								+ record.setname + '</td></tr>');
			});
}

function handle_input_change(event) {
	app.smart_filter.handler($(this).val(), update_cardsearch_result);
}

$(function() {
	$('#version-popover').popover({
		html : true
	});

	$('#card_search_form').on({
		keyup : debounce(handle_input_change, 250)
	});

});
