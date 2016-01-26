(function ui_decklist_edit(ui, $) {

/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {
	$('#descriptionMd').markdown({
		autofocus: true,
		iconlibrary: 'fa',
		hiddenButtons: ['cmdHeading', 'cmdImage', 'cmdCode'],
		footer: 'Press # to insert a card name, $ to insert a game symbol.',
		additionalButtons: 
			[[{
				name: "groupCard",
				data: [{
					name: "cmdCard",
					title: "Turn a card name into a card link",
					icon: "fa fa-clone",
					callback: ui.on_button_card
				}]
			},{
				name: "groupSymbol",
				data: [{
					name: "cmdSymbol",
					title: "Insert a game symbol",
					icon: "icon-power",
					callback: ui.on_button_symbol
				}]
			},{
				name: "groupCustom",
				data: [{
					name: "cmdCustom1",
					title: "Heading 1",
					icon: "fa fa-header",
					callback: _.partial(ui.on_button_heading, '#')
				},{
					name: "cmdCustom2",
					title: "Heading 2",
					icon: "fa fa-header small",
					callback: _.partial(ui.on_button_heading, '##')
				},{
					name: "cmdCustom3",
					title: "Heading 3",
					icon: "fa fa-header smaller",
					callback: _.partial(ui.on_button_heading, '###')
				}]
			}]]
	});
};

ui.on_button_heading = function ui_on_button_heading(heading, e) {
    // Append/remove # surround the selection
    var chunk, cursor, selected = e.getSelection(), content = e.getContent(), pointer, prevChar;

    if (selected.length === 0) {
      // Give extra word
      chunk = e.__localize('heading text');
    } else {
      chunk = selected.text + '\n';
    }

    // transform selection and set the cursor into chunked text
    if ((pointer = heading.length+2, content.substr(selected.start-pointer,pointer) === heading+' ')
        || (pointer = heading.length+1, content.substr(selected.start-pointer,pointer) === heading)) {
      e.setSelection(selected.start-pointer,selected.end);
      e.replaceSelection(chunk);
      cursor = selected.start-pointer;
    } else if (selected.start > 0 && (prevChar = content.substr(selected.start-1,1), !!prevChar && prevChar != '\n')) {
      e.replaceSelection('\n\n'+heading+' '+chunk);
      cursor = selected.start+heading.length+4;
    } else {
      // Empty string before element
      e.replaceSelection(heading+' '+chunk);
      cursor = selected.start+heading.length+1;
    }

    // Set the cursor
    e.setSelection(cursor,cursor+chunk.length);
}

ui.on_button_symbol = function ui_on_button_symbol(e) 
{
	var button = $('button[data-handler=bootstrap-markdown-cmdSymbol]');
	$(button).attr('data-toggle', 'dropdown');
	$(button).next().remove();
	
	var menu = $('<ul class="dropdown-menu">').insertAfter(button).on('click', 'li', function (event) {
		var icon = $(this).data('icon');
		var chunk = '<span class="icon-'+icon+'"></span>';
		ui.replace_selection(e, e.getSelection(), chunk);
		$(menu).remove();
		$(button).off('click');
	});
	
	var icons = 'baratheon greyjoy intrigue lannister martell military thenightswatch power stark targaryen tyrell unique plot attachment location character event agenda neutral'.split(' ');
	icons.forEach(function (icon) {
		menu.append('<li data-icon="'+icon+'"><a href="#"><span style="display:inline-block;width:2em;text-align:center" class="icon-'+icon+'"></span> '+icon+'</a></li>');
	});
	$(button).dropdown();
}

ui.on_button_card = function ui_on_button_card(e) 
{
	var button = $('button[data-handler=bootstrap-markdown-cmdCard]');
	$(button).attr('data-toggle', 'dropdown');
	$(button).next().remove();
	
	var menu = $('<ul class="dropdown-menu">').insertAfter(button).on('click', 'li', function (event) {
		var code = $(this).data('code'), name = $(this).data('name');
		var chunk = '['+name+'](' + Routing.generate('cards_zoom', { card_code: code }) + ')';
		ui.replace_selection(e, e.getSelection(), chunk);
		$(menu).remove();
		$(button).off('click');
	});
	
	var cards = app.data.cards.find({name: new RegExp(e.getSelection().text, 'i')}, {'$orderBy': {name: 1}});
	if(cards.length > 10) {
		cards = cards.slice(0, 10);
	}
	cards.forEach(function (card) {
		menu.append('<li data-code="'+card.code+'" data-name="'+card.name+'"><a href="#">' + card.name + ' <small><i>' + card.pack_name + '</i></small></a></li>');
	})
	$(button).dropdown();
}

ui.replace_selection = function ui_replace_selection(e, selected, chunk) 
{
    e.replaceSelection(chunk);
    var cursor = selected.start;
    e.setSelection(cursor,cursor+chunk.length);
    e.$textarea.focus();
}

/**
 * called when the app data is loaded
 * @memberOf ui
 */
ui.on_data_loaded = function on_data_loaded() {
};

/**
 * called when both the DOM and the data app have finished loading
 * @memberOf ui
 */
ui.on_all_loaded = function on_all_loaded() {
	app.textcomplete.setup('#descriptionMd');
	app.deck.display('#decklist', { cols: 1 });
};


})(app.ui, jQuery);
