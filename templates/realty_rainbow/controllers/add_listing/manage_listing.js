
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MANAGE_LISTING.JS
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

var manageListingClass = function(){
    self = this;

    this.$button = $('.form-buttons input[type=submit]');

    this.init = function(){
        this.$button.click(function(){
            setTimeout(function(){
                self.$button
                    .attr('disabled', true)
                    .addClass('disabled')
                    .val(lang['loading']);
            }, 1);
        });
    }

    this.enableButton = function(){
        setTimeout(function(){
            self.$button
                .attr('disabled', false)
                .removeClass('disabled')
                .val(self.$button.data('default-phrase')
                    ? self.$button.data('default-phrase')
                    : 'No default phrase found');
        }, 2);
    }
}

var manageListing = new manageListingClass();
manageListing.init();
