(function (ui, $) {

    ui.group_coresets = function() {
        var div = $('#owned_packs');
        div.find('[data-id=1], [data-id=1-2], [data-id=1-3]').wrapAll('<div class="btn-group btn-inner-group" />');
        div.show();
    };

    ui.init_select_buttons = function() {
        $('#owned_packs')
            .on('click', '.select-all', function(e) {
                var cycle = $(this).closest('.cycle');
                cycle.find('.btn').addClass('active');
                ui.update_pack_counting(cycle);
            })
            .on('click', '.select-none', function(e) {
                var cycle = $(this).closest('.cycle');
                cycle.find('.btn').removeClass('active');
                ui.update_pack_counting(cycle);
            })
            .on('click', 'label.btn', function() {
                var cycle = $(this).toggleClass('active').closest('.cycle');
                ui.update_pack_counting(cycle);
            });

        $('#save-collection').on('click', function() {
        	
        });
    };

    ui.init_pack_counting = function() {
        $('.cycle').each(function() {
            ui.update_pack_counting(this);
        });
    };

    ui.update_pack_counting = function(el) {
        var cycle = $(el);
        var checked = 0;
        var total = 0;
        cycle.find('label.btn').each(function() {
            if ($(this).hasClass('active')) {
                checked++;
            }

            total++;
        });

        cycle.find('.pack-count').text(checked + ' / ' + total);
    };

    ui.update_selected_packs = function() {
        var packs = [];
        $('#owned_packs label.btn.active').each(function() {
            packs.push($(this).data('id'));
        });
        $('#selected-packs').val(packs.join(','));
        console.log($('#selected-packs').val());
      	app.user.data.owned_packs = $('#selected-packs').val();
      	app.user.store();
    };


    /**
     * called when the DOM is loaded
     * @memberOf ui
     */
    ui.on_dom_loaded = function() {
        ui.group_coresets();
        ui.init_select_buttons();
        ui.init_pack_counting();
    };

    /**
     * called when the app data is loaded
     * @memberOf ui
     */
    ui.on_data_loaded = function() {
    };

    /**
     * called when both the DOM and the data app have finished loading
     * @memberOf ui
     */
    ui.on_all_loaded = function() {
    };
})(app.ui, jQuery);