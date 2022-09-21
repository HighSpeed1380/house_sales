{strip}
<!-- item out value tpl -->

{if $item.Type == 'checkbox' && is_array($item.Values)}
    <ul class="checkboxes row">
    {if $item.Opt2}{math assign='col_count' equation='12 / opt' opt=$item.Opt2}{/if}
    {foreach from=$item.Values item='tile' name='checkboxF'}
        {if !empty($item.Condition)}
            {assign var="tit_source" value=$tile.Key}
        {else}
            {assign var="tit_source" value=$tile.ID}
        {/if}
        {if $item.Opt1 || $tit_source|in_array:$item.source}
            <li title="{$lang[$tile.pName]}" class="col-xs-12 {if $col_count}col-sm-{$col_count}{else}col-lg-4 col-md-6 col-sm-4{/if} {if $tit_source|in_array:$item.source}active{/if}">
                <img src="{$rlTplBase}img/blank.gif" alt="" />{$lang[$tile.pName]}
            </li>
        {/if}
    {/foreach}
    </ul>
{else}
    {if $item.Type == 'phone'}
        {if $item.Contact && $item.fake}
            <span>{$item.value}</span>
        {else}
            {if $item.Hidden}
                {if $item.source && $listing_data && $listing_data.ID}
                    {assign var='entityID' value=$listing_data.ID}
                    {assign var='entity' value='listing'}
                {elseif !$item.source && $seller_info && $seller_info.ID}
                    {assign var='entityID' value=$seller_info.ID}
                    {assign var='entity' value='account'}
                {/if}

                <div class="hidden-phone"
                     data-entity="{$entity}"
                     data-entity-id="{$entityID}"
                     data-field="{$item.Key}"
                     data-listing-id="{if $listing_data && $listing_data.ID}{$listing_data.ID}{/if}"
                >
                    {if $config.hidden_phone_numbers > 0}
                        {$item.value}
                    {/if}
                </div>
                <div class="show-phone{if $entity === 'account' && $config.hidden_phone_numbers > 0} mt-1 mb-1{/if}">
                    <a title="{$lang.phone_show_number}" href="javascript://">{$lang.phone_show_number}</a>
                </div>
            {else}
                <a href="tel:{$item.value}">{$item.value}</a>
            {/if}
        {/if}
    {elseif $item.Condition == 'isEmail'}
        {if $item.Contact && $item.fake}<span>{$item.value}</span>{else}<a href="mailto:{$item.value}">{$item.value}</a>{/if}
    {elseif $item.Condition == 'isUrl'}
        {if $item.Contact && $item.fake}<span>{$item.value}</span>{else}<a rel="nofollow" href="{$item.value}" target="_blank">{$item.value|replace:'http://':''|replace:'https://':''|truncate:35:'..':true:true}</a>{/if}
    {else}
        {$item.value}
    {/if}
{/if}

<!-- item out value tpl end -->
{/strip}
