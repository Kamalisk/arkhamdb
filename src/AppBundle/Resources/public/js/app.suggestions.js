(function app_suggestions(suggestions, $) {

suggestions.codesFromindex = [];
suggestions.matrix = [];
suggestions.indexFromCodes = {};
suggestions.current = [];
suggestions.exclusions = [];
suggestions.number = 3;

/**
 * @memberOf suggestions
 */
suggestions.query = function query(side) {
	suggestions.promise = $.ajax('/'+side+'.json', {
		dataTYpe: 'json',
		success: function (data) {
			suggestions.codesFromindex = data.index;
			suggestions.matrix = data.matrix;
			// reconstitute the full matrix from the lower half matrix
			for(var i=0; i<suggestions.matrix.length; i++) {
				for(var j=i; j<suggestions.matrix.length; j++) {
					suggestions.matrix[i][j] = suggestions.matrix[j][i];
				}
			}
			for(var i=0; i<suggestions.codesFromindex.length; i++) {
				suggestions.indexFromCodes[suggestions.codesFromindex[i]] = i;
			}
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
		}
	});
	suggestions.promise.done(suggestions.compute);
};

/**
 * @memberOf suggestions
 */
suggestions.compute = function compute() {
	if(suggestions.number)
	{

		// init current suggestions
		suggestions.codesFromindex.forEach(function (code, index) {
			suggestions.current[index] = {
				code: code,
				proba: 0
			};
		});
		// find used cards
		var indexes = app.data.cards.find({"indeck":{'$gt':0}}).map(function (card) {
			return suggestions.indexFromCodes[card.code];
		});
		//console.log(indexes);
		// add suggestions of all used cards
		indexes.forEach(function (i) {
			if(suggestions.matrix[i]) {
				suggestions.matrix[i].forEach(function (value, j) {
					suggestions.current[j].proba += (value || 0);
				});
			}
		});
		// remove suggestions of already used cards
		indexes.forEach(function (i) {
			if(suggestions.current[i]) suggestions.current[i].proba = 0;
		});
		// remove suggestions of identity
		app.data.cards.find({type_code:'character'}).map(function (card) {
			return suggestions.indexFromCodes[card.code];
		}).forEach(function (i) {
			if(suggestions.current[i]) suggestions.current[i].proba = 0;
		});
		// remove suggestions of excluded cards
		suggestions.exclusions.forEach(function (i) {
			suggestions.current[i].proba = 0;
		});
		// sort suggestions
		suggestions.current.sort(function (a, b) {
			return (b.proba - a.proba);
		});

	}
	suggestions.show();
};

/**
 * @memberOf suggestions
 */
suggestions.show = function show() {
	var table = $('#table-suggestions');
	var tbody = table.children('tbody');

	tbody.empty();
	if(!suggestions.number && table.is(':visible')) {
		table.hide();
		return;
	}
	if(suggestions.number && !table.is(':visible')) {
		table.show();
	}
	var nb = 0;
	for(var i=0; i<suggestions.current.length; i++) {
		var card = app.data.cards.findById(suggestions.current[i].code);
		if ($('input[name="'+card.pack_code+'"]').is(":checked")){
			if(app.deck.can_include_card(card) && !card.indeck && card.xp === 0) {
				var div = suggestions.div(card);
				div.on('click', 'button.close', suggestions.exclude.bind(this, card.code));
				tbody.append(div);
				if(++nb == suggestions.number) {
					break;
				}
			}
		}
	}
};

/**
 * @memberOf suggestions
 */
suggestions.div = function div(record) {
	var faction = record.faction_code;
	var influ = "";
	for (var i = 0; i < record.factioncost; i++)
		influ += "&bull;";

	var radios = '';
	for (var i = 0; i <= record.maxqty; i++) {
		radios += '<label class="btn btn-xs btn-default'
				+ (i == record.indeck ? ' active' : '')
				+ '"><input type="radio" name="qty-' + record.code
				+ '" value="' + i + '">' + i + '</label>';
	}
	var div = $('<tr class="card-container" data-code="'
				+ record.code
				+ '"><td><button type="button" class="close"><span aria-hidden="true">&times;</span><span class="sr-only">Remove</span></button></td>'
				+ '<td><div class="btn-group" data-toggle="buttons">'
				+ radios
				+ '</div></td><td><a class="card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ record.name + '</a></td><td class="influence-' + faction
				+ '">' + influ + '</td><td class="type" title="' + record.type
				+ '">'
				+ '</td></tr>');

	return div;
};

/**
 * @memberOf suggestions
 */
suggestions.exclude = function exclude(code) {
	suggestions.exclusions.push(suggestions.indexFromCodes[code]);
	suggestions.compute();
};

/**
 * @memberOf suggestions
 */
suggestions.pick = function pick(event) {
	InputByTitle = false;
	var input = this;
	$(input).closest('tr').animate({
		opacity: 0.1
	}, function() {
		app.ui.on_suggestion_quantity_change.call(this, event);
	});
};

$(function() {
	//suggestions.query("base");

	//console.log("suggestions fired");

	$('#table-suggestions').on({
		change : suggestions.pick
	}, 'input[type=radio]');

});

})(app.suggestions = {}, jQuery);
