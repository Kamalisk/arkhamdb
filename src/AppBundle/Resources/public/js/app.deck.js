(function app_deck(deck, $) {

var date_creation,
	date_update,
	description_md,
	id,
	name,
	tags,
	meta,
	choices,
	xp,
	xp_spent = 0, 
	exile_string = "",
	exiles = [],
	investigator_code,
	investigator_name,
	investigator,
	deck_options,
	unsaved,
	user_id,
	taboo_id, 
	sort_type = "default",
	sort_dir = 1,
	problem_list = [],
	no_collection = true,
	collection = {},
	problem_labels = {
		too_few_cards: "Contains too few cards",
		too_many_cards: "Contains too many cards",
		deck_options_limit: "Contains too many limited cards", 
		too_many_copies: "Contains too many copies of a card (by title)",
		invalid_cards: "Contains forbidden cards (cards not permitted by Investigator)",
		investigator: "Doesn't comply with the Investigator requirements"
	},
	header_tpl = _.template('<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>'),
	card_line_tpl = _.template('<span class="icon icon-<%= card.type_code %> icon-<%= card.faction_code %>"></span><% if (typeof(card.faction2_code) !== "undefined") { %><span class="icon icon-<%= card.faction2_code %>"></span> <% } %> <a href="<%= card.url %>" class="card card-tip fg-<%= card.faction_code %> <% if (typeof(card.faction2_code) !== "undefined") { %> fg-dual <% } %>" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="<%= card.code %>"><%= card.name %></a>'),
	layouts = {},
	layout_data = {};
	

/*
 * Templates for the different deck layouts, see deck.get_layout_data
 */
// one block view
layouts[1] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-5 col-print-6"><%= images %></div><div class="col-sm-7 col-print-6"><%= meta %></div></div><div class="row"><h4 class="deck-section">Deck</h4><div class="col-sm-10 col-print-10"><%= cards %></div></div> <div id="upgrade_changes"></div> </div>'); 
// two colum view
layouts[2] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-5 col-print-6"><%= images %></div><div class="col-sm-7 col-print-6"><%= meta %></div></div><h4 class="deck-section">Deck</h4><div class="row"><div class="col-sm-6 col-print-6"><%= assets %> <%= permanent %> <%= bonded %></div><div class="col-sm-6 col-print-6"><%= events %> <%= skills %> <%= treachery %> <%= enemy %> <%= hunches %></div></div> <div id="upgrade_changes"></div></div>');
layouts[3] = _.template('<div class="deck-content"><div class="row"><div class="col-sm-4"><%= images %><%= meta %></div><h4 class="deck-section">Deck</h4><div class="col-sm-4"><%= assets %><%= skills %></div><div class="col-sm-4"><%= events %><%= treachery %></div></div></div>');
// single column view
layouts[4] = _.template('<div class="deck-content"><div class="row"><%= images %></div><div class="row"><div class="col-sm-7 col-print-6"><%= meta %></div></div><div class="row"><h4 class="deck-section">Deck</h4><div class="col-sm-12 col-print-12"><%= assets %> <%= permanent %> <%= bonded %> <%= events %> <%= skills %> <%= treachery %> <%= enemy %></div></div> <div id="upgrade_changes"></div></div>');
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
	meta = data.meta;
	choices = [];
	investigator_code = data.investigator_code;
	investigator_name = data.investigator_name;
	investigator = false;
	unsaved = data.unsaved;
	user_id = data.user_id;
	exile_string = data.exile_string;
	taboo_id = null;
	if (exile_string){
		exiles = exile_string.split(",");
	}
	xp = data.xp;
	xp_adjustment = data.xp_adjustment;
	next_deck = data.next_deck;
	previous_deck = data.previous_deck;
	if (localStorage && localStorage.getItem('ui.deck.sort')) {
		deck.sort_type = localStorage.getItem('ui.deck.sort');
	}
	deck.choices = [];
	// parse pack owner string
	collection = {};
	no_collection = true;
	
	if(app.data.isLoaded) {
		deck.onloaded(data);
	} else {
		$(document).on('data.app', function () { 
			deck.onloaded(data);
		});
	}
}

deck.onloaded = function(data){
	deck.set_slots(data.slots, data.ignoreDeckLimitSlots);
	investigator = app.data.cards.findById(investigator_code);
	
	if (data.meta){
		deck.meta = JSON.parse(data.meta);
	}
	if (!deck.meta){
		deck.meta = {};
	}
	// check for special deck building rules
	// selecting a class for deck building options
	// selecting front and back for investigator options
	if (investigator && investigator.deck_options && investigator.deck_options.length) {	
		deck.deck_options = investigator.deck_options;
		var alternates = app.data.cards.find({'alternate_of_code': investigator.code});
		if (alternates && alternates.length > 0) {
			var alternate_choices = [];
			for (var i = 0; i < alternates.length; i++){
				alternate_choices.push(alternates[i].code)
			}
			deck.choices.push({'back_select': alternate_choices});
			deck.choices.push({'front_select': alternate_choices});
		}
		for (var i = 0; i < investigator.deck_options.length; i++){
			var option = investigator.deck_options[i];
			if (option.faction_select){
				deck.choices.push(option);
				if (!deck.meta || !deck.meta.faction_selected){
					deck.meta.faction_selected = option.faction_select[0];
				} 
			}
			if (option.deck_size_select){
				deck.choices.push(option);
				if (!deck.meta || !deck.meta.deck_size_selected){
					deck.meta.deck_size_selected = option.deck_size_select[0];
				} 
			}
		}
	}
	// if they user has selected different deck building options, point deck_options to the alternate one
	if (deck.meta && deck.meta.alternate_back) {
		var alternate = app.data.cards.findById(deck.meta.alternate_back);
		if (alternate && alternate.deck_options && alternate.deck_options.length) {
			deck.deck_options = alternate.deck_options;
		}
	}
	if (data.taboo_id){
		deck.taboo_id = data.taboo_id;
	}

	if (app.user.data && app.user.data.owned_packs) {
		var packs = app.user.data.owned_packs.split(',');
		_.forEach(packs, function(str) {
			collection[str] = 1;
			no_collection = false;
		});
	}
}

/**
 * Sets the slots of the deck
 * @memberOf deck
 */
deck.set_slots = function set_slots(slots, ignoreSlots) {
	app.data.cards.update({}, {
		indeck: 0,
		ignore: 0
	});

	for(code in slots) {
		if(slots.hasOwnProperty(code)) {
			app.data.cards.updateById(code, {indeck: slots[code]});			
		}
	}
	for(code in ignoreSlots) {
		if(ignoreSlots.hasOwnProperty(code)) {
			app.data.cards.updateById(code, {ignore: ignoreSlots[code]});			
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
	if (xp_adjustment) {
		return xp + xp_adjustment;
		
	} else {
		return xp;
	}
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
 * @returns integer
 */
deck.get_xp_adjustment = function get_xp_adjustment() {
	if (!xp_adjustment) {
		xp_adjustment = 0;
	}
	return xp_adjustment;
}

/**
 * @memberOf deck
 * @returns integer
 */
deck.set_xp_adjustment = function set_xp_adjustment(xp_adj) {
	if (!xp_adjustment) {
		xp_adjustment = 0;
	}
	
	xp_adjustment = xp_adj;
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
	var myriad_madness = {};
	deck.get_real_draw_deck().forEach(function (card) {
		if (card && (card.xp || card.taboo_xp) && card.ignore < card.indeck) {
			var qty = card.indeck;
			if (typeof card.real_text !== 'undefined' && card.real_text.indexOf('Myriad.') !== -1) {
				qty = 1;
				if (myriad_madness[card.real_name]) {
					qty = 0;
				}
				myriad_madness[card.real_name] = 1;
			}
			xp += (card.xp + (card.taboo_xp ? card.taboo_xp : 0)) * (qty - card.ignore) * (card.exceptional ? 2: 1);
		}
	});
	return xp;
	
}


deck.get_nb_cards = function get_nb_cards(cards) {
	if(!cards) cards = deck.get_cards();
	var quantities = _.pluck(cards, 'indeck');
	var ignores = _.pluck(cards, 'ignore');
	var total = _.reduce(quantities, function(memo, num) { return memo + num; }, 0);
	total -= _.reduce(ignores, function(memo, num) { return memo + num; }, 0);
	return total;
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
	// before displaying a deck, apply the currently active taboo list
	app.data.apply_taboos(deck.taboo_id);
	options = _.extend({sort: 'type', cols: 2}, options);

	var deck_content = deck.get_layout_data(options);

	$(container)
		.removeClass('deck-loading')
		.empty();

	$(container).append(deck_content);
	if (app.deck_history){
		app.deck_history.setup('#history');
	} 

}

deck.get_layout_data = function get_layout_data(options) {
	
	var data = {
			images: '',
			meta: '',
			assets: '',
			events: '',
			skills: '',
			treachery: '',
			enemy: '',
			permanent: '',
			bonded: '',
			hunches: '',
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
		if (deck.meta && deck.meta.deck_size_selected){
			size = parseInt(deck.meta.deck_size_selected, 10);
		}
		var versatile = app.data.cards.findById("06167");
		if (versatile && versatile.indeck) {
			size = size + 5;
			if (versatile.indeck > 1) {
				size = size + 5;
			}
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
	deck.update_layout_section(data, 'meta', $('<h4 style="font-weight:bold"><a class="card card-tip" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+deck.get_investigator_code()+'">'+investigator_name+'</a></h4>'));
	if (app.deck.meta && app.deck.meta.alternate_back) {
		var alternate = app.data.cards.findById(app.deck.meta.alternate_back);
		deck.update_layout_section(data, 'meta', $('<div>Alternate back: <a class="card card-tip" data-toggle="modal" data-back="true" data-remote="false" data-target="#cardModal" data-code="'+alternate.code+'">'+investigator_name+' ('+alternate.pack_name+')</a></div>'));
	}
	if (app.deck.meta && app.deck.meta.alternate_front) {
		var alternate = app.data.cards.findById(app.deck.meta.alternate_front);
		deck.update_layout_section(data, 'meta', $('<div>Alternate front: <a class="card card-tip" data-toggle="modal" data-front="true" data-remote="false" data-target="#cardModal" data-code="'+alternate.code+'">'+investigator_name+' ('+alternate.pack_name+')</a></div>'));
	}
	deck.update_layout_section(data, 'meta', $('<div>'+deck.get_draw_deck_size()+' cards ('+deck.get_real_draw_deck_size()+' total)</div>').addClass(deck.get_draw_deck_size() < size ? 'text-danger': ''));
	deck.update_layout_section(data, 'meta', $('<div>'+deck.get_xp_usage()+' experience required.</div>'));
	var pack_string = _.map(deck.get_included_packs(), function (pack) { return pack.name+(pack.quantity > 1 ? ' ('+pack.quantity+')' : ''); }).join(', ');
	deck.update_layout_section(data, 'meta', $('<div><span onclick="$(\'#packs_required\').toggle()" style="border-bottom: 1px dashed #cfcfcf;" title="' + pack_string + '">' + deck.get_included_packs().length + ' packs required </span>' + ' <div style="display:none;" id="packs_required">'+pack_string+'</div> </div>'));
	if(deck.get_tags && deck.get_tags() ) {
		deck.update_layout_section(data, 'meta', $('<div>'+deck.get_tags().replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();})+'</div>'));
	}
	if(deck.taboo_id && app.data.taboos.findOne({'id':deck.taboo_id})) {
		var taboo = app.data.taboos.findOne({'id':deck.taboo_id});
		deck.update_layout_section(data, 'meta', $('<div>'+taboo.name+' ('+taboo.date_start+')</div>'));
	}
	if(problem) {
		if (deck.problem_list && deck.problem_list.length > 0){
			deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+deck.problem_list.join(', ')+'</div>'));
		} else {
			deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>'));
		}
		
	}
	//deck.update_layout_section(data, 'meta', $('<div class="text-danger small"><span class="fa fa-exclamation-triangle"></span> '+problem_labels[problem]+'</div>'));
	
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
		deck.update_layout_section(data, "cards", deck.get_layout_section({'code': 1, "position": 1}, {'pack_name':1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "faction"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "name":1}, {'faction_name': 1}, null));
		layout_template = 1;
	} else if (deck.sort_type == "factionnumber"){
		deck.update_layout_section(data, "cards", deck.get_layout_section({'faction_code': 1, "code": 1, "position": 1}, {'faction_name': 1}, null));
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
		deck.update_layout_section(data, 'assets', deck.get_layout_data_one_section({'type_code':'asset', permanent: false}, 'type_name'));
		
		if (investigator_name == "Joe Diamond") {
			deck.update_layout_section(data, 'events', deck.get_layout_data_one_section({'type_code': 'event', '$not': {'traits':/Insight./}, permanent: false }, 'type_name'));
			deck.update_layout_section(data, 'hunches', deck.get_layout_data_one_section({'type_code': 'event', 'traits':/Insight./, permanent: false}, 'hunches'));
		} else {
			deck.update_layout_section(data, 'events', deck.get_layout_data_one_section({'type_code': 'event', permanent: false}, 'type_name'));	
		}
		deck.update_layout_section(data, 'skills', deck.get_layout_data_one_section({'type_code': 'skill', permanent: false}, 'type_name'));
		deck.update_layout_section(data, 'treachery', deck.get_layout_data_one_section({'type_code': 'treachery', permanent: false}, 'type_name'));
		deck.update_layout_section(data, 'enemy', deck.get_layout_data_one_section({'type_code': 'enemy', permanent: false}, 'type_name'));
		deck.update_layout_section(data, 'permanent', deck.get_layout_data_one_section({permanent: true}, 'type_name'));
	}
	if (options && options.layout) {
		layout_template = options.layout;
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
	} else {
		var cards = deck.get_cards(sort, query);
	}
	
	if(cards.length) {
		
		//$(header_tpl({code: "Cards", name: "Cards", quantity: deck.get_nb_cards(cards)})).appendTo(section);
		//'<h5><span class="icon icon-<%= code %>"></span> <%= name %> (<%= quantity %>)</h5>'
		// run through each card and display display it
		deck.create_card_group(cards, context).appendTo(section);
			
	} else if (cards.constructor !== Array){		
		$.each(cards, function (index, group_cards) {
		//cards.forEach(function (group_cards) {			
			if (group_cards.constructor === Array){
				$(header_tpl({code: index, name: index == "undefined" ? "Null" : index, quantity: group_cards.reduce(function(a,b){ return a + b.indeck}, 0) })).appendTo(section);
				deck.create_card_group(group_cards, context).appendTo(section);
			}
		});
	}
	return section;
}


deck.update_layout_section = function update_layout_section(data, section, element) {
	data[section] = data[section] + element[0].outerHTML;
}

deck.get_layout_data_one_section = function get_layout_data_one_section(query, displayLabel) {
	var section = $('<div>');

	var cards = deck.get_cards({ name: 1 }, query);
	if(cards.length) {
		var name = "";
		if (displayLabel == "hunches") {
			name = "Hunches";
		} else {
			name = cards[0][displayLabel];
		}
		
		if (query.type_code == "asset"){
			$(header_tpl({code: name, name: name, quantity: deck.get_nb_cards(cards)})).appendTo(section);
			var slots = {
				'Hand': [],
				'Hand x2': [],
				'Arcane': [],
				'Arcane x2': [],
				'Accessory': [],
				'Accessory': [],
				'Body': [],
				'Ally': [],
				'Tarot': [],
				'Other': []
			};
			
			cards.forEach(function (card) {
				var $div = deck.create_card(card);


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
			if (query.permanent) {
				$(header_tpl({code: "Permanent", name: "Permanent", quantity: deck.get_nb_cards(cards)})).appendTo(section);
			} else {
				$(header_tpl({code: name, name: name, quantity: deck.get_nb_cards(cards)})).appendTo(section);
			}
			cards.forEach(function (card) {
				var div = deck.create_card(card);
				div.appendTo(section);
			});
		}
	}
	return section;
}


deck.create_card_group = function(cards, context){
	var section = $('<div>');
	cards.forEach(function (card) {
		var $div = deck.create_card(card);
		$div.appendTo(section);
	});
	return section;
}

deck.create_card = function create_card(card){
	var $div = $('<div>').addClass(deck.can_include_card(card) ? '' : 'invalid-card');

	$div.append($(card_line_tpl({card:card})));
	
	$div.prepend(card.indeck+'x ');
	if(card.xp && card.xp > 0) {
		$div.append(app.format.xp(card.xp, card.indeck));
	}
	if(card.taboo_xp && card.taboo_xp > 0) {
		$div.append(app.format.xp(card.taboo_xp, card.indeck, "taboo"));
	}
	if(card.xp === undefined) {
		$div.append(' <span class="fa fa-star" title="Does not count towards deck size"></span>');
	}
	if(card.ignore) {
		$div.append(' <span class="fa fa-star" style="color:green;" title="'+card.ignore+' of these do not count towards deck size"></span>');
	}
	if(card.taboo === true) {
		$div.append(' <span class="icon-tablet" style="color:purple;" title="Is mutated by the current taboo list"></span>');
	}
	if(card.exceptional === true) {
		$div.append(' <span class="icon-eldersign" style="color:orange;" title="Exceptional. Double xp cost and limit one per deck."></span>');
	}
	
	if (!no_collection){
		var pack = app.data.packs.findById(card.pack_code);
		if (!collection[pack.id]) {
			$div.append(' <span class="fa fa-question" title="This card is not part of your collection"></span>');
		}
	}
	
	if (card.name == "Random Basic Weakness" && $("#special-collection").length > 0 ){
		$div.append(' <a class="fa fa-random" title="Replace with randomly selected weakness from currently selected packs" data-random="'+card.code+'"> <span ></span></a> ');
	}
	return $div;
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
 * @return boolean true if at least one other card quantity was updated
 */
deck.set_card_ignores = function set_card_ignores(card_code, nb_copies) {
	var card = app.data.cards.findById(card_code);
	if(!card) return false;

	var updated_other_card = false;

	app.data.cards.updateById(card_code, {
		ignore: nb_copies
	});

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
deck.get_ignored_cards = function get_ignored_cards() {
	var cards = deck.get_cards();
	var ignored = {};
	cards.forEach(function (card) {
		if (card.ignore > 0){
			ignored[card.code] = card.ignore;
		}
	});
	return ignored;
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
deck.get_ignored_json = function get_ignored_json() {
	return JSON.stringify(deck.get_ignored_cards());
}
/**
 * @memberOf deck
 */
deck.get_meta_json = function get_meta_json() {
	return JSON.stringify(deck.meta);
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
			if (typeof card.real_text !== 'undefined' && card.real_text.indexOf('Myriad.') !== -1) {
				copies_and_deck_limit[card.real_name].deck_limit = 3;
			}
		} else {
			value.nb_copies += card.indeck;
			value.deck_limit = Math.min(card.deck_limit, value.deck_limit);
			if (typeof card.real_text !== 'undefined' && card.real_text.indexOf('Myriad.') !== -1) {
				value.deck_limit = 3;
			}
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
		if (deck.meta && deck.meta.deck_size_selected){
			size = parseInt(deck.meta.deck_size_selected, 10);
		}
		var versatile = app.data.cards.findById("06167");
		if (versatile && versatile.indeck) {
			size = size + 5;
			if (versatile.indeck > 1) {
				size = size + 5;
			}
		}
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
		

	for (var i = 0; i < deck.deck_options.length; i++){

		if (deck.deck_options[i].limit_count && deck.deck_options[i].limit){
			if (deck.deck_options[i].limit_count > deck.deck_options[i].limit){
				if (deck.deck_options[i].error){
					deck.problem_list.push(deck.deck_options[i].error);
				}
				return 'investigator';
			}
		}
		
		if (deck.deck_options[i].atleast_count && deck.deck_options[i].atleast){
			if (deck.deck_options[i].atleast.factions && deck.deck_options[i].atleast.min){
				var faction_count = 0;
				$.each(deck.deck_options[i].atleast_count, function(key, value){
					if (value >= deck.deck_options[i].atleast.min){
						faction_count++;
					}
				})
				if (faction_count < deck.deck_options[i].atleast.factions){
					if (deck.deck_options[i].error){
						deck.problem_list.push(deck.deck_options[i].error);
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

deck.reset_limit_count = function (){
	if (investigator){
		var versatile = app.data.cards.findById("06167");//06167
		var on_your_own = app.data.cards.findById("53010");
		// if they user has selected different deck building options, point deck_options to the alternate one
		if (deck.meta && deck.meta.alternate_back) {
			var alternate = app.data.cards.findById(deck.meta.alternate_back);
			if (alternate && alternate.deck_options && alternate.deck_options.length) {
				deck.deck_options = alternate.deck_options;
			} else {
				deck.deck_options = investigator.deck_options;
			}
		} else {
			deck.deck_options = investigator.deck_options;
		}
		for (var i = deck.deck_options.length - 1; i >= 0 ; i--) {
			if (deck.deck_options[i] && deck.deck_options[i].dynamic) {
				deck.deck_options.splice(i, 1);
			} else {
				deck.deck_options[i].limit_count = 0;
				deck.deck_options[i].atleast_count = {};
			}
		}
		if (on_your_own && on_your_own.indeck) {
			// We put it at the front of the requirements since it is a negated one.
			var new_option = {name: "on_your_own", dynamic: true, not: true, slot: ['Ally']};
			deck.deck_options.unshift(new_option);
		}
		if (versatile && versatile.indeck) {
			var new_option = {name: "versatile", dynamic: true, faction:["guardian", "seeker", "survivor", "mystic", "rogue"], limit: 1, limit_count: 0, level:{"min":0, "max":0} };
			deck.deck_options.push(new_option);
			if (versatile.indeck > 1) {
				new_option = {name: "versatile", dynamic: true, faction:["guardian", "seeker", "survivor", "mystic", "rogue"], limit: 1, limit_count: 0, level:{"min":0, "max":0} };
				deck.deck_options.push(new_option);
			}
		}
	}
}

deck.get_invalid_cards = function get_invalid_cards() {
	//var investigator = app.data.cards.findById(investigator_code);
	deck.reset_limit_count();
	return _.filter(deck.get_cards({'xp': -1}), function (card) {
		return ! deck.can_include_card(card, true);
	});
}

/**
 * returns true if the deck can include the card as parameter
 * @memberOf deck
 */
deck.can_include_card = function can_include_card(card, limit_count, hard_count) {
	// hide investigators
	if (card.type_code === "investigator") {
		return false;
	}
	if (card.faction_code === "mythos") {
		return false;
	}

	// reject cards restricted
	if (card.restrictions && card.restrictions.investigator && !card.restrictions.investigator[investigator_code]) {
			return false;
	}
	
	//var investigator = app.data.cards.findById(investigator_code);
	// store the overflow from one rule to another for deck limit counting
	var overflow = 0; 
	if (deck.deck_options && deck.deck_options.length) {
		
		for (var i = 0; i < deck.deck_options.length; i++){
			var option = deck.deck_options[i];
			
			var valid = false;

			if (option.faction_select && app.deck.meta && app.deck.meta.faction_selected){
				// if the user has selected a faction, update this option with the correct choice
				option.faction = [];
				option.faction.push(app.deck.meta.faction_selected);
			}
			
			if (option.faction){
				// needs to match at least one faction
				var faction_valid = false;
				for(var j = 0; j < option.faction.length; j++){
					var faction = option.faction[j];
					if (card.faction_code == faction || card.faction2_code == faction){
						faction_valid = true;
					}
				}
				
				if (!faction_valid){
					continue;
				}
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
			}
			
			if (option.slot){
				// needs to match at least one slot
				var slot_valid = false;
				
				for(var j = 0; j < option.slot.length; j++){
					var slot = option.slot[j];
					
					if (card.slot && card.slot.toUpperCase().indexOf(slot.toUpperCase()) !== -1){
						slot_valid = true;
					}
				}
				
				if (!slot_valid){
					continue;
				}
			}
			
			if (option.trait){
				// needs to match at least one trait				
				var trait_valid = false;				
				
				for(var j = 0; j < option.trait.length; j++){
					var trait = option.trait[j];
					
					if (card.real_traits && card.real_traits.toUpperCase().indexOf(trait.toUpperCase()+".") !== -1){
						trait_valid = true;
					}
				}
				
				if (!trait_valid){
					continue;
				}
			}
			
			if (option.uses){
				// needs to match at least one trait	
				var uses_valid = false;
				
				for(var j = 0; j < option.uses.length; j++){
					var uses = option.uses[j];
					
					if (card.real_text && card.real_text.toUpperCase().indexOf(""+uses.toUpperCase()+").") !== -1){
						uses_valid = true;
					}
				}
				
				if (!uses_valid){
					continue;
				}

			}
			
			if (option.text){
				// match a regular custom expression on the text
				var text_valid = false;
				
				for(var j = 0; j < option.text.length; j++){
					var text = option.text[j];
					
					if (card.real_text && card.real_text.toLowerCase().match(text)){
						text_valid = true;
					}
				}
				
				if (!text_valid){
					continue;
				}

			}
			
			if (option.level){
				// needs to match at least one faction
				var level_valid = false;
				
				if (typeof card.xp !== 'undefined' && option.level){
					if (card.xp >= option.level.min && card.xp <= option.level.max){
						level_valid = true;
					}else {
						continue;	
					}
				}
			}
			
			
			if (option.not){
				return false;
			}else {
				if (limit_count && option.limit){
					if (option.limit_count >= option.limit) {
						continue;
					} 
					if (hard_count){
						option.limit_count += 1;
					} else {
						// if we have left over from previous options, use that value instead of the qty
						if (overflow) {
							option.limit_count += overflow;
						} else {
							option.limit_count += card.indeck;
						}
						
					}
					if (option.limit_count > option.limit) {
						overflow = option.limit_count - option.limit;
						option.limit_count = option.limit;
						continue;
					}
					
				}
				if (limit_count && option.atleast){
					if (!option.atleast_count[card.faction_code]){
						option.atleast_count[card.faction_code] = 0;
					}
					option.atleast_count[card.faction_code] += card.indeck;
					if (card.faction2_code) {
						if (!option.atleast_count[card.faction2_code]) {
							option.atleast_count[card.faction2_code] = 0;
						}
						option.atleast_count[card.faction2_code] += card.indeck;
					}
				}
				
				return true;
			}
			
		}
	}
	
	return false;
}

})(app.deck = {}, jQuery);
