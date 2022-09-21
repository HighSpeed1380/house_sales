<?php
/**
 * Smarty plugin by Flynax
 * @package Smarty
 * @subpackage plugins
 */


/**
 * convert string to key format
 *
 * Type:     modifier
 * Name:     str2key
 * Purpose:  converting
 * 
 * @author   John Freeman
 * @param string
 * @return string
 */
function smarty_modifier_str2key( $string = false )
{
	global $rlValid;
	
    return $rlValid -> str2key($string);
}

?>
