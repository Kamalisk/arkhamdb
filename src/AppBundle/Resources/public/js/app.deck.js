(function app_deck(deck, $) {

var date_creation,
	date_update,
	description_md,
	id,
	name,
	tags,
	investigator_code,
	investigator_name,
	unsaved,
	user_id,
	problem_labels = {
		too_few_cards: "Contains too few cards",
		too_many_cards: "Contains too many cards",
		too_many_copies: "Contains too many copies of a card (by title)",
		invalid_cards: "Contains forbidden cards (cards not permitted by Investigator)",
		investigator: "Doesn't comply with the Investigator requirements"
	},
	header_tpl = _.template('<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>'),
	card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> icon-<%= card.faction_code %>"></span> <a href="<%= card.url %>" class="card card-tip fg-<%= card.faction_code %>" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>'),
	layouts = {},
	layout_data = {};

/*
 * Templates for the different deck layouts, see deck.get_layout_data
 */
layouts[1] = _.template('<div class="deck-content"><%= meta %><%= assets %><%= events %><%= skills %> <%= outassets %> <%= outevents %> <%= outskills %> <%= outtreachery %> <%= outenemy %> </div>');
layouts[2] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-5 col-print-6"><%= images %></div><div class="col-sm-7 col-print-6"><%= meta %></div></div><div class="row"><div class="col-sm-6 col-print-6"><%= assets %></div><div class="col-sm-6 col-print-6"><%= events %> <%= skills %></div></div> <hr> <div class="row"><div class="col-sm-6 col-print-6"> <%= outassets %> <%= outevents %> <%= outskills %> </div><div class="col-sm-6 col-print-6"><%= outtreachery %> <%= outenemy %></div> </div></div>');
layouts[3] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-4"><%= images %><%= meta %></div><div class="col-sm-4"><%= assets %><%= skills %></div><div class="col-sm-4"><%= events %><%= treachery %></div></div></div>');

/**
 * @memberOf deck
 */
deck.init = function init(data) {
	date_creation = data.date_creation;
	date_update = data.date_update;
	description_md = data.description_md;
	id = data.id;
	name = data.name;
	tags = data.tags;
	investigator_code = data.investigator_code;
	investigator_name = data.investigator_name;
	unsaved = data.unsaved;
	user_id = data.user_id;
	
	if(app.data.isLoaded) {
		deck.set_slots(data.slots);
	} else {
		console.log("deck.set_slots put on hold until data.app");
		$(document).on('data.app', function () { deck.set_slots(data.slots); });
	}
}

/**
 * Sets the slots of the deck
 * @memberOf deck
 */
deck.set_slots = function set_slots(slots) {
	app.data.cards.update({}, {
		indeck: 0
	});
	//console.log(slots);
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
deck.get_investigator_code = function get_investigator_code() {
	return investigator_code;
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
			'$nin' : []
		},
		xp: {
			'$exists': true
		}
	});
}

/**
 * @memberOf deck
 */
