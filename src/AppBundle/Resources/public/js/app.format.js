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
	return (card.isUnique ? '<span class="icon-unique"></span> ' : "") + card.name;
}

/**
 * @memberOf format
 */
format.pack_faction = function pack_faction(card) {
	var text = card.pack_name + ' #' + card.position + '. ';
	text += card.faction_name + '. ';
	if(card.isLoyal) text += 'Loyal. ';
	if(card.plotLimit) text += 'Plot deck limit: '+card.plotLimit+'. ';
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
		if(card.isMilitary) text += '<span class="color-military icon-military" title="Military"></span> ';
		if(card.isIntrigue) text += '<span class="color-intrigue icon-intrigue" title="Intrigue"></span> ';
		if(card.isPower) text += '<span class="color-power icon-power" title="Power"></span> ';
		break;
	case 'attachment':
	case 'location':
	case 'event':
		text += 'Cost: '+(card.cost != null ? card.cost : 'X')+'. ';
		break;
	case 'plot':
		text += 'Gold: '+card.income+'. ';
		text += 'Initiative: '+card.initiative+'. ';
		text += 'Claim: '+card.claim+'. ';
		text += 'Reserve: '+card.reserve+'. ';
		break;
	}
	return text;
};

/**
 * @memberOf format
 */
format.text = function text(card) {
	var text = card.text || '';
	text = text.replace(/\[(\w+)\]/g, '<span class="icon-$1"></span>')
	text = text.split("\n").join('</p><p>');
	return '<p>'+text+'</p>';
};

})(app.format = {}, jQuery);
