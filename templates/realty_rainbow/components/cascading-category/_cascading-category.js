
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: _CASCADING-CATEGORY.JS
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
    $.cascadingCategory = function(element, options){
        var self = this;

        this.$element        = $(element);
        this.$form           = this.$element.closest('form');
        this.$selectors      = this.$form.find('select.multicat');
        this.$parentIDsField = this.$form.find('input[name="f[category_parent_ids]"]');
        this.$postField      = this.$form.find('input[name="f[Category_ID]"]');
        
        this.selected_ids    = this.$parentIDsField.val() ? this.$parentIDsField.val().split(',').reverse() : false;

        this.init = function(){
            this.$selectors.change(function(){
                var category_id  = $(this).val();
                var listing_type = self.$postField.data('listing-type');
                var index        = self.$selectors.index(this);
                var $nextField   = self.$selectors.eq(++index);
                var $option      = $(this).find('option:selected');

                // Disable all next
                var index_to_disable = category_id > 0 && !$option.data('no-subcategories') ? index : index-1;
                self.$selectors.filter(':gt('+ index_to_disable +')')
                    .attr('disabled', true)
                    .val('0')
                    .trigger('change');

                // Set selected category ID
                self.$postField.val(category_id);

                // Collect all selected IDs
                var category_ids = $.map(self.$selectors, function(select){
                    if (select.value > 0) {
                        return select.value;
                    }
                });
                self.$parentIDsField.val(category_ids.join(','));

                if ($option.data('no-subcategories')) {
                    $nextField.addClass('disabled');
                    return;
                }

                // Load next level
                if (category_id > 0 && $nextField.length) {
                    self.loadOptions($option, category_id, listing_type, $nextField);
                }
            });

            this.preSelect();
        }

        /**
         * Fields auto select
         */
        this.preSelect = function(){
            if (this.selected_ids.length) {
                this.$selectors.filter(':not(:disabled):last')
                    .val(this.selected_ids.pop())
                    .trigger('change');
            }
        }

        /**
         * Load options to the selector
         */
        this.loadOptions = function($option, category_id, listing_type, $target) {
            var data = {
                id:   category_id,
                type: listing_type,
                mode: 'getCategoriesByType'
            };

            flUtil.ajax(data, function(response, status){
                if (status != 'success') {
                    return;
                }

                $target
                    .empty()
                    .append(
                        $('<option>')
                            .val(0)
                            .text(lang['any'])
                    );

                if (!response) {
                    $option.attr('data-no-subcategories', true);
                    $target
                        .attr('disabled', true)
                        .addClass('disabled');

                    return;
                }

                $target
                    .removeAttr('disabled')
                    .removeClass('disabled');

                $.each(response, function(i, category) {
                    $target.append(
                        $('<option>')
                            .val(category.ID)
                            .text(category.name)
                    );
                });

                self.preSelect();
            });
        }

        this.init();
    }

    $.fn.cascadingCategory = function(options){
        return this.each(function(){
            (new $.cascadingCategory(this, options));
        });
    };
}(jQuery));
