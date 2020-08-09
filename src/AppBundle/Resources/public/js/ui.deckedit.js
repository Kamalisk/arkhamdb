(function ui_deck(ui, $) {

var DisplayColumnsTpl = '',
	SortKey = 'type_code',
	SortOrder = 1,
	CardDivs = [[],[],[]],
	Config = null;

/**
 * reads ui configuration from localStorage
 * @memberOf ui
 */
ui.read_config_from_storage = function read_config_from_storage() {
	if (localStorage) {
		var stored = localStorage.getItem('ui.deck.config');
		if(stored) {
			Config = JSON.parse(stored);
		}
	}
	Config = _.extend({
		'show-unusable': false,
		'show-only-deck': false,
		'display-column': 1,
		'core-set': 2,
		'show-suggestions': 0,
		'buttons-behavior': 'exclusive'
	}, Config || {});
}

/**
 * write ui configuration to localStorage
 * @memberOf ui
 */
ui.write_config_to_storage = function write_config_to_storage() {
	if (localStorage) {
		localStorage.setItem('ui.deck.config', JSON.stringify(Config));
	}
}

/**
 * inits the state of config buttons
 * @memberOf ui
 */
ui.init_config_buttons = function init_config_buttons() {
	// radio
	['display-column', 'core-set', 'show-suggestions', 'buttons-behavior'].forEach(function (radio) {
		$('input[name='+radio+'][value='+Config[radio]+']').prop('checked', true);
	});
	// checkbox
	['show-unusable', 'show-only-deck'].forEach(function (checkbox) {
		if(Config[checkbox]) $('input[name='+checkbox+']').prop('checked', true);
	})
}

/**
 * sets the maxqty of each card
 * @memberOf ui
 */
ui.set_max_qty = function set_max_qty() {
	var cores = 0;
	if($("[name=core]").is(":checked")){
		cores++;
	}
	if($("[name=core-2]").is(":checked")){
		cores++;
	}
	
	app.data.cards.find().forEach(function(record) {
		var max_qty = Math.min(3, record.deck_limit);
		if (record.pack_code == 'core') {
			max_qty = Math.min(max_qty, record.quantity * cores);
		}
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
	//app.deck.choices.push({'faction_select':["guardian","seeker"]});
	
	$('#faction_selector').hide();
	$('#deck_size_selector').hide();
	
	$('[data-filter=faction_selector]').empty();
	$('[data-filter=deck_size_selector]').empty();
	
	if (app.deck.choices && app.deck.choices.length > 0){
		for (var i = 0; i < app.deck.choices.length; i++){
			var choice = app.deck.choices[i];
			if (choice.faction_select) {
				$('#faction_selector').show();
				choice.faction_select.forEach(function(faction_code){
					var example = app.data.cards.find({"faction_code": faction_code})[0];
					var label = $('<option value="' + faction_code + '" title="'+example.faction_name+'"><span class="icon-' + faction_code + '"></span> ' + example.faction_name + '</option>');
					//label.tooltip({container: 'body'});
					$('[data-filter=faction_selector]').append(label);
				});
			}
			if (choice.deck_size_select) {
				$('#deck_size_selector').show();
				choice.deck_size_select.forEach(function(size){
					var label = $('<option value="' + size + '" title="'+size+' Cards"> ' + size + ' Cards</option>');
					//label.tooltip({container: 'body'});
					$('[data-filter=deck_size_selector]').append(label);
				});
			}
			if (choice.back_select) {
				$('#back_selector').show();
				var label = $('<option value="" title="Original">Original</option>');
				$('[data-filter=back_selector]').append(label);
				choice.back_select.forEach(function(inv){
					var card = app.data.cards.findById(inv);
					var label = $('<option value="' + card.code + '" title="'+card.name+'"> ' + card.name + ' ('+card.pack_name+')</option>');
					$('[data-filter=back_selector]').append(label);
				});
			}
			if (choice.front_select) {
				$('#front_selector').show();
				var label = $('<option value="" title="Original">Original</option>');
				$('[data-filter=front_selector]').append(label);
				choice.front_select.forEach(function(inv){
					var card = app.data.cards.findById(inv);
					var label = $('<option value="' + card.code + '" title="'+card.name+'"> ' + card.name + ' ('+card.pack_name+')</option>');
					$('[data-filter=front_selector]').append(label);
				});
			}
		}
	}
	
	$('[data-filter=faction_code]').empty();
	var faction_codes = app.data.cards.distinct('faction_code').sort();
	var neutral_index = faction_codes.indexOf('neutral');
	faction_codes.splice(neutral_index, 1);
	faction_codes.push('neutral');
	
	faction_codes.forEach(function(faction_code) {
		if (faction_code == "mythos"){
			return;
		}
		var example = app.data.cards.find({"faction_code": faction_code})[0];
		var label = $('<label class="btn btn-default btn-sm" data-code="'
				+ faction_code + '" title="'+example.faction_name+'"><input type="checkbox" name="' + faction_code
				+ '"><span class="icon-' + faction_code + '"></span> ' + example.faction_name + '</label>');
		label.tooltip({container: 'body'});
		$('[data-filter=faction_code]').append(label);
	});
	
	
	$('[data-filter=faction_code]').button();
	
	var label = $('<label class="btn btn-default btn-sm" data-code="'
			+ "basicweakness" + '" title="'+"Basic Weakness"+'"><input type="checkbox" name="' + "basicweakness"
			+ '"><span class="icon-' + "basicweakness" + '"></span>' + "Basic Weakness" + '</label>');
	label.tooltip({container: 'body'});
	$('[data-filter=subtype_code]').append(label);
	
	var label = $('<label class="btn btn-default btn-sm" data-code="'
			+ "special" + '" title="'+"Character"+'"><input type="checkbox" name="' + "special"
			+ '"><span class="icon-' + "special" + '"></span>' + "Character" + '</label>');
	label.tooltip({container: 'body'});
	$('[data-filter=subtype_code]').append(label);
	
	
	var label = $('<label class="btn btn-default btn-sm" data-code="'
			+ "campaign" + '" title="'+"Campaign"+'"><input type="checkbox" name="' + "campaign"
			+ '"><span class="icon-' + "campaign" + '"></span>' + "Campaign" + '</label>');
	label.tooltip({container: 'body'});
	$('[data-filter=subtype_code]').append(label);
	
	$('[data-filter=subtype_code]').button();
}


/**
 * builds the type selector
 * @memberOf ui
 */
ui.build_type_selector = function build_type_selector() {
	$('[data-filter=type_code]').empty();
	['asset','event','skill', 'basicweakness'].forEach(function(type_code) {
		var example = app.data.cards.find({"type_code": type_code})[0];
		// not all card types might exist
		if (example) {
			var label = $('<label class="btn btn-default btn-sm" data-code="'
					+ type_code + '" title="'+example.type_name+'"><input type="checkbox" name="' + type_code
					+ '"><span class="icon-' + type_code + '"></span>' + example.type_name + '</label>');
			label.tooltip({container: 'body'});
			$('[data-filter=type_code]').append(label);
		}
	});
	$('[data-filter=type_code]').button();
	

	var label = $('<label class="btn btn-default btn-sm" data-code="'
			+ "xp" + '" title="'+"Level 0"+'"><input type="checkbox" name="' + "xp0"
			+ '"><span class="icon-' + "xp" + '"></span>' + "Level 0" + '</label>');
	label.tooltip({container: 'body'});
	$('[data-filter=xp]').append(label);
	
	var label = $('<label class="btn btn-default btn-sm" data-code="'
			+ "xp" + '" title="'+"Level 1-5"+'"><input type="checkbox" name="' + "xp15"
			+ '"><span class="icon-' + "xp" + '"></span>' + "Level 1-5" + '</label>');
	label.tooltip({container: 'body'});
	$('[data-filter=xp]').append(label);
	
	$('[data-filter=xp]').button();
}


/**
 * builds the pack selector
 * @memberOf ui
 */
ui.build_taboo_selector = function build_taboo_selector() {
	$('[data-filter=taboo_code]').empty();
	
	app.data.taboos.find({
		active: {
			'$eq': 1
		}
	}, {
		$orderBy: {
			id: -1
		}
	}).forEach(function(record) {
		$('<option value="'+record.id+'">' + record.name + ' (' + record.date_start +')</option>').appendTo('[data-filter=taboo_code]');
	});
	$('<option value="">None</option>').appendTo('[data-filter=taboo_code]');
}


/**
 * builds the pack selector
 * @memberOf ui
 */
ui.build_pack_selector = function build_pack_selector() {
	$('[data-filter=pack_code]').empty();
	
	//$('<li><h2>Defaults to packs in your collection</p></h2>').appendTo('[data-filter=pack_code]');
	
	// parse pack owner string
	var collection = {};
	var no_collection = true;
	if (app.user.data && app.user.data.owned_packs) {
      var packs = app.user.data.owned_packs.split(',');
      _.forEach(packs, function(str) {
          collection[str] = 1;
          no_collection = false;
      });
			//console.log(app.user.data.owned_packs, collection);
  }
	
	
	app.data.packs.find({
		name: {
			'$exists': true
		}
	}, {
	    $orderBy: {
	        cycle_position: 1,
	        position: 1
	    }
	}).forEach(function(record) {
		// checked or unchecked ? checked by default
		var checked = false;
		if (collection[record.id]){
			checked = true;
		}
		// if not yet available, uncheck pack
		//if(record.available === "") checked = false;
		// if user checked it previously, check pack
		// if(localStorage && localStorage.getItem('set_code_' + record.code) !== null) checked = true;
		// if pack used by cards in deck, check pack
		
		if (no_collection && localStorage && localStorage.getItem('set_code_' + record.code) === "true"){
			checked = true;
		} else if (no_collection && localStorage && localStorage.getItem('set_code_' + record.code) === "false"){
			checked = false;
		} else if (no_collection && record.available !== ""){
			checked = true;
		}

		var cards = app.data.cards.find({
			pack_code: record.code,
			indeck: {
				'$gt': 0
			}
		});
		if(cards.length) {
			checked = true;
		}

		$('<li><a href="#"><label><input type="checkbox" name="' + record.code + '"' + (checked ? ' checked="checked"' : '') + '>' + record.name + '</label></a></li>').appendTo('[data-filter=pack_code]');
		// special case for core set 2
		if (record.code == "core"){
			if (collection[record.id+"-2"]){
				checked = true;
			}else {
				checked = false;
			}
			
			if (no_collection && localStorage && localStorage.getItem('set_code_' + record.code+"-2") === "true"){
				checked = true;
			} else if (no_collection && localStorage && localStorage.getItem('set_code_' + record.code+"-2") === "false"){
				checked = false;
			} else if (no_collection && record.available !== ""){
				//checked = true;
			}
			
			var cards = app.data.cards.find({
				pack_code: record.code,
				indeck: {
					'$gt': 1
				},
				quantity: {
					'$eq': 1
				}
			});
			if(cards.length) checked = true;

			$('<li><a href="#"><label><input type="checkbox" name="' + record.code + '-2"' + (checked ? ' checked="checked"' : '') + '>Second ' + record.name + '</label></a></li>').appendTo('[data-filter=pack_code]');
		}
	});
}


/**
 * @memberOf ui
 */
ui.init_selectors = function init_selectors() {
	$('[data-filter=faction_code]').find('input[name=neutral]').prop("checked", true).parent().addClass('active');
	var investigator = app.data.cards.findById(app.deck.get_investigator_code());
	//console.log(investigator);
	if (investigator.faction_code){
		$('[data-filter=faction_code]').find('input[name='+investigator.faction_code+']').prop("checked", true).parent().addClass('active');
	}
	$('[data-filter=subtype_code]').find('input[name=basicweakness]').prop("checked", true).parent().addClass('active');
	$('[data-filter=xp]').find('input[name=xp0]').prop("checked", true).parent().addClass('active');
	
	if (app.deck.get_previous_deck()){
		$('[data-filter=xp]').find('input[name=xp15]').prop("checked", true).parent().addClass('active');
		$('[data-filter=xp]').find('input[name=xp0]').prop("checked", false).parent().removeClass('active');
	} else {
		$('[data-filter=xp]').find('input[name=xp0]').prop("checked", true).parent().addClass('active');
		$('[data-filter=xp]').find('input[name=xp15]').prop("checked", false).parent().removeClass('active');
	}

	if (app.deck.taboo_id){
		$('[data-filter=taboo_code]').val(app.deck.taboo_id);
	} else {
		$('[data-filter=taboo_code]').val("");
	}
	if (app.deck.meta){
		if (app.deck.meta.faction_selected){
			$('[data-filter=faction_selector]').val(app.deck.meta.faction_selected);
		}
		if (app.deck.meta.deck_size_selected){
			$('[data-filter=deck_size_selector]').val(app.deck.meta.deck_size_selected);
		}
		if (app.deck.meta.alternate_back){
			$('[data-filter=back_selector]').val(app.deck.meta.alternate_back);
		}
		if (app.deck.meta.alternate_front){
			$('[data-filter=front_selector]').val(app.deck.meta.alternate_front);
		}
	}
	
}

function uncheck_all_others() {
	$(this).closest('[data-filter]').find("input[type=checkbox]").prop("checked",false);
	$(this).children('input[type=checkbox]').prop("checked", true).trigger('change');
}

function check_all_others() {
	$(this).closest('[data-filter]').find("input[type=checkbox]").prop("checked",true);
	$(this).children('input[type=checkbox]').prop("checked", false);
}

function uncheck_all_active() {
	$(this).closest('[data-filter]').find("label.active").button('toggle');
}

function check_all_inactive() {
	$(this).closest('[data-filter]').find("label:not(.active)").button('toggle');
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
		if (!event.shiftKey && Config['buttons-behavior'] === 'exclusive' || event.shiftKey && Config['buttons-behavior'] === 'cumulative') {
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
	if(q.match(/^\w[:<>!]/)) app.smart_filter.update(q);
	else app.smart_filter.update('');
	ui.refresh_list();
}
/**
 * @memberOf ui
 * @param event
 */
ui.on_input_smartfilter2 = function on_input_smartfilter2(event) {
	var q = $(this).val();
	if(q.match(/^\w[:<>!]/)) app.smart_filter2.update(q);
	else app.smart_filter2.update('');
	ui.refresh_list2();
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_submit_form = function on_submit_form(event) {
	var deck_json = app.deck.get_json();
	var ignored_json = app.deck.get_ignored_json();
	var meta_json = app.deck.get_meta_json();
	$('input[name=content]').val(deck_json);
	$('input[name=ignored]').val(ignored_json);
	$('input[name=meta]').val(meta_json);
	$('input[name=xp_spent]').val(app.deck.get_xp_spent());
	$('input[name=xp_adjustment]').val(app.deck.get_xp_adjustment());
	$('input[name=description]').val($('textarea[name=description_]').val());
	$('input[name=tags]').val($('input[name=tags_]').val());
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_config_change = function on_config_change(event) {
	var name = $(this).attr('name');
	var type = $(this).prop('type');
	//console.log(name, type);
	switch(type) {
	case 'radio':
		var value = $(this).val();
		if(!isNaN(parseInt(value, 10))) value = parseInt(value, 10);
		Config[name] = value;
		break;
	case 'checkbox':
		Config[name] = $(this).prop('checked');
		break;
	}
	ui.write_config_to_storage();
	switch(name) {
		case 'buttons-behavior':
		break;
		case 'display-column':
		ui.update_list_template();
		ui.refresh_lists();
		break;
		case 'show-suggestions':
		ui.toggle_suggestions();
		ui.refresh_lists();
		break;
		default:
		ui.refresh_lists();
	}
}


/**
 * @memberOf ui
 * @param event
 */
ui.on_core_change = function on_core_change(event) {
	var name = $(this).attr('name');
	var type = $(this).prop('type');
	if (localStorage) {
		localStorage.setItem('set_code_' + name, $(this).is(":checked")  );
	}
	switch(name) {
		case 'core':
		case 'core-2':
		ui.set_max_qty();
		ui.reset_list();
		break;
		default:
		ui.refresh_lists();
	}
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_taboo_change = function on_taboo_change(event) {
	var name = $(this).attr('name');
	var type = $(this).prop('type');
	var value = $(this).prop('value');

	app.deck.taboo_id = parseInt(value);
	app.data.apply_taboos(app.deck.taboo_id);
	ui.reset_list();
	ui.on_deck_modified();
}

ui.toggle_suggestions = function toggle_suggestions() {
	app.suggestions.number = Config['show-suggestions'];
	app.suggestions.show();
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_table_sort_click = function on_table_sort_click(event) {
	event.preventDefault();
	var new_sort = $(this).data('sort');
	if (SortKey == new_sort) {
		SortOrder *= -1;
	} else {
		SortKey = new_sort;
		SortOrder = 1;
	}
	ui.refresh_list();
	ui.update_sort_caret();
}

ui.chaos = function() {

	if (!window.confirm("This will replace your deck with an Ultimatum of Chaos deck, are you sure you wish to continue? (This may not work for all investigators)")){
		return;
	}

	var counter = 0;
	var	filters = ui.get_filters("potato");
	var query = app.smart_filter.get_query(filters);
	//query['subtype_code'] = {'$ne': 'basicweakness'};
	query['xp'] = 0;
	query['permanent'] = false;
	
	var cards = app.data.cards.find(query);
	var valid_cards = [];
	
	cards.forEach(function (card) {
		card.indeck = 0;
		if (app.deck.can_include_card(card)){
			valid_cards.push(card);
		}
	});
	app.deck.reset_limit_count();
	
	var size = valid_cards.length;
	var deck_size = app.data.cards.findById(app.deck.get_investigator_code()).deck_requirements.size;
	if (app.deck.meta.deck_size_selected) {
		deck_size = parseInt(app.deck.meta.deck_size_selected);
	}
	if (size >= deck_size){
		while (counter < deck_size){
			var random_id = Math.floor(Math.random() * size)
			var random_card = valid_cards[random_id];
			if (random_card.indeck < random_card.deck_limit){
				if (app.deck.can_include_card(random_card, true, true)){
					random_card.indeck++;
					//console.log(random_card.name, random_card.indeck, counter);
					counter++;
					//console.log(random_card.name, random_card.indeck, counter);
				}
			}
		}
	}
	
	valid_cards.forEach(function(card){
		app.deck.set_card_copies(card.code, card.indeck);
	})
	
	ui.on_deck_modified();
};



/**
 * @memberOf ui
 * @param event
 */
ui.on_list_quantity_change = function on_list_quantity_change(event) {
	var row = $(this).closest('.card-container');
	var code = row.data('code');
	var quantity = parseInt($(this).val(), 10);
//	row[quantity ? "addClass" : "removeClass"]('in-deck');
	ui.on_quantity_change(code, quantity);
}
ui.on_suggestion_quantity_change = function on_suggestion_quantity_change(event) {
	console.log(event);
	var row = $(event.target).closest('.card-container');
	var code = row.data('code');
	var quantity = parseInt($(event.target).val(), 10);
//	row[quantity ? "addClass" : "removeClass"]('in-deck');
	ui.on_quantity_change(code, quantity);
}

/**
 * @memberOf ui
 * @param event
 */
ui.on_modal_quantity_change = function on_modal_quantity_change(event) {
	var modal = $('#cardModal');
	var code =  modal.data('code');
	var quantity = parseInt($(this).val(), 10);
	modal.modal('hide');
	if ($(this).attr("name") == "ignoreqty"){
		ui.on_ignore_quantity_change(code, quantity);
	} else {
		ui.on_quantity_change(code, quantity);
	}

	setTimeout(function () {
		$('#filter-text').typeahead('val', '').focus();
	}, 100);
}

ui.refresh_row = function refresh_row(card_code, quantity) {
	// for each set of divs (1, 2, 3 columns)
	CardDivs.forEach(function(rows) {
		var row = rows[card_code];
		if(!row) return;

		// rows[card_code] is the card row of our card
		// for each "quantity switch" on that row
		row.find('input[name="qty-' + card_code + '"]').each(function(i, element) {
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
 * @memberOf ui
 */
ui.on_quantity_change = function on_quantity_change(card_code, quantity) {
	var update_all = app.deck.set_card_copies(card_code, quantity);
	ui.refresh_deck();
	app.suggestions.compute();
	if(update_all) {
		ui.refresh_lists();
	}
	else {
		ui.refresh_row(card_code, quantity);
	}
	app.deck_history.all_changes();
}
ui.on_ignore_quantity_change = function on_ignore_quantity_change(card_code, quantity) {
	var update_all = app.deck.set_card_ignores(card_code, quantity);
	ui.refresh_deck();
	app.deck_history.all_changes();
}

/**
 * sets up event handlers ; dataloaded not fired yet
 * @memberOf ui
 */
ui.setup_event_handlers = function setup_event_handlers() {
	
	$('#global_filters [data-filter]').on({
		click : ui.on_click_filter
	}, 'label');
	
	$('#build_filters [data-filter]').on({
		change : ui.refresh_list,
		click : ui.on_click_filter
	}, 'label');
	$('#personal_filters [data-filter]').on({
		change : ui.refresh_list2,
		click : ui.on_click_filter
	}, 'label');
	
	$('#deck_options [data-filter=faction_selector]').on({
		change : function(event){
			app.deck.meta.faction_selected = event.target.value;
			ui.refresh_deck();
			ui.refresh_lists();
		}
	});

	$('#deck_options [data-filter=deck_size_selector]').on({
		change : function(event){
			app.deck.meta.deck_size_selected = event.target.value;
			ui.refresh_deck();
			ui.refresh_lists();
		}
	});

	$('#deck_options [data-filter=back_selector]').on({
		change : function(event){
			app.deck.meta.alternate_back = event.target.value;
			ui.refresh_deck();
			ui.refresh_lists();
		}
	});

	$('#deck_options [data-filter=front_selector]').on({
		change : function(event){
			app.deck.meta.alternate_front = event.target.value;
			ui.refresh_deck();
			ui.refresh_lists();
		}
	});

	$('#filter-text').on('input', ui.on_input_smartfilter);
	$('#filter-text-personal').on('input', ui.on_input_smartfilter2);

	$('#save_form').on('submit', ui.on_submit_form);

	$('#btn-save-as-copy').on('click', function(event) {
		$('#deck-save-as-copy').val(1);
	});

	$('#btn-cancel-edits').on('click', function(event) {
		var unsaved_edits = app.deck_history.get_unsaved_edits();
		if(unsaved_edits.length) {
			var confirmation = confirm("This operation will revert the changes made to the deck since "+unsaved_edits[0].date_creation.calendar()+". The last "+(unsaved_edits.length > 1 ? unsaved_edits.length+" edits" : "edit")+" will be lost. Do you confirm?");
			if(!confirmation) return false;
		}
		else {
			if(app.deck_history.is_changed_since_last_autosave()) {
				var confirmation = confirm("This operation will revert the changes made to the deck. Do you confirm?");
				if(!confirmation) return false;
			}
		}
		$('#deck-cancel-edits').val(1);
	});

	$('#config-options').on('change', 'input', ui.on_config_change);
	$('#global_filters [data-filter=pack_code]').on('change', 'input', ui.on_core_change);
	$('[data-filter=taboo_code]').on('change',  ui.on_taboo_change);
	$('#collection').on('change', 'input[type=radio]', ui.on_list_quantity_change);
	$('#special-collection').on('change', 'input[type=radio]', ui.on_list_quantity_change);
	
	$('#deck').on('click', 'a[data-random]', ui.select_basic_weakness);
	$('#deck').on('click', '#xp_up', ui.on_adjust_xp_up);
	$('#deck').on('click', '#xp_down', ui.on_adjust_xp_down);
	
	$('#global_filters').on('click', '#chaos', ui.chaos);
	
	
	$('#cardModal').on('keypress', function(event) {
		var num = parseInt(event.which, 10) - 48;
		$('#cardModal .modal-qty input[type=radio][value=' + num + ']').trigger('change');
	});
	$('#cardModal').on('change', 'input[type=radio]', ui.on_modal_quantity_change);

	$('thead').on('click', 'a[data-sort]', ui.on_table_sort_click);

}

ui.on_adjust_xp_up = function on_adjust_xp_up() {
	app.deck.set_xp_adjustment(app.deck.get_xp_adjustment()+1);
	app.deck_history && app.deck_history.setup('#history');
}
ui.on_adjust_xp_down = function on_adjust_xp_down() {
	app.deck.set_xp_adjustment(app.deck.get_xp_adjustment()-1);
	app.deck_history && app.deck_history.setup('#history');
}

ui.select_basic_weakness = function select_basic_weakness() {
	// replace the random weakness card in the deck with a random weakness
	var weaknesses = app.data.cards.find({"subtype_code" : "basicweakness"});
	var filtered_weaknesses = [];
	weaknesses.forEach(function (card){
		//console.log(card);
		
		if($("[name="+card.pack_code+"]").is(":checked") && card.code != "01000" && card.indeck < card.maxqty){
			filtered_weaknesses.push(card);
		}
	});
	if (filtered_weaknesses.length > 0){
		var weakness = filtered_weaknesses[ Math.round(Math.random(0, 1) * (filtered_weaknesses.length-1)) ];
		if ($(this) && $(this).data("random")){
			ui.on_quantity_change($(this).data("random"), 0);	
		}
		ui.on_quantity_change(weakness.code, weakness.indeck+1);
	}
	
}


/**
 * returns the current card filters as an array
 * @memberOf ui
 */
ui.get_filters = function get_filters(prefix) {
	var filters = {};
	var target = "#build_filters [data-filter], #global_filters [data-filter]";
	if (prefix){
		target = "#"+prefix+"_filters [data-filter], #global_filters [data-filter]";
	}
	$(target).each(
		function(index, div) {
			var column_name = $(div).data('filter');
			var arr = [];
			if(column_name == "subtype_code"){
				if($("input[name=basicweakness]").prop('checked')) {
					filters[column_name] = {
						'$in': ['basicweakness']
					};
				} else if($("input[name=special]").prop('checked')) {
					filters['encounter_code'] = {
						'$exists': false
					};

					filters['$or'] = [
						{
							"subtype_code": {
								'$nin': ['basicweakness']
							}
						},{
							"subtype_code": {
								'$exists': false
							}
						}
					];
					
					//console.log(filters);
				} else if($("input[name=specialweakness]").prop('checked')) {
					filters['subtype_code'] = {
						'$in': ['weakness']
					};
					filters['encounter_code'] = {
						'$exists': false
					};
				} else if($("input[name=campaign]").prop('checked')) {
					filters['encounter_code'] = {
						'$exists': true
					};
				} else {
					filters['xp'] = {
						'$exists': false
					};
				}
			} else {
				$(div).find("input[type=checkbox]").each(
					function(index, elt) {
						if ($(elt).attr('name') == "xp0"){
							if($(elt).prop('checked')) arr.push(0);
						} else if ($(elt).attr('name') == "xp15") {
							// search for any xp value from 1-5 
							if($(elt).prop('checked')) {
								arr.push(1);
								arr.push(2);
								arr.push(3);
								arr.push(4);
								arr.push(5);
							} 
						} else {
							if ($(elt).attr('name') == "core-2"){
								if($(elt).prop('checked')) arr.push("core");
							}else {
								if($(elt).prop('checked')) arr.push($(elt).attr('name'));	
							}
							
						}
					}
				);
				if(arr.length) {
					// check both faction codes
					if (column_name == "faction_code"){
						filters['$or'] = [
							{"faction_code": { '$in': arr }},
							{"faction2_code": { '$in': arr }}
						];
					} else {
						filters[column_name] = {
							'$in': arr
						};
					}
					
				}
			}
		}
	);
	if (!filters['xp']){
		filters['xp'] = {};
	}
	if (prefix){
		filters['xp']['$exists'] = false;
	} else {
		filters['xp']['$exists'] = true;
	}
	filters['deck_limit'] = {};
	filters['deck_limit']['$exists'] = true;
	//console.log(filters);
	return filters;
}

/**
 * updates internal variables when display columns change
 * @memberOf ui
 */
ui.update_list_template = function update_list_template() {
	switch (Config['display-column']) {
	case 1:
		DisplayColumnsTpl = _.template(
			'<tr>'
				+ '<td><div class="btn-group" data-toggle="buttons"><%= radios %></div></td>'
				+ '<td><a class="card card-tip fg-<%= card.faction_code %> <% if (typeof(card.faction2_code) !== "undefined") { %> fg-dual <% } %>" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ '<%= card.name %></a>'
				+ '<% if (card.taboo) { %> <span class="icon-tablet" style="color:purple;" title="Is mutated by the current taboo list"></span> <% } %>'
				+ '<% if (card.exceptional) { %> <span class="icon-eldersign" style="color:orange;" title="Exceptional. Double xp cost and limit one per deck."></span> <% } %>'
				+ '</td>'
				+ '<td class="xp"><%= card.xp %></td>'
				+ '<td class="cost"><%= card.cost %></td>'
				+ '<td class="type" style="text-align : left;"><span class="" title="<%= card.type_name %>"><%= card.type_name %></span> <% if (card.slot) { %> - <%= card.slot %> <% } %></td>'
				+ '<td class="faction"><span class="fg-<%= card.faction_code %>" title="<%= card.faction_name %>"><%= card.faction_name %></span></td>'
			+ '</tr>'
		);
		break;
	case 2:
		DisplayColumnsTpl = _.template(
			'<div class="col-sm-6">'
				+ '<div class="media">'
					+ '<div class="media-left"><img class="media-object"  onerror="this.onerror=null;this.src=\'/bundles/cards/<%= card.code %>.png\';" src="/bundles/cards/<%= card.code %>.jpg" alt="<%= card.name %>"></div>'
					+ '<div class="media-body">'
						+ '<h4 class="media-heading"><a class="card card-tip" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a></h4>'
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
					+ '<div class="media-left"><img class="media-object" onerror="this.onerror=null;this.src=\'/bundles/cards/<%= card.code %>.png\';" src="/bundles/cards/<%= card.code %>.jpg" alt="<%= card.name %>"></div>'
					+ '<div class="media-body">'
						+ '<h5 class="media-heading"><a class="card card-tip" data-code="<%= card.code %>" href="<%= url %>" data-target="#cardModal" data-remote="false" data-toggle="modal"><%= card.name %></a></h5>'
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
		'<label class="btn btn-xs btn-default <%= active %>"><input type="radio" name="qty-<%= card.code %>" value="<%= i %>"><%= i %></label>'
	);
	
	//console.log(card.name, card.maxqty, card.quantity);
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

ui.reset_list = function reset_list() {
	CardDivs = [[],[],[]];
	ui.refresh_lists();
}


ui.refresh_lists = function refresh_lists() {
	ui.refresh_list();
	ui.refresh_list2();
}

/**
 * destroys and rebuilds the list of available cards
 * don't fire unless 250ms has passed since last invocation
 * @memberOf ui
 */
ui.refresh_list = _.debounce(function refresh_list() {
	$('#collection-table').empty();
	$('#collection-grid').empty();

	var counter = 0;
	var container = $('#collection-table');
	var	filters = ui.get_filters();
	var query = app.smart_filter.get_query(filters);
	var orderBy = {};

	SortKey.split('|').forEach(function (key) {
		orderBy[key] = SortOrder;
	});
	if(SortKey !== 'name') orderBy['name'] = 1;
	var cards = app.data.cards.find(query, {'$orderBy': orderBy});
	var divs = CardDivs[ Config['display-column'] - 1 ];
	
	cards.forEach(function (card) {
		if (Config['show-only-deck'] && !card.indeck) return;
		var unusable = !app.deck.can_include_card(card);
		if (!Config['show-unusable'] && unusable) return;

		var row = divs[card.code];
		if(!row) row = divs[card.code] = ui.build_row(card);

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

		if (Config['display-column'] > 1 && (counter % Config['display-column'] === 0)) {
			container = $('<div class="row"></div>').appendTo($('#collection-grid'));
		}

		container.append(row);
		counter++;
	});
}, 250);


/**
 * destroys and rebuilds the list of available cards
 * don't fire unless 250ms has passed since last invocation
 * @memberOf ui
 */
ui.refresh_list2 = _.debounce(function refresh_list2() {
	$('#special-collection-table').empty();
	$('#special-collection-grid').empty();

	var counter = 0,
		container = $('#special-collection-table'),
		filters = ui.get_filters("personal"),
		query = app.smart_filter2.get_query(filters),
		orderBy = {};

	SortKey.split('|').forEach(function (key ) {
		orderBy[key] = SortOrder;
	});
	if(SortKey !== 'name') orderBy['name'] = 1;
	var cards = app.data.cards.find(query, {'$orderBy': orderBy});
	var divs = CardDivs[ Config['display-column'] - 1 ];

	cards.forEach(function (card) {
		if (Config['show-only-deck'] && !card.indeck) return;
		var unusable = !app.deck.can_include_card(card);
		if (!Config['show-unusable'] && unusable) return;
		
		var row = divs[card.code];
		if(!row) row = divs[card.code] = ui.build_row(card);
		
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

		if (Config['display-column'] > 1 && (counter % Config['display-column'] === 0)) {
			container = $('<div class="row"></div>').appendTo($('#collection-grid'));
		}

		container.append(row);
		counter++;
	});
}, 250);

/**
 * called when the deck is modified and we don't know what has changed
 * @memberOf ui
 */
ui.on_deck_modified = function on_deck_modified() {	
	ui.refresh_deck();
	ui.refresh_lists();
	//app.suggestions && app.suggestions.compute();
	//app.deck_history.all_changes();
}


/**
 * @memberOf ui
 */
ui.refresh_deck = function refresh_deck() {
	app.deck.display('#deck');
	app.draw_simulator && app.draw_simulator.reset();
	app.deck_charts && app.deck_charts.setup();
	//app.suggestions && app.suggestions.compute();
}

/**
 * @memberOf ui
 */
ui.setup_typeahead = function setup_typeahead() {

	function findMatches(q, cb) {
		if(q.match(/^\w:/)) return;
		var regexp = new RegExp(q, 'i');
		cb(app.data.cards.find({name: regexp}));
	}

	$('#filter-text').typeahead({
		hint: true,
		highlight: true,
		minLength: 2
	},{
		name : 'cardnames',
		displayKey: 'name',
		source: findMatches
	});

}

ui.update_sort_caret = function update_sort_caret() {
	var elt = $('[data-sort="'+SortKey+'"]');
	$(elt).closest('tr').find('th').removeClass('dropup').find('span.caret').remove();
	$(elt).after('<span class="caret"></span>').closest('th').addClass(SortOrder > 0 ? '' : 'dropup');
}

ui.init_filter_help = function init_filter_help() {
	$('#filter-text-button').popover({
		container: 'body',
		content: app.smart_filter.get_help(),
		html: true,
		placement: 'bottom',
		title: 'Smart filter syntax'
	});
}

ui.setup_dataupdate = function setup_dataupdate() {
	$('a.data-update').click(function (event) {
		$(document).on('data.app', function (event) {
			$('a.data-update').parent().text("Data refreshed. You can save or reload your deck.");
		});
		app.data.update();
		return false;
	})
}

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {
	ui.init_config_buttons();
	ui.init_filter_help();
	ui.update_sort_caret();
	ui.toggle_suggestions();
	ui.setup_event_handlers();
	app.textcomplete && app.textcomplete.setup('#description');
	app.markdown && app.markdown.setup('#description', '#description-preview')
	app.draw_simulator && app.draw_simulator.on_dom_loaded();
	app.card_modal && $('#filter-text').on('typeahead:selected typeahead:autocompleted', app.card_modal.typeahead);
};

/**
 * called when the app data is loaded
 * @memberOf ui
 */
ui.on_data_loaded = function on_data_loaded() {	
	app.draw_simulator && app.draw_simulator.on_data_loaded();
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
	ui.build_taboo_selector();
	ui.init_selectors();
	// for now this needs to be done here
	ui.set_max_qty();
	ui.refresh_deck(); // now updates the deck changes and history too
	ui.refresh_lists(); // update the card selection lists
	ui.setup_typeahead();
	ui.setup_dataupdate();
	
	var investigator = app.data.cards.findById(app.deck.get_investigator_code());
	app.suggestions.query("sugg-"+investigator.code);
	
};

ui.read_config_from_storage();

})(app.ui, jQuery);
