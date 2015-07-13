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
	return (card.is_unique ? '<span class="card-unique"></span> ' : "") + card.name;
}

/**
 * @memberOf format
 */
format.pack_faction = function pack_faction(card) {
	var text = card.pack_name + ' #' + card.position + '. ';
	text += card.faction_name + '. ';
	if(card.is_loyal) text += 'Loyal. ';
	if(card.is_limited) text += 'Limited. ';
	return text;
}

/**
 * @memberOf format
 */
format.info = function info(card) {
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
		text += 'Initiative: '+card.initiative+'. ';
		text += 'Claim: '+card.claim+'. ';
		text += 'Reserve: '+card.reserve+'. ';
		text += 'Plot deck limit: '+card.plotLimit+'. ';
		break;
	}
	return text;
};

/**
 * @memberOf format
 */
format.text = function text(card) {
	var text = card.text || '';
	
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

})(app.format = {}, jQuery);
