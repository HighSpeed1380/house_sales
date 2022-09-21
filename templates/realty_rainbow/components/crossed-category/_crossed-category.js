
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: _CROSSED-CATEGORY.JS
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

var crossedCategoryClass = function(){
    var self = this;
    
    this.crossed_number = 0;
    this.defined_listing_type = null;
    this.defined_category_id = null;

    var crossed_left;
    var crossed_categories = [];

    var $current_option = null;
    var $container = $('.crossed-categories');
    var $add_button_cont = $('.crossed-add');
    var $add_button = $add_button_cont.find('a');
    var $counter = $('#crossed_counter');
    var $tree_cont = $('.crossed-tree');
    var $crossed_tree = $tree_cont.find('ul');
    var $select_type = $container.find('ul.select-type');
    var $select_category = $container.find('ul.select-category');
    var $input = $('input[name=crossed_categories]');
    var $crossed_selection = $('.crossed-selection');

    this.init = function(crossed_number, selected_type, selected_category_id){
        // Reset number data
        if (crossed_number) {
            crossed_left = this.crossed_number = crossed_number;
        }

        // Save current category ID
        this.defined_category_id = selected_category_id;

        // Handle crossed from POST
        if ($input.val()) {
            $.each($input.val().split(','), function(index, id){
                crossed_categories.push(id);
                crossed_left--;
            });
        }

        // Update GUI
        this.updateGUI(crossed_number, selected_type, selected_category_id);

        // Load jsRender library and category_selector plugin
        flUtil.loadScript([
            rlConfig['libs_url'] + 'javascript/jsRender.js',
            rlConfig['tpl_base'] + 'components/category-selector/_category-selector.js'
        ], function(){
            $crossed_selection.categorySelector({
                actionButton: $add_button,
                selectedType: selected_type,
                ajaxKey: 'crossed_categories',
                onChange: function($select, $option){
                    $current_option = $option;
                },
                onLevelLoad: function($select, $option, $current_cont){
                    // Disable selected categories
                    if (crossed_categories.length || self.defined_category_id) {
                        $.each($current_cont.find('select > option'), function(index, option){
                            if (crossed_categories.indexOf($(option).val()) >= 0
                                || $(option).val() == self.defined_category_id
                            ) {
                                $(option).attr('disabled', true);
                            }
                        });
                    }
                }
            });
        });

        this.addCategoryHandler();
        this.removeCategoryHandler();
    }

    this.updateGUI = function(crossed_number, defined_listing_type, selected_category_id){
        // Re-assign value
        if (crossed_number) {
            crossed_left = this.crossed_number = crossed_number;

            // Reduce selected categories count
            if (crossed_categories.length) {
                crossed_left -= crossed_categories.length;
            }
        }

        // Save current category ID
        if (selected_category_id) {
            this.defined_category_id = selected_category_id;
        }

        // Update GUI
        $counter.text(crossed_left);

        // Exceeded number handler
        $container[crossed_left == 0
            ? 'addClass'
            : 'removeClass'
        ]('exceeded');

        // Empty tree handler
        $tree_cont[crossed_left == this.crossed_number
            ? 'addClass'
            : 'removeClass'
        ]('empty');

        // Set selected categories in the input
        $input.val(crossed_categories.join(','));

        // Select pre-defined listing type
        if (defined_listing_type) {
            $select_type.find('input[name=section_crossed][value=' + defined_listing_type + ']')
                .trigger('click');
        }

        // Hide or show the type panel
        $select_type[rlConfig['crossed_categories_by_type'] && $select_type.find('> li').length > 1
            ? 'removeClass'
            : 'addClass'
        ]('hide');

        this.updateSingleCategory(selected_category_id);
    }

    this.resetGUI = function(selected_category_id){
        $tree_cont.addClass('empty');
        $crossed_tree.empty();

        crossed_categories = [];

        $select_category.find('select > option').each(function(){
            $(this).attr('disabled', false);
        });

        this.updateSingleCategory(selected_category_id);
    }

    this.addCategoryHandler = function(){
        $add_button.click(function(){
            if ($(this).hasClass('disabled')) {
                return;
            }

            var page_path = $current_option.data('path');
            var id = $current_option.val();
            var url = rlConfig['seo_url'] + $select_type.find('input[name=section_crossed]:checked').data('path') + '/' + page_path + '.html';

            // No mod rewrite mode
            if (!rlConfig['mod_rewrite']) {
                var url = rlConfig['seo_url'] + '?page=' + page_path + '&category=' + id;
            }

            if (crossed_categories.indexOf(id) >= 0) {
                return;
            }

            if (crossed_left <= 0) {
                printMessage('error', lang['crossed_top_text_denied']);
                return;
            }

            var $type_option = $crossed_selection.find('[name=section_crossed]:checked');

            if ($type_option.data('single-category-id')) {
                $type_option.attr('disabled', true);
            } else {
                $current_option.attr('disabled', true);
            }
            $(this).addClass('disabled');

            // Append category to the tree
            $crossed_tree.append(
                $('<li>')
                    .attr('data-id', id)
                    .append(
                        $('<a>')
                            .text($.trim($current_option.text()))
                            .attr('href', url)
                            .attr('target', '_blank')
                    ).append(
                        $('<img>')
                            .attr('src', rlConfig['tpl_base'] + 'img/blank.gif')
                            .attr('title', lang['remove'])
                            .addClass('remove')
                    )
            );

            // Save selected category
            crossed_categories.push(id);
            crossed_left--;

            self.updateGUI();
        });
    }

    this.removeCategoryHandler = function(){
        $crossed_tree.on('click', 'img.remove', function(){
            var $container = $(this).closest('li');
            var id = $container.data('id').toString();

            // Remove element
            $container.remove();

            // Decrase counter
            var index = crossed_categories.indexOf(id);
            crossed_categories.splice(index, 1);
            crossed_left++;

            // Update GUI
            self.updateGUI();

            var $type_option = $crossed_selection.find('[data-single-category-id=' + id + ']');

            // Enable type option
            if ($type_option.length) {
                $type_option.attr('disabled', false);
                $add_button.removeClass('disabled');
            }
            // Enable category in selector
            else {
                $select_category.find('select > option').each(function(){
                    if (id == $(this).val()) {
                        $(this).attr('disabled', false);
                    }
                });
            }
        });
    }

    /**
     * Update single category related interfaces
     *
     * @since 4.8.0
     *
     * @param int selected_category_id - current category ID
     */
    this.updateSingleCategory = function(selected_category_id){
        var $type_options = $crossed_selection.find('[name=section_crossed]');
        $type_options.filter(':disabled').attr('disabled', false);
        $type_options.filter(':checked').attr('checked', false);

        $add_button.addClass('disabled');

        if (selected_category_id) {
            $crossed_selection.find('[data-single-category-id=' + selected_category_id + ']').attr('disabled', true);
        }
    }
}
