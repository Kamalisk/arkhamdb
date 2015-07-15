(function app_deck_gallery(deck_gallery, $) {

/**
 * @memberOf deck_gallery
 */
deck_gallery.display = function display(container) {
	var table = $('<table>').appendTo(container),
		row = $('<tr>').appendTo(table),
		cards = app.deck.get_cards({'type_code':1});
	
	cards.forEach(function (card) {
		var cell = $('<td><div><img src="' + card.imagesrc + '" alt="Card Image"><div>' + card.indeck + '</div></div></td>');
		cell.appendTo(row);
	})
};

})(app.deck_gallery = {}, jQuery);
