<!-- news block tpl -->

{if !empty($all_news)}
    <ul class="news">
    {foreach from=$all_news item='newsData'}
        <li>
            <div>
                <div class="date">
                    {$newsData.Date|date_format:$smarty.const.RL_DATE_FORMAT}
                </div>
                
                <a title="{$newsData.title}" href="{$rlBase}{if $config.mod_rewrite}{$pages.news}/{$newsData.Path}.html{else}?page={$pages.news}&amp;id={$newsData.ID}{/if}">
                    <h4>{$newsData.title}</h4>
                </a>
            </div>
            <article>
                {assign var='newsContent' value=$newsData.content}
                {assign var='newsContent' value=$newsContent|regex_replace:"/(<style[^>]*>[^>]*<\\/style>)/mi":""|strip_tags:false}

                {$newsContent|truncate:$config.news_block_content_length:"":false}
                {if $newsContent|strlen > $config.news_block_content_length}...{/if}
            </article>
        </li>
    {/foreach}
    </ul>
    <div class="ralign">
        <a title="{$lang.all_news}" href="{pageUrl key='news'}">{$lang.all_news}</a>
    </div>
{else}
    {$lang.no_news}
{/if}

<!-- news block tpl end -->
