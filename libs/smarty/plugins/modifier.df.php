<?php
/**
 * Smarty plugin by Flynax
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty get DATA FORMAT modifier plugin
 *
 * Type:     modifier
 * Name:     df
 * Purpose:  get data format resource by key name
 * 
 * @author   John Freeman
 * @param string
 * @return array
 */
function smarty_modifier_df( $field = false )
{
	global $rlCategories;
	
	if ( !$field )
		return false;
	
    return $rlCategories -> getDF($field);
}

?>
