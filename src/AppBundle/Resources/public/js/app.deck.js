(function app_deck(deck, $) {

deck.json = {};
	
var date_creation,
	date_update,
	description_md,
	history,
	id,
	name,
	slots,
	tags,
	faction_code,
	faction_name,
	unsaved,
	user_id,
	header_tpl = _.template('<h5><%= name %> (<%= quantity %>)</h5>'),
	card_line_tpl = _.template('<div><%= card.indeck %>x <a href="<%= card.url %>" class="card card-tooltip" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a> <i>(<%= card.pack_name %>)</i></div>');
	
/**
 * @memberOf deck
 */
deck.init = function init() {
	date_creation = deck.json.date_creation;
	date_update = deck.json.date_update;
	description_md = deck.json.description_md;
	history = deck.json.history;
	id = deck.json.id;
	name = deck.json.name;
	slots = deck.json.slots;
	tags = deck.json.tags;
	faction_code = deck.json.faction_code;
	faction_name = deck.json.faction_name;
	unsaved = deck.json.unsaved;
	user_id = deck.json.user_id;
	
	app.data.cards.update({}, {
		indeck: 0
	});
	for(code in slots) {
		if(slots.hasOwnProperty(code)) {
			app.data.cards.updateById(code, {indeck: slots[code]});
		}
	}
}

/**
 * @memberOf deck
 * @returns
 */
deck.get_faction_code = function get_faction_code() {
	return faction_code;
}

/**
 * @memberOf deck
 */
deck.get_agenda = function get_agenda() {
	var result = app.data.cards.find({
		indeck: {
			'$gt': 0
		},
		type_code: 'agenda'
	});
	return result.length ? result[0] : null;
}

/**
 * @memberOf deck
 */
deck.display = function display(container, sort, nb_columns) {
	var deck_content = $('<div class="deck_content">');
	
	/* to sort cards, we need: 
	 * name of the key to sort upon
	 * label to display for each key
	 * order of the values
	 */
	var sortKey = '', displayLabel = '', valuesOrder = [];
	switch(sort) {
	case 'type':
		sortKey = 'type_code';
		displayLabel = 'type_name';
		valuesOrder = ['agenda','plot','character','attachment','location','event'];
		break;
	}
	
	valuesOrder.forEach(function (sortValue) {
		var query = {
			indeck: {
				'$gt': 0
			}
		};
		query[sortKey] = sortValue;
		var cards = app.data.cards.find(query, {
			'$orderBy': { name: 1 }
		});
		if(!cards.length) return;

		$(header_tpl({name:cards[0][displayLabel], quantity: cards.length})).appendTo(deck_content);
		cards.forEach(function (card) {
			$(card_line_tpl({card:card})).appendTo(deck_content);
		})
	})
	
	$(container)
		.removeClass('deck-loading')
		.empty()
		.append(deck_content);
	
}

/**
 * @memberOf deck
 */
deck.set_card_copies = function set_card_copies(card_code, nb_copies) {
	app.data.cards.updateById(card_code, {
		indeck: nb_copies
	});
}

/**
 * @memberOf deck
 */
deck.get_json = function get_json() {
	var cards = app.data.cards.find({
		indeck: {
			'$gt': 0
		}
	}, {
		'$orderBy': { code: 1 }
	});
	var content = {};
	cards.forEach(function (card) {
		content[card.code] = card.indeck;
	});
	return JSON.stringify(content);
}

/**
 * @memberOf deck
 */
deck.get_export = function get_export(format) {
	
}

/**
 * @memberOf deck
 */
deck.get_problem = function get_problem() {
	
}

/**
 * @memberOf deck
 */
deck.autosave = function autosave() {
	
}

/**
 * @memberOf deck
 */
deck.load_snapshot = function load_snapshot() {
	
}

/**
 * @memberOf deck
 * @returns
 */
deck.get_minor_faction_code = function get_minor_faction_code() {
	var agenda = deck.get_agenda();
	// special case for the Core Set Banners
	var banners_core_set = {
		'01198': 'baratheon',
		'01199': 'greyjoy',
		'01200': 'lannister',
		'01201': 'martell',
		'01202': 'nightswatch',
		'01203': 'stark',
		'01204': 'targaryen',
		'01205': 'tyrell'
	};
	return banners_core_set[agenda.code];
}

/**
 * returns true if the deck can include the card as parameter
 * @memberOf deck
 */
deck.can_include_card = function can_include_card(card) {
	// neutral card => yes
	if(card.faction_code === 'neutral') return true;
	
	// in-house card => yes
	if(card.faction_code === faction_code) return true;
	
	// out-of-house and loyal => no
	if(card.isLoyal) return false;
	
	// minor faction => yes
	var minor_faction_code = deck.get_minor_faction_code();
	if(minor_faction_code && minor_faction_code === card.faction_code) return true;
	
	// if none above => no
	return false;
}
	
})(app.deck = {}, jQuery);
