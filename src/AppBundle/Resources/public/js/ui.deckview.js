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
		case 'btn-print': window.print(); break;
		case 'btn-sort-type': DisplaySort = 'type'; ui.refresh_deck()(); break;
		case 'btn-sort-position': DisplaySort = 'position'; ui.refresh_deck()(); break;
		case 'btn-sort-faction': DisplaySort = 'faction'; ui.refresh_deck()(); break;
		case 'btn-sort-name': DisplaySort = 'name'; ui.refresh_deck()(); break;
		case 'btn-display-plain': export_plaintext(); break;
		case 'btn-display-bbcode': export_bbcode(); break;
		case 'btn-display-markdown': export_markdown(); break;
	}

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
	ui.refresh_deck();
	$('#btn-publish').prop('disabled', !!app.deck.get_problem());
};

})(app.ui, jQuery);
