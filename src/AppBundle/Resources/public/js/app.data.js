(function app_data(data, $) {

/** 
 * loads the database from local
 * sets up a Promise on all data loading/updating
 * @memberOf data
 */
data.load = function load() {

	var fdb = new ForerunnerDB();
	data.db = fdb.db('agot2db');
	
	data.sets = data.db.collection('set', {primaryKey:'code'});
	data.cards = data.db.collection('card', {primaryKey:'code'});
	
	data.dfd = {
		sets: new $.Deferred(),
		cards: new $.Deferred()
	};
	
	$.when(data.dfd.sets, data.dfd.cards).done(data.update_done).fail(data.update_fail);

	data.sets.load(function (err) {
		if(err) {
			data.dfd.sets.reject(false);
			return;
		}
		data.cards.load(function (err) {
			if(err) {
				data.dfd.cards.reject(false);
				return;
			}
			// data has been fetched from local store, triggering event
			$(document).trigger('data.app');
			// then we ask the server if new data is available
			data.query();
		});
	});		
}

/**
 * queries the server to update data
 * @memberOf data
 */
data.query = function query() {
	$.ajax({
		url: Routing.generate('api_sets'),
		success: data.parse_sets,
		error: function () {
			data.dfd.sets.reject(true);
		}
	});
	
	$.ajax({
		url: Routing.generate('api_cards'),
		success: data.parse_cards,
		error: function () {
			data.dfd.cards.reject(true);
		}
	});
};

/**
 * called if all operations (load+update) succeed
 * deferred returns true if data has been updated
 * @memberOf data
 */
data.update_done = function update_done(sets_updated, cards_updated) {
	if(sets_updated || cards_updated) {
		var message = "A new version of the data is available. Click <a href=\"javascript:window.location.reload(true)\">here</a> to reload your page.";
		var alert = $('<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
		$('#wrapper>div.container').prepend(alert);
	}
};

/**
 * called if an operation (load+update) fails
 * deferred returns true if data has been loaded
 * @memberOf data
 */
data.update_fail = function update_fail(sets_loaded, sets_loaded) {
	if(!sets_loaded || !cards_loaded) {
		var message = "Unable to load the data. Click <a href=\"javascript:window.location.reload(true)\">here</a> to reload your page.";
		var alert = $('<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
		$('#wrapper>div.container').prepend(alert);
	} else {
		var message = "Unable to update the data. Click <a href=\"javascript:window.location.reload(true)\">here</a> to reload your page.";
		var alert = $('<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
		$('#wrapper>div.container').prepend(alert);
	}
};

/**
 * updates the database if necessary, from fetched data
 * @memberOf data
 */
data.update_collection = function update_collection(data, collection, lastModified, deferred) {
	/*
	 * we look for a row with last_modified equal or greater than lastModified
	 * if we find one, then the database is up-to-date
	 */
	var newerRecords = collection.find({
		last_modified: {
			'$gte': lastModified
		}
	});
	
	if(newerRecords.length) {
		deferred.resolve(false);
		return;
	}
	
	collection.setData(data);
	collection.insert({last_modified: lastModified});
	
	collection.save(function (err) {
		if(!err) {
			deferred.resolve(true);
		} else {
			deferred.reject(true)
		}
	});
}

/**
 * handles the response to the ajax query for sets data
 * @memberOf data
 */
data.parse_sets = function parse_sets(response, textStatus, jqXHR) {
	var lastModified = new Date(jqXHR.getResponseHeader('Last-Modified')).toISOString();
	data.update_collection(response, data.sets, lastModified, data.dfd.sets);
};

/**
 * handles the response to the ajax query for the cards data
 * @memberOf data
 */
data.parse_cards = function parse_cards(response, textStatus, jqXHR) {
	var lastModified = new Date(jqXHR.getResponseHeader('Last-Modified')).toISOString();
	data.update_collection(response, data.cards, lastModified, data.dfd.cards);
};

$(function() {
	data.load();
});

})(app.data = {}, jQuery);