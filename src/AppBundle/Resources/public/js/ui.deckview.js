(function ui_deck(ui, $) {

var DisplaySort = 'type'

function confirm_publish(event) {
	var button = $(this);
	if($(button).hasClass('processing')) return;
	$(button).addClass('processing');

	$('#publish-form-alert').remove();

	$.ajax(Routing.generate('deck_publish', {deck_id:app.deck.get_id()}), {
		dataType: 'json',
		success: function( response ) {
		  if(typeof response === 'object') {
			  $('#publish-deck-name').val(response.name);
			  $('#publish-deck-id').val(response.id);
			  $('#publish-deck-description').val(response.description_md);
			  $('#btn-publish-submit').text("Go").prop('disabled', false);
		  }
		  else
		  {
			  $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">That deck cannot be published because <a href="'+response+'">another decklist</a> already has the same composition.</div>');
			  $('#btn-publish-submit').text("Refused").prop('disabled', true);
		  }
	  },
	  error: function( jqXHR, textStatus, errorThrown ) {
		  console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
		  $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">'+jqXHR.responseText+'</div>');
		  $('#btn-publish-submit').text("Error").prop('disabled', true);
	  },
	  complete: function() {
		  $(button).removeClass('processing');
		  $('#publishModal').modal('show');
	  }
	});
}

function confirm_delete() {
	$('#delete-deck-name').text(app.deck.get_name());
	$('#delete-deck-id').val(app.deck.get_id());
	$('#deleteModal').modal('show');
}

ui.do_action_deck = function do_action_deck(event) {

	var action_id = $(this).attr('id');
	if(!action_id) return;

	switch(action_id) {
		case 'btn-publish': confirm_publish(); break;
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
	app.deck.display('#deck', DisplaySort);
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
	app.deck.init();
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