deck.get_real_draw_deck = function get_real_draw_deck(sort) {
	return deck.get_cards(sort, {
		type_code: {
			'$nin' : []
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
deck.get_real_draw_deck_size = function get_real_draw_deck_size(sort) {
	var draw_deck = deck.get_real_draw_deck();
	return deck.get_nb_cards(draw_deck);
}

/**
 * @memberOf deck
 */
deck.get_xp_usage = function get_xp_usage(sort) {
	var xp = 0;
	deck.get_draw_deck().forEach(function (card) {
		if (card && card.xp){
			xp += card.xp * card.indeck;
		}
	});
	return xp;
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
			assets: '',
			events: '',
			skills: '',
			outassets: '',
			outevents: '',
			outskills: '',
			outtreachery: '',
			outenemy: ''
	};
	
	//var investigator = deck.get_investigator();
	var problem = deck.get_problem();

	var card = app.data.cards.findById(this.get_investigator_code());
	var size = 30;
	var req_count = 0;
	var req_met_count = 0;
	
	if (card.deck_requirements){
		if (card.deck_requirements.size){
			size = card.deck_requirements.size;
		}
		// must have the required cards
		if (card.deck_requirements.card){
			$.each(card.deck_requirements.card, function (key, value){
				req_count++;
				var req = app.data.cards.findById(value);
				if (req && req.indeck){
					req_met_count++;
				}
			});
			if (req_met_count < req_count){
				//return "investigator";
			}
		}
	}
	
	deck.update_layout_section(data, 'images', $('<div style="margin-bottom:10px"><img src="/bundles/cards/'+deck.get_investigator_code()+'.png" class="img-responsive">'));
	
	deck.update_layout_section(data, 'meta', $('<h4 style="font-weight:bold"><a class="card card-tip data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+deck.get_investigator_code()+'">'+investigator_name+'</a></h4>'));
	//deck.update_layout_section(data, 'meta', $('<h4 style="font-weight:bold">'+investigator_name+'</h4>'));
	deck.update_layout_section(data, 'meta', $('<div>'+deck.get_draw_deck_size()+' cards ('+deck.get_real_draw_deck_size()+' total)</div>').addClass(deck.get_draw_deck_size() < size ? 'text-danger': ''));
	deck.update_layout_section(data, 'meta', $('<div>'+deck.get_xp_usage()+' experience required.</div>'));
	deck.update_layout_section(data, 'meta', $('<div>Packs: ' + _.map(deck.get_included_packs(), function (pack) { return pack.name+(pack.quantity > 1 ? ' ('+pack.quantity+')' : ''); }).join(', ') + '</div>'));
	if(problem) {
		deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>'));
	}

	deck.update_layout_section(data, 'assets', deck.get_layout_data_one_section('type_code', 'asset', 'type_name', false));
	deck.update_layout_section(data, 'events', deck.get_layout_data_one_section('type_code', 'event', 'type_name', false));
	deck.update_layout_section(data, 'skills', deck.get_layout_data_one_section('type_code', 'skill', 'type_name', false));
	//deck.update_layout_section(data, 'treachery', deck.get_layout_data_one_section('type_code', 'treachery', 'type_name', false));
	
	deck.update_layout_section(data, 'outassets', deck.get_layout_data_one_section('type_code', 'asset', 'type_name', true));
	deck.update_layout_section(data, 'outevents', deck.get_layout_data_one_section('type_code', 'event', 'type_name', true));
	deck.update_layout_section(data, 'outskills', deck.get_layout_data_one_section('type_code', 'skill', 'type_name', true));
	deck.update_layout_section(data, 'outtreachery', deck.get_layout_data_one_section('type_code', 'treachery', 'type_name', true));
	deck.update_layout_section(data, 'outenemy', deck.get_layout_data_one_section('type_code', 'enemy', 'type_name', true));
	return data;
}

deck.update_layout_section = function update_layout_section(data, section, element) {
	data[section] = data[section] + element[0].outerHTML;
}

deck.get_layout_data_one_section = function get_layout_data_one_section(sortKey, sortValue, displayLabel, out) {
	var section = $('<div>');
	var query = {};
	query[sortKey] = sortValue;
	if (out == true){
		query.xp = {
			'$exists': false
		};
	} else {
		query.xp = {
			'$in': [0,1,2,3,4,5]
		};	
	}
	
	var cards = deck.get_cards({ name: 1 }, query);
	if(cards.length) {
		var name = cards[0][displayLabel];
		if (sortValue == "asset"){
			$(header_tpl({code: sortValue, name: name, quantity: deck.get_nb_cards(cards)})).appendTo(section);
			var slots = {
				'1-Handed': [],
				'2-Handed': [],
				'Accessory': [],
				'Accessory': [],
				'Body': [],
				'Ally': [],
				'Other': []
			};
			cards.forEach(function (card) {
				var $div = $('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card');
				if (card.slot){
					$div.append($(card_line_tpl({card:card})+' <span class="small slot-header">'+card.slot+'</span>'));
				}else {
					$div.append($(card_line_tpl({card:card})));
				}
				$div.prepend(card.indeck+'x ');
				if(card.xp && card.xp > 0) {
					$div.append(' <span class="xp-'+card.xp+'">('+card.xp+')</span>');
				}
				if(app.data.cards.find({'name': card.name}).length > 1) {
					//$div.append(' ('+card.pack_code+')');
				}
				if (card.slot && slots[card.slot]){
					slots[card.slot].push($div);
				} else {
					slots["Other"].push($div);
				}
			});
			$.each(slots,function (index, slot) {
				if(slot.length > 0){
					//$('<div class="slot-header small">'+index+'</div>').appendTo(section);
					$.each(slot,function (index, div) {
						div.appendTo(section);
					});
				}
			});
		} else {
			$(header_tpl({code: sortValue, name: name, quantity: deck.get_nb_cards(cards)})).appendTo(section);
			cards.forEach(function (card) {
				var $div = $('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card');
				
				if (sortValue == "asset"){
					if (card.slot){
						$div.append($(card_line_tpl({card:card})+' <span class="small slot-header">'+card.slot+'</span>'));
					}else {
						$div.append($(card_line_tpl({card:card})));
					}
				}else {
					$div.append($(card_line_tpl({card:card})));
				}
				$div.prepend(card.indeck+'x ');
				if(card.xp && card.xp > 0) {
					$div.append(' <span class="xp-'+card.xp+'">('+card.xp+')</span>');
				}
				if(app.data.cards.find({'name': card.name}).length > 1) {
					//$div.append(' ('+card.pack_code+')');
				}
				$div.appendTo(section);
			});
		}
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
deck.get_copies_and_deck_limit = function get_copies_and_deck_limit() {
	var copies_and_deck_limit = {};
	deck.get_draw_deck().forEach(function (card) {
		var value = copies_and_deck_limit[card.name];
		if(!value) {
			copies_and_deck_limit[card.name] = {
					nb_copies: card.indeck,
					deck_limit: card.deck_limit
			};
		} else {
			value.nb_copies += card.indeck;
			value.deck_limit = Math.min(card.deck_limit, value.deck_limit);
		}
	})
	return copies_and_deck_limit;
}

/**
 * @memberOf deck
 */
deck.get_problem = function get_problem() {
	
	// get investigator data
	var card = app.data.cards.findById(this.get_investigator_code());
	var size = 30;
	if (card.deck_requirements){
		if (card.deck_requirements.size){
			size = card.deck_requirements.size;
		}
		console.log(card.deck_requirements);
		// must have the required cards
		if (card.deck_requirements.card){
			var req_count = 0;
			var req_met_count = 0;
			$.each(card.deck_requirements.card, function (key, value){
				req_count++;
				var req = app.data.cards.findById(value);
				if (req && req.indeck){
					req_met_count++;
				}
			});
			if (req_met_count < req_count){
				return "investigator";
			}
		}
	}
	
	// at least 60 others cards
	if(deck.get_draw_deck_size() < size) {
		return 'too_few_cards';
	}

	// too many copies of one card
	if(_.findKey(deck.get_copies_and_deck_limit(), function(value) {
	    return value.nb_copies > value.deck_limit;
	}) != null) return 'too_many_copies';

	// no invalid card
	if(deck.get_invalid_cards().length > 0) {
		return 'invalid_cards';
	}
	
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
	
	// hide investigators
	if (card.type_code === "investigator") {
		return false;
	}
	if (card.faction_code === "mythos") {
		return false;
	}
	
	// reject cards restricted
	if (card.restrictions && card.restrictions.investigator &&  card.restrictions.investigator[0] !== investigator_code){
		return false;
	}
	
	var investigator = app.data.cards.findById(investigator_code);
	if (investigator.deck_options) {
		if (investigator.deck_options.faction && investigator.deck_options.faction[card.faction_code]){
			return true;
		}
		if (investigator.deck_options.cards && investigator.deck_options.cards.any){
			return true;
		}
	}
	
	// allow all cards
	// XXX
	return false;
	// neutral card => yes
	//if(card.faction_code === 'neutral') return true;

	// in-house card => yes
	//if(card.faction_code === faction_code) return true;

	// out-of-house and loyal => no
	//if(card.is_loyal) return false;

	// minor faction => yes
	//var minor_faction_code = deck.get_minor_faction_code();
	//if(minor_faction_code && minor_faction_code === card.faction_code) return true;

	// if none above => no
	return false;
}

})(app.deck = {}, jQuery);
