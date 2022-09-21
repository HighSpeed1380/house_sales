<!-- listing box grid-view box styles -->

<style>
{literal}

ul.lb-box-grid li.item .photo {
    width: 60px;
    height: 60px;
    float: left;
    margin-right: 10px;
    padding: 0;
    border: 0;
    border-radius: 0;
}
ul.lb-box-grid li.item .photo img {
    width: 100%;
    height: 100%;
    border: 0px;
}
/* craigslist fallback */
ul.lb-box-grid > li div.picture.no-picture img {
    background-size: cover;
}
/* craigslist fallback end */
ul.lb-box-grid li.item ul {
    padding: 0!important;
    margin: 0!important;
    overflow: hidden;
    background: transparent;
    box-shadow: none;
    width: auto!important;
}
ul.lb-box-grid li.item ul > li.title {
    margin: -2px 0 5px 0;
    text-overflow: ellipsis;
    padding: 0px;

    position: static;
    background: transparent;

    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
ul.lb-box-grid li.item ul > li.title > a {
    white-space: normal;
}
ul.lb-box-grid li.item span.price-tag {
    font-weight: normal;
}

/* rlt option */
body[dir=rtl]  ul.lb-box-grid li.item .photo {
    float: right;
    margin-right: 0;
    margin-left: 10px;
}

/*** ALL DESKTOPS VIEW ***/
@media screen and (min-width: 992px) {
    .side_block ul.lb-box-grid li.col-md-12:not(:last-child) {
        margin-bottom: 10px;
    }
}
/*** MIDDLE DESKTOP VIEW ***/
@media screen and (min-width: 992px) and (max-width: 1199px) {
    .two-middle ul.lb-box-grid li.col-md-12:not(:last-child) {
        margin-bottom: 10px;
    }
}
/*** LARGE DESKTOP VIEW ***/
@media screen and (min-width: 1200px) {
    .two-middle ul.lb-box-grid li.col-md-12:not(.col-lg-6):not(:last-child) {
        margin-bottom: 10px;
    }
}
/*** MOBILE VIEW ***/
@media screen and (max-width: 767px) {
    ul.lb-box-grid li.item {
        max-width: none;
    }
    ul.lb-box-grid li.item:not(:last-child) {
        margin-bottom: 10px;
    }
}

{/literal}
</style>

<!-- listing box grid-view box styles end -->
{if preg_match('/(_flatty|_modern|_modern_wide|escort.*wide)$/', $tpl_settings.name)}
    <!-- listing box grid-view box styles | flatty fallback -->
    <style>
    {literal}

    ul.lb-box-grid li.item ul {
        padding: 0!important;
        text-align: left;
    }
    body[dir=rtl] ul.lb-box-grid li.item ul {
        text-align: right;
    }
    ul.lb-box-grid li.item .photo img {
        background-size: cover;
        background-position: center;
        background-color: rgba(0,0,0,.025);
        background-image: url('{/literal}{$rlTplBase}{literal}img/no-picture.png');
    }

    {/literal}
    </style>
    <!-- listing box grid-view box styles | flatty fallback end -->
{elseif $tpl_settings.name == 'boats_seaman_wide'}
    <!-- boats seaman fallback -->
    <style>
    {literal}

    ul.lb-box-grid li.item ul > li.title > a {
        color: inherit;
    }

    {/literal}
    </style>
    <!-- boats seaman fallback end -->
{else $tpl_settings.name|strpos:'_nova'}
    <!-- listing box grid-view box styles | nova fallback -->
    <style>
    {literal}

    ul.featured.lb-box-grid > li > ul {
        border: 0px;
        border-radius: 0;
    }

    {/literal}
    </style>
{/if}

{if  $tpl_settings.name|strpos:'escort_'}
    <!-- escort wide fallback -->
    <style>
    {literal}

    ul.lb-box-grid > li > ul > li a {
        font-size: 1.071em!important;
    }

    {/literal}
    </style>
    <!-- escort wide fallback end -->
{/if}
{if $tpl_settings.name == 'general_flatty' && $pageInfo.Controller == 'listing_details'}
    <!-- general flatty fallback -->
    <style>
    {literal}

    @media screen and (max-width: 991px) and (min-width: 768px) {
        ul.lb-box-grid > li {
            margin-bottom: 10px;
            padding: 0!important;
        }
        ul.lb-box-grid > li:last-child {
            margin-bottom: 0;
        }
    }

    {/literal}
    </style>
    <!-- general flatty fallback end -->
{/if}
