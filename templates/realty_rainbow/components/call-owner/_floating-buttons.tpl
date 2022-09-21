<!-- Call owner mobile floating buttons -->

{if !$is_owner && $pageInfo.Controller == 'listing_details' && $config.show_call_owner_button && $allow_contacts}
    <div class="contact-owner-navbar d-lg-none hlight w-100">
        <div class="point1 container mx-auto">
            <div class="d-flex pt-3 pb-3 content-padding">
                {if $config.messages_module}<input type="button" class="flex-fill mr-2 contact-owner" value="{phrase key='contact_owner'}" accesskey="{phrase key='contact_owner'}" />{/if}
                <input class="flex-fill call-owner{if $config.messages_module} ml-2{/if}" data-listing-id="{$listing_data.ID}" type="button" value="{phrase key='call_owner'}" />
            </div>
        </div>
    </div>
{/if}

<!-- Call owner mobile floating buttons end -->
