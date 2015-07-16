var Snapshots = []; // deck contents autosaved
var Autosave_timer = null;
var Deck_changed_since_last_autosave = false;
var Autosave_running = false;
var Autosave_period = 60;

app.data_loaded.add(function() {

	if(Modernizr.touch) {
		$('#faction_code, #type_code').css('width', '100%').addClass('btn-group-vertical');
	} else {
		$('#faction_code, #type_code').addClass('btn-group');
	}

	function findMatches(q, cb) {
		if(q.match(/^\w:/)) return;
		var matches = app.data.cards({name: {likenocase: q}}).map(function (record) {
			return { value: record.name };
		});
		cb(matches);
	}

	$('#filter-text').typeahead({
		  hint: true,
		  highlight: true,
		  minLength: 2
		},{
		name : 'cardnames',
		displayKey: 'value',
		source: findMatches
	});

	make_cost_graph();
	make_strength_graph();

	$('html,body').css('height', 'auto');

});

$(function() {
	$('html,body').css('height', '100%');

	$('#filter-text').on('typeahead:selected typeahead:autocompleted',
			app.card_modal.typeahead);

	$(document).on('hidden.bs.modal', function (event) {
		if(InputByTitle) {
			setTimeout(function () {
				$('#filter-text').typeahead('val', '').focus();
			}, 100);
		}
	});
	
	$('#cardModal').on({
		keypress : function(event) {
			var num = parseInt(event.which, 10) - 48;
			$('.modal input[type=radio][value=' + num + ']').trigger('change');
		}
	});
	
	$('#filter-text-button')
			.tooltip(
					{
						html : true,
						container : 'body',
						placement : 'bottom',
						trigger : 'click',
						title : "<h5>Smart filter syntax</h5><ul style=\"text-align:left\"><li>x: filters on text</li><li>a: flavor text</li><li>s: subtype</li><li>o: cost</li><li>v: agenda points</li><li>n: faction cost</li><li>p: strength</li><li>g: advancement cost</li><li>h: trash cost</li><li>u: uniqueness</li><li>y: quantity in pack</li></ul><code>s:\"code gate\" x:trace</code> to find code gates with trace"
					});

	
});


function load_snapshot(event) {
	var index = $(this).data('index');
	var snapshot = Snapshots[index];
	if(!snapshot) return;

	app.data.cards().each(function(record) {
		var indeck = 0;
		if (snapshot.content[record.code]) {
			indeck = parseInt(snapshot.content[record.code], 10);
		}
		app.data.cards(record.___id).update({
			indeck : indeck
		});
	});
	update_deck();
	refresh_collection();
	app.suggestions.compute();
	Deck_changed_since_last_autosave = true;
	return false;
}
function get_deck_content() {
	var deck_content = {};
	app.data.cards({
		indeck : {
			'gt' : 0
		}
	}).each(function(record) {
		deck_content[record.code] = record.indeck;
	});
	return deck_content;
}
