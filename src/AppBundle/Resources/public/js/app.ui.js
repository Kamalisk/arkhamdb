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

$(document).ready(function () {
	console.log('ui.on_dom_loaded');
	
	if(Modernizr.touch) {
		//$('#svg').remove();
		//$('form.external').removeAttr('target');
	} else {
		$('[data-toggle="tooltip"]').tooltip();
	}

	if(typeof ui.on_dom_loaded === 'function') ui.on_dom_loaded();
	dom_loaded.resolve();
});
$(document).on('data.app', function () {
	console.log('ui.on_data_loaded');
	if(typeof ui.on_data_loaded === 'function') ui.on_data_loaded();
	data_loaded.resolve();
});
$(document).on('start.app', function () {
	console.log('ui.on_all_loaded');
	if(typeof ui.on_all_loaded === 'function') ui.on_all_loaded();
});
$.when(dom_loaded, data_loaded).done(function () {
	setTimeout(function () {
		$(document).trigger('start.app');
	}, 0);
});

})(app.ui = {}, jQuery);
