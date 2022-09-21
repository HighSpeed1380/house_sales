<!-- MultiField tpl header -->

{if $multi_format_keys}
<script>
    var mfFields = new Array();
    var mfFieldVals = new Array();
    lang['select'] = "{$lang.select}";
    lang['not_available'] = "{$lang.not_available}";
</script>
{/if}

<script>
    lang['any'] = "{$lang.any}";
    var mfGeoFields = new Array();
    if (typeof rlLang == 'undefined') var rlLang = '{$smarty.const.RL_LANG_CODE}';
</script>

<style>
{literal}

.mf-opt-label {
    margin-left: 20px;
}
.mf-opt-label.mf-disabled {
    filter: grayscale(.9);
    opacity: 0.70;
}
.mf-hint {
    padding: 15px 0;
}

{/literal}
</style>

<!-- MultiField tpl header end -->
