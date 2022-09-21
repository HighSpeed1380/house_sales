{assign var='block_cookies_index' value='ap_blocks_'|cat:$key}
{assign var='smarty_cookies' value=$smarty.cookies}

<div class="block{if $block.Status == 'approval' && $cKey == 'home'} hide{/if}"{if $key} id="apblock:{$key}"{/if}>
    <table class="header{if !$block_caption}_no_caption{elseif !isset($navigation)}_light{/if}">
    <tr>
        <td class="left"></td>
        <td class="center">
            {if !$block_caption}
                <div></div>
            {else}
                {if isset($navigation)}<div class="move{if $smarty_cookies.$block_cookies_index == 'hide'} hover{/if}"></div>{/if}
                {$block_caption}
                {if isset($navigation)}<div class="collapse{if $smarty_cookies.$block_cookies_index == 'hide'} collapse_hover{/if}"></div>{/if}
            {/if}
        </td>
        <td class="right"></td>
    </tr>
    </table>    
    <div class="outer{if $smarty_cookies.$block_cookies_index == 'hide'} hide{/if}"{if $key} lang="{$key}"{/if}>
        <div class="body{if $loading} block_loading{/if}" {if $fixed}style="height: 190px;overflow-y: auto;"{/if}>
