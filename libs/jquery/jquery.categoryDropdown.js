
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: JQUERY.CATEGORYDROPDOWN.JS
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

(function ($) {

	$.categoryDropdown = function(el, options) {
		var base = this;

		base.data = new Array(); // categories data
		base.parents = new Array(); // category parents in defaultSelection mode
		base.opts = $.extend({}, $.categoryDropdown.defaults, options);
		base.phrases = new Array();
		base.type_key = null;
		base.query = false;

		base.init = function() {
			base.opts.phrases.default = $(el).find('> option:first').text();

			if (base.opts.listingType == null && base.opts.listingTypeKey == null) {
				console.log("ERROR: $.categoryDropdown plugin requires {listingType: 'listing type dropdown selector' or listingTypeKey: 'listing type key'} parameter specified");
				return;
			}

			// do default action
			if (base.opts.listingType != null && $(base.opts.listingType).val() != '') {
				base.type_key = $(base.opts.listingType).val();
				base.load(0);
			} else if (base.opts.listingTypeKey != null) {
				base.type_key = base.opts.listingTypeKey;
				base.load(0);
			}

			// build iterface
			base.buildInterface();

			// listing type click events
			$(base.opts.listingType).change(function(){
				base.type_key = $(this).val() == '' ? null : $(this).val();

				if (base.type_key == null) {
					base.clear();
				} else {
					base.dropdown.text(base.opts.phrases.select_category);
					base.box.empty();
					base.clearValue();
					base.load(0);
				}
			});

			// interface click event
			$(base.dropdown).click(function(event){
				if (base.type_key == null) {
					return;
                }

				base.box.toggle();
			});

			// document click event
			$(document).bind('click touchstart', function(event){
				if (!$(event.target).parents().hasClass('cd-extendable')) {
					base.box.hide();
				}
			});

			// track related form reset
			base.trackReset();
		}

		base.load = function(id) {
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
			base.query = $.getJSON(rlUrlHome+'request.ajax.php', {item: 'getCategoriesByType', type: base.type_key, id: id}, function(response){
				if (response == null || response.length == 0)
					return;

				if (typeof base.data[base.type_key] == 'undefined') {
					base.data[base.type_key] = new Array();
				}

				base.data[base.type_key][id] = new Array();

				for (var i = 0; i < response.length; i++) {
					base.data[base.type_key][id].push(response[i]);
				}
				base.buildDropdown(id);
			});
		}

		base.buildDropdown = function(id) {
			var selector = 'cd_category_'+id;
			base.box.append('<select size="12" id="'+selector+'"><option value="">'+base.opts.phrases.select+'</option></select>');
			base.box.scrollLeft(3000);

			if (base.data[base.type_key][id].length == 0) {
				$('#'+selector).find('> option:first').val(base.opts.phrases.no_categories_available);
			} else {
				for (var i in base.data[base.type_key][id]) {
					if (typeof base.data[base.type_key][id][i] == 'function') {
                        continue;
                    }

					var option = '<option value="'+base.data[base.type_key][id][i].ID+'">test</option>';
					$('#'+selector).append(option);

					// set name
					$('#'+selector).find('> option:last').text(base.data[base.type_key][id][i].name);
				}

				// set change event listener
				$('#'+selector).change(function(){
					base.change(this, id, $(this).val());
				});
			}

			// enable dropdown
			base.dropdown.removeClass('disabled');

			// default selection
			base.defaultSelection(id);
		}

		base.change = function(obj, parent, id) {
			// remove alls next selects
			$(obj).nextAll('select').remove();
			
			// emulate selection
			base.selectValue(obj, id);

			// update title
			base.setTitle(obj);

            // run callback function
            if (typeof base.opts.onChange == 'function') {
                base.opts.onChange(id, parent);
            }

			// no selection
			if (id == '') {
				return;
			}

			// no sub-categories
			var category = base.getDataById(base.type_key, parent, id);

            if (!category) {
                base.clear();
                return;
            }

			if (parseInt(category.Sub_cat) == 0) {
				return;
            }

			// load new next select
			base.load(id);
		}

		base.getDataById = function(type, parent, id) {
			if (base.data[type][parent]) {
				for (var i = 0; i < base.data[type][parent].length; i++) {
					if (base.data[type][parent][i]['ID'] == id) {
						return base.data[type][parent][i];
					}
				}
			}
		}

		base.setTitle = function(obj) {
			var title = new Array();
			$($(obj).prevAll().get().reverse()).each(function(){
				title.push($(this).find('option:selected').text());
			});

			if ($(obj).find('option:selected').val() != '') {
				title.push('<b>'+$(obj).find('option:selected').text()+'');
			}

			if (title.length == 0) {
				base.dropdown.text(base.opts.phrases.select_category);
				return;
			}

			base.dropdown.html(title.join(' » '));
		}

		base.selectValue = function(obj, id) {
			if (id == '') {
				if (base.box.find('> select').length < 2) {
					base.clearValue();
					return;
				}

				id = $(obj).prev().val();
			}

			$(el).find('option:first').val(id);
			$(el).val(id).trigger('change');
		}

		base.clear = function() {
			base.dropdown.text(base.opts.phrases.default);
			base.box.hide();
			base.clearValue();

			base.dropdown.addClass('disabled');
		}

		base.clearValue = function() {
			$(el).find('option:first').val('');
			$(el).val('');
		}

		base.trackReset = function() {
			$(el).closest('form').find('input[id^=reset]').click(function(e){
				base.clear();
			});
		}

		base.defaultSelection = function(id) {
			if (parseInt(base.opts.default_selection) > 0) {
				// load data before action
				if (id == 0) {
					$.getJSON(rlUrlHome+'request.ajax.php', {item: 'getCategoryParent', id: base.opts.default_selection}, function(response){
						if (response != null && response.length > 0) {
							base.parents = response.split(',').reverse();   
						}
                        
                        if (response !== false) {
                            base.triggerSelection();
                        } else {
                            base.opts.default_selection = null;
                        }
					});
				}
				// continue selection
				else {
					base.triggerSelection();
				}
			}
		}

		base.triggerSelection = function() {
			if (base.parents.length > 0) {
				base.box.find('select:last').val(base.parents[0]).change();
				base.parents.shift();
			} else {
				base.box.find('select:last').val(base.opts.default_selection).change();
				base.opts.default_selection = null; // completed
			}
		}

		base.buildInterface = function() {
			var pos = $(el).position();
			$(el).after(base.opts.interfaceDom).hide();

			// clear element on reset trigger
			$(el).on('reset', function(){
				base.clear();
			});

			base.dropdown = $(el).next().find('> div.dropdown');
			base.box = $(el).next().find('> div.box');

			var phrase = base.opts.phrases.select_category;
			if (base.type_key == null) {
				phrase = base.opts.phrases.default;
				base.dropdown.addClass('disabled');
			}
			base.dropdown.text(phrase);

			// set max width
			var max = $(document).width() - pos.left - 20;
			base.box.css('maxWidth', max+'px');
		}

		base.init();
	};

	// Plugin defaults – added as a property on our plugin function.
	$.categoryDropdown.defaults = {
		listingType: null, // listing type dropdown selector
		listingTypeKey: null, // listing type key
		phrases: new Object(),
		interfaceDom: '<div class="cd-extendable"><div class="dropdown"></div><div class="box"></div></div>',
		default_selection: null,
        onChange: null
	};

	$.fn.categoryDropdown = function(options){
		return this.each(function(){
			new $.categoryDropdown(this, options);
		});
	};

})(jQuery);
