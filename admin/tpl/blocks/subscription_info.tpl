<!-- Subscription Information -->

{if $subscription}
{assign var='gateway_name' value='payment_gateways+name+'|cat:$subscription.gateway}
<table class="form">
    <tr>
        <td class="name" width="180">{$lang.payment_gateway}</td>
        <td class="value">{$lang[$gateway_name]}</td>
    </tr>
    <tr>
        <td class="name" width="180">{$lang.subscription_id}</td>
        <td class="value">{$subscription.id}</td>
    </tr>
    <tr>
        <td class="name" width="180">{$lang.status}</td>
        <td class="value">{$subscription.status}</td>
    </tr>
    <tr>
        <td class="name" width="180">{$lang.last_date_update}</td>
        <td class="value">{$subscription.last_date_update}</td>
    </tr>
</table>
{else}
    <table class="form">
    <tr>
        <td class="name">{$lang.subscription_not_found}</td>
    </tr>
    </table>
{/if}

<!-- end Subscription Information -->
