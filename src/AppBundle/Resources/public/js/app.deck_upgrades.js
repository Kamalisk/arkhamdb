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
		var result = app.diff.compute_simple([last_deck.content, deck.content]);
		if(!result) return;
		
		var diff = result[0];
		var cards_removed = [];
		var cards_added = [];
		var cards_exiled = {};
		var myriad_buys = {};
		var cost = 0;
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
		});
				
		// find arcane research
		var arcane_research = app.data.cards.findById("04109");
		var spell_upgrade_discounts = 0;
		if (arcane_research && arcane_research.indeck) {
			spell_upgrade_discounts += arcane_research.indeck;
		}

		// find adaptable
		var adaptable = app.data.cards.findById("02110");
		var free_0_cards = 0;
		var removed_0_cards = 0;
		if (adaptable && adaptable.indeck){
			free_0_cards += 2 * adaptable.indeck;
		}

		if (last_deck.exile_string){
			free_0_cards += last_deck.exile_string.split(",").length;
			removed_0_cards = last_deck.exile_string.split(",").length;
			_.each(last_deck.exile_string.split(","), function (code, id) {
				if (cards_exiled[code]){
					cards_exiled[code] = 2;
				} else {
					cards_exiled[code] = 1;
				}
			});
		}
		
		// first check for same named cards
		_.each(cards_added, function (addition) {
			const myriad = addition.card.real_text.indexOf('Myriad.') !== -1;
			_.each(cards_removed, function (removal) {
				if (addition.qty > 0 && removal.qty > 0 && addition.card.xp >= 0 && addition.card.real_name == removal.card.real_name && addition.card.xp > removal.card.xp){
					addition.qty = addition.qty - removal.qty;
					if (spell_upgrade_discounts > 0 && removal.card.real_traits && removal.card.real_traits.indexOf('Spell.') !== -1 && addition.card.real_traits && addition.card.real_traits.indexOf('Spell.') !== -1) {
						// It's a spell card, and we have arcane research discounts remaining.
						var upgradeCost = ((addition.card.xp - removal.card.xp) * (myriad ? 1 : removal.qty));
						if (myriad) {
							myriad_buys[addition.card.real_name] = true;
						}
						while (spell_upgrade_discounts > 0 && upgradeCost > 0) {
							upgradeCost--;
							spell_upgrade_discounts--;
						}
						cost = cost + upgradeCost;
					} else {
						cost = cost + ((addition.card.xp - removal.card.xp) * (myriad ? 1 : removal.qty));
					}
					removal.qty = Math.abs(addition.qty);
					if (myriad) {
						myriad_buys[addition.card.real_name] = true;
					}
				}
				if (removal.card.xp === 0){
					removed_0_cards += removal.qty;
				}
			});
		});
		
		//console.log(last_deck, free_0_cards, removed_0_cards);
		// then pay for all changes
		_.each(cards_added, function (addition) {
			const myriad = addition.card.real_text.indexOf('Myriad.') !== -1;
			if (myriad) {
				if (myriad_buys[addition.card.real_name]) {
					addition.qty = 0;
				}
				myriad_buys[addition.card.real_name] = true;
			}
			if (addition.card.xp >= 0) {
				//console.log("CARD", 		addition);
				if (addition.card.xp === 0 && removed_0_cards > 0 && free_0_cards > 0){
					//Update this loop to work with more than 1 copy of card.
					while (removed_0_cards > 0 && free_0_cards > 0 && addition.qty > 0){
						removed_0_cards--;
						free_0_cards--;
						addition.qty--;
						if (myriad) {
							// Only pay for first one with myriad, even with adaptable.
							addition.qty = 0;
							break;
						}
					}
				}
				
				cost = cost + (Math.max(addition.card.xp * (addition.card.exceptional ? 2: 1), 1) * (addition.qty > 0 && myriad ? 1 : addition.qty));
				addition.qty = 0;
			}
		});
		
		var add_list = [];
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
		
		if (add_list.length <= 0 && remove_list.length <= 0){
			div.append('<div class="deck-content">No Changes</div>');
		}else {
			div.append('<div class="deck-content"><div class="row"><div class="col-sm-6 col-print-6">'+add_list.join('<br>')+'</div><div class="col-sm-6 col-print-6">'+remove_list.join('<br>')+'</div></div></div>');	
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
