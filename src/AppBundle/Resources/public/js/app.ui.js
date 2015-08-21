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
	} else {
		$('[data-toggle="tooltip"]').tooltip();
	}
	$('time').each(function (index, element) {
		var datetime = moment($(element).attr('datetime'));
		$(element).html(datetime.fromNow());
		$(element).attr('title', datetime.format('LLLL'));
	});
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
	$('abbr').each(function (index, element) {
		var title;
		switch($(element).text().toLowerCase()) {
		case 'renown': 
			title = "After you win a challenge in which this character is participating, he may gain 1 power.";
			break;
		case 'intimidate': 
			title = "After you win a challenge in which this character is participating, you may choose and kneel a character, controlled by the losing opponent, with equal or lower STR than the amount of STR by which the challenge was won.";
			break;
		case 'stealth': 
			title = "When you declare this character as an attacker, you may choose a character without stealth controlled by the defending opponent. That character cannot be declared as a defender for this challenge.";
			break;
		case 'insight': 
			title = "After you win a challenge in which this character is participating, you may draw 1 card.";
			break;
		case 'limited': 
			title = "No more than 1 card in total with the limited keyword can be marshaled (or played, if the card is an event) by each player each round. No more than 1 limited card can be placed by each player during setup.";
			break;
		case 'pillage': 
			title = "After you win a challenge in which this character is participating, you may discard 1 card from the top of the losing opponent's deck.";
			break;
		case 'terminal': 
			title = "If attached card leaves play, discard this attachment.";
			break;
		case 'ambush': 
			title = "You may pay X gold to put this card into play from your hand during the challenges phase.";
			break;
		}
		if(title) $(element).attr('title', title).tooltip();
	})
});
$.when(dom_loaded, data_loaded).done(function () {
	setTimeout(function () {
		$(document).trigger('start.app');
	}, 0);
});

})(app.ui = {}, jQuery);
