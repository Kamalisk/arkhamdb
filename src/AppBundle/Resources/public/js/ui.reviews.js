
$(function () {
	$(window.document).on('click', '#review-button', write_review_open);
	$(window.document).on('click', '.social-icon-like', like_review);
	$(window.document).on('click', '.btn-write-comment', write_comment);
	$(window.document).on('submit', 'form.form-comment', form_comment_submit);
	
	$('time').each(function (index, element) {
		var datetime = moment($(element).attr('datetime'));
		$(element).html(datetime.calendar());
		$(element).attr('title', datetime.format('LLLL'));
	});
});

$.when(app.user.deferred).then(function() {
	if(app.user.data) {
		if(app.user.data.review_id) {
			setup_edit();
		} else {
			setup_write();
		}
	}
});

function write_comment(event) {
	$(this).replaceWith('<div class="input-group"><input type="text" class="form-control" name="comment" placeholder="Your comment"><span class="input-group-btn"><button class="btn btn-primary" type="submit">Post</button></span></div>');
}

function form_comment_submit(event) {
	event.preventDefault();
	var form = $(this);
	if(form.data('submitted')) return;
	form.data('submitted', true);
	$.ajax(form.attr('action'), {
		data: form.serialize(),
		type: 'POST',
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			if(data === true) {
				form.replaceWith('<div class="alert alert-success" role="alert">Your comment has been posted. It will appear on the site in a few minutes.</div>');
			} else {
				form.replaceWith('<div class="alert alert-danger" role="alert">'+data+'</div>');
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
			form.replaceWith('<div class="alert alert-danger" role="alert">An error occured while posting your comment ('+jqXHR.statusText+'). Reload the page and try again.</div>');
		}
	});
}

function setup_write() {
	$('#reviews-header').prepend('<button class="pull-right btn btn-default" id="review-button"><span class="glyphicon glyphicon-pencil"></span> Write a review</button>');
}

function setup_edit() {
	var review_id = app.user.data.review_id;
	$('#review-'+review_id+' .review-text').append('<button class="btn btn-default" id="review-button"><span class="glyphicon glyphicon-pencil"></span> Edit review</a>');
	$('input[name=review_id').val(review_id);
}

function like_review(event) {
	event.preventDefault();
	var obj = $(this);
	var review_id = obj.closest('article.review').data('index');
	$.post(Routing.generate('card_review_like'), {
		id : review_id
	}, function(data, textStatus, jqXHR) {
		obj.find('.num').text(data);
	});
}

function write_review_open(event) {
	if(!app.user.data) {
		alert('You must be logged in to write a card review.');
		return;
	}
	var form = $("#review-edit-form");
	$(this).remove();
	
	form.append('<div><div class="form-group">'
			+ '<textarea id="review-form-text" class="form-control" rows="20" name="review" placeholder="Write your analysis of the card, in at least 200 characters. You can write a number of card reviews equal to your reputation. This is not a place for questions or comments. Type # to enter a card name. Type $ to enter a symbol."></textarea>'
			+ '</div><div class="well text-muted" id="review-form-preview"><small>Preview. Look <a href="http://daringfireball.net/projects/markdown/dingus">here</a> for a Markdown syntax reference.</small></div>'
			+ '<button type="submit" class="btn btn-success">Submit review</button></div>');
	
	form.on('submit', function (event) {
		event.preventDefault();
		if($('#review-form-preview').text().length < 200) {
			alert('Your review must at least 200 characters long.');
			return;
		}
		var url = Routing.generate('card_review_post');
		if(app.user.data.review_id) {
			url = Routing.generate('card_review_edit');
		}
		var data = $(this).serialize();
		$.ajax(url, {
			data: data,
			type: 'POST',
			dataType: 'json',
			success: function(data, textStatus, jqXHR) {
				if(data === true) {
					form.replaceWith('<div class="alert alert-success" role="alert">Your review has been posted. It will appear on the site in a few minutes.</div>');
				} else {
					form.replaceWith('<div class="alert alert-danger" role="alert">An error occured while posting your review. '+data+'</div>');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
				form.replaceWith('<div class="alert alert-danger" role="alert">An error occured while posting your review ('+jqXHR.statusText+'). Reload the page and try again.</div>');
			}
		});
	});
	
	var converter = new Markdown.Converter();
	$('#review-form-text').on(
		'keyup',
		function() {
			$('#review-form-preview').html(converter.makeHtml($('#review-form-text').val()));
		}
	);
	
	$('#review-form-text').textcomplete(
			[
					{
						match : /\B#([\-+\w]*)$/,
						search : function(term, callback) {
							callback(app.data.cards({
								title : {
									likenocase : term
								},
								cyclenumber : {
									'!=': 0
								}
							}).get());
						},
						template : function(value) {
							return value.title;
						},
						replace : function(value) {
							return '[' + value.title + ']('
									+ Routing.generate('cards_zoom', {card_code:value.code})
									+ ')';
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

	if(app.user.data.review_id) {
		$('#review-form-text').val(app.user.data.review_text).trigger('keyup');
	}

}
