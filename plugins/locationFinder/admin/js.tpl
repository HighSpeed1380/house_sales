<!-- locationFinder javascript on settings page -->

{geoAutocompleteAPI}

<script>
var locationFinder_default_location = '{$config.locationFinder_default_location}';
var locationFinder_disallow_sync = {if $disallow_sync}true{else}false{/if};
lang['locationFinder_geocoding_mismatch'] = '{$lang.locationFinder_geocoding_mismatch}';
{literal}

$(document).ready(function(){
    // Geolocation autocomplete
    var $input = $('input[name="post_config[locationFinder_search][value]"][type=text]');

    $input.geoAutocomplete({
        onSelect: function(name, lat, lng){
            $('input[name="locationFinder_default_location"]').val(lat + ',' + lng);
        }
    });

    $input.after('<input type="hidden" name="locationFinder_default_location" value="'+locationFinder_default_location+'" />');

    // Group position
    var field_position = $('select[name="post_config[locationFinder_position][value]"]');
    var field_type = $('input[name="post_config[locationFinder_type][value]"]');
    var field_group = $('select[name="post_config[locationFinder_group][value]"]');

    var locationFinder_check = function(){
        var val = field_position.val();

        if (val == 'in_group') {
            field_type.closest('tr').show();
            field_group.closest('tr').show();
        } else {
            field_type.closest('tr').hide();
            field_group.closest('tr').hide();
        }
    }

    locationFinder_check();
    field_position.change(function(){
        locationFinder_check();
    });

    if (locationFinder_disallow_sync) {
        var $opt = $('[name="post_config[locationFinder_mapping][value]"][type=radio]');
        $opt.attr('disabled', true);
        $opt.parent().find('span.settings_desc').text(lang['locationFinder_geocoding_mismatch']);
    }
});

{/literal}
</script>

<!-- locationFinder javascript on settings page end -->
