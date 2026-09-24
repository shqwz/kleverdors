<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.sppagebuilderloadmodule
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomShaper\Plugin\Content\Sppagebuilderloadmodule\Extension;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\CMSPlugin;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
    require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
}

/**
 * Plugin to enable loading modules into content (e.g. articles)
 * This uses the {loadmodule} syntax
 *
 * @since  5.4.3
 */
final class Sppagebuilderloadmodule extends CMSPlugin
{
    protected static $modules = [];

    protected static $mods = [];

    /**
     * Plugin that loads module positions within content
     *
     * @param   string   $context   The context of the content being passed to the plugin.
     * @param   object   &$item  The item object.  Note $item->text is also available
     * @param   object   &$params The parameters
     * @param   integer  $page   The page number
     *
     * @return  void
     *
     * @since   5.4.3
     */
    public function onContentPrepare($context, &$item, &$params, $page = 0)
    {
        // Only execute if $item is an object and has a text property
        if (!is_object($item) || !property_exists($item, 'content') || is_null($item->text)) {
            return;
        }

        // Expression to search for (positions)
        $regex = '/{loadposition\s(.*?)}/i';

        // Expression to search for(modules)
        $regexmod = '/{loadmodule\s(.*?)}/i';

        // Expression to search for(id)
        $regexmodid = '/{loadmoduleid\s([1-9][0-9]*)}/i';

        // Remove macros and don't run this plugin when the content is being indexed
        if ($context === 'com_finder.indexer') {
            if (str_contains($item->text, 'loadposition')) {
                $item->text = preg_replace($regex, '', $item->text);
            }

            if (str_contains($item->text, 'loadmoduleid')) {
                $item->text = preg_replace($regexmodid, '', $item->text);
            }

            if (str_contains($item->text, 'loadmodule')) {
                $item->text = preg_replace($regexmod, '', $item->text);
            }

            return;
        }

        if (str_contains($item->text, '{loadposition ')) {
            // Find all instances of plugin and put in $matches for loadposition
            // $matches[0] is full pattern match, $matches[1] is the position
            preg_match_all($regex, $item->text, $matches, PREG_SET_ORDER);

            // No matches, skip this
            if ($matches) {
                foreach ($matches as $match) {
                    $matcheslist = explode(',', $match[1]);

                    $position = trim($matcheslist[0]);
                    $style    = trim($matcheslist[1]);

                    $output = $this->load($position, $style);

                    // We should replace only first occurrence in order to allow positions with the same name to regenerate their content:
                    if (($start = strpos($item->text, $match[0])) !== false) {
                        $item->text = substr_replace($item->text, $output, $start, strlen($match[0]));
                    }
                }
            }
        }

        if (str_contains($item->text, '{loadmodule ')) {
            // Find all instances of plugin and put in $matchesmod for loadmodule
            preg_match_all($regexmod, $item->text, $matchesmod, PREG_SET_ORDER);

            // If no matches, skip this
            if ($matchesmod) {
                foreach ($matchesmod as $matchmod) {
                    $matchesmodlist = explode(',', $matchmod[1]);

                    // First parameter is the module, will be prefixed with mod_ later
                    $module = trim($matchesmodlist[0]);

                    // Second parameter is the title
                    $title = '';

                    if (array_key_exists(1, $matchesmodlist)) {
                        $title = htmlspecialchars_decode(trim($matchesmodlist[1]));
                    }

                    if (array_key_exists(2, $matchesmodlist)) {
                        $stylemod = trim($matchesmodlist[2]);
                    }

                    $output = $this->loadModule($module, $title, $stylemod);

                    // We should replace only first occurrence in order to allow positions with the same name to regenerate their content:
                    if (($start = strpos($item->text, $matchmod[0])) !== false) {
                        $item->text = substr_replace($item->text, $output, $start, strlen($matchmod[0]));
                    }
                }
            }
        }

        if (str_contains($item->text, '{loadmoduleid ')) {
            // Find all instances of plugin and put in $matchesmodid for loadmoduleid
            preg_match_all($regexmodid, $item->text, $matchesmodid, PREG_SET_ORDER);

            // If no matches, skip this
            if ($matchesmodid) {
                foreach ($matchesmodid as $match) {
                    $id     = trim($match[1]);
                    $output = $this->loadID($id);

                    // We should replace only first occurrence in order to allow positions with the same name to regenerate their content:
                    if (($start = strpos($item->text, $match[0])) !== false) {
                        $item->text = substr_replace($item->text, $output, $start, strlen($match[0]));
                    }
                }
            }
        }
    }

    /**
     * Loads and renders the module
     *
     * @param   string  $position  The position assigned to the module
     * @param   string  $style     The style assigned to the module
     *
     * @return  mixed
     *
     * @since   5.4.3
     */
    private function load($position, $style = 'none')
    {
        $document = $this->getApplication()->getDocument();
        $renderer = $document->loadRenderer('module');
        $modules  = ModuleHelper::getModules($position);
        $params   = ['style' => $style];
        ob_start();

        foreach ($modules as $module) {
            echo $renderer->render($module, $params);
        }

        return ob_get_clean();
    }

    /**
     * This is always going to get the first instance of the module type unless
     * there is a title.
     *
     * @param   string  $module  The module title
     * @param   string  $title   The title of the module
     * @param   string  $style   The style of the module
     *
     * @return  mixed
     *
     * @since   5.4.3
     */
    private function loadModule($module, $title, $style = 'none')
    {
        $document = $this->getApplication()->getDocument();
        $renderer = $document->loadRenderer('module');
        $mod      = ModuleHelper::getModule($module, $title);

        // If the module without the mod_ isn't found, try it with mod_.
        // This allows people to enter it either way in the content
        if (!isset($mod)) {
            $name = 'mod_' . $module;
            $mod  = ModuleHelper::getModule($name, $title);
        }

        $params = ['style' => $style];
        ob_start();

        if ($mod->id) {
            echo $renderer->render($mod, $params);
        }

        return ob_get_clean();
    }

    /**
     * Loads and renders the module
     *
     * @param   string  $id  The id of the module
     *
     * @return  mixed
     *
     * @since   5.4.3
     */
    private function loadID($id)
    {
        $document = $this->getApplication()->getDocument();
        $renderer = $document->loadRenderer('module');
        $modules  = ModuleHelper::getModuleById($id);
        $params   = ['style' => 'none'];
        ob_start();

        if ($modules->id > 0) {
            echo $renderer->render($modules, $params);
        }

        return ob_get_clean();
    }
}
