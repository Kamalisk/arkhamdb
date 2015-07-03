if (typeof app != "object")
	var app = { data_loaded: jQuery.Callbacks() };

app.deck_gallery = {};
(function(deck_gallery, $) {
	var images = null;

	deck_gallery.update = function() {

		images = [ Identity.imagesrc ];
		qtys = [ 1 ];
		app.data.cards({
			indeck : {
				'gt' : 0
			},
			type_code : {
				'!is' : 'identity'
			}
		}).order('type_code,name').each(function(record) {
			images.push(record.imagesrc);
			qtys.push(record.indeck);
		});
		for (var i = 0; i < images.length; i++) {
			var cell = $('<td><div><img src="' + images[i] + '" alt="Card Image"><div>'+qtys[i]+'</div></div></td>');
			$('#deck_gallery tr').append(cell.data('index', i));
		}
	};

})(app.deck_gallery, jQuery);
