<!-- checkout step -->

<script>
{literal}

if (typeof window.paymentFailValidationCallback == 'undefined') {
    window.paymentFailValidationCallback = new Array();
}

window.paymentFailValidationCallback.push(function(){
    manageListing.enableButton();
});

{/literal}
</script>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.order_details}

<div class="info-table">
    <div class="table-cell">
        <div class="name"><div><span>{$lang.listing}</span></div></div>
        <div class="value">{$listing_title}</div>
    </div>

    <div class="table-cell">
        <div class="name"><div><span>{$lang.plan}</span></div></div>
        <div class="value">{$plan_info.name}</div>
    </div>

    <div class="table-cell">
        <div class="name"><div><span>{$lang.price}</span></div></div>
        <div class="value">
            {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
            {$plan_info.Price}
            {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
        </div>
    </div>

    {rlHook name='checkoutStepAfterOrderInfo'}
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

<form id="form-checkout" method="post" action="{buildFormAction}">
    <input type="hidden" name="step" value="checkout" />

    {gateways}

    <div class="form-buttons form">
        <a href="{buildPrevStepURL show_extended=$manageListing->singleStep}">{$lang.perv_step}</a>
        <input type="submit" value="{$lang.next_step}" data-default-phrase="{$lang.next_step}" />
    </div>
</form>

<!-- checkout step -->
