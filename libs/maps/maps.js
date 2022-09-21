
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MAPS.JS
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

var mapClass = function(){
    var self = this;

    this.$container      = null;
    this.map             = null;
    this.contentPlaceholder = '<div class="content-placeholder"><div class="content-placeholder-picture"></div><div class="content-placeholder-info"><div></div><div></div><div></div></div></div>';
    this.markers         = [];
    this.bounds          = [];
    this.itemIDs         = [];
    this.markerCluster   = null;
    this.geocoder        = null;
    this.baseComponentsLoaded = false;

    // Tile layer options
    this.layerUrl        = null;
    this.layerOptions    = {
        reuseTiles: true,
        updateWhenIdle: false
    };
    this.providerOptions = {};

    // Map options
    this.options         = {
        center: [37.7650611,-122.4657379],
        zoom: 10,
        scrollWheelZoom: false,
        minimizePrice: {
            centSeparator: '.',
            priceDelimiter: ',',
            kPhrase: 'k',
            mPhrase: 'm',
            bPhrase: 'b'
        },
        detectRetina: true,
        addresses: [],
        markerCluster: false,
        // geocoder: {
        //     placeholder: null,
        //     position: 'topright'
        // },
        geocoder: false,
        beforeIdle: false,
        idle: false,
        zoomControl: false,
        control: 'topright',
        userLocation: false,
        interactive: false,
        componentPath: rlConfig['tpl_base'],
        lang: null
    }

    // Map objects
    this.markerIcon      = {
        iconUrl: rlConfig['libs_url'] + 'maps/pictures/marker.svg',
        iconSize: [42, 42],
        iconAnchor: [21, 42],
        popupAnchor: [0, -42]
    };

    this.prepareProviderOptions = function(){
        var lng = this.options.lang ? this.options.lang : rlLang;

        switch (rlConfig['map_provider']){
            case 'alternative':
                //this.layerUrl = 'https://api.tiles.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw';
                //this.layerUrl = 'https://api.mapbox.com/styles/v1/mapbox/streets-v9/tiles/256/{z}/{x}/{y}{retina}?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw';
                //this.layerUrl = 'https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}{retina}.png';
                this.layerUrl = 'https://basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{retina}.png';
                this.providerOptions = {
                    attribution: 'Map data &copy; <a href="https://carto.com/">Carto</a>',
                    retina: L.Browser.retina ? '@2x' : ''
                }
                break;

            case 'openstreetmap':
                var locale = ['de'].indexOf(lng) >= 0 ? lng : 'org';
                this.layerUrl = 'https://{s}.tile.openstreetmap.' + locale + '/{z}/{x}/{y}.png';
                this.providerOptions = {
                    subdomains: ['a', 'b', 'c'],
                    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a>'
                }
                break;

            case 'google':
                this.layerUrl = 'https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&scale={retina}&hl=' + lng;
                this.providerOptions = {
                    subdomains: ['', 1, 2, 3],
                    attribution: 'Map data &copy; <a href="https://www.google.com/maps/">Google Maps</a>',
                    retina: L.Browser.retina ? 2 : 1
                }
                break;

            case 'yandex':
                this.layerUrl = 'https://core-renderer-tiles.maps.yandex.net/tiles?l=map&x={x}&y={y}&z={z}&scale={retina}&lang=' + lng;
                this.providerOptions = {
                    attribution: 'Map data &copy; <a href="https://yandex.ru/maps/">Yandex</a>',
                    retina: L.Browser.retina ? 2 : 1
                }

                // Switch to "Elliptical Mercator" projection
                this.options.crs = L.CRS.EPSG3395;
                break;
        }
    }

    this.init = function($container, options){
        if (!$container.length) {
            console.log('mapAPI: No map container provided');
        }

        this.options    = $.extend(true, this.options, options);
        this.$container = $container;

        this.loadModules();
    }

    /**
     * Load base map component styles
     *
     * @param bool force - Force load without data checking
     */
    this.loadBaseComponent = function(force){
        if (this.baseComponentsLoaded) {
            return;
        }

        var load = function(){
            var css = [];
            css.push(self.options.componentPath + 'components/map-listing/map-listing.css');
            css.push(self.options.componentPath + 'components/map-account/map-account.css');
            css.push(self.options.componentPath + 'components/marker-label/marker-label.css');

            flUtil.loadStyle(css);
            self.baseComponentsLoaded = true;
        }

        if (force) {
            load();
            return;
        }

        for (var i in this.options.addresses) {
            if (this.options.addresses[i].label || this.options.addresses[i].preview) {
                load();
                break;
            }
        };
    }

    this.initOptions = function(){
        if (typeof this.options.beforeIdle == 'function') {
            this.options.beforeIdle.call(this, this.map);
        }

        this.prepareProviderOptions();
        this.optimizeMapData(this.options.addresses);
        this.initCluster();
        this.redefineMapCenter();
        this.createMap();
        this.addMarkers(this.options.addresses, true);

        if (typeof this.options.idle == 'function') {
            this.map.whenReady(function(){
                this.options.idle.call(self, self.map);
            });
        }
    }

    this.loadModules = function(){
        var js  = [];
        var css = [];

        js.push(rlConfig['libs_url'] + 'javascript/jsRender.js');

        this.loadBaseComponent();

        if (this.options.markerCluster) {
            js.push(rlConfig['libs_url'] + 'maps/leaflet.markercluster.js');
            css.push(this.options.componentPath + 'components/marker-cluster/marker-cluster.css');
        }

        if (this.options.geocoder) {
            js.push(rlConfig['libs_url'] + 'maps/geoAutocomplete.js');
            css.push(this.options.componentPath + 'components/geo-autocomplete/geo-autocomplete.css');
        }

        if (css.length) {
            flUtil.loadStyle(css);
        }

        if (js.length) {
            flUtil.loadScript(js, function(){
                self.initOptions();
            });
        } else {
            this.initOptions();
        }
    }

    this.optimizeMapData = function(addresses){
        if (!addresses.length) {
            return;
        }

        addresses.forEach(function(marker, index, object){
            if (typeof marker.latLng == 'string') {
                marker.latLng = marker.latLng.split(',');
            }
        });
    }

    this.initCluster = function(){
        if (!this.options.markerCluster) {
            return;
        }

        var options = {
            showCoverageOnHover: false
        };

        if (this.options.markerCluster.groupCount) {
            options.iconCreateFunction = this.customCluster;
        }

        this.markerCluster = new L.MarkerClusterGroup(options);
    }

    this.redefineMapCenter = function(){
        if (this.options.addresses.length == 1) {
            this.options.center = this.options.addresses[0].latLng;
        }
    }

    this.createMap = function(){
        this.map = L.map(this.$container[0], this.options);

        if (this.options.geocoder) {
            L.Control.Geocoder = L.Control.extend({
                onAdd: function(map) {
                    var input = L.DomUtil.create('input', 'leaflet-autocomplete');
                    input.type = 'text';
                    L.DomEvent.disableClickPropagation(input);

                    if (map.options.geocoder.placeholder) {
                        input.placeholder = map.options.geocoder.placeholder;
                    }

                    return input;
                }
            });

            L.control.geocoder = function(options) {
                return new L.Control.Geocoder(options);
            }

            L.control.geocoder(this.options.geocoder).addTo(this.map);

            this.$container.find('.leaflet-autocomplete').geoAutocomplete({
                onSelect: function(name, lat, lng){
                    self.map.panTo(new L.LatLng(lat, lng));

                    if (typeof self.options.geocoder.onSelect == 'function') {
                        self.options.geocoder.onSelect.call(this, name, lat, lng)
                    }
                }
            });
        }

        L.tileLayer(this.layerUrl, $.extend(this.layerOptions, this.providerOptions))
            .addTo(this.map);

        if (this.options.control) {
            L.control.zoom({position: this.options.control})
                .addTo(this.map);

            // Define user location button
            if (this.options.userLocation) {
                if (typeof this.options.userLocation.success == 'function') {
                    this.map.on('locationfound', this.options.userLocation.success);
                }

                var onLocationError = typeof this.options.userLocation.failure == 'function'
                    ? this.options.userLocation.failure
                    : this.onLocationError;

                this.map.on('locationerror', onLocationError);

                var customControl = L.Control.extend({
                    options: {position: this.options.control},
                    onAdd: function (map) {
                        var $container = $('<div>')
                            .addClass('leaflet-bar leaflet-control leaflet-control-location')
                            .append(
                                $('<a>')
                                    .attr('href', 'javascript://')
                                    .append('<svg viewBox="0 0 20 20"><use xlink:href="#userLocation"></use></svg>')
                            ).on('click', function(){
                                self.map.locate({
                                    setView: true,
                                    maxZoom: 16
                                });
                            });

                        return $container[0];
                    },
                });

                this.map.addControl(new customControl());
            }
        }
    }

    this.addMarkers = function(addresses){
        if (!addresses.length) {
            return;
        }

        this.optimizeMapData(addresses);

        var icon = L.icon(this.markerIcon);

        addresses.forEach(function(address){
            var id = parseInt(address.ID ? address.ID : address.id);

            if (id && self.options.interactive) {
                if (self.itemIDs.indexOf(id) < 0) {
                    self.itemIDs.push(id);
                } else {
                    return;
                }
            }

            var latLng = address.lat && address.lng
                ? [address.lat, address.lng]
                : address.latLng;

            if (!latLng) {
                console.log('mapAPI: No marker coordinate provided');
                return;
            }

            if (address.label || address.preview) {
                self.loadBaseComponent(true);
            }

            var marker = L.marker(latLng);

            if (id && self.options.interactive) {
                marker.itemID = id;
            }

            if (address.gc && parseInt(address.gc) > 1) {
                marker.groupCount = parseInt(address.gc);
            }

            if (address.label) {
                if (self.options.minimizePrice) {
                    address.label = self.shortPrice(address.label);
                }

                self.setMarkerLabel(marker, address.label);
            } else {
                marker.setIcon(icon);
            }

            // Append simple popup
            if (address.content) {
                marker.bindPopup(address.content);
            }
            // Append custom popup
            else if (address.preview) {
                if (address.preview.id) {
                    var componentClass = address.preview.component ? address.preview.component : 'listing';
                    var popupClass = 'leaflet-custom-popup_' + componentClass;

                    if (address.label) {
                        popupClass += ' leaflet-custom-popup_label-style';
                    }

                    if (address.gc > 1) {
                        popupClass += ' leaflet-custom-popup_group-style';
                    }

                    var popup = L.popup({
                        className: 'leaflet-custom-popup ' + popupClass
                    });
                    var onOpen = typeof address.preview.onOpen == 'function'
                        ? address.preview.onOpen
                        : self.onPopupOpen;

                    popup.setContent(self.contentPlaceholder);

                    marker
                        .bindPopup(popup)
                        .on('popupopen', function(popup){
                            return onOpen(popup, address);
                        });

                    if (typeof address.preview.onClick == 'function') {
                        marker.on('click', address.preview.onClick);
                    }
                } else {
                    console.log('mapAPI: No {preview.id} param found for marker address');
                }
            }

            if (self.markerCluster) {
                self.markerCluster.addLayer(marker);
            } else {
                marker.addTo(self.map);
            }

            self.markers.push(marker);
            self.bounds.push(L.latLng(address.latLng));
        });

        if (self.markerCluster) {
            this.map.addLayer(self.markerCluster);
        }

        // Remove not visible markers
        if (this.options.interactive) {
            this.removeOutBoundsMarkers();
        }
        // Fit bounds
        else if (addresses.length > 1) {
            this.map.fitBounds(this.bounds);
        }
    }

    this.onPopupOpen = function(popup, address){
        var ajaxMode  = 'getListingData';
        var component = rlConfig.tpl_cors_base + 'components/map-listing/map-listing.tmpl';
        var onOpen    = flFavoritesHandler;

        if (address.preview.component == 'account') {
            ajaxMode  = 'getAccountData';
            component = rlConfig.tpl_cors_base + 'components/map-account/map-account.tmpl';
            onOpen    = null;
        } else if (address.preview.component) {
            component = address.preview.component;

            if (address.preview.ajaxMode) {
                ajaxMode = address.preview.ajaxMode;
            }
        }

        var onOpenCallback = function(){
            if (typeof onOpen == 'function') {
                onOpen.call(this, popup, address);
            }
        }

        if (!address.contentLoaded) {
            var data = {
                mode: ajaxMode,
                id: address.preview.id
            };
            flUtil.ajax(data, function(response, status){
                if (status == 'success' && response['status'] == 'OK') {
                    address.contentLoaded = true;

                    $.get(component, function(data){
                        var tmpl = $.templates(data);
                        var content = tmpl.render(response.results);
                        popup.popup.setContent(content);

                        onOpenCallback();

                        if (typeof address.preview.onContentReady == 'function') {
                            address.preview.onContentReady.call(popup, address, content);
                        }
                    });
                } else {
                    printMessage('error', lang['system_error']);
                }
            });
        } else {
            onOpenCallback();
        }
    }

    this.onLocationError = function(e){
        var message = location.protocol == 'https' && lang['gps_support_denied']
            ? lang['gps_support_denied']
            : e.message;

        printMessage('warning', message);
    }

    this.removeAllMarkersExcept = function(ignoreIDs){
        for (var i in this.markers) {
            if (ignoreIDs && ignoreIDs.indexOf(this.markers[i].itemID) >= 0) {
                continue;
            }

            this.removeMarker(i);
        }
    }

    this.removeAllMarkers = function(){
        for (var i in this.markers) {
            this.removeMarker(i);
        }
    }

    this.setMarkerLabel = function(marker, label){
        marker.setIcon(L.divIcon({
            html: '<span>' + label + '</span>',
            className: 'marker-label'
        }));
    }

    this.getMarkerByID = function(id){
        var marker = null;

        if (!id) {
            return marker;
        }

        for (var i in this.markers) {
            if (id == this.markers[i].itemID) {
                marker = this.markers[i];
                break;
            }
        }

        return marker;
    }

    this.getClusterByMarker = function(marker){
        var cluster = this.markerCluster.getVisibleParent(marker);

        return cluster._group ? cluster : null;
    }

    this.closePopups = function(){
        this.map.closePopup();
        this.map.closeTooltip();
    }

    this.removeOutBoundsMarkers = function(){
        var bounds = this.map.getBounds();

        for (var i in this.markers) {
            var marker = this.markers[i];

            if (!bounds.contains(marker.getLatLng())) {
                self.removeMarker(i)
            }
        }
    }

    this.removeMarker = function(i){
        var marker = this.markers[i];

        this.map.removeLayer(marker);
        delete this.markers[i];

        if (this.markerCluster) {
            this.markerCluster.removeLayer(marker);
        }

        if (this.options.interactive) {
            var index = this.itemIDs.indexOf(marker.itemID);
            if (index >= 0) {
                this.itemIDs.splice(index, 1);
            }
        }
    }

    this.customCluster = function(cluster) {
        var childCount = cluster.getChildCount();
        var markers = cluster.getAllChildMarkers();

        for (var i in markers) {
            if (markers[i].groupCount > 1) {
                childCount += markers[i].groupCount - 1;
            }
        }

        var c = ' marker-cluster-';
        if (childCount < 10) {
            c += 'small';
        } else if (childCount < 100) {
            c += 'medium';
        } else {
            c += 'large';
        }

        return new L.DivIcon({
            html: '<div><span>' + childCount + '</span></div>',
            className: 'marker-cluster' + c,
            iconSize: new L.Point(40, 40)
        });
    }

    this.shortPrice = function(string){
        var matches = string.match(/^([^0-9]+)?([\d\.\,\'\s]+)([^0-9]+)?$/i);
        if (matches && (matches[1] || matches[3]) && matches[2]) {
            var currency = matches[1] || matches[3];
            var conv_price = matches[2].split(eval('/\\' + this.options.minimizePrice.centSeparator + '[0-9]{0,2}/'));
            var pattern = new RegExp('[' + this.options.minimizePrice.priceDelimiter + ']', 'gi');
            conv_price = conv_price[0].replace(pattern, '');
            var plain_number = conv_price;
            var comma_count = Math.floor(conv_price.length/3);
            comma_count -= conv_price.length%3 == 0 ? 1 : 0;

            if (comma_count > 0) {
                var sign, short;
                if (comma_count == 1) {
                    sign = this.options.minimizePrice.kPhrase;
                    short = plain_number/1000;
                } else if (comma_count == 2) {
                    sign = this.options.minimizePrice.mPhrase;
                    short = plain_number/1000000;
                } else if (comma_count > 2) {
                    sign = this.options.minimizePrice.bPhrase;
                    short = plain_number/1000000000;
                }

                if (sign) {
                    short = Math.round(short*10)/10;
                    string = short + ' ' + sign;
                    string = matches[1] ? matches[1] + string : string + matches[3];
                }
            }
        }

        return string;
    }
}

var flMap = new mapClass();
