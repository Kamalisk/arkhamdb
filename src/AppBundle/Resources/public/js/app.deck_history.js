(function app_deck_history(deck_history, $) {

var tbody,
	clock,
	snapshots_init = [],
	snapshots = [],
	base = {},
	xp_spent = 0,
	progressbar,
	timer,
	ajax_in_process = false,
	period = 60,
	previous_meta,
	changed_since_last_autosave = false;


/**
 * @memberOf deck_history
 */
deck_history.all_changes = function all_changes() {
	//console.log("ch ch changes", app.deck.get_content());
	if (snapshots.length <= 0){
		//console.log("boo");
		return;
	}
	// compute diff between last snapshot and current deck
	var last_snapshot = deck_history.base.content;
	var last_meta = deck_history.base.meta;
	var current_deck = app.deck.get_content();
	var current_meta = app.deck.meta || {};

	var result = app.diff.compute_simple([current_deck, last_snapshot], [current_meta, last_meta]);
	if(!result) return;

	var diff = result[0];
	//console.log("DIFFF ", result);

	var free_0_cards = 0;
	var removed_0_cards = 0;
	var cards_removed = [];
	var cards_added = [];
	var cards_exiled = {};

	var cards_customized = [];
	_.each(result[2], function(choices, code) {
		var card = app.data.cards.findById(code);
		var card_customization = {
			"choices": choices,
			"code": code,
			"card": card,
		};
		cards_customized.push(card_customization);
	});
	_.each(diff[1], function (qty, code) {
		var card = app.data.cards.findById(code);
		if(!card) return;
		var card_change = {
			"qty": qty,
			"code": code,
			"card": card
		};
		cards_removed.push(card_change);
	});

	_.each(diff[0], function (qty, code) {
		var card = app.data.cards.findById(code);
		if(!card) return;
		var card_change = {
			"qty": qty,
			"code": code,
			"card": card
		};
		cards_added.push(card_change);
		// versatile
		if (card_change.code == "06167") {
			free_0_cards += card_change.qty * 5;
			removed_0_cards += card_change.qty * 5;
		}
		// ancestral knowledge
		else if (card_change.code == "07303") {
			free_0_cards += 5;
			removed_0_cards += 5;
		}
		// underworld market
		else if (card_change.code == "09077") {
			free_0_cards += 10;
			removed_0_cards += 10;
		}
	});

	// find deja vu
	var deja_vu = app.data.cards.findById("60531");
	var deja_vu_exiled = {};
	// There's a limit on how many discounts per card, and a limit on how
	// many uses overall.
	var deja_vu_max_discount = 0;
	var deja_vu_discounts = 0;
	if (deja_vu && deja_vu.indeck) {
		// You get a discount of 1 XP per exile card, per deja vu.
		deja_vu_max_discount += deja_vu.indeck;
		deja_vu_discounts += deja_vu_max_discount * 3;
	}

	// find down the rabbit hole
	var down_the_rabbit_hole = app.data.cards.findById("08059");
	var dtr_xp = down_the_rabbit_hole && down_the_rabbit_hole.indeck ? 1 : 0;
	var upgrade_discounts = 0;
	if (down_the_rabbit_hole && down_the_rabbit_hole.indeck) {
		upgrade_discounts += 2;
	}

	// find arcane research
	var arcane_research = app.data.cards.findById("04109");
	var spell_upgrade_discounts = 0;
	if (arcane_research && arcane_research.indeck) {
		spell_upgrade_discounts += arcane_research.indeck;
	}

	// find adaptable
	var adaptable = app.data.cards.findById("02110");
	if (adaptable && adaptable.indeck){
		free_0_cards += 2 * adaptable.indeck;
	}
	if (app.deck.get_exiles() && app.deck.get_exiles().length > 0){
		free_0_cards += app.deck.get_exiles().length;
		removed_0_cards = app.deck.get_exiles().length;
		_.each(app.deck.get_exiles(), function (code, id) {
			if (cards_exiled[code]){
				cards_exiled[code] = 2;
				deja_vu_exiled[code] = 2;
			} else {
				cards_exiled[code] = 1;
				deja_vu_exiled[code] = 1;
			}
		});
	}

	var cost = 0;
	var free_customize_swaps = {};
	_.each(cards_customized, function(customization) {
		_.each(customization.choices, function (choice) {
			var xp = choice.xp_delta;
			if (xp > 0 && spell_upgrade_discounts > 0 && customization.card.real_traits && customization.card.real_traits.indexOf('Spell.') !== -1) {
				// Handle Arcane Research discounts if its a 'spell'
				var max_discount = Math.min(spell_upgrade_discounts, xp);
				xp = xp - max_discount;
				spell_upgrade_discounts = spell_upgrade_discounts - max_discount;
			}
			if (xp > 0 && upgrade_discounts > 0) {
				// Handle DTTR, at most one per 'upgrade' taken.
				xp = xp - 1;
				upgrade_discounts = upgrade_discounts - 1;
			}
			cost = cost + xp;
			free_customize_swaps[customization.code] = (free_customize_swaps[customization.code] || 0) + choice.xp_delta;
		});
	});
	var myriad_madness = {};
	// first check for same named cards
	_.each(cards_added, function (addition) {
		_.each(cards_removed, function (removal) {
			var addition_xp = addition.card.xp;
			var removal_xp = removal.card.xp;
			if (typeof addition.card.real_text !== 'undefined' && addition.card.real_text.indexOf('Myriad.') !== -1) {
				addition.qty = 1;
				if (myriad_madness[addition.card.real_name]) {
					addition.qty = 0;
				}
				myriad_madness[addition.card.real_name] = 1;
			}
			if (typeof removal.card.real_text !== 'undefined' && removal.card.real_text.indexOf('Myriad.') !== -1) {
				removal.qty = 1;
			}
			if (addition.card.taboo_xp){
				addition_xp += addition.card.taboo_xp;
			}
			if (removal.card.taboo_xp){
				removal_xp += removal.card.taboo_xp;
			}
			if (addition.qty > 0 && removal.qty > 0 && addition_xp >= 0 && addition.card.real_name == removal.card.real_name && addition_xp > removal_xp){
				var upgraded_count = Math.min(addition.qty, removal.qty);
				addition.qty = addition.qty - removal.qty;
				if (spell_upgrade_discounts > 0 && removal.card.real_traits && removal.card.real_traits.indexOf('Spell.') !== -1 && addition.card.real_traits && addition.card.real_traits.indexOf('Spell.') !== -1) {
					// It's a spell card, and we have arcane research discounts remaining.
					var upgradeCost = ((addition_xp - removal_xp) * removal.qty)
					while (spell_upgrade_discounts > 0 && upgradeCost > 0) {
						upgradeCost--;
						spell_upgrade_discounts--;
					}
					for (var i = 0; i < upgraded_count; i++) {
						if (upgradeCost > 0 && upgrade_discounts > 0) {
							upgradeCost--;
							upgrade_discounts--;
						}
					}
					cost = cost + upgradeCost;
				} else {
					var upgradeCost = (addition_xp - removal_xp) * removal.qty;
					for (var i = 0; i < upgraded_count; i++) {
						if (upgradeCost > 0 && upgrade_discounts > 0) {
							upgradeCost--;
							upgrade_discounts--;
						}
					}
					cost = cost + upgradeCost;
				}
				if (addition.card.permanent && !removal.card.permanent) {
					free_0_cards += upgraded_count;
					removed_0_cards += upgraded_count;
				}
				removal.qty = Math.abs(addition.qty);
			}
		});
	});
	_.each(cards_removed, function (removal) {
		if (!app.deck.can_include_card(removal.card)){
			// Even though its not technically a L0 card, any 'invalid' card that was removed,
			// by our updated deck rules, can be replaced with an L0 for free.
			free_0_cards += removal.qty;
			removed_0_cards += removal.qty;
		} else if (removal.card.xp === 0){
			removed_0_cards += removal.qty;
		}
	});
	//console.log({ removed_0_cards, free_0_cards });

	myriad_madness = {};
	//console.log(removed_0_cards);
	// then pay for all changes
	_.each(cards_added, function (addition) {
		var addition_xp = addition.card.xp;
		if (typeof addition.card.real_text !== 'undefined' && addition.card.real_text.indexOf('Myriad.') !== -1) {
			addition.qty = 1;
			if (myriad_madness[addition.card.real_name]) {
				addition.qty = 0;
			}
			myriad_madness[addition.card.real_name] = 1;
		}
		if (addition.card.exceptional){
			addition_xp *= 2;
		}
		if (addition.card.taboo_xp){
			addition_xp += addition.card.taboo_xp;
		}
		if (deja_vu_exiled[addition.card.code] && deja_vu_discounts > 0){
			var discount_per_card = Math.min(
				addition_xp,
				deja_vu_max_discount
			);
			let deja_vu_cost = 0;
			// Handle cards one at a time, since each one needs to have a corresponding
			// exile and we still need to have 'uses' left.
			for (var i = 0; i < addition.qty; i++){
				if (deja_vu_exiled[addition.card.code]){
					deja_vu_exiled[addition.card.code]--;
					discount = Math.min(discount_per_card, deja_vu_discounts);
					deja_vu_discounts -= discount;
					deja_vu_cost += addition_xp - discount;
				} else {
					// Only get discount if we replaced something, and have uses left.
					deja_vu_cost += addition_xp;
				}
			}
			cost = cost + deja_vu_cost + dtr_xp;
			addition.qty = 0;
		} else if (addition_xp >= 0){
			while (addition.qty > 0 && addition.card.xp === 0 && (free_customize_swaps[addition.code] || 0) > 0) {
				// Still have to pay taboo XP and DTR_XP, but customizations lets you swap in cards for free
				// without paying min 1 cost, per the FAQ
				cost = cost + dtr_xp + addition_xp;
				addition.qty = addition.qty - 1;
			}
			if (addition.qty > 0 && addition.card.xp === 0 && removed_0_cards > 0 && free_0_cards > 0){
				// Account for taboo xp, which still must be paid when deck size grows.
				cost = cost + (addition.qty * addition_xp);
				free_0_cards -= addition.qty;
				removed_0_cards -= addition.qty;
				if (removed_0_cards < 0 || free_0_cards < 0){
					// I think this shouldn't be 1, since now Sled Dogs (4/non-myriad) card exists.
					// Should be Math.abs(free_0_cards) I believe.
					addition.qty = 1;
					cost = cost - (addition.qty * addition_xp);
				} else {
					addition.qty = 0;
				}
			}
			if (addition.card.indeck - addition.qty > 0 && addition.card.ignore) {
				addition.card.ignore = addition.card.ignore - (addition.card.indeck - addition.qty);
			}
			// Down the Rabbit Hole satisfiest the minimum 1 XP cost when swapping in cards.
			cost = cost + ((dtr_xp > 0 ? (dtr_xp + addition_xp) : Math.max(addition_xp, 1)) * (addition.qty - addition.card.ignore));
			addition.qty = 0;
		}
	});


	var add_list = [];
	var remove_list = [];
	var custom_list = [];
	var exile_list = [];
	// run through the changes and show them
	_.each(diff[0], function (qty, code) {
		var card = app.data.cards.findById(code);
		if(!card) return;
		add_list.push('+'+qty+' '+'<a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+app.format.xp(card.xp)+'</a>');
		//add_list.push('+'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+''+(card.xp >= 0 ? ' ('+card.xp+')' : '')+'</a>');
	});
	_.each(diff[1], function (qty, code) {
		var card = app.data.cards.findById(code);
		if(!card) return;
		remove_list.push('&minus;'+qty+' '+'<a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+app.format.xp(card.xp)+'</a>');
		//remove_list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+'</a>');
	});
	_.each(cards_customized, function(customization) {
		var card = customization.card;
		var lines = (card.customization_text || '').split('\n');
		if (card) {
			_.each(customization.choices, function (choice) {
				if (choice.xp_delta === 0) {
					return;
				}
				var index = choice.index;
				if (index >= card.customization_options.length) {
					return;
				}
				var option = card.customization_options[index];
				var line = lines[index];
				if (option && line) {
					var choice_name = line.replace(/.*?<b>/, '').replace(/<\/b>.*/, '');
					custom_list.push('↑'+' '+'<a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+app.format.xp(choice.xp_delta, 1, 'custom')+'</a>&nbsp;<b>' + choice_name + '</b>');
				}
			});
		}
	});
	_.each(cards_exiled, function (qty, code) {
		var card = app.data.cards.findById(code);
		if(!card) return;
		exile_list.push('&minus;'+qty+' '+'<a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+app.format.xp(card.xp)+'</a>');
		//remove_list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+'</a>');
	});
	if (cost && app.deck.get_previous_deck()){
		app.deck.set_xp_spent(cost)
	}
	node = $("#upgrade_changes");
	if(app.deck.get_previous_deck()){
		node.empty();
		node.append('<h4 class="deck-section">Progress</h4>');

		if (app.deck.get_xp_adjustment()){
			node.append('<div>Available experience: <span id="xp_up" class="fa fa-plus-circle xp_up"></span> '+app.deck.get_xp()+' <span id="xp_down" class="fa fa-minus-circle xp_down"></span> ('+app.deck.get_xp_adjustment()+')</div>');
		} else {
			node.append('<div>Available experience: <span id="xp_up" class="fa fa-plus-circle xp_up"></span> '+app.deck.get_xp()+' <span id="xp_down" class="fa fa-minus-circle xp_down"></span></div>');
		}

		node.append('<div>Spent experience: '+cost+'</div>');
		if (app.deck.get_previous_deck() && $('#save_form').length <= 0){
			node.append('<div><a href="'+Routing.generate('deck_view', {deck_id:app.deck.get_previous_deck()})+'">View Previous Deck</a></div>');
		}
		if (app.deck.get_next_deck()){
			node.append('<div><a href="'+Routing.generate('deck_view', {deck_id:app.deck.get_next_deck()})+'">View Next Deck</a></div>');
		}
		node.append('<h4 class="deck-section">Changes</h4>');
		if (add_list.length <= 0 && remove_list.length <= 0 && custom_list.length <= 0){
			node.append('<div class="deck-content">No Changes</div>');
		}else {
			node.append('<div class="deck-content"><div class="row"><div class="col-sm-6 col-print-6">'+add_list.join('<br>')+'</div><div class="col-sm-6 col-print-6">'+remove_list.join('<br>')+'</div><div class="col-sm-6 col-print-6">'+custom_list.join('<br>')+'</div></div></div>');
		}
		if (exile_list.length > 0){
			node.append('<b>Exiled Cards</b>');
			node.append('<div class="deck-content"><div class="row"><div class="col-sm-6 col-print-6">'+exile_list.join('<br>')+'</div></div></div>');
		}
	} else if (app.deck.get_next_deck()){
		if (app.deck.get_next_deck()){
			node.empty();
			node.append('<h4 class="deck-section">Progress</h4>');
			node.append('<div><a href="'+Routing.generate('deck_view', {deck_id:app.deck.get_next_deck()})+'">View Next Deck</a></div>');
		}
	}
	//console.log(result[1])
}

/**
 * @memberOf deck_history
 */
deck_history.autosave = function autosave() {

	// check if deck has been modified since last autosave
	if(!changed_since_last_autosave) return;

	// compute diff between last snapshot and current deck
	var last_snapshot = snapshots[snapshots.length-1].content;
	var last_meta = snapshots[snapshots.length-1].meta;
	var current_deck = app.deck.get_content();
	var current_meta = app.deck.meta || {};

	changed_since_last_autosave = false;

	var result = app.diff.compute_simple([current_deck, last_snapshot], [current_meta, last_meta]);
	if(!result) return;

	var diff = result[0];
	var diff_json = JSON.stringify(diff);
	if(diff_json == '[{},{},{},{}]') return;

	// send diff to autosave
	$('#tab-header-history').html("Autosave...");
	ajax_in_process = true;

	$.ajax(Routing.generate('deck_autosave'), {
		data: {
			diff: diff_json,
			meta: JSON.stringify(current_meta),
			deck_id: app.deck.get_id()
		},
		type: 'POST',
		success: function(data, textStatus, jqXHR) {
			deck_history.add_snapshot({
				datecreation: data,
				variation: diff,
				content: current_deck,
				is_saved: false,
				meta: current_meta,
			});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			changed_since_last_autosave = true;
		},
		complete: function () {
			$('#tab-header-history').html("History");
			ajax_in_process = false;
		}
	});

}

/**
 * @memberOf deck_history
 */
deck_history.autosave_interval = function autosave_interval() {
	// if we are in the process of an ajax autosave request, do nothing now
	if(ajax_in_process) return;

	// making sure we don't go into negatives
	if(timer < 0) timer = period;

	// update progressbar
	$(progressbar).css('width', (timer*100/period)+'%').attr('aria-valuenow', timer).find('span').text(timer+' seconds remaining.');

	// timer action
	if(timer === 0) {
		deck_history.autosave();
	}

	timer--;
}

function split_choices(choice) {
	if (typeof choice === 'string') {
		return choice.split(',');
	}
	return choice || [];
}

function decode_customization(choice) {
	if (!choice) {
		return null;
	}
	var parts = choice.split('|');
	var result = { index: parseInt(parts[0], 10), xp: parseInt(parts[1], 10) };
	if (parts.length > 2) {
		result.choice = parts[2];
	}
	return result;
}

/**
 * @memberOf deck_history
 */
deck_history.add_snapshot = function add_snapshot(snapshot) {
	snapshot.date_creation = snapshot.date_creation ? moment(snapshot.date_creation) : moment();
	snapshots.push(snapshot);

	var list = [];
	if(snapshot.variation) {
		_.each(snapshot.variation[0], function (qty, code) {
			var card = app.data.cards.findById(code);
			if(!card) return;
			list.push('+'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+'</a>');
		});
		_.each(snapshot.variation[1], function (qty, code) {
			var card = app.data.cards.findById(code);
			if(!card) return;
			list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+'</a>');
		});
		if (snapshot.variation[2]) {
			_.each(snapshot.variation[2], function (choices, code) {
				var card = app.data.cards.findById(code);
				if(!card || !card.customization_text) return;
				var lines = card.customization_text.split("\n");
				var previous_choices = _.map(
					split_choices((snapshot.variation[3] || {})[code] || ''),
					function(c) {
						return decode_customization(c);
					}
				);
				if (choices) {
					_.each(split_choices(choices), choice => {
						var current = decode_customization(choice);
						if (!current || current.index >= card.customization_options.length) {
							return;
						}
						var option = card.customization_options[current.index];
						var previous = _.find(previous_choices, function (p) {
							return !!p && p.index === current.index;
						});
						var xp = current.xp - (previous ? previous.xp : 0);
						var line = lines[current.index];
						if (option && line && xp > 0) {
							var choice_name = line.replace(/.*?<b>/, '').replace(/<\/b>.*/, '');
							list.push('↑ <a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+app.format.xp(xp, 1, 'custom')+'</a>&nbsp;<b>' + choice_name + '</b>');
						}
					});
				}
			});
		}

		if (snapshot.variation[3]) {
			_.each(snapshot.variation[3], function (choices, code) {
				var card = app.data.cards.findById(code);
				if(!card || !card.customization_text) return;
				var lines = card.customization_text.split("\n");
				var current_choices = _.map(
					split_choices((snapshot.variation[2] || {})[code] || ''),
					function(c) {
						return decode_customization(c);
					}
				);
				if (choices) {
					_.each(split_choices(choices), choice => {
						var previous = decode_customization(choice);
						if (!previous || previous.index >= card.customization_options.length) {
							return;
						}
						if (_.find(current_choices, function (c) {
							return !!c && c.index === previous.index;
						})) {
							// Already handled it in the above.
							return;
						}
						var xp = previous.xp;
						var option = card.customization_options[previous.index];
						var line = lines[previous.index];
						if (option && line && xp > 0) {
							var choice_name = line.replace(/.*?<b>/, '').replace(/<\/b>.*/, '');
							list.push('<span class="invalid-card">↑ '+'<a href="'+card.url+'" class="card card-tip invalid-card fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+app.format.xp(xp, 1, 'custom')+'</a>&nbsp;<b>' + choice_name + '</b></span>');
						}
					});
				}
			});
		}
	} else {
		list.push("First version");
	}

	tbody.prepend('<tr'+(snapshot.is_saved ? '' : ' class="warning"')+'><td>'+snapshot.date_creation.calendar()+(snapshot.is_saved ? '' : ' (unsaved)')+'</td><td>'+(snapshot.version || '')+'</td><td>'+list.join('<br>')+'</td><td><a role="button" href="#" data-index="'+(snapshots.length-1)+'"">Revert</a></td></tr>');

	timer = -1; // start autosave timer

}

/**
 * @memberOf deck_history
 */
deck_history.load_snapshot = function load_snapshot(event) {

	var index = $(this).data('index');
	var snapshot = snapshots[index];
	if(!snapshot) return;

	app.data.cards.find({}).forEach(function(card) {
		var indeck = 0;
		if (snapshot.content[card.code]) {
			indeck = snapshot.content[card.code];
		}
		app.data.cards.updateById(card.code, {
			indeck : indeck,
			customizations : [],
		});
	});
	if (snapshot.meta) {
		app.deck.meta = snapshot.meta
		app.deck.load_customizations(app.deck.meta);
	}

	app.ui.on_deck_modified();
	changed_since_last_autosave = true;

	// cancel event
	return false;

}

/**
 * @memberOf deck_history
 */
deck_history.notify_change = function notify_change() {
	changed_since_last_autosave = true;
}

deck_history.get_unsaved_edits = function get_unsaved_edits() {
	return _.filter(snapshots, function (snapshot) {
		return snapshot.is_saved === false;
	}).sort(function (a, b) {
		return a.date_creation - b.datecreation;
	});
}

deck_history.is_changed_since_last_autosave = function is_changed_since_last_autosave() {
	return changed_since_last_autosave;
}

deck_history.init = function init(data, previous_deck_meta)
{
	// console.log("ch ch changes", app.deck.get_content());
	snapshots_init = data;
	previous_meta = previous_deck_meta;
}

/**
 * @memberOf deck_history
 * @param container
 */
deck_history.setup = function setup_history(container)
{
	tbody = $(container).find('tbody');
	tbody.on('click', 'a[role=button]', deck_history.load_snapshot);
	// Needed to clear the table out since 'setup' gets called multiple times.
	tbody.empty();
	progressbar = $(container).find('.progress-bar');

	clock = setInterval(deck_history.autosave_interval, 1000);

	snapshots_init.forEach(function (snapshot) {
		if (!deck_history.base){
			deck_history.base = snapshot;
			deck_history.base.meta = previous_meta || snapshot.meta;
		}
		deck_history.add_snapshot(snapshot);
	});

	deck_history.all_changes();
}

})(app.deck_history = {}, jQuery);
