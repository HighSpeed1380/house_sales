{if $photos_count < $listing.Photos}
    <form method="post" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=photos&amp;id={$smarty.get.id}" enctype="multipart/form-data">
    <input name="upload" value="true" type="hidden" />
        <div style="padding: 0 0 0 10px;">
            <table id="upload_fields">
            <tr>
                <td>{$lang.photo}:</td>
                <td style="width: 10px;" rowspan="100"></td>
                <td>
                    <input class="file" type="file" name="photo_1" />
                </td>
                <td rowspan="2">
                    {if $listing.Photos - $photos_count > 1}<span id="lable_1" onclick="add_photo_field('{$listing.Photos}', '{$photos_count}');" class="label">{$lang.add_field}</span>{/if}
                </td>
            </tr>
            <tr>
                <td>{$lang.description}:</td>
                <td>
                    <input class="text" type="text" name="description_1" />
                </td>
            </tr>
            </table>
        </div>
        <input style="margin: 8px 5px 4px 11px;" class="button" type="submit" name="finish" value="{$lang.upload}" />
    </form>
{else}
    {assign var='plan_lang' value='listing_plans+name+'|cat:$listing.Plan_key}

    {assign var='replace_count' value=`$smarty.ldelim`count`$smarty.rdelim`}
    {assign var='replace_plan' value=`$smarty.ldelim`plan`$smarty.rdelim`}

    <div style="margin: 10px;" class="grey_middle">{$lang.no_more_photos|replace:$replace_count:$listing.Photos|replace:$replace_plan:$lang.$plan_lang}</div>
{/if}
