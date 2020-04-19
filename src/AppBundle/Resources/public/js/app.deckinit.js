
(function app_deck_init(deck_init, $) {
	var deck_init_config = null;

	var faction_selected = "";
	var owned = false;
	var order = "name";

	deck_init.show_faction = function(faction) {
		deck_init.faction_selected = faction;
		deck_init.update_display();
	}
	deck_init.toggle_owned = function() {
		deck_init.owned = !deck_init.owned;
		deck_init.update_display();
		deck_init_config.all = !deck_init.owned;
		if (localStorage) {
			localStorage.setItem('ui.deck.init', JSON.stringify(deck_init_config));
		}
	}
	deck_init.change_order = function() {
		deck_init.order = $("#order").val();
		deck_init_config.order = deck_init.order;
		if (localStorage) {
			localStorage.setItem('ui.deck.init', JSON.stringify(deck_init_config));
		}
		deck_init.update_display();
	}
	deck_init.sort = function (order) {
		var divs = $("#investigator_divs > div");

		var sort_name_function = function (a, b) {
			if ($(a).attr("data-name").replace(/[^A-Za-z0-9 ]/, "") > $(b).attr("data-name").replace(/[^A-Za-z0-9 ]/, "")) {
				return 1;
			}
			return -1;
		}

		var sort_function = sort_name_function;
		if (order == "faction") {
			sort_function = function(a, b) {
				if ($(a).attr("data-class") == $(b).attr("data-class")) {
					return sort_name_function(a, b);
				}
				if ($(a).attr("data-class") > $(b).attr("data-class")) {
					return 1;
				}
				return -1;
			}
		} else if (order == "willpower" || order == "agility" || order == "combat" || order == "intellect") {
			sort_function = function(a, b) {
				if ($(a).attr("data-"+order) == $(b).attr("data-"+order)) {
					return sort_name_function(a, b);
				}
				if ($(a).attr("data-"+order) > $(b).attr("data-"+order)) {
					return -1;
				}
				return 1;
			}
		}

		divs.sort(sort_function);
		$("#investigator_divs").empty();
		$("#investigator_divs").append(divs);
	}
	deck_init.update_display = function () {
		deck_init.sort(deck_init.order);
		$(".faction-filter").removeClass("selected");
		if (deck_init.faction_selected) {
			$("#filter_title").text(deck_init.faction_selected.charAt(0).toUpperCase() + deck_init.faction_selected.substring(1));
			$(".faction-"+deck_init.faction_selected).addClass("selected");
			$(".inv").hide();
			if (deck_init.owned) {
				$(".owned.inv-class-"+deck_init.faction_selected).show();
			} else {
				$(".inv-class-"+deck_init.faction_selected).show();
			}
		} else {
			$("#filter_title").text("All");
			$(".faction-all").addClass("selected");
			$(".inv").hide();
			if (deck_init.owned) {
				$(".owned.inv").show();
			} else {
				$(".inv").show();
			}
		}
	}

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

	$(document).ready(function(){
		if (localStorage) {
			var stored = localStorage.getItem('ui.deck.init');
			if(stored) {
				deck_init_config = JSON.parse(stored);
			}
		}
		deck_init_config = _.extend({
			'all': true,
		}, deck_init_config || {});

		if (deck_init_config.all){
			deck_init.owned = false;
		} else {
			deck_init.owned = true;
		}
		if (deck_init_config.order){
			deck_init.order = deck_init_config.order;
			$("#order").val(deck_init.order);
		}
		$("#deck_init_all").attr("checked", deck_init.owned);
		deck_init.show_faction('');
	});
})(app.deck_init = {}, jQuery);