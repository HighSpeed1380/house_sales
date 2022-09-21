<?php
/**
 * Smarty plugin by Flynax
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Fit character into the string by position
 *
 * Type:     modifier
 * Name:     flStrFit
 * Purpose:  fit char into the string
 * 
 * @author   John Freeman
 * @param string
 * @return array
 */
function smarty_modifier_flStrFit( $string = false, $pos = false, $char = '-' )
{
	global $reefless;
	
	return $reefless -> flStrSplit($string, $pos, $char);
}

?>
