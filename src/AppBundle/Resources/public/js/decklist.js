app.data_loaded.add(function() {
	$(this).closest('tr').siblings().removeClass('active');
	$(this).closest('tr').addClass('active');
	for (var i = 0; i < Decklist.cards.length; i++) {
		var slot = Decklist.cards[i];
		app.data.get_cards_by_code(slot.card_code).update({indeck : parseInt(slot.qty, 10)});
	}
	update_deck();
});

function setup_comment_form() {
	
	var form = $('<form method="POST" action="'+Routing.generate('decklist_comment')+'"><input type="hidden" name="id" value="'+Decklist.id+'"><div class="form-group">'
			+ '<textarea id="comment-form-text" class="form-control" rows="4" name="comment" placeholder="Enter your comment in Markdown format. Type # to enter a card name. Type $ to enter a symbol. Type @ to enter a user name."></textarea>'
			+ '</div><div class="well text-muted" id="comment-form-preview"><small>Preview. Look <a href="http://daringfireball.net/projects/markdown/dingus">here</a> for a Markdown syntax reference.</small></div>'
			+ '<button type="submit" class="btn btn-success">Submit comment</button></form>').insertAfter('#comment-form');
	
	var already_submitted = false;
	form.on('submit', function (event) {
		event.preventDefault();
		var data = $(this).serialize();
		if(already_submitted) return;
		already_submitted = true;
		$.ajax(Routing.generate('decklist_comment'), {
			data: data,
			type: 'POST',
			success: function(data, textStatus, jqXHR) {
				form.replaceWith('<div class="alert alert-success" role="alert">Your comment has been posted. It will appear on the site in a few minutes.</div>');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
				form.replaceWith('<div class="alert alert-danger" role="alert">An error occured while posting your comment ('+jqXHR.statusText+'). Reload the page and try again.</div>');
			}
		});
	});
	
	$('#social-icon-comment').on('click', function() {
		$('#comment-form-text').trigger('focus');
	});


	var converter = new Markdown.Converter();
	$('#comment-form-text').on(
		'keyup',
		function() {
			$('#comment-form-preview').html(converter.makeHtml($('#comment-form-text').val()));
		}
	);
	
	$('#comment-form-text').textcomplete(
			[
					{
						match : /\B#([\-+\w]*)$/,
						search : function(term, callback) {
							callback(app.data.cards({
								name : {
									likenocase : term
								},
								cycleposition : {
									'!=': 0
								}
							}).get());
						},
						template : function(value) {
							return value.name;
						},
						replace : function(value) {
							return '[' + value.name + ']('
									+ Routing.generate('cards_zoom', {card_code:value.code})
									+ ')';
						},
						index : 1
					}, {
						match : /\B@([\-+\w]*)$/,
						search : function(term, callback) {
							var regexp = new RegExp('^' + term);
							callback($.grep(Commenters, function(commenter) {
								return regexp.test(commenter);
							}));
						},
						template : function(value) {
							return value;
						},
						replace : function(value) {
							return '`@' + value + '`';
						},
						index : 1
					}, {
						match : /\$([\-+\w]*)$/,
						search : function(term, callback) {
							var regexp = new RegExp('^' + term);
							callback($.grep(['credit', 'recurring-credit', 'click', 'link', 'trash', 'subroutine', 'mu', '1mu', '2mu', '3mu', 
								'anarch', 'criminal', 'shaper', 'haas-bioroid', 'weyland-consortium', 'jinteki', 'nbn'],
								function(symbol) { return regexp.test(symbol); }
							));
						},
						template : function(value) {
							return value;
						},
						replace : function(value) {
							return '<span class="icon icon-' + value + '"></span>';
						},
						index : 1
					} ]);

}

function setup_social_icons() {
	
	if(!app.user.data || app.user.data.is_author || app.user.data.is_liked) {
		var element = $('#social-icon-like');
		element.replaceWith($('<span class="social-icon-like"></span').html(element.html()));
	}
	
	if(!app.user.data) {
		var element = $('#social-icon-favorite');
		element.replaceWith($('<span class="social-icon-favorite"></span').html(element.html()));
	} else if(app.user.data.is_favorite) {
		var element = $('#social-icon-favorite');
		element.attr('title', "Remove from favorites");
	} else {
		var element = $('#social-icon-favorite');
		element.attr('title', "Add to favorites");
	}
	
	if(!app.user.data) {
		var element = $('#social-icon-comment');
		element.replaceWith($('<span class="social-icon-comment"></span').html(element.html()));
	}

}

