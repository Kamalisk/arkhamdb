(function ui_deck(ui, $) {

var DisplaySort = 'type'

function confirm_delete() {
	$('#delete-deck-name').text(app.deck.get_name());
	$('#delete-deck-id').val(app.deck.get_id());
	$('#deleteModal').modal('show');
}

ui.do_action_deck = function do_action_deck(event) {

	var action_id = $(this).attr('id');
	if(!action_id) return;

	switch(action_id) {
		case 'btn-delete': confirm_delete(); break;
		case 'btn-upgrade': ui.upgrade(app.deck.get_id()); break;
		case 'btn-print': window.print(); break;
		case 'btn-sort-type': DisplaySort = 'type'; ui.refresh_deck()(); break;
		case 'btn-sort-position': DisplaySort = 'position'; ui.refresh_deck(); break;
		case 'btn-sort-faction': DisplaySort = 'faction'; ui.refresh_deck(); break;
		case 'btn-sort-name': DisplaySort = 'name'; ui.refresh_deck(); break;
		case 'btn-display-plain': export_plaintext(); break;
		case 'btn-display-bbcode': export_bbcode(); break;
		case 'btn-display-markdown': export_markdown(); break;
	}

}

ui.upgrade = function upgrade(deck_id) {
	//console.log(deck_id);
	$('#upgrade_deck').val(deck_id);
	var list = this.create_exile_list();
	if (list){
		$('#upgrade-exile-list').empty();
		$('#upgrade-exile-list').append(list);
	}
	$('#upgrade_xp').val(0);
	$('#upgradeModal').modal('show');
	setTimeout(function() { $('#upgrade_xp').focus().select(); }, 500);
}

ui.create_exile_list = function create_exile_list(){
	var exile_cards = app.data.cards.find({
		exile: {
			'$eq': true
		},
		indeck: {
			'$gt': 0
		}
	});
	if (exile_cards.length){
		var list = $('<ul>');
		exile_cards.forEach(function (card) {
			list.append('<li><label><input type="checkbox" name="exiles[]" value="'+card.code+'"> '+card.name+'</label></li>');
			if (card.indeck > 1){
				list.append('<li><label><input type="checkbox" name="exiles[]" value="'+card.code+'"> '+card.name+'</label></li>');
			}
		});
		return list;
	} else {
		return false;
	}
}


ui.upgrade_process = function upgrade_process(event) {
	return true;
	event.preventDefault();
	var data = {};
	data.xp = 10;
	data.deck_id = $('#upgrade_deck').val();
	$.ajax(Routing.generate("deck_upgrade", { deck_id: $('#upgrade_deck').val() }), {
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			var response = jqXHR.responseJSON;
			if(!response.success) {
				alert('An error occured while upgrading the deck.');
				return;
			}
			// redirect here
		},
		error: function(jqXHR, textStatus, errorThrown) {
			//console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			alert('An error occured while upgrading the deck.');
		}
	});

}

/**
 * sets up event handlers ; dataloaded not fired yet
 * @memberOf ui
 */
ui.setup_event_handlers = function setup_event_handlers() {
	$('#btn-group-deck').on({
		click: ui.do_action_deck
	}, 'button[id],a[id]');

}

/**
 * @memberOf ui
 */
ui.refresh_deck = function refresh_deck() {
	app.deck.display('#deck');
	app.draw_simulator && app.draw_simulator.reset();
	app.deck_charts && app.deck_charts.setup();
}

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {
	ui.setup_event_handlers();
	app.draw_simulator && app.draw_simulator.on_dom_loaded();
};

/**
 * called when the app data is loaded
 * @memberOf ui
 */
ui.on_data_loaded = function on_data_loaded() {

};

/**
 * called when both the DOM and the data app have finished loading
 * @memberOf ui
 */
ui.on_all_loaded = function on_all_loaded() {
	app.markdown && app.markdown.update(app.deck.get_description_md() || '*No description.*', '#description');
	if ($('#deck').length > 0){
		ui.refresh_deck();
		app.deck_history.setup();
	}

};

})(app.ui, jQuery);
