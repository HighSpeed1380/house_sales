
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: _ACCOUNT-REMOVING.JS
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
 * Show/Hide popup with account removing details
 * @since 4.7.0
 */
var flAccountRemovingClass = function(){
    var self            = this;
    var $errorContainer = null;
    var request_sent    = false;

    /**
     * Initialization of popup
     * Popup doesn't show "password" field for incomplete account
     * 
     * @param {int}    account_id - ID of incomplete account
     * @param {string} hash       - Temp hash of incomplete account
     */
    this.init = function(account_id, hash){
        $baseContainer = $('div.main-wrapper').length ? $('div.main-wrapper') : $('#main_container');

        $baseContainer.popup({
            click  : false,
            width  : 350,
            caption: lang['warning'],
            content: $('<div>')
                        .addClass('account-removing-popup')
                        .text(lang[account_id ? 'account_remove_notice' : 'account_remove_notice_pass'])
                        .append(
                            $('<div>')
                                .addClass('account-removing-popup-password')
                                .append(
                                    !account_id ? $('<input>')
                                        .attr(
                                            {
                                                type       : 'password',
                                                name       : 'account-removing-password-input',
                                                placeholder: lang['password']
                                            }
                                        ).on('keyup', function(event){
                                            // send request if user click Enter
                                            if (event.keyCode == '13') {
                                                $('div.popup input.button').trigger('click');
                                            }
                                        }) : null,
                                    $('<div>').addClass('field error hide').append($('<label>'))
                                )
                        ),
            navigation: {
                okButton: {
                    text   : lang['delete_account'],
                    class  : 'button warning',
                    onClick: function(popup){
                        self.$errorContainer = popup.$interface.find('div.error > label');
                        var $removeButton    = popup.$interface.find('input.button.warning');
                        var $cancelButton    = popup.$interface.find('input.cancel');
                        var default_phrase   = lang['delete_account'];

                        // block both buttons
                        $removeButton.attr('disabled', true).addClass('disabled').val(lang['loading']);
                        $cancelButton.addClass('disabled');

                        // remove account without passwrod confirmation
                        if (account_id && hash) {
                            self.request_sent = true;

                            flUtil.ajax({mode: 'removeAccount', id: account_id, hash: hash, lang: rlLang}, 
                                function(response, status){
                                    if (status == 'success') {
                                        if (response.status == 'OK' && response.redirect) {
                                            $(window).off('beforeunload');
                                            location.href = response.redirect;
                                        } else if (response.status == 'ERROR') {
                                            self.request_sent = false;
                                            popup.close();
                                            printMessage('error', lang['system_error']);
                                        }
                                    } else {
                                        self.request_sent = false;
                                        popup.close();
                                        printMessage('error', lang['system_error']);
                                    }
                                }
                            );
                        } else {
                            var $passContainer  = popup.$interface.find('[name="account-removing-password-input"]');
                            var password        = $passContainer.val().trim();

                            if (password == '' || password.length <= 3) {
                                self.showError(lang['password_lenght_fail']);
                                $removeButton.removeAttr('disabled').removeClass('disabled').val(default_phrase);
                                $cancelButton.removeClass('disabled');
                                $passContainer.addClass('error');
                            } else {
                                self.request_sent = true;

                                flUtil.ajax({mode: 'removeAccount', pass: password, lang: rlLang}, 
                                    function(response, status){
                                        if (status == 'success') {
                                            if (response.status == 'OK' && response.redirect) {
                                                $(window).off('beforeunload');
                                                location.href = response.redirect;
                                            } else if (response.status == 'ERROR' && response.message) {
                                                self.request_sent = false;
                                                $removeButton
                                                    .removeAttr('disabled')
                                                    .removeClass('disabled')
                                                    .val(default_phrase);
                                                $cancelButton.removeClass('disabled');
                                                self.showError(response.message);
                                                $passContainer.addClass('error');
                                            }
                                        } else {
                                            self.request_sent = false;
                                            $removeButton
                                                .removeAttr('disabled')
                                                .removeClass('disabled')
                                                .val(default_phrase);
                                            $cancelButton.removeClass('disabled');
                                            self.showError(lang['system_error']);
                                        }
                                    }
                                );
                            }

                            // remove errors in popup
                            $passContainer.on('keyup', function(){
                                $passContainer.removeClass('error');
                                self.$errorContainer.removeClass('error').text('');
                                self.$errorContainer.parent().addClass('hide');
                            });
                        }

                        $(window).on('beforeunload', function(){
                            if (self.request_sent) {
                                return lang['account_remove_in_process'];
                            }
                        });
                    }
                },
                cancelButton: {
                    text   : lang['cancel'],
                    class  : 'cancel',
                    onClick: function(popup) {
                        if (self.request_sent) {
                            self.showError(lang['account_remove_in_process']);
                        } else {
                            popup.destroy();
                        }
                    }
                }
            },
            onClose: function(){
                if (self.request_sent) {
                    self.showError(lang['account_remove_in_process']);
                } else {
                    this.destroy();
                }
            }
        });
    }

    this.showError = function(phrase){
        if (!phrase || !self.$errorContainer) {
            return false;
        }

        self.$errorContainer.text(phrase).parent().removeClass('hide');
    }
}

var flAccountRemoving = new flAccountRemovingClass();
