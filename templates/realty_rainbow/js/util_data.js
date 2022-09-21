
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: UTIL_DATA.JS
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

var flUtilDataClass = function(){
    this.adaptArray = function(data, index, int_indexes, float_indexes){
        if (!data) {
            console.log('adaptArray() failed | No data param passed');
            return false;
        }

        if (!index) {
            console.log('adaptArray() failed | No index param passed');
            return data;
        }

        var new_data = [];

        for (var i in data) {
            // Int values conversion
            if (int_indexes) {
                for (var j in data[i]) {
                    if (int_indexes.indexOf(j) >= 0) {
                        data[i][j] = parseInt(data[i][j]);
                    }
                }
            }

            // Float values conversion
            if (float_indexes) {
                for (var j in data[i]) {
                    if (float_indexes.indexOf(j) >= 0) {
                        data[i][j] = parseFloat(data[i][j]);
                    }
                }
            }
            new_data[data[i][index]] = data[i];
        }

        return new_data;
    }
}

var flUtilData = new flUtilDataClass();