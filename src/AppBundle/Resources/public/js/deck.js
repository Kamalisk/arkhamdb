var InputByTitle = false;
var DisplayColumns = 1;
var CoreSets = 3;
var Buttons_Behavior = 'cumulative';
var Snapshots = []; // deck contents autosaved
var Autosave_timer = null;
var Deck_changed_since_last_autosave = false;
var Autosave_running = false;
var Autosave_period = 60;

app.data_loaded.add(function() {
	var localStorageDisplayColumns;
	if (localStorage
			&& (localStorageDisplayColumns = parseInt(localStorage
					.getItem('display_columns'), 10)) !== null
			&& [ 1, 2, 3 ].indexOf(localStorageDisplayColumns) > -1) {
		DisplayColumns = localStorageDisplayColumns;
	}
	$('input[name=display-column-' + DisplayColumns + ']')
			.prop('checked', true);

	var localStorageCoreSets;
	if (localStorage
			&& (localStorageCoreSets = parseInt(localStorage
					.getItem('core_sets'), 10)) !== null
			&& [ 1, 2, 3 ].indexOf(localStorageCoreSets) > -1) {
		CoreSets = localStorageCoreSets;
	}
	$('input[name=core-set-' + CoreSets + ']').prop('checked', true);

	var localStorageSuggestions;
	if (localStorage
			&& (localStorageSuggestions = parseInt(localStorage
					.getItem('show_suggestions'), 10)) !== null
			&& [ 0, 3, 10 ].indexOf(localStorageSuggestions) > -1) {
		app.suggestions.number = localStorageSuggestions;
	}
	$('input[name=show-suggestions-' + app.suggestions.number + ']').prop('checked', true);

	var localStorageButtonsBehavior;
	if (localStorage
			&& (localStorageButtonsBehavior = localStorage.getItem('buttons_behavior')) !== null
			&& [ 'cumulative', 'exclusive' ].indexOf(localStorageButtonsBehavior) > -1) {
		Buttons_Behavior = localStorageButtonsBehavior;
	}
	$('input[name=buttons-behavior-' + Buttons_Behavior + ']').prop('checked', true);

	app.data.cards({
		side_code : {
			"!is" : Side
		}
	}).remove();
	var sets_in_deck = {};
	app.data.cards().each(function(record) {
		var indeck = 0;
		if (Deck[record.code]) {
			indeck = parseInt(Deck[record.code], 10);
			sets_in_deck[record.set_code] = 1;
		}
		app.data.cards(record.___id).update({
			indeck : indeck,
			factioncost : record.factioncost || 0
		});
	});
	update_deck();
	app.data.cards().each(function(record) {
		var max_qty = 3;
		if (record.set_code == 'core')
			max_qty = Math.min(record.quantity * CoreSets, 3);
		if (record.type_code == "identity" || record.limited)
			max_qty = 1;
		if(Identity.faction_code == "neutral") {
			max_qty = 9;
		}
		app.data.cards(record.___id).update({
			maxqty : max_qty
		});
	});
	if(Modernizr.touch) {
		$('#faction_code, #type_code').css('width', '100%').addClass('btn-group-vertical');
	} else {
		$('#faction_code, #type_code').addClass('btn-group');
	}
	$('#faction_code').empty();
	$.each(app.data.cards().distinct("faction_code").sort(
			function(a, b) {
				return b === "neutral" ? -1 : a === "neutral" ? 1 : a < b ? -1
						: a > b ? 1 : 0;
			}), function(index, record) {
		var example = app.data.cards({"faction_code": record}).first();
		var faction = example.faction.replace(/.*Weyland.*/, 'Weyland');
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
		$('#faction_code').append(label);
	});
	$('#faction_code').button();
	$('#faction_code').children('label[data-code='+Identity.faction_code+']').each(function(index, elt) {
		$(elt).button('toggle');
	});

	$('#type_code').empty();
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
		$('#type_code').append(label);
	});
	$('#type_code').button();
	$('#type_code').children('label:first-child').each(function(index, elt) {
		$(elt).button('toggle');
	});

	$('#set_code').empty();
	app.data.sets().each(
			function(record) {
				var checked = record.available === ""
						&& sets_in_deck[record.code] == null ? ''
						: ' checked="checked"';
				$('#set_code').append(
						'<li><a href="#"><label><input type="checkbox" name="'
								+ record.code + '"' + checked + '>'
								+ record.name + '</label></a></li>');
			});

	$('input[name=Identity]').prop("checked", false);
	if (Identity.code == "03002")
		$('input[name=Jinteki]').prop("checked", false);

	$('.filter')
			.each(
					function(index, div) {
						var columnName = $(div).attr('id');
						var arr = [], checked;
						$(div)
								.find("input[type=checkbox]")
								.each(
										function(index, elt) {
											if (columnName == "set_code"
													&& localStorage
													&& (checked = localStorage
															.getItem('set_code_'
																	+ $(elt)
																			.attr(
																					'name'))) !== null)
												$(elt).prop('checked',
														checked === "on");
											if ($(elt).prop('checked'))
												arr.push($(elt).attr('name'));
										});
						Filters[columnName] = arr;
					});
	FilterQuery = {};
	$.each(Filters, function(k) {
		if (Filters[k] != '') {
			FilterQuery[k] = Filters[k];
		}
	});

	refresh_collection();

	function findMatches(q, cb) {
		if(q.match(/^\w:/)) return;
		var matches = app.data.cards({name: {likenocase: q}}).map(function (record) {
			return { value: record.name };
		});
		cb(matches);
	}
	
	$('#filter-text').typeahead({
		  hint: true,
		  highlight: true,
		  minLength: 2
		},{
		name : 'cardnames',
		displayKey: 'value',
		source: findMatches
	});
	
	make_cost_graph();
	make_strength_graph();
	
	$('html,body').css('height', 'auto');
	
});

