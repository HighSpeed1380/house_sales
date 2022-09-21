<!-- build textarea with CKEditor -->

{if $fckEditorParams.fckEditorJsLoad}
    <script src="{$smarty.const.RL_LIBS_URL}ckeditor/ckeditor.js"></script>
    <script src="{$smarty.const.RL_LIBS_URL}ckfinder/ckfinder.js"></script>
{/if}

<textarea name="{$fckEditorParams.name}" id="{$fckEditorParams.name}" rows="10" cols="80">
    {$fckEditorParams.value}
</textarea>

<script>
var toolbar = rlConfig['fckeditor_bar'] == 'Basic' 
    ? 'Basic' 
    : [
        ['Source', '-', 'Bold', 'Italic', 'Underline', 'Strike'],
        ['Image', 'Flash', 'Link', 'Unlink', 'Anchor'],
        ['Undo', 'Redo', '-', 'Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'],
        ['TextColor', 'BGColor']
    ];

var editor_{$fckEditorParams.name} = CKEDITOR.replace('{$fckEditorParams.name}', {literal}{{/literal}
    language            : rlConfig['lang'],
    width               : "{if $fckEditorParams.width == '100%' || !$fckEditorParams.width}97%{else}{$fckEditorParams.width}{/if}",
    height              : "{if $fckEditorParams.height}{$fckEditorParams.height}{else}160{/if}",
    toolbar             : toolbar,
    filebrowserBrowseUrl: rlConfig['libs_url'] + 'ckfinder/ckfinder.html',
    filebrowserUploadUrl: rlConfig['libs_url'] + 'ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files'
{literal}}{/literal});
CKFinder.setupCKEditor(editor_{$fckEditorParams.name}, '../');
</script>

<!-- build textarea with CKEditor end -->
