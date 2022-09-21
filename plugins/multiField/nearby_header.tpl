<!-- nearby listings header -->

<div class="col-12 mf-nearby-wrapper mt-2 mb-2">
    {if $mf_nearby_listings_only}
        <div class="text-notice">{$lang.no_listings_found_deny_posting}</div>
    {/if}

    <div class="align-center">
        {include file=$smarty.const.RL_PLUGINS|cat:'multiField/static/nearby.svg'}
    </div>

    <div class="fieldset divider text-center">
        <header>{$lang.mf_nearby_listings_hint}</header>
    </div>
</div>

<!-- nearby listings header end -->
