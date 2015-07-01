if (typeof NRDB != "object")
	var NRDB = { data_loaded: jQuery.Callbacks() };

NRDB.smart_filter = {};
(function(smart_filter, $) {
	var SmartFilterQuery = [];
	
	smart_filter.get_query = function(FilterQuery) {
		var query = $.merge([], SmartFilterQuery);
		if(FilterQuery) query.push(FilterQuery);
		return query;
	};
	
	smart_filter.handler = function (value, callback) {
		var conditions = filterSyntax(value);
		SmartFilterQuery = [];

		for (var i = 0; i < conditions.length; i++) {
			var condition = conditions[i];
			var type = condition.shift();
			var operator = condition.shift();
			var values = condition;

			switch (type) {
			case "e":
				add_string_sf('set_code', operator, values);
				break;
			case "c":
				add_integer_sf('cyclenumber', operator, values);
				break;
			case "f":
				add_string_sf('faction_letter', operator, values);
				break;
			case "t":
				add_string_sf('type', operator, values);
				break;
			case "":
				add_string_sf('title', operator, values);
				break;
			case "x":
				add_string_sf('text', operator, values);
				break;
			case "a":
				add_string_sf('flavor', operator, values);
				break;
			case "s":
				add_string_sf('subtype', operator, values);
				break;
			case "o":
				add_integer_sf('cost', operator, values);
				break;
			case "v":
				add_integer_sf('agendapoints', operator, values);
				break;
			case "n":
				add_integer_sf('factioncost', operator, values);
				break;
			case "p":
				add_integer_sf('strength', operator, values);
				break;
			case "g":
				add_integer_sf('advancementcost', operator, values);
				break;
			case "h":
				add_integer_sf('trash', operator, values);
				break;
			case "y":
				add_integer_sf('quantity', operator, values);
				break;
			case "u":
				add_boolean_sf('uniqueness', operator, values);
				break;
			}
		}

		callback();
	};
	
	function add_integer_sf(key, operator, values) {
		for (var j = 0; j < values.length; j++) {
			values[j] = parseInt(values[j], 10);
		}
		var condition = {};
		switch (operator) {
		case ":":
			condition[key] = {
				'is' : values
			};
			break;
		case "<":
			condition[key] = {
				'lt' : values
			};
			break;
		case ">":
			condition[key] = {
				'gt' : values
			};
			break;
		case "!":
			condition[key] = {
				'!is' : values
			};
			break;
		}
		SmartFilterQuery.push(condition);
	}
	function add_string_sf(key, operator, values) {
		var condition = {};
		switch (operator) {
		case ":":
			condition[key] = {
				'isNull': false,
				'likenocase' : values
			};
			break;
		case "!":
			condition[key] = {
				'isNull': false,
				'!likenocase' : values
			};
			break;
		}
		SmartFilterQuery.push(condition);
	}
	function add_boolean_sf(key, operator, values) {
		var condition = {}, value = values.pop();
		switch (operator) {
		case ":":
			condition[key] = {
				'is': value ? true : false
			};
			break;
		case "!":
			condition[key] = {
				'is': value ? false : true
			};
			break;
		}
		SmartFilterQuery.push(condition);
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

	
})(NRDB.smart_filter, jQuery);
