(function app_format(format, $) {

/**
 * @memberOf format
 */
format.traits = function traits(card) {
	if (card.customizations && card.customization_change) {
		var changes = card.customization_change.split('\n');
		for (var i=0; i<card.customizations.length; i++) {
			var custom = card.customizations[i];
			if (custom && custom.unlocked && custom.option.text_change === 'trait') {
				return changes[custom.index] || card.traits || '';
			}
		}
	}
	return card.traits || '';
};

/**
 * @memberOf format
 */
format.xp = function xp(xp, in_deck, css) {
	var string = "";
	if (xp && xp > 0)
	{
 		string += ' <span class="card-xp xp-'+xp+'';
 		if (css){
 			string += ' '+css;
 		}
 		string += '">'+("••••••••••••••••••".slice(-xp))+"</span>";
	}
	return string;
};


/**
 * @memberOf format
 */
format.fancy_int = function traits(num) {
	var string = (num != null ? (num < 0 ? "X" : num) : '&ndash;')
	return string;
};

/**
 * @memberOf format
 */
format.name = function name(card) {
	var name = (card.is_unique ? '<span class="icon-unique"></span> ' : "") + card.name;
	if (card.subname){
		name += '<div class="card-subname small">'+card.subname+'</div>';
	}
	return name;
}

/**
 * @memberOf format
 */
format.faction = function faction(card) {
	var text = '<span class="fg-'+card.faction_code+' icon-'+card.faction_code+'"></span> '+ card.faction_name + '. ';
	if (card.faction2_code) {
		text += '<span class="fg-'+card.faction2_code+' icon-'+card.faction2_code+'"></span> '+ card.faction2_name + '. ';
	}
	if (card.faction3_code) {
		text += '<span class="fg-'+card.faction3_code+' icon-'+card.faction3_code+'"></span> '+ card.faction3_name + '. ';
	}
	return text;
}

/**
 * @memberOf format
 */
format.pack = function pack(card) {
	var text = card.pack_name + ' #' + card.position + '. ';
	if (card.encounter_name){
		text += card.encounter_name;
		if (card.encounter_position){
			text += " #"+card.encounter_position;
			if (card.quantity > 1){
				text += "-";
				text += (card.encounter_position+card.quantity-1);
			}
		}
	}
	return text;
}

/**
 * @memberOf format
 */
format.info = function info(card) {
	var text = '';

	var cost = card.cost;
	var health = card.health;
	var sanity = card.sanity;
	var xp = card.xp;
	if (card.customizations) {
		var customization_xp = 0;
		for(var i=0; i<card.customizations.length; i++) {
			var custom = card.customizations[i];
			customization_xp = customization_xp + (custom.xp || 0);
			if (custom.unlocked) {
				if (custom.option.health) {
					health = (health || 0) + custom.option.health;
				}
				if (custom.option.sanity) {
					sanity = (sanity || 0) + custom.option.sanity;
				}
				if (custom.option.cost) {
					cost = (cost || 0) + custom.option.cost;
				}
			}
		}
		xp = Math.floor((customization_xp + 1) / 2.0);
	}
	switch(card.type_code) {
		case 'agenda':
			text += '<div>Doom: '+format.fancy_int(card.doom)+'.</div>';
			break;
		case 'act':
			text += '<div>Clues: '+format.fancy_int(card.clues)+'.</div>';
			break;
		case 'location':
			if (card.clues_fixed || card.clues == 0){
				text += '<div>Shroud: '+format.fancy_int(card.shroud)+'. Clues: '+format.fancy_int(card.clues)+'.</div>';
			} else {
				text += '<div>Shroud: '+format.fancy_int(card.shroud)+'. Clues: '+format.fancy_int(card.clues)+'<span class="icon icon-per_investigator"></span>.</div>';
			}
			break;
		case 'enemy':
			text += '<div>Fight: '+format.fancy_int(card.enemy_fight)+'. Health: '+format.fancy_int(card.health)+'';
			if (card.health_per_investigator){
				text += '<span class="icon icon-per_investigator"></span>';
			}
			text += '. Evade: '+format.fancy_int(card.enemy_evade)+'.</div>';
			text += '<div>Damage: '+format.fancy_int(card.enemy_damage)+'. Horror: '+format.fancy_int(card.enemy_horror)+'.</div>';
			break;
		case 'investigator':
			text += '<div>Willpower: '+card.skill_willpower+'. Intellect: '+card.skill_intellect+'. Combat: '+card.skill_combat+'. Agility: '+card.skill_agility+'.</div>';
			text += '<div>Health: '+card.health+'. Sanity: '+card.sanity+'.</div>'
			break;
		case 'asset':
		case 'event':
			text += '<div>Cost: '+format.fancy_int(cost)+'. '+(xp ? "XP: "+xp+"." : "")+'</div>';

			if (card.skill_willpower || card.skill_intellect || card.skill_combat || card.skill_agility || card.skill_wild){
				text += '<div>Test Icons: ';
				if (card.skill_willpower){
					text += Array(card.skill_willpower+1).join('<span class="icon icon-willpower color-willpower"></span>');
				}
				if (card.skill_intellect){
					text += Array(card.skill_intellect+1).join('<span class="icon icon-intellect color-intellect"></span>');
				}
				if (card.skill_combat){
					text += Array(card.skill_combat+1).join('<span class="icon icon-combat color-combat"></span>');
				}
				if (card.skill_agility){
					text += Array(card.skill_agility+1).join('<span class="icon icon-agility color-agility"></span>');
				}
				if (card.skill_wild){
					text += Array(card.skill_wild+1).join('<span class="icon icon-wild color-wild"></span>');
				}
				text += '</div>';
			}
			if (card.health || card.sanity){
				text += '<div>Health: '+format.fancy_int(health)+'. Sanity: '+format.fancy_int(sanity)+'.</div>';
			}
			break;
		case 'skill':
			if (xp){
				text += '<div>'+(xp ? "XP: "+xp+"." : "")+'</div>';
			}
			if (card.skill_willpower || card.skill_intellect || card.skill_combat || card.skill_agility || card.skill_wild){
				text += '<div>Test Icons: ';
				if (card.skill_willpower){
					text += Array(card.skill_willpower+1).join('<span class="icon icon-willpower color-willpower"></span>');
				}
				if (card.skill_intellect){
					text += Array(card.skill_intellect+1).join('<span class="icon icon-intellect color-intellect"></span>');
				}
				if (card.skill_combat){
					text += Array(card.skill_combat+1).join('<span class="icon icon-combat color-combat"></span>');
				}
				if (card.skill_agility){
					text += Array(card.skill_agility+1).join('<span class="icon icon-agility color-agility"></span>');
				}
				if (card.skill_wild){
					text += Array(card.skill_wild+1).join('<span class="icon icon-wild color-wild"></span>');
				}
				text += '</div>';
			}
			break;
	}
	return text;
};

/**
 * @memberOf format
 */
format.slot = function slot(card) {
	var slot = card.slot;
	if (card.customizations) {
		for (var i=0; i<card.customizations.length; i++) {
			var custom = card.customizations[i];
			var option = custom.option;
			if (custom.unlocked && option.choice === 'remove_slot') {
				const choice = parseInt(custom.choice || '0', 10) || 0;
				var slots = slot.split('.');
				var new_slots = [];
				for (let j=0; j<slots.length; j++) {
					if (j !== choice) {
						new_slots.push(slots[j].trim());
					}
				}
				slot = new_slots.join('. ');
			}
		}
	}
	return slot;
}

/**
 * @memberOf format
 */
format.text = function text(card, alternate) {
	var text = card.text || '';
	if (alternate){
		text = card[alternate];
	}
	if (card.customizations) {
		var lines = text.split('\n');
		var changes = (card.customization_change || '').split('\n');
		for (var i=0; i<card.customizations.length; i++) {
			var custom = card.customizations[i];
			var option = custom.option;
			var change = changes[custom.index] || '';
			if (custom.unlocked && option.text_change) {
				switch (option.text_change) {
					case 'replace': {
						lines[option.position || 0] = change || '';
						break;
					}
					case 'append': {
						lines.push(change || '');
						break;
					}
					default: {
						// Delay handling inserts for now.
						break;
					}
				}
			}
		}
		for (var i=0; i<card.customizations.length; i++) {
			var custom = card.customizations[i];
			var option = custom.option;
			var change = changes[custom.index] || '';
			if (custom.unlocked && option.text_change === 'insert') {
				lines.splice(option.position || 0, 0, change || '');
			}
		}
		text = lines.join('\n');
	}
	text = text.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
 format.customization_text = function customization_text(card) {
	var text = card.customization_text || '';
	text = text.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.back_text = function back_text(card) {
	var text = card.back_text || '';
	text = text.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>')
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.html_page = function back_text(element) {
	var curInnerHTML = element.innerHTML;
	curInnerHTML = curInnerHTML.replace(/\[\[([^\]]+)\]\]/g, '<b><i>$1</i></b>');
	curInnerHTML = curInnerHTML.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>');
	element.innerHTML = curInnerHTML;
};


})(app.format = {}, jQuery);
