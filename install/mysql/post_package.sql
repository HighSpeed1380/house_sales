/** ipGeo Plugin **/
INSERT INTO `{db_prefix}config` (`Group_ID`, `Position`, `Key`, `Default`, `Values`, `Type`, `Data_type`, `Plugin`) VALUES
(19, 0, 'ipgeo_default_country', 'US', '', 'select', '', 'ipgeo'),
(0, 0, 'ipgeo_database_version', '', '', 'text', 'varchar', 'ipgeo');

INSERT INTO `{db_prefix}config_groups` (`ID`, `Key`, `Position`, `Plugin`) VALUES
(19, 'ipgeo', 19, 'ipgeo');

INSERT INTO `{db_prefix}hooks` (`Name`, `Class`, `Plugin`, `Code`, `Status`) VALUES
('init', 'IPGeo', 'ipgeo', '', 'active'),
('apMixConfigItem', 'IPGeo', 'ipgeo', '', 'active'),
('apNotifications', 'IPGeo', 'ipgeo', '', 'active'),
('apAjaxRequest', 'IPGeo', 'ipgeo', '', 'active');

INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`) VALUES
('en', 'admin', '0', 'ipgeo_preparing', 'Preparing to download', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'admin', '1', 'ipgeo_file_download_info', 'Downloading {file} files of {files}', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'admin', '1', 'ipgeo_import_completed', 'Importing has been successfully completed', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'admin', '1', 'ipgeo_db_uptodate', 'The database you run is up to date', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'system', '0', 'ipgeo_update_notice', 'You should install/update the IP database from the [IP Geo Location] Manager that you can find in the <b>Plugins</b> section.', '', '0', 'ipgeo', 'active'),
('en', 'admin', '0', 'ipgeo_remote_install_text', 'The current IP database of the Geo plugin needs to be updated.<br>An update will be downloaded from the Flynax Server, which may take a few minutes. <br>Click the <b>Install</b> button and stay on the page until the process is over.', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'admin', '0', 'ipgeo_remote_update_text', 'You may check for the IP database updates by clicking the <b>Update</b> button.<br>The process may take a few minutes, please stay on the page until the process is over.', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'admin', '0', 'ipgeo_remote_update_status', '{percent}% completed...', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'admin', '1', 'ipgeo_too_many_failed_requests', 'You have exceeded the limit for bad requests to the server; please try again later or contact Flynax Support.', 'ipgeo', '0', 'ipgeo', 'active'),
('en', 'admin', '', 'title_ipgeo', 'IP Geo Location', '', '0', 'ipgeo', 'active'),
('en', 'admin', '', 'description_ipgeo', 'Detects user location by IPs', 'plugins', '0', 'ipgeo', 'active'),
('en', 'admin', '0', 'config_groups+name+ipgeo', 'IP Geo Location', 'settings', '0', 'ipgeo', 'active'),
('en', 'admin', '0', 'config+name+ipgeo_default_country', 'Default country', 'settings', '0', 'ipgeo', 'active'),
('en', 'admin', '0', 'config+des+ipgeo_default_country', 'Default country in case of IP location failure', 'settings', '0', 'ipgeo', 'active');

INSERT INTO `{db_prefix}plugins` (`Key`, `Class`, `Name`, `Description`, `Version`, `Controller`, `Fcontroller`, `Files`, `Uninstall`, `Status`, `Install`) VALUES
('ipgeo', 'IPGeo', 'IP Geo Location', 'Detects user location by IPs', '2.0.0', 'ipgeo', '', 'a:7:{i:0;s:15:\"admin/.htaccess\";i:1;s:19:\"admin/ipgeo.inc.php\";i:2;s:15:\"admin/ipgeo.tpl\";i:3;s:12:\"i18n/ru.json\";i:4;s:19:\"vendor/autoload.php\";i:5;s:9:\".htaccess\";i:6;s:17:\"rlIPGeo.class.php\";}', '', 'active', '1');
/** ipGeo Plugin end **/

/** locationFinder Plugin **/
INSERT INTO `{db_prefix}config` (`Group_ID`, `Position`, `Key`, `Default`, `Values`, `Type`, `Data_type`, `Plugin`) VALUES
(20, 0, 'locationFinder_search_divider', '', '', 'divider', '', 'locationFinder'),
(20, 1, 'locationFinder_search', 'San Francisco, CA, USA', '', 'text', '', 'locationFinder'),
(20, 2, 'locationFinder_use_location', '1', '', 'bool', '', 'locationFinder'),
(20, 3, 'locationFinder_map_divider', '', '', 'divider', '', 'locationFinder'),
(20, 4, 'locationFinder_map_zoom', '13', '', 'select', '', 'locationFinder'),
(20, 5, 'locationFinder_position', 'in_group', '', 'select', '', 'locationFinder'),
(20, 6, 'locationFinder_group', 'location', '', 'select', '', 'locationFinder'),
(20, 7, 'locationFinder_type', 'prepend', '', 'radio', '', 'locationFinder'),
(20, 8, 'locationFinder_mapping_divider', '', '', 'divider', '', 'locationFinder'),
(20, 9, 'locationFinder_mapping', '0', '', 'bool', '', 'locationFinder'),
(20, 10, 'locationFinder_use_neighborhood', '1', '', 'bool', '', 'locationFinder'),
(20, 11, 'locationFinder_mapping_country', 'country', '', 'select', '', 'locationFinder'),
(20, 12, 'locationFinder_mapping_state', 'country_level1', '', 'select', '', 'locationFinder'),
(20, 13, 'locationFinder_mapping_city', 'country_level2', '', 'select', '', 'locationFinder'),
(0, 0, 'locationFinder_db_version', '2.0', '', 'text', 'varchar', 'locationFinder'),
(0, 0, 'locationFinder_default_location', '37.7577627,-122.4726194', '', 'text', 'varchar', 'locationFinder');

INSERT INTO `{db_prefix}config_groups` (`ID`, `Key`, `Position`, `Plugin`) VALUES
(20, 'locationFinder', 20, 'locationFinder');

INSERT INTO `{db_prefix}hooks` (`Name`, `Class`, `Plugin`, `Code`, `Status`) VALUES
('addListingPreFields', 'LocationFinder', 'locationFinder', '', 'active'),
('editListingPreFields', 'LocationFinder', 'locationFinder', '', 'active'),
('afterListingEdit', 'LocationFinder', 'locationFinder', '', 'active'),
('afterListingCreate', 'LocationFinder', 'locationFinder', '', 'active'),
('afterListingUpdate', 'LocationFinder', 'locationFinder', '', 'active'),
('addListingPostSimulation', 'LocationFinder', 'locationFinder', '', 'active'),
('editListingPostSimulation', 'LocationFinder', 'locationFinder', '', 'active'),
('listingDetailsBottom', 'LocationFinder', 'locationFinder', '', 'active'),
('ajaxRequest', 'LocationFinder', 'locationFinder', '', 'active'),
('apPhpListingsView', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apTplListingsFormEdit', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apTplListingsFormAdd', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpListingsPost', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpListingsAfterEdit', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpListingsAfterAdd', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apMixConfigItem', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apTplContentBottom', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apTplHeader', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apAjaxRequest', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apTplListingTypesForm', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpListingTypesPost', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpListingTypesBeforeAdd', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpListingTypesBeforeEdit', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpConfigBeforeUpdate', 'LocationFinderAP', 'locationFinder', '', 'active'),
('apPhpConfigBottom', 'LocationFinderAP', 'locationFinder', '', 'active');

INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`) VALUES
('en', 'common', '0', 'locationFinder_fieldset_caption', 'Location on map', '', '0', 'locationFinder', 'active'),
('en', 'common', '0', 'locationFinder_location', 'Location', '', '0', 'locationFinder', 'active'),
('en', 'common', '0', 'locationFinder_drag_notice', 'Drag and drop the marker or double click on the map to move the marker to the desired location.', '', '0', 'locationFinder', 'active'),
('en', 'common', '0', 'locationFinder_hint', 'If you want to show more accurate coordinates on the map you can enter Country, State/Region, City and Address separated by commas, do a search and then drag the marker to the correct location on the map afterwards.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_form_top', 'Top of form', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_form_bottom', 'Bottom of form', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_place_in_form', 'Place in fields group', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_prepend', 'Above all fields', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_append', 'Below all fields', '', '0', 'locationFinder', 'active'),
('en', 'common', '0', 'locationFinder_address_hint', 'Search Address', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_mapping_manager', 'Location Mapping Manager', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_mapping_no_fields_mapping', 'No location-related fields have been found, please set the fields mapping [here].', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_mapping_mf_inactive_error', 'The \"Multifield/Location Filter\" plugin should be installed and activated, go to [Plugin Manager] and install/activate the Plugin.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_mapping_table_error', 'No mapping table has been found in the database, click [here] to create the required table.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '1', 'locationFinder_js_location_not_selected_error', 'Please select a location from the dropdown first', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_default_map_hint', 'Please select a location from the dropdown to change it\'s mapping', '', '0', 'locationFinder', 'active'),
('en', 'admin', '1', 'locationFinder_mapping_saved', 'Location mapping data were successfully updated', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_update_database', 'Update Database', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_import_update_database', 'Import/Update Database', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_incompatible_database_error', 'The location database you use is incompatible with the latest geo mapping database we offer.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_database_structure_created', 'A missing table in the database has been successfully created.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_remote_update_status', '{percent}% completed...', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_remote_install_text', 'Update files will be downloaded from the Flynax Server and imported to your site local database.<br />Updating may take a few minutes; click the <b>Install</b> button and stay on the page until the process is over.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_remote_update_text', 'You may check for an update of the Geo Mapping database by clicking the <b>Update</b> button; the process may take a few minutes. Please stay on the page until importing is over.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_remote_update_notice', 'Do not update the database if you modified mapping in the previous step; all current mapping data will be lost during the import.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_preparing', 'Preparing for upload', '', '0', 'locationFinder', 'active'),
('en', 'admin', '1', 'locationFinder_file_download_info', 'Downloading {file} of {files} files', '', '0', 'locationFinder', 'active'),
('en', 'admin', '1', 'locationFinder_file_upload_info', 'Uploading {file} of {files} files', '', '0', 'locationFinder', 'active'),
('en', 'admin', '1', 'locationFinder_import_completed', 'Importing has been successfully completed', '', '0', 'locationFinder', 'active'),
('en', 'admin', '1', 'locationFinder_db_uptodate', 'Your database version is up to date', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_option_name', 'Location Finder', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_mapping_table_mismatch', 'The current database version is out of date and needs to be updated to 2.0.<br />Click \"Import\" to download a new database (a global location database), or \"Update Structure\" of the database if you use your own locations.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_update_structure', 'Update Structure', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_database_structure_updated', 'The database table structure has been successfully updated.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_no_geocoder_location', 'Unable to find the location. Please try looking for \"{location}\" in the search field on the map.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'locationFinder_geocoding_mismatch', 'Location synchronization is available If Google is selected as the geocoding provider.', '', '0', 'locationFinder', 'active'),
('en', 'admin', '', 'title_locationFinder', 'Location Finder', '', '0', 'locationFinder', 'active'),
('en', 'admin', '', 'description_locationFinder', 'Identifies location of users and allows them to show accurate addresses on the map', 'plugins', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config_groups+name+locationFinder', 'Location Finder', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_search_divider', 'Search settings', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_search', 'Default location', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_use_location', 'Use visitor location', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+des+locationFinder_use_location', 'Disable this option to use default location. If Enabled the ssl protection for Add Listing page might be required.', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_map_divider', 'Map settings', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_map_zoom', 'Default map zoom', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_position', 'Map position', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_group', 'Place map in group', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_type', 'Position in group', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_mapping_divider', 'Geo mapping', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_mapping', 'Use location mapping', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+des+locationFinder_mapping', 'Synchronizes map location with values of location-related fields. IMPORTANT: Synchronization of the maps with locations (offered by Multifield location database) is available with Google geocoding only.', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_use_neighborhood', 'Split into neighborhoods', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+des+locationFinder_use_neighborhood', 'Split metropolises into neighborhoods and smaller areas, if possible.', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_mapping_country', 'Country field', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_mapping_state', 'State field', 'settings', '0', 'locationFinder', 'active'),
('en', 'admin', '0', 'config+name+locationFinder_mapping_city', 'City field', 'settings', '0', 'locationFinder', 'active');

INSERT INTO `{db_prefix}plugins` (`Key`, `Class`, `Name`, `Description`, `Version`, `Controller`, `Fcontroller`, `Files`, `Uninstall`, `Status`, `Install`) VALUES
('locationFinder', 'LocationFinder', 'Location Finder', 'Identifies location of users and allows them to show accurate addresses on the map', '5.0.2', 'locationFinder', '', 'a:11:{i:0;s:7:\"map.tpl\";i:1;s:26:\"rlLocationFinder.class.php\";i:2;s:28:\"rlLocationFinderAP.class.php\";i:3;s:13:\"static/lib.js\";i:4;s:18:\"static/apStyle.css\";i:5;s:13:\"admin/row.tpl\";i:6;s:13:\"admin/map.tpl\";i:7;s:12:\"admin/js.tpl\";i:8;s:28:\"admin/locationFinder.inc.php\";i:9;s:24:\"admin/locationFinder.tpl\";i:10;s:12:\"i18n/ru.json\";}', '\n        $GLOBALS[\'reefless\']->loadClass(\'LocationFinder\', false, \'locationFinder\');\n        $GLOBALS[\'rlLocationFinder\']->uninstall();\n    ', 'active', '1');

ALTER TABLE `{db_prefix}listing_types` ADD `Location_finder` ENUM('0', '1') NOT NULL DEFAULT '1' AFTER `Status`;

ALTER TABLE `{db_prefix}listings` ADD `lf_zoom` INT(2) NOT NULL;
/** locationFinder Plugin end **/

/** listings_box Plugin **/
INSERT INTO `{db_prefix}blocks` (`Page_ID`, `Category_ID`, `Subcategories`, `Sticky`, `Cat_sticky`, `Key`, `Position`, `Side`, `Type`, `Content`, `Tpl`, `Header`, `Plugin`, `Status`, `Readonly`) VALUES
('1', '', '0', '0', '0', 'listing_box_1', 1, 'bottom', 'php', '\n                global $rlSmarty;\n                $GLOBALS[\"reefless\"]->loadClass(\"ListingsBox\", null, \"listings_box\");\n                $listings_box = $GLOBALS[\"rlListingsBox\"] -> getListings( \"{listing_types_set}\", \"recently_added\", \"6\", \"1\", \"0\" );\n                $rlSmarty->assign_by_ref(\"listings_box\", $listings_box);\n                $rlSmarty->assign(\"type\", \"{listing_types_set}\");$box_option[\'display_mode\'] = \"default\";$rlSmarty->assign(\"box_option\", $box_option);\n                $rlSmarty->display(RL_PLUGINS . \"listings_box\" . RL_DS . \"listings_box.block.tpl\");\n            ', '0', '1', 'listings_box', 'active', '1');

DROP TABLE IF EXISTS `{db_prefix}listing_box`;
CREATE TABLE `{db_prefix}listing_box` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Type` varchar(255) NOT NULL,
  `Box_type` enum('top_rating','popular','recently_added','random','featured') NOT NULL DEFAULT 'recently_added',
  `Count` varchar(10) NOT NULL,
  `Unique` enum('1','0') NOT NULL DEFAULT '0',
  `By_category` enum('1','0') NOT NULL DEFAULT '0',
  `Display_mode` enum('default','grid') NOT NULL DEFAULT 'default',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `{db_prefix}listing_box` (`ID`, `Type`, `Box_type`, `Count`, `Unique`, `By_category`, `Display_mode`) VALUES
(1, '{listing_types_set}', 'recently_added', '6', '1', '0', 'default');

INSERT INTO `{db_prefix}hooks` (`Name`, `Class`, `Plugin`, `Code`, `Status`) VALUES
('apAjaxRequest', 'ListingsBox', 'listings_box', '', 'active'),
('tplHeader', 'ListingsBox', 'listings_box', '', 'active'),
('apPhpBlocksPost', 'ListingsBox', 'listings_box', '', 'active');

INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`) VALUES
('en', 'admin', '1', 'listings_box_ext_box_type', 'Box Type', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_number_of_listing', 'Number of listings', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_add_new_block', 'Add a box', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_block_list', 'All Boxes', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_top_rating', 'Top Rated', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_popular', 'Popular', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_recently_added', 'Recently Added', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_random', 'Random', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_more_listings', 'The number of listings should not exceed 30', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_dublicate', 'Prevent duplicate listings in other boxes', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_display_mode', 'Appearance', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_default', 'Default', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_grid', 'Small Thumb Grid', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_featured', 'Featured', '', '0', 'listings_box', 'active'),
('en', 'admin', '0', 'listings_box_by_category', 'Filter by category', '', '0', 'listings_box', 'active'),
('en', 'admin', '', 'title_listings_box', 'Listing Boxes', '', '0', 'listings_box', 'active'),
('en', 'admin', '', 'description_listings_box', 'The Plugin generates boxes for random, featured, popular and new listings in the front end', 'plugins', '0', 'listings_box', 'active'),
('en', 'common', '0', 'blocks+name+listing_box_1', 'Recently Added', '', '0', 'listings_box', 'active');

INSERT INTO `{db_prefix}plugins` (`Key`, `Class`, `Name`, `Description`, `Version`, `Controller`, `Fcontroller`, `Files`, `Uninstall`, `Status`, `Install`) VALUES
('listings_box', 'ListingsBox', 'Listing Boxes', 'The Plugin generates boxes for random, featured, popular and new listings in the front end', '3.0.6', 'listings_box', '', 'a:7:{i:0;s:23:\"rlListingsBox.class.php\";i:1;s:22:\"listings_box.block.tpl\";i:2;s:21:\"listings_box.grid.tpl\";i:3;s:10:\"header.tpl\";i:4;s:22:\"admin/listings_box.tpl\";i:5;s:26:\"admin/listings_box.inc.php\";i:6;s:12:\"i18n/ru.json\";}', '\r\n        $GLOBALS[\'reefless\']->loadClass(\'ListingsBox\', null, \'listings_box\');\r\n        $GLOBALS[\'rlListingsBox\']->uninstall();\r\n    ', 'active', '1');
/** listings_box Plugin end **/

/** multiField Plugin **/
DROP TABLE IF EXISTS `{db_prefix}multi_formats`;
CREATE TABLE `{db_prefix}multi_formats` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Parent_ID` int(11) NOT NULL,
  `Parent_IDs` varchar(47) NOT NULL,
  `Position` int(5) NOT NULL DEFAULT '0',
  `Levels` int(11) DEFAULT '0',
  `Key` varchar(100) NOT NULL DEFAULT '',
  `Default` enum('0','1') NOT NULL DEFAULT '0',
  `Geo_filter` enum('0','1') DEFAULT '0',
  `Status` enum('active','approval') NOT NULL DEFAULT 'active',
  `Path` varchar(255) NOT NULL,
  `Latitude` double NOT NULL,
  `Longitude` double NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Parent_ID` (`Parent_ID`),
  KEY `Status` (`Status`),
  KEY `Key` (`Key`),
  KEY `Path` (`Path`),
  KEY `Group_index` (`Parent_ID`,`Key`,`Status`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `{db_prefix}multi_formats_lang_en`;
CREATE TABLE `{db_prefix}multi_formats_lang_en` (
  `Key` varchar(100) NOT NULL,
  `Value` varchar(32) NOT NULL,
  KEY `Key` (`Key`),
  KEY `Value` (`Value`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `{db_prefix}config_groups` (`ID`, `Key`, `Position`, `Plugin`) VALUES
(18, 'geo_filter_config', 18, 'multiField');

INSERT INTO `{db_prefix}config` (`Group_ID`, `Position`, `Key`, `Default`, `Values`, `Type`, `Data_type`, `Plugin`) VALUES
(18, 0, 'mf_geo_autodetect', '1', '', 'bool', '', 'multiField'),
(18, 1, 'mf_geofilter_expiration', '90', '', 'text', '', 'multiField'),
(18, 2, 'mf_show_nearby_listings', '0', '', 'bool', '', 'multiField'),
(18, 3, 'mf_nearby_distance', '150', '', 'text', 'int', 'multiField'),
(18, 4, 'mf_select_interface', 'usernavbar', 'box,usernavbar', 'select', '', 'multiField'),
(18, 5, 'mf_autocomplete_divider', '', '', 'divider', '', 'multiField'),
(18, 6, 'mf_geo_block_autocomplete', '1', '', 'bool', '', 'multiField'),
(18, 7, 'mf_geo_autocomplete_limit', '10', '', 'text', 'int', 'multiField'),
(18, 8, 'mf_popular_locations_level', 'country_level2', 'country_level2', 'select', '', 'multiField'),
(18, 9, 'mf_seo_divider', '', '', 'divider', '', 'multiField'),
(18, 10, 'mf_geo_subdomains_type', 'combined', 'mixed,combined,unique', 'select', '', 'multiField'),
(18, 11, 'mf_geo_subdomains', '0', '', 'bool', '', 'multiField'),
(18, 12, 'mf_listing_geo_urls', '0', '', 'bool', '', 'multiField'),
(18, 13, 'mf_multilingual_path', '0', '', 'bool', '', 'multiField'),
(18, 14, 'mf_account_page_filtration', 'none', 'none,filter,url', 'select', '', 'multiField'),
(18, 15, 'mf_urls_in_sitemap', '', 'all,not_empty', 'select', '', 'multiField'),
(18, 16, 'mf_home_in_sitemap', '0', '', 'bool', '', 'multiField'),
(18, 17, 'mf_filtering_divider', '', '', 'divider', '', 'multiField'),
(0, 0, 'mf_db_version', 'locations6', '', 'text', 'varchar', 'multiField'),
(0, 0, 'mf_filtering_pages', '', '', 'text', 'varchar', 'multiField'),
(0, 0, 'mf_location_url_pages', '', '', 'text', 'varchar', 'multiField'),
(0, 0, 'mf_geo_data_format', '{\"ID\":\"1\",\"Order_type\":\"alphabetic\",\"Levels\":\"3\",\"Key\":\"countries\"}', '', 'text', 'varchar', 'multiField'),
(0, 0, 'mf_format_keys', 'countries', '', 'text', 'varchar', 'multiField'),
(0, 0, 'cache_multi_formats', '', '', 'text', 'varchar', 'multiField');

INSERT INTO `{db_prefix}hooks` (`Name`, `Class`, `Plugin`, `Code`, `Status`) VALUES
('apMixConfigItem', 'MultiFieldAP', 'multiField', '', 'active'),
('listingDetailsBeforeMetaData', 'GeoFilter', 'multiField', '', 'active'),
('apPhpCategoriesBottom', 'MultiFieldAP', 'multiField', '', 'active'),
('phpGetProfileModifyField', 'GeoFilter', 'multiField', '', 'active'),
('pageinfoArea', 'GeoFilter', 'multiField', '', 'active'),
('phpSearchOnMapDefaultAddress', 'GeoFilter', 'multiField', '', 'active'),
('apPhpAccountsValidate', 'GeoFilter', 'multiField', '', 'active'),
('phpValidateUserLocation', 'GeoFilter', 'multiField', '', 'active'),
('apPhpConfigBottom', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpConfigBeforeUpdate', 'MultiFieldAP', 'multiField', '', 'active'),
('apAjaxRequest', 'MultiFieldAP', 'multiField', '', 'active'),
('phpRecentlyAddedModifyPreSelect', 'GeoFilter', 'multiField', '', 'active'),
('apAjaxLangExportSelectPhrases', 'MultiFieldAP', 'multiField', '', 'active'),
('phpOriginalUrlRedirect', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyPreSelect', 'GeoFilter', 'multiField', '', 'active'),
('phpMetaTags', 'GeoFilter', 'multiField', '', 'active'),
('phpUrlBottom', 'GeoFilter', 'multiField', '', 'active'),
('phpBeforeLoginValidation', 'GeoFilter', 'multiField', '', 'active'),
('ajaxRequest', 'GeoFilter', 'multiField', '', 'active'),
('specialBlock', 'GeoFilter', 'multiField', '', 'active'),
('accountsGetDealersByCharSqlWhere', 'GeoFilter', 'multiField', '', 'active'),
('boot', 'GeoFilter', 'multiField', '', 'active'),
('smartyFetchHook', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyWhereFeatured', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyWhereByPeriod', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyWhereByAccount', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyWhere', 'GeoFilter', 'multiField', '', 'active'),
('phpCategoriesGetCategories', 'GeoFilter', 'multiField', '', 'active'),
('phpCategoriesGetCategoriesCache', 'GeoFilter', 'multiField', '', 'active'),
('init', 'GeoFilter', 'multiField', '', 'active'),
('apTplControlsForm', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpAccountFieldsAfterAdd', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpListingFieldsAfterAdd', 'MultiFieldAP', 'multiField', '', 'active'),
('apTplListingFieldSelect', 'MultiFieldAP', 'multiField', '', 'active'),
('apTplAccountFieldSelect', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpSubmitProfileEnd', 'MultiFieldAP', 'multiField', '', 'active'),
('apTplHeader', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpListingsTop', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpAccountsTop', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpFormatsAjaxDeleteFormatPreDelete', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpDataFormatsBottom', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpFieldsAjaxDeleteField', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpListingFieldsTop', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpFieldsAjaxDeleteAField', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpAccountFieldsBeforeEdit', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpListingFieldsBeforeEdit', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpAccountFieldsTop', 'MultiFieldAP', 'multiField', '', 'active'),
('apTplFieldsFormBottom', 'MultiFieldAP', 'multiField', '', 'active'),
('apTplFooter', 'MultiFieldAP', 'multiField', '', 'active'),
('addListingPreFields', 'MultiField', 'multiField', '', 'active'),
('tplProfileFieldSelect', 'MultiField', 'multiField', '', 'active'),
('tplRegFieldSelect', 'MultiField', 'multiField', '', 'active'),
('tplSearchFieldSelect', 'MultiField', 'multiField', '', 'active'),
('tplListingFieldSelect', 'MultiField', 'multiField', '', 'active'),
('adaptValueBottom', 'MultiField', 'multiField', '', 'active'),
('pageinfoArea', 'MultiField', 'multiField', '', 'active'),
('staticDataRegister', 'MultiField', 'multiField', '', 'active'),
('tplFooter', 'MultiField', 'multiField', '', 'active'),
('tplHeader', 'MultiField', 'multiField', '', 'active'),
('ajaxRequest', 'MultiField', 'multiField', '', 'active'),
('apPhpFormatsAjaxDeleteFormat', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpConfigAfterUpdate', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpLanguageAfterImport', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpAfterAddLanguage', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpAfterDeleteLanguage', 'MultiFieldAP', 'multiField', '', 'active'),
('phpCommonFieldValuesAdaptationTop', 'MultiField', 'multiField', '', 'active'),
('phpCommonFieldValuesAdaptationBottom', 'MultiField', 'multiField', '', 'active'),
('apPhpDataFormatsAfterEdit', 'MultiFieldAP', 'multiField', '', 'active'),
('apExtDataFormatsUpdate', 'MultiFieldAP', 'multiField', '', 'active'),
('phpAccountAddressAssign', 'MultiField', 'multiField', '', 'active'),
('apPhpPagesValidate', 'MultiFieldAP', 'multiField', '', 'active'),
('apPhpCategoriesDataValidate', 'MultiFieldAP', 'multiField', '', 'active'),
('sitemapAddPluginUrls', 'GeoFilter', 'multiField', '', 'active'),
('phpCategoryGetDF', 'MultiField', 'multiField', '', 'active'),
('getPhrase', 'MultiField', 'multiField', '', 'active'),
('phpCacheUpdateDataFormats', 'MultiField', 'multiField', '', 'active'),
('phpCacheGetBeforeFetch', 'MultiField', 'multiField', '', 'active'),
('tplHeaderUserNav', 'GeoFilter', 'multiField', '', 'active'),
('phpListingTypeBrowseQuickSearchMode', 'GeoFilter', 'multiField', '', 'active'),
('accountTypeTop', 'GeoFilter', 'multiField', '', 'active'),
('phpGetPersonalAddressAfter', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyField', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyOrder', 'GeoFilter', 'multiField', '', 'active'),
('listingTop', 'GeoFilter', 'multiField', '', 'active'),
('listingAfterFields', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyFieldByPeriod', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyOrderByPeriod', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifySelectFeatured', 'GeoFilter', 'multiField', '', 'active'),
('listingsModifyOrderFeatured', 'GeoFilter', 'multiField', '', 'active'),
('utilsRedirectURL', 'GeoFilter', 'multiField', '', 'active');

INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`) VALUES
('en', 'admin', '1', 'ext_multi_formats_manager', 'Multileveled Data Entry Manager', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_edit_item', 'Edit Entry', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_add_item', 'Add an Entry', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_formats_list', 'All Entries', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_manage_items', 'Manage Items', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_order_type', 'Sorting order', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_name', 'Name', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_lf_created', 'The system has added a listing field to the level automatically. Click [here] to edit the field.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_af_created', 'The system has added a registration field to the level automatically. Click [here] to edit the field.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_related_listing_fields', 'Listing fields linked to this level', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_related_account_fields', 'Registration fields linked to this level', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_related_fields', 'Connected Fields', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_no_related_fields', 'There are no fields.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_flsource', 'Import Data', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_server_datalist', 'Data entries available on the Flynax server.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_all', 'Import the entire database', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_partially', 'Select items to be imported', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_choose_items_to_import', 'Select items to be imported', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_without_parent_hint', 'Check the box to import the parent item.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_without_parent_ignore', 'Include the parent entry', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_completed', 'The items have been successfully imported.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_geofilter', 'Geo filtering', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_path', 'Path', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_path_short', 'The path is too short.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_path_exists', 'The path is already in use; please consider using a different path.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_refresh', 'Refresh', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_geo_path_rebuilt', 'The paths have been successfully refreshed.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_fields_rebuilt', 'The fields have been successfully refreshed.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_refresh_in_progress', 'Processing, please wait...', '', '0', 'multiField', 'active'),
('en', 'admin', '1', 'ext_notice_delete_item', 'Are you sure you want to remove the item?', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_importing', 'Processing', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '0', 'mf_geo_type_location', 'Type your location here', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '0', 'mf_geo_box_default', 'Geo filtering is not configured.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_current', 'Current item', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_subprogress', 'Item progress', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import', 'Import', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_resume', 'Continue importing', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_rebuild', 'Refresh dependent fields (Multifield)', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_rebuild_path', 'Refresh paths (Multifield)', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_rebuild_no_format_configured', 'There are no multileveled data entries for refreshing.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_rebuild_no_fields_configured', 'There are no fields connected with multileveled data entries for refreshing.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_geo_denied_hint', 'Location filtering is not available for system pages.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_subdomains_prompt', 'The location structure on subdomains have been changed. Are you sure you want to refresh the location structure in URLs?', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_search_all_levels', 'Search in all data entries', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_apply_location_to_url', 'Add location to URL', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_preselect_data_hint', 'Note: The plugin only adds current/selected locations to location-related fields.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_rebuild_path_promt', 'Location URLs will be rebuilt with location names and your custom URLs will be overwritten.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_rebuild_path_in_progress', 'Refreshing location URLs in process; closing the page will stop the process.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_no_geo_filtering_format', 'There are no data entries connected with geo filtering. Changes in the settings will not have any effect.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_geo_location', 'Geolocation', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+option+mf_geo_subdomains_type_mixed', 'Mixed', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+option+mf_geo_subdomains_type_combined', 'Full', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+option+mf_geo_subdomains_type_unique', 'City only', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_geo_prefilling_group', 'Prefilling Locations in Fields on Pages', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_import_sync_phrases', 'Import translation', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_path_exists_in_mf', 'The \"{path}\" path is already in use for the location; please consider using a different path.', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+option+mf_urls_in_sitemap_all', 'All URLs', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+option+mf_urls_in_sitemap_not_empty', 'URLs with listings/accounts only', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_sitemap_dryrun_box_content', 'To make sure your server offers enough resources, the Plugin will try to rebuild the sitemap, do you confirm the action?', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_sitemap_rebuilding', 'The sitemap is being rebuilt...', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '1', 'mf_is_your_location', 'Is {location} your location?', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '0', 'mf_select_location', 'Select Location', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '0', 'mf_no_location_in_popover', 'We were unable to detect your location, do you want to select your city from the list?', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '0', 'mf_select_city_hint', 'Search for a city or select popular from the list', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+option+mf_select_interface_box', 'Box', '', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+option+mf_select_interface_usernavbar', 'User Navigation Bar in header', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '0', 'mf_reset_location', 'Reset Location', '', '0', 'multiField', 'active'),
('en', 'frontEnd', '0', 'mf_nearby_listings_hint', 'Similar listings within nearby locations', '', '0', 'multiField', 'active'),
('en', 'admin', '1', 'mf_geo_filter_field_restriction', 'You are unable to change the data entry of the field assigned to the geo filtration', 'listing_fields', '0', 'multiField', 'active'),
('en', 'admin', '1', 'mf_inactive_parent_status_hint', 'Parent item is inactive', 'multi_formats', '0', 'multiField', 'active'),
('en', 'admin', '1', 'mf_enable_nearby_hint', 'Coordinate details are missing from your location database. Click OK to get them added automatically, or Cancel the pop-up and enter the coordinates manually.', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '1', 'mf_database_mismatch_nearby_hint', 'Coordinate details are missing from your location database; please enter the coordinates manually.', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'mf_coordinates', 'Coordinates', 'multi_formats', '0', 'multiField', 'active'),
('en', 'admin', '', 'title_multiField', 'Multifield/Location Filter', '', '0', 'multiField', 'active'),
('en', 'admin', '', 'description_multiField', 'Adds dependent fields and filters listings by locations', 'plugins', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config_groups+name+geo_filter_config', 'Geo Filter', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_geo_autodetect', 'Auto detection of user location', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_geofilter_expiration', 'Cookie expiration period', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_geofilter_expiration', 'The number of days, during which the system will keep a user location in the browser cookie.', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_select_interface', 'Select location in', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_autocomplete_divider', 'Autocomplete Field', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_geo_block_autocomplete', 'Autocomplete field in Location Filter box', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_geo_autocomplete_limit', 'Number of suggested locations', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_seo_divider', 'SEO', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_geo_subdomains_type', 'Location structure in URLs', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_geo_subdomains_type', '<div style=\"padding: 8px 0 5px;line-height: 15px;\"><div class=\"hide\"><b>Mixed</b> - usa.domain.com/california/miami/;<br><b>Full</b> - usa-california-miami.domain.com;<br><b>City only</b> - miami.domain.com.</div><div><b>Full</b> - www.domain.com/usa/california/miami/;<br><b>City only</b> - www.domain.com/miami/.</div></div>', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_geo_subdomains', 'Location on subdomains', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_geo_subdomains', 'Unavailable if one of the listing types is enabled on a subdomain (See \"Listing type URL\" setting).', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_listing_geo_urls', 'Add locations to listing URLs', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_listing_geo_urls', 'Generates the URLs of Listing Details using location details specified in listings.', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_multilingual_path', 'Multilingual paths', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_multilingual_path', '<div><div>Allows you to build paths of location pages in other languages, e.g. praha.site.com or www.site.com/mu&gbreve;la/</div><div>The option can be enabled with 2 and more active languages on the site.</div></div>', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_account_page_filtration', 'Filtering ads on seller page', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_account_page_filtration', '<div style=\"padding: 8px 0 5px;line-height: 15px;\"><b>None</b> - Ads are not filtered on the seller page;<br><b>Filter</b> - Ads are filtered on the seller page by locations selected by visitors;<br><b>URL</b> - Locations are added to seller page URLs; no filtering is done.</div>', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_urls_in_sitemap', 'Add GEO-modified URLs to Sitemap', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_urls_in_sitemap', '<div>Adding URLs with locations to the sitemap is a resource and time consuming process.<br>If you get an error increase the following PHP parameters - memory_limit and max_execution_time.</div>', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_home_in_sitemap', 'Home page URL in sitemap', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_home_in_sitemap', '<div>When enabled, the home page will be added to the sitemap with all the locations because the system cannot detect properly availability of listings/accounts on the page.</div>', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_filtering_divider', 'Filtering Listings/Accounts on Pages', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_show_nearby_listings', 'Show nearby listings', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_nearby_distance', 'Nearby listings radius', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+des+mf_nearby_distance', 'km from the target location center', 'settings', '0', 'multiField', 'active'),
('en', 'admin', '0', 'config+name+mf_popular_locations_level', 'Popular locations in the pop-up', 'settings', '0', 'multiField', 'active'),
('en', 'box', '0', 'blocks+name+geo_filter_box', 'Location Filter', 'geo_filter_box', '0', 'multiField', 'active');

INSERT INTO `{db_prefix}blocks` (`Page_ID`, `Category_ID`, `Subcategories`, `Sticky`, `Cat_sticky`, `Key`, `Position`, `Side`, `Type`, `Content`, `Tpl`, `Header`, `Plugin`, `Status`, `Readonly`) VALUES
('1,42', '', '0', '0', '0', 'geo_filter_box', 1, 'left', 'smarty', '{include file=$smarty.const.RL_PLUGINS|cat:"multiField"|cat:$smarty.const.RL_DS|cat:"geo_box.tpl"}', '1', '1', 'multiField', 'trash', '1');

INSERT INTO `{db_prefix}plugins` (`Key`, `Class`, `Name`, `Description`, `Version`, `Controller`, `Fcontroller`, `Files`, `Uninstall`, `Status`, `Install`) VALUES
('multiField', 'MultiField', 'Multifield/Location Filter', 'Adds dependent fields and filters listings by locations', '2.6.1', 'multi_formats', '', 'a:31:{i:0;s:18:\"admin/flsource.tpl\";i:1;s:16:\"admin/import.php\";i:2;s:26:\"admin/import_interface.tpl\";i:3;s:21:\"admin/manage_item.tpl\";i:4;s:23:\"admin/multi_formats.tpl\";i:5;s:27:\"admin/multi_formats.inc.php\";i:6;s:22:\"admin/refreshEntry.tpl\";i:7;s:18:\"admin/settings.tpl\";i:8;s:19:\"admin/tplFooter.tpl\";i:9;s:19:\"admin/tplHeader.tpl\";i:10;s:21:\"admin/dataEntries.tpl\";i:11;s:21:\"admin/nearbyCheck.tpl\";i:12;s:17:\"static/aStyle.css\";i:13;s:18:\"static/gallery.png\";i:14;s:13:\"static/lib.js\";i:15;s:19:\"static/lib_admin.js\";i:16;s:22:\"static/autocomplete.js\";i:17;s:18:\"static/gallery.svg\";i:18;s:17:\"static/nearby.svg\";i:19;s:16:\"autocomplete.tpl\";i:20;s:21:\"location_selector.tpl\";i:21;s:11:\"geo_box.tpl\";i:22;s:10:\"mfield.tpl\";i:23;s:18:\"mfield_account.tpl\";i:24;s:21:\"rlGeoFilter.class.php\";i:25;s:22:\"rlMultiField.class.php\";i:26;s:24:\"rlMultiFieldAP.class.php\";i:27;s:13:\"tplFooter.tpl\";i:28;s:13:\"tplHeader.tpl\";i:29;s:12:\"i18n/ru.json\";i:30;s:17:\"nearby_header.tpl\";}', '', 'active', '1');
/** multiField Plugin end **/

/** fieldBoundBoxes Plugin **/
ALTER TABLE `{db_prefix}pages` ADD `Fbb_hidden` ENUM('0', '1') NOT NULL DEFAULT '0' AFTER `Readonly`;

DROP TABLE IF EXISTS `{db_prefix}field_bound_boxes`;
CREATE TABLE `{db_prefix}field_bound_boxes` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Key` varchar(255) NOT NULL,
  `Field_key` varchar(255) NOT NULL,
  `Multiple_items` enum('0','1') NOT NULL DEFAULT '0',
  `Columns` enum('auto','1','2','3','4') NOT NULL DEFAULT 'auto',
  `Page_columns` enum('auto','2','3','4') NOT NULL DEFAULT 'auto',
  `Show_count` enum('0','1') NOT NULL DEFAULT '0',
  `Postfix` enum('0','1') NOT NULL DEFAULT '0',
  `Parent_page` enum('0','1') NOT NULL DEFAULT '0',
  `Listing_type` varchar(255) NOT NULL,
  `Icons_position` enum('left','right','top','bottom') NOT NULL DEFAULT 'top',
  `Icons_width` int(5) NOT NULL DEFAULT 0,
  `Icons_height` int(5) NOT NULL DEFAULT 0,
  `Resize_icons` enum('0','1') NOT NULL DEFAULT '1',
  `Orientation` enum('landscape','portrait') NOT NULL DEFAULT 'landscape',
  `Show_empty` enum('0','1') NOT NULL DEFAULT '1',
  `Style` enum('text','text_pic','icon','responsive') NOT NULL DEFAULT 'text',
  `Sorting` enum('position','alphabet') NOT NULL DEFAULT 'position',
  `Status` enum('active','approval','trash') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`ID`),
  KEY `Key` (`Key`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS `{db_prefix}field_bound_items`;
CREATE TABLE `{db_prefix}field_bound_items` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Box_ID` int(11) NOT NULL DEFAULT 0,
  `Position` int(5) NOT NULL DEFAULT 0,
  `Key` varchar(100) NOT NULL DEFAULT '',
  `Path` varchar(100) NOT NULL DEFAULT '',
  `pName` varchar(255) NOT NULL DEFAULT '',
  `Count` int(5) NOT NULL DEFAULT 0,
  `Icon` varchar(255) NOT NULL DEFAULT '',
  `Status` enum('active','approval','trash') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`ID`),
  KEY `Box_ID` (`Box_ID`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `{db_prefix}hooks` (`Name`, `Class`, `Plugin`, `Code`, `Status`) VALUES
('apPhpMultifieldGetAvailablePages', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apAjaxRequest', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apTplControlsForm', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpListingsAfterAdd', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('phpListingsAjaxDeleteListing', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpListingsAfterEdit', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpListingFieldsAfterEdit', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apExtDataFormatsUpdate', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apExtListingsUpdate', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpFormatsAjaxDeleteItem', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpFormatsAjaxMassActions', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpFormatsAjaxAddItem', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpFormatsAjaxEditItem', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpFieldsAjaxDeleteField', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpFormatsAjaxDeleteFormat', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apExtListingFieldsUpdate', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apExtBlocksUpdate', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpBlocksAfterEdit', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('afterListingDone', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('editListingAdditionalInfo', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('phpListingsUpgradeListing', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('afterImport', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('cronAdditional', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('phpMetaRelPrevNext', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apExtPagesUpdate', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpPagesAfterEdit', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpListingsMassActions', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apExtPagesSql', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('sitemapExcludedPages', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('sitemapAddPluginUrls', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('tplHeader', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('phpMetaTags', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('listingsModifyWhere', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('listingsModifyPreSelect', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('listingsModifyJoin', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpIndexBottom', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active'),
('apPhpBlocksGetPageWhere', 'FieldBoundBoxes', 'fieldBoundBoxes', '', 'active');

INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`) VALUES
('en', 'admin', '1', 'fb_field', 'Target field', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_show_empty', 'Show empty items', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_html_postfix', '.html at the end of URLs', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_icons_position', 'Icon position', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_box_settings', 'Box settings', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '1', 'fb_icon_settings', 'Icon settings', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_page_settings', 'Page settings', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_seo_settings', 'Item SEO defaults', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_item_path', 'Item path', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_item_icon', 'Icon', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '1', 'fb_icon_deleted', 'You have successfully removed the icon.', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_option_key', 'option_path', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_items_list', 'All Items', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_recount_text', 'Recount field-bound boxes', 'controls', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '1', 'fb_listings_recounted', 'Field-bound boxes have been recounted.', 'controls', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_regenerate_path_desc', 'Leave the field empty to get the URL generated automatically.', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_current_icon', 'Current icon', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_rebuild_box_items', 'Update Items', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '1', 'fb_items_recopied', 'The items have been successfully recopied from the field attributes', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_rebuild_notice', 'Are you sure you want to update the items and recopy field attributes because it will result in loss of icons and other data?', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_seo_defaults_hint', 'You may apply the {item} variable to the <b>fields below</b> and it will be replaced with the actual item name', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_box_style', 'Box design', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_box_style_text', 'Text', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_box_style_text_pic', 'Text and picture', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_box_style_responsive', 'Responsive picture', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_box_style_icon', 'Icon only', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_picture_settings', 'Picture settings', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_use_parent_page', 'Box landing page', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_use_parent_page_hint', 'When enabled, it generates a separate page for the field-bound box that you can manage.', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_columns', 'columns', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_auto', 'Auto', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_responsive_style_icons_hint', 'Min 500px / 500px; use good quality pictures', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'system', '0', 'fbb_no_options', 'There are no available options.', '', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_orientation', 'Picture orientation', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_landscape', 'Landscape', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_portrait', 'Portrait', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'frontEnd', '0', 'fbb_view_listings', 'View Listings', '', '0', 'fieldBoundBoxes', 'active'),
('en', 'system', '0', 'fbb_picture_side_small', 'Please make sure you filled in the width and height fields; recommended value - 15 and larger', '', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_resize_pictures', 'Resize uploaded icons', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '1', 'fb_svg_icons', 'SVG Icons', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fb_select_from_gallery', 'or [Select from Gallery] ', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'system', '0', 'fbb_item_exists_in_box', 'The item has already been added to the box', '', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '1', 'fbb_no_items_found', 'The item has not been found.', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_too_much_items_found', 'Too many results found; please narrow down your search.', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_items_search_hint', 'Please search for field attributes to add more items to the box.', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'system', '0', 'fbb_box_added_multiple', 'You have successfully added the new box.<br /><br />The target field contains too many attributes, please use the \"Add a New Item\" button to search and add more items to the box.', '', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_icon_left', 'Left', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_icon_right', 'Right', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '0', 'fbb_counter_single_line', 'Show the counter under the text', 'field_bound_boxes', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '', 'title_fieldBoundBoxes', 'Field-bound Boxes', '', '0', 'fieldBoundBoxes', 'active'),
('en', 'admin', '', 'description_fieldBoundBoxes', 'Allows you to show field attributes in the form of categories in separate boxes', 'plugins', '0', 'fieldBoundBoxes', 'active');

INSERT INTO `{db_prefix}plugins` (`Key`, `Class`, `Name`, `Description`, `Version`, `Controller`, `Fcontroller`, `Files`, `Uninstall`, `Status`, `Install`) VALUES
('fieldBoundBoxes', 'FieldBoundBoxes', 'Field-bound Boxes', 'Allows you to show field attributes in the form of categories in separate boxes', '2.2.0', 'field_bound_boxes', '', 'a:10:{i:0;s:31:\"admin/field_bound_boxes.inc.php\";i:1;s:27:\"admin/field_bound_boxes.tpl\";i:2;s:22:\"admin/icon_manager.tpl\";i:3;s:22:\"admin/refreshEntry.tpl\";i:4;s:12:\"i18n/ru.json\";i:5;s:19:\"field-bound_box.tpl\";i:6;s:10:\"header.tpl\";i:7;s:25:\"listings_by_field.inc.php\";i:8;s:21:\"listings_by_field.tpl\";i:9;s:27:\"rlFieldBoundBoxes.class.php\";}', '', 'active', '1');
/** fieldBoundBoxes Plugin end **/

/** socialMetaData Plugin **/
INSERT INTO `{db_prefix}config_groups` (`ID`, `Key`, `Position`, `Plugin`) VALUES
(33, 'social_meta_data', 29, 'socialMetaData');

INSERT INTO `{db_prefix}config` (`Group_ID`, `Position`, `Key`, `Default`, `Values`, `Type`, `Data_type`, `Plugin`) VALUES
(33, 0, 'smd_twitter_name', '', '', 'text', '', 'socialMetaData'),
(33, 1, 'smd_logo', '', '', 'text', '', 'socialMetaData'),
(33, 2, 'smd_fb_appid', '', '', 'text', '', 'socialMetaData'),
(33, 3, 'smd_fb_admins', '', '', 'text', '', 'socialMetaData');

INSERT INTO `{db_prefix}lang_keys` (`Code`, `Module`, `JS`, `Key`, `Value`, `Target_key`, `Modified`, `Plugin`, `Status`) VALUES
('en', 'admin', '', 'title_socialMetaData', 'Social Meta Data', '', '0', 'socialMetaData', 'active'),
('en', 'admin', '', 'description_socialMetaData', 'Generates social meta tags for Twitter, Facebook and other social networks', 'plugins', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config_groups+name+social_meta_data', 'Social Meta Data', 'settings', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config+name+smd_twitter_name', 'Twitter username', 'settings', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config+name+smd_logo', 'Logo file name', 'settings', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config+des+smd_logo', 'Upload the file to the plugin directory (/plugins/socialMetaData/), and make sure its resolution is 600x315 px or higher.', 'settings', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config+name+smd_fb_appid', 'Facebook App ID', 'settings', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config+des+smd_fb_appid', 'Create a new Facebook App from your account and add its ID. Click <a target=\'_blank\' href=\'https://developers.facebook.com/docs/apps/\'>here</a> to learn how to do it.', 'settings', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config+name+smd_fb_admins', 'Facebook Admin', 'settings', '0', 'socialMetaData', 'active'),
('en', 'admin', '0', 'config+des+smd_fb_admins', 'Enter the account ID of the Facebook App owner. Click <a target=\'_blank\' href=\'https://findmyfbid.in/\' alt=\'Find Facebook ID by URL\'>here</a> to get the ID by the Facebook URL.', 'settings', '0', 'socialMetaData', 'active');

INSERT INTO `{db_prefix}hooks` (`Name`, `Class`, `Plugin`, `Code`, `Status`) Values
('boot', 'SocialMetaData', 'socialMetaData', '', 'active'),
('tplHeaderCommon', 'SocialMetaData', 'socialMetaData', '', 'active');

INSERT INTO `{db_prefix}plugins` (`Key`, `Class`, `Name`, `Description`, `Version`, `Controller`, `Fcontroller`, `Files`, `Uninstall`, `Status`, `Install`) VALUES
('socialMetaData', '', 'Social Meta Data', 'Generates social meta tags for Twitter, Facebook and other social networks', '1.2.6', '', '', 'a:3:{i:0;s:26:\"rlSocialMetaData.class.php\";i:1;s:20:\"social_meta_data.tpl\";i:2;s:12:\"i18n/ru.json\";}', '', 'active', '1');
/** socialMetaData Plugin end **/
