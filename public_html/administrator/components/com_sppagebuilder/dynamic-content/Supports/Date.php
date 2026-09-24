<?php

/**
 * @package     EasyStore.Administrator
 * @subpackage  com_easystore
 * @copyright   (C) 2023 - 2024 JoomShaper. <https://www.joomshaper.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Supports;

use DateTimeZone;
use InvalidArgumentException;
use Joomla\CMS\Date\Date as JoomlaDate;
use Joomla\CMS\Factory;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\DateRange;

defined('_JEXEC') or die;

class Date
{
    public static function create($date, $tzOffset = null)
    {
        return new JoomlaDate($date, $tzOffset);
    }

    /**
     * Generate the date range from today to a previous date.
     * 
     * @param string $range
     * @return array
     * @since 5.5.0
     */
    public static function generateRange($range)
    {
        if (!in_array($range, DateRange::all())) {
            throw new InvalidArgumentException(sprintf('Invalid date range provided: %s. Please use one of the following: %s', $range, implode(', ', DateRange::all())));
        }

        $today      = new JoomlaDate('now', new DateTimeZone('UTC'));
        $previous   = new JoomlaDate('now', new DateTimeZone('UTC'));

        switch ($range) {
            case DateRange::LAST_24_HOURS:
                $previous->modify('-24 hours');
                break;
            case DateRange::LAST_7_DAYS:
                $previous->modify('-7 days');
                break;
            case DateRange::LAST_30_DAYS:
                $previous->modify('-30 days');
                break;
        }

        return [$previous, $today];
    }

    /**
     * Get a safe SQL date for storing in the database.
     * 
     * @param string $time
     * @param mixed $tzOffset
     *
     * @return string
     * @since 5.5.0
     */
    public static function sqlSafeDate($time = 'now', $tzOffset = null)
    {
        return Factory::getDate($time, $tzOffset)->toSql();
    }

    /**
     * Format the date.
     * 
     * @param string $date The date to format.
     * @param string $format The format to use.
     * @return string
     * @since 5.5.0
     */
    public static function format($date = 'now', $format = 'Y-m-d H:i:s', $tzOffset = null)
    {
        return Factory::getDate($date, $tzOffset)->format($format);
    }
}
