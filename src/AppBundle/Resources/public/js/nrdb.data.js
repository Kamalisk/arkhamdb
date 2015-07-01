if (typeof NRDB != "object")
	var NRDB = { 
		data_loaded: jQuery.Callbacks(), 
		api_url: {
			sets: 'http://netrunnerdb.com/api/sets/',
			cards: 'http://netrunnerdb.com/api/cards/'
		},
		locale: 'en'
	};
NRDB.data = {};
(function(data, $) {
	data.sets = {};
	data.cards = {};

	var sets_data = null;
	var cards_data = null;
	var is_modified = null;

	data.query = function() {
		data.initialize();
		data.promise_sets = $
				.ajax(NRDB.api_url.sets+"?jsonp=NRDB.data.parse_sets&_locale="
						+ NRDB.locale);
		data.promise_cards = $
				.ajax(NRDB.api_url.cards+"?jsonp=NRDB.data.parse_cards&_locale="
						+ NRDB.locale);
		$.when(data.promise_sets, data.promise_cards).done(data.initialize);
	};

	data.initialize = function() {
		if (is_modified === false)
			return;

		sets_data = sets_data
				|| JSON.parse(localStorage
						.getItem('sets_data_' + NRDB.locale));
		if(!sets_data) return;
		data.sets = TAFFY(sets_data);
		data.sets.sort("cyclenumber,number");

		cards_data = cards_data
				|| JSON
						.parse(localStorage
								.getItem('cards_data_' + NRDB.locale));
		if(!cards_data) return;
		data.cards = TAFFY(cards_data);
		data.cards.sort("code");
		
		NRDB.data_loaded.fire();
	};

	data.parse_sets = function(response) {
		if(typeof response === "undefined") return;
		var json = JSON.stringify(sets_data = response);
		is_modified = is_modified
				|| json != localStorage.getItem("sets_data_" + NRDB.locale);
		localStorage.setItem("sets_data_" + NRDB.locale, json);
	};

	data.parse_cards = function(response) {
		if(typeof response === "undefined") return;
		var json = JSON.stringify(cards_data = response);
		is_modified = is_modified
				|| json != localStorage.getItem("cards_data_" + NRDB.locale);
		localStorage.setItem("cards_data_" + NRDB.locale, json);
	};

	data.get_card_by_code = function(code) {
		if(data.cards) {
			return data.get_cards_by_code(code).first();
		}
	};
	
	data.get_cards_by_code = function(code) {
		if(data.cards) {
			return data.cards({code:String(code)});
		}
	};
	
	$(function() {
		if(NRDB.api_url) data.query();
	});

})(NRDB.data, jQuery);


