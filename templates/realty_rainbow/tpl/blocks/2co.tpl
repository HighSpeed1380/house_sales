<!-- 2checkout gateway -->

<input type="hidden" name="2co-token" id="2co-token" value="" />
<div class="submit-cell">
    <div class="name">{$lang.card_holder_name}</div>
    <div class="field">
        <input type="text" name="2co[card_name]" id="2co-name" class="wauto" maxlength="35" size="35" value="{$smarty.post.2co.card_name}" />
    </div>
</div>
<div id="card-element" style="max-width: 520px;"></div>

<script type="text/javascript">

var tc_btnEl = '#btn-checkout, #form-checkout input[type="submit"]';
var tc_btnVal = $(tc_btnEl).val();

{literal}
$(document).ready(function() {
    $(tc_btnEl).click(function() {
        if ($('input[name="gateway"]:checked').val() != '2co') {
            $('#2co-form').remove();
            return true;
        }
        $(tc_btnEl).val(lang['loading']);
        $(tc_btnEl).attr('disabled', true); 
        return true;
    });

    $("input.numeric").numeric();
    $('input#use-account-info').prop('checked', true);
    $('#billing-form').hide();

    flUtil.loadScript('https://2pay-js.2checkout.com/v1/2pay.js', function(){
        // Initialize the 2Pay.js client.
        let jsPaymentClient = new TwoPayClient('{/literal}{$config.2co_id}{literal}');

        // Create the component that will hold the card fields.
        let component = jsPaymentClient.components.create('card');

        // Mount the card fields component in the desired HTML tag. This is where the iframe will be located.
        component.mount('#card-element');

        // Handle form submission.
        $(tc_btnEl).click(function(event) {
            event.preventDefault();

            // Extract the Name field value
            const billingDetails = {
                name: document.getElementById('2co-name').value
            };

            // Call the generate method using the component as the first parameter
            // and the billing details as the second one
            jsPaymentClient.tokens.generate(component, billingDetails).then((response) => {
                $('#2co-token').val(response.token);
                $('#form-checkout').submit();
            }).catch((error) => {
                printMessage('error', error);
            });
        });
    });
});
{/literal}
</script>

<!-- end 2checkout gateway -->
