
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LIB.JS
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

var mfHandlerClass = function(){
    var self = this;

    this.formPrefix  = 'f';
    this.fields = [];
    this.values = [];

    this.$form = null;

    this.init = function(prefix, fields, values, form){
        if (!fields) {
            return;
        }

        this.fields = fields;
        this.values = values;
        this.formPrefix = prefix ? prefix : this.formPrefix;
        this.$form = form;

        this.fieldsInit();
    }

    this.fieldsInit = function(){
        for (var i in this.fields) {
            field = this.fields[i];

            var $select = this.getElement('select[name="' + this.formPrefix + '[' + field + ']"]');
            var is_top  = field.indexOf('_level') < 0;

            (function($element, field_name){
                $element.change(function(){
                    self.buildNextField($(this).val(), field_name);
                });
            })($select, field);

            if (is_top) {
                // Make the single available option selected by default
                if ($select.find('option').length == 2) {
                    $select.find('option:eq(1)').attr('selected', true);
                }

                // Select default/selected option
                if ($select.find('option').filter(':selected').val() != '0') {
                    $select.trigger('change');
                } else if (typeof(this.values) != 'undefined' && this.values[field]) {
                    // Select exact match
                    if ($select.find('option[value=' + this.values[field] + ']').length) {
                        $select.val(this.values[field]);
                        $select.trigger('change');
                    }
                    // Select similar key as fallback
                    else if ($select.find('option[value$=' + this.values[field] + ']').length) {
                        var val = $select.find('option[value$=' + this.values[field] + ']').val();
                        $select.val(val);
                        $select.trigger('change');
                    }
                }
            } else {
                // Disable next level fields
                $select
                    .attr('disabled', true)
                    .val(0);
            }
        }
    }

    this.buildNextField = function(value, name){
        var $nextField = this.getNextField(name);
        var next_name  = this.getNextFieldName(name)

        this.disableNextFields(name);

        if (!$nextField || !value || value === '0') {
            return false;
        }

        $nextField.find('option:first')
            .attr('selected', true)
            .text(lang['loading']);

        var data = {
            mode: 'mfNext',
            lang: rlLang,
            item: value
        };

        $.post(rlConfig['ajax_url'], data, function(response, status){
            if (status == 'success' && response.status == 'ok') {
                if (response.data.length > 0) {
                    var default_key = null;

                    $nextField
                        .attr('disabled', false)
                        .html(
                            $('<option>')
                                .val(0)
                                .text(lang['select'])
                        );

                    $.each(response.data, function(index, item) {
                        $nextField.append(
                            $('<option>')
                                .val(item.Key)
                                //.attr('data-path', item.Path) // TODO, do we still need Path here?
                                .attr('selected', item.Default == '1')
                                .text(item.name)
                        );

                        // pre-select default options
                        if (item.Default == '1') {
                            default_key = item.Key;
                        }
                    });

                    // Pre-select field
                    if (default_key || (self.values && self.values[next_name])) {
                        var set_val = self.values[next_name] || default_key;

                        if ($nextField.find('option[value=' + set_val + ']').length) {
                            $nextField
                                .val(set_val)
                                .trigger('change');
                        }

                        if (self.values[next_name]) {
                            delete self.values[next_name];
                        }
                    }
                } else {
                    self.disableNextFields(name, lang['not_available'])
                }
            } else {
                console.log('MultiField: Unable to load next level data, ajax call failed');
            }
        }, 'json');
    }

    this.getNextField = function(name){
        return this.getSelect(this.fields.indexOf(name) + 1);
    }

    this.getNextFieldName = function(name){
        var index = this.fields.indexOf(name) + 1;

        return this.fields[index]
            ? this.fields[index]
            : false;
    }

    this.getSelect = function(index) {
        return this.fields[index]
            ? this.getElement('select[name="' + this.formPrefix + '[' + this.fields[index] + ']"]')
            : false;
    }

    this.getElement = function(selector) {
        return this.$form ? this.$form.find(selector) : $(selector);
    }

    this.disableNextFields = function(name, text){
        var index = this.fields.indexOf(name) + 1;

        while (this.fields[index]) {
            $select = this.getSelect(index);
            $select
                .attr('disabled', true)
                .val(0)
                .trigger('change');

            if (text) {
                $select.find('> option:first').text(text);
            }

            index++;
        }
    }
}
