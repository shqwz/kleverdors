<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use AddonParser;
use ApplicationHelper;
use DateTime;
use FieldsHelper;
use JLoader;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Menu;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;
use SppagebuilderHelperArticles;

class CollectionHelper
{
    /**
     * Cache for detail page IDs indexed by collection ID
     * 
     * @var array
     * 
     * @since 6.0.0
     */
    protected static $detailPageIdCache = [];

    /**
     * Cache for menu item IDs indexed by collection ID
     * 
     * @var array
     * 
     * @since 6.0.0
     */
    protected static $menuItemIdCache = [];

    /**
     * List of fields that are not prefixed with the collection title.
     *
     * @var array
     *
     * @since 5.5.0
     */
    public const NON_PREFIXED_FIELDS = [
        'created', 'modified', 'language', 'created_by', 'modified_by', 'published'
    ];

    /**
     * Get the selector from the CSS.
     *
     * @param string $css The CSS content.
     * @return string|null The selector or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getSelector($css)
    {
        $pattern = "/^(.*?)\{/";
        preg_match($pattern, $css, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Generate the CSS for the collection addon.
     *
     * @param object $addon The addon object.
     * @param object $layouts The layouts object.
     * @return array The generated CSS.
     *
     * @since 5.5.0
     */
    public static function generateDynamicContentCSS($addon, $layouts)
    {
        if (empty($addon->name))
        {
            return '';
        }

        $addonPath = AddonParser::getAddonPath($addon->name);
        $output = '';

        if (file_exists($addonPath . '/site.php'))
        {
            require_once $addonPath . '/site.php';

            $addonClassName = ApplicationHelper::generateSiteClassName($addon->name);
            $addonInstance = new $addonClassName($addon);

            $addonCss = $layouts->addon_css->render(array('addon' => $addon));

            if (method_exists($addonClassName, 'css'))
            {
                $css = $addonInstance->css();
                $addonSelector = static::getSelector($addonCss);
                $instanceSelector = static::getSelector($css);

                if (empty($addonSelector) || empty($instanceSelector)) {
                    return [];
                }

                return [
                    $addonSelector => $addonCss,
                    $instanceSelector => $css
                ];
            }
        }

        return $output;
    }

