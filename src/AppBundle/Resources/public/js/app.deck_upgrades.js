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
	
	var current_deck = {};
	current_deck.content = app.deck.get_content();
	var last_deck = current_deck;
	$("#upgrade_changes").empty();
	var counter = upgrades.length;
	$("#upgrade_changes").append('<h4>History</h4>');
	_.each(upgrades, function (deck) {		
		console.log(last_deck, deck.content);
		var result = app.diff.compute_simple([last_deck.content, deck.content]);
		if(!result) return;
		
		var diff = result[0];
		var cards_removed = [];
		var cards_added = [];
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
		// first check for same named cards
		_.each(cards_added, function (addition) {
			_.each(cards_removed, function (removal) {
				if (addition.qty > 0 && removal.qty > 0 && addition.card.xp >= 0 && addition.card.name == removal.card.name && addition.card.xp > removal.card.xp){
					addition.qty = addition.qty - removal.qty;				
					cost = cost + ((addition.card.xp - removal.card.xp) * removal.qty);
					removal.qty = Math.abs(addition.qty);
				}
			});
		});
		// first check for same named cards
		_.each(cards_added, function (addition) {
			if (addition.card.xp >= 0){			
				cost = cost + (Math.max(addition.card.xp, 1) * addition.qty);
				addition.qty = 0;
			}
		});
		
		var add_list = [];
		var remove_list = [];
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
			remove_list.push('&minus;'+qty+' '+'<a href="'+card.url+'" class="card card-tip fg-'+card.faction_code+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-code="'+card.code+'">'+card.name+'</a>'+(card.xp >= 0 ? "•".repeat(card.xp) : '')+'</a>');
			//remove_list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card-tip" data-code="'+code+'">'+card.name+'</a>');
		});
		
		if (cost){
			app.deck.set_xp_spent(cost)
		}
		
		if (edit_mode){
			$("#upgrade_changes").append('<h4>Progress</h4>');
			$("#upgrade_changes").append('<div>Available experience: '+app.deck.get_xp()+'. Spent experience: '+cost+'</div>');
			if (app.deck.get_previous_deck() && $('#save_form').length <= 0){
				$("#upgrade_changes").append('<div><a href="'+Routing.generate('deck_view', {deck_id:app.deck.get_previous_deck()})+'">View Previous Deck</a></div>');
			}
			if (app.deck.get_next_deck()){
				$("#upgrade_changes").append('<div><a href="'+Routing.generate('deck_view', {deck_id:app.deck.get_next_deck()})+'">View Next Deck</a></div>');
			}
		}
		
		if (deck.xp){
			$("#upgrade_changes").append('<h5>Scenario '+counter+' complete - '+deck.xp+' xp spent. '+deck.xp_left+' xp remaining</h5>');
		} else {
			$("#upgrade_changes").append('<h5>Scenario '+counter+' complete - 0 xp spent.</h5>');	
		}
		
		if (add_list.length <= 0 && remove_list.length <= 0){
			$("#upgrade_changes").append('<div class="deck-content">No Changes</div>');
		}else {
			$("#upgrade_changes").append('<div class="deck-content"><div class="row"><div class="col-sm-6 col-print-6">'+add_list.join('<br>')+'</div><div class="col-sm-6 col-print-6">'+remove_list.join('<br>')+'</div></div></div>');	
		}
		$("#upgrade_changes").append('<hr>');
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
