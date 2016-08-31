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
			text += '<div>Doom: '+card.doom+'.</div>';
			break;
		case 'act':
			text += '<div>Clues: '+card.clues+'.</div>';
			break;
		case 'enemy':
			text += '<div>Fight: '+card.enemy_fight+'. Health: '+card.health+'. Evade: '+card.enemy_evade+'.</div>';
			text += '<div>Damage: '+card.enemy_damage+'. Horror: '+card.enemy_horror+'.</div>';
			break;
		case 'investigator':
			text += '<div>Will: '+card.will+'. Lore: '+card.lore+'. Strength: '+card.strength+'. Agility: '+card.agility+'.</div>';
			text += '<div>Health: '+card.health+'. Sanity: '+card.sanity+'.</div>'
			break;	
		case 'asset':
		case 'event':
			text += '<div>Cost: '+(card.cost != null ? (card.cost < 0 ? "X" : card.cost) : 'None')+'. '+(card.xp ? "XP: "+card.xp+"." : "")+'</div>';

			if (card.will || card.lore || card.strength || card.agility || card.wild){
				text += '<div>Test Icons: ';
				if (card.will){
					text += Array(card.will+1).join('<span class="icon icon-will color-will"></span>');
				}
				if (card.lore){
					text += Array(card.lore+1).join('<span class="icon icon-lore color-lore"></span>');
				}
				if (card.strength){
					text += Array(card.strength+1).join('<span class="icon icon-strength color-strength"></span>');
				}
				if (card.agility){
					text += Array(card.agility+1).join('<span class="icon icon-agility color-agility"></span>');
				}
				if (card.wild){
					text += Array(card.wild+1).join('<span class="icon icon-wild color-wild"></span>');
				}
				text += '</div>';
			}
			if (card.health || card.sanity){
				text += '<div>Health: '+(card.health ? card.health : "None")+'. Sanity: '+(card.sanity ? card.sanity : "None")+'.</div>';
			}
			break;
		case 'skill':
			if (card.xp){
				text += '<div>'+(card.xp ? "XP: "+card.xp+"." : "")+'</div>';
			}
			if (card.will || card.lore || card.strength || card.agility || card.wild){
				text += '<div>Test Icons: ';
				if (card.will){
					text += Array(card.will+1).join('<span class="icon icon-will color-will"></span>');
				}
				if (card.lore){
					text += Array(card.lore+1).join('<span class="icon icon-lore color-lore"></span>');
				}
				if (card.strength){
					text += Array(card.strength+1).join('<span class="icon icon-strength color-strength"></span>');
				}
				if (card.agility){
					text += Array(card.agility+1).join('<span class="icon icon-agility color-agility"></span>');
				}
				if (card.wild){
					text += Array(card.wild+1).join('<span class="icon icon-wild color-wild"></span>');
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

})(app.format = {}, jQuery);
