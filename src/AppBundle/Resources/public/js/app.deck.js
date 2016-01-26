(function app_deck(deck, $) {

var date_creation,
	date_update,
	description_md,
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
	card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> fg-<%= card.faction_code %>"></span> <a href="<%= card.url %>" class="card card-tip" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>'),
	layouts = {},
	layout_data = {};

/*
 * Templates for the different deck layouts, see deck.get_layout_data
 */
layouts[1] = _.template('<div class="deck-content"><%= meta %><%= plots %><%= characters %><%= attachments %><%= locations %><%= events %></div>');
layouts[2] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-6"><%= meta %></div><div class="col-sm-6"><%= plots %></div></div><div class="row"><div class="col-sm-6"><%= characters %></div><div class="col-sm-6"><%= attachments %><%= locations %><%= events %></div></div></div>');
layouts[3] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-4"><%= meta %><%= plots %></div><div class="col-sm-4"><%= characters %></div><div class="col-sm-4"><%= attachments %><%= locations %><%= events %></div></div></div>');

/**
 * Called on page load before DOM and data
 * @memberOf deck
 */
deck.init = function init(data) {
	date_creation = data.date_creation;
	date_update = data.date_update;
	description_md = data.description_md;
	id = data.id;
	name = data.name;
	slots = data.slots;
	tags = data.tags;
	faction_code = data.faction_code;
	faction_name = data.faction_name;
	unsaved = data.unsaved;
	user_id = data.user_id;
	
	// when app.data has finished, update the card database
	$(document).on('data.app', deck.on_data_loaded);
}

deck.on_data_loaded = function deck_on_data_loaded()
{
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
deck.display = function display(container, options) {
	
	options = _.extend({sort: 'type', cols: 2}, options);

	var layout_data = deck.get_layout_data(options);
	var deck_content = layouts[options.cols](layout_data);

	$(container)
		.removeClass('deck-loading')
		.empty();

	$(container).append(deck_content);
}

deck.get_layout_data = function get_layout_data(options) {
	
	var data = {
			images: '',
			meta: '',
			plots: '',
			characters: '',
			attachments: '',
			locations: '',
			events: ''
	};
	
	var agenda = deck.get_agenda();
	var problem = deck.get_problem();

	deck.update_layout_section(data, 'images', $('<div style="margin-bottom:10px"><img src="/bundles/app/images/factions/'+deck.get_faction_code()+'.png" class="img-responsive">'));
	if(agenda) {
		deck.update_layout_section(data, 'images', $('<div><img src="'+agenda.imagesrc+'" class="img-responsive">'));
	}

	deck.update_layout_section(data, 'meta', $('<h4 style="font-weight:bold">'+faction_name+'</h4>'));
	if(agenda) {
		var agenda_line = $('<h5>').append($(card_line_tpl({card:agenda})));
		agenda_line.find('.icon').remove();
		deck.update_layout_section(data, 'meta', agenda_line);
	}
	deck.update_layout_section(data, 'meta', $('<div>Draw deck: '+deck.get_draw_deck_size()+' cards</div>').addClass(deck.get_draw_deck_size() < 60 ? 'text-danger': ''));
	deck.update_layout_section(data, 'meta', $('<div>Plot deck: '+deck.get_plot_deck_size()+' cards</div>').addClass(deck.get_plot_deck_size() != 7 ? 'text-danger': ''));
	deck.update_layout_section(data, 'meta', $('<div>Packs: ' + _.map(deck.get_included_packs(), function (pack) { return pack.name+(pack.quantity > 1 ? ' ('+pack.quantity+')' : ''); }).join(', ') + '</div>'));
	if(problem) {
		deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>'));
	}

	deck.update_layout_section(data, 'plots', deck.get_layout_data_one_section('type_code', 'plot', 'type_name'));
	deck.update_layout_section(data, 'characters', deck.get_layout_data_one_section('type_code', 'character', 'type_name'));
	deck.update_layout_section(data, 'attachments', deck.get_layout_data_one_section('type_code', 'attachment', 'type_name'));
	deck.update_layout_section(data, 'locations', deck.get_layout_data_one_section('type_code', 'location', 'type_name'));
	deck.update_layout_section(data, 'events', deck.get_layout_data_one_section('type_code', 'event', 'type_name'));
	
	return data;
}

deck.update_layout_section = function update_layout_section(data, section, element) {
	data[section] = data[section] + element[0].outerHTML;
}

deck.get_layout_data_one_section = function get_layout_data_one_section(sortKey, sortValue, displayLabel) {
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
	app.deck_history && app.deck_history.notify_change();

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
		'01202': 'thenightswatch',
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
