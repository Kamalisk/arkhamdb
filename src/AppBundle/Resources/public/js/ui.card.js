(function ui_card(ui, $) {

	/**
	 * The user is loaded and they have written a review on the page
	 */
	ui.setup_edit = function setup_edit(review_id, is_faq) 
	{
		if (is_faq){
			var button = $('<button class="btn btn-default" id="faq-button"><span class="glyphicon glyphicon-pencil"></span> Edit FAQ</a>');	
		} else {
			var button = $('<button class="btn btn-default" id="review-button"><span class="glyphicon glyphicon-pencil"></span> Edit Review</a>');
		}
		$('#review-'+review_id+' .review-text').append(button);
		if (is_faq){
			$('#faq-edit-form input[name=review_id').val(review_id);
		}else {
			$('#review-edit-form input[name=review_id').val(review_id);	
		}
		
	}
	
	/**
	 * The user is loaded and they haven't written a review on the page yet
	 */
	ui.setup_write = function setup_write(is_faq)
	{
		if (is_faq){
			var button = $('<button class="pull-right btn btn-default" id="faq-button"><span class="glyphicon glyphicon-plus"></span> Write a FAQ</button>');	
		} else {
			var button = $('<button class="pull-right btn btn-default" id="review-button"><span class="glyphicon glyphicon-plus"></span> Write a review</button>');
		}
		$('#reviews-header').prepend(button);
	}
	
	ui.check_review = function check_review(event)
	{
		var faq = false;
		if (event.data && event.data.is_faq){
			faq = true;
		}
		event.preventDefault();
		
		if (faq){
			var form = $("#faq-edit-form");
		}else {
			if($('#review-form-preview').text().length < 200) {
				alert('Your review must at least 200 characters long.');
				return;
			}
			var form = $("#review-edit-form");
		}
		
		var url = Routing.generate('card_review_post');
		if (faq){
			if(app.user.data.faq_id) {
				url = Routing.generate('card_review_edit');
			}
		}else {
			if(app.user.data.review_id) {
				url = Routing.generate('card_review_edit');
			}	
		}
		
		
		var data = $(this).serialize();
		
		$.ajax(url, {
			data: data,
			type: 'POST',
			dataType: 'json',
			success: function(data, textStatus, jqXHR) {
				ui.notify(form, 'success', "Your review has been posted. It will appear on the site in a few minutes.");
				form.remove();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
				ui.notify(form, 'danger', jqXHR.responseJSON.message);
			}
		});
	}
	
	ui.notify = function notify(form, type, message)
	{
		var alert = $('<div class="alert" role="alert"></div>').addClass('alert-'+type).text(message);
		$(form).after(alert);
	}
	
	/**
	 * The user has clicked on the button to write a new review or edit the current one
	 * This function adds a review form to the page
	 */
	ui.write_review_open = function write_review_open(event)
	{
		var button = this;
		$(button).remove();

		/**
		 * Display the form
		 */
		
		var faq = false;
		if (event.data && event.data.is_faq){
			faq = true;
		}
		
		if (faq){
			var form = $("#faq-edit-form"); 	
		} else {
			var form = $("#review-edit-form"); 	
		}
		
		if (faq && app.user && app.user.data.can_faq ){
			form.append('<div><div class="form-group">'
				+ '<textarea id="faq-form-text" class="form-control" rows="20" name="review" placeholder="Write your analysis of the card, in at least 200 characters. You can write a number of card reviews equal to your reputation. This is not a place for questions or comments. Type # to enter a card name. Type $ to enter a symbol."></textarea>'
				+ '</div><div class="well text-muted" id="faq-form-preview"><small>Preview. Look <a href="http://daringfireball.net/projects/markdown/dingus">here</a> for a Markdown syntax reference.</small></div>'
				+ '<input type="hidden" name="is_faq" id="is_faq" value="1">'
				+ '<button type="submit" class="btn btn-success">Submit FAQ</button></div>');
		} else {
			form.append('<div><div class="form-group">'
				+ '<textarea id="review-form-text" class="form-control" rows="20" name="review" placeholder="Write your analysis of the card, in at least 200 characters. You can write a number of card reviews equal to your reputation. This is not a place for questions or comments. Type # to enter a card name. Type $ to enter a symbol."></textarea>'
				+ '</div><div class="well text-muted" id="review-form-preview"><small>Preview. Look <a href="http://daringfireball.net/projects/markdown/dingus">here</a> for a Markdown syntax reference.</small></div>'
				+ '<button type="submit" class="btn btn-success">Submit review</button></div>');	
		}
		
		
		
		/**
		 * Setup the Markdown preview and Textcomplete shortcuts
		 */
		if (faq){
			form.on('submit', {"is_faq": true}, ui.check_review);
			app.markdown.setup('#faq-form-text', '#faq-form-preview');
			app.textcomplete.setup('#faq-form-text');
		}else {
			form.on('submit', ui.check_review);
			app.markdown.setup('#review-form-text', '#review-form-preview');
			app.textcomplete.setup('#review-form-text');
		}
		/**
		 * If the User already wrote a review, we fill the form with the current values
		 */
		 if (faq){
		 	if(app.user.data.faq_id) {
				//$('#is_faq').prop( "checked", true );
				$('#faq-form-text').val(app.user.data.faq_text).trigger('keyup');
			}
		 } else {
			if(app.user.data.review_id) {
				$('#review-form-text').val(app.user.data.review_text).trigger('keyup');
			}
		}
	}
	
	/**
	 * The user has clicked on "Add a comment"
	 * Thsi function replace that button with a one-line for to input and submit the comment
	 */
	ui.write_comment = function write_comment(event)
	{
		event.preventDefault();
		$(this).replaceWith('<div class="input-group"><input type="text" class="form-control" name="comment" placeholder="Your comment"><span class="input-group-btn"><button class="btn btn-primary" type="submit">Post</button></span></div>');
	}

	/**
	 * The user has clicked on "Submit the comment"
	 * @param event
	 */
	ui.form_comment_submit = function form_comment_submit(event)
	{
		event.preventDefault();
		var form = $(this);
		if(form.data('submitted')) return;
		form.data('submitted', true);
		$.ajax(form.attr('action'), {
			data: form.serialize(),
			type: 'POST',
			dataType: 'json',
			success: function(data, textStatus, jqXHR) {
				ui.notify(form, 'success', "Your comment has been posted. It will appear on the site in a few minutes.");
				form.remove();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
				ui.notify(form, 'danger', jqXHR.responseBody.message);
			}
		});
	}
	
	ui.like_review = function like_review(event)
	{
		event.preventDefault();
		var obj = $(this);
		var review_id = obj.closest('article.review').data('id');
		$.post(Routing.generate('card_review_like'), {
			id : review_id
		}, function(data, textStatus, jqXHR) {
			obj.find('.num').text(jqXHR.responseJSON.nbVotes);
		});
	}
	
	/**
	 * called when the DOM is loaded
	 * @memberOf ui
	 */
	ui.on_dom_loaded = function on_dom_loaded() 
	{
		app.user.loaded.done(function () {
			if(app.user.data.review_id) {
				ui.setup_edit(app.user.data.review_id);
			}
			if(app.user.data.faq_id) {
				ui.setup_edit(app.user.data.faq_id, true);
			} 
			if(!app.user.data.review_id) {
				ui.setup_write();				
			}
			if (app.user && app.user.data.can_faq ){
				if(!app.user.data.faq_id) {
					ui.setup_write(true);				
				}
			}
		});
		
		$(window.document).on('click', '.btn-write-comment', ui.write_comment);
		$(window.document).on('click', '.social-icon-like', ui.like_review);
		$(window.document).on('click', '#review-button', ui.write_review_open);
		$(window.document).on('click', '#faq-button', {'is_faq': true}, ui.write_review_open);
		$(window.document).on('submit', 'form.form-comment', ui.form_comment_submit);
	};

})(app.ui, jQuery);
