<!-- plan option jsrender template -->

<script id="plan_selector_option" type="text/x-jsrender">{strip}
    <option value="[%:ID%]" [%if Listings_remains%]data-available="true"[%/if%] [%if plan_disabled%]disabled="disabled"[%/if%]>
        [%:name%] (
        [%if plan_disabled%]
            {$lang.used_up}
        [%else Listings_remains%]
            {$lang.available}
        [%else%]
            [%if Price == 0%]{$lang.free}[%else%]
            {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
            [%:Price%]
            {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
            [%/if%]
        [%/if%]
        )
    </option>
{/strip}</script>

<!-- plan option jsrender template end -->