    /**
     * Get the detail page ID for the dynamic content collection.
     *
     * @param int $collectionId The collection ID.
     * @return int|null The detail page ID or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getDetailPageId($collectionId)
    {
        if (empty($collectionId)) {
            return null;
        }

        if (array_key_exists($collectionId, static::$detailPageIdCache)) {
            return static::$detailPageIdCache[$collectionId];
        }

        $page = Page::where('extension', 'com_sppagebuilder')
            ->where('extension_view', 'dynamic_content:detail')
            ->where('view_id', $collectionId)
            ->first(['id']);

        $pageId = $page->id ?? null;
        static::$detailPageIdCache[$collectionId] = $pageId;

        return $pageId;
    }

    /**
     * Create the route url for the dynamic content detail page.
     *
     * @param object $item The item to create the route url for.
     * @return string|null The route url or null if not found.
     *
     * @since 5.5.0
     */
    public static function createRouteUrl($item)
    {
        $collectionId = $item['collection_id'];
        $itemId = $item['id'];
        $pageId = static::getDetailPageId($collectionId);

        if (empty($collectionId) || empty($itemId) || empty($pageId)) {
            return null;
        }

        if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
            $menuItemId = static::getArticlesMenuItemId();
            $routeUrl = 'index.php?option=com_sppagebuilder&view=dynamic&collection_item_id=' . $itemId;
            $routeUrl .= '&collection_type=articles';
        } else if ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
            $menuItemId = static::getTagsMenuItemId();
            $routeUrl = 'index.php?option=com_sppagebuilder&view=dynamic&collection_item_id=' . $itemId;
            $routeUrl .= '&collection_type=tags';
        } else {
            $menuItemId = static::getCurrentMenuItemId($collectionId);
            $routeUrl = 'index.php?option=com_sppagebuilder&view=dynamic&collection_item_id=' . $itemId;
            $routeUrl .= '&collection_type=normal-source';
        }

        if (!empty($menuItemId)) {
            $routeUrl .= '&Itemid=' . $menuItemId;
        }

        return Route::_($routeUrl, false);
    }

    /**
     * Get the dynamic content data from the item.
     *
     * @param object $attribute The attribute to get the data from.
     * @param array $item The item to get the data from.
     * @return array|null The dynamic content data or null if not found.
     *
     * @since 5.5.0
     */
    public static function getDynamicContentData($attribute, $item)
    {
        if (empty($attribute) || empty($item)) {
            return null;
        }

        $path = $attribute->path ?? '';
        $segments = explode('.', $path);

        if (empty($segments) || !is_array($segments)) {
            $segments = [];
        }

        $value = $item;

        foreach ($segments as $segment) {
            $key = in_array($segment, static::NON_PREFIXED_FIELDS)
                ? $segment
                : CollectionItemsService::createFieldKey((int) $segment);

            if (is_array($value)) {
                if (!array_key_exists($key, $value)) {
                    $value = static::getReferenceValueByPath($value['id'], $segment);
                } else {
                    $value = $value[$key] ?? null;
                }
            }

            $value = is_object($value) ? (array) $value : $value;
        }

        if (is_array($value)) {
            if (array_key_exists('value', $value)) {
                $value = $value['value'];
            }
        }

        if (is_array($value) || is_object($value)) {
            return $value;
        }

        // Pick the value from the option store for the option type field.
        $optionStore = $item['option_store'] ?? [];

        if (isset($optionStore[$value])) {
            return $optionStore[$value];
        }

        return $value;
    }

    /**
     * When creating links for nested dynamic content fields, we need to get the parent object
     * that contains the actual link target, rather than the final field value.
     * 
     * For example, if we have a blog post with an author reference field:
     * {
     *     "id": 1,
     *     "field_1": "My Blog Post",              // title field
     *     "field_2": {                            // author reference field
     *         "id": 123,
     *         "field_3": "John Smith",            // author name field
     *         "field_4": "john@example.com"       // author email field
     *     }
     * }
     * 
     * And we want to link to the author's email (field_2.field_4), we need to return
     * the entire author object (field_2) as we need the author id to create the link to navigate there.
     * 
     * This method extracts the parent object by traversing the attribute path
     * and stopping at the second-to-last segment.
     *
     * @param array $item The item to prepare.
     * @param object $attribute The attribute to prepare the item for.
     * @return array|null The prepared item or null if the item is empty.
     *
     * @since 5.5.0
     */
    public static function prepareItemForLink($item, $attribute)
    {
        if (empty($item) || empty($attribute)) {
            return null;
        }

        $path = $attribute->path ?? '';
        $segments = explode('.', $path);

        if (empty($segments) || !is_array($segments)) {
            $segments = [];
        }

        // For single segment paths (direct fields), return the item itself
        if (count($segments) === 1) {
            return $item;
        }

        if (isset($item['collection_id']) && ($item['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID || $item['collection_id'] === CollectionIds::TAGS_COLLECTION_ID)) {
            return $item;
        }

        $value = $item;
        $length = count($segments);
        $segmentsUntilLast = array_slice($segments, 0, $length - 1);

        foreach ($segmentsUntilLast as $segment) {
            $key = in_array($segment, static::NON_PREFIXED_FIELDS)
                ? $segment
                : CollectionItemsService::createFieldKey((int) $segment);

            if (is_array($value)) {
                if (!array_key_exists($key, $value)) {
                    $value = static::getReferenceValueByPath($value['id'], $segment);

                    if (!empty($value['reference_item_id'])) {
                        $value = (new CollectionItemsService)->getCollectionItem($value['reference_item_id']);
                    }
                } else {
                    $value = $value[$key] ?? null;
                }
            }

            $value = is_object($value) ? (array) $value : $value;   
        }

        return $value;
    }

    /**
     * Get the collection item ID from the URL.
     *
     * @return int|null The collection item ID or null if not found.
     *
     * @since 5.5.0
     */
    public static function getCollectionItemIdFromUrl()
    {
        $input = Factory::getApplication()->input;
        $itemIds = $input->get('collection_item_id', [], 'ARRAY');

        if (empty($itemIds)) {
            return null;
        }

        return (int) $itemIds[count($itemIds) - 1];
    }

    public static function getDetailPageDataFromArticles()
    {
        $itemId = static::getCollectionItemIdFromUrl();
        if (!class_exists('SppagebuilderHelperArticles')) {
            require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
        }

        $articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
        $articles = \SppagebuilderHelperArticles::getArticles($articlesCount);

        $version = new Version();
        $JoomlaVersion = $version->getShortVersion();
        if ((float) $JoomlaVersion >= 4) {
            JLoader::registerAlias('FieldsHelper', 'Joomla\Component\Fields\Administrator\Helper\FieldsHelper');
        } else {
            JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
        }

        foreach ($articles as $article) {
            $custom_fields = FieldsHelper::getFields('com_content.article', $article);
            if ($article->id == $itemId) {

                $articleData = (array) $article;
                $articleData['collection_id'] = -2;
                

                $articleData['introtext'] = self::replaceFieldShortcodes($articleData['introtext'] ?? '', $custom_fields);
                $articleData['fulltext'] = self::replaceFieldShortcodes($articleData['fulltext'] ?? '', $custom_fields);
                $articleData['featured_image'] = $articleData['featured_image'] ?? '';
                $articleData['image_thumbnail'] = $articleData['image_thumbnail'] ?? $articleData['featured_image'] ?? '';
                $articleData['username'] = $articleData['username'] ?? '';
                $articleData['category'] = $articleData['category'] ?? '';
                
                return $articleData;
            }
        }
    }

    private static function replaceFieldShortcodes($text, $custom_fields) {
		$fieldMap = [];
		foreach ($custom_fields as $field) {
			if (isset($field->id)) {
				$fieldMap[$field->id] = (isset($field->value) && $field->value) ? $field->value : '';
			}
		}
        
		return preg_replace_callback('/\{field\s+(\d+)\}/', function($matches) use ($fieldMap) {
			$fieldId = $matches[1];
            $value = $fieldMap[$fieldId] ?? '';
			return isset($fieldMap[$fieldId]) ? $fieldMap[$fieldId] : '';
		}, $text);
	}

    /**
     * Get the detail page data
     * 
     * @return array|null The data or null if not found
     * @since 6.0.0
     */
    public static function getDetailPageDataFromTags()
    {
        $itemId = static::getCollectionItemIdFromUrl();
        

        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__tags')
            ->where('id = ' . (int) $itemId)
            ->where('published = 1');
        $db->setQuery($query);
        $tag = $db->loadObject();
        
        if ($tag) {

            $tagData = (array) $tag;
            $tagData['collection_id'] = CollectionIds::TAGS_COLLECTION_ID;
            

            $tagData['title'] = $tagData['title'] ?? '';
            $tagData['alias'] = $tagData['alias'] ?? '';
            $tagData['description'] = $tagData['description'] ?? '';
            
            return $tagData;
        }
        
        return null;
    }

    /**
     * Get the dynamic content item data from the database.
     *
     * @return array|null The dynamic content item data or null if not found.
     *
     * @since 5.5.0
     */
    public static function getDetailPageData()
    {
        $itemId = static::getCollectionItemIdFromUrl();

        if (empty($itemId)) {
            return null;
        }

        $service = new CollectionDataService();
        $item = $service->fetchCollectionItemById($itemId);

        return $item ?? null;
    }

    public static function getJoomlaSingleArticleRoute($item)
    {
        if (empty($item)) {
            return null;
        }
        return !empty($item['link']) ? $item['link'] : null;
    }

    /**
     * Create a dynamic content link.
     *
     * @param object $link The link object.
     * @param array $item The item to create the link for.
     * @return string|null The link or null if not found.
     *
     * @since 5.5.0
     */
    public static function createDynamicContentLink($link, $item)
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
                    return static::getJoomlaSingleArticleRoute($item);
                }
                $page = !empty($pageId)
                    ? Page::where('id', $pageId)->first(['extension_view', 'view_id', 'id'])
                    : null;

                if (empty($page) || $page->isEmpty()) {
                    return null;
                }

                if ($page->extension_view === 'dynamic_content:detail') {
                    $routeUrl = 'index.php?option=com_sppagebuilder&view=dynamic';

                    if ($page->view_id === CollectionIds::ARTICLES_COLLECTION_ID) {
                        $routeUrl = static::getJoomlaSingleArticleRoute($item);
                        return Route::_($routeUrl, false);
                    } else if ($page->view_id === CollectionIds::TAGS_COLLECTION_ID) {
                        $menuItemId = static::getTagsMenuItemId();
                        if (!empty($menuItemId)) {
                            $routeUrl .= '&Itemid=' . $menuItemId;
                        }
                        return Route::_(static::buildRouteWithTagItemId($routeUrl, $item['id']), false);
                    } else {
                        $menuItemId = static::getCurrentMenuItemId($page->view_id);
                        if (!empty($menuItemId)) {
                            $routeUrl .= '&Itemid=' . $menuItemId;
                        }
                        return Route::_(static::buildRouteWithCollectionItemId($routeUrl, $item['id']), false);
                    }
                }

                $routeUrl = 'index.php?option=com_sppagebuilder&view=page&id=' . $page->id;
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

    /**
     * Generate the link attributes.
     *
     * @param array $linkOptions The link options.
     * @return array The link attributes.
     *
     * @since 5.5.0
     */
    public static function generateLinkAttributes($linkOptions)
    {
        $url = $linkOptions['url'] ?? null;
        $target = $linkOptions['target'] ?? null;
        $nofollow = $linkOptions['nofollow'] ?? null;
        $noreferrer = $linkOptions['noreferrer'] ?? null;
        $noopener = $linkOptions['noopener'] ?? null;

        $attributes = [
            'href' => '',
            'target' => '',
            'rel' => '',
            'has_link' => false,
        ];

        if (!empty($url)) {
            $attributes['href'] = $url;
            $attributes['has_link'] = true;
        }

        if (!empty($target)) {
            $attributes['target'] = $target;
        }

        $rel = [];

        if (!empty($nofollow)) {
            $rel[] = 'nofollow';
        }

        if (!empty($noreferrer)) {
            $rel[] = 'noreferrer'; 
        }

        if (!empty($noopener)) {
            $rel[] = 'noopener';
        }

        if (!empty($rel)) {
            $attributes['rel'] = implode(' ', $rel);
        }

        return $attributes;
    }

    private static function phpToIcu(string $php): string
{
    $map = [
        'd' => 'dd', 'j' => 'd', 'D' => 'EEE', 'l' => 'EEEE',
        'm' => 'MM', 'n' => 'M', 'M' => 'MMM', 'F' => 'MMMM',
        'y' => 'yy', 'Y' => 'yyyy',
        'H' => 'HH', 'G' => 'H', 'h' => 'hh', 'g' => 'h',
        'i' => 'mm', 's' => 'ss',
        'A' => 'a', 'a' => 'a',
    ];
    return preg_replace_callback('/[djDlmnMFyYHGhgisAa]/', function($m) use ($map) {
        return $map[$m[0]] ?? $m[0];
    }, $php);
}

    /**
     * Format the date.
     *
     * @param string $date The date to format.
     * @param object $attribute The attribute to format the date for.
     * @return string|null The formatted date or null if not found.
     *
     * @since 5.5.0
     */
    public static function formatDate($date, $attribute)
    {
        $format = $attribute->date_format ?? 'd M, Y H:i A';
        if (empty($date) || empty($format)) {
            return $date;
        }

        if ($format === 'n-time-ago') {
            $date = Date::create($date);
            $now = Date::create('now');
            $interval = $now->diff($date);
            $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;

            if ($minutes === 0) {
                return Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_JUST_NOW');
            } elseif ($minutes < 60) {
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_MINUTES_AGO', $minutes);
            } elseif ($minutes < 1440) {
                $hours = floor($minutes / 60);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_HOURS_AGO', $hours);
            } elseif ($minutes < 10080) {
                $days = floor($minutes / 1440);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_DAYS_AGO', $days);
            } elseif ($minutes < 43200) {
                $weeks = floor($minutes / 10080);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_WEEKS_AGO', $weeks);
            } elseif ($minutes < 525600) {
                $months = floor($minutes / 43200);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_MONTHS_AGO', $months);
            } else {
                $years = floor($minutes / 525600);
                return Text::plural('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_YEARS_AGO', $years);
            }
        }

        $format = $format === 'custom' ? $attribute->date_format_custom : $format;
        
        $lang = Factory::getLanguage()->getTag();
        $lang = str_replace('-', '_', $lang);
        $lang .= '@numbers=native';
        
        $dateObj = new DateTime($date);
        
        if(!class_exists('IntlDateFormatter')) {
            return $dateObj->format($format);
        }
        
        $format = self::phpToIcu($format);
        $formatter = new \IntlDateFormatter(
            $lang,         
            \IntlDateFormatter::NONE,      
            \IntlDateFormatter::NONE,
            $dateObj->getTimezone()->getName(),
            \IntlDateFormatter::GREGORIAN,
            $format
        );
    
        return $formatter->format($dateObj);
    }

    /**
     * Check if the field has a circular reference.
     * Circular reference means that the field is referencing itself.
     *
     * @param int $fieldId The field ID.
     * @return bool True if the field has a circular reference, false otherwise.
     *
     * @since 5.5.0
     */
    public static function hasCircularReference($fieldId)
    {
        $field = CollectionField::where('id', $fieldId)->first(['collection_id', 'reference_collection_id']);

        if ($field->isEmpty()) {
            return false;
        }

        return $field->collection_id === $field->reference_collection_id;
    }

    /**
     * Get the first collection item ID.
     *
     * @param integer $collectionId The collection ID.
     * @return int|null The first collection item ID or null if not found.
     *
     * @since 5.5.0
     */
    public static function getFirstCollectionItemId(int $collectionId)
    {

        if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
            if (!\class_exists('SppagebuilderHelperArticles')) {
                require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
            }

            try {
                $articles = \SppagebuilderHelperArticles::getArticles(1, 'oldest');
                if (!empty($articles) && isset($articles[0])) {
                    return $articles[0]->id;
                }
            } catch (\Exception $e) {
                return null;
            }

            return null;
        }


        if ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
            try {
                $db = \Joomla\CMS\Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('id')
                    ->from('#__tags')
                    ->where('published = 1')
                    ->order('id ASC');
                $db->setQuery($query, 0, 1);
                $tagId = $db->loadResult();
                
                if ($tagId) {
                    return (int) $tagId;
                }
            } catch (\Exception $e) {
                return null;
            }

            return null;
        }

        // Handle regular collections
        $item = CollectionItem::where('collection_id', $collectionId)
            ->orderBy('id', 'ASC')
            ->first(['id']);

        if ($item->isEmpty()) {
            return null;
        }

        return $item->id;
    }

    /**
     * Prepare the image url for displaying.
     *
     * @param string $src The image source.
     * @return string|null The image URL or null if not found.
     *
     * @since 5.5.0
     */
    public static function getImageUrl($src)
    {
        if (empty($src)) {
            return null;
        }

        if (strpos($src, 'http') === 0) {
            return $src;
        }

        return Uri::root(true) . '/' . $src;
    }

    /**
     * Get the value by the path.
     *
     * @param int $itemId The item ID.
     * @param int $fieldId The field ID.
     * @return array|null The value or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getReferenceValueByPath($itemId, $fieldId)
    {
        $item = CollectionItemValue::where('item_id', $itemId)
            ->where('field_id', $fieldId)
            ->first(['value', 'reference_item_id']);

        if ($item->isEmpty()) {
            return null;
        }

        $item->id = $item->reference_item_id ?? null;
        return $item->toArray();
    }

    /**
     * Build the route with the item ID.
     *
     * @param string $url The URL to build the route for.
     * @param int $itemId The item ID to build the route for.
     * @return string|null The built route or null if not found.
     *
     * @since 6.0.0
     */
    protected static function buildRouteWithArticleItemId($url, $articleId)
    {
        $currentRoute = Uri::getInstance($url);
        

        $currentRoute->setVar('collection_item_id', [$articleId]);
        $currentRoute->setVar('collection_type', 'articles');
        
        return $currentRoute->toString();
    }

    /**
     * Build the route with the tag item ID.
     *
     * @param string $url The URL to build the route for.
     * @param int $tagId The tag ID to build the route for.
     * @return string|null The built route or null if not found.
     *
     * @since 6.0.0
     */
    public static function buildRouteWithTagItemId($url, $tagId)
    {
        $currentRoute = Uri::getInstance($url);
        $currentRoute->setVar('collection_item_id', [$tagId]);
        $currentRoute->setVar('collection_type', 'tags');
        return $currentRoute->toString();
    }

    /**
     * Build the route with the collection item ID.
     *
     * @param string $url The URL to build the route for.
     * @param int $itemId The item ID to build the route for.
     * @return string|null The built route or null if not found.
     *
     * @since 5.5.0
     */
    public static function buildRouteWithCollectionItemId($url, $itemId)
    {
        $currentRoute = Uri::getInstance($url);
        $app = Factory::getApplication();
        $input = $app->input;
        $itemIds = $input->get('collection_item_id', [], 'ARRAY');
        

        $collectionIdOfItemToPush = static::getCollectionIdOfCollectionItem($itemId);
        $collectionType = 'normal-source';
        
        if (empty($itemIds)) {
            $itemIds = [];
            $itemIds[] = $itemId;
            $currentRoute->setVar('collection_item_id', $itemIds);
            $currentRoute->setVar('collection_type', $collectionType);
            return $currentRoute->toString();
        }

        $lastItemId = $itemIds[count($itemIds) - 1];
        $itemIdToPush = $itemId;

        $collectionIdOfLastItem = static::getCollectionIdOfCollectionItem($lastItemId);

        if ($collectionIdOfLastItem !== $collectionIdOfItemToPush) {
            $itemIds[] = $itemIdToPush;
        } else {
            $itemIds[count($itemIds) - 1] = $itemIdToPush;
        }

        $currentRoute->setVar('collection_item_id', $itemIds);
        $currentRoute->setVar('collection_type', $collectionType);
        return $currentRoute->toString();
    }

    /**
     * Get the collection ID of the collection item.
     *
     * @param int $itemId The item ID.
     * @return int|null The collection ID or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getCollectionIdOfCollectionItem($itemId)
    {

        $item = CollectionItem::where('id', $itemId)->first(['collection_id']);

        if (!$item->isEmpty()) {
            return $item->collection_id;
        }

        return null;
    }

    /**
     * Get the menu item ID.
     *
     * @return int|null The menu item ID or null if not found.
     *
     * @since 6.0.0
     */
    protected static function getArticlesMenuItemId()
    {
        $pageId = Page::where(['extension_view' => 'dynamic_content:index', 'view_id' => CollectionIds::ARTICLES_COLLECTION_ID])->first(['id']);

        $pageId = !empty($pageId->id) ? $pageId->id : null;

        if (empty($pageId)) {
            return null;
        }

        $menuItems = Menu::whereLike('link', '%option=com_sppagebuilder&view=page&id=' . $pageId . '%')
            ->where('client_id', 0)
            ->where('published', 1)
            ->get(['link', 'id']);

        if (empty($menuItems)) {
            return null;
        }

        $app = Factory::getApplication();
        $menu = $app->getMenu();
        $activeMenuItem = $menu->getActive();
        $activeMenuItemId = $activeMenuItem->id ?? null;

        $menuItems = Arr::make($menuItems);
        $menuItem = $menuItems->find(function ($item) use ($activeMenuItemId) {
            $query = Uri::getInstance($item->link);
            if ($query->getVar('view') !== 'page') {
                return false;
            }
            $pageId = intval($query->getVar('id') ?? 0);
            $pageCollectionId = static::getCollectionIdFromPageId($pageId);

            return ($pageCollectionId === CollectionIds::ARTICLES_COLLECTION_ID) && ($item->id === $activeMenuItemId) ? $item->id : null;
        });

        return !empty($menuItem->id) ? $menuItem->id : null;
    }

    /**
     * Get the menu item ID.
     *
     * @return int|null The menu item ID or null if not found.
     *
     * @since 6.0.0
     */
    public static function getTagsMenuItemId()
    {
        $menuItems = Menu::whereLike('link', '%option=com_sppagebuilder%')
            ->where('client_id', 0)
            ->where('published', 1)
            ->get(['link', 'id']);

        if (empty($menuItems)) {
            return null;
        }

        $menuItems = Arr::make($menuItems);
        $menuItem = $menuItems->find(function ($item) {
            $query = Uri::getInstance($item->link);
            if ($query->getVar('view') !== 'page') {
                return false;
            }
            $pageId = intval($query->getVar('id') ?? 0);
            $pageCollectionId = static::getCollectionIdFromPageId($pageId);


            return $pageCollectionId === CollectionIds::TAGS_COLLECTION_ID;
        });

        if (empty($menuItem)) {
            /** @var CMSApplication */
            $app = Factory::getApplication();
            $input = $app->input;
            $itemId = $input->getInt('Itemid', 0);

            return $itemId ?: null;
        }

        return $menuItem->id;
    }

   /**
     * Get the current item ID from the URL.
     *
     * @param int $collectionId The collection ID.
     * @return int|null The current item ID or null if not found.
     *
     * @since 5.5.0
     */
    public static function getCurrentMenuItemId($collectionId)
    {
        if (array_key_exists($collectionId, static::$menuItemIdCache)) {
            return static::$menuItemIdCache[$collectionId];
        }

        $menuItems = Menu::whereLike('link', '%option=com_sppagebuilder%')
            ->where('client_id', 0)
            ->where('published', 1)
            ->get(['link', 'id']);

        if (empty($menuItems)) {
            static::$menuItemIdCache[$collectionId] = null;
            return null;
        }

        $menuItems = Arr::make($menuItems);
        $menuItem = $menuItems->find(function ($item) use ($collectionId) {
            $query = Uri::getInstance($item->link);
            if ($query->getVar('view') !== 'page') {
                return false;
            }
            $pageId = intval($query->getVar('id') ?? 0);
            $pageCollectionId = static::getCollectionIdFromPageId($pageId);

            if (empty($pageCollectionId)) {
                return false;
            }

            return $pageCollectionId === (int) $collectionId;
        });

        if (empty($menuItem)) {
            /** @var CMSApplication */
            $app = Factory::getApplication();
            $input = $app->input;
            $itemId = $input->getInt('Itemid', 0);

            $result = $itemId ?: null;
            static::$menuItemIdCache[$collectionId] = $result;
            return $result;
        }

        static::$menuItemIdCache[$collectionId] = $menuItem->id;
        return $menuItem->id;
    }

    /**
     * Get the collection ID from the page ID.
     *
     * @param int $pageId The page ID.
     * @return int|null The collection ID or null if not found.
     *
     * @since 5.5.0
     */
    protected static function getCollectionIdFromPageId($pageId)
    {
        $page = Page::where('id', $pageId)->first(['extension_view', 'view_id', 'id']);

        if ($page->isEmpty()) {
            return null;
        }

        return $page->view_id ?? null;
    }
}
