<?php
/**
 * Smarty plugin by Flynax
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Decode html entities
 *
 * Type:     modifier
 * Name:     fl_html_entities_decode
 * Purpose:  decode data
 * 
 * @author   John Freeman
 * @param string
 * @return string
 */
function smarty_modifier_html_decode( $string = false )
{
    return html_entity_decode($string, null, 'utf-8');
}

?>
