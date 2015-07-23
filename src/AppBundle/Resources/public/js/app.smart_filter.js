(function app_smart_filter(smart_filter, $) {

var SmartFilterQuery = [];

/**
 * called when the list is refreshed
 * @memberOf smart_filter
 */
smart_filter.get_query =  function get_query(query) {
	return _.extend(query, SmartFilterQuery);
};

/**
 * called when the filter input is modified
 * @memberOf smart_filter
 */
smart_filter.update =  function update(value) {
	var conditions = filterSyntax(value);
	SmartFilterQuery = {};

	for (var i = 0; i < conditions.length; i++) {
		var condition = conditions[i];
		var type = condition.shift();
		var operator = condition.shift();
		var values = condition;

		switch (type) {
		case "a":
			add_string_sf('flavor', operator, values);
			break;
		case "b":
			add_integer_sf('claim', operator, values);
			break;
//		case "c":
//			add_integer_sf('cycle', operator, values);
//			break;
		case "d":
			add_boolean_sf('isLimited', operator, values);
			break;
		case "e":
			add_string_sf('pack_code', operator, values);
			break;
		case "f":
			add_string_sf('faction_code', operator, values);
			break;
		case "g":
			add_boolean_sf('isIntrigue', operator, values);
			break;
		case "h":
			add_integer_sf('reserve', operator, values);
			break;
		case "i":
			add_string_sf('illustrator', operator, values);
			break;
		case "k":
			add_string_sf('traits', operator, values);
			break;
		case "l":
			add_boolean_sf('isLoyal', operator, values);
			break;
		case "m":
			add_boolean_sf('isMilitary', operator, values);
			break;
		case "n":
			add_integer_sf('income', operator, values);
			break;
		case "o":
			add_integer_sf('cost', operator, values);
			break;
		case "p":
			add_boolean_sf('isPower', operator, values);
			break;
//		case "r":
//			add_string_sf('date_release', operator, values);
//			break;
		case "s":
			add_integer_sf('strength', operator, values);
			break;
		case "t":
			add_string_sf('type_code', operator, values);
			break;
		case "u":
			add_boolean_sf('isUnique', operator, values);
			break;
		case "v":
			add_integer_sf('initiative', operator, values);
			break;
		case "x":
			add_string_sf('text', operator, values);
			break;
		case "y":
			add_integer_sf('quantity', operator, values);
			break;
		}
	}
};

function add_integer_sf(key, operator, values) {
	for (var j = 0; j < values.length; j++) {
		values[j] = parseInt(values[j], 10);
	}
	switch (operator) {
	case ":":
		SmartFilterQuery[key] = {
			'$in' : values
		};
		break;
	case "<":
		SmartFilterQuery[key] = {
			'$lt' : values[0]
		};
		break;
	case ">":
		SmartFilterQuery[key] = {
			'$gt' : values[0]
		};
		break;
	case "!":
		SmartFilterQuery[key] = {
			'$nin' : values
		};
		break;
	}
}
function add_string_sf(key, operator, values) {
	for (var j = 0; j < values.length; j++) {
		values[j] = new RegExp(values[j], 'i');
	}
	switch (operator) {
	case ":":
		SmartFilterQuery[key] = {
			'$in' : values
		};
		break;
	case "!":
		SmartFilterQuery[key] = {
			'$nin' : values
		};
		break;
	}
}
function add_boolean_sf(key, operator, values) {
	var value = values.shift(), target = !!value;
	switch (operator) {
	case ":":
		SmartFilterQuery[key] = {
			'$eq': target
		};
		break;
	case "!":
		SmartFilterQuery[key] = {
			'$ne': target
		};
		break;
	}
}
function filterSyntax(query) {
	// renvoie une liste de conditions (array)
	// chaque condition est un tableau à n>1 éléments
	// le premier est le type de condition (0 ou 1 caractère)
	// les suivants sont les arguments, en OR

	query = query.replace(/^\s*(.*?)\s*$/, "$1").replace('/\s+/', ' ');

	var list = [];
	var cond = null;
	// l'automate a 3 états :
	// 1:recherche de type
	// 2:recherche d'argument principal
	// 3:recherche d'argument supplémentaire
	// 4:erreur de parsing, on recherche la prochaine condition
	// s'il tombe sur un argument alors qu'il est en recherche de type, alors le
	// type est vide
	var etat = 1;
	while (query != "") {
		if (etat == 1) {
			if (cond !== null && etat !== 4 && cond.length > 2) {
				list.push(cond);
			}
			// on commence par rechercher un type de condition
			if (query.match(/^(\w)([:<>!])(.*)/)) { // jeton "condition:"
				cond = [ RegExp.$1.toLowerCase(), RegExp.$2 ];
				query = RegExp.$3;
			} else {
				cond = [ "", ":" ];
			}
			etat = 2;
		} else {
			if (   query.match(/^"([^"]*)"(.*)/) // jeton "texte libre entre guillements"
				|| query.match(/^([^\s]+)(.*)/) // jeton "texte autorisé sans guillements"
			) {
				if ((etat === 2 && cond.length === 2) || etat === 3) {
					cond.push(RegExp.$1);
					query = RegExp.$2;
					etat = 2;
				} else {
					// erreur
					query = RegExp.$2;
					etat = 4;
				}
			} else if (query.match(/^\|(.*)/)) { // jeton "|"
				if ((cond[1] === ':' || cond[1] === '!')
						&& ((etat === 2 && cond.length > 2) || etat === 3)) {
					query = RegExp.$1;
					etat = 3;
				} else {
					// erreur
					query = RegExp.$1;
					etat = 4;
				}
			} else if (query.match(/^ (.*)/)) { // jeton " "
				query = RegExp.$1;
				etat = 1;
			} else {
				// erreur
				query = query.substr(1);
				etat = 4;
			}
		}
	}
	if (cond !== null && etat !== 4 && cond.length > 2) {
		list.push(cond);
	}
	return list;
}
	
})(app.smart_filter = {}, jQuery);
