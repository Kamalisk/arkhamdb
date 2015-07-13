(function ui_deck(ui, $) {

var DisplayColumns = 1,
	DisplayColumnsTpl = '',
	CoreSets = 1,
	Buttons_Behavior = 'cumulative',
	SortKey = 'type_code',
	SortOrder = 1,
	CardDivs = [[],[],[]],
	ShowOnlyDeck  = false,
	HideDisabled = false;

/**
 * reads ui configuration from localStorage
 * @memberOf ui
 */
ui.read_from_storage = function read_from_storage() {
	var localStorageDisplayColumns;
	if (localStorage
			&& (localStorageDisplayColumns = parseInt(localStorage
					.getItem('display_columns'), 10)) !== null
			&& [ 1, 2, 3 ].indexOf(localStorageDisplayColumns) > -1) {
		DisplayColumns = localStorageDisplayColumns;
	}

	var localStorageCoreSets;
	if (localStorage
			&& (localStorageCoreSets = parseInt(localStorage
					.getItem('core_sets'), 10)) !== null
			&& [ 1, 2, 3 ].indexOf(localStorageCoreSets) > -1) {
		CoreSets = localStorageCoreSets;
	}

	if(app.suggestions) {
		var localStorageSuggestions;
		if (localStorage
				&& (localStorageSuggestions = parseInt(localStorage
						.getItem('show_suggestions'), 10)) !== null
				&& [ 0, 3, 10 ].indexOf(localStorageSuggestions) > -1) {
			app.suggestions.number = localStorageSuggestions;
		}
	}

	var localStorageButtonsBehavior;
	if (localStorage
			&& (localStorageButtonsBehavior = localStorage.getItem('buttons_behavior')) !== null
			&& [ 'cumulative', 'exclusive' ].indexOf(localStorageButtonsBehavior) > -1) {
		Buttons_Behavior = localStorageButtonsBehavior;
	}
}

/**
 * inits the state of config buttons
 * @memberOf ui
 */
ui.init_config_buttons = function init_config_buttons() {
	$('input[name=display-column-' + DisplayColumns + ']').prop('checked', true);
	$('input[name=core-set-' + CoreSets + ']').prop('checked', true);
	if(app.suggestions) $('input[name=show-suggestions-' + app.suggestions.number + ']').prop('checked', true);
	$('input[name=buttons-behavior-' + Buttons_Behavior + ']').prop('checked', true);
}

/**
 * removes titles, which cannot be used in decks
 * @memberOf ui
 */
ui.remove_melee_titles = function remove_melee_titles() {
	app.data.cards.remove({
		'type_code': 'title'
	});
}

/**
 * sets the maxqty of each card
 * @memberOf ui
 */
ui.set_max_qty = function set_max_qty() {
	app.data.cards.find().forEach(function(record) {
		var max_qty = 3;
		if (record.set_code == 'core')
			max_qty = Math.min(record.quantity * CoreSets, 3);
		if (record.type_code == "plot")
			max_qty = record.plotLimit;
		if (record.type_code == "agenda")
			max_qty = 1;
		app.data.cards.updateById(record.code, {
			maxqty : max_qty
		});
	});
}

/**
 * builds the faction selector
 * @memberOf ui
 */
ui.build_faction_selector = function build_faction_selector() {
	/*
	 $('[data-filter=faction_code]').empty();
	$.each(app.data.cards().distinct("faction_code").sort(
			function(a, b) {
				return b === "neutral" ? -1 : a === "neutral" ? 1 : a < b ? -1
						: a > b ? 1 : 0;
			}), function(index, record) {
		var example = app.data.cards({"faction_code": record}).first();
		var faction = example.faction;
		var label = $('<label class="btn btn-default btn-sm" data-code="' + record
				+ '" title="'+faction+'"><input type="checkbox" name="' + record
				+ '"><img src="'
				+ Url_FactionImage.replace('xxx', record)
				+ '" style="height:12px" alt="'+record+'"></label>');
		if(Modernizr.touch) {
			label.append(' '+faction);
			label.addClass('btn-block');
		} else {
			label.tooltip({container: 'body'});
		}
		$('[data-filter=faction_code]').append(label);
	});
	$('[data-filter=faction_code]').button();
	$('[data-filter=faction_code]').children('label[data-code='+Identity.faction_code+']').each(function(index, elt) {
		$(elt).button('toggle');
	});
	 */
}

/**
 * builds the type selector
 * @memberOf ui
 */
ui.build_type_selector = function build_type_selector() {
	/*
	 $('[data-filter=type_code]').empty();
	$.each(app.data.cards().distinct("type_code").sort(), function(index, record) {
		var example = app.data.cards({"type_code": record}).first();
		var label = $('<label class="btn btn-default btn-sm" data-code="'
				+ record + '" title="'+example.type+'"><input type="checkbox" name="' + record
				+ '"><img src="' + Url_TypeImage.replace('xxx', record)
				+ '" style="height:12px" alt="'+record+'"></label>');
		if(Modernizr.touch) {
			label.append(' '+example.type);
			label.addClass('btn-block');
		} else {
			label.tooltip({container: 'body'});
		}
		$('[data-filter=type_code]').append(label);
	});
	$('[data-filter=type_code]').button();
	$('[data-filter=type_code]').children('label:first-child').each(function(index, elt) {
		$(elt).button('toggle');
	});
	 */
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_click_filter = function on_click_filter(event) {
	var dropdown = $(this).closest('ul').hasClass('dropdown-menu');
	if (dropdown) {
		if (event.shiftKey) {
			if (!event.altKey) {
				uncheck_all_others.call(this);
			} else {
				check_all_others.call(this);
			}
		}
		event.stopPropagation();
	} else {
		if (!event.shiftKey && Buttons_Behavior === 'exclusive' || event.shiftKey && Buttons_Behavior === 'cumulative') {
			if (!event.altKey) {
				uncheck_all_active.call(this);
			} else {
				check_all_inactive.call(this);
			}
		}
	}
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_input_smartfilter = function on_input_smartfilter(event) {
	var q = $(this).val();
	if(q.match(/^\w[:<>!]/)) app.smart_filter.handler(q);
	else app.smart_filter.handler('');
	ui.refresh_list();
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_submit_form = function on_submit_form(event) {
	var deck_json = JSON.stringify(get_deck_content());
	$('input[name=content]').val(deck_json);
	$('input[name=description]').val($('textarea[name=description_]').val());
	$('input[name=tags]').val($('input[name=tags_]').val());
}

/**
 * sets up event handlers ; dataloaded not fired yet
 * @memberOf ui
 */
ui.setup_event_handlers = function setup_event_handlers() {

	$('[data-filter]').on({
		change : ui.refresh_list,
		click : ui.on_click_filter
	}, 'label');

	$('#filter-text').on('input', ui.on_input_smartfilter);

	$('#save_form').on('submit', ui.on_submit_form);

	$('#btn-save-as-copy').on('click', function(event) {
		$('#deck-save-as-copy').val(1);
	});
	
	$('#btn-cancel-edits').on('click', function(event) {
		var edits = $.grep(Snapshots, function (snapshot) {
			return snapshot.saved === false;
		});
		if(edits.length) {
			var confirmation = confirm("This operation will revert the changes made to the deck since "+edits[edits.length-1].date_creation.calendar()+". The last "+(edits.length > 1 ? edits.length+" edits" : "edit")+" will be lost. Do you confirm?");
			if(!confirmation) return false;
		}
		$('#deck-cancel-edits').val(1);
	});

	$('#collection').on('change', 'input[type=radio]', ui.on_list_quantity_change);
	$('#cardModal').on('change', 'input[type=radio]', ui.on_modal_quantity_change);
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_list_quantity_change = function on_list_quantity_change(event) {
	var row = $(this).closest('.card-container');
	var code = row.data('code');
	var quantity = parseInt($(this).val(), 10);
	row[quantity ? "addClass" : "removeClass"]('in-deck');
	ui.on_quantity_change(code, quantity);
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_modal_quantity_change = function on_modal_quantity_change(event) {
	var modal = $(this).closest('div.modal');
	var code =  modal.data('code');
	var quantity = parseInt($(this).val(), 10);
	modal.modal('hide');
	ui.on_quantity_change(code, quantity);
}

/**
 * @memberOf ui
 */
ui.on_quantity_change = function on_quantity_change(code, quantity) {
	
	app.deck.set_card_copies(code, quantity);
	ui.refresh_deck();

	// for each set of divs (1, 2, 3 columns)
	$.each(CardDivs, function(nbcols, rows) {
		var row = rows[code];
		if(!row) return;
		
		// rows[code] is the card row of our card
		// for each "quantity switch" on that row
		rows[code].find('input[name="qty-' + code + '"]').each(function(i, element) {
			
			// if that switch is NOT the one with the new quantity, uncheck it
			// else, check it
			if($(element).val() != quantity) {
				$(element).prop('checked', false).closest('label').removeClass('active');
			} else {
				$(element).prop('checked', true).closest('label').addClass('active');
			}
		});
	});

}


/**
 * builds the pack selector
 * @memberOf ui
 */
ui.build_pack_selector = function build_pack_selector() {
	$('[data-filter=pack_code]').empty();
	app.data.sets.find({
		name: {
			'$exists': true
		}
	}).forEach(function(record) {
		// checked or unchecked ? checked by default
		var checked = true;
		// if not yet available, uncheck pack
		if(record.available === "") checked = false;
		// if user checked it previously, check pack
		if(localStorage && localStorage.getItem('set_code_' + record.code) !== null) checked = true;
		// if pack used by cards in deck, check pack
		var cards = app.data.cards.find({
			pack_code: record.code, 
			indeck: {
				'$gt': 0
			}
		});
		if(cards.length) checked = true;

		$('<li><a href="#"><label><input type="checkbox" name="' + record.code + '"' + (checked ? ' checked="checked"' : '') + '>' + record.name + '</label></a></li>').appendTo('[data-filter=pack_code]');
	});
}

/**
 * returns the current card filters as an array
 * @memberOf ui
 */
ui.get_filters = function get_filters() {
	var filters = [];
	$('[data-filter]').each(
		function(index, div) {
			var columnName = $(div).data('filter');
			var arr = [];
			$(div).find("input[type=checkbox]").each(
				function(index, elt) {
					if($(elt).prop('checked')) arr.push($(elt).attr('name'));
				}
			);
			if(arr.length) {
				filters[columnName] = {
					'$in': arr
				};
			}
		}
	);
	return filters;
}

/**
 * updates internal variables when display columns change
 * @memberOf ui
 */
ui.update_list_template = function update_list_template() {
	switch (DisplayColumns) {
	case 1:
		DisplayColumnsTpl = _.template(
			'<tr>'
				+ '<td><div class="btn-group" data-toggle="buttons"><%= radios %></div></td>'
				+ '<td><a class="card card-tooltip" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a></td>'
				+ '<td><%= card.type_name %></td>'
				+ '<td><%= card.faction_name %></td>'
			+ '</tr>'
		);
		break;
	case 2:
		DisplayColumnsTpl = _.template(
			'<div class="col-sm-6">'
				+ '<div class="media">'
					+ '<div class="media-left"><img class="media-object" src="/bundles/cards/<%= card.code %>.png" alt="<%= card.name %>"></div>'
					+ '<div class="media-body">'
						+ '<h4 class="media-heading"><a class="card" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a></h4>'
						+ '<div class="btn-group" data-toggle="buttons"><%= radios %></div>' 
					+ '</div>' 
				+ '</div>' 
			+ '</div>'
		);
		break;
	case 3:
		DisplayColumnsTpl = _.template(
			'<div class="col-sm-4">'
				+ '<div class="media">'
					+ '<div class="media-left"><img class="media-object" src="/bundles/cards/<%= card.code %>.png" alt="<%= card.name %>"></div>'
					+ '<div class="media-body">'
						+ '<h5 class="media-heading"><a class="card" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal">>%= card.name %></a></h5>'
						+ '<div class="btn-group" data-toggle="buttons"><%= radios %></div>'
					+ '</div>' 
				+ '</div>' 
			+ '</div>'
		);
	}
}

/**
 * builds a row for the list of available cards
 * @memberOf ui
 */
ui.build_row = function build_row(card) {
	var radios = '', radioTpl = _.template(
		'<label class="btn btn-xs btn-default <%= active %>"><input type="radio" name="qty-<% card.code %>" value="<%= i %>"><%= i %></label>'
	);
	
	for (var i = 0; i <= card.maxqty; i++) {
		radios += radioTpl({
			i: i,
			active: (i == card.indeck ? ' active' : ''),
			card: card
		});
	}

	var html = DisplayColumnsTpl({
		radios: radios,
		url: Routing.generate('cards_zoom', {card_code:card.code}),
		card: card
	});
	return $(html);
}

/**
 * destroys and rebuilds the list of available cards
 * don't fire unless 250ms has passed since last invocation
 * @memberOf ui
 */
ui.refresh_list = _.debounce(function refresh_list() {
	$('#collection-table').empty();
	$('#collection-grid').empty();
	
	var counter = 0,
		container = $('#collection-table'),
		filters = ui.get_filters(),
		query = app.smart_filter.get_query(filters),
		orderBy = {};
	
	orderBy[SortKey] = SortOrder;
	orderBy['name'] = 1;
	var cards = app.data.cards.find(query, {'$orderBy': orderBy});

	cards.forEach(function (card) {
		if (ShowOnlyDeck && !card.indeck) return;
		var unusable = !app.deck.can_include_card(card);
		if (HideDisabled && unusable) return;

		var row = CardDivs[DisplayColumns][card.code];
		if(!row) row = CardDivs[DisplayColumns][card.code] = ui.build_row(card);
		
		row.data("code", card.code).addClass('card-container');
		
		row.find('input[name="qty-' + card.code + '"]').each(
			function(i, element) {
				if($(element).val() == card.indeck) {
					$(element).prop('checked', true).closest('label').addClass('active');
				} else {
					$(element).prop('checked', false).closest('label').removeClass('active');
				}
			}
		);

		if (unusable) {
			row.find('label').addClass("disabled").find('input[type=radio]').attr("disabled", true);
		}

		if (DisplayColumns > 1 && (counter % DisplayColumns === 0)) {
			container = $('<div class="row"></div>').appendTo($('#collection-grid'));
		}
		
		container.append(row);
		counter++;
	});
}, 250);

/**
 * @memberOf ui
 */
ui.refresh_deck = function refresh_deck() {
	app.deck.display('#deck', 'type', 1);
	//app.draw_simulator.reset();

}

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {
	ui.init_config_buttons();
	ui.setup_event_handlers();
};

/**
 * called when the app data is loaded
 * @memberOf ui
 */
ui.on_data_loaded = function on_data_loaded() {
	ui.remove_melee_titles();
	app.deck.init();
	ui.set_max_qty();
};

/**
 * called when both the DOM and the data app have finished loading
 * @memberOf ui
 */
ui.on_all_loaded = function on_all_loaded() {
	ui.update_list_template();
	ui.build_faction_selector();
	ui.build_type_selector();
	ui.build_pack_selector();
	ui.refresh_deck();
	ui.refresh_list();
};

ui.read_from_storage();

})(app.ui, jQuery);
