<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: NEWS.INC.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

if ($config['mod_rewrite']) {
    $path = $rlValid->xSql($_GET['nvar_1']);

    // trailing number mode
    if ($_GET['listing_id']) {
        $path .= '-' . $_GET['listing_id'];
    }
    $article_id = $rlDb->getOne('ID', "`Path` = '{$path}'", 'news');
} else {
    $article_id = (int) $_GET['id'];
}

$page_info['current'] = (int) $_GET['pg'];

$reefless->loadClass('News');

if ($article_id) {
    $article = $rlNews->get($article_id, true);
    $rlSmarty->assign_by_ref('article', $article);

    $page_info['meta_description'] = $article['meta_description'];
    $page_info['meta_keywords']    = $article['meta_keywords'];
    $page_info['title']            = $article['title'];
    $page_info['h1']               = $article['title'];

    $bread_crumbs[] = array(
        'title' => $page_info['title']
    );

    // build link to return to news list
    $back_link = $reefless->getPageUrl('news');

    if ($_SESSION['news_last_viewed_page'] >= 2) {
        if ($config['mod_rewrite']) {
            $back_link = str_replace(
                '.html',
                '/index' . $_SESSION['news_last_viewed_page'] . '.html',
                $back_link
            );
        } else {
            $back_link .= '&pg=' . $_SESSION['news_last_viewed_page'];
        }
    }

    $rlSmarty->assign_by_ref('back_link', $back_link);

    /**
     * @since 4.7.1 - Added $back_link parameter
     * @since 4.6.0 - $article
     */
    $rlHook->load('newsItem', $article, $back_link);
} else {
    $_SESSION['news_last_viewed_page'] = (int) $page_info['current'];

    $news = $rlNews->get(false, true, $page_info['current']);
    $rlSmarty->assign_by_ref('news', $news);

    // Redirect to first page when no news on the page
    if ($page_info['current'] && !$news) {
        Flynax\Utils\Util::redirect($reefless->getPageUrl('news'));
    }

    $page_info['calc'] = $rlNews->calc_news;

    $rlHook->load('newsList');

    // build rss
    $rss = array(
        'item'  => 'news',
        'title' => $lang['pages+name+' . $pages['news']],
    );
    $rlSmarty->assign_by_ref('rss', $rss);
}
