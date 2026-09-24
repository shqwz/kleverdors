<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined ('_JEXEC') or die ('Restricted access');

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
    require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
}

use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\CMS\Filter\OutputFilter;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;

class SppagebuilderRouterBase
{
	private static $aliasFieldCache = [];
	private static $aliasCache = [];
	private static $collectionIdCache = [];

	public static function buildRoute(&$query)
	{
		$segments = array();
		/** @var CMSApplication */
		$app = Factory::getApplication();
		$menu = $app->getMenu();

		$version = new Version();
		$joomlaVersion = (float) $version->getShortVersion();

		if ($joomlaVersion >= 6 && isset($query['view']) && $query['view'] === 'form' && isset($query['layout']) && $query['layout'] === 'edit-iframe' && isset($query['lang']))
		{
			return $segments;
		}

		// We need a menu item.  Either the one specified in the query, or the current active one if none specified
		if (empty($query['Itemid']))
		{
			$menuItem = $menu->getActive();
		}
		else
		{
			$menuItem = $menu->getItem($query['Itemid']);
		}

		$menuItemGiven = !empty($query['Itemid']);

		// Check again
		if ($menuItemGiven && isset($menuItem) && $menuItem->component !== 'com_sppagebuilder')
		{
			$menuItemGiven = false;
			unset($query['Itemid']);
		}

		if (isset($query['view']) && $query['view'])
		{
			$view = $query['view'];
		}
		else
		{
			// We need to have a view in the query or it is an invalid URL
			return $segments;
		}

		if (($menuItem instanceof stdClass) && $menuItem->query['view'] === $query['view'] && isset($query['id']) && (int) $menuItem->query['id'] === (int) $query['id'])
		{
			unset($query['view']);
			unset($query['id']);

			return $segments;
		}

		if ($query['view'] === "page")
		{
			if (!$menuItemGiven) {
				$segments[] = $view;
				$segments[] = $query['id'] ?? 0;
			}
			unset($query['view']);
			unset($query['id']);
		}

		if ($view === 'dynamic')
		{
			$collectionItemId = $query['collection_item_id'] ?? [];

			if (!is_array($collectionItemId)) {
				$collectionItemId = [$collectionItemId];
			}

			$collectionType = $query['collection_type'] ?? 'normal-source';

			unset($query['collection_item_id']);
			unset($query['collection_type']); // Remove collection_type parameter
			
			// Always generate alias for SEF URLs, regardless of menu item
			$alias = static::getSlugsByCollectionItemIds($collectionItemId, $collectionType);
			if (!empty($alias)) {
				$segments = array_merge($segments, $alias);
			}
		}

		if (isset($query['view']) && $query['view'])
		{
			unset($query['view']);
		}

		if (isset($query['id']) && $query['id'])
		{
			$id = $query['id'];
			unset($query['id']);
		}

		if(isset($query['tmpl']) && $query['tmpl'])
		{
			unset($query['tmpl']);
		}

		if(isset($query['layout']) && $query['layout'])
		{
			$segments[] = $query['layout'];
			if(isset($id)) {
				$segments[] = $id;
			}
			unset($query['layout']);
		}

		return $segments;
	}

	private static function getCollectionTypeFromAlias($alias)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('alias')
			->from('#__content')
			->where('state = 1')
			->where('alias = ' . $db->quote($alias));
		$db->setQuery($query);
		$result = $db->loadResult();

		if ($result) {
			return 'articles';
		}

