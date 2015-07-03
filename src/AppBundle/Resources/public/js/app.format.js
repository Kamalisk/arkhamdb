if (typeof app != "object")
	var app = { data_loaded: jQuery.Callbacks() };
app.format = {};
(function(format, $) {
	
	format.type = function(card) {
		var type = '<span class="card-type">'+card.type+'</span>';
		if(card.subtype) type += '<span class="card-traits">: '+card.subtype+'</span>';
		if(card.type_code == "agenda") type += ' &middot; <span class="card-prop">'+card.advancementcost+'/'+card.agendapoints+'</span>';
		if(card.type_code == "identity" && card.side_code == "corp") type += ' &middot; <span class="card-prop">'+card.minimumdecksize+'/'+card.influencelimit+'</span>';
		if(card.type_code == "identity" && card.side_code == "runner") type += ' &middot; <span class="card-prop">'+card.minimumdecksize+'/'+card.influencelimit+' '+card.baselink+'<span class="icon icon-link"></span></span>';
		if(card.type_code == "operation" || card.type_code == "event") type += ' &middot; <span class="card-prop">'+card.cost+'<span class="icon icon-credit"></span></span>';
		if(card.type_code == "resource" || card.type_code == "hardware") type += ' &middot; <span class="card-prop">'+card.cost+'<span class="icon icon-credit"></span></span>';
		if(card.type_code == "program") type += ' &middot; <span class="card-prop">'+card.cost+'<span class="icon icon-credit"></span> '+card.memoryunits+'<span class="icon icon-mu"></span></span>';
		if(card.type_code == "asset" || card.type_code == "upgrade") type += ' &middot; <span class="card-prop">'+card.cost+'<span class="icon icon-credit"></span> '+card.trash+'<span class="icon icon-trash"></span></span>';
		if(card.type_code == "ice") type += ' &middot; <span class="card-prop">'+card.cost+'<span class="icon icon-credit"></span></span>';
		return type;
	};

	format.text = function(card) {
		var text = card.text;
		
		text = text.replace(/\[Subroutine\]/g, '<span class="icon icon-subroutine"></span>');
		text = text.replace(/\[Credits\]/g, '<span class="icon icon-credit"></span>');
		text = text.replace(/\[Trash\]/g, '<span class="icon icon-trash"></span>');
		text = text.replace(/\[Click\]/g, '<span class="icon icon-click"></span>');
		text = text.replace(/\[Recurring Credits\]/g, '<span class="icon icon-recurring-credit"></span>');
		text = text.replace(/\[Memory Unit\]/g, '<span class="icon icon-mu"></span>');
		text = text.replace(/\[Link\]/g, '<span class="icon icon-link"></span>');
		text = text.split("\n").join('</p><p>');
		return '<p>'+text+'</p>';
	};

})(app.format, jQuery);
