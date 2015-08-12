(function app_draw_simulator(draw_simulator, $) {

var deck = null,
	initial_size = 0,
	draw_count = 0,
	container = null;

/**
 * @memberOf draw_simulator
 */
draw_simulator.reset = function reset() {
	$(container).empty();
	draw_simulator.on_data_loaded();
	draw_count = 0;
	draw_simulator.update_odds();
	$('#draw-simulator-clear').prop('disabled', true);
};

/**
 * @memberOf draw_simulator
 */
draw_simulator.on_dom_loaded = function on_dom_loaded() {
	$('#table-draw-simulator').on('click', 'button.btn', draw_simulator.handle_click);
	$('#table-draw-simulator').on('click', 'img, div.card-proxy', draw_simulator.toggle_opacity);
	container = $('#table-draw-simulator-content');
}

/**
 * @memberOf draw_simulator
 */
draw_simulator.on_data_loaded = function on_data_loaded() {
	deck = [];

	var cards = app.deck.get_draw_deck();
	cards.forEach(function (card) {
		for(var ex = 0; ex < card.indeck; ex++) {
			deck.push(card);
		}
	});
	initial_size = deck.length;
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
 * @param draw integer
 */
draw_simulator.do_draw = function do_draw(draw) {
	for(var pick = 0; pick < draw && deck.length > 0; pick++) {
		var rand = Math.floor(Math.random() * deck.length);
		var spliced = deck.splice(rand, 1);
		var card = spliced[0];
		var card_element;
		if(card.imagesrc) {
			card_element = $('<img src="'+card.imagesrc+'">');
		} else {
			card_element = $('<div class="card-proxy"><div>'+card.name+'</div></div>');
		}
		container.append(card_element);
		draw_count++;
	}
	draw_simulator.update_odds();
}

/**
 * @memberOf draw_simulator
 */
draw_simulator.handle_click = function handle_click(event) {

	event.preventDefault();

	var command = $(this).data('command');
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
	draw_simulator.do_draw(draw);

};

/**
 * @memberOf draw_simulator
 */
draw_simulator.toggle_opacity = function toggle_opacity(event) {
	$(this).css('opacity', 1.5 - parseFloat($(this).css('opacity')));
};

})(app.draw_simulator = {}, jQuery);
