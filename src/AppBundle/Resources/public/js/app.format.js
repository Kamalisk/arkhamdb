(function app_format(format, $) {

/**
 * @memberOf format
 */
format.traits = function traits(card) {
	return card.traits || '';
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

format.faction = function faction(card) {
	var text = '<span class="fg-'+card.faction_code+' icon-'+card.faction_code+'"></span> '+ card.faction_name + '. ';
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
	switch(card.type_code) {
		case 'agenda':
			text += '<div>Doom: '+format.fancy_int(card.doom)+'.</div>';
			break;
		case 'act':
			text += '<div>Clues: '+format.fancy_int(card.clues)+'.</div>';
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
			text += '<div>Cost: '+format.fancy_int(card.cost)+'. '+(card.xp ? "XP: "+card.xp+"." : "")+'</div>';

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
				text += '<div>Health: '+format.fancy_int(card.health)+'. Sanity: '+format.fancy_int(card.sanity)+'.</div>';
			}
			break;
		case 'skill':
			if (card.xp){
				text += '<div>'+(card.xp ? "XP: "+card.xp+"." : "")+'</div>';
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
format.text = function text(card) {
	var text = card.text || '';
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>')
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.back_text = function back_text(card) {
	var text = card.back_text || '';
	text = text.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>')
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

/**
 * @memberOf format
 */
format.html_page = function back_text(element) {
	var curInnerHTML = element.innerHTML;
	curInnerHTML = curInnerHTML.replace(/\[(\w+)\]/g, '<span title="$1" class="icon-$1"></span>')
	element.innerHTML = curInnerHTML;
};


})(app.format = {}, jQuery);
