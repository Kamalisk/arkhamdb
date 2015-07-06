app.user = {};
(function(user, $) {

	user.params = {};
	user.deferred = $.Deferred().always(function() {
		if(user.data) {
			user.update();
		} else {
			user.anonymous();
		}
		user.always();
	});
	
	user.query = function () {
		$.ajax(Routing.generate('user_info', user.params), {
			cache: false,
			dataType: 'json',
			success: function(data, textStatus, jqXHR) {
				user.data = data;
				user.deferred.resolve();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log('['+moment().format('YYYY-MM-DD HH:mm:ss')+'] Error on '+this.url, textStatus, errorThrown);
				user.deferred.reject();
			}
		});
	};
	
	user.retrieve = function () {
		if(localStorage) {
			var timestamp = new Date(parseInt(localStorage.getItem('user_timestamp'),10));
			var now = new Date();
			if(now - timestamp < 3600000) {
				var storedData = localStorage.getItem('user');
				if(storedData) {
					user.data = JSON.parse(storedData);
					user.deferred.resolve();
					return;
				}
			}
		}
		user.query();
	};
	
	user.wipe = function () {
		localStorage.removeItem('user');
		localStorage.removeItem('user_timestamp');
	};
	
	user.store = function () {
		localStorage.setItem('user', JSON.stringify(user.data));
		localStorage.setItem('user_timestamp', new Date().getTime());
	};
	
	user.anonymous = function() {
		user.wipe();
		$('#login').append('<ul class="dropdown-menu"><li><a href="'+Routing.generate('fos_user_security_login')+'">Login or Register</a></li></ul>');
	};
	
	user.update = function() {
		user.store();
		$('#login').addClass('dropdown').append('<ul class="dropdown-menu"><li><a href="'
				+ Routing.generate('user_profile') 
				+ '">Edit account</a></li><li><a href="'
				+ user.data.public_profile_url 
				+ '">Public profile</a></li><li><a href="'
				+ Routing.generate('user_comments')
				+ '">Comments</a></li><li><a href="'
				+ Routing.generate('fos_user_security_logout') 
				+ '" onclick="app.user.wipe()">Jack out</a></li></ul>');
	};
	
	user.always = function() {
		// show ads if not donator
		if(user.data && user.data.donation > 0) return;

		adsbygoogle = window.adsbygoogle || [];
		
		$('div.ad').each(function (index, element) {
			$(element).show();
			adsbygoogle.push({});
		});
	
		if($('ins.adsbygoogle').filter(':visible').length === 0) {
			$('div.ad').each(function (index, element) {
				$(element).addClass('ad-blocked').html("No ad,<br>no <span class=\"icon icon-credit\"></span>.<br>Like app?<br>Whitelist us<br>or <a href=\""+Routing.generate('donators')+"\">donate</a>.");
			});
		}
	}
	
	$(function() {
		if($.isEmptyObject(user.params)) {
			user.retrieve();
		} else {
			user.query();
		}
	});
	
})(app.user, jQuery);

