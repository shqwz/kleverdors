<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Constants;

defined('_JEXEC') or die;

/**
 * The valid collection field types.
 * @since 5.5.0
 */
final class FieldTypes
{
    /**
     * All the available field types constants.
     * @var string
     * @since 5.5.0
     */
    public const TITLE            = 'title';
    public const ALIAS            = 'alias'; 
    public const TEXT             = 'text';
    public const RICH_TEXT        = 'rich-text';
    public const IMAGE            = 'image';
    public const GALLERY          = 'gallery';
    public const VIDEO            = 'video';
    public const DATETIME         = 'date-time';
    public const LINK             = 'link';
    public const EMAIL            = 'email';
    public const PHONE            = 'phone';
    public const NUMBER           = 'number';
    public const RATING           = 'rating';
    public const SWITCH           = 'switch';
    public const COLOR            = 'color';
    public const OPTION           = 'option';
    public const FILE             = 'file';
    public const REFERENCE        = 'reference';
    public const MULTI_REFERENCE  = 'multi-reference';

    /**
     * Get all the field types
     * 
     * @return array
     * @since 5.5.0
     */
    public static function all()
    {
        return [
            self::TITLE,
            self::ALIAS,
            self::TEXT,
            self::RICH_TEXT,
            self::IMAGE,
            self::GALLERY,
            self::EMAIL,
            self::PHONE,
            self::VIDEO,
            self::DATETIME,
            self::LINK,
            self::NUMBER,
            self::RATING,
            self::SWITCH,
            self::COLOR,
            self::OPTION,
            self::FILE,
            self::REFERENCE,
            self::MULTI_REFERENCE,
        ];
    }

    /**
     * Convert the field types to a string
     * 
     * @return string
     * @since 5.5.0
     */
    public function __toString()
    {
        return implode(',', self::all());
    }

    /**
     * Convert the field types to a string
     * 
     * @return string
     * @since 5.5.0
     */
    public function toString()
    {
        return $this->__toString();
    }
}
