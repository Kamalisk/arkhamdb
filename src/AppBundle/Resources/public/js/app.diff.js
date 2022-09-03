(function app_diff(diff, $) {

// takes an array of strings and returns an object where each string of the array
// is a key of the object and the value is the number of occurences of the string in the array
function array_count(list) {
	var obj = {};
	var list = list.sort();
	for(var i=0; i<list.length; ) {
		for(var j=i+1; j<list.length; j++) {
			if(list[i] !== list[j]) break;
		}
		obj[list[i]] = (j-i);
		i=j;
	}
	return obj;
}

/**
 * contents is an array of content
 * content is a hash of pairs code-qty
 * @memberOf diff
 */
diff.compute_simple = function compute_simple(contents, meta_contents) {
	var customizations = [];
	var ensembles = [];
	for(var decknum=0; decknum<contents.length; decknum++) {
		var choices = {};
		var cards = [];
		$.each(contents[decknum], function (code, qty) {
			for(var copynum=0; copynum<qty; copynum++) {
				cards.push(code);
			}
		});
		Object.keys(meta_contents[decknum] || {}).forEach(function(key) {
			var val = meta_contents[decknum][key];
			if (key.indexOf('cus_') === 0) {
				var entries = val ? val.split(',') : [];
				var r = [];
				for(var i=0; i<entries.length; i++) {
					var entry = entries[i];
					var parts = entry.split('|');
					var index = parseInt(parts[0], 10);
					var xp = parseInt(parts[1], 10);
					var choice = parts.length > 2 ? parts[2] : undefined;
					r.push({
						index,
						xp,
						choice,
						raw: entry,
					});
				}
				if (r.length) {
					choices[key.substring(4)] = r;
				}
			}
		});
		ensembles.push(cards);
		customizations.push(choices);
	}

	var meta_additions={};
	var meta_removals={};
	var new_customizations = {};
	Object.keys(customizations[0]).concat(Object.keys(customizations[1])).forEach(function(code) {
		var choices = customizations[0][code] || [];
		var previous = customizations[1][code] || [];
		var new_choices = [];
		var additions = [];
		var removals = [];
		// Check for additions
		for(var i=0; i<choices.length; i++) {
			var choice = choices[i];
			var found = false;
			var p = null;
			for (var j=0; j<previous.length; j++) {
				if (choice.raw === previous[j].raw) {
					found = true;
					break;
				}
				if (choice.index == previous[j].index) {
					// Found a matching index, so we can't have an exact match.
					p = previous[j];
					break;
				}
			}
			if (!found) {
				// Something changed
				new_choices.push({
					index: choice.index,
					xp_delta: choice.xp - ((p && p.xp) || 0)
				});
				additions.push(choice.raw);
			}
		}
		// Check for removals.
		for (var j=0; j<previous.length; j++) {
			var found = false;
			for (var i=0; i<choices.length; i++) {
				if (choices[i].raw === previous[j].raw) {
					found = true;
					break;
				}
			}
			if (!found) {
				removals.push(previous[j].raw);
			}
		}
		if (new_choices.length) {
			new_customizations[code] = new_choices;
		}
		if (additions.length) {
			meta_additions[code] = additions;
		}
		if (removals.length) {
			meta_removals[code] = removals;
		}
	});

	var conjunction = [];
	for(var i=0; i<ensembles[0].length; i++) {
		var code = ensembles[0][i];
		var indexes = [ i ];
		for(var j=1; j<ensembles.length; j++) {
			var index = ensembles[j].indexOf(code);
			if(index > -1) indexes.push(index);
			else break;
		}
		if(indexes.length === ensembles.length) {
			conjunction.push(code);
			for(var j=0; j<indexes.length; j++) {
				ensembles[j].splice(indexes[j], 1);
			}
			i--;
		}
	}

	var listings = [];
	for(var i=0; i<ensembles.length; i++) {
		listings[i] = array_count(ensembles[i]);
	}
	// Add customization changes to the variations.
	listings[2] = meta_additions;
	listings[3] = meta_removals;

	var intersect = array_count(conjunction);
	return [listings, intersect, new_customizations];
};

})(app.diff = {}, jQuery);
