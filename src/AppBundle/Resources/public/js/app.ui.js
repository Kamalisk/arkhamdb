(function ui_deck(ui, $) {

var dom_loaded = new $.Deferred(),
	data_loaded = new $.Deferred();

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {};

/**
 * called when the app data is loaded
 * @memberOf ui
 */
ui.on_data_loaded = function on_data_loaded() {};

/**
 * called when both the DOM and the app data have finished loading
 * @memberOf ui
 */
ui.on_all_loaded = function on_all_loaded() {};

ui.insert_alert_message = function ui_insert_alert_message(type, message) {
	var alert = $('<div class="alert" role="alert"></div>').addClass('alert-'+type).append(message);
	$('#wrapper>div.container').first().prepend(alert);
}

$(document).ready(function () {
	$('[data-toggle="tooltip"]').tooltip();
	$('time').each(function (index, element) {
		var datetime = moment($(element).attr('datetime'));
		$(element).html(datetime.fromNow());
		$(element).attr('title', datetime.format('LLLL'));
	});
	if(typeof ui.on_dom_loaded === 'function') ui.on_dom_loaded();
	dom_loaded.resolve();
});
$(document).on('data.app', function () {
	if(typeof ui.on_data_loaded === 'function') ui.on_data_loaded();
	data_loaded.resolve();
});
$(document).on('start.app', function () {
	if(typeof ui.on_all_loaded === 'function') ui.on_all_loaded();
	
	// try to update packs to set owned
	app.user.loaded.always(function() {
	  app.data.packs.update({}, {
	      owned: true
	  });
	
	  
	});

	
	/*
	$('abbr').each(function (index, element) {
		var title;
		switch($(element).text().toLowerCase()) {
		case 'renown': 
			title = "After you win a challenge in which this character is participating, he may gain 1 power.";
			break;		
		}
		if(title) $(element).attr('title', title).tooltip();
	})
	*/
});
$.when(dom_loaded, data_loaded).done(function () {
	setTimeout(function () {
		$(document).trigger('start.app');
	}, 0);
});

})(app.ui = {}, jQuery);
