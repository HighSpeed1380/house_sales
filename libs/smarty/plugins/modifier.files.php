<?php
/**
 * Smarty plugin by Flynax
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty detect FILES entry plugin
 *
 * Type:     modifier
 * Name:     files
 * Purpose:  detect if key exist in FILES array
 * 
 * @author   John Freeman
 * @param string
 * @return bool
 */
function smarty_modifier_files( $field = false, $parent = false )
{
	if ( !$field )
		return false;
	
	if ( $parent )
	{
		return (bool)$_FILES[$parent]['name'][$field];
	}
	else
	{
		return (bool)$_FILES[$field]['name'];
	}
}

?>