function uncheck_all_others() {
	$(this).closest(".filter").find("input[type=checkbox]").prop("checked",false);
	$(this).children('input[type=checkbox]').prop("checked", true).trigger('change');
}

function check_all_others() {
	$(this).closest(".filter").find("input[type=checkbox]").prop("checked",true);
	$(this).children('input[type=checkbox]').prop("checked", false);
}

function uncheck_all_active() {
	$(this).closest(".filter").find("label.active").button('toggle');
}

function check_all_inactive() {
	$(this).closest(".filter").find("label:not(.active)").button('toggle');
}

$(function() {
	$('html,body').css('height', '100%');

	$('#filter-text').on('typeahead:selected typeahead:autocompleted',
			app.card_modal.typeahead);
	
	$(document).on('hidden.bs.modal', function (event) {
		if(InputByTitle) {
			setTimeout(function () {
				$('#filter-text').typeahead('val', '').focus();
			}, 100);
		}
	});
	
	$('#set_code,.search-buttons').on(
			{
				change : handle_input_change,
				click : function(event) {
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
			}, 'label');

	$('#filter-text').on({
		input : function (event) {
			var q = $(this).val();
			if(q.match(/^\w[:<>!]/)) app.smart_filter.handler(q, refresh_collection);
			else app.smart_filter.handler('', refresh_collection);
		}
	});

	$('#save_form').submit(handle_submit);

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
	$('#collection').on({
		change : function(event) {
			InputByTitle = false;
			handle_quantity_change.call(this, event);
		}
	}, 'input[type=radio]');
	$('#collection').on({
		click : function(event) {
			InputByTitle = false;
		}
	}, 'a.card');
	$('.modal').on({
		change : handle_quantity_change
	}, 'input[type=radio]');
	$('input[name=show-disabled]').on({
		change : function(event) {
			HideDisabled = !$(this).prop('checked');
			refresh_collection();
		}
	});
	$('input[name=only-deck]').on({
		change : function(event) {
			ShowOnlyDeck = $(this).prop('checked');
			refresh_collection();
		}
	});
	$('input[name=display-column-1]').on({
		change : function(event) {
			$('input[name=display-column-2]').prop('checked', false);
			$('input[name=display-column-3]').prop('checked', false);
			DisplayColumns = 1;
			if (localStorage)
				localStorage.setItem('display_columns', DisplayColumns);
			refresh_collection();
		}
	});
	$('input[name=display-column-2]').on({
		change : function(event) {
			$('input[name=display-column-1]').prop('checked', false);
			$('input[name=display-column-3]').prop('checked', false);
			DisplayColumns = 2;
			if (localStorage)
				localStorage.setItem('display_columns', DisplayColumns);
			refresh_collection();
		}
	});
	$('input[name=display-column-3]').on({
		change : function(event) {
			$('input[name=display-column-1]').prop('checked', false);
			$('input[name=display-column-2]').prop('checked', false);
			DisplayColumns = 3;
			if (localStorage)
				localStorage.setItem('display_columns', DisplayColumns);
			refresh_collection();
		}
	});
	$('input[name=core-set-1]').on({
		change : function(event) {
			$('input[name=core-set-2]').prop('checked', false);
			$('input[name=core-set-3]').prop('checked', false);
			CoreSets = 1;
			if (localStorage)
				localStorage.setItem('core_sets', CoreSets);
			update_core_sets();
			refresh_collection();
		}
	});
	$('input[name=core-set-2]').on({
		change : function(event) {
			$('input[name=core-set-1]').prop('checked', false);
			$('input[name=core-set-3]').prop('checked', false);
			CoreSets = 2;
			if (localStorage)
				localStorage.setItem('core_sets', CoreSets);
			update_core_sets();
			refresh_collection();
		}
	});
	$('input[name=core-set-3]').on({
		change : function(event) {
			$('input[name=core-set-1]').prop('checked', false);
			$('input[name=core-set-2]').prop('checked', false);
			CoreSets = 3;
			if (localStorage)
				localStorage.setItem('core_sets', CoreSets);
			update_core_sets();
			refresh_collection();
		}
	});
	$('input[name=show-suggestions-0]').on({
		change : function(event) {
			$('input[name=show-suggestions-3]').prop('checked', false);
			$('input[name=show-suggestions-10]').prop('checked', false);
			app.suggestions.number = 0;
			if (localStorage)
				localStorage.setItem('show_suggestions', app.suggestions.number);
			app.suggestions.show();
		}
	});
	$('input[name=show-suggestions-3]').on({
		change : function(event) {
			$('input[name=show-suggestions-0]').prop('checked', false);
			$('input[name=show-suggestions-10]').prop('checked', false);
			app.suggestions.number = 3;
			if (localStorage)
				localStorage.setItem('show_suggestions', app.suggestions.number);
			app.suggestions.show();
		}
	});
	$('input[name=show-suggestions-10]').on({
		change : function(event) {
			$('input[name=show-suggestions-0]').prop('checked', false);
			$('input[name=show-suggestions-3]').prop('checked', false);
			app.suggestions.number = 10;
			if (localStorage)
				localStorage.setItem('show_suggestions', app.suggestions.number);
			app.suggestions.show();
		}
	});
	$('input[name=buttons-behavior-cumulative]').on({
		change : function(event) {
			$('input[name=buttons-behavior-exclusive]').prop('checked', false);
			$('input[name=buttons-behavior-exclusive]').prop('checked', false);
			Buttons_Behavior = 'cumulative';
			if (localStorage)
				localStorage.setItem('buttons_behavior', Buttons_Behavior);
		}
	});
	$('input[name=buttons-behavior-exclusive]').on({
		change : function(event) {
			$('input[name=buttons-behavior-cumulative]').prop('checked', false);
			$('input[name=buttons-behavior-cumulative]').prop('checked', false);
			Buttons_Behavior = 'exclusive';
			if (localStorage)
				localStorage.setItem('buttons_behavior', Buttons_Behavior);
		}
	});
	$('thead').on({
		click : handle_header_click
	}, 'a[data-sort]');
	$('#cardModal').on({
		keypress : function(event) {
			var num = parseInt(event.which, 10) - 48;
			$('.modal input[type=radio][value=' + num + ']').trigger('change');
		}
	});
	$('#filter-text-button')
			.tooltip(
					{
						html : true,
						container : 'body',
						placement : 'bottom',
						trigger : 'click',
						title : "<h5>Smart filter syntax</h5><ul style=\"text-align:left\"><li>x: filters on text</li><li>a: flavor text</li><li>s: subtype</li><li>o: cost</li><li>v: agenda points</li><li>n: faction cost</li><li>p: strength</li><li>g: advancement cost</li><li>h: trash cost</li><li>u: uniqueness</li><li>y: quantity in pack</li></ul><code>s:\"code gate\" x:trace</code> to find code gates with trace"
					});
	
	var converter = new Markdown.Converter();
	$('#description').on(
			'keyup',
			function() {
				$('#description-preview').html(
						converter.makeHtml($('#description').val()));
			});

	$('#description').textcomplete(
			[
					{
						match : /\B#([\-+\w]*)$/,
						search : function(term, callback) {
							callback(app.data.cards({
								name : {
									likenocase : term
								},
								cycleposition : {
									'!=': 0
								}
							}).get());
						},
						template : function(value) {
							return value.name;
						},
						replace : function(value) {
							return '[' + value.name + ']('
									+ Routing.generate('cards_zoom', {card_code:value.code})
									+ ')';
						},
						index : 1
					}, {
						match : /\$([\-+\w]*)$/,
						search : function(term, callback) {
							var regexp = new RegExp('^' + term);
							callback($.grep(['credit', 'recurring-credit', 'click', 'link', 'trash', 'subroutine', 'mu', '1mu', '2mu', '3mu', 
								'anarch', 'criminal', 'shaper', 'haas-bioroid', 'weyland-consortium', 'jinteki', 'nbn'],
								function(symbol) { return regexp.test(symbol); }
							));
						},
						template : function(value) {
							return value;
						},
						replace : function(value) {
							return '<span class="icon icon-' + value + '"></span>';
						},
						index : 1
					}
			]);
	
	$('#tbody-history').on('click', 'a[role=button]', load_snapshot);
	$.each(History, function (index, snapshot) {
		add_snapshot(snapshot);
	});
	setInterval(autosave_interval, 1000);
});
function autosave_interval() {
	if(Autosave_running) return;
	if(Autosave_timer < 0 && Deck_id) Autosave_timer = Autosave_period;
	//('#tab-header-history').html('History '+Autosave_timer);
	$('#history-timer-bar').css('width', (Autosave_timer*100/Autosave_period)+'%').attr('aria-valuenow', Autosave_timer).find('span').text(Autosave_timer+' seconds remaining.');
	if(Autosave_timer === 0) {
		deck_autosave();
	}
	Autosave_timer--;
}
// if diff is undefined, consider it is the content at load
function add_snapshot(snapshot) {
	snapshot.date_creation = snapshot.date_creation ? moment(snapshot.date_creation) : moment();
	Snapshots.push(snapshot);
	
	var list = [];
	if(snapshot.variation) {
		$.each(snapshot.variation[0], function (code, qty) {
			var card = app.data.get_card_by_code(code);
			if(!card) return; 
			list.push('+'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card" data-code="'+code+'">'+card.name+'</a>');
		});
		$.each(snapshot.variation[1], function (code, qty) {
			var card = app.data.get_card_by_code(code);
			if(!card) return; 
			list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card" data-code="'+code+'">'+card.name+'</a>');
		});
	} else {
		list.push("First version");
	}
	
	$('#tbody-history').prepend('<tr'+(snapshot.saved ? '' : ' class="warning"')+'><td>'+snapshot.date_creation.calendar()+(snapshot.saved ? '' : ' (unsaved)')+'</td><td>'+list.join('<br>')+'</td><td><a role="button" href="#" data-code="'+(Snapshots.length-1)+'"">Revert</a></td></tr>');
	
	Autosave_timer = -1; // start timer
}
function load_snapshot(event) {
	var index = $(this).data('index');
	var snapshot = Snapshots[index];
	if(!snapshot) return;
	
	app.data.cards().each(function(record) {
		var indeck = 0;
		if (snapshot.content[record.code]) {
			indeck = parseInt(snapshot.content[record.code], 10);
		}
		app.data.cards(record.___id).update({
			indeck : indeck
		});
	});
	update_deck();
	refresh_collection();
	app.suggestions.compute();
	Deck_changed_since_last_autosave = true;
	return false;
}
function deck_autosave() {
	// check if deck has been modified since last autosave
	if(!Deck_changed_since_last_autosave || !Deck_id) return;
	// compute diff between last snapshot and current deck
	var last_snapshot = Snapshots[Snapshots.length-1].content;
	var current_deck = get_deck_content();
	Deck_changed_since_last_autosave = false;
	var r = app.diff.compute_simple([current_deck, last_snapshot]);
	if(!r) return;
	var diff = JSON.stringify(r[0]);
	if(diff == '[{},{}]') return;
	// send diff to autosave
	$('#tab-header-history').html("Autosave...");
	Autosave_running = true;
	$.ajax(Routing.generate('deck_autosave', {deck_id:Deck_id}), {
		data: {diff:diff},
		type: 'POST',
		success: function(data, textStatus, jqXHR) {
			add_snapshot({datecreation: data, variation: r[0], content: current_deck, saved: false});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			Deck_changed_since_last_autosave = true;
		},
		complete: function () {
			$('#tab-header-history').html("History");
			Autosave_running = false;
		}
	});
}
function handle_header_click(event) {
	event.preventDefault();
	var new_sort = $(this).data('sort');
	if (Sort == new_sort) {
		Order *= -1;
	} else {
		Sort = new_sort;
		Order = 1;
	}
	$(this).closest('tr').find('th').removeClass('dropup').find('span.caret')
			.remove();
	$(this).after('<span class="caret"></span>').closest('th').addClass(
			Order > 0 ? '' : 'dropup');
	refresh_collection();
}
function handle_input_change(event) {
	var div = $(this).closest('.filter');
	var columnName = div.attr('id');
	var arr = [];
	div.find("input[type=checkbox]").each(
			function(index, elt) {
				if ($(elt).prop('checked'))
					arr.push($(elt).attr('name'));
				if (columnName == "set_code" && localStorage)
					localStorage.setItem('set_code_' + $(elt).attr('name'), $(
							elt).prop('checked') ? "on" : "off");
			});
	Filters[columnName] = arr;

	FilterQuery = {};
	$.each(Filters, function(k) {
		if (Filters[k] != '') {
			FilterQuery[k] = Filters[k];
		}
	});
	refresh_collection();
}
function get_deck_content() {
	var deck_content = {};
	app.data.cards({
		indeck : {
			'gt' : 0
		}
	}).each(function(record) {
		deck_content[record.code] = record.indeck;
	});
	return deck_content;
}
function handle_submit(event) {
	var deck_json = JSON.stringify(get_deck_content());
	$('input[name=content]').val(deck_json);
	$('input[name=description]').val($('textarea[name=description_]').val());
	$('input[name=tags]').val($('input[name=tags_]').val());
}

function handle_quantity_change(event) {
	var index = $(this).closest('.card-container').data('index')
			|| $(this).closest('div.modal').data('index');
	var in_collection = $(this).closest('#collection').size();
	var quantity = parseInt($(this).val(), 10);
	$(this).closest('.card-container')[quantity ? "addClass" : "removeClass"]('in-deck');
	var cards = app.data.get_cards_by_code(index);
	cards.update({indeck : quantity});
	var card = cards.first();
	if (card.type_code == "identity") {
		if (Identity.faction != card.faction) {
			// change of faction, reset agendas
			app.data.cards({
				indeck : {
					'gt' : 0
				},
				type_code : 'agenda'
			}).update({
				indeck : 0
			});
			// also automatically change tag of deck
			$('input[name=tags_]').val(
					$('input[name=tags_]').val().split(' ').map(function (tag) {
						return tag === Identity.faction_code ? card.faction_code : tag;
					}).join(' ')
			);
		}
		app.data.cards({
			indeck : {
				'gt' : 0
			},
			type_code : 'identity',
			code : {
				'!==' : index
			}
		}).update({
			indeck : 0
		});
	}
	update_deck();
	if (card.type_code == "identity") {
		app.draw_simulator.reset();
		$.each(CardDivs, function(nbcols, rows) {
			if (rows)
				$.each(rows, function(index, row) {
					row.removeClass("disabled").find('label').removeClass(
							"disabled").find('input[type=radio]').attr(
							"disabled", false);
				});
		});
		refresh_collection();
	} else {
		$.each(CardDivs, function(nbcols, rows) {
			// rows is an array of card rows
			if (rows && rows[index]) {
				// rows[index] is the card row of our card
				rows[index].find('input[name="qty-' + index + '"]').each(
					function(i, element) {
						if ($(element).val() != quantity) {
							$(element).prop('checked', false).closest(
							'label').removeClass('active');
						} else {
							if(!in_collection) {
								$(element).prop('checked', true).closest(
								'label').addClass('active');
							}
						}
					}
				);
			}
		});
	}
	$('div.modal').modal('hide');
	app.suggestions.compute();
	
	Deck_changed_since_last_autosave = true;
}

function update_core_sets() {
	CardDivs = [ null, {}, {}, {} ];
	app.data.cards({
		set_code : 'core'
	}).each(function(record) {
		var max_qty = Math.min(record.quantity * CoreSets, 3);
		if (record.type_code == "identity" || record.limited)
			max_qty = 1;
		if(Identity.faction_code == "neutral") max_qty = 9;
		app.data.cards(record.___id).update({
			maxqty : max_qty
		});
	});
}

function build_div(record) {
	var faction = record.faction_code;
	var influ = "";
	for (var i = 0; i < record.factioncost; i++)
		influ += "&bull;";

	var radios = '';
	for (var i = 0; i <= record.maxqty; i++) {
		radios += '<label class="btn btn-xs btn-default'
				+ (i == record.indeck ? ' active' : '')
				+ '"><input type="radio" name="qty-' + record.code
				+ '" value="' + i + '">' + i + '</label>';
	}

	var div = '';
	switch (DisplayColumns) {
	case 1:

		var imgsrc = record.faction_code == "neutral" ? "" : '<img src="'
				+ Url_FactionImage.replace('xxx', record.faction_code)
				+ '.png" alt="'+record.faction+'">';
		div = $('<tr class="card-container" data-code="'
				+ record.code
				+ '"><td><div class="btn-group" data-toggle="buttons">'
				+ radios
				+ '</div></td><td><a class="card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ record.name + '</a></td><td class="influence-' + faction
				+ '">' + influ + '</td><td class="type" title="' + record.type
				+ '"><img src="/web/bundles/app/images/types/'
				+ record.type_code + '.png" alt="'+record.type+'">'
				+ '</td><td class="faction" title="' + record.faction + '">'
				+ imgsrc + '</td></tr>');
		break;

	case 2:

		div = $('<div class="col-sm-6 card-container" data-code="'
				+ record.code
				+ '">'
				+ '<div class="media"><div class="media-left">'
				+ '<img class="media-object" src="/web/bundles/netrunnerdbcards/images/cards/en/'
				+ record.code
				+ '.png" alt="'+record.name+'">'
				+ '</div><div class="media-body">'
				+ '    <h4 class="media-heading"><a class="card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ record.name + '</a></h4>'
				+ '    <div class="btn-group" data-toggle="buttons">' + radios
				+ '</div>' + '    <span class="influence-' + faction + '">'
				+ influ + '</span>' + '</div>' + '</div>' + '</div>');
		break;

	case 3:

		div = $('<div class="col-sm-4 card-container" data-code="'
				+ record.code
				+ '">'
				+ '<div class="media"><div class="media-left">'
				+ '<img class="media-object" src="/web/bundles/netrunnerdbcards/images/cards/en/'
				+ record.code
				+ '.png" alt="'+record.name+'">'
				+ '</div><div class="media-body">'
				+ '    <h5 class="media-heading"><a class="card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ record.name + '</a></h5>'
				+ '    <div class="btn-group" data-toggle="buttons">' + radios
				+ '</div>' + '    <span class="influence-' + faction + '">'
				+ influ + '</span>' + '</div>' + '</div>' + '</div>');
		break;

	}

	return div;
}

function is_card_usable(record) {
	if (Identity.code == "03002"
			&& record.faction_code == "jinteki")
		return false;
	if (record.type_code == "agenda"
			&& record.faction_code != "neutral"
			&& record.faction_code != Identity.faction_code
			&& Identity.faction_code != "neutral")
		return false;
	return true;
}

function update_filtered() {
	$('#collection-table').empty();
	$('#collection-grid').empty();
	
	var counter = 0, container = $('#collection-table');
	var SmartFilterQuery = app.smart_filter.get_query(FilterQuery);
	app.data.cards.apply(window, SmartFilterQuery)
			.order(Sort + (Order > 0 ? " intl" : " intldesc") + ',name')
			.each(
					function(record) {

						if (ShowOnlyDeck && !record.indeck)
							return;

						var unusable = !is_card_usable(record);

						if (HideDisabled && unusable)
							return;

						var index = record.code;
						var row = (CardDivs[DisplayColumns][index] || (CardDivs[DisplayColumns][index] = build_div(record)))
								.data("index", record.code);
						row.find('input[name="qty-' + record.code + '"]').each(
								function(i, element) {
									if ($(element).val() == record.indeck)
										$(element).prop('checked', true)
												.closest('label').addClass(
														'active');
									else
										$(element).prop('checked', false)
												.closest('label').removeClass(
														'active');
								});

						if (unusable)
							row.find('label').addClass("disabled").find(
									'input[type=radio]').attr("disabled", true);

						if (DisplayColumns > 1
								&& counter % DisplayColumns === 0) {
							container = $('<div class="row"></div>').appendTo(
									$('#collection-grid'));
						}
						container.append(row);
						counter++;
					});
}
var refresh_collection = debounce(update_filtered, 250);
