(function app_card_modal(card_modal, $) {

var modal = null;

/**
 * @memberOf card_modal
 */
card_modal.display_modal = function display_modal(event, element) {
	event.preventDefault();
	$(element).qtip('destroy', true);
	fill_modal($(element).data('code'));
};

/**
 * @memberOf card_modal
 */
card_modal.typeahead = function typeahead(event, card) {
	fill_modal(card.code);
	$('#cardModal').modal('show');
};

/**
 * @memberOf card_modal
 */
card_modal.update_modal = function update_modal(card) {
	var modal = $('#cardModal');
	update_qty(modal, card);
	update_info(modal, card);
	update_customizations(modal, card);
}

function update_info(modal, card) {
	modal.find('.modal-info').html(
		'<div class="card-faction">' + app.format.faction(card) + (card.slot ? (' ' + app.format.slot(card)) : '') + '</div>'
		+ '<div class="card-info">' + app.format.info(card) + '</div>'
		+ '<div class="card-traits">' + app.format.traits(card) + '</div>'
		+ '<div class="card-text border-'+card.faction_code+'">' + app.format.text(card) + '</div>'
		+ (card.taboo_text ? '<div class="card-text-taboo border-'+card.faction_code+'">' + app.format.text(card, "taboo_text") + '</div>' : '')
		+ (card.taboo_xp ? '<div class="card-text-taboo border-'+card.faction_code+'">This card costs ' + card.taboo_xp + ' additional experience</div>' : '')
		+ '<div class="card-pack">' + app.format.pack(card) + '</div>'
	);
}

function update_qty(modal, card) {
	var qtyelt = modal.find('.modal-qty');
	if(qtyelt && card.maxqty) {
		var qty = '';
		for(var i=0; i<=card.maxqty; i++) {
			qty += '<label class="btn btn-sm btn-default"><input class="qty" type="radio" name="qty" value="'+i+'">'+i+'</label>';
		}
		qtyelt.html(qty);

		qtyelt.find('label').each(function (index, element) {
			if(index == card.indeck) $(element).addClass('active');
			else $(element).removeClass('active');
		});
		qtyelt.closest('.row').show();
	} else {
		if(qtyelt) qtyelt.closest('.row').hide();
	}
}

function make_customization_subchoice(card, index, option, choice, editable, stat_choices) {
	if (!option.choice) {
		return { html: '' };
	}
	if (option.xp && (!choice || choice.xp < option.xp)) {
		return { html: '' };
	}
	var id = 'custom_choice_' + index;
	switch (option.choice) {
		case 'choose_card': {
			editable = editable && (!choice || !choice.locked);
			var chosen_cards = (choice && choice.choice) ? choice.choice.split('^') : [];
			var r = '<div style="display:flex; flex-direction: column; margin-left: 8px; margin-top: 4px; margin-bottom: 8px;">';
			for (var i =0; i<chosen_cards.length; i++) {
				var code = chosen_cards[i];
				var chosen_card = app.data.cards.findById(code);
				r = r + '<div>- ' + (chosen_card ? chosen_card.name : 'Unknown Card');
				if (editable) {
					r = r + '<button style="margin-left: 8px; margin-bottom: 2px;" class="remove-card-choice" value="' + index + '|' + i + '">X</button>';
				}
				r = r + '</div>';
			}

			var show_search = editable && (chosen_cards.length < option.quantity);
			if (show_search) {
				r = r + '<div><input class="form-control" id="' + id + '" name="' + id + '"' + ((choice && choice.locked) || !editable ? ' disabled' : '') + '/></div>';
			}
			r = r + '</div>';
			return {
				html: r,
				build: show_search ? function() {
					function findCustomizationMatches(q, cb) {
						if(q.match(/^\w:/)) return;
						var regexp = new RegExp(q, 'i');
						var cards = app.data.cards.find({name: regexp});
						var matching_cards = [];
						for (var i=0; i<cards.length; i++) {
							var card = cards[i];
							if (!card) {
								continue;
							}
							if (card.duplicate_of_code) {
								continue;
							}
							if (!option.card) {
								// No condition;
								matching_cards.push(card);
								continue;
							}
							if (option.card.type) {
								var type_matches = false;
								for (var j=0; j<option.card.type.length; j++) {
									if (option.card.type[j] === card.type_code) {
										type_matches = true;
										break;
									}
								}
								if (!type_matches) {
									continue;
								}
							}
							if (option.card.trait) {
								if (!card.real_traits) {
									continue;
								}
								var trait_matches = false;
								for (var j=0; j<option.card.trait.length; j++) {
									if (card.real_traits.toLowerCase().indexOf(option.card.trait[j].toLowerCase()) !== -1) {
										trait_matches = true;
										break;
									}
								}
								if (!trait_matches) {
									continue;
								}
							}
							matching_cards.push(card);
						}
						cb(matching_cards);
					}
					$('#' + id).typeahead({
						hint: true,
						highlight: true,
						minLength: 2
					},{
						name : 'customization',
						display: function(data) {
							return data.name;
						},
						source: findCustomizationMatches,
						templates: {
							suggestion: function (data){
								var value = data.name;
								if (data.xp && data.xp > 0) {
									value = value+' ('+data.xp+')';
								}
								if (data.subname) {
									if (data.type_code === 'treachery') {
										value = value + ' (' + data.subname + ')'
									} else {
										value = value + ' - <i>' + data.subname + '</i>';
									}
								}
								return '<div>' + value + '</div>';
							}
						}
					});
					$('#' + id).on('typeahead:selected typeahead:autocompleted', function(event, chosen_card) {
						app.ui.on_customization_change(card.code, index, option.xp,
							choice && choice.choice ? choice.choice + '^' + chosen_card.code : chosen_card.code
						);
					});
				} : undefined,
			};
		}
		case 'choose_trait': {
			editable = editable && (!choice || !choice.locked);
			var chosen_traits = (choice && choice.choice) ? choice.choice.split('^') : [];
			var r = '<div style="display:flex; flex-direction: column; margin-left: 8px; margin-top: 4px; margin-bottom: 8px;">';
			for (var i =0; i<chosen_traits.length; i++) {
				var trait = chosen_traits[i];
				r = r + '<div>- ' + trait;
				if (editable) {
					r = r + '<button style="margin-left: 8px; margin-bottom: 2px;" class="remove-card-choice" value="' + index + '|' + i + '">X</button>';
				}
				r = r + '</div>';
			}

			var show_search = editable && (chosen_traits.length < option.quantity);
			if (show_search) {
				r = r + '<div style="display:flex; flex-direction: row;">' +
					'<input type="text" class="form-control" id="' + id + '" name="' + id + '"' + ((choice && choice.locked) || !editable ? ' disabled' : '') + '/>' +
					'<button style="margin-left: 8px; margin-bottom: 2px; display: none;" id="' + id + '_done">Done</button>' +
					'</div>';
			}
			r = r + '</div>';
			return {
				html: r,
				build: show_search ? function() {
					$('#' + id).on('focus', function(event) {
						$('#' + id + '_done').show();
					});
					$('#' + id).on('blur', function(event) {
						var trait = event.target.value.trim().replace(/[\^|,]/g, '');
						if (trait) {
							app.ui.on_customization_change(card.code, index, option.xp,
								choice && choice.choice ? choice.choice + '^' + trait : trait
							);
						}
						$('#' + id + '_done').hide();
					});
					$('#' + id).on('keypress', function(event) {
						if (event.key === "Enter") {
							event.preventDefault();
							var trait = event.target.value.trim().replace(/[\^|,]/g, '');
							if (trait) {
								app.ui.on_customization_change(card.code, index, option.xp,
									choice && choice.choice ? choice.choice + '^' + trait : trait
								);
							}
						}
					});
				} : undefined,
			};
		}
		case 'remove_slot': {
			var r = '<div style="display:flex; flex-direction: row; margin-left: 8px; margin-top: 4px; margin-bottom: 8px;">' +
				'<select class="form-control" id="' + id + '" name="' + id + '"' + ((choice && choice.locked) || !editable ? ' disabled' : '') + '>';
			var real_slots = (card.real_slot || '').split('.');
			var slots = (card.slot || '').split('.');
			for (var i=0; i<real_slots.length; i++) {
				var selected=(!choice || !choice.choice ? 0 : parseInt(choice.choice, 10)) === i;
				var real_slot = real_slots[i].trim();
				if (!real_slot) {
					break;
				}
				var slot = (slots[i] || real_slot).trim();
				r += '<option value="' + index + '|' + (option.xp || 0) + '|' + i + '"' + (selected ? ' selected' : '') + '>' + slot + '</option>';
			}
			r += '</select></div>';
			return {
				html: r,
			};
		}

		case 'choose_skill': {
			var r = '<div style="display:flex; flex-direction: row; margin-left: 8px; margin-top: 4px; margin-bottom: 8px;">' +
				'<div class="btn-group" data-toggle="buttons">';
			var all_stats = ['willpower', 'intellect', 'combat', 'agility'];
			var locked = (choice && choice.locked) || !editable;
			if (locked) {
				var stat = (choice && choice.choice) || 'wild';
				r += '<span title="' + stat + '" class="icon-' + stat + '"></span>';
			} else {
				for (var i=0; i<all_stats.length; i++) {
					var stat = all_stats[i];
					var selected=(!choice || !choice.choice ? 0 : parseInt(choice.choice, 10)) === i;
					var already_selected = false;
					for (var j=0; j<stat_choices.length; j++) {
						if (stat_choices[j].index !== index && stat_choices[j].choice === stat) {
							already_selected = true;
							break;
						}
					}
					if (!already_selected && (!locked || selected)) {
						r += '<label class="btn btn-sm btn-default' + (choice && choice.choice === stat ? ' active' : '') + '"><input class="customize" type="radio" name="' + id + '"' + ' value="' + index + '|' + (option.xp || 0) + '|' + stat + '"' + '><span title="' + stat + '" class="icon-' + stat + '"></span></input></label>';
					}
				}
			}
			r += '</div></div>';
			return {
				html: r,
			};
		}
		default:
			return {
				html: '',
			};
	}
}

function update_customizations(modal, card) {
	var build_controls = [];
	var customization_html = '';
	if(card.customization_options && card.customization_text) {
		customization_html += '<h4 class="modal-title">Customizations</h4>';
		if (card.indeck) {
			var stat_choices = [];
			for (var i=0; i<card.customization_options.length; i++) {
				var option=card.customization_options[i];
				if (option.choice === 'choose_skill') {
					var choice=null;
					if (card.customizations) {
						for(var j=0; j<card.customizations.length; j++) {
							if (card.customizations[j].index === i) {
								choice = card.customizations[j];
								break;
							}
						}
					}
					if (choice) {
						stat_choices.push(choice);
					}
				}
			}
			var lines = card.customization_text.split('\n');
			customization_html += '<div class="card-text border-'+card.faction_code+'">';
			for(var i=0; i<card.customization_options.length; i++) {
				var option=card.customization_options[i];
				var choice=null;
				if (card.customizations) {
					for(var j=0; j<card.customizations.length; j++) {
						if (card.customizations[j].index === i) {
							choice = card.customizations[j];
							break;
						}
					}
				}
				var chosen_xp=(choice && choice.xp) || 0;
				var locked_xp=(choice && choice.locked_xp) || 0;
				var line = lines[i] || '';
				line = line.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
				line = line.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
				var control = make_customization_subchoice(card, i, option, choice, !!card.maxqty, stat_choices);
				if (control.html) {
					line = line.replace(/:.*$/,'') + ': ';
				}
				if (card.maxqty) {
					// Edit mode
					line = line.replace(/□+\s?/, '');
					customization_html += '<div style="display:flex; flex-direction: row; align-items:flex-start;' + (!option.xp ? ' margin-left: 8px;' : '') + '">';

					for(var j=0; j<option.xp; j++) {
						var name = 'custom_' + i;
						var chosen = chosen_xp > j;
						var locked = locked_xp > j;
						var style = (j === 0 ? 'margin-left: 8px;' : 'margin-left: 2px;') + (j + 1 === option.xp ? 'margin-right: 8px;' : '');
						customization_html += '<input style="' + style + '" type="checkbox" id="' + name + '" name="' + name + '" value="' + i +'|' + j + '"' + (chosen ? ' checked' : '') + (locked ? ' disabled' : '') + '></input>';
					}
					customization_html += '<label for="custom_' + i + '">' + line + '</label>' + '</div>';
				} else {
					for(var j=0; j<chosen_xp; j++) {
						line = line.replace('□', '☑');
					}
					customization_html += '<div style="margin-left: 8px">' + line + '</div>';
				}

				if (control.html) {
					customization_html += '' + control.html + '';
					if (control.build) {
						build_controls.push(control.build);
					}
				}
			}
			customization_html += '</div>';
		} else {
			// Show all the customizations since we are in card view mode (not in the deck at all yet).
			customization_html += '<hr/><div class="card-text border-'+card.faction_code+'">' + app.format.customization_text(card) + '</div>';
		}
	}
	modal.find('.modal-customization').html(customization_html);
	for (var i=0; i<build_controls.length; i++) {
		// Now build the advanced controls once we have set the HTML.
		build_controls[i]();
	}
}

function fill_modal (code) {
	var card = app.data.cards.findById(code),
		modal = $('#cardModal');

	if(!card) return;

	modal.data('code', code);
	modal.find('.card-modal-link').attr('href', card.url);
	modal.find('.card-modal-link').attr('target', '_blank');
	modal.find('h3.modal-title').html(app.format.name(card));
	modal.find('.modal-image').html('<img class="img-responsive" src="'+card.imagesrc+'">');

	update_info(modal, card);
	update_customizations(modal, card);
	update_qty(modal, card);

	var qtyelt = modal.find('.modal-ignore');
	if(qtyelt && card.maxqty && (card.code == "05040" || (card.real_traits && (card.real_traits.indexOf('Spell.') !== -1 || card.real_traits.indexOf('Fortune.') !== -1 || card.real_traits.indexOf('Gambit.') !== -1 )))) {
		qtyelt.closest('.modal-deck-ignore').show();
		var qty = '';
		for(var i=0; i<=card.maxqty; i++) {
			if (card.ignore == i) {
				qty += '<label class="btn btn-sm btn-default active"><input class="qty" type="radio" name="ignoreqty" value="'+i+'">'+i+'</label>';
			} else {
				qty += '<label class="btn btn-sm btn-default"><input class="qty" type="radio" name="ignoreqty" value="'+i+'">'+i+'</label>';
			}
		}
		qtyelt.html(qty);
	} else {
		if(qtyelt) qtyelt.closest('.modal-deck-ignore').hide();
	}

	var qtyelt = modal.find('.modal-side-qty');
	if(qtyelt && card.maxqty) {
		qtyelt.closest('.modal-side-deck-qty').show();
		var qty = '';
		for(var i=0; i<=card.maxqty; i++) {
			if (card.insidedeck == i) {
				qty += '<label class="btn btn-sm btn-default active"><input class="qty" type="radio" name="sideqty" value="'+i+'">'+i+'</label>';
			} else {
				qty += '<label class="btn btn-sm btn-default"><input class="qty" type="radio" name="sideqty" value="'+i+'">'+i+'</label>';
			}
		}
		qtyelt.html(qty);
	} else {
		if(qtyelt) qtyelt.closest('.modal-side-deck-qty').hide();
	}
}

$(function () {

	$('body').on({click: function (event) {
		var element = $(this);
		if(event.shiftKey || event.altKey || event.ctrlKey || event.metaKey) {
			event.stopPropagation();
			return;
		}
		card_modal.display_modal(event, element);
	}}, '.card');

})

})(app.card_modal = {}, jQuery);
