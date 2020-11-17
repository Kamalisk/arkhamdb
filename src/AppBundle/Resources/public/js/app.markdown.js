(function app_markdown(markdown, $) {

	markdown.setup = function setup(textarea, preview) 
	{
		$(textarea).on('change keyup', function() {
			$(preview).html(DOMPurify.sanitize(marked($(textarea).val())))
		});
		$(textarea).trigger('change');
	}

	markdown.refresh = function refresh(textarea, preview) 
	{
		$(preview).html(DOMPurify.sanitize(marked($(textarea).val())))
	}

	markdown.update = function update(text_markdown, preview) 
	{
		$(preview).html(DOMPurify.sanitize(marked(text_markdown)))
	}

})(app.markdown = {}, jQuery);
