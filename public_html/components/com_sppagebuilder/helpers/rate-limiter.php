<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;

//no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Check Rate limiter helper class.
 */
if (!class_exists('SppagebuilderRateLimiterHelper')) {

    /**
     * Rate limiter helper class.
     * This class provides methods to limit the number of requests a user can make within a time window.
     */
    class SppagebuilderRateLimiterHelper
    {
        /**
         * Check if the user has exceeded the rate limit.
         *
         * @param string $key A unique identifier for the user or request (e.g., user ID, IP address).
         * @param int    $maxRequests Maximum allowed requests within the time window.
         * @param int    $timeWindow Time window in seconds.
         * @return bool True if the user is within the limit, false if the limit is exceeded.
         */
        public static function isRateLimited($key, $maxRequests, $timeWindow)
        {
            $session = Factory::getSession();
            $rateLimiterKey = 'sppagebuilder_rate_limiter_' . $key;
            $currentTime = time();

            $rateData = $session->get($rateLimiterKey, [
                'count' => 0,
                'start_time' => $currentTime,
            ]);

            if (($currentTime - $rateData['start_time']) > $timeWindow) {
                $rateData['count'] = 0;
                $rateData['start_time'] = $currentTime;
            }

            $rateData['count']++;
            $session->set($rateLimiterKey, $rateData);

            return $rateData['count'] > $maxRequests;
        }

        /**
         * Get the remaining time before the rate limit resets.
         *
         * @param string $key A unique identifier for the user or request.
         * @param int    $timeWindow Time window in seconds.
         * @return int The remaining time in seconds.
         */
        public static function getTimeUntilReset($key, $timeWindow)
        {
            $session = Factory::getSession();
            $rateLimiterKey = 'sppagebuilder_rate_limiter_' . $key;

            $rateData = $session->get($rateLimiterKey, [
                'count' => 0,
                'start_time' => time(),
            ]);

            $remainingTime = ($rateData['start_time'] + $timeWindow) - time();

            return max(0, $remainingTime);
        }
    }
}
