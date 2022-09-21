
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: UTIL.JS
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

var flUtilClass = function(){
    /**
     * Last ajax request
     *
     * @type object
     */
    this.ajaxRequest = null;

    /**
     * Last ajax request key
     *
     * @type String
     */
    this.ajaxKey = null;

    /**
     * Media points data
     *
     * @type Array
     */
    this.media_points = {
        all_tablet_mobile: 'screen and (max-width: 991px)'
    };

    /**
     * Delay of slow internet
     * @since 4.7.0
     * @type  {Number}
     */
    this.loadingDelay = 300;

    /**
     * Array of function which can modify ajax data before ajax call
     *
     * @since 4.8.2
     * @type Array
     */
    this.modifyDataFunctions = [];

    /**
     * Initial class method
     *
     */
    this.init = function() {
        $.ajaxSetup({
            crossDomain: true,
            xhrFields: {
                withCredentials: true
            }
        });

        this.markLoadedScripts();
        this.markLoadedStyles();
    };

    /**
     * Mark all document loaded scripts to avoid it's repeat
     * uploading by loadScript method
     */
    this.markLoadedScripts = function() {
        var scripts = document.getElementsByTagName('script');

        for (var i in scripts) {
            if (!scripts[i].src || typeof scripts[i].onload == 'function') {
                continue;
            }

            scripts[i].onload = (function(i){
                scripts[i].loaded = true;
            })(i);
        }
    };

    /**
     * Mark all document loaded styles to avoid it's repeat uploading by loadStyle method
     *
     * @since 4.9.0
     */
    this.markLoadedStyles = function() {
        var styles = document.querySelectorAll('link[rel="stylesheet"]');

        for (var i in styles) {
            if (!styles[i].href || typeof styles[i].onload === 'function') {
                continue;
            }

            styles[i].onload = (function(i){
                styles[i].loaded = true;
            })(i);
        }
    };

    /**
     * Do ajax call
     *
     * @param array    - ajax request data
     * @param function - callback function
     * @param boolean  - is get request
     */
    this.ajax = function(data, callback, get) {
        // Abort the previous query if it's still in progress
        if (this.ajaxRequest
            && data.ajaxKey
            && data.ajaxKey == this.ajaxKey
        ) {
            this.ajaxRequest.abort();
        }

        // Apply data modifiers
        if (this.modifyDataFunctions.length) {
            for (var i in this.modifyDataFunctions) {
                if (typeof this.modifyDataFunctions[i] == 'function') {
                    this.modifyDataFunctions[i].call(this, data, callback, get)
                }
            }
        }

        if (!data.mode) {
            console.log('flynax.ajax - no "mode" index in the data parameter found, "mode" is required');
            return;
        }

        if (typeof callback != 'function') {
            console.log('flynax.ajax - the second parameter should be callback function');
            return;
        }

        /**
         * Get content in current/selected language
         * @since 4.8.0
         */
        data.lang = data.lang ? data.lang : rlLang;

        // request options
        var options = {
            type: get ? 'GET' : 'POST',
            url: rlConfig['ajax_url'],
            data: data,
            dataType: 'json',
            crossDomain: true,
            xhrFields: {
                withCredentials: true
           }
        }

        // save move
        this.ajaxKey = data.ajaxKey;

        // process request
        this.ajaxRequest = $.ajax(options)
            .success(function(response, status){
                callback(response, status);
            })
            .fail(function(object, status){
                if (status == 'abort') {
                    return;
                }

                callback(false, status);
            });
    };

    /**
     * Load script(s) once
     *
     * @param mixed    - script src as string or strings array
     * @param function - callback function
     */
    this.loadScript = function(src, callback){
        var loaderClass = function(){
            var self = this;

            this.completed = false;
            this.urls = [];
            this.done = [];
            this.loaded = [];
            this.callback = function(){};

            this.init = function(src, callback){
                if (!src) {
                    console.log('loadScript Error: no scrip to load specified');
                    return;
                }

                this.urls = typeof src == 'string' ? [src] : src;
                this.callback = typeof callback == 'function'
                    ? callback
                    : this.callback;

                // Fix script url protocol
                this.fixProtocol();

                // Check for already loaded script
                this.checkLoaded();

                // Loads scripts
                for (var i in this.urls) {
                    this.load(this.urls[i], i);
                }

                // Call callback
                this.call();
            }

            this.checkLoaded = function(){
                var loaded_scripts = document.getElementsByTagName('script');

                for (var i in loaded_scripts) {
                    if (typeof loaded_scripts[i] != 'object') {
                        continue;
                    }

                    var index = this.urls.indexOf(loaded_scripts[i]['src']);

                    if (index < 0) {
                        continue;
                    }

                    // Process loaded script
                    this.processLoaded(loaded_scripts[i], index);
                }
            }

            this.processLoaded = function(script, index){
                if (script.loaded) {
                    this.loaded[index] = true;
                    this.done[index] = true;
                } else {
                    var event = script.onload;

                    script.onload = function(){
                        self.done[index] = true;

                        // Call original event
                        if (typeof event == 'function') {
                            event.call();
                        }

                        // Call new event
                        self.call();
                    };
                    this.loaded[index] = true;
                }
            }

            // Check state
            this.isStateReady = function(readyState){
                return (!readyState || $.inArray(readyState, ['loaded', 'complete', 'uninitialized']) >= 0);
            }

            // Load script
            this.load = function(url, i) {
                // Skip loaded
                if (this.loaded[i]) {
                    return;
                }

                // Create script
                var script = document.createElement('script');
                script.src = url;

                // Bind to load events
                script.onload = function(){
                    if (self.isStateReady(script.readyState)) {
                        self.done[i] = true;

                        // Run the callback
                        self.call();

                        // Mark as loaded
                        script.loaded = true;
                    }

                    // Handle memory leak in IE
                    //script.onload = script.onreadystatechange = script.onerror = null; TODO TEST
                };

                // On error callback
                script.onerror = function(){
                    self.callback.call(new Error('Unable to load the script: ' + url));
                };

                // Append script into the head
                var head = document.getElementsByTagName('head')[0];
                head.appendChild(script);

                // Mark as loaded
                this.loaded[i] = true;
            }

            this.isReady = function(){
                var count = 0;
                for (var i in this.done) {
                    if (this.done[i] === true) {
                        count++;
                    }
                }

                return count == this.urls.length;
            }

            this.call = function() {
                if (this.isReady() && !this.completed) {
                    this.callback.call(this);
                    this.completed = true;
                }
            }

            this.fixProtocol = function() {
                if (!location.protocol) {
                    return;
                }

                for (var i in this.urls) {
                    if (0 === this.urls[i].indexOf('//')) {
                        this.urls[i] = location.protocol + this.urls[i];
                    }
                }
            }
        }

        var loader = new loaderClass();
        loader.init(src, callback);
    }

    this.loadStyle = function(src) {
        var loaderClass = function () {
            var self = this;

            this.completed = false;
            this.urls = [];
            this.done = [];
            this.loaded = [];

            this.init = function(src){
                if (!src) {
                    console.log('loadStyle Error: no style file to load specified');
                    return;
                }

                this.urls = typeof src == 'string' ? [src] : src;

                // Fix script url protocol
                this.fixProtocol();

                // Check for already loaded style
                this.checkLoaded();

                // Loads styles
                for (var i in this.urls) {
                    this.load(this.urls[i], i);
                }
            }

            this.checkLoaded = function() {
                var styles = document.querySelectorAll('link[rel="stylesheet"]');

                for (var i in styles) {
                    if (typeof styles[i] !== 'object' || !styles[i].href || styles[i].loaded === true) {
                        continue;
                    }

                    var index = this.urls.indexOf(styles[i]['href']);

                    if (index < 0) {
                        continue;
                    }

                    // Process load the style
                    this.processLoaded(styles[i], index);
                }
            }

            this.processLoaded = function(style, index) {
                if (style.loaded) {
                    this.loaded[index] = true;
                    this.done[index]   = true;
                } else {
                    var event = style.onload;

                    style.onload = function() {
                        self.done[index] = true;

                        // Call original event
                        if (typeof event == 'function') {
                            event.call();
                        }
                    };
                    this.loaded[index] = true;
                }
            }

            // Check state
            this.isStateReady = function(readyState){
                return (!readyState || $.inArray(readyState, ['loaded', 'complete', 'uninitialized']) >= 0);
            }

            // Load style
            this.load = function(url, i) {
                // Skip loaded
                if (this.loaded[i]) {
                    return;
                }

                // Create style
                var $style = $('<link>').attr({'rel': 'stylesheet', 'type': 'text/css', 'href': url});

                // Bind to load events
                $style.onload = function() {
                    if (self.isStateReady($style.readyState)) {
                        self.done[i] = true;

                        // Mark as loaded
                        $style.loaded = true;
                    }
                };

                // On error callback
                $style.onerror = function(){
                    self.callback.call(new Error('Unable to load the script: ' + url));
                };

                // Append style into the head
                $style.appendTo('head');

                // Mark as loaded
                this.loaded[i] = true;
            }

            this.isReady = function() {
                var count = 0;
                for (var i in this.done) {
                    if (this.done[i] === true) {
                        count++;
                    }
                }

                return count === this.urls.length;
            }

            this.fixProtocol = function() {
                if (!location.protocol) {
                    return;
                }

                for (var i in this.urls) {
                    if (0 === this.urls[i].indexOf('//')) {
                        this.urls[i] = location.protocol + this.urls[i];
                    }
                }
            }
        }

        if (rlLangDir === 'rtl'
            && src.indexOf(rlConfig.domain.replace(/^\./, '')) >= 0
            && src.indexOf('-rtl') < 0
            && src.indexOf('components/') > 0
        ) {
            src = src.replace(/(.*)\.css/, '$1-rtl.css');
        }

        new loaderClass().init(src);
    }

    /**
     * Checks if a string is an email
     *
     * @since 4.9.0
     *
     * @param email {string}
     * @returns {boolean}
     */
    this.isEmail = function (email) {
        return /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(String(email).toLowerCase());
    }
}

var flUtil = new flUtilClass();
