(function app_deck(deck, $) {

var date_creation,
	date_update,
	description_md,
	id,
	name,
	tags,
	xp,
	xp_spent = 0, 
	exile_string = "",
	exiles = [],
	investigator_code,
	investigator_name,
	unsaved,
	user_id,
	sort_type = "default",
	sort_dir = 1,
	problem_list = [],
	problem_labels = {
		too_few_cards: "Contains too few cards",
		too_many_cards: "Contains too many cards",
		deck_options_limit: "Contains too many limited cards", 
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
layouts[1] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-5 col-print-6"><%= images %></div><div class="col-sm-7 col-print-6"><%= meta %></div></div><div class="row"><div class="col-sm-10 col-print-10"><%= cards %></div></div></div>'); 
layouts[2] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-5 col-print-6"><%= images %></div><div class="col-sm-7 col-print-6"><%= meta %></div></div><div class="row"><div class="col-sm-6 col-print-6"><%= assets %></div><div class="col-sm-6 col-print-6"><%= events %> <%= skills %></div></div> <hr> <div class="row"><div class="col-sm-6 col-print-6"> <%= outassets %> <%= outevents %> <%= outskills %> </div><div class="col-sm-6 col-print-6"><%= outtreachery %> <%= outenemy %></div> </div><div id="upgrade_changes"></div></div>');
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
	investigator = false;
	unsaved = data.unsaved;
	user_id = data.user_id;
	exile_string = data.exile_string;
	if (exile_string){
		exiles = exile_string.split(",");
	}
	xp = data.xp;
	next_deck = data.next_deck;
	previous_deck = data.previous_deck;
	if (localStorage && localStorage.getItem('ui.deck.sort')) {
		deck.sort_type = localStorage.getItem('ui.deck.sort');
	}
	
	
	if(app.data.isLoaded) {
		deck.set_slots(data.slots);
		investigator = app.data.cards.findById(investigator_code);
		
	} else {
		//console.log("deck.set_slots put on hold until data.app");
		$(document).on('data.app', function () { 
			deck.set_slots(data.slots); 
			investigator = app.data.cards.findById(investigator_code);
		});
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
deck.get_tags = function get_tags() {
	return tags;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.get_next_deck = function get_next_deck() {
	return next_deck;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.get_previous_deck = function get_previous_deck() {
	return previous_deck;
}


/**
 * @memberOf deck
 * @returns integer
 */
deck.get_xp = function get_xp() {
	return xp;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.get_xp_spent = function get_xp_spent() {
	return xp_spent;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.set_xp_spent = function set_xp_spent(spent_xp) {
	xp_spent = spent_xp;
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
deck.get_exiles = function get_exiles() {
	return exiles;
}

/**
 * @memberOf deck
 * @returns string
 */
deck.get_exile_string = function get_exile_string() {
	return exile_string;
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
deck.get_cards = function get_cards(sort, query, group) {
	sort = sort || {};
	sort['code'] = 1;
	
	query = query || {};
	query.indeck = {
		'$gt': 0
	};
	
	var options = {
		'$orderBy': sort
	};
	if (group){
		options.$groupBy = group;
	}
	//console.log(query, options);
	return app.data.cards.find(query, options);
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
		},
		permanent: false
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
 * get the actual deck used in the game, which excludes permanents
 */
deck.get_physical_draw_deck = function get_physical_draw_deck(sort) {
	return deck.get_cards(sort, {
		type_code: {
			'$nin' : []
		},
		permanent: false
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
	deck.get_real_draw_deck().forEach(function (card) {
		if (card && card.xp){
			xp += card.xp * card.indeck * (card.exceptional ? 2: 1);
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


deck.change_sort = function(sort_type){
	if (localStorage) {
		localStorage.setItem('ui.deck.sort', sort_type);
	}
	deck.sort_type = sort_type;
	if ($("#deck")){
		deck.display('#deck');
	}

	if ($("#deck-content")){
		deck.display('#deck-content');
	}
	
	if ($("#decklist")){
		deck.display('#decklist');
	}
	
}

/**
 * @memberOf deck
 */
deck.display = function display(container, options) {
	// XXX fetch the selected sort here
	// default is 2 it seems
	//console.log(options);
	options = _.extend({sort: 'type', cols: 2}, options);

	var deck_content = deck.get_layout_data(options);

	$(container)
		.removeClass('deck-loading')
		.empty();

	//console.log(deck_content, container);
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
			outenemy: '',
			cards: ''
	};
	
	//var investigator = deck.get_investigator();
	var problem = deck.get_problem();
	$("input[name=problem]").val(problem);
	
	var card = app.data.cards.findById(this.get_investigator_code());
	var size = 30;
	var req_count = 0;
	var req_met_count = 0;
	
	if (card && card.deck_requirements){
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
	deck.update_layout_section(data, 'meta', $('<div><span style="border-bottom: 1px dashed #cfcfcf;" title="' + _.map(deck.get_included_packs(), function (pack) { return pack.name+(pack.quantity > 1 ? ' ('+pack.quantity+')' : ''); }).join(', ') + '">' + deck.get_included_packs().length + ' packs required </span>' + '</div>'));
	if(deck.get_tags && deck.get_tags() ) {
		deck.update_layout_section(data, 'meta', $('<div>'+deck.get_tags().replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();})+'</div>'));
	}
	if(problem) {
		//console.log(deck.problem_list);
		if (deck.problem_list && deck.problem_list.length > 0){
			deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+deck.problem_list.join(', ')+'</div>'));
		} else {
			deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>'));
		}
		
	}
	//deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>'));
	//console.log("reload", deck.sort_type);
	
	
	//var sort = "default";
	//sort = $("#sort_deck_view").val();
	var layout_template = 2;
	if (deck.sort_type == "name"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'name': 1}, null, null));
		//deck.update_layout_section(data, "cards", deck.get_layout_section({'name': 1}, {"type_name":1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "set"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'pack_code': 1, "name": 1}, {'pack_name':1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "setnumber"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'pack_code': 1, "position": 1}, {'pack_name':1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "faction"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "name":1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "factionnumber"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "pack_code":1, "position": 1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "factionxp"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "xp":1, "name": 1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "number"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'code': 1}, null, null));
		layout_template = 1;
	} else if (deck.sort_type == "xp"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'xp': -1, 'name': 1}, {xp: 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "cost"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'cost': 1, 'name': 1}, {'cost':1}, null));
		layout_template = 1;
	} else {
		layout_template = 2;
		deck.update_layout_section(data, 'assets', deck.get_layout_data_one_section('type_code', 'asset', 'type_name', false));
		deck.update_layout_section(data, 'events', deck.get_layout_data_one_section('type_code', 'event', 'type_name', false));
		deck.update_layout_section(data, 'skills', deck.get_layout_data_one_section('type_code', 'skill', 'type_name', false));
		
		deck.update_layout_section(data, 'outassets', deck.get_layout_data_one_section('type_code', 'asset', 'type_name', true));
		deck.update_layout_section(data, 'outevents', deck.get_layout_data_one_section('type_code', 'event', 'type_name', true));
		deck.update_layout_section(data, 'outskills', deck.get_layout_data_one_section('type_code', 'skill', 'type_name', true));
		deck.update_layout_section(data, 'outtreachery', deck.get_layout_data_one_section('type_code', 'treachery', 'type_name', true));
		deck.update_layout_section(data, 'outenemy', deck.get_layout_data_one_section('type_code', 'enemy', 'type_name', true));
	}
	
	return layouts[layout_template](data);
}

deck.get_layout_section = function(sort, group, filter){
	var section = $('<div>');
	var query = {};
	var groups = {};
	var context = "";
	if (sort && sort.code){
		context = "number";
	}
	if (sort && sort.position){
		context = "number";
	}
	// if we have a group, then send the group by to the query
	if (group){
		var cards = deck.get_cards(sort, query, group);	
		//console.log(group);
	} else {
		var cards = deck.get_cards(sort, query);
	}
	
	if(cards.length) {
		
		//console.log(cards);
		//$(header_tpl({code: "Cards", name: "Cards", quantity: deck.get_nb_cards(cards)})).appendTo(section);
		//'<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>'
		// run through each card and display display it
		deck.create_card_group(cards, context).appendTo(section);
			
	} else if (cards.constructor !== Array){		
		$.each(cards, function (index, group_cards) {
		//cards.forEach(function (group_cards) {			
			if (group_cards.constructor === Array){
				//console.log(group_cards);
				$(header_tpl({code: index, name: index == "undefined" ? "Null" : index, quantity: group_cards.reduce(function(a,b){ return a + b.indeck}, 0) })).appendTo(section);
				deck.create_card_group(group_cards, context).appendTo(section);
			}
		});
	}
	return section;
}

deck.create_card_group = function(cards, context){
	var section = $('<div>');
	cards.forEach(function (card) {
		var $div = $('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card');
			
		$div.append($(card_line_tpl({card:card})));
		$div.prepend(card.indeck+'x ');
		if(card.xp && card.xp > 0) {
			$div.append(app.format.xp(card.xp));
		}
		
		if (context && context == "number"){
			$div.append(" | "+card.pack_name+" #"+card.position);
		}

		// add special random selection button for the random basic weakness item
		if (card.name == "Random Basic Weakness" && $("#special-collection").length > 0 ){
			$div.append(' <a class="fa fa-random" title="Replace with randomly selected weakness from currently selected packs" data-random="'+card.code+'"> <span ></span></a> ');
		}
		$div.appendTo(section);
	});
	return section;
}


deck.update_layout_section = function update_layout_section(data, section, element) {
	data[section] = data[section] + element[0].outerHTML;
}

deck.get_layout_data_one_section = function get_layout_data_one_section(sortKey, sortValue, displayLabel, out) {
	var section = $('<div>');
	var query = {};
	query[sortKey] = sortValue;
	if (out == true){
		query["$or"] = [
			{
				xp: {
					'$exists': false
				}
			},
			{
				permanent: true
			}
		];
	} else {
		query.xp = {
			'$in': [0,1,2,3,4,5]
		};
		query.permanent = false;
	}
	
	var cards = deck.get_cards({ name: 1 }, query);
	if(cards.length) {
		var name = cards[0][displayLabel];
		if (sortValue == "asset"){
			$(header_tpl({code: sortValue, name: name, quantity: deck.get_nb_cards(cards)})).appendTo(section);
			var slots = {
				'Hand': [],
				'Hand x2': [],
				'Arcane': [],
				'Arcane x2': [],
				'Accessory': [],
				'Accessory': [],
				'Body': [],
				'Ally': [],
				'Other': []
			};
			cards.forEach(function (card) {
				var $div = $('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card');
				//if (card.slot){
				//	$div.append($(card_line_tpl({card:card})+' <span class="small slot-header">'+card.slot+'</span>'));
				//}else {
				$div.append($(card_line_tpl({card:card})));
				//}
				$div.prepend(card.indeck+'x ');
				if(card.xp && card.xp > 0) {
					$div.append(app.format.xp(card.xp, card.indeck));
					
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
					$('<div class="slot-header small">'+index+'</div>').appendTo(section);
					$.each(slot,function (index, div) {
						div.appendTo(section);
					});
				}
			});
		} else {
			$(header_tpl({code: sortValue, name: name, quantity: deck.get_nb_cards(cards)})).appendTo(section);
			cards.forEach(function (card) {
				var $div = $('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card');
				
				$div.append($(card_line_tpl({card:card})));
				
				$div.prepend(card.indeck+'x ');
				if(card.xp && card.xp > 0) {
					$div.append(app.format.xp(card.xp, card.indeck));
				}
				if(app.data.cards.find({'name': card.name}).length > 1) {
					//$div.append(' ('+card.pack_code+')');
				}
				if (card.name == "Random Basic Weakness" && $("#special-collection").length > 0 ){
					$div.append(' <a class="fa fa-random" title="Replace with randomly selected weakness from currently selected packs" data-random="'+card.code+'"> <span ></span></a> ');
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
		var value = copies_and_deck_limit[card.real_name];
		if(!value) {
			copies_and_deck_limit[card.real_name] = {
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
	// store list of all problems 
	deck.problem_list = [];
	if (card && card.deck_requirements){
		if (card.deck_requirements.size){
			size = card.deck_requirements.size;
		}
		//console.log(card.deck_requirements);
		// must have the required cards
		if (card.deck_requirements.card){
			var req_count = 0;
			var req_met_count = 0;
			$.each(card.deck_requirements.card, function (key, possible){
				req_count++;
				var found_match = false;
				$.each(possible, function (code, code2){
					var req = app.data.cards.findById(code);
					if (req && req.indeck){
						found_match = true;
					}
				});
				if (found_match){
					req_met_count++;
				}
			});
			if (req_met_count < req_count){
				return "investigator";
			}
		}
	} else {
		
	}

	// too many copies of one card
	if(_.findKey(deck.get_copies_and_deck_limit(), function(value) {
	    return value.nb_copies > value.deck_limit;
	}) != null) return 'too_many_copies';

	// no invalid card
	if(deck.get_invalid_cards().length > 0) {
		return 'invalid_cards';
	}
		
	//console.log(investigator);
	for (var i = 0; i < investigator.deck_options.length; i++){
		//console.log(investigator.deck_options);
		if (investigator.deck_options[i].limit_count && investigator.deck_options[i].limit){
			if (investigator.deck_options[i].limit_count > investigator.deck_options[i].limit){
				if (investigator.deck_options[i].error){
					deck.problem_list.push(investigator.deck_options[i].error);
				}
				return 'investigator';
			}
		}
		
		if (investigator.deck_options[i].atleast_count && investigator.deck_options[i].atleast){
			if (investigator.deck_options[i].atleast.factions && investigator.deck_options[i].atleast.min){
				var faction_count = 0;
				$.each(investigator.deck_options[i].atleast_count, function(key, value){
					if (value >= investigator.deck_options[i].atleast.min){
						faction_count++;
					}
				})
				if (faction_count < investigator.deck_options[i].atleast.factions){
					if (investigator.deck_options[i].error){
						deck.problem_list.push(investigator.deck_options[i].error);
					}
					return 'investigator';
				}
			}
		}
	}
	
		// at least 60 others cards
	if(deck.get_draw_deck_size() < size) {
		return 'too_few_cards';
	}
	
	// at least 60 others cards
	if(deck.get_draw_deck_size() > size) {
		return 'too_many_cards';
	}
	
}

deck.get_invalid_cards = function get_invalid_cards() {
	//var investigator = app.data.cards.findById(investigator_code);
	if (investigator){
		for (var i = 0; i < investigator.deck_options.length; i++){
			investigator.deck_options[i].limit_count = 0;
			investigator.deck_options[i].atleast_count = {};
		}
	}
	return _.filter(deck.get_cards(), function (card) {
		return ! deck.can_include_card(card, true);
	});
}

/**
 * returns true if the deck can include the card as parameter
 * @memberOf deck
 */
deck.can_include_card = function can_include_card(card, limit_count) {
	
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
	
	//var investigator = app.data.cards.findById(investigator_code);
	
	if (investigator && investigator.deck_options && investigator.deck_options.length) {
		
		//console.log(card);
		for (var i = 0; i < investigator.deck_options.length; i++){
			var option = investigator.deck_options[i];
			//console.log(option);
			
			var valid = false;
			
			if (option.faction){
				// needs to match at least one faction				
				var faction_valid = false;
				for(var j = 0; j < option.faction.length; j++){
					var faction = option.faction[j];
					if (card.faction_code == faction){
						faction_valid = true;
					}
				}
				
				if (!faction_valid){
					continue;
				}
				//console.log("faction valid");
			}
			
			if (option.type){
				// needs to match at least one faction				
				var type_valid = false;
				for(var j = 0; j < option.type.length; j++){
					var type = option.type[j];
					if (card.type_code == type){
						type_valid = true;
					}
				}
				
				if (!type_valid){
					continue;
				}
				//console.log("faction valid");
			}
			
			if (option.trait){
				// needs to match at least one trait				
				var trait_valid = false;				
				
				for(var j = 0; j < option.trait.length; j++){
					var trait = option.trait[j];
					//console.log(card.traits, trait.toUpperCase()+".");
					
					if (card.traits && card.traits.toUpperCase().indexOf(trait.toUpperCase()+".") !== -1){
						trait_valid = true;
					}
				}
				
				if (!trait_valid){
					continue;
				}
				//console.log("faction valid");
			}
			
			if (option.uses){
				// needs to match at least one trait	
				var uses_valid = false;
				
				for(var j = 0; j < option.uses.length; j++){
					var uses = option.uses[j];
					//console.log(card.traits, trait.toUpperCase()+".");
					
					if (card.text && card.text.toUpperCase().indexOf(""+uses.toUpperCase()+").") !== -1){
						uses_valid = true;
					}
				}
				
				if (!uses_valid){
					continue;
				}
				//console.log("faction valid");
			}
			
			if (option.level){
				// needs to match at least one faction
				var level_valid = false;
				//console.log(option.level, card.xp, card.xp >= option.level.min, card.xp <= option.level.max);
				
				if (typeof card.xp !== 'undefined' && option.level){
					if (card.xp >= option.level.min && card.xp <= option.level.max){
						level_valid = true;
					}else {
						continue;	
					}
				}
				//console.log("level valid");
			}
			
			
			if (option.not){
				return false;
			}else {
				if (limit_count && option.limit){
					//console.log(card);
					option.limit_count += card.indeck;
				}
				if (limit_count && option.atleast){
					if (!option.atleast_count[card.faction_code]){
						option.atleast_count[card.faction_code] = 0;
					}
					option.atleast_count[card.faction_code] += card.indeck;
				}
				return true;
			}
			
		}
	}
	
	if (!card.xp){
		
	}
	
	return false;
}

})(app.deck = {}, jQuery);
