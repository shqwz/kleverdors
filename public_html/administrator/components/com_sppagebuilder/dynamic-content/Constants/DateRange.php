<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Constants;

defined('_JEXEC') or die;

final class DateRange
{
    /**
     * The last 24 hours date range.
     * 
     * @var string
     * @since 5.5.0
     */
    public const LAST_24_HOURS = '24h';

    /**
     * The last 7 days date range.
     * 
     * @var string
     * @since 5.5.0
     */
    public const LAST_7_DAYS = '7d';

    /**
     * The last 30 days date range.
     * 
     * @var string
     * @since 5.5.0
     */
    public const LAST_30_DAYS = '30d';

    /**
     * Get all the date ranges.
     * 
     * @return array
     * @since 5.5.0
     */
    public static function all()
    {
        return [
            self::LAST_24_HOURS,
            self::LAST_7_DAYS,
            self::LAST_30_DAYS,
        ];
    }
}
