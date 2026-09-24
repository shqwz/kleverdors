<?php
/**
 * @package     SP Page Builder Plugins
 * @subpackage  Finder.Sppagebuilder
 *
 * @copyright   Copyright (C) 2018 JoomShaper. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Version;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;

$version = new Version();
$JoomlaVersion = $version->getShortVersion();

if (version_compare($JoomlaVersion, '4.0.0', '>='))
{
	JLoader::registerAlias('FinderIndexerAdapter', 'Joomla\Component\Finder\Administrator\Indexer\Adapter');
	JLoader::registerAlias('FinderIndexerResult', 'Joomla\Component\Finder\Administrator\Indexer\Result');
}
else
{
	JLoader::register('FinderIndexerAdapter', JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php');
	JLoader::register('FinderIndexerResult', JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/result.php');
}

if(!class_exists('SppagebuilderHelperSite'))
{
	require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/helper.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
    require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
}

/**
 * Plugin class for Page Builder smart search finder plugin
 */
class PlgFinderSppagebuilder extends FinderIndexerAdapter
{
	/**
	 * The plugin context.
	 *
	 * @var		string	$context	The context.
	 * @since	1.0.0
	 */
	protected $context = 'Sppagebuilder';

	/**
	 * The extension name.
	 *
	 * @var		string	$extension	The extension name
	 * @since	1.0.0
	 */
	protected $extension = 'com_sppagebuilder';

	/**
	 * The sub layout to use when rendering the results.
	 *
	 * @var		string		$layout		The sub layout name.
	 * @since	1.0.0
	 */
	protected $layout = 'page';

	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var		string		$type_title		The indexing type
	 * @since	1.0.0
	 */
	protected $type_title = 'Page';

	/**
	 * The table name.
	 *
	 * @var		string		$table	The page builder table name.
	 */
	protected $table = '#__sppagebuilder';

	/**
	 * The field the published state is stored in.
	 *
	 * @var		string		$state_field	The state field name.
	 * @since	1.0.0
	 */
	protected $state_field = 'published';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var		bool	$autoloadLanguage	Auto loading language
	 * @since	1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to remove the link information for items that have been deleted.
	 */
	public function onFinderAfterDelete($context, $table)
	{
		if ($context === 'com_sppagebuilder.page')
		{
			$id = $table->id;
		}
		elseif ($context === 'com_finder.index')
		{
			$id = $table->link_id;
		}
		else
		{
			return true;
		}

		// Remove the items.
		return $this->remove($id);
	}

	/**
	 * Method to determine if the access level of an item changed.
	 */
	public function onFinderAfterSave($context, $row, $isNew)
	{
		if ($context === 'com_sppagebuilder.page')
		{
			if (!$isNew && $this->old_access != $row->access)
			{
				$this->itemAccessChange($row);
			}

			$this->reindex($row->id);
		}

		return true;
	}

	/**
	 * Method to reindex the link information for an item that has been saved.
	 * This event is fired before the data is actually saved so we are going
	 * to queue the item to be indexed later.
	 */
	public function onFinderBeforeSave($context, $row, $isNew)
	{
		if ($context === 'com_sppagebuilder.page')
		{
			if (!$isNew)
			{
				$this->checkItemAccess($row);
			}
		}

		return true;
	}

	/**
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished, archived, or unarchived from the list view.
	 */
	public function onFinderChangeState($context, $pks, $value)
	{
		if ($context === 'com_sppagebuilder.page')
		{
			$this->itemStateChange($pks, $value);
		}

		if ($context === 'com_plugins.plugin' && $value === 0)
		{
			$this->pluginDisable($pks);
		}
	}

