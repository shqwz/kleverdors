<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Supports;

use DateTime;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\String\StringHelper;

defined('_JEXEC') or die;

final class Str extends StringHelper
{
    /**
     * Convert a JSON string to an array.
     * 
     * @param string $value The JSON string.
     *
     * @return array
     * @since 5.5.0
     */
    public static function toArray($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true) ?? [];
    }

    /**
     * Process the value to ensure it's an array.
     * 
     * @param string $value The value to process.
     *
     * @return array
     * @since 5.5.0
     */
    public static function process($value)
    {
        if (empty($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return $value;
    }

    /**
     * Make a safe URL string.
     * 
     * @param string $value The string to make safe.
     *
     * @return string
     * @since 5.5.0
     */
    public static function safeUrl(string $value)
    {
        return ApplicationHelper::stringURLSafe($value);
    }

    /**
     * Generate a UUID version 4.
     * 
     * @return string
     * @since 5.5.0
     */
    public static function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, // Version 4 UUID
            mt_rand(0, 0x3fff) | 0x8000, // Variant
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Check if the value is an HTML string.
     * 
     * @param string $value The value to check.
     *
     * @return bool
     * @since 5.5.0
     */
    public static function isHtmlString($value)
    {
        if (!is_string($value)) {
            return false;
        }

        $pattern = '/<[a-z][\s\S]*>/i';
        return preg_match($pattern, $value) === 1;
    }

    /**
     * Sanitize the HTML string.
     * 
     * @param string $value The value to sanitize.
     *
     * @return string
     * @since 5.5.0
     */
    public static function sanitizeHtmlString(string $value)
    {
        $value = preg_replace('/<(script|iframe|embed|object|applet)[^>]*?>.*?<\/\1>/is', '', $value);
        $value = strip_tags($value, '<p><a><b><i><u><strong><em><span><div><br><ul><ol><li><h1><h2><h3><h4><h5><h6><img><table><tr><td><th><thead><tbody><blockquote>');

        return $value;
    }

    /**
     * Convert a plural word to its singular form.
     * 
     * @param string $word The word to convert.
     *
     * @return string
     * @since 5.5.0
     */
    public static function toSingular(string $word)
    {
        $irregulars = [
            'children' => 'child',
            'people' => 'person',
            'teeth' => 'tooth',
            'feet' => 'foot',
            'geese' => 'goose',
            'mice' => 'mouse',
            'men' => 'man',
            'women' => 'woman',
            'oxen' => 'ox'
        ];

        if (array_key_exists($word, $irregulars)) {
            return $irregulars[$word];
        }

        $rules = [
            '/(quiz)zes$/i' => '$1',
            '/(matr)ices$/i' => '$1ix',
            '/(vert|ind)ices$/i' => '$1ex',
            '/^(ox)en$/i' => '$1',
            '/(alias|status)es$/i' => '$1',
            '/([octop|vir])i$/i' => '$1us',
            '/(cris|ax|test)es$/i' => '$1is',
            '/(shoe)s$/i' => '$1',
            '/(o)es$/i' => '$1',
            '/(bus)es$/i' => '$1',
            '/([m|l])ice$/i' => '$1ouse',
            '/(x|ch|ss|sh)es$/i' => '$1',
            '/(m)ovies$/i' => '$1ovie',
            '/(s)eries$/i' => '$1eries',
            '/([^aeiouy]|qu)ies$/i' => '$1y',
            '/([lr])ves$/i' => '$1f',
            '/(tive)s$/i' => '$1',
            '/(hive)s$/i' => '$1',
            '/([^f])ves$/i' => '$1fe',
            '/(^analy)ses$/i' => '$1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
            '/([ti])a$/i' => '$1um',
            '/(n)ews$/i' => '$1ews',
            '/s$/i' => ''
        ];

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }

        return $word;
    }

    /**
     * Convert a singular word to its plural form.
     * 
     * @param string $word The word to convert.
     *
     * @return string
     * @since 5.5.0
     */
    public static function toPlural(string $word)
    {
        $irregulars = [
            'person' => 'people',
            'man' => 'men',
            'child' => 'children',
            'foot' => 'feet',
            'goose' => 'geese',
            'mouse' => 'mice',
            'woman' => 'women',
            'ox' => 'oxen',
            'tooth' => 'teeth'
        ];

        if (array_key_exists($word, $irregulars)) {
            return $irregulars[$word];
        }

        $rules = [
            '/(quiz)$/i' => '$1zes',
            '/(matr|vert|ind)ix|ex$/i' => '$1ices',
            '/(x|ch|ss|sh)$/i' => '$1es',
            '/([^aeiouy]|qu)y$/i' => '$1ies',
            '/(hive)$/i' => '$1s',
            '/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
            '/sis$/i' => 'ses',
            '/([ti])um$/i' => '$1a',
            '/(buffal|tomat)o$/i' => '$1oes',
            '/(bu)s$/i' => '$1ses',
            '/(alias|status)$/i' => '$1es',
            '/(octop|vir)us$/i' => '$1i',
            '/(ax|test)is$/i' => '$1es',
            '/s$/i' => 's',
            '/$/' => 's'
        ];

        foreach ($rules as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }

        return $word;
    }

    /**
     * Check if the value is a date.
     * 
     * @param string $value The value to check.
     *
     * @return bool
     * @since 5.5.0
     */
    public static function isDate(string $value)
    {
        return strtotime($value) !== false;
    }
}
