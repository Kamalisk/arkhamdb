(function ui_decks(ui, $) {

ui.decks = [];

ui.confirm_publish = function confirm_publish(event) {

	var button = $(this);
	if($(button).hasClass('processing')) return;
	$(button).addClass('processing');

	var deck = ui.decks[$(this).closest('tr').data('id')];
	console.log(deck);
	
	$.ajax(Routing.generate('deck_publish', {deck_id:deck.id}), {
	  success: function( response ) {
		  if(response == "") {
			  $('#btn-publish-submit').text("Go").prop('disabled', false);
		  }
		  else 
		  {
			  $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">That deck cannot be published because <a href="'+response+'">another decklist</a> already has the same composition.</div>');
			  $('#btn-publish-submit').text("Refused");
		  }
	  },
	  error: function( jqXHR, textStatus, errorThrown ) {
		  console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
		  $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">'+jqXHR.responseText+'</div>');
		  $('#btn-publish-submit').text("Error")
	  },
	  complete: function() {
		  $(button).removeClass('processing');
		  $('#publishModal').modal('show');
	  }
	});
	
	$('#publish-form-alert').remove();
	$('#publish-deck-name').val(deck.name);
	$('#publish-deck-id').val(deck.id);
	$('#publish-deck-description').val(deck.description);
	$('#btn-publish-submit').text("Checking...").prop('disabled', true);
	$('#publishModal').modal('show');

}

ui.confirm_delete = function confirm_delete(event) {
	var deck = ui.decks[$(this).closest('tr').data('id')];
	console.log(deck);
		
	$('#delete-deck-name').text(deck.name);
	$('#delete-deck-id').val(deck.id);
	$('#deleteModal').modal('show');
}

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {
	
	$('#decks').on('click', 'button.btn-publish-deck', ui.confirm_publish);
	$('#decks').on('click', 'button.btn-delete-deck', ui.confirm_delete);
	$('#decks').on('click', 'input[type=checkbox]', function (event) {
		var checked = $(this).closest('tbody').find('input[type=checkbox]:checked');
		var button = $('#btn-group-selection button');
		if(checked.size()) {
			button.removeClass('btn-default').addClass('btn-primary')
		} else {
			button.addClass('btn-default').removeClass('btn-primary')
		}
		
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
