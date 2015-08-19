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
	problem_labels = {
		too_many_plots: "Contains too many Plots",
		too_few_plots: "Contains too few Plots",
		too_many_different_plots: "Contains more than one duplicated Plot",
		too_many_agendas: "Contains more than one Agenda",
		too_few_cards: "Contains too few cards",
		invalid_cards: "Contains forbidden cards (cards no permitted by Faction or Agenda)",
		agenda: "Doesn't comply with the Agenda conditions"
	},
	header_tpl = _.template('<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>'),
	card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> fg-<%= card.faction_code %>"></span> <a href="<%= card.url %>" class="card card-tip" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>');

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
deck.get_name = function get_name() {
	return name;
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
deck.get_agendas = function get_agendas() {
	return deck.get_cards(null, {
		type_code: 'agenda'
	});
}

/**
 * @memberOf deck
 */
deck.get_agenda = function get_agenda() {
	var agendas = deck.get_agendas();
	return agendas.length ? agendas[0] : null;
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
deck.get_cards = function get_cards(sort, query) {
	sort = sort || {};
	sort['code'] = 1;

	query = query || {};
	query.indeck = {
		'$gt': 0
	};

	return app.data.cards.find(query, {
		'$orderBy': sort
	});
}

/**
 * @memberOf deck
 */
deck.get_draw_deck = function get_draw_deck(sort) {
	return deck.get_cards(sort, {
		type_code: {
			'$nin' : ['agenda','plot']
		}
	});
}

/**
 * @memberOf deck
 */
deck.get_draw_deck_size = function get_draw_deck_size(sort) {
	var draw_deck = deck.get_draw_deck();
	return deck.get_nb_cards(draw_deck);
}

/**
 * @memberOf deck
 */
deck.get_plot_deck = function get_plot_deck(sort) {
	return deck.get_cards(sort, {
		type_code: 'plot'
	});
}

/**
 * @memberOf deck
 * @returns the number of plot cards
 */
deck.get_plot_deck_size = function get_plot_deck_size(sort) {
	var plot_deck = deck.get_plot_deck();
	return deck.get_nb_cards(plot_deck);
}

/**
 * @memberOf deck
 * @returns the number of different plot cards
 */
deck.get_plot_deck_variety = function get_plot_deck_variety(sort) {
	var plot_deck = deck.get_plot_deck();
	return plot_deck.length;
}

deck.get_nb_cards = function get_nb_cards(cards) {
	if(!cards) cards = deck.get_cards();
	var quantities = _.pluck(cards, 'indeck');
	return _.reduce(quantities, function(memo, num) { return memo + num; }, 0);
}

/**
 * @memberOf deck
 */
deck.get_included_packs = function get_included_packs() {
	var cards = deck.get_cards();
	var nb_packs = {};
	cards.forEach(function (card) {
		nb_packs[card.pack_code] = Math.max(nb_packs[card.pack_code] || 0, card.indeck / card.quantity);
	});
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
	packs.forEach(function (pack) {
		pack.quantity = nb_packs[pack.code] || 0;
	})
	return packs;
}

/**
 * @memberOf deck
 */
deck.display = function display(container, sort) {
	var elements;

	if(sort === 'type') {
		elements = deck.display_by_type();
	}
	else {
		elements = deck.display_by_other();
	}

	$(container)
		.removeClass('deck-loading')
		.empty();

	elements.forEach(function (element) {
		$(container).append(element);
	})
}

deck.display_by_other = function display_by_other() {
	/* to sort cards, we need:
	 * name of the key to sort upon
	 * label to display for each key
	 * order of the values
	 */
	var sortKey = '', displayLabel = '', valuesOrder = [];
	switch(sort) {
	case 'faction':
		sortKey = 'faction_code';
		displayLabel = 'faction_name';
		valuesOrder = app.data.cards.distinct('faction_code').sort();
		break;
	}

	var deck_content = $('<div class="deck-content">');

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
			$('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card').append($(card_line_tpl({card:card}))).prepend(card.indeck+'x ').appendTo(deck_content);
		})
	})

	// TODO
}

