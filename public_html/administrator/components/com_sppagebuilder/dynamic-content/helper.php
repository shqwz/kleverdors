<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use JoomShaper\SPPageBuilder\DynamicContent\Exceptions\ValidatorException;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;

/**
 * Create a new response instance
 * 
 * @return Response
 * @since 5.5.0
 */
if (!function_exists('response')) {
    function response()
    {
        return Response::create()->withHeaders([
            'Accept' => 'text/html, application/json, */*',
            'Connection' => 'keep-alive'
        ]);
    }
}

/**
 * Execute a callback with an exception handler
 * 
 * @param mixed $value
 * @param Closure $callback
 * 
 * @return mixed
 * @since 5.5.0
 */
if (!function_exists('withException')) {
    function withException($value, Closure $callback)
    {
        try {
            $callback($value);
        } catch (Exception $error) {
            if ($error instanceof ValidatorException) {
                return response()->json($error->getData(), $error->getCode());
            }

            return response()->json(['message' => $error->getMessage()], $error->getCode());
        }

        return $value;
    }
}

/**
 * Execute a callback and return the value
 * 
 * @param mixed $value
 * @param Closure $callback
 * 
 * @return mixed
 * @since 5.5.0
 */
if (!function_exists('tap')) {
    function tap($value, Closure $callback) {
        $callback($value);
        return $value;
    }
}

/**
 * Get the current logged in user
 * 
 * @return User
 * @since 5.5.0
 */
if (!function_exists('getCurrentLoggedInUser')) {
    function getCurrentLoggedInUser() {
        if (JVERSION >= 4) {
            return Factory::getApplication()->getIdentity();
        }

        return Factory::getUser();
    }
}

/**
 * Execute a callback and return the data and error
 * 
 * @param Closure $callback
 * 
 * @return array
 * @since 5.5.0
 */
if (!function_exists('wrapErrorSafe')) {
    function wrapErrorSafe(Closure $callback) {
        $data = null;
        $error = null;

        try {
            $data = $callback();
        } catch (Throwable $error) {
            $error = $error;
        }

        return [$data, $error];
    }
}