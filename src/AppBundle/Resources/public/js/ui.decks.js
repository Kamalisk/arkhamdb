(function ui_decks(ui, $) {

ui.decks = [];

ui.confirm_publish = function confirm_publish(event) {
	var button = $(this);
	if($(button).hasClass('processing')) return;
	$(button).addClass('processing');

	var deck_id = $(this).closest('tr').data('id');

	$('#publish-form-alert').remove();

	$.ajax(Routing.generate('deck_publish', {deck_id:deck_id}), {
		dataType: 'json',
		success: function( response ) {
		  if(typeof response === 'object') {
			  $('#publish-deck-name').val(response.name);
			  $('#publish-deck-id').val(response.id);
			  $('#publish-deck-description').val(response.description_md);
			  $('#btn-publish-submit').text("Go").prop('disabled', false);
		  }
		  else
		  {
			  $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">That deck cannot be published because <a href="'+response+'">another decklist</a> already has the same composition.</div>');
			  $('#btn-publish-submit').text("Refused").prop('disabled', true);
		  }
	  },
	  error: function( jqXHR, textStatus, errorThrown ) {
		  console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
		  $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">'+jqXHR.responseText+'</div>');
		  $('#btn-publish-submit').text("Error").prop('disabled', true);
	  },
	  complete: function() {
		  $(button).removeClass('processing');
		  $('#publishModal').modal('show');
	  }
	});


}

ui.confirm_delete = function confirm_delete(event) {
	var tr = $(this).closest('tr');
	var deck_id = tr.data('id');
	var deck_name = tr.find('.deck-name').text();
	$('#delete-deck-name').text(deck_name);
	$('#delete-deck-id').val(deck_id);
	$('#deleteModal').modal('show');
}

ui.confirm_delete_all = function confirm_delete_all(ids) {
	$('#delete-deck-list-id').val(ids.join('-'));
	$('#deleteListModal').modal('show');
}

function sort_list(type)
{
	var container = $('#decks');
    var current_sort = container.data('sort-type');
    var current_order = container.data('sort-order');
    var order = current_order || 1;
    if(current_sort && current_sort == type) {
        order = -order;
    }
    container.data('sort-type', type);
    container.data('sort-order', order);
    var sort = type.split(/,/).map(function (t) { return t+' '+(order > 0 ? 'desc' : 'asec'); }).join(',');
	var sorted_list_id = DeckDB().order(sort).select('id');
	var first_id = sorted_list_id.shift();
	var deck_elt = $('#deck_'+first_id);

	container.prepend(deck_elt);
	sorted_list_id.forEach(function (id) {
		deck_elt = $('#deck_'+id).insertAfter(deck_elt);
	});

}

ui.update_tag_toggles = function update_tag_toggles() {

	return;

	// tags is an object where key is tag and value is array of deck ids
	var tag_dict = Decks.reduce(function (p, c) {
		c.tags.forEach(function (t) {
			if(!p[t]) p[t] = [];
			p[t].push(c.id);
		});
		return p;
	}, {});
	var tags = [];
	for(var tag in tag_dict) {
		tags.push(tag);
	}
	var container = $('#tag_toggles').empty();
	tags.sort().forEach(function (tag) {
		$('<button type="button" class="btn btn-default btn-xs" data-toggle="button">'+tag+'</button>').data('deck_id', tag_dict[tag].join(' ')).appendTo(container);
	});

}

ui.set_tags = function set_tags(id, tags) {
	var elt = $('tr[data-id='+id+']');
	var div = elt.find('div.tags').empty();
	tags.forEach(function (tag) {
		div.append($('<span class="tag">'+tag+'</span>'));
	});

	ui.update_tag_toggles();
}

ui.tag_add = function tag_add(ids) {
    $('#tag_add_ids').val(ids);
	$('#tagAddModal').modal('show');
    setTimeout(function() { $('#tag_add_tags').focus(); }, 500);
}

ui.tag_add_process = function tag_add_process(event) {
    event.preventDefault();
    var ids = $('#tag_add_ids').val().split(/,/);
    var tags = $('#tag_add_tags').val().split(/\s+/);
    if(!ids.length || !tags.length) return;
	ui.tag_process_any('tag_add', { ids: ids, tags: tags });
}

ui.tag_remove = function tag_remove(ids) {
    $('#tag_remove_ids').val(ids);
	$('#tagRemoveModal').modal('show');
    setTimeout(function() { $('#tag_remove_tags').focus(); }, 500);
}

ui.tag_remove_process = function tag_remove_process(event) {
    event.preventDefault();
    var ids = $('#tag_remove_ids').val().split(/,/);
    var tags = $('#tag_remove_tags').val().split(/\s+/);
    if(!ids.length || !tags.length) return;
	ui.tag_process_any('tag_remove', { ids: ids, tags: tags });
}

ui.tag_clear = function tag_clear(ids) {
    $('#tag_clear_ids').val(ids);
	$('#tagClearModal').modal('show');
}

ui.tag_clear_process = function tag_clear_process(event) {
    event.preventDefault();
    var ids = $('#tag_clear_ids').val().split(/,/);
    if(!ids.length) return;
	ui.tag_process_any('tag_clear', { ids: ids });
}

ui.tag_process_any = function tag_process_any(route, data) {
	$.ajax(Routing.generate(route), {
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			var response = jqXHR.responseJSON;
			if(!response.success) {
				alert('An error occured while updating the tags.');
				return;
			}
			$.each(response.tags, function (id, tags) {
				ui.set_tags(id, tags);
			});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			alert('An error occured while updating the tags.');
		}
	});
}

ui.do_action_selection = function do_action_selection(event) {
	event.stopPropagation();
	var action_id = $(this).attr('id');
	var ids = $('.list-decks input:checked').map(function (index, elt) {
		return $(elt).closest('tr').data('id');
	}).get();
	if(!action_id || !ids.length) return;
	switch(action_id) {
//		case 'btn-compare': do_diff(ids); break;
//		case 'btn-compare-collection': do_diff_collection(ids); break;
		case 'btn-tag-add': ui.tag_add(ids); break;
		case 'btn-tag-remove-one': ui.tag_remove(ids); break;
		case 'btn-tag-remove-all': ui.tag_clear(ids); break;
		case 'btn-delete-selected': ui.confirm_delete_all(ids); break;
		case 'btn-download-text': ui.download_text_selection(ids); break;
		case 'btn-download-octgn': ui.download_octgn_selection(ids); break;
	}
	return false;
}

ui.do_action_sort = function do_action_sort(event) {
	event.stopPropagation();
	var action_id = $(this).attr('id');
	if(!action_id) return;
	switch(action_id) {
		case 'btn-sort-update': sort_list('dateupdate'); break;
		case 'btn-sort-creation': sort_list('datecreation'); break;
		case 'btn-sort-identity': sort_list('identity_name,name'); break;
		case 'btn-sort-faction': sort_list('faction_code,name'); break;
		case 'btn-sort-lastpack': sort_list('cycle_id,pack_position'); break;
		case 'btn-sort-name': sort_list('name'); break;
	}
	return false;
}


/**
 * called when the DOM is loaded
 * @memberOf ui
 */
ui.on_dom_loaded = function on_dom_loaded() {

	$('#decks').on('click', 'button.btn-publish-deck', ui.confirm_publish);
	$('#decks').on('click', 'button.btn-delete-deck', ui.confirm_delete);
	$('#decks').on('click', 'input[type=checkbox]', function (event) {
		var checked = $(this).closest('tbody').find('input[type=checkbox]:checked');
		var button = $('#btn-group-selection button');
		if(checked.size()) {
			button.removeClass('btn-default').addClass('btn-primary')
		} else {
			button.addClass('btn-default').removeClass('btn-primary')
		}

	});

	$('#btn-group-selection').on('click', 'button[id],a[id]', ui.do_action_selection);
	$('#btn-group-sort').on('click', 'button[id],a[id]', ui.do_action_sort);


};

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

};


})(app.ui, jQuery);
