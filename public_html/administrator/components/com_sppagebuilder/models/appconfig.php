<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;

/**
 * AppConfig Model Class for managing app configs.
 * 
 * @version 4.1.0
 */
class SppagebuilderModelAppconfig extends ListModel
{
	/**
	 * Media __construct function
	 * 
	 * @param mixed $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
	}

	/**
	 * Get the page list.
	 * 
	 * @return array
	 * @since 5.5.0
	 */
	public function getPageList()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$pageTypes = array_map(
			[$db, 'quote'],
			[
				Page::PAGE_TYPE_REGULAR,
				Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX,
				Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL
			]
		);

		$query->select(['id', 'title', 'extension_view', 'view_id'])
			->from($db->quoteName('#__sppagebuilder'))
			->where($db->quoteName('extension_view') . 'IN (' . implode(',', $pageTypes) . ')')
			->where($db->quoteName('published') . ' = 1')
			->order($db->quoteName('title') . ' ASC');

		$db->setQuery($query);

		try
		{
			$pages = $db->loadObjectList();

			$pages = Arr::make($pages);
			$pages = $pages->reduce(function($carry, $current) {
				if (in_array($current->extension_view, [Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX, Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL]) && $current->view_id === CollectionIds::ARTICLES_COLLECTION_ID) {
					$carry['articles'] ??= [
						'label' => Text::_('COM_SPPAGEBUILDER_PAGE_TYPE_ARTICLES'),
						'icon' => 'articles',
						'options' => []
					];
					$carry['articles']['options'][] = $current;
				} elseif (in_array($current->extension_view, [Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX, Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL])) {
					$carry['cms'] ??= [
						'label' => Text::_('COM_SPPAGEBUILDER_PAGE_TYPE_DYNAMIC_CONTENT'),
						'icon' => 'dynamicContent',
						'options' => []
					];
					$carry['cms']['options'][] = $current;
				} elseif ($current->extension_view === Page::PAGE_TYPE_REGULAR) {
					$carry['page'] ??= [
						'label' => Text::_('COM_SPPAGEBUILDER_PAGE_TYPE_PAGES'),
						'icon' => 'pages',
						'options' => []
					];
					$carry['page']['options'][] = $current;
				} else {
					$carry[$current->extension_view] ??= [
						'label' => ucfirst($current->extension_view),
						'options' => []
					];
					$carry[$current->extension_view]['options'][] = $current;
				}
				return $carry;
			}, []);

			$pages = $pages->map(function ($page) {
				$items = Arr::make($page['options']);
				$page['options'] = $items->map(function ($item) {
					if ($item->extension_view === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX) {
						$collection = ($item->view_id === CollectionIds::ARTICLES_COLLECTION_ID || $item->view_id === CollectionIds::TAGS_COLLECTION_ID) ? null : Collection::find($item->view_id);
						$item->legend = '/' . ($collection ? $collection->alias : ($item->view_id === CollectionIds::TAGS_COLLECTION_ID ? 'tags' : 'articles'));
					} elseif ($item->extension_view === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL) {
						$collection = ($item->view_id === CollectionIds::ARTICLES_COLLECTION_ID || $item->view_id === CollectionIds::TAGS_COLLECTION_ID) ? null : Collection::find($item->view_id);
						$item->legend = '/' . ($collection ? $collection->alias : ($item->view_id === CollectionIds::TAGS_COLLECTION_ID ? 'tags' : 'articles')) . '/:slug';
					}
					return (object) [
						'label' => $item->title,
						'value' => $item->id,
						'legend' => $item->legend ?? ''
					];
				})->toArray();
				return $page;
			});

			return $pages->toArray();
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	public function getPopupList(){
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(['id', 'title'])
			->from($db->quoteName('#__sppagebuilder'))
			->where($db->quoteName('extension_view') . '=' . $db->quote('popup'))
			->where($db->quoteName('published') . ' = 1')
			->order($db->quoteName('title') . ' ASC');

		$db->setQuery($query);

		try
		{
			return $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	public function getEasyStoreCategories()
	{
		if (!ComponentHelper::isEnabled('com_easystore'))
		{
			return [];
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('DISTINCT a.id, a.title, a.level, a.published, a.lft');
		$subQuery = $db->getQuery(true)
			->select('id, title, level, published, parent_id, lft, rgt')
			->from('#__easystore_categories')
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('id') . ' > 1');

		$query->from('(' . $subQuery->__toString() . ') AS a')
			->join('LEFT', $db->quoteName('#__easystore_categories') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt');
		$query->order('a.lft ASC');

		$db->setQuery($query);
		$categories = $db->loadObjectList();

		$easystoreCategories = [['value' => '', 'label' => Text::_('COM_SPPAGEBUILDER_ADDON_EASYSTORE_ALL_CAT')]];

		if (!empty($categories))
		{
			foreach ($categories as $category)
			{
				$value = (object) [
					'value' => $category->id,
					'label' => str_repeat('- ', ($category->level)) . $category->title
				];

				$easystoreCategories[] = $value;
			}
		}

		return $easystoreCategories;
	}

	public function getCategories()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(['id', 'title', 'level', 'lft'])
			->from($db->quoteName('#__categories'))
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('extension') . ' = ' . $db->quote('com_sppagebuilder'))
			->order($db->quoteName('lft') . ' ASC');

		$db->setQuery($query);

		try
		{
			return $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	/**
	 * Plugin Model function
	 * This function takes the type of plugin we want to query for in the database, then queries in the db and returns the object
	 * @param  string $pluginsType type of the plugins we want to get
	 * @return mixed
	 * @since  5.3.6
	 */

	public function getPlugins(string $pluginsType)
	{
		
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		
		$query->select(['name', 'element'])
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote($pluginsType))
			->where($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('client_id') . ' = 0');

		$db->setQuery($query);

		try
		{
			return $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	public function getMenus()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(['id', 'title', 'link', 'language'])
			->from($db->quoteName('#__menu'))
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('id') . ' > 1')
			->where($db->quoteName('client_id') . ' = 0')
			->order($db->quoteName('title') . ' ASC');

		$db->setQuery($query);
		$menuItems = [];

		try
		{
			$menuItems = $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!empty($menuItems))
		{
			foreach ($menuItems as &$item)
			{
				$langCode = Multilanguage::isEnabled() ? '&lang=' . $item->language : '';
				$langValue = $item->language == '*' ? Text::_('JALL') : $item->language;
				$item->id = $item->link . '&Itemid=' . $item->id . $langCode;
				$item->title = $item->title . ' (' . $langValue . ')';
				unset($item->link);
			}

			unset($item);
		}

		return $menuItems;
	}

	public function getModules()
	{
		return [];
	}

	public function getAccessLevels()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select(['id', 'title'])
			->from($db->quoteName('#__viewlevels'))
			->order($db->quoteName('ordering') . ' ASC');

		$db->setQuery($query);

		try
		{
			return $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	public function getLanguages()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select([$db->quoteName('lang_code', 'id'), 'title'])
			->from($db->quoteName('#__languages'))
			->where($db->quoteName('published') . ' = 1')
			->order($db->quoteName('ordering') . ' ASC');

		$db->setQuery($query);

		try
		{
			return $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	public function getUserPermissions($pageId = 0)
	{
		$user = Factory::getUser();

		if (!$user->id)
		{
			return [
				'admin' => false,
				'manage' => false,
				'create' => false,
				'edit' => false,
				'edit_state' => false,
				'edit_own' => false,
				'delete' => false,
				'page' => [
					'edit' => false,
					'delete' => false,
					'edit_state' => false,
					'menu_access' => false
				]
			];
		}

		$isAdmin = $user->authorise('core.admin', 'com_sppagebuilder');
		$canManage = $user->authorise('core.manage', 'com_sppagebuilder');
		$canCreate = $user->authorise('core.create', 'com_sppagebuilder');
		$canEdit = $user->authorise('core.edit', 'com_sppagebuilder');
		$canEditState = $user->authorise('core.edit.state', 'com_sppagebuilder');
		$canEditOwn = $user->authorise('core.edit.own', 'com_sppagebuilder');
		$canDelete = $user->authorise('core.delete', 'com_sppagebuilder');
		$hasMenuAccess = $user->authorise('core.create', 'com_menus') || $user->authorise('core.edit', 'com_menus');

		$canEditPage = $user->authorise('core.edit', 'com_sppagebuilder.page.' . $pageId);
		$canDeletePage = $user->authorise('core.delete', 'com_sppagebuilder.page.' . $pageId);
		$canEditStatePage = $user->authorise('core.edit.state', 'com_sppagebuilder.page.' . $pageId);

		return [
			'admin' => $isAdmin,
			'manage' => $canManage,
			'create' => $canCreate,
			'edit' => $canEdit,
			'edit_state' => $canEditState,
			'edit_own' => $canEditOwn,
			'delete' => $canDelete,
			'page' => [
				'edit' => $canEditPage,
				'delete' => $canDeletePage,
				'edit_state' => $canEditStatePage,
				'menu_access' => $hasMenuAccess
			]
		];
	}

	public function getCollections()
	{
		$collections = Collection::all();
		if (empty($collections)) {
			return [];
		}

		$collections = Arr::make($collections);

		return $collections->reduce(function ($carry, $collection) {
			$carry[$collection->id] = $collection->title;
			return $carry;
		}, [])->toArray();
	}
}
