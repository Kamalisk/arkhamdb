(function app_textcomplete(textcomplete, $) {
	
var icons = 'baratheon greyjoy intrigue lannister martell military nightswatch power stark targaryen tyrell unique plot attachment location character event agenda neutral'.split(' ');

/**
 * options: cards, icons, users
 */
textcomplete.setup = function setup(textarea, options) {
	
	options = _.extend({cards: true, icons: true, users: false}, options);
	
	var actions = [];
	
	if(options.cards) {
		actions.push({
			match : /\B#([\-+\w]*)$/,
			search : function(term, callback) {
				var regexp = new RegExp(term);
				callback(app.data.cards.find({
					name : regexp
				}));
			},
			template : function(value) {
				return value.name;
			},
			replace : function(value) {
				return '<a href="'+Routing.generate('cards_zoom', {card_code:value.code})+'" class="card-tip" data-code="'+value.code+'">'+value.name+'</a>';
				return '[' + value.name + ']('
						+ Routing.generate('cards_zoom', {card_code:value.code})
						+ ')';
			},
			index : 1
		})
	}
	
	if(options.icons) {
		actions.push({
						match : /\$([\-+\w]*)$/,
						search : function(term, callback) {
							var regexp = new RegExp('^' + term);
							callback(_.filter(icons,
								function(symbol) { return regexp.test(symbol); }
							));
						},
						template : function(value) {
							return value;
						},
						replace : function(value) {
							return '<span class="icon-' + value + '"></span>';
						},
						index : 1
					});
	}
	
	if(options.users) {
		
	}
	
	$(textarea).textcomplete(actions);

}

})(app.textcomplete = {}, jQuery);