	/**
	 * Method to index an item. The item must be a FinderIndexerResult object.
	 */
	protected function index( FinderIndexerResult $item, $format = 'html')
	{
		$item->setLanguage();

		// Check if the extension is enabled
		if (ComponentHelper::isEnabled($this->extension) === false)
		{
			return;
		}


		// Set the item context
		$item->context = 'com_sppagebuilder.page';

		$menuItem = self::getActiveMenu($item->id);

		if (empty($item->body) || $item->body === '[]')
		{
			$item->body = $item->text;
		}

		if( isset($item->extension_view ) && $item->extension_view == 'dynamic_content:detail')
		{
			$input = Factory::getApplication()->input;
			$viewId = $item->view_id;
			$itemIds = CollectionItemsService::fetchItemIdsByCollectionId($viewId);
			
			foreach($itemIds as $itemId)
			{
				$itemClone = clone $item;
				$input->set('collection_item_id', [$itemId]);

				$itemClone->summary = SppagebuilderHelperSite::getPrettyText($itemClone->body);
				$itemClone->body = SppagebuilderHelperSite::getPrettyText($itemClone->body);
				$linkObject = (object) ['type' => 'page', 'page' => $itemClone->id]; 
				$itemClone->url = $this->generateDynamicContentLink($linkObject, ['id' => $itemId]);
				$itemClone->route = $itemClone->url;
				$itemClone->path = $itemClone->route;

				if (isset($menuItem->title) && $menuItem->title)
				{
					$itemClone->title = $menuItem->title;
				} else {
					$itemClone->title = CollectionDataService::getItemTitleById($itemId, $viewId) ?? $itemClone->title;
				}

				$itemClone->addInstruction(Indexer::META_CONTEXT, 'user');
				$itemClone->addTaxonomy('Type', 'Page');
				$itemClone->addTaxonomy('Language', $itemClone->language);

				$this->indexer->index($itemClone);
			}

			return;

		} else {
			// Set the summary and the body from page builder settings object.
			$item->summary = SppagebuilderHelperSite::getPrettyText($item->body);
			$item->body = SppagebuilderHelperSite::getPrettyText($item->body);
		}

		

		$item->url = $this->getUrl($item->id, $this->extension, $this->layout);
		$link = 'index.php?option=com_sppagebuilder&view=page&id=' . $item->id;

		if ($item->language && $item->language !== '*' && Multilanguage::isEnabled())
		{
			$link .= '&lang=' . $item->language;
		}

		if (isset($menuItem->id) && $menuItem->id)
		{
			$link .= '&Itemid=' . $menuItem->id;
		}

		$item->route = $link;
		$item->path = $item->route;

		if (isset($menuItem->title) && $menuItem->title)
		{
			$item->title = $menuItem->title;
		}

		// Handle the page author data.
		$item->addInstruction(Indexer::META_CONTEXT, 'user');

		// Add the type taxonomy data.
		$item->addTaxonomy('Type', 'Page');

		// Add the language taxonomy data.
		$item->addTaxonomy('Language', $item->language);

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to setup the indexer to be run.
	 */
	protected function setup()
	{
		JLoader::register('SppagebuilderRouter', JPATH_SITE . '/components/com_sppagebuilder/route.php');

		return true;
	}

	/**
	 * Method to get the SQL query used to retrieve the list of page items.
	 */
	protected function getListQuery($query = null)
	{
		$db = Factory::getDbo();

		// Check if we can use the supplied SQL query.
		$query = $query instanceof JDatabaseQuery ? $query : $db->getQuery(true)
			->select('a.id, a.view_id, a.title AS title, a.content AS body, a.text, a.created_on AS start_date')
			->select('a.created_by, a.modified, a.modified_by, a.language')
			->select('a.access, a.catid, a.extension, a.extension_view, a.published AS state, a.ordering')

			->select('u.name')
			->from('#__sppagebuilder AS a')
			->join('LEFT', '#__users AS u ON u.id = a.created_by')
			->where($db->quoteName('a.extension') . ' = '  . $db->quote('com_sppagebuilder'))
			->where($db->quoteName('published') . ' = '. $db->quote('1'))
			->where($db->quoteName('a.extension_view') . ' != '  . $db->quote('popup'));


		return $query;
	}

	public static function getActiveMenu($pageId) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('title, id'));
		$query->from($db->quoteName('#__menu'));
		$query->where($db->quoteName('link') . ' LIKE '. $db->quote('%option=com_sppagebuilder&view=page&id='. $pageId .'%'));
		$query->where($db->quoteName('published') . ' = '. $db->quote('1'));
		$db->setQuery($query);
		$item = $db->loadObject();

		return $item;
	}

	private function generateDynamicContentLink($link, $item)
    {
        if (empty($link) || empty($item)) {
            return null;
        }

        $linkType = $link->type ?? null;

        if (empty($linkType)) {
            return null;
        }

        switch ($linkType) {
            case 'page':
                $pageId = $link->page ?? null;
                if ($pageId === CollectionIds::ARTICLES_COLLECTION_ID) {
                    return CollectionHelper::getJoomlaSingleArticleRoute($item);
                }
                $page = !empty($pageId)
                    ? Page::where('id', $pageId)->first(['extension_view', 'view_id', 'id'])
                    : null;

                if (empty($page) || $page->isEmpty()) {
                    return null;
                }

                if ($page->extension_view === 'dynamic_content:detail') {
                    $routeUrl = '/index.php?option=com_sppagebuilder&view=dynamic';

                    if ($page->view_id === CollectionIds::ARTICLES_COLLECTION_ID) {
                        $routeUrl = CollectionHelper::getJoomlaSingleArticleRoute($item);
                        return Route::_($routeUrl, false);
                    } else if ($page->view_id === CollectionIds::TAGS_COLLECTION_ID) {
                        $menuItemId = CollectionHelper::getTagsMenuItemId();
                        if (!empty($menuItemId)) {
                            $routeUrl .= '&Itemid=' . $menuItemId;
                        }
                        return Route::_(CollectionHelper::buildRouteWithTagItemId($routeUrl, $item['id']), false);
                    } else {
                        $menuItemId = CollectionHelper::getCurrentMenuItemId($page->view_id);
                        if (!empty($menuItemId)) {
                            $routeUrl .= '&Itemid=' . $menuItemId;
                        }
                        return Route::_(CollectionHelper::buildRouteWithCollectionItemId($routeUrl, $item['id']), false);
                    }
                }

                $routeUrl = '/index.php?option=com_sppagebuilder&view=page&id=' . $page->id;
                return Route::_($routeUrl, false);
            case 'url':
                return $link->url ?? null;
            case 'menu':
                return Route::_($link->menu, false);
            case 'popup': // Implement popup link later
            default:
                return null;
        }
    }
}