function setup_title() {
	var title = $('h1.decklist-name');
	if(app.user.data && app.user.data.is_author && app.user.data.can_delete) {
		title.prepend('<a href="#" title="Delete decklist" id="decklist-delete"><span class="fa fa-trash-o pull-right text-danger"></span></a>');
	}
	if(app.user.data && app.user.data.is_author) {
		title.prepend('<a href="#" title="Edit decklist name / description" id="decklist-edit"><span class="fa fa-pencil pull-right"></span></a>');
	}
}

function setup_comment_hide() {
	if(app.user.data && app.user.data.is_author) {
		$('.comment-hide-button').remove();
		$('<a href="#" class="comment-hide-button"><span class="text-danger fa fa-times" style="margin-left:.5em"></span></a>').appendTo('.collapse.in > .comment-date').on('click', function (event) {
			if(confirm('Do you really want to hide this comment for everybody?')) {
				hide_comment($(this).closest('td'));
			}
			return false;
		});
		$('<a href="#" class="comment-hide-button"><span class="text-success fa fa-check" style="margin-left:.5em"></span></a>').appendTo('.collapse:not(.in) > .comment-date').on('click', function (event) {
			if(confirm('Do you really want to unhide this comment?')) {
				unhide_comment($(this).closest('td'));
			}
			return false;
		});
	}
}

function hide_comment(element) {
	var id = element.attr('id').replace(/comment-/, '');
	$.ajax(Routing.generate('decklist_comment_hide', { comment_id: id, hidden: 1 }), {
		type: 'POST',
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			if(data === true) {
				$(element).find('.collapse').collapse('hide');
				$(element).find('.comment-toggler').show().prepend('The comment will be hidden for everyone in a few minutes.');
				setTimeout(setup_comment_hide, 1000);
			} else {
				alert(data);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			alert('An error occured while hiding this comment ('+jqXHR.statusText+'). Reload the page and try again.');
		}
	});
}

function unhide_comment(element) {
	var id = element.attr('id').replace(/comment-/, '');
	$.ajax(Routing.generate('decklist_comment_hide', { comment_id: id, hidden: 0 }), {
		type: 'POST',
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			if(data === true) {
				$(element).find('.collapse').collapse('show');
				$(element).find('.comment-toggler').hide();
				setTimeout(setup_comment_hide, 1000);
			} else {
				alert(data);
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			alert('An error occured while unhiding this comment ('+jqXHR.statusText+'). Reload the page and try again.');
		}
	});
}



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

function edit_form() {
	$('#editModal').modal('show');
}

function delete_form() {
	$('#deleteModal').modal('show');
}

function do_action_decklist(event) {
	var action_id = $(this).attr('id');
	if (!action_id || !SelectedDeck)
		return;
	switch (action_id) {
	case 'btn-download-text':
		location.href = Routing.generate('decklist_export_text', {decklist_id:Decklist.id});
		break;
	case 'btn-download-octgn':
		location.href = Routing.generate('decklist_export_octgn', {decklist_id:Decklist.id});
		break;
	case 'btn-export-bbcode':
		export_bbcode();
		break;
	case 'btn-export-markdown':
		export_markdown();
		break;
	case 'btn-export-plaintext':
		export_plaintext();
		break;
	}
}

function send_like() {
	var obj = $(this);
	$.post(Routing.generate('decklist_like'), {
		id : Decklist.id
	}, function(data, textStatus, jqXHR) {
		obj.find('.num').text(data);
	});
}

function send_favorite() {
	var obj = $(this);
	$.post(Routing.generate('decklist_favorite'), {
		id : Decklist.id
	}, function(data, textStatus, jqXHR) {
		obj.find('.num').text(data);
		var title = obj.data('original-tooltip');
		obj.data('original-tooltip',
				title == "Add to favorites" ? "Remove from favorites"
						: "Add to favorites");
		obj.attr('title', obj.data('original-tooltip'));
	});
	
	send_like.call($('#social-icon-like'));
}
