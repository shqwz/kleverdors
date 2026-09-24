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
final class Conditions
{
    public const IS_SET = 'is-set';
    public const IS_NOT_SET = 'is-not-set';
    public const IS_YES = 'is-yes';
    public const IS_NO = 'is-no';
    public const EQUALS = 'equals';
    public const EQUALS_IN_REFERENCE = 'equals-in-reference';
    public const NOT_EQUALS_IN_REFERENCE = 'not-equals-in-reference';
    public const CONTAINS = 'contains';
    public const NOT_EQUALS = 'not-equals';
    public const NOT_CONTAINS = 'not-contains';
    public const STARTS_WITH = 'starts-with';
    public const NOT_STARTS_WITH = 'not-starts-with';
    public const ENDS_WITH = 'ends-with';
    public const NOT_ENDS_WITH = 'not-ends-with';
    public const IS_GREATER_THAN = 'is-greater-than';
    public const IS_LESS_THAN = 'is-less-than';
    public const IS_GREATER_THAN_OR_EQUAL_TO = 'is-greater-than-or-equal-to';
    public const IS_LESS_THAN_OR_EQUAL_TO = 'is-less-than-or-equal-to';
    public const IS_INCLUDE = 'is-include';
    public const IS_NOT_INCLUDE = 'is-not-include';
    public const IS_INCLUDE_PARENT = 'is-include-parent';
    public const IS_BEFORE = 'is-before';
    public const IS_BEFORE_OR_EQUAL = 'is-before-or-equal';
    public const IS_AFTER = 'is-after';
    public const IS_AFTER_OR_EQUAL = 'is-after-or-equal';
    public const IS_BETWEEN_DATE = 'is-between-date';
    public const IS_NOT_BETWEEN_DATE = 'is-not-between-date';
    public const IS_ASSOCIATED_WITH = 'is-associated-with';
    public const RELATED = 'related';

    public const MATCH_ALL = 'all';
    public const MATCH_ANY = 'any';

    /**
     * Get all conditions.
     * 
     * @return array
     * @since 5.5.0
     */
    public static function all()
    {
        return [
            self::IS_SET,
            self::IS_NOT_SET,
            self::IS_YES,
            self::IS_NO,
            self::EQUALS,
            self::EQUALS_IN_REFERENCE,
            self::NOT_EQUALS,
            self::NOT_EQUALS_IN_REFERENCE,
            self::CONTAINS,
            self::NOT_CONTAINS,
            self::STARTS_WITH,
            self::NOT_STARTS_WITH,
            self::ENDS_WITH,
            self::NOT_ENDS_WITH,
            self::IS_GREATER_THAN,
            self::IS_LESS_THAN,
            self::IS_GREATER_THAN_OR_EQUAL_TO,
            self::IS_LESS_THAN_OR_EQUAL_TO,
            self::IS_INCLUDE,
            self::IS_NOT_INCLUDE,
            self::IS_INCLUDE_PARENT,
            self::IS_BEFORE,
            self::IS_BEFORE_OR_EQUAL,
            self::IS_AFTER,
            self::IS_AFTER_OR_EQUAL,
            self::IS_BETWEEN_DATE,
            self::IS_NOT_BETWEEN_DATE,
            self::IS_ASSOCIATED_WITH,
        ];
    }

    /**
     * Get the linear conditions.
     * 
     * @return array
     * @since 5.5.0
     */
    public static function getLinearConditions()
    {
        return [
            self::IS_SET,
            self::IS_NOT_SET,
            self::IS_YES,
            self::IS_NO,
            self::EQUALS,
            self::CONTAINS,
            self::NOT_EQUALS,
            self::NOT_CONTAINS,
            self::STARTS_WITH,
            self::NOT_STARTS_WITH,
            self::ENDS_WITH,
            self::NOT_ENDS_WITH,
            self::IS_GREATER_THAN,
            self::IS_LESS_THAN,
            self::IS_GREATER_THAN_OR_EQUAL_TO,
            self::IS_LESS_THAN_OR_EQUAL_TO,
            self::IS_BEFORE,
            self::IS_BEFORE_OR_EQUAL,
            self::IS_AFTER,
            self::IS_AFTER_OR_EQUAL,
            self::IS_BETWEEN_DATE,
            self::IS_NOT_BETWEEN_DATE,
        ];
    }

    /**
     * Get the non-linear conditions.
     * 
     * @return array
     * @since 5.5.0
     */
    public static function getNonLinearConditions()
    {
        return [
            self::IS_INCLUDE,
            self::IS_NOT_INCLUDE,
            self::IS_INCLUDE_PARENT,
            self::EQUALS_IN_REFERENCE,
            self::NOT_EQUALS_IN_REFERENCE,
            self::IS_ASSOCIATED_WITH,
            self::RELATED,
        ];
    }

    /**
     * Get the match conditions.
     * 
     * @return array
     * @since 5.5.0
     */
    public static function match()
    {
        return [
            self::MATCH_ALL,
            self::MATCH_ANY,
        ];
    }

    /**
     * Check if the condition should check the value.
     * @param string $condition The condition to check.
     * @return bool True if the condition should check the value, false otherwise.
     * @since 5.5.0
     */
    public static function shouldCheckValue($condition)
    {
        return !in_array($condition, [
            self::IS_SET,
            self::IS_NOT_SET,
            self::IS_YES,
            self::IS_NO,
        ]);
    }
}
