
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: _CATEGORY-SELECTOR.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

(function($) {
    $.categorySelector = function(element, options){
        var self = this;

        this.options = $.extend({}, $.categorySelector.defaultOptions, options);

        this.$element = $(element);
        this.$select_category = this.$element.find(this.options.categoryContainer);
        this.$select_type = this.$element.find(this.options.typeContainer);

        this.init = function(){
            this.mediaPoints();
            this.typeChange();
            this.loadCategoryLevel();
        }

        /**
         * Media point change handler
         */
        this.mediaPoints = function(){
            enquire.register(flUtil.media_points.all_tablet_mobile, {
                match: function(){
                    self.$select_category.find('li select')
                        .removeAttr('size');
                },
                unmatch: function(){
                    self.$select_category.find('li select')
                        .attr('size', 10);
                }
            });
        }

        /**
         * Types change handler
         */
        this.typeChange = function(){
            this.$select_type.find('input').change(function(){
                // Show related section
                self.$select_category.find('li.show').removeClass('show');

                var $related_container = self.$select_category.find('li[data-type-key='+ $(this).val() +']');
                $related_container.addClass('show');

                var single_category_id = $(this).data('single-category-id');

                // Simulate option change
                if (single_category_id) {
                    if (typeof self.options.onChange == 'function') {
                        var $option = $('<option>')
                                        .attr('data-path', $(this).data('single-category-path'))
                                        .text($(this).data('single-category-name'))
                                        .val(single_category_id);
                        var $select = $('<select>')
                                        .append($option)
                                        .val(single_category_id);
                        self.options.onChange.call(self, $select, $option, single_category_id);

                        if (self.options.actionButtonDefaultAction) {
                            self.options.actionButton.removeClass('disabled');
                        }
                    }
                }
                // Load content
                else {
                    if ($related_container.find('select.tmp').length) {
                        $related_container.find('select.tmp').val(0).trigger('change');
                    } else {
                        if (!$related_container.find('select').val()) {
                            self.options.actionButton.addClass('disabled');
                        }
                    }
                }
            });

            // Select requested type
            if (this.options.selectedType) {
                this.$select_type.find('input[value=' + this.options.selectedType + ']')
                    .trigger('click');
            }
            // Select first if no one checked
            else if (!this.$select_type.find('input:checked').length) {
                this.$select_type.find('input:first').trigger('click');
            }
        }

        /**
         * Load category level handler
         */
        this.loadCategoryLevel = function(){
            var current_key = this.$select_category.find('> li.show').data('type-key');

            // select on change listener
            this.$select_category.on('change', 'select', function(e){
                var $select = $(this);
                var $option = $(this).find('option:selected');
                var $container = $select.closest('div');
                var id = $select.val();
                var type = $select.closest('li').data('type-key');

                // return if no category selected
                if (!id) {
                    // remove next selects
                    $select.closest('div').nextAll().remove();

                    // disable action button
                    if (self.options.actionButton) {
                        self.options.actionButton.addClass('disabled');
                    }
                    return;
                }

                // Default "action" button action
                if (self.options.actionButtonDefaultAction) {
                    self.options.actionButton[$option.hasClass('locked')
                        ? 'addClass'
                        : 'removeClass'
                    ]('disabled');
                }

                // Call onChange callback
                if (typeof self.options.onChange == 'function') {
                    self.options.onChange.call(self, $select, $option, id);
                }
                
                // return if there aren't sub-categories
                if ($option.hasClass('no-subcategories')) {
                    // remove next selects
                    $select.closest('div').nextAll().remove();

                    return;
                }

                // return if the next level for currency category already loaded
                if ($container.next().data('parent-category') == id) {
                    return;
                }

                var loadingColumnDelay = setTimeout(function(){
                    self.addNextEmptyColumn(id);
                }, flUtil.loadingDelay);

                // load next level of categories
                var data = {
                    mode: 'getCategoryLevel',
                    lang: rlLang,
                    type: type,
                    parent_id: id,
                    account_id: rlAccountInfo['ID'],
                    from_db: 1,
                    ajaxKey: self.options.ajaxKey
                };
                flUtil.ajax(data, function(response, status){
                    if (status == 'success') {
                        clearTimeout(loadingColumnDelay);

                        // remove next selects
                        $select.closest('div').nextAll().remove();

                        var no_user_category = self.$select_type.find('input:checked').data('no-user-category');

                        // no categories mode
                        if (response.count == 0 && !$option.hasClass('user-category') && !no_user_category) {
                            if ($select.hasClass('tmp')) {
                                $select.find('option').text(
                                    $select.data('no-data-phrase')
                                );
                            }
                            return;
                        }
                        
                        var $container = $select.closest('li');
                        var items = {
                            items: response.results,
                            parent_user_category: $option.hasClass('user-subcategory') && !no_user_category
                        };

                        // clear initial tmp select
                        if ($select.hasClass('tmp')) {
                            $select.closest('div').remove();
                        }

                        // append data to the next level
                        $container.append(
                            $('<div>')
                                .attr('data-parent-category', id)
                                .addClass('col-md-4')
                                .append(
                                    self.options.template.render(items)
                                )
                        );

                        // Scroll right inside the container
                        $container.scrollLeft($container.width());

                        var $current_cont = $container.find('> div').last();

                        // tablet/mobile selectors mode
                        if (media_query != 'desktop') {
                            $container.find('select').removeAttr('size')
                        }

                        // parent points handler
                        if (self.options.selectedID) {
                            var $next_select = $current_cont.find('select');
                            var next_id = self.options.selectedID;

                            if (self.options.parentIDs.length) {
                                next_id = self.options.parentIDs.shift();
                            } else if (typeof self.options.userCategoryID != 'undefined') {
                                self.options.selectedID = self.options.userCategoryID;
                            }

                            $next_select
                                .val(next_id)
                                .trigger('change');
                        }

                        // Call onLevelLoad callback
                        if (typeof self.options.onLevelLoad == 'function') {
                            self.options.onLevelLoad.call(self, $select, $option, $current_cont, id);
                        }
                    } else {
                        printMessage('error', lang['system_error']);
                    }
                });
            });

            // trigger the first level change to load initial level data
            this.$select_category.find('> li[data-type-key='+ current_key +'] select').val(0).trigger('change');
        }

        /**
         * Add empty column with "Loading" value
         * @since 4.7.0
         * @param {int} id - ID of selected category in current column
         */
        this.addNextEmptyColumn = function(id){
            if (parseInt(id)) {
                var $catSelector   = self.$select_category.find('li.show.row');
                var $currentColumn = $catSelector.find('option[value=' + id + ']').closest('div');
                var $emptySelect   = $('<select>')
                    .attr('size', media_query == 'desktop' ? '10' : '')
                    .addClass('tmp')
                    .append(
                        $('<option>')
                            .addClass('locked')
                            .val(lang['loading'])
                            .text(lang['loading'])
                            .attr('disabled', media_query == 'desktop' ? true : false)
                    );

                $currentColumn.nextAll('div').remove();

                // add next empty column if second column doesn't exist yet
                if (!$currentColumn.next('div').length) {
                    $catSelector.append(
                        $('<div>')
                            .addClass('col-md-4 empty-column')
                            .append($emptySelect)
                    );
                } else {
                    $catSelector.find('div:last').append($emptySelect);
                }
            }
        }

        this.init();
    }

    // default options
    $.categorySelector.defaultOptions = {
        typeContainer: 'ul.select-type',
        categoryContainer: 'ul.select-category',
        template: $('#category_level_select'),
        actionButton: null,
        actionButtonDefaultAction: true,
        onChange: null,
        onLevelLoad: null,
        selectedID: null,
        parentIDs: null,
        userCategoryID: null,
        selectedType: null,
        ajaxKey: 'category_selector'
    };

    $.fn.categorySelector = function(options){
        return this.each(function(){
            (new $.categorySelector(this, options));
        });
    };
}(jQuery));
