
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SEARCH_MAP.JS
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

var flMapSearch = function(){
    var self = this;

    this.options = {
        mapContainer: null,
        mapCenter: null,
        mapAltLocation: null,
        mapZoom: 14,
        listingGrid: null,
        searchForm: null,
        tabBar: null,
        perPage: 10,
        desktopLimit: 500,
        mobileLimit: 75,
        geocoder: true
    };

    this.$gridHeader = null;
    this.$gridContainer = null;
    this.$listingContainer = null;

    this.requestTimeout = 1 * 1000;
    this.listingsTimer = false;
    this.preventCall = false;
    this.adsLimit = 0;
    this.activeTypeKey = null;
    this.formData = [];

    this.map = null;
    this.mapClass = null;

    this.init = function(options) {
        this.options = $.extend(this.options, options);

        this.adsLimit = media_query == 'mobile' ? this.options.mobileLimit : this.options.desktopLimit;

        // Pan map to the visitor location defined by IPGeo plugin
        if (this.options.mapAltLocation && !this.options.mapCenter.length) {
            var data = {
                mode: 'geocoder',
                lang: rlLang,
                params: this.options.mapAltLocation
            };

            flUtil.ajax(data, function(response, status){
                if (status == 'success' && response.status == 'OK' && response.results[0]) {
                    self.options.mapCenter = [response.results[0].lat,response.results[0].lng];
                } else {
                    self.options.mapCenter = [];
                }

                self.initMap();
            });
        }
        // Init map with default location
        else {
            self.initMap();
        }

        // Define listing grid related elements
        if (this.options.listingGrid) {
            this.$gridHeader = this.options.listingGrid.find('#listings_cont > header');
            this.$listingContainer = this.options.listingGrid.find('div.wrapper > div');
            this.$container = $('.search-map-container');
            this.$mobileNav = self.$container.find('.mobile-navigation > div');
        }
    }

    this.initMap = function(){
        flMap.init(this.options.mapContainer, {
            center: this.options.mapCenter,
            zoom: this.options.mapZoom,
            markerCluster: {
                groupCount: true
            },
            geocoder: !this.options.geocoder ? false : {
                placeholder: lang['enter_a_location'],
                position: 'topright'
            },
            interactive: true,
            minimizePrice: {
                centSeparator: rlConfig['price_separator'],
                priceDelimiter: rlConfig['price_delimiter'],
                kPhrase: lang['short_price_k'],
                mPhrase: lang['short_price_m'],
                bPhrase: lang['short_price_b']
            },
            userLocation: true,
            idle: function(map){
                self.map = map;
                self.mapClass = this;

                self.searchForm();
                self.tabBar();
                self.setEvents();

                if (!flynax.getHash()) {
                    self.getListings();
                }
            }
        });
    }

    this.setEvents = function(){
        this.map.on('moveend', function(e){
            self.callListings();
        });

        if (this.options.listingGrid) {
            $('.control.btn').click(function(){
                var side = rlLangDir == 'ltr' ? 'left' : 'right';
                var $mapListings = $('#map_listings');
                var margin = self.$container.hasClass('collapse') ? 0 : '-' + $mapListings.width() + 'px';
                $mapListings.css('margin-' + side, margin);
                self.$container.toggleClass('collapse');

                $mapListings.on('transitionend', function(e){
                    if (e.originalEvent.propertyName == 'margin-' + side) {
                        self.map.invalidateSize();
                    }
                });
            });

            enquire.register("screen and (max-width: 767px)", {
                match: function(){
                    self.gridModeSwitcher(true);
                },
                unmatch: function(){
                    self.gridModeSwitcher(false);
                }
            });

            this.options.mapContainer.click(function(){
                var $mapButton = self.$mobileNav.filter('.map');
                if (!$mapButton.hasClass('active')) {
                    $mapButton.trigger('click');
                }
            });

            this.$mobileNav.click(function(){
                if ($(this).hasClass('active')) {
                    return;
                }

                self.$mobileNav.filter('.active').removeClass('active');
                var mode = $(this).attr('class');
                $(this).addClass('active');

                self.$container
                    .attr('class', 'search-map-container')
                    .addClass(mode);
            });
        }
    }

    this.gridModeSwitcher = function(list){
        var mode = list ? 'list' : 'grid';
        $('#map_area .first-slide #listings').attr('class', 'clearfix ' + mode);
    }

    this.searchForm = function(){
        if (!this.options.searchForm) {
            return;
        }

        var $activeForm = this.options.searchForm.filter(':not(.hide)').find('form');

        this.activeTypeKey = $activeForm.attr('accesskey');
        this.formData      = $activeForm.find('select,input[type=radio]:checked,input[type=text],input[type=number],input[type=hidden]').serializeArray();

        this.options.searchForm.find('form').submit(function(e) {
            self.formData = $(this).find('select,input[type=radio]:checked,input[type=text],input[type=number],input[type=hidden]').serializeArray();
            self.activeTypeKey = $(this).attr('accesskey');

            self.mapClass.closePopups();
            self.getListings(true);

            // Show map
            if (self.options.listingGrid && media_query == 'mobile') {
                self.$mobileNav.filter('.map').trigger('click');
            }

            e.preventDefault();
        });
    }

    this.tabBar = function(){
        if (!this.options.tabBar) {
            return;
        }

        var timer = false;

        this.options.tabBar.find('li a, label').click(function(){
            if (media_query == 'mobile') {
                return;
            }

            clearTimeout(timer);

            var id = $(this).data('target');

            timer = setTimeout(function(){
                $('#area_' + id + ' form').submit();
            }, 1000);
        });

        if (flynax.getHash()) {
            $('#area_' + flynax.getHash().replace('_tab', '') + ' form').submit();
        }
    }

    this.callListings = function(){
        clearTimeout(this.listingsTimer);
        this.listingsTimer = setTimeout(function(){
            if (!self.preventCall) {
                self.getListings();
            } else {
                self.preventCall = false;
            }
        }, this.requestTimeout);
    }

    this.getListings = function(clear){
        self.loading(true);

        var data = {
            mode: 'getListingsByCoordinates',
            ajaxKey: 'getListingsByCoordinates',
            lang: rlLang,
            type: self.activeTypeKey,
            start: 0,
            form: self.formData,
            device: media_query,
            home_page: rlPageInfo['key'] == 'home' ? 1 : 0
        };

        flUtil.ajax($.extend({}, data, self.getEdgeBounds()), function(result, status){
            if (status == 'success') {
                var listingIDs = [];
                totalListings = 0;

                if (result && result.count > 0) {
                    totalListings = parseInt(result.listings.length);

                    // Prepare listings
                    for (var i in result.listings) {
                        result.listings[i].preview = {
                            id: result.listings[i].ID,
                            onClick: function(){
                                self.preventCall = true;
                            }
                        }

                        var gc = parseInt(result.listings[i].gc);

                        if (gc > 1) {
                            totalListings += gc - 1;
                            result.listings[i].label = lang['count_properties'].replace('{count}', gc);
                            result.listings[i].preview.onOpen = self.onGroupOpen;
                        } else {
                            result.listings[i].label = result.listings[i].price;
                        }

                        listingIDs.push(parseInt(result.listings[i].ID));
                    }

                    self.mapClass.addMarkers(result.listings);

                    // show limit message
                    if (result.count > self.adsLimit) {
                        printMessage('warning', lang['map_search_limit_warning']);
                        clear = true;
                    }
                }

                if (clear) {
                    self.mapClass.removeAllMarkersExcept(listingIDs);
                }

                // Listing grid
                if (self.options.listingGrid) {
                    self.$listingContainer.empty();

                    var $caption = self.$gridHeader.find('.caption');

                    if (result && result.count > 0) {
                        $caption.text(lang['number_property_found'].replace('{count}', totalListings));

                        // Render listings
                        self.buildGrid(result.listings);
                    } else {
                        $caption.text(lang['no_properties_found']);
                    }
                }
            } else {
                printMessage('error', lang['map_listings_request_fail']);
            }

            self.loading(false);
        });
    }

    this.getGridMode = function(){
        return media_query == 'mobile' ? 'list' : 'grid row';
    }

    this.buildGrid = function(listings){
        // interface
        this.$listingContainer.append('<section id="listings" class="clearfix '+ this.getGridMode() +'"></section><div></div>');

        var current_page = 1;
        var $listingContainer = this.$listingContainer.find('#listings');
        var $paginationContainer = $listingContainer.next();
        var ls = null;
        var rs = null;
        var pages = Math.ceil(listings.length / this.options.perPage);

        var loadPage = function(){
            var from = (current_page * self.options.perPage) - self.options.perPage;
            var to = from + self.options.perPage;

            $listingContainer.empty();

            $('#tmplListing').tmpl(listings.slice(from, to)).appendTo($listingContainer);
            $listingContainer.find('> article > *').on('mouseenter mouseleave', function(e){
                var id = parseInt($(this).parent().attr('id').split('_')[2]);
                self.highlightListing(id, e.type == 'mouseleave');
            }).on('click', function(){
                var id = parseInt($(this).parent().attr('id').split('_')[2]);
                self.zoomToGroup(id);
            });

            $paginationContainer.find('input').val(current_page);
            self.options.listingGrid.find('> div').animate({scrollTop: 0}, 'fast');

            flFavoritesHandler();

            if (pages > 1) {
                if (current_page == 1) {
                    ls.hide();
                    rs.show();
                }
                else if (current_page == pages) {
                    ls.show();
                    rs.hide();
                }
                else {
                    ls.show();
                    rs.show();
                }
            }
        }

        // build pagination
        if (pages > 1) {
            $('#tmplPagination').tmpl({pages: pages}).appendTo($paginationContainer);
            ls = $paginationContainer.find('li.navigator.ls');
            rs = $paginationContainer.find('li.navigator.rs');

            // events
            rs.click(function(){
                current_page++;
                loadPage();
            });

            ls.click(function(){
                current_page--;
                loadPage();
            });

            $paginationContainer.find('input').keypress(function(e){
                if (e.which == 13) {
                    var r_page = parseInt($(this).val());
                    if (r_page > 0 && r_page <= pages) {
                        current_page = r_page;
                        loadPage();
                    }
                }
            });
        }

        loadPage();
    }

    this.onGroupOpen = function(popup, address){
        if (!address.contentLoaded) {
            var data = {
                mode: 'getListingsByCoordinates',
                item: 'n/a',
                lang: rlLang,
                type: self.activeTypeKey,
                start: 0,
                form: self.formData,
                group: 1,
                lat: address.lat,
                lng: address.lng
            };
            flUtil.ajax(data, function(response, status){
                if (status == 'success') {
                    address.contentLoaded = true;

                    if (response.count) {
                        var content = $('<header>')
                            .text(lang['number_property_found'].replace('{count}', response.count))
                            .get(0).outerHTML;

                        $.get(rlConfig.tpl_cors_base + 'components/map-listing/map-listing.tmpl', function(data){
                            var tmpl = $.templates(data);
                            content += tmpl.render(response.listings);
                            popup.popup.setContent(content);

                            flFavoritesHandler();
                        });
                    } else {
                        popup.popup.setContent(lang['system_error']);
                    }
                } else {
                    printMessage('error', lang['system_error']);
                }
            });
        } else {
            flFavoritesHandler();
        }
    }

    this.highlightListing = function(id, reset){
        var marker = this.mapClass.getMarkerByID(id);

        if (marker) {
            var cluster = this.mapClass.getClusterByMarker(marker);

            if (cluster) {
                $(cluster.getElement())[
                    reset ? 'removeClass' : 'addClass'
                ]('marker-cluster_hover');
            } else {
                $(marker.getElement())[
                    reset ? 'removeClass' : 'addClass'
                ]('marker-label_hover');
            }
        }
    }

    this.zoomToGroup = function(id){
        var marker = this.mapClass.getMarkerByID(id);

        if (marker && marker.groupCount > 1) {
            var cluster = this.mapClass.getClusterByMarker(marker);

            if (cluster) {
                this.mapClass.markerCluster.zoomToShowLayer(marker, function(){
                    marker.openPopup();
                });
            } else {
                if (!marker.getPopup().isOpen()) {
                    marker.openPopup();
                }
            }
        }

        // Show map
        if (this.options.listingGrid && media_query == 'mobile') {
            clearTimeout(this.listingsTimer);
            self.preventCall = true;
            self.$mobileNav.filter('.map').trigger('click');
        }
    }

    this.getEdgeBounds = function(){
        return {
            centerLat: this.map.getCenter().lat,
            centerLng: this.map.getCenter().lng,
            northEastLat: this.map.getBounds().getNorthEast().lat,
            northEastLng: this.map.getBounds().getNorthEast().lng,
            southWestLat: this.map.getBounds().getSouthWest().lat,
            southWestLng: this.map.getBounds().getSouthWest().lng
        };
    }

    this.loading = function(enable){
        $('.loading-container')[
            enable ? 'addClass' : 'removeClass'
        ]('loading-container_show');

        if (enable) {
            $('.notification div.close').trigger('click');
        }

        if (this.options.listingGrid) {
            this.$gridHeader[
                enable ? 'addClass' : 'removeClass'
            ]('progress');
        }
    }
};
var mapSearch = new flMapSearch();
