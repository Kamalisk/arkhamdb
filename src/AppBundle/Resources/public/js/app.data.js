(function app_data(data, $) {

var force_update = false;

/**
 * loads the database from local
 * sets up a Promise on all data loading/updating
 * @memberOf data
 */
data.load = function load() {

	data.isLoaded = false;

	var fdb = new ForerunnerDB();
	data.db = fdb.db('arkhamdb');
	// seems that indexedDB is failing in chrome, so switching to localstorage for now
	data.db.persist.driver("IndexedDB");

	data.masters = {
		packs: data.db.collection('master_pack', {primaryKey:'code', changeTimestamp: true}),
		cards: data.db.collection('master_card', {primaryKey:'code', changeTimestamp: true}),
		taboos: data.db.collection('master_taboo', {primaryKey:'code', changeTimestamp: true})
	};

	data.dfd = {
		packs: new $.Deferred(),
		cards: new $.Deferred(),
		taboos: new $.Deferred()
	};

	$.when(data.dfd.packs, data.dfd.cards, data.dfd.taboos).done(data.update_done).fail(data.update_fail);

		// load pack data
	data.masters.taboos.load(function (err) {
		if(err) {
			console.log('error when loading taboos', err);
			force_update = true;
		}
		// load pack data
		data.masters.packs.load(function (err) {
			if(err) {
				console.log('error when loading packs', err);
				force_update = true;
			}
			// loading cards
			data.masters.cards.load(function (err) {
				if(err) {
					console.log('error when loading cards', err);
					force_update = true;
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
					force_update = true;
				}

				/*
				 * if database is empty, we will wait for the new data
				 */
				if(data.masters.packs.count() === 0 || data.masters.cards.count() === 0 || data.masters.taboos.count() === 0) {
					console.log('database is empty => load it', data.masters.packs.count(), data.masters.cards.count(), data.masters.taboos.count());
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

	data.taboos = data.db.collection('taboo', {primaryKey:'code', changeTimestamp: false});
	data.taboos.setData(data.masters.taboos.find());

	data.isLoaded = true;

	$(document).trigger('data.app');
}

/**
 * triggers a forced update of the database
 * @memberOf data
 */
data.update = function update() {
	_.each(data.masters, function (collection) {
		collection.drop();
	});
	data.load();
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
			data.dfd.packs.reject(false);
		}
	});

	$.ajax({
		url: Routing.generate('api_taboos'),
		success: data.parse_taboos,
		error: function (jqXHR, textStatus, errorThrown) {
			console.log('error when requesting taboos', errorThrown);
			data.dfd.taboos.reject(false);
		}
	});

	$.ajax({
		url: Routing.generate('api_cards')+"?encounter=1",
		success: data.parse_cards,
		error: function (jqXHR, textStatus, errorThrown) {
			console.log('error when requesting cards', errorThrown);
			data.dfd.cards.reject(false);
		}
	});
};

/**
 * called if all operations (load+update) succeed (resolve)
 * deferred returns true if data has been updated
 * @memberOf data
 */
data.update_done = function update_done(packs_updated, cards_updated, taboos_updated) {
	if(force_update) {
		data.release();
		return;
	}

	if(packs_updated === true || cards_updated === true) {
		/*
		 * we display a message informing the user that they can reload their page to use the updated data
		 * except if we are on the front page, because data is not essential on the front page
		 */
		if($('.site-title').size() === 0) {
			var message = "A new version of the data is available. Click <a href=\"javascript:window.location.reload(true)\">here</a> to reload your page.";
			app.ui.insert_alert_message('warning', message);
		}
	}
};

/**
 * called if an operation (load+update) fails (reject)
 * deferred returns true if data has been loaded
 * @memberOf data
 */
data.update_fail = function update_fail(packs_loaded, cards_loaded) {
	if(packs_loaded === false || cards_loaded === false) {
		var message = "Unable to load the data. Click <a href=\"javascript:window.location.reload(true)\">here</a> to reload your page.";
		app.ui.insert_alert_message('danger', message);
	} else {
		/*
		 * since data hasn't been persisted, we will have to do the query next time as well
		 * -- not much we can do about it
		 * but since data has been loaded, we call the promise
		 */
		data.release();
	}
};

/**
 * updates the database if necessary, from fetched data
 * @memberOf data
 */
data.update_collection = function update_collection(data, collection, lastModifiedData, deferred) {
	var lastChangeDatabase = new Date(collection.metaData().lastChange)
	var isCollectionUpdated = false;

	/*
	 * if we decided to force the update,
	 * or if the database is fresh,
	 * or if the database is older than the data,
	 * then we update the database
	 */
	if(force_update || !lastChangeDatabase || lastChangeDatabase < lastModifiedData) {
		console.log('data is newer than database or update forced => update the database')
		collection.setData(data);
		isCollectionUpdated = true;
	}

	collection.save(function (err) {
		if(err) {
			console.log('error when saving '+collection.name(), err, collection);
			deferred.reject(true)
		} else {
			deferred.resolve(isCollectionUpdated);
		}
	});
}

data.apply_taboos = function apply_taboos(taboo_id){
	// reset previous taboo changes
	app.data.cards.find({'taboo':true}).forEach(function (card){
		var update = {};
		update.taboo = false;
		if (card.taboo_exceptional){
			update.exceptional = false;
			update.taboo_exceptional = false;
		}
		if (card.taboo_remove_exceptional) {
			update.exceptional = true;
			update.taboo_remove_exceptional = false;
		}
		if (card.taboo_deck_requirements) {
			update.deck_requirements = card.deck_requirements;
			update.taboo_deck_requirements = null;
		}
		if (card.taboo_deck_options) {
			update.deck_options = card.taboo_deck_options;
			update.taboo_deck_options = null;
		}
		update.taboo_xp = 0;
		update.taboo_text = "";
		app.data.cards.updateById(card.code, update);
	});

	//console.log("taboos", taboo_id);
	var taboo = app.data.taboos.findOne({'id':parseInt(taboo_id)});
	if (taboo){
		var parsed_taboo = JSON.parse(taboo.cards);
		parsed_taboo.forEach(function (taboo_card){
			//console.log(taboo_card);
			var og_card = app.data.cards.findOne({'code':taboo_card.code});
			var cards = app.data.cards.find({'duplicate_of_code':taboo_card.code});
			cards.push(og_card);
			cards.forEach(function (card){
				var update = {'taboo': true};
				if (taboo_card.exceptional){
					update.exceptional = true;
					update.taboo_exceptional = true;
				} else if (taboo_card.exceptional === false) {
					update.exceptional = false;
					update.taboo_remove_exceptional = true;
				}

				if (taboo_card.deck_options) {
					update.taboo_deck_options = card.deck_options;
					update.deck_options = taboo_card.deck_options;
				}
				if (taboo_card.deck_requirements) {
					update.taboo_deck_requirements = card.deck_requirements;
					update.deck_requirements = taboo_card.deck_requirements;
				}
				if (taboo_card.xp){
					update.taboo_xp = taboo_card.xp;
				}
				if (taboo_card.text){
					update.taboo_text = taboo_card.text;
				}
				app.data.cards.updateById(card.code, update);
			});
		});
	}
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

/**
 * handles the response to the ajax query for the cards data
 * @memberOf data
 */
data.parse_taboos = function parse_taboos(response, textStatus, jqXHR) {
	var lastModified = new Date(jqXHR.getResponseHeader('Last-Modified'));
	data.update_collection(response, data.masters.taboos, lastModified, data.dfd.taboos);
};

$(function() {
	data.load();
});

})(app.data = {}, jQuery);