		return 'normal-source';
	}

	// Parse
	public static function parseRoute(&$segments)
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();
		$menu = $app->getMenu();
		$item = $menu->getActive();
		$vars = array();

		// Page
		if (count($segments) === 2 && $segments[0] === 'page')
		{
			$vars['view'] = $segments[0];
			$vars['id'] = (int) $segments[1];

			return $vars;
		}

		// Form
		if (count($segments) === 2 && $segments[0] === 'edit')
		{
			$vars['view'] = 'form';
			$vars['id'] = (int) $segments[1];
			$vars['tmpl'] = 'component';
			$vars['layout'] = 'edit';

			return $vars;
		}

		// determine collection type based on alias
		$collectionType = static::getCollectionTypeFromAlias($segments[0]);

		$collectionItemIds = static::getCollectionItemIdsFromSlugs($segments);

		$isValidCollectionPage = false;

		if (isset($collectionItemIds)) {
			$collectionArray = Arr::make($collectionItemIds);

			$isValidCollectionPage = $collectionArray->every(function ($element) {
				return !empty($element);
			});
		}

		// Dynamic Content
		if (!empty($item) && $item->query['option'] === 'com_sppagebuilder' && $item->query['view'] === 'page' && $isValidCollectionPage) {
			$vars['view'] = 'dynamic';

			if (!empty($collectionItemIds)) {
				foreach ($collectionItemIds as $collectionItemId) {
					$vars['collection_item_id'][] = $collectionItemId;
				}
			}

			$vars['collection_type'] = $collectionType;

			return $vars;
		}

		return $vars;
	}

	private static function getCollectionItemIdsFromSlugs($slugs)
	{
		if (empty($slugs)) {
			return [];
		}

		return Arr::make($slugs)->map(function ($slug) {
			return static::getCollectionItemIdFromSlug($slug);
		})->toArray();
	}

	private static function getCollectionItemIdFromSlug($slug)
	{
		try {
			$articleId = self::getArticleIdBySlug($slug);
			if ($articleId) {
				return $articleId;
			}
		} catch (\Exception $e) {
		}

		if (preg_match('/^article-(\d+)$/', $slug, $matches)) {
			return (int) $matches[1];
		}
				
		$aliasFields = CollectionField::where('type', FieldTypes::ALIAS)->get(['id']);
		$aliasFieldIds = Arr::make($aliasFields)->pluck('id')->toArray();
		
		if (!empty($aliasFieldIds)) {
			$aliasField = CollectionItemValue::whereIn('field_id', $aliasFieldIds)
				->where('value', $slug)
				->first(['item_id']);
			
			if (!$aliasField->isEmpty()) {
				return $aliasField->item_id ?? null;
			}
		}

		return null;
	}

	private static function getSlugsByCollectionItemIds($collectionItemIds, $collectionType = 'normal-source')
	{
		if (empty($collectionItemIds)) {
			return [];
		}

		if(empty(self::$aliasCache)){
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('value, field_id, item_id')
				->from('#__sppagebuilder_collection_item_values')
				->whereIn('field_id', $db->setQuery(
					$db->getQuery(true)
						->select('id')
						->from('#__sppagebuilder_collection_fields')
						->where('type = ' . $db->quote(FieldTypes::ALIAS))
				)->loadColumn());
			$db->setQuery($query);
			$aliasItems = $db->loadObjectList();
			
			foreach (Arr::make($aliasItems) as $element) { 
				$key = $element->item_id . '_' . $element->field_id;
				self::$aliasCache[$key] = $element;
			}
		}

		if(empty(self::$collectionIdCache)){
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('id, collection_id')
				->from('#__sppagebuilder_collection_items');
			$db->setQuery($query);
			$collectionItems = $db->loadObjectList(); 
			
			foreach (Arr::make($collectionItems) as $element) { 
				self::$collectionIdCache[$element->id] = $element->collection_id;
			}
		}

		return Arr::make($collectionItemIds)->map(function ($id) use ($collectionType) {
			return static::getItemAliasByCollectionItemId(static::getCollectionIdFromItemId($id, $collectionType), $id);
		})->toArray();
	}

	private static function getCollectionIdFromCache($itemId)
	{
		if (isset(self::$collectionIdCache[$itemId])) {
			return self::$collectionIdCache[$itemId];
		}

		return null;
	}

	private static function getCollectionIdFromItemId($itemId, $collectionType = 'normal-source')
	{
		if ($collectionType === 'articles') {
			return CollectionIds::ARTICLES_COLLECTION_ID;
		}
		
		if ($collectionType === 'tags') {
			return CollectionIds::TAGS_COLLECTION_ID;
		}
		
		$collectionId = self::getCollectionIdFromCache($itemId);

		if ($collectionId) {
			return $collectionId;
		}

		try {
			if (self::articleExists($itemId)) {
				return CollectionIds::ARTICLES_COLLECTION_ID;
			}
		} catch (\Exception $e) {
		}

		$db = \Joomla\CMS\Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__tags')
			->where('id = ' . (int) $itemId)
			->where('published = 1');
		$db->setQuery($query);
		$tagCount = $db->loadResult();
		
		if ($tagCount > 0) {
			return CollectionIds::TAGS_COLLECTION_ID;
		}

		return null;
	}

	private static function getAliasFieldFromCache($collectionId)
	{
		if (isset(self::$aliasFieldCache[$collectionId])) {
			return self::$aliasFieldCache[$collectionId];
		}

		$aliasField = CollectionField::where('collection_id', $collectionId)
			->where('type', FieldTypes::ALIAS)
			->first(['id']);

		if ($aliasField->isEmpty()) {
			self::$aliasFieldCache[$collectionId] = null;
			return null;
		}

		self::$aliasFieldCache[$collectionId] = $aliasField;
		return $aliasField;
	}

	private static function getItemAliasByCollectionItemId($collectionId, $collectionItemId)
	{
		if (empty($collectionId) || empty($collectionItemId)) {
			return null;
		}

		if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
			if (!\class_exists('SppagebuilderHelperArticles')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
			}

			try {
				$article = self::getArticleByCollectionItemId($collectionItemId);
				if ($article) {
					$alias = !empty($article->alias) ? $article->alias : OutputFilter::stringURLSafe($article->title);
					return $alias;
				}
			} catch (\Exception $e) {
				return 'article-' . $collectionItemId;
			}

			return 'article-' . $collectionItemId;
		}

		if ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
			try {
				$db = \Joomla\CMS\Factory::getDbo();
				$query = $db->getQuery(true)
					->select('alias, title')
					->from('#__tags')
					->where('id = ' . (int) $collectionItemId)
					->where('published = 1');
				$db->setQuery($query);
				$tag = $db->loadObject();
				
				if ($tag) {
					$alias = !empty($tag->alias) ? $tag->alias : OutputFilter::stringURLSafe($tag->title);
					return $alias;
				}
			} catch (\Exception $e) {
				return 'tag-' . $collectionItemId;
			}

			return 'tag-' . $collectionItemId;
		}

		$aliasField = self::getAliasFieldFromCache($collectionId);

		if ($aliasField->isEmpty()) {
			return null;
		}

		$alias = self::getAliasFromCache($collectionItemId, $aliasField->id);

		if (empty($alias)) {
			return null;
		}

		return $alias->value;
	}

	private static function getAliasFromCache($itemId, $fieldId)
	{
		$cacheKey = $itemId . '_' . $fieldId;

		if (isset(self::$aliasCache[$cacheKey])) {
			return self::$aliasCache[$cacheKey];
		}

		return null;
	}

	private static function getArticleByCollectionItemId($collectionItemId){
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('alias, title')
			->from('#__content')
			->where('id = ' . (int) $collectionItemId)
			->where('state = 1');
		$db->setQuery($query);
		return $db->loadObject();
	}

	private static function getArticleIdBySlug($slug){
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('id')
			->from('#__content')
			->where('state = 1')
			->where('alias = ' . $db->quote($slug));
		$db->setQuery($query);
		$articleId = $db->loadResult();
		
		if ($articleId) {
			return $articleId;
		}
		
		$query = $db->getQuery(true)
			->select('id, title')
			->from('#__content')
			->where('state = 1');
		$db->setQuery($query);
		$articles = $db->loadObjectList();
		
		foreach ($articles as $article) {
			if (OutputFilter::stringURLSafe($article->title ?? '') === $slug) {
				return $article->id;
			}
		}
		
		return null;
	}

	private static function articleExists($articleId){
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__content')
			->where('id = ' . (int) $articleId)
			->where('state = 1');
		$db->setQuery($query);
		return (bool) $db->loadResult();
	}
}

