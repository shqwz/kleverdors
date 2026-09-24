<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Router\Route;

/**
 * SP Page Builder Association Helper
 *
 * @since  6.1.0
 */
abstract class SppagebuilderHelperAssociation extends Joomla\CMS\Association\AssociationExtensionHelper
{
    /**
     * Method to get the associations for a given item
     *
     * @param   integer  $id      Id of the item
     * @param   string   $view    Name of the view
     * @param   string   $layout  View layout
     *
     * @return  array   Array of associations for the item
     *
     * @since  6.1.0
     */
    public static function getAssociations($id = 0, $view = null, $layout = null)
    {
        $jinput    = Factory::getApplication()->getInput();
        $view      = $view ?? $jinput->get('view');
        $component = $jinput->getCmd('option');

        if (empty($id)) {
            $collectionItemId = $jinput->get('collection_item_id');
            if (is_array($collectionItemId)) {
                if (!empty($collectionItemId[0])) {
                    $id = $collectionItemId[0];
                } elseif (!empty($collectionItemId)) {
                    $id = array_values($collectionItemId)[0];
                }
            } elseif (!empty($collectionItemId)) {
                $id = $collectionItemId;
            }

            if (empty($id)) {
                $id = $jinput->getInt('id');
            }
        }

        if (!Associations::isEnabled() || !Multilanguage::isEnabled()) {
            return [];
        }

        if ($layout === null && $jinput->get('view') == $view && $component == 'com_sppagebuilder') {
            $layout = $jinput->get('layout', '', 'string');
        }

        if ($view === 'dynamic') {
            if ($id) {
                $user      = Factory::getUser();
                $groups    = implode(',', $user->getAuthorisedViewLevels());
                $db        = Factory::getDbo();
                $advClause = [];

                $advClause[] = 'c2.access IN (' . $groups . ')';

                $advClause[] = 'c2.language != ' . $db->quote(Factory::getLanguage()->getTag());

                if (!$user->authorise('core.edit.state', 'com_sppagebuilder') && !$user->authorise('core.edit', 'com_sppagebuilder')) {
                    $advClause[] = 'c2.published = 1';
                }

                $associations = Associations::getAssociations(
                    'com_sppagebuilder',
                    '#__sppagebuilder_collection_items',
                    'com_sppagebuilder.collection_item',
                    $id,
                    'id',
                    '',
                    '',
                    $advClause
                );

                $return = [];

                foreach ($associations as $tag => $item) {
                    $return[$tag] = self::getCollectionItemRoute($item->id, $item->language, $layout);
                }

                return $return;
            }
        }

        return [];
    }

    /**
     * Get the route for a collection item
     *
     * @param   integer  $id       The collection item ID
     * @param   string   $language The language code
     * @param   string   $layout   The layout
     *
     * @return  string  The route
     *
     * @since  6.1.0
     */
    protected static function getCollectionItemRoute($id, $language = null, $layout = null)
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $menus = $app->getMenu();
        $activeMenu = $menus->getActive();
        $itemid = !empty($activeMenu->id) ? $activeMenu->id : null;
            $route = 'index.php?option=com_sppagebuilder&view=dynamic&collection_item_id[0]=' . $id . '&collection_type=normal-source';

            if ($itemid) {
                $route .= '&Itemid=' . $itemid;
            }
            
            if ($language && $language !== '*') {
                $route .= '&lang=' . $language;
            }
    
            return Route::_($route);
    }
}
