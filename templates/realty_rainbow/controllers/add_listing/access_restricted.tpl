<!-- Add listing access restricted view -->

<div class="area_done step_area content-padding">
    {if $button_phrase}
        <div class="text-notice">{$phrase}</div>
        <a class="button" href="{$link}">{$button_phrase}</a>
    {else}
        {assign var='replace' value='<a href="'|cat:$link|cat:'">$1</a>'}
        {$phrase|regex_replace:'/\[(.*)\]/':$replace}
    {/if}
</div>

<!-- Add listing access restricted view end -->