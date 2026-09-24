<?php
/*
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Http;

defined('_JEXEC') or die;

use Joomla\Input\Input;

/**
 * The request class.
 * @property-read    Input   $get
 * @property-read    Input   $post
 * @property-read    Input   $request
 * @property-read    Input   $server
 * @property-read    Input   $env
 * @property-read    Files   $files
 * @property-read    Cookie  $cookie
 * @property-read    Json    $json
 *
 * @method      integer  getInt($name, $default = null)       Get a signed integer.
 * @method      integer  getUint($name, $default = null)      Get an unsigned integer.
 * @method      float    getFloat($name, $default = null)     Get a floating-point number.
 * @method      boolean  getBool($name, $default = null)      Get a boolean value.
 * @method      string   getWord($name, $default = null)      Get a word.
 * @method      string   getAlnum($name, $default = null)     Get an alphanumeric string.
 * @method      string   getCmd($name, $default = null)       Get a CMD filtered string.
 * @method      string   getBase64($name, $default = null)    Get a base64 encoded string.
 * @method      string   getString($name, $default = null)    Get a string.
 * @method      string   getHtml($name, $default = null)      Get a HTML string.
 * @method      string   getPath($name, $default = null)      Get a file path.
 * @method      string   getUsername($name, $default = null)  Get a username.
 * @method      mixed    getRaw($name, $default = null)       Get an unfiltered value.
 * @method      boolean  exists($name)                       Check if a value exists.
 * @method      void     set($name, $value)                   Set a value.
 * @method      array    getArray($name = null, $default = null) Get an array.
 * @method      void     def($name = null, $default = null)    Set a default value.
 *
 * @since 5.5.0
 */
class Request extends Input
{
    /**
     * Construct the Request object.
     * 
     * @param array $data The data to initialize the request with.
     * @param array $options The options to initialize the request with.
     * @since 5.5.0
     */
    public function __construct(array $data = [], array $options = [])
    {
        parent::__construct($data, $options);
    }
}
