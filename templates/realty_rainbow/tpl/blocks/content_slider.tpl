<!-- content slider -->

{addJS file=$rlTplBase|cat:'components/content-slider/util.js'}
{addJS file=$rlTplBase|cat:'components/content-slider/carousel.js'}

<div id="homeCarousel" class="carousel slide carousel-fade" data-ride="carousel">
    <ol class="carousel-indicators">
        {if $home_slides|@count > 1}
            {foreach from=$home_slides item='slide' name='slides'}
            <li data-target="#homeCarousel" 
                data-slide-to="{$smarty.foreach.slides.index}"
                {if $smarty.foreach.slides.first}
                class="active"
                {/if}></li>
            {/foreach}
        {/if}
    </ol>
    <div class="carousel-inner h-100">
        {foreach from=$home_slides item='slide' name='slides'}
            <div class="carousel-item{if $smarty.foreach.slides.first} active{/if}">
                <img class="carousel-picture d-block w-100 h-100"
                     src="{$smarty.const.RL_FILES_URL}slides/{$slide.Picture}"
                     alt="{$slide.title}">

                <div class="carousel-caption">
                    <h2 class="carousel-slide-heading">{$slide.title}</h2>
                    <p class="carousel-slide-description">{$slide.description}</p>

                    {if $slide.URL}
                    <a class="mt-md-4 button" href="{$slide.URL}">{$lang.view_details}</a>
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>
    {if $home_slides|@count > 1}
    <a class="carousel-control-prev" href="#homeCarousel" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    </a>
    <a class="carousel-control-next" href="#homeCarousel" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
    </a>
    {/if}
</div>

<!-- content slider end -->
