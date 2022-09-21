
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: CATEGORYDROPDOWN.JS
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

/**
 * Category dropdown plugin
 *
 * @since 4.8.2 - Function moved to template core
 * @package jQuery
 */

(function($){
    $.categoryDropdown = function(el, options){
        var base = this;

        base.data = []; // categories data
        base.parents = []; // category parents in defaultSelection mode
        base.opts = $.extend({}, $.categoryDropdown.defaults, options);
        base.type_key = null;
        base.query = false;
        base.animation_in_rogress = false;
        base.action_href = null;
        base.ready = false;

        base.init = function(){
            base.opts.phrases.default = $(el).find('> option:first').text();

            if (base.opts.listingType == null && base.opts.listingTypeKey == null) {
                console.log("ERROR: $.categoryDropdown plugin requires {listingType: 'listing type dropdown selector' or listingTypeKey: 'listing type key'} parameter specified");
                return;
            }

            if (base.opts.default_selection_parents) {
                base.parents = base.opts.default_selection_parents.split(',');
            }

            if (base.opts.typesData != null && base.opts.default_listing_type_key) {
                base.parents.push(base.opts.default_listing_type_key);
            }

            base.parents = base.parents.reverse();

            // build interface
            base.buildInterface();

            // multiple listing types mode
            if (base.opts.typesData != null) {
                // save form action path
                base.action_href = $(el).closest('form').attr('action');

                // reset form action
                base.resetFormAction();

                base.data[0] = Array(base.opts.typesData);
                base.type_key = 0;
                base.load(0);
            }
            // single listing type mode
            else {
                if (base.opts.listingType != null && $(base.opts.listingType).val() != '') {
                    base.type_key = $(base.opts.listingType).val();
                    base.load(0);
                }
                else if (base.opts.listingTypeKey != null) {
                    base.type_key = base.opts.listingTypeKey;
                    base.load(0);
                }
            }

            // listing type click events
            $(base.opts.listingType).change(function(){
                base.type_key = $(this).val() == '' ? null : $(this).val();

                if (base.type_key == null) {
                    base.clear();
                }
                else {
                    base.dropdown.text(base.opts.phrases.select_category);
                    base.box.empty();
                    base.clearValue();
                    base.load(0);
                }
            });

            // interface click event
            $(base.dropdown).click(function(event){
                if (base.type_key == null)
                    return;

                base.container.toggleClass('opened');
            });

            // bread crumbs click event
            base.bc.click(function(){
                base.goBack();
            });

            // document click event
            $(document).bind('click touchstart', function(event){
                if (!$(event.target).parents().hasClass('cd-extendable')) {
                    base.container.removeClass('opened');
                }
            });

            // track related form reset
            base.trackReset();
        }

        base.load = function(id){
            // do action if the data is already exist
            if (typeof base.data[base.type_key] != 'undefined' && typeof base.data[base.type_key][id] != 'undefined') {
                base.buildDropdown(id);
                return;
            }

            // abort previous query
            if (base.query) {
                base.query.abort();
            }

            // load data before action
            base.query = $.getJSON(rlConfig['ajax_url'], {
                mode: 'getCategoriesByType',
                type: !jQuery.isNumeric(id) ? id : base.type_key,
                id: id,
                lang: rlLang
            }, function(response){
                if (response == null || response.length == 0)
                    return;

                if (typeof base.data[base.type_key] == 'undefined') {
                    base.data[base.type_key] = [];
                }

                base.data[base.type_key][id] = [];

                for (var i = 0; i < response.length; i++) {
                    base.data[base.type_key][id].push(response[i]);
                }
                base.buildDropdown(id);
            });
        }

        base.buildDropdown = function(id){
            var selector = 'cd_category_' + id;
            var default_value = base.opts.phrases.select;
            var default_id = '';

            if (base.box.find('> ul').length > 0) {
                default_value = base.box.find('> ul:last > li.selected > a').text();
                default_id = base.box.find('> ul:last > li.selected').attr('accesskey');
            }

            base.box.append('<ul id="' + selector + '"><li accesskey="' + default_id + '" class="selected"><a href="javascript://">' + default_value + '</a></li></ul>');

            if (base.data[base.type_key][id].length == 0) {
                $('#' + selector).find('> li:first').text(base.opts.phrases.no_categories_available);
            }
            else {
                for (var i in base.data[base.type_key][id]) {
                    if (typeof base.data[base.type_key][id][i] == 'function') continue;

                    var option = '<li accesskey="' + base.data[base.type_key][id][i].ID + '">';

                    if (parseInt(base.data[base.type_key][id][i].Sub_cat) > 0) {
                        option += '<span title="' + lang['show_subcategories'] + '"></span>';
                    }

                    option += '<a href="javascript://"></a>';

                    option += '</li>';
                    $('#' + selector).append(option);

                    // set name
                    $('#' + selector).find('> li:last > a').html(base.data[base.type_key][id][i].name);
                }

                // scroll top
                base.box.animate({scrollTop: 0});

                // set load next level listener
                $('#' + selector).find('> li').click(function(){
                    var next_id = $(this).attr('accesskey');
                    base.change($(this).closest('ul'), id, next_id);

                    if ($(this).index() == 0 || $(this).attr('accesskey') == '') {
                        return;
                    }

                    base.load(next_id);
                });
            }

            // move forward
            if (base.box.find('> ul').length > 1) {
                var offset = (base.box.find('> ul').length - 1) * 100;
                var animation = rlLangDir == 'ltr' ? {marginLeft: '-' + offset + '%'} : {marginRight: '-' + offset + '%'};
                base.box.find('> ul:first').animate(animation);
            }

            base.bcStatus();

            // enable dropdown
            base.dropdown.removeClass('disabled');

            // default selection
            base.defaultSelection();

            // update title
            base.setTitle($('#' + selector));
        }

        base.change = function(obj, parent, id){
            // remove all next selects
            $(obj).nextAll('ul').remove();

            // emulate category ID selection
            base.selectValue(obj, id);

            // save parent category IDs
            base.setParentIDs(obj);

            // update title
            base.setTitle(obj);

            // update form key
            if (base.opts.typesData != null) {
                base.updateFormKey(id);
            }

            // no selection
            if (id == '') {
                base.resetFormAction();
                return;
            }

            // replace form key
            if (!jQuery.isNumeric(id)) {
                base.updateFormAction(id);
            }
        }

        base.setTitle = function(obj){
            var title = [];
            var name = '';

            $(obj).prevAll().each(function(){
                title.push($(this).find('li.selected > a').text());
            });

            if (title.length > 0) {
                title.pop();
            }

            title.push(base.opts.phrases.select);

            if ($(obj).find('li.selected').attr('accesskey') != '') {
                name = $(obj).find('li.selected > a').text();
            }

            if (base.ready) {
                base.dropdown.text(name);
            }

            if (title.length <= 1 && name == '') {
                base.dropdown.html(base.opts.phrases.select_category);
                return;
            }

            base.bc.html(title.reverse().join(' / '));
        }

        base.selectValue = function(obj, id){
            obj.find('li.selected').removeClass('selected');

            if (id == '') {
                obj.find('li:first').addClass('selected');

                if (base.box.find('> ul').length < 2) {
                    base.clearValue();
                    return;
                }

                id = $(obj).prev().find('li.selected').attr('accesskey');
            } else {
                obj.find('li[accesskey=' + id + ']').addClass('selected');
            }

            id = parseInt(id) == id ? id : '';

            $(el).find('option:first').val(id);
            $(el).val(id);
        }

        base.setParentIDs = function(obj){
            var ids = [];
            var key = '';

            $(obj).parent().find('ul').each(function(){
                var id = $(this).find('> li:gt(0).selected').attr('accesskey');

                if (id) {
                    if (jQuery.isNumeric(id)) {
                        ids.push(id);
                    } else {
                        key = id;
                    }
                }
            });

            base.$parentTypeKey.val(key);
            base.$parentIDs.val(ids.join(','));
        }

        base.goBack = function(){
            if (!base.animation_in_rogress) {
                base.animation_in_rogress = true;

                var offset = (base.box.find('> ul').length - 2) * 100;
                var animation = rlLangDir == 'ltr' ? {marginLeft: '-' + offset + '%'} : {marginRight: '-' + offset + '%'};
                base.box.find('> ul:first').animate(animation, function(){
                    base.box.find('> ul:last').remove();
                    base.bcStatus();

                    var current = base.box.find('> ul:last');
                    base.setTitle(current);
                    base.selectValue(current, current.find('li.selected').attr('accesskey'));

                    base.animation_in_rogress = false;
                });
            }
        }

        base.bcStatus = function(){
            if (base.box.find('> ul').length > 1) {
                base.bc.show();
            } else {
                base.bc.hide();
            }
        }

        base.clear = function(){
            base.dropdown.text(base.opts.phrases.default);
            base.container.removeClass('opened')
            base.clearValue();

            base.dropdown.addClass('disabled');
        }

        base.clearValue = function(){
            $(el).find('li:first').attr('accesskey', '');
            $(el).val('');
        }

        base.trackReset = function(){
            $(el).closest('form').find('input[id^=reset]').click(function(e){
                base.clear();
            });
        }

        base.defaultSelection = function(){
            if (base.parents.length) {
                base.triggerSelection();
            } else {
                base.ready = true;
            }
        }

        base.triggerSelection = function(){
            base.box.find('ul:last > li[accesskey=' + base.parents[0] +']').click();
            base.parents.shift();

            if (!base.parents.length) {
                base.ready = true;
            }
        }

        base.buildInterface = function(){
            $(el).after(base.opts.interfaceDom).hide();
            $(el).before(base.opts.parentIDsDom);

            // clear element on reset trigger
            $(el).on('reset', function(){
                base.clear();
            });

            base.container = $(el).next();
            base.$parentIDs = $(el).parent().find('[name="f[category_parent_ids]"]');
            base.$parentTypeKey = $(el).parent().find('[name="f[type_parent_key]"]');
            base.dropdown = base.container.find('> div.dropdown');
            base.bc = base.container.find('> div.box > div.bc');
            base.box = base.container.find('> div.box > div.uls');

            var phrase = base.opts.phrases.select_category;
            if (base.type_key == null) {
                phrase = base.opts.phrases.default;
                base.dropdown.addClass('disabled');
            }
            base.dropdown.text(phrase);
        }

        base.updateFormAction = function(id){
            var index = base.opts.listingTypeKey.indexOf(id);
            var path = base.opts.typesData[index].Path;
            var action_href = base.action_href;

            // subdomain mode
            if (base.opts.typesData[index].Link_type == 'subdomain') {
                action_href = action_href.replace('{type}/', '')
                    .replace(/(https?\:\/\/)/, '$1{type}.');
            }

            var action_path = action_href.replace('{type}', path);
            $(el).closest('form').attr('action', action_path);
        }

        base.resetFormAction = function(){
            $(el).closest('form').attr('action', $(el).closest('form').attr('accesskey'));
        }

        base.updateFormKey = function(id){
            var form_key_input = $(el).closest('form').find('input[name=post_form_key]');

            if (id == '') {
                form_key_input.val('');
            } else if (!jQuery.isNumeric(id)) {
                var index = base.opts.listingTypeKey.indexOf(id);
                var form_key = id + '_';
                form_key += base.opts.typesData[index].Advanced_search ? 'advanced' : 'quick';

                form_key_input.val(form_key);
            }
        }

        base.init();
    };

    // Plugin defaults
    $.categoryDropdown.defaults = {
        listingType: null, // listing type dropdown selector
        listingTypeKey: null, // listing type key
        typesData: [],
        phrases: {},
        interfaceDom: '<div class="cd-extendable"><div class="dropdown"></div><div class="box"><div class="bc"></div><div class="uls"></div></div></div>',
        parentIDsDom: '<input type="hidden" name="f[category_parent_ids]" /><input type="hidden" name="f[type_parent_key]" />',
        default_listing_type_key: null,
        default_selection_parents: null
    };

    $.fn.categoryDropdown = function(options){
        return this.each(function(){
            new $.categoryDropdown(this, options);
        });
    };
})(jQuery);
