(function app_deck_charts(deck_charts, $) {

var charts = [],
	faction_colors = {

		seeker :
			'#e3d852',

		neutral :
			'#cfcfcf',

		guardian :
			'#1d7a99',

		survivor :
			'#c00106',

		rogue :
			'#509f16',

		mystic :
			'#ae1eae'

	};

deck_charts.chart_faction = function chart_faction() {
	var factions = {};
	var draw_deck = app.deck.get_draw_deck();
	draw_deck.forEach(function (card) {
		if(!factions[card.faction_code]) factions[card.faction_code] = { code: card.faction_code, name: card.faction_name, count: 0};
		factions[card.faction_code].count += card.indeck;
	})

	var data = [];
	_.each(_.values(factions), function (faction) {
		data.push({
			name: faction.name,
			label: '<span class="icon icon-'+faction.code+'"></span>',
			color: faction_colors[faction.code],
			y: faction.count
		});
	})

	$("#deck-chart-faction").highcharts({
		chart: {
            type: 'column'
        },
		title: {
            text: "Card Factions"
        },
		subtitle: {
            text: "Draw deck only"
        },
		xAxis: {
			categories: _.pluck(data, 'label'),
			labels: {
				useHTML: true
			},
            title: {
                text: null
            }
        },
		yAxis: {
            min: 0,
			allowDecimals: false,
			tickInterval: 3,
            title: null,
            labels: {
                overflow: 'justify'
            }
        },
        series: [{
			type: "column",
			animation: false,
            name: '# cards',
			showInLegend: false,
            data: data
        }],
		plotOptions: {
			column: {
			    borderWidth: 0,
			    groupPadding: 0,
			    shadow: false
			}
		}
    });
}


deck_charts.chart_cost = function chart_cost() {

	var data = [];

	var draw_deck = app.deck.get_draw_deck();
	draw_deck.forEach(function (card) {
		if(typeof card.cost === 'number') {
			data[card.cost] = data[card.cost] || 0;
			data[card.cost] += card.indeck;
		}
	})
	data = _.flatten(data).map(function (value) { return value || 0; });

	$("#deck-chart-cost").highcharts({
		chart: {
			type: 'line'
		},
		title: {
			text: "Card Cost"
		},
		subtitle: {
			text: "Cost X ignored"
		},
		xAxis: {
			allowDecimals: false,
			tickInterval: 1,
			title: {
				text: null
			}
		},
		yAxis: {
			min: 0,
			allowDecimals: false,
			tickInterval: 1,
			title: null,
			labels: {
				overflow: 'justify'
			}
		},
		tooltip: {
			headerFormat: '<span style="font-size: 10px">Cost {point.key}</span><br/>'
		},
		series: [{
			animation: false,
			name: '# cards',
			showInLegend: false,
			data: data
		}]
	});
}


deck_charts.chart_skill = function chart_skill() {

	var icons = {};
	icons['willpower'] = {code: "willpower", "name": "Willpower", count: 0};
	icons['intellect'] = {code: "intellect", "name": "Intellect", count: 0};
	icons['combat'] = {code: "combat", "name": "Combat", count: 0};
	icons['agility'] = {code: "agility", "name": "Agility", count: 0};
	icons['wild'] = {code: "wild", "name": "Wild", count: 0};
	var draw_deck = app.deck.get_draw_deck();
	draw_deck.forEach(function (card) {
		if (card.skill_willpower && card.skill_willpower > 0){
			icons['willpower'].count += card.indeck * card.skill_willpower;
		}
		if (card.skill_intellect && card.skill_intellect > 0){
			icons['intellect'].count += card.indeck * card.skill_intellect;
		}
		if (card.skill_combat && card.skill_combat > 0){
			icons['combat'].count += card.indeck * card.skill_combat;
		}
		if (card.skill_agility && card.skill_agility > 0){
			icons['agility'].count += card.indeck * card.skill_agility;
		}
		if (card.skill_wild && card.skill_wild > 0){
			icons['wild'].count += card.indeck * card.skill_wild;
		}
	})

	var data = [];
	_.each(_.values(icons), function (icon) {
		data.push({
			name: icon.name,
			label: '<span class="icon icon-'+icon.code+'"></span>',
			//color: faction_colors[faction.code],
			y: icon.count
		});
	})
	data = _.flatten(data).map(function (value) { return value || 0; });
		
	$("#deck-chart-skill").highcharts({
		chart: {
			type: 'column'
		},
		title: {
			text: "Card Skill Icons"
		},
		subtitle: {
			text: ""
		},
		xAxis: {
			categories: _.pluck(data, 'label'),
			labels: {
				useHTML: true
			},
			title: {
				text: null
			}
		},
		yAxis: {
			min: 0,
			allowDecimals: false,
			tickInterval: 3,
			title: null,
			labels: {
				overflow: 'justify'
			}
		},
		series: [{
			type: "column",
			animation: false,
			name: '# of skill icons',
			showInLegend: false,
			data: data
		}],
		plotOptions: {
			column: {
				borderWidth: 0,
				groupPadding: 0,
				shadow: false
			}
		}
	});
}


deck_charts.chart_slot = function chart_slot() {

	var slots = {};
	var draw_deck = app.deck.get_draw_deck();
	draw_deck.forEach(function (card) {
		if (card.type_code != "asset"){
			return;
		}
		var card_slot = "Other";
		if (card.slot){
			card_slot = card.slot;
		}
		if(!slots[card_slot]) slots[card_slot] = { name: card_slot, count: 0};
		slots[card_slot].count += card.indeck;
	})

	var data = [];
	_.each(_.values(slots), function (slot) {
		data.push({
			name: slot.name,
			label: slot.name,
			//color: faction_colors[faction.code],
			y: slot.count
		});
	})
	data = _.flatten(data).map(function (value) { return value || 0; });
		
	$("#deck-chart-slot").highcharts({
		chart: {
			type: 'column'
		},
		title: {
			text: "Asset Slots"
		},
		subtitle: {
			text: ""
		},
		xAxis: {
			categories: _.pluck(data, 'label'),
			labels: {
				useHTML: true
			},
			title: {
				text: null
			}
		},
		yAxis: {
			min: 0,
			allowDecimals: false,
			tickInterval: 3,
			title: null,
			labels: {
				overflow: 'justify'
			}
		},
		series: [{
			type: "column",
			animation: false,
			name: '# of cards',
			showInLegend: false,
			data: data
		}],
		plotOptions: {
			column: {
				borderWidth: 0,
				groupPadding: 0,
				shadow: false
			}
		}
	});
}

deck_charts.setup = function setup(options) {
	deck_charts.chart_faction();
	deck_charts.chart_cost();
	deck_charts.chart_skill();
	deck_charts.chart_slot();
}

$(document).on('shown.bs.tab', 'a[data-toggle=tab]', function (e) {
	deck_charts.setup();
});

})(app.deck_charts = {}, jQuery);
