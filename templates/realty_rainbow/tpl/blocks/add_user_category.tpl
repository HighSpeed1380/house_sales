<!-- add user category -->

<script id="add_user_category" type="text/x-jsrender">
    {assign var='tmp_link' value='<a href="javascript:void(0);" class="add">$1</a>'}
    {assign var='replace' value='<b>[%:category%]</b>'}
    {assign var='find' value=`$smarty.ldelim`category`$smarty.rdelim`}

    <span class="tmp-category">
        <span class="tmp-info">{$lang.tmp_category_info|regex_replace:'/\[(.*)\]/':$tmp_link|replace:$find:$replace}</span>
        <span class="tmp-input">
            <input type="text" />
            <input value="{$lang.add}" data-default-value="{$lang.add}" type="button" />
            <span class="red margin">{$lang.cancel}</span>
        </span>
    </span>
</script>

<script class="fl-js-dynamic">
{literal}

var addUserCategoryAction = function($select, $option, $current_cont, id){
    // build user category submission interface
    if ($option.hasClass('user-category')) {
        var parent_category = {category: $option.text()};

        // append dom
        $current_cont.append(
            $('#add_user_category').render(parent_category)
        );

        var $user_category_cont = $current_cont.find('span.tmp-category');
        var $user_category_input = $user_category_cont.find('input[type=text]');

        // handle events
        $user_category_cont
            .on('click', '.tmp-info a', function(){
                $user_category_cont.addClass('show-input');
                $user_category_input.focus();
            })
            .on('click', 'span.red', function(){
                $user_category_cont.removeClass('show-input');
            })
            .on('click', 'input[type=button]', function(){
                var name = $user_category_input.val();
                var $button = $(this);

                if (name) {
                    $button.val(lang['loading']);

                    // add tmp category
                    var data = {
                        mode: 'addUserCategory',
                        parent_id: id,
                        name: name,
                        account_id: rlAccountInfo['ID']
                    };
                    flUtil.ajax(data, function(response, status){
                        // reset button state
                        $button.val($button.data('default-value'));

                        // process results
                        if (status == 'success') {
                            if (response.status == 'OK') {
                                // add new category to the select
                                $current_cont.find('select').append(
                                    $('<option>')
                                        .text(name)
                                        .val(response.results)
                                        .attr('data-path', rlConfig['user_category_path_prefix'] + response.results)
                                    )
                                    .focus()
                                    .val(response.results)
                                    .change();

                                // remove interface
                                $user_category_input.val('');
                                $user_category_cont.fadeOut(function(){
                                    $(this).remove();
                                });
                            } else {
                                printMessage('error', response.message);
                            }
                        } else {
                            printMessage('error', lang['system_error']);
                        }
                    });
                }
            });
    }
}

{/literal}
</script>

<!-- add user category end -->