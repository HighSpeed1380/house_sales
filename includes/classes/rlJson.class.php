<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Converts to and from JSON format.
 *
 * JSON (JavaScript Object Notation) is a lightweight data-interchange
 * format. It is easy for humans to read and write. It is easy for machines
 * to parse and generate. It is based on a subset of the JavaScript
 * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
 * This feature can also be found in  Python. JSON is a text format that is
 * completely language independent but uses conventions that are familiar
 * to programmers of the C-family of languages, including C, C++, C#, Java,
 * JavaScript, Perl, TCL, and many others. These properties make JSON an
 * ideal data-interchange language.
 *
 * This package provides a simple encoder and decoder for JSON notation. It
 * is intended for use with client-side Javascript applications that make
 * use of HTTPRequest to perform server communication functions - data can
 * be encoded into JSON notation for use in a client-side javascript, or
 * decoded from incoming Javascript requests. JSON format is native to
 * Javascript, and can be directly eval()'ed with no further parsing
 * overhead
 *
 * All strings should be in ASCII or UTF-8 format!
 *
 * LICENSE: Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met: Redistributions of source code must retain the
 * above copyright notice, this list of conditions and the following
 * disclaimer. Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
 * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category
 * @package     rlJson
 * @author      Michal Migurski <mike-json@teczno.com>
 * @author      Matt Knapp <mdknapp[at]gmail[dot]com>
 * @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 * @copyright   2005 Michal Migurski
 * @version     CVS: $Id: JSON.php,v 1.31 2006/06/28 05:54:17 migurski Exp $
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
 */

/**
 * Marker constant for rlJson::decode(), used to flag stack state
 */
define('rlJson_SLICE', 1);

/**
 * Marker constant for rlJson::decode(), used to flag stack state
 */
define('rlJson_IN_STR', 2);

/**
 * Marker constant for rlJson::decode(), used to flag stack state
 */
define('rlJson_IN_ARR', 3);

/**
 * Marker constant for rlJson::decode(), used to flag stack state
 */
define('rlJson_IN_OBJ', 4);

/**
 * Marker constant for rlJson::decode(), used to flag stack state
 */
define('rlJson_IN_CMT', 5);

/**
 * Behavior switch for rlJson::decode()
 */
define('rlJson_LOOSE_TYPE', 16);

/**
 * Behavior switch for rlJson::decode()
 */
define('rlJson_SUPPRESS_ERRORS', 32);

/**
 * Converts to and from JSON format.
 *
 * Brief example of use:
 *
 * @deprecated 4.8.2 - Remove when all plugins will use native PHP functions
 *
 * <code>
 * // create a new instance of rlJson
 * $json = new rlJson();
 *
 * // convert a complexe value to JSON notation, and send it to the browser
 * $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
 * $output = $json->encode($value);
 *
 * print($output);
 * // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
 *
 * // accept incoming POST data, assumed to be in JSON notation
 * $input = file_get_contents('php://input', 1000000);
 * $value = $json->decode($input);
 * </code>
 */
class rlJson
{
    /**
     * encodes an arbitrary variable into JSON format
     *
     * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
     *                           see argument 1 to rlJson() above for array-parsing behavior.
     *                           if var is a strng, note that encode() always expects it
     *                           to be in ASCII or UTF-8 format!
     *
     * @return   mixed   JSON string representation of input var or an error if a problem occurs
     * @access   public
     */
    public function encode($var)
    {
        return json_encode($var);
    }

    /**
     * decodes a JSON string into appropriate variable
     *
     * @param    string  $str    JSON-formatted string
     *
     * @return   mixed   number, boolean, string, array, or object
     *                   corresponding to given JSON input string.
     *                   See argument 1 to rlJson() above for object-output behavior.
     *                   Note that decode() always returns strings
     *                   in ASCII or UTF-8 format!
     * @access   public
     */
    public function decode($str, $assoc = false)
    {
        return json_decode($str, $assoc);
    }

    /*** DEPRECATED METHODS ***/

    /**
     * constructs a new JSON instance
     *
     * @param    int     $use    object behavior flags; combine with boolean-OR
     *
     *                           possible values:
     *                           - rlJson_LOOSE_TYPE:  loose typing.
     *                                   "{...}" syntax creates associative arrays
     *                                   instead of objects in decode().
     *                           - rlJson_SUPPRESS_ERRORS:  error suppression.
     *                                   Values which can't be encoded (e.g. resources)
     *                                   appear as NULL instead of throwing errors.
     *                                   By default, a deeply-nested resource will
     *                                   bubble up with an error, so all return values
     *                                   from encode() should be checked with isError()
     */
    public function __construct($use = 0)
    {}

    /**
     * convert a string from one UTF-16 char to one UTF-8 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     *
     * @param    string  $utf16  UTF-16 character
     * @return   string  UTF-8 character
     * @access   private
     */
    public function utf162utf8($utf16)
    {}

    /**
     * convert a string from one UTF-8 char to one UTF-16 char
     *
     * Normally should be handled by mb_convert_encoding, but
     * provides a slower PHP-only method for installations
     * that lack the multibye string extension.
     *
     * @param    string  $utf8   UTF-8 character
     * @return   string  UTF-16 character
     * @access   private
     */
    public function utf82utf16($utf8)
    {}

    /**
     * array-walking function for use in generating JSON-formatted name-value pairs
     *
     * @param    string  $name   name of key to use
     * @param    mixed   $value  reference to an array element to be encoded
     *
     * @return   string  JSON-formatted name-value pair, like '"name":value'
     * @access   private
     */
    public function name_value($name, $value)
    {}

    /**
     * reduce a string by removing leading and trailing comments and whitespace
     *
     * @param    $str    string      string value to strip of comments and whitespace
     *
     * @return   string  string value stripped of comments and whitespace
     * @access   private
     */
    public function reduce_string($str)
    {}

    /**
     * @todo Ultimately, this should just call PEAR::isError()
     */
    public function isError($data, $code = null)
    {}
}

if (class_exists('PEAR_Error')) {
    class rlJson_Error extends PEAR_Error
    {}
} else {
    /**
     * @todo Ultimately, this class shall be descended from PEAR_Error
     */
    class rlJson_Error
    {}
}
