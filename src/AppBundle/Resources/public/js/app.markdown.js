(function app_markdown(markdown, $) {

	markdown.setup = function setup(textarea, preview) 
	{
		$(textarea).on('change keyup', function() {
			$(preview).text(marked($(textarea).val()))
		});
		$(textarea).trigger('change');
	}

	markdown.refresh = function refresh(textarea, preview) 
	{
		$(preview).text(marked($(textarea).val()))
	}

	markdown.update = function update(text_markdown, preview) 
	{
		$(preview).text(marked(text_markdown))
	}

})(app.markdown = {}, jQuery);
