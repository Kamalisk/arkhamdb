(function app_draw_simulator(draw_simulator, $) {
	
var deck = null, initial_size = 0, draw_count = 0, container = null;

/**
 * @memberOf draw_simulator
 */
draw_simulator.reset = function reset() {
	if(container) container.empty();
	deck = null;
	initial_size = draw_count = 0;
	update_odds();
	check_draw_type();
	$('#draw-simulator-clear').attr('disabled', true);
};

/**
 * @memberOf draw_simulator
 */
draw_simulator.init = function init() {
	container = $('#table-draw-simulator-content');
	deck = [];
	check_draw_type();
	app.data.cards({indeck:{'gt':0},type_code:{'!is':'identity'}}).each(function (record) {
		for(var ex = 0; ex < record.indeck; ex++) {
			deck.push(record);
		}
	});
	initial_size = deck.length;
};

function update_odds() {
	for(var i=1; i<=3; i++) {
		var odd = hypergeometric.get_cumul(1, initial_size, i, draw_count);
		$('#draw-simulator-odds-'+i).text(Math.round(100*odd));
	}
}

function check_draw_type() {
	identity = app.data.cards({indeck:{'gt':0},type_code:{'is':'identity'}}).first();
	var special_button = $("#draw-simulator-special");
	var special_draw = false;
	switch (identity.code) {
		case "02083":
			special_draw = true;
			special_button.attr("data-draw-type", "andy");
			break;
		case "07029": // MaxX
			special_draw = true;
			special_button.attr("data-draw-type", "maxx");
			break;
		default:
			special_button.hide();
	}
	if (special_draw) {
		special_button.text(identity.name.split(":")[0]).attr("disabled",false).show();
	}
}

function do_draw(draw, draw_type) {
	for(var pick = 0; pick < draw && deck.length > 0; pick++) {
		var rand = Math.floor(Math.random() * deck.length);
		var spliced = deck.splice(rand, 1);
		var card = spliced[0];
		var card_element;
		if(card.imagesrc) {
			card_element = $('<img src="'+card.imagesrc+'" class="card-image">');
		} else {
			card_element = $('<div class="card-proxy"><div>'+card.name+'</div></div>');
		}
		switch (draw_type) {
			case "maxx":
				if ((pick + 1) % 3 != 0) {
					card_element.addClass("trashed");
				}
				break;
		}
		if ($("#draw-simulator-special").attr("data-draw-type") == "andy") {
			$("#draw-simulator-special").attr("disabled",true);
		}
		container.append(card_element);
		draw_count++;
	}
	update_odds();
}

/**
 * @memberOf draw_simulator
 */
draw_simulator.handle_click = function handle_click(event) {

	event.preventDefault();
	var draw_type = false;
	var id = $(this).attr('id');
	var command = id.substr(15);
	$('#draw-simulator-clear').attr('disabled', false);
	if(command === 'clear') {
		draw_simulator.reset();
		return;
	}
	if(event.shiftKey) {
		draw_simulator.reset();
	}
	if(deck === null) {
		draw_simulator.init();
	}
	var draw;
	if(command === 'all') {
		draw = deck.length;
	} else if(command === 'special') {
		draw_type = $(this).attr('data-draw-type');
		switch (draw_type) {
			case "maxx":
				draw = 3;
				break;
			case "andy":
				draw = 9;
				$(this).attr("disabled", true);
				break;
		}
	} else {
		draw = parseInt(command, 10);
	}
	if(isNaN(draw)) return;
	do_draw(draw, draw_type);

};

/**
 * @memberOf draw_simulator
 */
draw_simulator.toggle_opacity = function toggle_opacity(event) {
	$(this).css('opacity', 1.5 - parseFloat($(this).css('opacity')));
};

$(function () {
	$('#table-draw-simulator').on({click: draw_simulator.handle_click}, 'button.btn');
	$('#table-draw-simulator').on({click: draw_simulator.toggle_opacity}, 'img.card-image, div.card-proxy');
	app.draw_simulator.init();
});

})(app.draw_simulator = {}, jQuery);