deck.display_by_type = function display_by_type() {
	var agenda = deck.get_agenda();
	var problem = deck.get_problem();

	var deck_content = $('<div class="deck-content">');
	var deck_content_first_row = $('<div class="row">').appendTo(deck_content);

	var deck_intro_images = $('<div class="col-xs-2">').appendTo(deck_content_first_row);
	deck_intro_images.append('<div style="margin-bottom:10px"><img src="/bundles/app/images/factions/'+deck.get_faction_code()+'.png" class="img-responsive">');
	if(agenda) {
		deck_intro_images.append('<div><img src="'+agenda.imagesrc+'" class="img-responsive">');
	}

	var deck_intro_meta = $('<div class="col-sm-4">').appendTo(deck_content_first_row);
	deck_intro_meta.append('<h4 style="font-weight:bold">'+faction_name+'</h4>');
	if(agenda) {
		$('<h5>').append($(card_line_tpl({card:agenda}))).appendTo(deck_intro_meta).find('.icon').remove();
	}
	$('<div>Draw deck: '+deck.get_draw_deck_size()+' cards</div>').addClass(deck.get_draw_deck_size() < 60 ? 'text-danger': '').appendTo(deck_intro_meta);
	$('<div>Plot deck: '+deck.get_plot_deck_size()+' cards</div>').addClass(deck.get_plot_deck_size() != 7 ? 'text-danger': '').appendTo(deck_intro_meta);
	deck_intro_meta.append('<div>Packs: ' + _.map(deck.get_included_packs(), function (pack) { return pack.name+(pack.quantity > 1 ? ' ('+pack.quantity+')' : ''); }).join(', ') + '</div>');
	if(problem) {

		$('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>').appendTo(deck_intro_meta);
	}

	var deck_intro_plots = $('<div class="col-sm-6">').appendTo(deck_content_first_row);
	deck_intro_plots.append(deck.display_one_section('type_code', 'plot', 'type_name'));

	var deck_content_second_row = $('<div class="row">').appendTo(deck_content);

	var deck_draw_deck_left = $('<div class="col-sm-6">').appendTo(deck_content_second_row);
	deck_draw_deck_left.append(deck.display_one_section('type_code', 'character', 'type_name'));

	var deck_draw_deck_right = $('<div class="col-sm-6">').appendTo(deck_content_second_row);
	deck_draw_deck_right.append(deck.display_one_section('type_code', 'attachment', 'type_name'));
	deck_draw_deck_right.append(deck.display_one_section('type_code', 'location', 'type_name'));
	deck_draw_deck_right.append(deck.display_one_section('type_code', 'event', 'type_name'));


	return [ deck_content ];
}

deck.display_one_section = function display_one_section(sortKey, sortValue, displayLabel) {
	var section = $('<div>');
	var query = {};
	query[sortKey] = sortValue;
	var cards = deck.get_cards({ name: 1 }, query);
	if(cards.length) {
		$(header_tpl({code: sortValue, name:cards[0][displayLabel], quantity: deck.get_nb_cards(cards)})).appendTo(section);
		cards.forEach(function (card) {
			$('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card').append($(card_line_tpl({card:card}))).prepend(card.indeck+'x ').appendTo(section);
		})
	}
	return section;
}

/**
 * @memberOf deck
 * @return boolean true if at least one other card quantity was updated
 */
deck.set_card_copies = function set_card_copies(card_code, nb_copies) {
	var card = app.data.cards.findById(card_code);
	if(!card) return false;

	var updated_other_card = false;

	// card-specific rules
	switch(card.type_code) {
		case 'agenda':
		app.data.cards.update({
			type_code: 'agenda'
		}, {
			indeck: 0
		});
		updated_other_card = true;
		break;
	}

	app.data.cards.updateById(card_code, {
		indeck: nb_copies
	});
	if(app.deck_history) app.deck_history.notify_change();

	return updated_other_card;
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
	// exactly 7 plots
	if(deck.get_plot_deck_size() > 7) {
		return 'too_many_plots';
	}
	if(deck.get_plot_deck_size() < 7) {
		return 'too_few_plots';
	}

	// at least 6 different plots
	if(deck.get_plot_deck_variety() < 6) {
		return 'too_many_different_plots';
	}

	// no more than 1 agenda
	if(deck.get_nb_cards(deck.get_agendas()) > 1) {
		return 'too_many_agendas';
	}

	// at least 60 others cards
	if(deck.get_draw_deck_size() < 60) {
		return 'too_few_cards';
	}

	// no invalid card
	if(deck.get_invalid_cards().length > 0) {
		return 'invalid_cards';
	}

	// the condition(s) of the agenda must be fulfilled
	var agenda = deck.get_agenda();
	if(!agenda) return;
	switch(agenda.code) {
		case '01027':
		if(deck.get_nb_cards(deck.get_cards(null, { type_code: { $in: [ 'character', 'attachment', 'location', 'event' ] }, faction_code: 'neutral' })) > 15) {
			return 'agenda';
		}
		break;
		case '01198':
		case '01199':
		case '01200':
		case '01201':
		case '01202':
		case '01203':
		case '01204':
		case '01205':
		var minor_faction_code = deck.get_minor_faction_code();
		if(deck.get_nb_cards(deck.get_cards(null, { type_code: { $in: [ 'character', 'attachment', 'location', 'event' ] }, faction_code: minor_faction_code })) < 12) {
			return 'agenda';
		}
		break;
	}
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

deck.get_invalid_cards = function get_invalid_cards() {
	return _.filter(deck.get_cards(), function (card) {
		return ! deck.can_include_card(card);
	});
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
	if(card.is_loyal) return false;

	// minor faction => yes
	var minor_faction_code = deck.get_minor_faction_code();
	if(minor_faction_code && minor_faction_code === card.faction_code) return true;

	// if none above => no
	return false;
}

})(app.deck = {}, jQuery);
