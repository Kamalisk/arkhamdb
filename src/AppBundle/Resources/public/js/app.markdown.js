(function app_markdown(markdown, $) {

	markdown.setup = function setup(textarea, preview) 
	{
		$(textarea).on('change keyup', function() {
			$(preview).html(marked($(textarea).val()))
		});
		$(textarea).trigger('change');
	}

	markdown.refresh = function refresh(textarea, preview) 
	{
		$(preview).html(marked($(textarea).val()))
	}

	markdown.update = function update(text_markdown, preview) 
	{
		$(preview).html(marked(text_markdown))
	}

})(app.markdown = {}, jQuery);
