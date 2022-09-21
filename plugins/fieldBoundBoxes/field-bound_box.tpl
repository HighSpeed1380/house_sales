<!-- field-bound box tpl -->

{if empty($fbb_options)}
    <span class="text-notice">{phrase key='fbb_no_options' db_check=true}</span>
{else}
    {php}
    global $page_info;

    $block = $this->get_template_vars('block');
    $side_bar_exists = $this->get_template_vars('side_bar_exists');
    $fbb_is_nova = $this->get_template_vars('fbb_is_nova');
    $fbb_box = $this->get_template_vars('fbb_box');
    $special_block = $this->get_template_vars('home_page_special_block');
    $is_special = isset($special_block) && $special_block['Key'] == $block['Key'];

    $columns = $this->get_template_vars('pageMode') ? $fbb_box['Page_columns'] : $fbb_box['Columns'];
    $icons_horizontal = $fbb_box['Icons_position'] == 'right' || $fbb_box['Icons_position'] == 'left';

    if ($columns == 'auto') {
        if ($fbb_box['Style'] == 'text_pic') {
            if ($icons_horizontal) {
                $class = 'col-12 col-sm-6 col-md-4 ';
                $class .= $side_bar_exists ? 'col-xl-3' : 'col-xl-2';

                if (in_array($block['Side'], array('middle_left', 'middle_right'))) {
                    $class = 'col-15 col-sm-6 col-md-6 ';
                    $class .= $side_bar_exists ? 'col-lg-12 col-xl-6' : ' col-xl-4';
                } elseif ($block['Side'] == 'left' || $is_special) {
                    $class = 'col-12 col-sm-6 col-md-4 col-lg-12';
                }
            } else {
                $class = 'col-6 col-md-3 col-lg-3';
                $class .= !$side_bar_exists || $fbb_is_nova ? ' col-xl-2' : '';

                if (in_array($block['Side'], array('middle_left', 'middle_right'))) {
                    $class = 'col-6 col-xl-4';
                } elseif ($block['Side'] == 'left' || $is_special) {
                    $class = 'col-6 col-md-3 col-lg-6';
                }
            }
        } else {
            $class = 'col-sm-6 col-md-4 ';
            $class .= !$side_bar_exists || $fbb_is_nova ? 'col-lg-3' : 'col-lg-4';

            if (in_array($block['Side'], array('middle_left', 'middle_right'))) {
                $class = 'col-sm-6';
            } elseif ($block['Side'] == 'left' || $is_special) {
                $class = 'col-sm-6 col-md-4 col-lg-12';
            }
        }
    } else {
        $set_col = 12 / $columns;
        $class = 'col-md-' . $set_col;
    }

    if ($GLOBALS['tpl_settings']['bootstrap_grid_no_xl']) {
        $class = str_replace(['col-xl-2', 'col-xl-3', 'col-xl-4'], '', $class);
    }

    /**
     * @todo - Remove this code when the plugun compatibility > 4.8.1
     */
    if ($is_special && version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<=')) {
        $class = str_replace('col-md-3', 'col-md-4', $class);
    }

    $this->assign('icons_horizontal', $icons_horizontal);
    $this->assign('fbb_col_class', $class);
    $this->assign('fbb_columns', $columns);

    $no_picture_url = RL_TPL_BASE . 'img/no-picture.png';
    $this->assign_by_ref('no_picture_url', $no_picture_url);

    if (is_file(RL_ROOT . 'templates/' . $GLOBALS['config']['template'] . '/img/no-picture.svg')) {
        $no_picture_url = RL_TPL_BASE . 'img/no-picture.svg';
    }
    {/php}

    {if $fbb_box.Style == 'responsive'}
        <ul class="row field-bound-box-responsive field-bound-box-responsive_{$fbb_box.Orientation}{if $fbb_columns != 'auto'} field-bound-box-responsive_custom-column{/if}">
            {foreach from=$fbb_options item='option'}
            <li class="{$fbb_col_class}">
                <a class="w-100 field-bound-box-responsive__wrapper d-block position-relative" href="{$option.link}">
                    <img class="w-100 h-100 position-absolute{if !$option.Icon} field-bound-box-responsive__img_no-picture{/if}" 
                         src="{if $option.Icon}{$option.Icon}{else}{$no_picture_url}{/if}" />
                    <div class="field-bound-box-responsive__footer w-100 position-absolute d-flex flex-column">
                        <div class="field-bound-box-responsive__info d-flex flex-row align-items-end">
                            <span class="w-100 field-bound-box-responsive__name text-truncate flex-fill text-overflow pr-2" title="{$option.name}">{$option.name}</span>
                            {if $fbb_box.Show_count}
                                <div class="field-bound-box-responsive__count">{$option.Count}</div>
                            {/if}
                        </div>

                        {if $fbb_box.Orientation == 'portrait'}
                            <div class="text-center field-bound-box-responsive__button">{$lang.fbb_view_listings}</div>
                        {/if}
                    </div>
                </a>
            </li>
            {/foreach}
        </ul>
    {elseif $fbb_box.Style == 'text'}
        <div class="categories{if !$fbb_box.Show_count} sub-categories-exist{/if}">
            <ul class="row field-bound-box-text{if $fbb_columns != 'auto'} field-bound-box-text_custom-column{/if}">
                {foreach from=$fbb_options item='option'}
                <li class="{$fbb_col_class} item{if $fbb_box.Show_count && !$option.Count} empty-category{/if}">
                    <div class="parent-cateory field-bound-box-text__wrapper{if $fbb_is_nova || $fbb_flex_layout} d-flex flex-row-reverse justify-content-end{/if}">
                        {if $fbb_box.Show_count}
                            <div class="{if $fbb_is_nova || $fbb_flex_layout}ml-2 font-size-xs text-info font-weight-bold category-counter{else}category-counter{/if}">
                                <span class="d-flex">{$option.Count}</span>
                            </div>
                        {/if}

                        <div class="category-name shrink-fix">
                            <a class="category text-overflow align-top mw-100" title="{$option.name}" href="{$option.link}">{$option.name}</a>
                        </div>
                    </div>
                </li>
                {/foreach}
            </ul>
        </div>
    {elseif $fbb_box.Style == 'text_pic'}
        <ul class="row field-bound-box-text-pic">
            {if $fbb_box.Icons_position == 'right'}
                {assign var='icons_position' value='flex-row-reverse'}
            {elseif $fbb_box.Icons_position == 'top'}
                {assign var='icons_position' value='flex-column'}
            {elseif $fbb_box.Icons_position == 'bottom'}
                {assign var='icons_position' value='flex-column-reverse'}
            {/if}

            {foreach from=$fbb_options item='option'}
            <li class="{$fbb_col_class} {if $fbb_box.Show_count && !$option.Count} empty-category{/if}{if !$icons_horizontal} text-center{/if}">
                <a class="field-bound-box-text-pic__wrapper mw-100 d-inline-flex category {$icons_position}{if !$icons_horizontal} align-items-center{/if}" title="{$option.name}" href="{$option.link}">
                    <img style="width: {$fbb_box.Icons_width}px;height: {$fbb_box.Icons_height}px;"
                         src="{if $option.Icon}{$option.Icon}{else}{$no_picture_url}{/if}"
                         class="field-bound-box-text-pic__img{if !$option.Icon} field-bound-box-text-pic__img_no-picture{/if}" />

                    <span class="d-flex shrink-fix mw-100 {if $icons_horizontal}align-items-center {if $fbb_box.Icons_position == 'left'}ml-2{else}mr-2{/if}{else}my-2{if $fbb_box.Orientation == 'portrait' && $fbb_box.Show_count} flex-column{/if}{/if} justify-content-center">
                        <span class="text-overflow">{$option.name}</span>
                        {if $fbb_box.Show_count}
                            <span class="font-size-xs text-info font-weight-bold category-counter {if !$icons_horizontal && $fbb_box.Orientation == 'portrait'}mt-2{else}ml-2{/if}">{$option.Count}</span>
                        {/if}
                    </span>
                </a>
            </li>
            {/foreach}
        </ul>
    {elseif $fbb_box.Style == 'icon'}
        {php}
        global $page_info;

        $block = $this->get_template_vars('block');
        $side_bar_exists = $this->get_template_vars('side_bar_exists');
        $fbb_is_nova = $this->get_template_vars('fbb_is_nova');
        $special_block = $this->get_template_vars('home_page_special_block');
        $is_special = $special_block['Key'] == $block['Key'];

        $class = 'col-4 col-sm-3 ';

        if ($block['Side'] == 'left' || $is_special) {
            $class .= $fbb_is_nova ? 'col-md-2 col-lg-6 col-xl-4' : 'col-md-2 col-lg-4';
        } elseif (in_array($block['Side'], array('middle_left', 'middle_right'))) {
            $class .= $side_bar_exists ? 'col-md-4 col-lg-4 col-xl-3' : 'col-md-4 col-lg-3 col-xl-2';
        } else {
            $class .= $side_bar_exists ? 'col-md-2' : 'col-md-2 col-lg-1';
        }

        if ($GLOBALS['tpl_settings']['bootstrap_grid_no_xl']) {
            $class = str_replace(['col-xl-2', 'col-xl-3', 'col-xl-4'], '', $class);
        }

        /**
         * @todo - Remove this code when the plugun compatibility > 4.8.1
         */
        if ($is_special && version_compare($GLOBALS['config']['rl_version'], '4.8.1', '<=')) {
            $class = str_replace('col-md-3', 'col-md-4', $class);
        }

        $this->assign('fbb_col_class', $class);
        {/php}

        <ul class="row field-bound-box-icon">
            {foreach from=$fbb_options item='option'}
            <li class="field-bound-box-icon__col {$fbb_col_class}">
                <a class="d-flex flex-column align-items-center hint{if !$option.Count} field-bound-box-item_empty{/if}" title="{$option.name}" href="{$option.link}">
                    <img style="width: {$fbb_box.Icons_width}px;height: {$fbb_box.Icons_height}px;"
                         src="{if $option.Icon}{$option.Icon}{else}{$no_picture_url}{/if}"
                         class="field-bound-box-icon__img{if !$option.Icon} field-bound-box-text-pic__img_no-picture{/if}" />

                    <span class="my-2 font-weight-bold{if !$option.Count} field-bound-box-count_empty{/if}">{$option.Count}</span>
                </a>
            </li>
            {/foreach}
        </ul>
    {/if}
{/if}

<!-- field-bound box tpl end-->
