(function(format, $) {
	
	format.traits = function(card) {
		return card.traits || '';
	};
	
	format.name = function(card) {
		return (card.is_unique ? '<span class="card-unique"></span> ' : "") + card.name;
	}
	
	format.pack_faction = function(card) {
		var text = card.pack_name + ' #' + card.position + '. ';
		text += card.faction_name + '. ';
		if(card.is_loyal) text += 'Loyal. ';
		if(card.is_limited) text += 'Limited. ';
		return text;
	}
	
	format.info = function(card) {
		var text = '<span class="card-type">'+card.type_name+'. </span>';
		switch(card.type_code) {
		case 'character':
			text += 'Cost: '+(card.cost != null ? card.cost : 'X')+'. ';
			text += 'STR: '+(card.strength != null ? card.strength : 'X')+'. '
			if(card.is_military) text += '<span class="color-military">[military]</span>';
			if(card.is_intrigue) text += '<span class="color-intrigue">[intrigue]</span>';
			if(card.is_power) text += '<span class="color-power">[power]</span>';
			break;
		case 'attachment':
		case 'location':
		case 'event':
			text += 'Cost: '+(card.cost != null ? card.cost : 'X')+'. ';
			break;
		case 'plot':
			text += 'Income: '+card.income+'. ';
			text += 'Claim: '+card.claim+'. ';
			text += 'Initiative: '+card.initiative+'. ';
			text += 'Reserve: '+card.reserve+'. ';
			break;
		}
		return text;
	};

	format.text = function(card) {
		var text = card.text;
		
		text = text.split("\n").join('</p><p>');
		return '<p>'+text+'</p>';
	};

})(app.format = {}, jQuery);
