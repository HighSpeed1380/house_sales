<!-- Field Bound Boxes plugin header.tpl -->

{if $fbb_is_nova}
{literal}
<style>
@media screen and (min-width: 1200px) {
    /*body:not(.no-sidebar) aside:not(.two-middle) .field-bound-box-responsive > li {
        flex: 0 0 25%;
        max-width: 25%;
    }*/
    body.no-sidebar aside:not(.two-middle) .field-bound-box-responsive:not(.field-bound-box-responsive_custom-column) > li,
    body.no-sidebar aside:not(.two-middle) .field-bound-box-text:not(.field-bound-box-text_custom-column) > li {
        flex: 0 0 20%;
        max-width: 20%;
    }
}
@media (max-width: 575px) {
    .field-bound-box-responsive > li {
        max-width: 300px;
    }
}
</style>
{/literal}
{/if}

{if $fbb_flex_layout}
{literal}
<style>
.fieldBoundBoxes .categories {
    margin-top: 0;
}
</style>
{/literal}
{/if}

{if $config.rl_version|version_compare:'4.8.1':'<='}
{literal}
<style>
/**
 * @todo - Remove once the plugin compatibility > 4.8.1
 */
.position-absolute {
    position: absolute;
}
.position-relative {
    position: relative;
}
.d-flex {
    display: flex;
}
.d-inline-flex {
  display: inline-flex;
}
.d-block {
    display: block;
}
.w-100 {
    width: 100%;
}
.mw-100 {
    max-width: 100% !important;
}
.h-100 {
    height: 100%;
}
.align-top {
    vertical-align: top !important;
}
.text-center {
    text-align: center;
}
.flex-fill {
    flex: 1;
}
.align-items-end {
    -ms-flex-align: end;
    align-items: flex-end;
}
.flex-row {
  -ms-flex-direction: row;
  flex-direction: row;
}
.flex-row-reverse {
  -ms-flex-direction: row-reverse;
  flex-direction: row-reverse;
}
.flex-column {
  -ms-flex-direction: column;
  flex-direction: column;
}
.flex-column-reverse {
  -ms-flex-direction: column-reverse;
  flex-direction: column-reverse;
}
.justify-content-end {
  -ms-flex-pack: end;
  justify-content: flex-end;
}
.align-items-center {
  -ms-flex-align: center;
  align-items: center;
}
.flex-wrap {
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
}
.ml-2 {
    margin-left: 0.5rem !important;
}
.mr-2 {
    margin-right: 0.5rem !important;
}
.my-2 {
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
}

body[dir=rtl] .ml-2 {
    margin-left: 0 !important;
    margin-right: 0.5rem !important;
}
body[dir=rtl] .mr-2 {
    margin-right: 0 !important;
    margin-left: 0.5rem !important;
}
/** TODO END */
</style>
{/literal}
{/if}

{if $tpl_settings.name|strpos:'modern' !== false}
{literal}
<style>
/**
 * @todo - Remove once the plugin compatibility > 4.8.1
 */
section.fieldBoundBoxes:not(.no-style) > div {
    padding-bottom: 20px !important;
}
</style>
{/literal}
{/if}

{literal}
<style>
.field-bound-box-responsive_landscape .field-bound-box-responsive__wrapper {
    padding-bottom: 65%;
}
.field-bound-box-responsive_portrait .field-bound-box-responsive__wrapper {
    padding-bottom: 135%;
}
.field-bound-box-responsive {
    margin-bottom: -30px;
}
.field-bound-box-responsive > li {
    margin-bottom: 30px;
}
.field-bound-box-responsive__wrapper:after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 1;
    background: linear-gradient(to top, rgba(0,0,0,.4), rgba(0,0,0,.5) 50%, rgba(0,0,0,0));
}
.field-bound-box-responsive__wrapper:hover:after {
    background: linear-gradient(to top, rgba(0,0,0,.25), rgba(0,0,0,.35) 50%, rgba(0,0,0,0));
}
.field-bound-box-responsive__wrapper img {
    object-fit: cover;
}
.field-bound-box-responsive__img_no-picture {
    object-fit: none !important;
}
.field-bound-box-responsive__footer {
    bottom: 0;
    z-index: 2;
}
.field-bound-box-responsive__info {
    padding: 0 10px 7px;
    color: white;
}
.field-bound-box-responsive__button {
    color: white;
    padding: 13px 0;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.2);

    transition: all 0.2s ease;
}
.field-bound-box-responsive__button:hover {
    color: black;
    background-color: rgb(255,255,255,.8);
}
.field-bound-box-responsive__name {
    font-size: 1.286em;
}
.field-bound-box-responsive__count {
    font-size: 1.375em;
}

.field-bound-box-text__wrapper {
    padding-bottom: 10px;
}

.field-bound-box-text-pic {
    margin-bottom: -20px;
}
.field-bound-box-text-pic__wrapper {
    margin: 0 !important;
    padding: 0 !important;
    font-size: 1em !important;
}
.field-bound-box-text-pic__img {
    object-fit: contain;
    max-width: 100%;
}
.field-bound-box-text-pic > li {
    padding-bottom: 20px;
}
.field-bound-box-text-pic__img_no-picture {
    object-fit: contain;
}
.field-bound-box-text-pic a.category {
    height: auto !important;
    background: initial;
}

.field-bound-box-icon {
    margin-bottom: -5px;
}
.field-bound-box-icon__col {
    padding-bottom: 10px;
}
.field-bound-box-icon__img {
    object-fit: contain;
}

.field-bound-box-item_empty img:not(.field-bound-box-text-pic__img_no-picture) {
    filter: brightness(1.1) opacity(0.8);
}
.field-bound-box-count_empty {
    opacity: .5;
}
</style>
{/literal}

<!-- Field Bound Boxes plugin header.tpl -->
