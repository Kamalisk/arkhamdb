
(function app_deck_init(deck_init, $) {
	var deck_init_config = null;

	deck_init.update_build_init = function(){
		$("#all_inv").toggleClass("hidden");
		$("#my_inv").toggleClass("hidden");
		if ($("#all_inv").hasClass("hidden")){
			deck_init_config.all = false;
		} else {
			deck_init_config.all = true;
		}
		if (localStorage) {
			localStorage.setItem('ui.deck.init', JSON.stringify(deck_init_config));
		}
	}
	
	deck_init.on_all_loaded = function() {	
		window.alert("rah?");
	}
	
	$(document).ready(function(){
		if (localStorage) {
			var stored = localStorage.getItem('ui.deck.init');
			if(stored) {
				deck_init_config = JSON.parse(stored);
			}
		}
		deck_init_config = _.extend({
			'all': false,
		}, deck_init_config || {});
		
		if (deck_init_config.all){
			$("#all_inv").removeClass("hidden");
			$("#my_inv").addClass("hidden");
			$("#deck_init_all").val("all");
		} else {
			$("#all_inv").addClass("hidden");
			$("#my_inv").removeClass("hidden");
			$("#deck_init_all").val("your");
		}
		
	});
})(app.deck_init = {}, jQuery);