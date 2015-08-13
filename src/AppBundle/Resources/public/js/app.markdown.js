(function app_markdown(markdown, $) {

	var converter = new Markdown.Converter();

	markdown.setup = function setup(textarea, preview) {

		$(textarea).on('keyup', function() {
			$(preview).html(converter.makeHtml($(textarea).val()))
		});

	}

	markdown.refresh = function refresh(textarea, preview) {

		$(preview).html(converter.makeHtml($(textarea).val()))

	}

	markdown.update = function update(text_markdown, preview) {

		$(preview).html(converter.makeHtml(text_markdown))

	}

})(app.markdown = {}, jQuery);
