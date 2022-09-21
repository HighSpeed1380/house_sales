<!-- select a category step -->

<div class="content-padding">
    <div class="text-notice">{$lang.add_listing_notice}</div>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'add_user_category.tpl'}

    <div class="category-selection">
        {include file=$componentDir|cat:'category-selector'|cat:$smarty.const.RL_DS|cat:'_category-selector.tpl' dropdown_data=$allowed_types}
    </div>

    <div class="form-buttons">
        <a id="next_step" href="javascript:void(0)" class="button disabled">{$lang.next}</a>
    </div>

    <script>
    rlConfig['user_category_path_prefix'] = '{$manageListing->userCategoryPathPrefix}';
    </script>

    <script class="fl-js-dynamic">
    {literal}

    (function(){
        "use strict";

        {/literal}
        var selected_id = {if $manageListing->category.ID}'{$manageListing->category.ID}'{else}false{/if};
        var user_category_id = {if $manageListing->category.user_category_id}'{$manageListing->category.user_category_id}'{else}false{/if};
        var parent_ids = '{if $manageListing->category.Parent_IDs}{$manageListing->category.Parent_IDs}{/if}';
        var selected_type = {if $manageListing->listingType}'{$manageListing->listingType.Key}'{else}null{/if};
        parent_ids = parent_ids == '' ? [] : parent_ids.split(',');
        {literal}

        // Load jsRender library and category_selector plugin
        flUtil.loadScript([
            rlConfig['libs_url'] + 'javascript/jsRender.js',
            rlConfig['tpl_base'] + 'components/category-selector/_category-selector.js'
        ], function(){
            $('.category-selection').categorySelector({
                actionButton: $('#next_step'),
                selectedID: selected_id,
                parentIDs: parent_ids,
                selectedType: selected_type,
                userCategoryID: user_category_id,
                onChange: function($select, $option){
                    // The "next step" button handler
                    if ($option.hasClass('locked')) {
                        this.options.actionButton.attr('href', 'javascript:void(0)');
                    } else {
                        var path = $option.data('path');
                        var next_step = $('ul.steps li.current').next().data('path');
                        var url = rlConfig['seo_url'] + rlPageInfo['path'] + '/' + path + '/' + next_step + '.html';

                        // No mod rewrite mode
                        if (!rlConfig['mod_rewrite']) {
                            var url = rlConfig['seo_url'] + '?page=' + rlPageInfo['path'] + '&step=' + next_step + '&id=' + $select.val();

                            if (path.indexOf(rlConfig['user_category_path_prefix']) === 0) {
                                url += '&' + rlConfig['user_category_path_prefix'];
                            }
                        }

                        this.options.actionButton.attr('href', url);
                    }
                },
                onLevelLoad: addUserCategoryAction
            });
        });
    })();

    {/literal}
    </script>
</div>

<!-- select a category step end -->
