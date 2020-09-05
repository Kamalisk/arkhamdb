(function app_tip(tip, $) {

var cards_zoom_regexp = /card\/(\d\d\d\d\d)$/,
	mode = 'text',
	hide_event = 'mouseout';

function display_card_on_element(card, element, event) {
	var content;
	var back_only = false;
	var front_only = false;
	if ($(element).data('back')) {
		back_only = $(element).data('back');
	}
	if ($(element).data('front')) {
		front_only = $(element).data('front');
	}
	if(mode == 'text') {
		var image = card.imagesrc ? '<div class="card-thumbnail card-thumbnail-3x card-thumbnail-'+card.type_code+'" style="background-image:url('+card.imagesrc+')"></div>' : "";		
		content = image	+ '<h4 class="card-name">' + app.format.name(card) + '</h4>';
		if (!back_only) {
			content = content + '<div class="card-faction">' + app.format.faction(card) + '</div>';
			content = content + '<div><span class="card-type">'+card.type_name+((card.type_code == "agenda" || card.type_code == "act") ? '. Stage '+card.stage : '')+(card.slot ? '. '+card.slot : "")+(card.subtype_name ? '. '+card.subtype_name : "")+'</span></div>';
			content = content + '<div class="card-traits">' + app.format.traits(card) + '</div>';
		}
		
		if (card.type_code == "agenda" || card.type_code == "act"){
			content += '<div class="card-info">' + app.format.info(card) + '</div>';
			content += '<div class="card-flavor">' + card.flavor + '</div><div class="card-text border-'+card.faction_code+'">' + app.format.text(card) + '</div>' 
		} else if (card.type_code == "location"){			
			if (card.back_text){
				content += '<div class="card-text">' + app.format.back_text(card) + '</div>';
			}
			if (card.back_flavor){
				content += '<div class="card-flavor">' + card.back_flavor + '</div>';
			}
			content += '<hr />';
			content += '<div class="card-info">' + app.format.info(card) + '</div>';
			content += '<div class="card-text border-'+card.faction_code+'">' + app.format.text(card) + '</div>';
		}else {
			if (!back_only) {
				content += '<div class="card-info">' + app.format.info(card) + '</div>';
				content += '<div class="card-text border-'+card.faction_code+'">' + app.format.text(card) + '</div>';
				if (card.taboo_text){
					content += '<div class="card-text-taboo border-'+card.faction_code+'">' + app.format.text(card, "taboo_text") + '</div>'
				}
				if (card.taboo_xp){
					content += '<div class="card-text-taboo border-'+card.faction_code+'">This card costs ' + card.taboo_xp + ' additional experience</div>'
				}
			}
			if (!front_only) {
				if (card.double_sided){
					content += '<hr />';
					if (card.back_flavor){
						//content += '<div class="card-flavor">' + card.back_flavor + '</div>';
					}
					if (card.back_text){
						content += '<div class="card-text border-'+card.faction_code+'">' + app.format.back_text(card) + '</div>';
					}
				}
			}
		}
		
		if (card.victory){
			content += '<div class="card-type">Victory ' + card.victory + '.</div>'
		}

		if (card.vengeance){
			content += '<div class="card-type">Vengeance ' + card.vengeance + '.</div>'
		}
		
		content += '<div class="card-pack">' + app.format.pack(card) + '</div>';
	
	}
	else {
		content = card.imagesrc ? '<img src="'+card.imagesrc+'">' : "";
	}

	var qtip = {
		content : {
			text : content
		},
		style : {
			classes : 'card-content qtip-bootstrap qtip-thronesdb qtip-thronesdb-' + mode
		},
		position : {
			my : mode == 'text' ? 'center left' : 'top left',
			at : mode == 'text' ? 'center right' : 'bottom right',
			viewport : $(window)
		},
		show : {
			event : event.type,
			ready : true,
			solo : true
		},
		hide: {
			event: hide_event
		}
	};
	
	$(element).qtip(qtip, event);
}

/**
 * @memberOf tip
 * @param event
 */
tip.display = function display(event) {	
	var code = $(this).data('code');
	var card = app.data.cards.findById(code);
	if (!card) return;
	if ($(this).hasClass('spoiler')){
		var card = {
			"name": "Encounter Spoiler",
			"text": "Encounter cards are hidden by default, click to reveal this card.\n You can also turn off spoiler protection for this session or change the setting in your user preferences\n <i>\"The oldest and strongest emotion of mankind is fear, and the oldest and strongest kind of fear is fear of the unknown\"</i>",
			"traits": "",
			"faction_name": "Spoiler",
			"type_name": "Hidden",
			"pack_name": "Unseen Horrors",
			"flavour": "",
			"position": "???"
		};
		display_card_on_element(card, this, event);
		return
	}
	display_card_on_element(card, this, event);
};

/**
 * @memberOf tip
 * @param event
 */
tip.reveal = function reveal(event) {	
	if ($(this).hasClass('spoiler')){
		$(this).removeClass('spoiler');
		$('.spoiler', this.parentNode.parentNode).removeClass('spoiler');
		event.preventDefault();
	}
};

/**
 * @memberOf tip
 * @param event
 */
tip.update_spoiler_cookie = function update_spoiler_cookie(event) {	
	if ($(this).is(':checked')){
		createCookie("spoilers", "hide");
		window.location.reload(false);
	} else {
		createCookie("spoilers", "show");
		window.location.reload(false);
	}
};

function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        var expires = "; expires=" + date.toUTCString();
    }
    else var expires = "";
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}

/**
 * @memberOf tip
 * @param event
 */
tip.guess = function guess(event) {
	if($(this).hasClass('no-popup')) return;
	var href = $(this).get(0).href;
	if(href && href.match(cards_zoom_regexp)) {
		var code = RegExp.$1;
		var generated_url = Routing.generate('cards_zoom', {card_code:code}, true);
		var card = app.data.cards.findById(code);
		if(card && href === generated_url) {
			display_card_on_element(card, this, event);
		}
	}
}

tip.set_mode = function set_mode(opt_mode) {
	if(opt_mode == 'text' || opt_mode == 'image') {
		mode = opt_mode;
	}
}

tip.set_hide_event = function set_hide_event(opt_hide_event) {
	if(opt_hide_event == 'mouseout' || opt_hide_event == 'unfocus') {
		hide_event = opt_hide_event;
	}
}

$(document).on('start.app', function () {
	$('body').on({
		mouseover : tip.display,
		click : tip.reveal
	}, 'a.card-tip');

	$('body').on({
		mouseover : tip.display,
		click : tip.reveal
	}, 'div.card-tip');
	
	$('body').on({
		click : tip.reveal
	}, '.spoiler');

	$('body').on({
		mouseover : tip.guess
	}, 'a:not(.card-tip)');
	
	$('body').on({
		change : tip.update_spoiler_cookie
	}, '#spoilers');

});
$(document).ready(function () {
	if (readCookie("spoilers") == "show"){
		$('#spoilers').prop('checked', false);
	}
});

})(app.tip = {}, jQuery);
