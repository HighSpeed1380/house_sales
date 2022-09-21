<!-- add listing -->

{rlHook name='addListingTopTpl'}

{if !$no_access}
    {if isset($manageListing->singleStep) && !$manageListing->singleStep}
        <!-- steps -->
        {assign var='allow_link' value=true}
        {assign var='current_found' value=false}

        <ul class="steps mobile">
            {assign var='steps_values' value=$steps|@array_values}
            {foreach from=$steps_values item='step' name='stepsF' key='step_key'}{strip}
                {if $cur_step == $step.key || !$cur_step}{assign var='allow_link' value=false}{/if}

                {assign var='next_index' value=$step_key+1}
                <li data-path="{$steps[$step.key].path}" class="{if $cur_step == $step.key}current{assign var='current_found' value=true}{elseif !$current_found}{if $steps_values[$next_index].key == $cur_step}prev {/if}past{/if}">
                    <a href="{if $allow_link}{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}{if $step.key == 'category'}.html?edit{else}/{$manageListing->category.Path}/{$steps[$step.key].path}.html{/if}{else}?page={$pageInfo.Path}{if $step.key != 'category'}&id={$manageListing->category.ID}{/if}{if $steps[$step.key].path}&step={$steps[$step.key].path}{/if}{if $step.key == 'category'}&edit{/if}{/if}{else}javascript:void(0){/if}" title="{$step.name}">{if $step.caption}<span>{$lang.step}</span> {$smarty.foreach.stepsF.iteration}{else}{$step.name}{/if}</a>
                </li>
            {/strip}{/foreach}
        </ul>
        <!-- steps -->

        <h1>{$pageInfo.name}</h1>
    {/if}

    {addJS file=$rlTplBase|cat:'controllers/add_listing/manage_listing.js'}

    {processStep}

    {rlHook name='addListingStepActionsTpl'}
{elseif $errors}
    <ul class="text-notice">
    {foreach from=$errors item='error'}
        <li>{$error}</li>
    {/foreach}
    </ul>
{/if}

<!-- add listing end -->
