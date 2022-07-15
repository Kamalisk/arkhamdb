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
		'<div class="card-faction">' + app.format.faction(card) + '</div>'
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
			qty += '<label class="btn btn-sm btn-default"><input type="radio" name="qty" value="'+i+'">'+i+'</label>';
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

function make_customization_subchoice(card, index, option, choice, editable) {
	if (!option.choice) {
		return '';
	}
	if (option.xp && (!choice || choice.xp < option.xp)) {
		return '';
	}
	var id = "custom_choice_" + index;
	switch (option.choice) {
		case 'remove_slot': {
			var r = '<select class="form-control" id="' + id + '" name="' + id + '"' + ((choice && choice.locked) || !editable ? ' disabled' : '') + '>';
			var real_slots = (card.real_slot || '').split(".");
			var slots = (card.slot || '').split(".");
			for (var i=0; i<real_slots.length; i++) {
				var selected=(!choice || !choice.choice ? 0 : parseInt(choice.choice, 10)) === i;
				var real_slot = real_slots[i].trim();
				if (!real_slot) {
					break;
				}
				var slot = (slots[i] || real_slot).trim();
				r += '<option value="' + index + '|' + (option.xp || 0) + '|' + i + '"' + (selected ? ' selected' : '') + '>' + slot + '</option>';
			}
			r += '</select>';
			return r;
		}
		default:
			return '';
	}
}

function update_customizations(modal, card) {
	var customization_html = '';
	if(card.customization_options && card.customization_text) {
		customization_html += '<h4 class="modal-title">Customizations</h4>';
		if (card.indeck) {
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
				var line=lines[i];
				line = line.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
				line = line.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
				var control = make_customization_subchoice(card, i, option, choice, !!card.maxqty);
				if (control) {
					line = line.replace(/:.*$/,'') + ': ';
				}
				if (card.maxqty) {
					// Edit mode
					line = line.replace(/□+ /, '');
					customization_html += '<div style="display:flex; flex-direction: row; align-items:flex-start;">';

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

				if (control) {
					customization_html += '<div style="display:flex; flex-direction: row; margin-left: 8px; margin-top: 4px; margin-bottom: 8px;">' + control + '</div>';
				}
			}
			customization_html += '</div>';
		} else {
			// Show all the customizations since we are in card view mode (not in the deck at all yet).
			customization_html += '<hr/><div class="card-text border-'+card.faction_code+'">' + app.format.customization_text(card) + '</div>';
		}
	}
	modal.find('.modal-customization').html(customization_html);
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
				qty += '<label class="btn btn-sm btn-default active"><input type="radio" name="ignoreqty" value="'+i+'">'+i+'</label>';
			} else {
				qty += '<label class="btn btn-sm btn-default"><input type="radio" name="ignoreqty" value="'+i+'">'+i+'</label>';
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
				qty += '<label class="btn btn-sm btn-default active"><input type="radio" name="sideqty" value="'+i+'">'+i+'</label>';
			} else {
				qty += '<label class="btn btn-sm btn-default"><input type="radio" name="sideqty" value="'+i+'">'+i+'</label>';
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
