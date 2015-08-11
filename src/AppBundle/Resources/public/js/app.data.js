(function app_data(data, $) {

var first_run = false;

/**
 * loads the database from local
 * sets up a Promise on all data loading/updating
 * @memberOf data
 */
data.load = function load() {

	var fdb = new ForerunnerDB();
	data.db = fdb.db('thronesdb');

	data.masters = {
		packs: data.db.collection('master_pack', {primaryKey:'code', changeTimestamp: true}),
		cards: data.db.collection('master_card', {primaryKey:'code', changeTimestamp: true})
	};

	data.dfd = {
		packs: new $.Deferred(),
		cards: new $.Deferred()
	};

	$.when(data.dfd.packs, data.dfd.cards).done(data.update_done).fail(data.update_fail);

	data.masters.packs.load(function (err) {
		if(err) {
			console.log('packs loading error', err);
			data.dfd.packs.reject(false);
			return;
		}
		data.masters.cards.load(function (err) {
			if(err) {
				console.log('cards loading error', err);
				data.dfd.cards.reject(false);
				return;
			}

			/*
			 * data has been fetched from local store, triggering event
			 * unless we don't have any data yet, in which case we will wait until data is updated before firing the event
			 */
			if(data.masters.packs.count() === 0 || data.masters.cards.count() === 0) {
				first_run = true;
			} else {
				data.release();
			}

			/*
			 * then we ask the server if new data is available
			 */
			data.query();
		});
	});
}

/**
 * release the data for consumption by other modules
 * @memberOf data
 */
data.release = function release() {
	data.packs = data.db.collection('pack', {primaryKey:'code', changeTimestamp: false});
	data.packs.setData(data.masters.packs.find());

	data.cards = data.db.collection('card', {primaryKey:'code', changeTimestamp: false});
	data.cards.setData(data.masters.cards.find());

	$(document).trigger('data.app');
}

/**
 * queries the server to update data
 * @memberOf data
 */
data.query = function query() {
	$.ajax({
		url: Routing.generate('api_packs'),
		success: data.parse_packs,
		error: function () {
			data.dfd.packs.reject(true);
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
data.update_done = function update_done(packs_updated, cards_updated) {
	if(packs_updated || cards_updated) {
		if(first_run) {
			data.release();
		} else {
			var message = "A new version of the data is available. Click <a href=\"javascript:window.location.reload(true)\">here</a> to reload your page.";
			var alert = $('<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
			$('#wrapper>div.container').prepend(alert);
		}
	}
};

/**
 * called if an operation (load+update) fails
 * deferred returns true if data has been loaded
 * @memberOf data
 */
data.update_fail = function update_fail(packs_loaded, cards_loaded) {
	if(!packs_loaded || !cards_loaded) {
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
	var lastChangeDatabase = new Date(collection.metaData().lastChange).getTime();
	var lastModifiedData = lastModified.getTime();

	/*
	 * if the database is not older than the data, we don't have to update the database
	 */
	if(lastChangeDatabase && lastChangeDatabase >= lastModifiedData) {
		deferred.resolve(false);
		return;
	}

	collection.setData(data);

	collection.save(function (err) {
		if(!err) {
			deferred.resolve(true);
		} else {
			deferred.reject(true)
		}
	});
}

/**
 * handles the response to the ajax query for packs data
 * @memberOf data
 */
data.parse_packs = function parse_packs(response, textStatus, jqXHR) {
	var lastModified = new Date(jqXHR.getResponseHeader('Last-Modified'));
	data.update_collection(response, data.masters.packs, lastModified, data.dfd.packs);
};

/**
 * handles the response to the ajax query for the cards data
 * @memberOf data
 */
data.parse_cards = function parse_cards(response, textStatus, jqXHR) {
	var lastModified = new Date(jqXHR.getResponseHeader('Last-Modified'));
	data.update_collection(response, data.masters.cards, lastModified, data.dfd.cards);
};

$(function() {
	data.load();
});

})(app.data = {}, jQuery);
