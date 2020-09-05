(function app_draw_simulator(draw_simulator, $) {

var deck = [],
	hand = [],
	initial_size = 0,
	draw_count = 0,
	container = null;


/**
 * @memberOf draw_simulator
 */
draw_simulator.on_data_loaded = function on_data_loaded() {
	draw_simulator.init();
}

/**
 * @memberOf draw_simulator
 */
draw_simulator.on_dom_loaded = function on_dom_loaded() {
	container = $('#table-draw-simulator-content');
	$('#table-draw-simulator').on('click', 'button.btn', draw_simulator.handle_click);
	$('#table-draw-simulator').on('click', 'div.simulator-hand-card', draw_simulator.select_card);
	$('#oddsModal').on({change: draw_simulator.compute_odds}, 'input');
}

draw_simulator.select_card = function select_card(event) {
	var index = $(this).attr('data-hand-id');
	if (hand[index]){
		if (hand[index].selected){
			hand[index].selected = false;
		} else {
			hand[index].selected = true;
		}
	}
	draw_simulator.render();
}

/**
 * @memberOf draw_simulator
 */
draw_simulator.init = function init() {
	deck = [];
	hand = [];
	draw_count = 0;
	var cards = app.deck.get_real_draw_deck();
	cards.forEach(function (card) {
		for(var ex = 0; ex < card.indeck; ex++) {
			if (card.name == "Duke" || card.permanent){
				return;
			}
			var new_card = {};
			new_card.data = card;
			deck.push(new_card);
		}
	});
	initial_size = deck.length;
	draw_simulator.render();
}


// store the deck and hand in an object, and just use this to draw the cards
draw_simulator.render = function() {
	$(container).empty();
	$.each(hand, function(key, card){
		if (card.data){
			var card_element = $('<div data-code="'+card.data.code+'" class="card card-tip simulator-hand-card" data-hand-id="'+(key)+'" data-type="'+card.data.type_code+'" data-subtype="'+card.data.subtype_code+'"></div>');
			if (card.selected){
				card_element.css('opacity', 0.6);
				$('[data-command=redraw]').prop('disabled', false);
				$('[data-command=reshuffle]').prop('disabled', false);
			}
			if(card.data.imagesrc) {
				card_element.append('<img src="'+card.data.imagesrc+'">');
			} else {
				card_element.append('<div class="card-proxy bg-'+card.data.faction_code+'">'+card.data.name+'</div>');
			}
			container.append(card_element);
			if (card.data.subtype_code && (card.data.subtype_code == "weakness" || card.data.subtype_code == "basicweakness") ){
				//$('[data-command=redraw]').prop('disabled', false);
			}
		}
	});
	draw_simulator.update_odds();
}

/**
 * @memberOf draw_simulator
 * @param draw integer
 */
draw_simulator.draw = function draw(qty) {
	for(var pick = 0; pick < qty && deck.length > 0; pick++) {
		var rand = Math.floor(Math.random() * deck.length);
		var spliced = deck.splice(rand, 1);
		var card = spliced[0];
		hand.push(card);
		draw_count++;
	}
	draw_simulator.render();
}


/**
 * @memberOf draw_simulator
 */
draw_simulator.reset = function reset() {
	draw_simulator.init();
};


/**
 * @memberOf draw_simulator
 */
draw_simulator.reshuffle = function reshuffle(redraw) {
	$('[data-command=redraw]').prop('disabled', true);
	$('[data-command=reshuffle]').prop('disabled', true);
	
	var count = 0;
	var keys_to_clear = [];
	$.each(hand, function(key, value){
		//if (value && (value.subtype_code == "weakness" || value.subtype_code == "basicweakness")){
		if (value.selected){
			value.selected = false;
			deck.push(value);
			keys_to_clear.push(key);
			count++;
			draw_count--;
		}
	});
	keys_to_clear = keys_to_clear.reverse();
	$.each(keys_to_clear, function(key, value){
		var spliced = hand.splice(value, 1);
	});
	draw_simulator.shuffle_deck(deck);
	if (redraw) {
		draw_simulator.draw(count);
	} else {
		draw_simulator.render();
	}
};


/**
 * Randomize array element order in-place.
 * Using Durstenfeld shuffle algorithm.
 */
draw_simulator.shuffle_deck = function(array) {
    for (var i = array.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var temp = array[i];
        array[i] = array[j];
        array[j] = temp;
    }
    return array;
}


/**
 * @memberOf draw_simulator
 */
draw_simulator.compute_odds = function compute_odds() {
	var inputs = {};
	$.each(['N','K','n','k'], function (i, key) {
		inputs[key] = parseInt($('#odds-calculator-'+key).val(), 10) || 0;
	});
	$('#odds-calculator-p').text( Math.round( 100 * app.hypergeometric.get_cumul(inputs.k, inputs.N, inputs.K, inputs.n) ) );
}



/**
 * @memberOf draw_simulator
 */
draw_simulator.update_odds = function update_odds() {
	for(var i=1; i<=3; i++) {
		var odd = app.hypergeometric.get_cumul(1, initial_size, i, draw_count);
		$('#draw-simulator-odds-'+i).text(Math.round(100*odd));
	}
}



/**
 * @memberOf draw_simulator
 */
draw_simulator.handle_click = function handle_click(event) {

	event.preventDefault();

	var command = $(this).data('command');
	if(command === 'redraw') {
		draw_simulator.reshuffle(true);
		return;
	}
	
	if(command === 'reshuffle') {
		draw_simulator.reshuffle(false);
		return;
	}
	
	$('[data-command=clear]').prop('disabled', false);
	if(command === 'clear') {
		draw_simulator.reset();
		return;
	}
	
	if(event.shiftKey) {
		draw_simulator.reset();
	}
	var draw;
	if(command === 'all') {
		draw = deck.length;
	} else {
		draw = command;
	}

	if(isNaN(draw)) return;
	draw_simulator.draw(draw);

};

})(app.draw_simulator = {}, jQuery);
