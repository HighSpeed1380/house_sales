<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLSOCIALMETADATA.CLASS.PHP
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

class rlSocialMetaData extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * @hook  boot
     * @since 1.0.3
     */
    public function hookBoot()
    {
        global $page_info, $listing_data, $config, $lang, $rlSmarty, $photos, $account, $article;

        if ($config['smd_logo'] && file_exists(RL_PLUGINS . "socialMetaData/{$config['smd_logo']}")) {
            $image = RL_PLUGINS_URL . "socialMetaData/{$config['smd_logo']}";
        } else {
            $image = '';
        }

        // collect meta data by page
        switch ($page_info['Controller']) {
            case 'listing_details':
                // Add price to meta data
                $priceTag = $config['price_tag_field'] ?: null;

                if ($listing_data[$priceTag]) {
                    $price = explode('|', $listing_data[$priceTag]);
                    $price = [
                        'currency_code' => $price[1],
                        'currency'      => $price[1] ? $lang['data_formats+name+' . $price[1]] : '',
                        'value'         => $GLOBALS['rlValid']->str2money($price[0]),
                        'og_value'      => $price[0] . '.00',
                    ];
                    $rlSmarty->assign('smd_price', $price);
                }

                // add second field of product to meta data
                if ($short_info = $GLOBALS['rlListings']->getShortDetails($listing_data['ID'])) {
                    $count_fields = 1;

                    foreach ($short_info['fields'] as $key => $field) {
                        if ($count_fields >= 2 && $key != $priceTag) {
                            $smd_second_field['key']   = $field['name'];
                            $smd_second_field['value'] = $field['value'];
                            break;
                        }

                        $count_fields++;
                    }

                    if ($smd_second_field['key'] && $smd_second_field['value']) {
                        $rlSmarty->assign('smd_second_field', $smd_second_field);
                    }
                }

                // legacy photo data (for < 4.6.0 version)
                $photos = $photos ?: $GLOBALS['media'];

                // add large main photo of listing
                if ($listing_data['Main_photo'] && is_array($photos[0])) {
                    $image = $photos[0]['Photo'];
                }

                // add default meta description if it not exist
                if (!$page_info['meta_description'] && $lang['pages+meta_description+view_details']) {
                    $page_info['meta_description'] = $lang['pages+meta_description+view_details'];
                }
                break;

            case 'account_type':
                if (!empty($account['Photo']) && file_exists(RL_FILES . $account['Photo'])) {
                    $image = RL_FILES_URL . $account['Photo'];
                }
                break;

            /*
             * @since 1.2.6
             */
            case 'news':
                if ($article) {
                    preg_match('~<img.*?src=["\']+(.*?)["\']+~', $article['content'], $urls);

                    if ($urls[1] && file_exists(str_replace(RL_FILES_URL, RL_FILES, $urls[1]))) {
                        $image = $urls[1];
                    }
                }
                break;
        }

        // get image properties for Facebook crawler
        if ($image) {
            $rlSmarty->assign('smd_logo', $image);
            $image     = str_replace([RL_FILES_URL, RL_PLUGINS_URL], [RL_FILES, RL_PLUGINS], $image);
            $imageData = getimagesize($image);

            if ($imageData[0] && $imageData[1] && $imageData['mime']) {
                $imageData = ['width' => $imageData[0], 'height' => $imageData[1], 'mime' => $imageData['mime']];
                $rlSmarty->assign('smd_logo_properties', $imageData);
            }
        }
    }

    /**
     * @hook  tplHeaderCommon
     * @since 1.0.3
     */
    public function hookTplHeaderCommon()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'socialMetaData' . RL_DS . 'social_meta_data.tpl');
    }

    /**
     * Update process of the plugin (copy from core)
     * @todo - Remove this method when compatibility will be >= 4.6.2
     * @param string $version
     */
    public function update($version)
    {
        $version_method = 'update' . (int) str_replace('.', '', $version);
        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }

    /**
     * Update to 1.2.2 version
     */
    public function update122()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}config`
            WHERE `Key` IN('smd_google_name','smd_price_key') AND `Plugin` = 'socialMetaData'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Key` IN('config+name+smd_google_name','config+name+smd_price_key') AND `Plugin` = 'socialMetaData'"
        );
    }

    /**
     * Update to 1.2.6 version
     */
    public function update126()
    {
        if (in_array('ru', array_keys($GLOBALS['languages']))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'socialMetaData/i18n/ru.json'), true);
            foreach (['config+des+smd_logo', 'config+des+smd_fb_appid', 'config+des+smd_fb_admins'] as $phraseKey) {
                $GLOBALS['rlDb']->updateOne([
                    'fields' => ['Value' => $russianTranslation[$phraseKey]],
                    'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
                ], 'lang_keys');
            }
        }
    }

    /**
     * Install process
     *
     * @since 1.2.3
     * @todo        - Remove when compatible will be >= 4.7.0
     */
    public function install()
    {}

    /**
     * Uninstall process
     *
     * @since 1.2.3
     * @todo        - Remove when compatible will be >= 4.7.0
     */
    public function uninstall()
    {}
}