$version = new Version();
$JoomlaVersion = (float) $version->getShortVersion();

if ($JoomlaVersion >= 4)
{
	class SppagebuilderRouter extends Joomla\CMS\Component\Router\RouterBase
	{
		public function build(&$query)
		{
			$segments = SppagebuilderRouterBase::buildRoute($query);
			return $segments;
		}

		public function parse(&$segments)
		{
			$vars = SppagebuilderRouterBase::parseRoute($segments);

			if (count($vars))
			{
				$segments = array();
			}

			return $vars;
		}
	}
}

/**
 * Build the route for the com_banners component
 *
 * This function is a proxy for the new router interface
 * for old SEF extensions.
 *
 * @param   array  &$query  An array of URL arguments
 *
 * @return  array  The URL arguments to use to assemble the subsequent URL.
 *
 * @since   3.3
 * @deprecated  4.0  Use Class based routers instead
 */
function SppagebuilderBuildRoute(&$query)
{
	$segments = SppagebuilderRouterBase::buildRoute($query);

	return $segments;
}

/**
 * Parse the segments of a URL.
 *
 * This function is a proxy for the new router interface
 * for old SEF extensions.
 *
 * @param   array  $segments  The segments of the URL to parse.
 *
 * @return  array  The URL attributes to be used by the application.
 *
 * @since   3.3
 * @deprecated  4.0  Use Class based routers instead
 */
function SppagebuilderParseRoute(&$segments)
{
	$vars = SppagebuilderRouterBase::parseRoute($segments);

	return $vars;
}