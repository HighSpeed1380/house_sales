
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: GEOAUTOCOMPLETE.JS
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

(function($){
    $.geoAutocomplete = function(elem, options) {
        var defaults = {
            provider: null,
            onSelect: null
        };

        var settings = $.extend({}, defaults, options );

        var resultsLimit = 5; // Locations in popup
        var requestDelay = 250; // Request delay in microseconds
        var activeClass  = 'geo-autocomplete__item_active';
        var openClass    = 'geo-autocomplete_open';

        var enterKey = 13;
        var arrowDownKey = 40;
        var arrowUpKey = 38;

        var timer       = null;
        var query       = '';
        var items       = [];
        var lastFilters = {};
        var formEvent   = null;
        var clickButtonType = null;
        var currentTarget = null;

        var $popup = null;
        var $elem = null;
        var $form = null;

        var currentItem = -1;
        var countItems  = 0;

        var ignoreKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Control', 'Meta', 'Alt', 'Shift',
                          'Enter', 'CapsLock', 'Home', 'End', 'PageUp', 'PageDown', '=', '`', ];

        var openPopup = function(){
            if (!$popup) {
                buildPopup();
            }

            $popup.addClass(openClass);
            setPopupPosition();

            clearFormEvent();
        }

        var closePopup = function(){
            if (!$popup) {
                return;
            }

            $popup.removeClass(openClass);

            restoreFormEvent();
        }

        var buildPopup = function(){
            $popup = $('<div>').addClass('geo-autocomplete');
            $('body').append($popup);
            setPopupPosition();
            setPopupNavigation();
        }

        var clearFormEvent = function(){
            if (!$form.length) {
                return;
            }

            formEvent = $form.get(0).onsubmit;
            $form.get(0).onsubmit = null;

            var formID = $form.attr('id');
            var $submitButton = $form.find('[type=submit]');
            var $refButton = $('[form='+formID+'][type=submit]');

            if ($submitButton.length) {
                $submitButton.attr('disabled', true);
                clickButtonType = 'submit';
            } else if (formID && $refButton.length) {
                $refButton.attr('disabled', true);
                clickButtonType = 'formReference';
            }
        }

        var restoreFormEvent = function(){
            if (!$form.length) {
                return;
            }

            $form.get(0).onsubmit = formEvent;
            formEvent = null;

            if (clickButtonType) {
                if (clickButtonType == 'submit') {
                    $form.find('[type=submit]').attr('disabled', false);
                } else {
                    $('[form='+$form.attr('id')+']').attr('disabled', false);
                }
                clickButtonType = null;
            }
        }

        var setPopupPosition = function(){
            var pos = $elem.offset();

            $popup.css({
                left: pos.left,
                top: pos.top + $elem.outerHeight(),
                width: $elem.outerWidth()
            });
        }

        var setPopupNavigation = function(){
            $elem.on('keyup', function(e){
                if (e.keyCode == enterKey) {
                    if ($popup.hasClass(openClass)) {
                        selectItem(currentItem);
                    } else {
                        $form.submit();
                    }
                    return;
                }

                if ([arrowDownKey, arrowUpKey].indexOf(e.keyCode) < 0) {
                    return;
                }

                switch(e.keyCode){
                    case arrowDownKey:
                        currentItem = currentItem == countItems - 1 ? 0 : currentItem + 1;
                        break;

                    case arrowUpKey:
                        currentItem = currentItem == 0 ? countItems - 1 : currentItem - 1;
                        break;
                }

                $popup.find('.' + activeClass).removeClass(activeClass);
                $popup.find('> div:eq(' + currentItem + ')').addClass(activeClass);

                e.preventDefault();
                e.stopPropagation();

                return false;
            }).on('click', function(){
                if ($popup.length && items.length) {
                    var currentFilters = getFilters();

                    if (JSON.stringify(lastFilters) == JSON.stringify(currentFilters)) {
                        openPopup();
                    } else {
                        request();
                    }
                }
            });

            $form.on('submit', function(e){
                if ($popup.hasClass(openClass)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            $popup.on('click', '> div', function(){
                var position = $popup.find('> div').index(this);
                selectItem(position);
            });

            $(document).on('mousedown', function(e){
                currentTarget = e.target;
            });

            $(document).on('click touchstart', function(event){
                if (event.target != $popup.get(0)
                    && event.target != $elem.get(0)
                    && event.target.type != 'submit' // input enter fires form submit element click, that is why we prevent it
                    && !$(event.target).parents().hasClass('geo-autocomplete'))
                {
                    closePopup();
                }
            });

            $(window).on('resize', setPopupPosition);
        }

        var getFilters = function(){
            var filters = {};

            for (var i in $elem.get(0).attributes) {
                var name  = $elem.get(0).attributes[i].name;
                var value = $elem.get(0).attributes[i].value;

                if (/^data\-filter\-/.test(name) && value != '') {
                    name = name.replace('data-filter-', '');
                    filters[name] = $elem.get(0).attributes[i].value;
                }
            }

            return filters;
        }

        var request = function(){
            var filters = getFilters();
            lastFilters = JSON.parse(JSON.stringify(filters));
            var q = query;

            if (Object.keys(filters).length) {
                filters.query = query;
                q = JSON.stringify(filters);
            }

            var data = {
                mode: 'placesAutocomplete',
                ajaxKey: 'placesAutocomplete',
                query: q,
                lang: rlLang,
                provider: settings.provider ? settings.provider : '',
                ajaxFrontend: true
            };

            flUtil.ajax(data, function(response, status){
                if (status == 'success') {
                    openPopup();

                    if (response.results) {
                        items = response.results;
                        populateResults();
                    } else {
                        resetResults();
                    }
                } else {
                    printMessage('warning', lang['system_error']);
                }
            });
        }

        var selectItem = function(position){
            position = position === -1 ? 0 : position;

            var item = items[position];
            $elem.val(item.location);

            var setCoordinates = function(name, lat, lng){
                $elem.attr({
                    'data-lat': lat, 
                    'data-lng': lng
                });

                if (typeof settings.onSelect == 'function') {
                    settings.onSelect.call(this, name, lat, lng);
                }
            }

            if (item.lat == '' || item.lng == '') {
                var data = {
                    mode: 'placesСoordinates',
                    place_id: item.place_id,
                    ajaxFrontend: true
                };

                flUtil.ajax(data, function(response, status){
                    if (status == 'success' && response.status == 'OK') {
                        item.lat = response.results.lat;
                        item.lng = response.results.lng;
                        setCoordinates(item.location, response.results.lat, response.results.lng);
                    }
                });
            } else {
                setCoordinates(item.location, item.lat, item.lng);
            }

            closePopup();
        }

        var populateResults = function(){
            $popup.empty();

            currentItem = -1;

            var match = new RegExp('^' + query, 'i');
            var length = query.length;

            countItems = length;

            $.each(items, function(index, item){
                if (index >= resultsLimit) {
                    return;
                }

                $popup.append(
                    $('<div>').append(
                        $('<div>')
                            .html(item.location.replace(match, '<b>' + item.location.substr(0, length) + '</b>'))
                            .attr('title', item.location)
                    )
                );
            });
        }

        var resetResults = function(){
            if (!$popup) {
                return;
            }

            $popup.empty();
            closePopup();
        }
     
        $elem = $(elem);
        $form = $elem.closest('form');

        $elem
            .attr('autocomplete', 'off')
            .on('keyup', function(e){
                clearTimeout(timer);

                if (ignoreKeys.indexOf(e.key) >= 0) {
                    return;
                }

                query = $(this).val();

                if (query.length < 3) {
                    resetResults();
                    return;
                }

                timer = setTimeout(request, requestDelay);
            })
            .on('click', function(){
                if ($(this).val().length >= 3 && !$popup) {
                    query = $(this).val();
                    request();
                }
            })
            .on('blur', function(){
                if ($(currentTarget).parents().hasClass('geo-autocomplete')) {
                    return;
                }

                closePopup();
            });
    };

    $.fn.geoAutocomplete = function(options){
        return this.each(function(){
            (new $.geoAutocomplete(this, options));
        });
    };
})(jQuery);
