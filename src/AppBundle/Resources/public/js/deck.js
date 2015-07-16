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

function uncheck_all_others() {
	$(this).closest(".filter").find("input[type=checkbox]").prop("checked",false);
	$(this).children('input[type=checkbox]').prop("checked", true).trigger('change');
}

function check_all_others() {
	$(this).closest(".filter").find("input[type=checkbox]").prop("checked",true);
	$(this).children('input[type=checkbox]').prop("checked", false);
}

function uncheck_all_active() {
	$(this).closest(".filter").find("label.active").button('toggle');
}

function check_all_inactive() {
	$(this).closest(".filter").find("label:not(.active)").button('toggle');
}

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

	var converter = new Markdown.Converter();
	$('#description').on(
			'keyup',
			function() {
				$('#description-preview').html(
						converter.makeHtml($('#description').val()));
			});

	$('#tbody-history').on('click', 'a[role=button]', load_snapshot);
	$.each(History, function (index, snapshot) {
		add_snapshot(snapshot);
	});
	setInterval(autosave_interval, 1000);
});
function autosave_interval() {
	if(Autosave_running) return;
	if(Autosave_timer < 0 && Deck_id) Autosave_timer = Autosave_period;
	//('#tab-header-history').html('History '+Autosave_timer);
	$('#history-timer-bar').css('width', (Autosave_timer*100/Autosave_period)+'%').attr('aria-valuenow', Autosave_timer).find('span').text(Autosave_timer+' seconds remaining.');
	if(Autosave_timer === 0) {
		deck_autosave();
	}
	Autosave_timer--;
}
// if diff is undefined, consider it is the content at load
function add_snapshot(snapshot) {
	snapshot.date_creation = snapshot.date_creation ? moment(snapshot.date_creation) : moment();
	Snapshots.push(snapshot);

	var list = [];
	if(snapshot.variation) {
		$.each(snapshot.variation[0], function (code, qty) {
			var card = app.data.get_card_by_code(code);
			if(!card) return;
			list.push('+'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card" data-code="'+code+'">'+card.name+'</a>');
		});
		$.each(snapshot.variation[1], function (code, qty) {
			var card = app.data.get_card_by_code(code);
			if(!card) return;
			list.push('&minus;'+qty+' '+'<a href="'+Routing.generate('cards_zoom',{card_code:code})+'" class="card" data-code="'+code+'">'+card.name+'</a>');
		});
	} else {
		list.push("First version");
	}

	$('#tbody-history').prepend('<tr'+(snapshot.saved ? '' : ' class="warning"')+'><td>'+snapshot.date_creation.calendar()+(snapshot.saved ? '' : ' (unsaved)')+'</td><td>'+list.join('<br>')+'</td><td><a role="button" href="#" data-code="'+(Snapshots.length-1)+'"">Revert</a></td></tr>');

	Autosave_timer = -1; // start timer
}
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
function deck_autosave() {
	// check if deck has been modified since last autosave
	if(!Deck_changed_since_last_autosave || !Deck_id) return;
	// compute diff between last snapshot and current deck
	var last_snapshot = Snapshots[Snapshots.length-1].content;
	var current_deck = get_deck_content();
	Deck_changed_since_last_autosave = false;
	var r = app.diff.compute_simple([current_deck, last_snapshot]);
	if(!r) return;
	var diff = JSON.stringify(r[0]);
	if(diff == '[{},{}]') return;
	// send diff to autosave
	$('#tab-header-history').html("Autosave...");
	Autosave_running = true;
	$.ajax(Routing.generate('deck_autosave', {deck_id:Deck_id}), {
		data: {diff:diff},
		type: 'POST',
		success: function(data, textStatus, jqXHR) {
			add_snapshot({datecreation: data, variation: r[0], content: current_deck, saved: false});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			Deck_changed_since_last_autosave = true;
		},
		complete: function () {
			$('#tab-header-history').html("History");
			Autosave_running = false;
		}
	});
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
