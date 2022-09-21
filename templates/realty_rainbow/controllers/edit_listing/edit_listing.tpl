<!-- edit listing -->

{if !$no_access}
    {addJS file=$rlTplBase|cat:'controllers/add_listing/manage_listing.js'}
    
    {processStep}
{elseif $errors}
    <ul class="text-notice">
    {foreach from=$errors item='error'}
        <li>{$error}</li>
    {/foreach}
    </ul>
{/if}

<!-- edit listing end -->