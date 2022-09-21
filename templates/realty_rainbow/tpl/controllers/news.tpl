<!-- news tpl -->

<div class="content-padding">
    {if $article}
        <div class="date">{$article.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>

        {rlHook name='newsPostCaption'}

        <article class="news">
            {$article.content}
            {rlHook name='newsPostContent'}
        </article>
        
        <div class="ralign">
            <a title="{$lang.back_to_news}" href="{$back_link}">{$lang.back_to_news}</a>
        </div>
    {else}
        {if $news}
            <ul class="news">
            {foreach from=$news item='news_item'}
                <li>
                    <div>
                        <div class="date">{$news_item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                        <a class="link-large" title="{$news_item.title}" href="{$rlBase}{if $config.mod_rewrite}{$pages.news}/{$news_item.Path}.html{else}?page={$pages.news}&amp;id={$news_item.ID}{/if}">
                            <h4>{$news_item.title}</h4>
                        {rlHook name='newsPostCaption'}</a>
                    </div>
                    
                    <article>
                        {assign var='newsContent' value=$news_item.content}
                        {assign var='newsContent' value=$newsContent|regex_replace:"/(<style[^>]*>[^>]*<\\/style>)/mi":""|strip_tags:false}

                        {$newsContent|truncate:$config.news_page_content_length:"":false}
                        {if $newsContent|strlen > $config.news_page_content_length}...{/if}
                        {rlHook name='newsPostContent'}
                    </article>
                </li>
            {/foreach}
            </ul>
            
            <!-- paging block -->
            {paging calc=$pageInfo.calc total=$news current=$pageInfo.current per_page=$config.news_at_page}
            <!-- paging block end -->
        {else}
            <div class="text-notice">{$lang.no_news}</div>
        {/if}
    {/if}
    
    {rlHook name='newsBottomTpl'}
</div>

<!-- news tpl end -->
