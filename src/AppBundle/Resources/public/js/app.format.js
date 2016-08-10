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
	return (card.is_unique ? '<span class="icon-unique"></span> ' : "") + card.name;
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
		break;	
	case 'asset':
	case 'event':
		text += 'Cost: '+(card.cost != null ? card.cost : 'X')+'. ';
		break;
	case 'skill':
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
