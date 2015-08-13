app.data_loaded.add(function() {
	$(this).closest('tr').siblings().removeClass('active');
	$(this).closest('tr').addClass('active');
	for (var i = 0; i < Decklist.cards.length; i++) {
		var slot = Decklist.cards[i];
		app.data.get_cards_by_code(slot.card_code).update({indeck : parseInt(slot.qty, 10)});
	}
	update_deck();
});


$(function() {

	$.when(app.user.deferred).then(function() {
		if(app.user.data) {
			setup_comment_form();
			setup_title();
			setup_comment_hide();
		} else {
			$('<p>You must be logged in to post comments.</p>').insertAfter('#comment-form');
		}
		setup_social_icons();
	});

	$(document).on('click', '#decklist-edit', edit_form);
	$(document).on('click', '#decklist-delete', delete_form);
	$(document).on('click', '#social-icon-like', send_like);
	$(document).on('click', '#social-icon-favorite', send_favorite);
	$(document).on('click', '#btn-group-decklist button[id],a[id]', do_action_decklist);
	$(document).on('click', '#btn-compare', compare_form);
	$(document).on('click', '#btn-compare-submit', compare_submit);

	$('div.collapse').each(function (index, element) {
		$(element).on('show.bs.collapse', function (event) {
			$(this).closest('td').find('.glyphicon-eye-open').removeClass('glyphicon-eye-open').addClass('glyphicon-eye-close');
		});
		$(element).on('hide.bs.collapse', function (event) {
			$(this).closest('td').find('.glyphicon-eye-close').removeClass('glyphicon-eye-close').addClass('glyphicon-eye-open');
		});
	});

	$('#menu-sort').on({
		click : function(event) {
			if ($(this).attr('id').match(/btn-sort-(\w+)/)) {
				DisplaySort = RegExp.$1;
				update_deck();
			}
		}
	}, 'a');

	$('time').each(function (index, element) {
		var datetime = moment($(element).attr('datetime'));
		$(element).html(datetime.calendar());
		$(element).attr('title', datetime.format('LLLL'));
	});
});

function compare_submit() {
	var url = $('#decklist2_url').val();
	var id = null;
	if(url.match(/^\d+$/)) {
		id = parseInt(url, 10);
	} else if(url.match(/decklist\/(\d+)\//)) {
		id = parseInt(RegExp.$1, 10);
	}
	if(id) {
		var id1, id2;
		if(Decklist.id < id) {
			id1 = Decklist.id;
			id2 = id;
		} else {
			id1 = id;
			id2 = Decklist.id;
		}
		location.href = Routing.generate('decklists_diff', {decklist1_id:id1, decklist2_id:id2});
	}
}

function compare_form() {
	$('#compareModal').modal('show');
	setTimeout(function () {
		$('#decklist2_url').focus();
	}, 1000);
}
