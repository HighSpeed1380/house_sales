<!-- MultiField tpl footer -->

{if $multi_format_keys}
    <script src="{$smarty.const.RL_PLUGINS_URL}multiField/static/lib.js"></script>

    <script>
    {literal}
    $(function(){
        for (var i in mfFields) {
            (function(fields, values){
                var mfHandler = new mfHandlerClass();
                mfHandler.init('f', fields, values);
            })(mfFields[i], mfFieldVals[i]);
        }
    });
    {/literal}
    </script>
{/if}

<!-- MultiField tpl footer end -->
