(function app_data(data, $) {

var force_update = false;

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
			console.log('error when loading packs', err);
			data.dfd.packs.reject(false);
			return;
		}
		data.masters.cards.load(function (err) {
			if(err) {
				console.log('error when loading cards', err);
				data.dfd.cards.reject(false);
				return;
			}

			/*
			 * data has been fetched from local store
			 */

			/*
			 * if database is older than 10 days, we assume it's obsolete and delete it
			 */
			var age_of_database = new Date() - new Date(data.masters.cards.metaData().lastChange);
			if(age_of_database > 864000000) {
				console.log('database is older than 10 days => refresh it');
				data.masters.packs.setData([]);
				data.masters.cards.setData([]);
			}

			/*
			 * if database is empty, we will wait for the new data
			 */
			if(data.masters.packs.count() === 0 || data.masters.cards.count() === 0) {
				console.log('database is empty => load it');
				force_update = true;
			}

			/*
			 * triggering event that data is loaded
			 */
			if(!force_update) {
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
		error: function (jqXHR, textStatus, errorThrown) {
			console.log('error when requesting packs', errorThrown);
			data.dfd.packs.reject(true);
		}
	});

	$.ajax({
		url: Routing.generate('api_cards'),
		success: data.parse_cards,
		error: function (jqXHR, textStatus, errorThrown) {
			console.log('error when requesting cards', errorThrown);
			data.dfd.cards.reject(true);
		}
	});
};

/**
 * called if all operations (load+update) succeed (resolve)
 * deferred returns true if data has been updated
 * @memberOf data
 */
data.update_done = function update_done(packs_updated, cards_updated) {
	if(force_update) {
		data.release();
		return;
	}

	if(packs_updated || cards_updated) {
		/*
		 * we display a message informing the user that they can reload their page to use the updated data
		 * except if we are on the front page, because data is not essential on the front page
		 */
		if($('.site-title').size() === 0) {
			var message = "A new version of the data is available. Click <a href=\"javascript:window.location.reload(true)\">here</a> to reload your page.";
			var alert = $('<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+message+'</div>');
			$('#wrapper>div.main.container:first').prepend(alert);
		}
	}
};

/**
 * called if an operation (load+update) fails (reject)
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
data.update_collection = function update_collection(data, collection, lastModifiedData, deferred) {
	var lastChangeDatabase = new Date(collection.metaData().lastChange)
	var is_collection_updated = false;

	/*
	 * if we decided to force the update,
	 * or if the database is fresh,
	 * or if the database is older than the data,
	 * then we update the database
	 */
	if(force_update || !lastChangeDatabase || lastChangeDatabase < lastModifiedData) {
		console.log('data is newer than database or update forced => update the database')
		collection.setData(data);
		is_collection_updated = true;
	}

	collection.save(function (err) {
		if(err) {
			console.log('error when saving '+collection.name(), err);
			deferred.reject(true)
		} else {
			deferred.resolve(is_collection_updated);
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
