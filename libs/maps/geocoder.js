
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: GEOCODER.JS
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
 * Geocoder
 *
 * @since 4.8.0
 *
 * @param mixed $query - Location search as a string, ex: "San Francisco, FL", or as an array with possible keys:
 *                       REVERSE LOOKUP
 *                       * latlng     - latitude and longitude as string '30.3390,10.9870' or as an array [30.3390,10.9870]
 *
 *                       FILTRATION
 *                       * country    - filter results by country or country code
 *                       * state      - filter results by state
 *                       * county     - filter results by county
 *                       * city       - filter results by city
 *                       * street     - filter results by street
 *                       * postalcode - filter results by postal code
 *                       * query      - additinal filteration by query but nominatim recommend against combine it with other filters
 * @param function callback
 */
var geocoder = function(params, callback){
    if (!params || typeof callback != 'function') {
        return 'geocoder failed, no params or callback function specified.';
    }

    var data = {
        mode: 'geocoder',
        ajaxFrontend: true,
        lang: rlLang,
        params: params
    };

    flUtil.ajax(data, callback);
}
