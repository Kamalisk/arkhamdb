if (typeof NRDB != "object")
	var NRDB = { data_loaded: jQuery.Callbacks() };

NRDB.deck_gallery = {};
(function(deck_gallery, $) {
	var images = null;

	deck_gallery.update = function() {

		images = [ Identity.imagesrc ];
		qtys = [ 1 ];
		NRDB.data.cards({
			indeck : {
				'gt' : 0
			},
			type_code : {
				'!is' : 'identity'
			}
		}).order('type_code,title').each(function(record) {
			images.push(record.imagesrc);
			qtys.push(record.indeck);
		});
		for (var i = 0; i < images.length; i++) {
			var cell = $('<td><div><img src="' + images[i] + '" alt="Card Image"><div>'+qtys[i]+'</div></div></td>');
			$('#deck_gallery tr').append(cell.data('index', i));
		}
	};

})(NRDB.deck_gallery, jQuery);
