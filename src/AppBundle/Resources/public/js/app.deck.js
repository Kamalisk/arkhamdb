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
	card_line_tpl = _.template('<div><%= card.indeck %>x <a href="<%= card.url %>" class="card card-tip" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a></div>');

/**
 * @memberOf deck
 */
deck.init = function init(json) {
	if(json) deck.json = json;
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
 * @returns string
 */
deck.get_id = function get_id() {
	return id;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_faction_code = function get_faction_code() {
	return faction_code;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_description_md = function get_description_md() {
	return description_md;
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
deck.get_history = function get_history() {
	return history;
}

/**
 * @memberOf deck
 */
deck.get_cards = function get_cards(sort) {
	sort = sort || {};
	sort['code'] = 1;

	return app.data.cards.find({
		indeck: {
			'$gt': 0
		}
	}, {
		'$orderBy': sort
	});
}


/**
 * @memberOf deck
 */
deck.get_draw_deck = function get_draw_deck(sort) {
	sort = sort || {};
	sort['code'] = 1;

	return app.data.cards.find({
		indeck: {
			'$gt': 0
		},
		type_code: {
			'$nin' : ['agenda','plot']
		}
	}, {
		'$orderBy': sort
	});
}

/**
 * @memberOf deck
 */
deck.get_draw_deck_size = function get_draw_deck_size(sort) {
	var draw_deck = deck.get_draw_deck();
	var quantities = _.pluck(draw_deck, 'indeck');
	return _.reduce(quantities, function(memo, num) { return memo + num; }, 0);
}

/**
 * @memberOf deck
 */
deck.get_plot_deck = function get_plot_deck(sort) {
	sort = sort || {};
	sort['code'] = 1;

	return app.data.cards.find({
		indeck: {
			'$gt': 0
		},
		type_code: {
			'$in' : ['plot']
		}
	}, {
		'$orderBy': sort
	});
}

/**
 * @memberOf deck
 */
deck.get_plot_deck_size = function get_plot_deck_size(sort) {
	var plot_deck = deck.get_plot_deck();
	var quantities = _.pluck(plot_deck, 'indeck');
	return _.reduce(quantities, function(memo, num) { return memo + num; }, 0);
}

/**
 * @memberOf deck
 */
deck.get_included_packs = function get_included_packs() {
	var cards = deck.get_cards();
	var pack_codes = _.uniq(_.pluck(cards, 'pack_code'));
	var packs = app.data.packs.find({
		'code': {
			'$in': pack_codes
		}
	}, {
		'$orderBy': {
			'available': 1
		}
	});
	return packs;
}

/**
 * @memberOf deck
 */
deck.display = function display(container, sort, nb_columns) {
	var deck_content = $('<div class="deck-content">');

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
			$(card_line_tpl({card:card})).addClass(deck.can_include_card(card) ? '' : 'invalid-card').appendTo(deck_content);
		})
	})

	var deck_intro = $('<div class="deck-intro"><div class="media"><div class="media-left"></div><div class="media-body"></div></div>');
	$(deck_intro).find('.media-left').append('<span class="icon-'+deck.get_faction_code()+' '+deck.get_faction_code()+'"></span>');
	$(deck_intro).find('.media-body').append('<h4>'+faction_name+'</h4>');
	$(deck_intro).find('.media-body').append('<div>Draw deck: '+deck.get_draw_deck_size()+' cards.</div>');
	$(deck_intro).find('.media-body').append('<div>Plot deck: '+deck.get_plot_deck_size()+' cards.</div>');
	$(deck_intro).find('.media-body').append('<div>Included packs: ' + _.pluck(deck.get_included_packs(), 'name').join(', ') + '.</div>');


	$(container)
		.removeClass('deck-loading')
		.empty()
		.append(deck_intro)
		.append(deck_content);

}

/**
 * @memberOf deck
 */
deck.set_card_copies = function set_card_copies(card_code, nb_copies) {
	app.data.cards.updateById(card_code, {
		indeck: nb_copies
	});
	if(app.deck_history) app.deck_history.notify_change();
}

/**
 * @memberOf deck
 */
deck.get_content = function get_content() {
	var cards = deck.get_cards();
	var content = {};
	cards.forEach(function (card) {
		content[card.code] = card.indeck;
	});
	return content;
}

/**
 * @memberOf deck
 */
deck.get_json = function get_json() {
	return JSON.stringify(deck.get_content());
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
 * @returns
 */
deck.get_minor_faction_code = function get_minor_faction_code() {
	var agenda = deck.get_agenda();
	if(!agenda) return;

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
