(function ui_decks(ui, $) {

ui.decks = [];

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {
	console.log(ui.decks);
	$('#decks').on('click', 'a', function (event) {
		var deck_id = $(this).closest('tr').data('id');
//		ui.open_deck_modal(deck_id);
	});
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
	
};


})(app.ui, jQuery);
