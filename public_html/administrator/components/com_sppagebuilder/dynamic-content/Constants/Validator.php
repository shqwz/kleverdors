<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Constants;

defined('_JEXEC') or die;

class Validator
{
    /**
     * The available rules
     * 
     * @var array
     * 
     * @since 5.5.0
     */
    public const AVAILABLE_RULES = [
        'required',
        'string',
        'array',
        'object',
        'boolean',
        'integer',
        'float',
        'email',
        'url',
        'min',
        'max',
        'in',
        'not_in',
        'regex',
        'number'
    ];

    /**
     * The rules to validator method map
     * 
     * @var array
     * 
     * @since 5.5.0
     */
    public const RULE_MAP = [
        'required'  => 'checkRequired',
        'string'    => 'checkString', 
        'array'     => 'checkArray',
        'object'    => 'checkObject',
        'boolean'   => 'checkBoolean',
        'integer'   => 'checkInteger',
        'number'    => 'checkNumber',
        'float'     => 'checkFloat',
        'email'     => 'checkEmail',
        'url'       => 'checkUrl',
        'min'       => 'checkMin',
        'max'       => 'checkMax',
        'in'        => 'checkIn',
        'not_in'    => 'checkNotIn',
        'regex'     => 'checkRegex',
    ];
}
