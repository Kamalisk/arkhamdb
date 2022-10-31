(function app_deck_upgrades(deck_upgrades, $) {

var tbody,
	upgrades = [],
	xp_spent = 0,
	edit_mode = false


/**
 * @memberOf deck_upgrades
 */
deck_upgrades.display = function display() {

	// no upgrades
	if (upgrades.length <= 0){
		return;
	}

	// put relevent data into deck object based on current deck
	var current_deck = {};
	current_deck.content = app.deck.get_content();
	current_deck.exile_string = app.deck.get_exile_string();

	var last_deck = current_deck;
	$("#upgrade_changes").empty();
	var counter = upgrades.length;
	$("#upgrade_changes").append('<h4 class="deck-section>History</h4>');
	_.each(upgrades, function (deck) {
		//console.log(last_deck, deck.content);
		var result = app.diff.compute_simple([last_deck.content, deck.content], [last_deck.meta, deck.meta]);
		if(!result) return;
		var free_0_cards = 0;
		var removed_0_cards = 0;
		var diff = result[0];
		var cards_removed = [];
		var cards_added = [];
		var cards_exiled = {};
		var cards_customized = [];
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
		_.each(result[2], function(choices, code) {
			var card = app.data.cards.findById(code);
			var card_customization = {
				"choices": choices,
				"code": code,
				"card": card,
			};
			cards_customized.push(card_customization);
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

		if (last_deck.exile_string){
			free_0_cards += last_deck.exile_string.split(",").length;
			removed_0_cards = last_deck.exile_string.split(",").length;
			_.each(last_deck.exile_string.split(","), function (code, id) {
				if (cards_exiled[code]){
					cards_exiled[code] = 2;
					deja_vu_exiled[code] = 2;
				} else {
					cards_exiled[code] = 1;
					deja_vu_exiled[code] = 1;
				}
			});
		}

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
				if (removal.card.real_text.indexOf('Myriad.') !== -1) {
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
						if (upgradeCost > 0 && upgrade_discounts > 0) {
							upgradeCost--;
							upgrade_discounts--;
						}
						cost = cost + upgradeCost;
					} else {
						var upgradeCost = ((addition_xp - removal_xp) * removal.qty)
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
				if (removal.card.xp === 0){
					removed_0_cards += removal.qty;
				}
			});
		});

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
			cost = cost + ((dtr_xp + Math.max(addition_xp, 1)) * (addition.qty - addition.card.ignore));
			addition.qty = 0;
		}
	});

		var add_list = [];
		var custom_list = [];
		var remove_list = [];
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
			if (cards_exiled[code]){
				qty = qty - cards_exiled[code];
				if (qty <= 0){
					return;
				}
			}
			remove_list.push('&minus;'+qty+' '+'<a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+(app.format.xp(card.xp))+'</a>');
			//remove_list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+'</a>');
		});
		_.each(cards_customized, function(customization) {
			var card = customization.card;
			var lines = (card.customization_text || '').split('\n');
			if (card) {
				_.each(customization.choices, function (choice) {
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
			exile_list.push('&minus;'+qty+' '+'<a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+(app.format.xp(card.xp))+'</a>');
			//remove_list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+'</a>');
		});

		if (cost){
			app.deck.set_xp_spent(cost)
		}

		var div = $('<div class="deck-upgrade-changes">');
		if (edit_mode){
			div.append('<h4 class="deck-section">Progress</h4>');

			div.append('<div>Available experience: '+app.deck.get_xp()+' <span class="fa fa-plus-circle"></span><span class="fa fa-minus-circle"></span></div>');
			div.append('<div>Spent experience: '+cost+'</div>');
			if (app.deck.get_previous_deck() && $('#save_form').length <= 0){
				div.append('<div><a href="'+Routing.generate('deck_view', {deck_id:app.deck.get_previous_deck()})+'">View Previous Deck</a></div>');
			}
			if (app.deck.get_next_deck()){
				div.append('<div><a href="'+Routing.generate('deck_view', {deck_id:app.deck.get_next_deck()})+'">View Next Deck</a></div>');
			}
		}

		if (deck.xp_adjustment){
			div.append('<h5>Scenario '+counter+' complete: '+deck.xp+' xp spent. '+deck.xp_left+' xp remaining ('+deck.xp_adjustment+')</h5>');
		} else {
			div.append('<h5>Scenario '+counter+' complete: '+deck.xp+' xp spent. '+deck.xp_left+' xp remaining</h5>');
		}

		if (add_list.length <= 0 && remove_list.length <= 0 && custom_list.length <= 0){
			div.append('<div class="deck-content">No Changes</div>');
		}else {
			div.append('<div class="deck-content"><div class="row"><div class="col-sm-6 col-print-6">'+add_list.join('<br>')+'</div><div class="col-sm-6 col-print-6">'+remove_list.join('<br>')+'</div><div class="col-sm-6 col-print-6">'+custom_list.join('<br>')+'</div></div></div>');
		}

		if (exile_list.length > 0){
			div.append('<b>Exiled Cards</b>');
			div.append('<div class="deck-content"><div class="row"><div class="col-sm-6 col-print-6">'+exile_list.join('<br>')+'</div></div></div>');
		}
		div.append('<hr>');
		$("#upgrade_changes").append(div);
		last_deck = deck;
		counter--;
	});
}

deck_upgrades.init = function init(data)
{
	// console.log("ch ch changes", app.deck.get_content());
	upgrades = data;
}

/**
 * @memberOf deck_history
 * @param container
 */
deck_upgrades.setup = function setup_upgrades()
{
	deck_upgrades.display();
}

})(app.deck_upgrades = {}, jQuery);
