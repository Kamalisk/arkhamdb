(function app_fuzzy_search(fuzzy_search, $) {

var types = ['agenda','asset','operation','upgrade','operation','barrier','code gate','sentry','other','event','hardware','resource','icebreaker','program'];
var dict = [];

/**
 * takes a card name and fuzzy-searches it in the card db
 * the input can include a qty indicator like 3x
 * returns an array of objects Card with an additional key "qty"
 * @memberOf fuzzy_search
 */
fuzzy_search.lookup = function lookup(input, max_results) {
    if(max_results == null) max_results = 5;
    var qty = null, name = input.replace(/\(.*\)/, '').replace(/[^0-9\.\-A-Za-z\u00C0-\u024F]+/g, ' ').replace(/\s+/, ' ').trim().toLowerCase();
	if(name.match(/^(\d+)x?\s*(.*)/)) {
		qty = parseInt(RegExp.$1, 10);
		name = RegExp.$2;
	} else if(name.match(/(.*?)\s*x?(\d+)$/)) {
		qty = parseInt(RegExp.$2, 10);
		name = RegExp.$1;
	}
	if(name == "" || name == 'influence spent' || name == 'agenda points' || name == 'cards') return;
	if(types.indexOf(name) > -1) return;
	
	var options = [];
	var query = app.data.cards({token: {likenocase:name}});
	if(query.count()) {
		query.each(function (record,recordnumber) {
			options.push(record);
		});
		options = options.sort(function (a, b) {
			return a.name.length - b.name.length;
		});
	} else if(typeof String.prototype.score === "function") {
		var matches = [];
		$.each(dict, function(index, row) {
			var score = row.token.score(name, 0.9);
            row.score = score;
			matches.push(row);
		});
		matches.sort(function (a,b) { return a.score > b.score ? -1 : a.score < b.score ? 1 : 0; });
		var bestScore = matches[0].score;
		for(var i=0; i<max_results && matches[i].score > 0.4 && matches[i].score > bestScore * 0.9; i++) {
			options.push(matches[i]);
		}
	}
    return { qty: qty, cards: options };
};

/**
 * @memberOf fuzzy_search
 */
app.data_loaded.add(function() {
	app.data.cards().each(function (record, recordnumber) {
        record.token = record.title.replace(/[^0-9\.\-A-Za-z\u00C0-\u017F]+/g, ' ').trim().toLowerCase();
		dict.push(record);
	});
});
	
})(app.fuzzy_search = {}, jQuery);
