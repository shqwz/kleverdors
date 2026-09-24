<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use AddonParser;
use FieldsHelper;
use JLoader;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;

class CollectionRenderer
{
    /**
     * Store the collection data.
     *
     * @var CollectionData
     * @since 5.5.0
     */
    protected $data;

    /**
     * Store the layouts.
     *
     * @var object
     * @since 5.5.0
     */
    protected $layouts = [];

    /**
     * Store the page name.
     *
     * @var string
     * @since 5.5.0
     */
    protected $pageName = 'none';

    /**
     * Store the addon object.
     *
     * @var object
     * @since 5.5.0
     */
    protected $addon;

    /**
     * Store the filters.
     *
     * @var array
     * @since 5.5.0
     */
    protected $filters = [];

    /**
     * Store the CSS content.
     *
     * @var array
     * @since 5.5.0
     */
    protected static $cssContent = [];

    /**
     * Initialize the CollectionRenderer.
     *
     * @param object $addon The addon object.
     * @since 5.5.0
     */
    public function __construct($addon)
    {
        $this->addon = $addon;
        $layoutPath = JPATH_ROOT . '/components/com_sppagebuilder/layouts';
        $this->layouts = (object) [
            'row_start' => new FileLayout('row.start', $layoutPath),
            'row_end'   => new FileLayout('row.end', $layoutPath),
            'row_css'   => new FileLayout('row.css', $layoutPath),
            'column_start' => new FileLayout('column.start', $layoutPath),
            'column_end'   => new FileLayout('column.end', $layoutPath),
            'column_css'   => new FileLayout('column.css', $layoutPath),
            'addon_start' => new FileLayout('addon.start', $layoutPath),
            'addon_end'   => new FileLayout('addon.end', $layoutPath),
            'addon_css'   => new FileLayout('addon.css', $layoutPath),
        ];

        $this->pageName  = 'none';
    }

    /**
     * Set the data object
     *
     * @param CollectionData $data The data object
     * 
     * @since 5.5.0
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the data object
     *
     * @return CollectionData
     * 
     * @since 5.5.0
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the data array
     *
     * @return array The data array
     * 
     * @since 5.5.0
     */
    public function getDataArray()
    {
        return $this->data->getData();
    }

    public function collectPaths($nodes)
    {
        $paths = [];

        foreach ($nodes as $node) {
            $rawPath = $node->settings->attribute->path ?? null;

            if (!empty($rawPath)) {
                $paths[] = $rawPath;
            }

            if (!empty($node->child_nodes)) {
                $childPaths = $this->collectPaths($node->child_nodes);
                $paths = array_merge($paths, $childPaths);
            }
        }

        return $paths;
    }

    /**
     * Fetch items with proper data structure
     *
     * @param int $limit The maximum number of items to fetch
     * @param string $direction The ordering direction (ASC/DESC)
     * @return array Array of items
     * 
     * @since 6.0.0
     */
    protected function fetchArticleItems($limit, $direction)
    {
        if (!\class_exists('SppagebuilderHelperArticles')) {
            require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
        }

        try { 
            $app = Factory::getApplication();
            $input = $app->input;
            $option = $input->get('option', 'com_content', 'string');
            $view = $input->get('view', '', 'string');
            $catId = '';
            $state = 1;

            if ($option === 'com_content' && ($view === 'category' || $view === 'featured' || $view === 'archive')) {
                $catId = $input->get('id', '', 'string');
            }

            $ordering = $direction === 'desc' ? 'latest' : 'oldest';

            if ($view === 'featured') {
                $ordering = 'featured';
            } else if ($view === 'archive') {
                $state = 2;
            }

            $articles = \SppagebuilderHelperArticles::getArticles($limit, $ordering, $catId, true, '', [], $state);

            $version = new Version();
            $JoomlaVersion = $version->getShortVersion();
            if ((float) $JoomlaVersion >= 4) {
                JLoader::registerAlias('FieldsHelper', 'Joomla\Component\Fields\Administrator\Helper\FieldsHelper');
            } else {
                JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
            }
            

            return array_map(function ($article) {
                $custom_fields = FieldsHelper::getFields('com_content.article', $article);
                $article->collection_id = CollectionIds::ARTICLES_COLLECTION_ID;
                $article->introtext = $this->replaceFieldShortcodes($article->introtext ?? '', $custom_fields);
                $article->fulltext = $this->replaceFieldShortcodes($article->fulltext ?? '', $custom_fields);
                $article->featured_image = $article->featured_image ?? '';
                $article->image_thumbnail = $article->image_thumbnail ?? $article->featured_image ?? '';
                $article->username = $article->username ?? '';
                $article->category = $article->category ?? '';
                $article->link = $article->link ?? '';
                return (array) $article;
            }, $articles);
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error fetching items for dynamic content: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    private function replaceFieldShortcodes($text, $custom_fields) {
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
     * Fetch items with proper data structure
     *
     * @param int $limit The maximum number of items to fetch
     * @param string $direction The ordering direction (ASC/DESC)
     * @return array Array of items
     * 
     * @since 6.0.0
     */
    protected function fetchTagItems($limit, $direction)
    {
        try {

            $db = \Joomla\CMS\Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__tags')
                ->where('published = 1')
                ->order('title ' . $direction);
            $db->setQuery($query, 0, $limit);
            $tags = $db->loadObjectList();
            

            return array_map(function ($tag) {
                $tag->collection_id = CollectionIds::TAGS_COLLECTION_ID;
                $tag->title = $tag->title ?? '';
                $tag->alias = $tag->alias ?? '';
                $tag->description = $tag->description ?? '';
                return (array) $tag;
            }, $tags);
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error fetching items for dynamic content: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Fetch data based on source
     *
     * @param int $source The source type
     * @param int $limit The maximum number of items to fetch
     * @param string $direction The ordering direction (ASC/DESC)
     * @return array Array of items
     * 
     * @since 6.0.0
     */
    protected function fetchArticlesOrTagsData($source, $limit, $direction)
    {
        if ($source === CollectionIds::TAGS_COLLECTION_ID) {
            return $this->fetchTagItems($limit, $direction);
        }
        

        return $this->fetchArticleItems($limit, $direction);
    }

    /**
     * Render the collection addon
     *
     * @param array $data The data to be rendered
     * @param object $addon The addon object containing settings and filters
     * @param object $layouts The layouts object containing the layout files
     * @param string $pageName The name of the page
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderCollectionAddon($data, $addon)
    {
        $childNodes = isset($addon->child_nodes) ? $addon->child_nodes : [];
        $id = 'sppb-dynamic-content-' . $addon->id;
        $noRecordsMessage = $addon->settings->no_records_message ?? Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS');
        $noRecordsDescription = $addon->settings->no_records_description ?? null;
        $class = $addon->settings->class ?? '';

        if (empty($data)) {
            $output = '<div class="sppb-dynamic-content-collection '. $class . '" id="' . $id . '">';
            $output .= '<div class="sppb-dynamic-content-no-records">';
            $output .= '<h4>' . $noRecordsMessage . '</h4>';

            if ($noRecordsDescription) {
                $output .= '<p>' . $noRecordsDescription . '</p>';
            }

            $output .= '</div>';
            $output .= '</div>';
            return $output;
        }


        $output = '<div class="sppb-dynamic-content-collection '. $class .'" id="' . $id . '">';

        foreach ($data as $index => $item) {
            $output .= $this->renderCollectionItem($childNodes, $item, $index);
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Render the individual collection item
     *
     * @param array $childNodes The child nodes of the collection item
     * @param array $item The item to be rendered
     * @param int $index The index of the item
     * @return string The rendered collection item
     * 
     * @since 5.5.0
     */
    public function renderCollectionItem($childNodes, $item, $index)
    {
        $output = '<div class="sppb-dynamic-content-collection__item">';

        foreach ($childNodes as $childNode) {
            if (empty((int) $childNode->visibility)) {
                continue;
            }

            if (!AddonParser::checkAddonACL($childNode)) {
                continue;
            }

            if ($childNode->name === 'dynamic_content_collection') {
                $newData = $this->getChildCollectionData($childNode, $item);
                $output .= $this->renderChildCollectionAddon($newData, $childNode, $this->layouts);
            } elseif ($childNode->name === 'div') {
                // Convey the dynamic item to the child node
                $childNode->settings->dynamic_item = $item;
                $output .= AddonParser::getDivHTMLViewForDynamicContent(
                    $childNode,
                    $this->layouts,
                    $this->pageName,
                    function($collectionAddon) use ($item) {
                        $newData = $this->getChildCollectionData($collectionAddon, $item);
                        return $this->renderChildCollectionAddon($newData, $collectionAddon, $this->layouts);
                    },
                    $index
                );
            } else {
                // Convey the dynamic item to the child node
                $childNode->settings->dynamic_item = $item;
                $output .= $this->renderChildNodeAddon($childNode, $this->layouts, $this->pageName, $index);
            }
        }

        $link = $this->addon->settings->link ?? null;
        $linkUrl = CollectionHelper::createDynamicContentLink($link, $item);
        $hasLink = !empty($linkUrl);

        if ($hasLink) {
            $app = Factory::getApplication();
            $option = $app->input->get('option', '', 'string');
            $view = $app->input->get('view', '', 'string');
            if ($option === 'com_content' && ($view === 'category' || $view === 'archive' || $view === 'featured' || $view === 'article') && !empty($item['link'])) {
                $linkUrl = $item['link'];
            }
            $output .= '<a href="' . $linkUrl . '" class="sppb-dynamic-content-collection__item-link" data-instant data-preload-collection data-preload-url="' . $linkUrl . '"></a>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Get the data for the child collection (a collection addon inside a collection addon)
     * 
     * This method handles two scenarios:
     * 1. With reference filters: Filters data based on the parent item using either "match all" 
     *    or "match any" conditions. The filtered data is then further processed with regular filters.
     *    If a negative limit is provided, it slices the reference filtered data.
     * 
     * 2. Without reference filters: Loads data directly from the source with the specified limit,
     *    then applies regular filters only.
     *
     * @param object $addon The addon object containing settings and filters
     * @param array $item The parent collection item used for reference filtering
     * @return array The filtered collection data
     * 
     * @since 5.5.0
     */
    public function getChildCollectionData($addon, $item)
    {
        $limit = $addon->settings->limit ?? 20;
        $direction = $addon->settings->direction ?? 'ASC';
        [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($addon->settings->filters);

        $collectionId = $addon->settings->source ?? null;
        $source = $addon->settings->source ?? -1;

        if ($source === CollectionIds::ARTICLES_COLLECTION_ID || $source === CollectionIds::TAGS_COLLECTION_ID) {
            $articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
            $items = $this->fetchArticlesOrTagsData($source, $articlesCount, $direction);
            
            $filteredData = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyArticleOrTagsFilter($source, $item, $addon->settings->filters)
                ->getData();
            
            return $filteredData;
        }

        $collectionFields = (new CollectionsService)->fetchCollectionFields($collectionId ?? -1);

        $allPaths = array_map(function ($item) {
            return CollectionItemsService::createFieldKey($item['path']);
        }, array_filter($collectionFields, function ($item) {
            return $item['type'] !== 'self';
        }));

        $path = $this->collectPaths($this->addon->child_nodes);

        if ($hasReferenceFilters) {
            $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($item, $referenceFilters, $direction);

            // Apply the regular filters to the reference filtered data
            $newData = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyFilters($regularFilters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths)
                ->getData();
        } else {
            $parentItem = CollectionHelper::getDetailPageData();
            $newData = (new CollectionData())
                ->setLimit($limit)
                ->setDirection($direction)
                ->setCurrentItemId($item['id'])
                ->loadDataBySource($addon->settings->source)
                ->setParentItem($parentItem ?? null)
                ->applyFilters($addon->settings->filters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths)
                ->getData();
        }

        return $newData;
    }

    /**
     * Render the content of the collection addon that placed inside a collection addon
     *
     * @param array     $data       The data to be rendered
     * @param object    $addon      The addon object containing settings and filters
     * @param object    $layouts    The layouts object containing the layout files
     * @param string    $pageName   The name of the page
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderChildCollectionAddon($data, $addon, $layouts)
    {
        $output = $layouts->addon_start->render(array('addon' => $addon));
        $output .= $this->renderCollectionAddon($data, $addon);
        $output .= $layouts->addon_end->render(array('addon' => $addon));
        $css = CollectionHelper::generateDynamicContentCSS($addon, $layouts);

        foreach ($css as $key => $value) {
            static::$cssContent[$key] = $value;
        }

        return $output;
    }

    /**
     * Render the regular child addons. This addons will skip the child collection addons and div addons.
     *
     * @param object    $addon      The addon object containing settings and filters
     * @param object    $layouts    The layouts object containing the layout files
     * @param string    $pageName   The name of the page
     * @param int       $index      The index of the item
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderChildNodeAddon($addon, $layouts, $pageName, $index)
    {
        return AddonParser::getAddonHTMLView($addon, $layouts, $pageName, false, [], $index, false);
    }

    /**
     * Render the collection addon.
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function render()
    {
        $settings = $this->addon->settings;
        $collectionId = $settings->source ?? null;
        $filters = $settings->filters ?? null;
        $limit = $settings->limit ?? 20;
        $direction = $settings->direction ?? 'ASC';
        $sortingColumn = (isset($settings->sorting_field_id) && $settings->sorting_field_id) ? $settings->sorting_field_id : null;
        $sortingColumn = $sortingColumn === 'default' ? null : $sortingColumn;
        $class = $settings->class ?? '';

        $collectionFields = (new CollectionsService)->fetchCollectionFields($collectionId ?? -1);

        $allPaths = array_map(function ($item) {
            return CollectionItemsService::createFieldKey($item['path']);
        }, array_filter($collectionFields, function ($item) {
            return $item['type'] !== 'self';
        }));

        $path = $this->collectPaths($this->addon->child_nodes);

        [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($settings->filters);
        // If the addon has reference filter that means it is a detail page
        // So we need to get the data for the detail page
        if ($hasReferenceFilters) {
            $parentItem = CollectionHelper::getDetailPageData();
            $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($parentItem, $referenceFilters, $direction);

            $data = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setSortingColumn($sortingColumn)
                ->setDirection($direction)
                ->applyFilters($regularFilters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths);
        } else {
            $parentItem = CollectionHelper::getDetailPageData();
            $data = (new CollectionData())
                ->setLimit($limit)
                ->setSortingColumn($sortingColumn)
                ->setDirection($direction)
                ->loadDataBySource($collectionId)
                ->setParentItem($parentItem ?? null)
                ->applyFilters($filters, $allPaths)
                ->applyUserFilters($allPaths)
                ->applyUserSearchFilters($collectionId, $path, $allPaths);
        }

        if (empty($data)) {
            $id = 'sppb-dynamic-content-' . $this->addon->id;
            $noRecordsMessage = $settings->no_records_message ?? Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS');
            $noRecordsDescription = $settings->no_records_description ?? null;
            $output = '<div class="sppb-dynamic-content-collection ' . $class . '" id="' . $id . '">';
            $output .= '<div class="sppb-dynamic-content-no-records">';
            $output .= '<h4>' . $noRecordsMessage . '</h4>';

            if ($noRecordsDescription) {
                $output .= '<p>' . $noRecordsDescription . '</p>';
            }

            $output .= '</div>';
            $output .= '</div>';
            return $output;
        }

        $this->setData($data);

        return $this->renderCollectionAddon($this->getDataArray(), $this->addon);
    }

    /**
     * Render items for the collection addon.
     *
     * @return string The rendered content
     * 
     * @since 6.0.0
     */
    public function renderArticles()
    {
        $settings = $this->addon->settings;
        $limit = $settings->limit ?? 20;
        $direction = $settings->direction ?? 'asc';
        $class = $settings->class ?? '';
        $source = $settings->source ?? CollectionIds::ARTICLES_COLLECTION_ID;

        if (!\class_exists('SppagebuilderHelperArticles')) {
            require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
        }

        $articlesCount = \SppagebuilderHelperArticles::getArticlesCount();

        $items = $this->fetchArticlesOrTagsData($source, $articlesCount, $direction);

        $parentItem = null;

        if ($source === CollectionIds::TAGS_COLLECTION_ID) {
            $parentItem = CollectionHelper::getDetailPageDataFromArticles();
            $items = (new CollectionData())
                ->loadDataBySourceForArticlesTags($source)
                ->setData($items)
                ->applyArticleOrTagsFilter($source, $parentItem, $settings->filters)
                ->setLimit($limit)
                ->setDirection($direction)
                ->getData();
        }

        if (empty($items)) {
            $id = 'sppb-dynamic-content-' . $this->addon->id;
            $noRecordsMessage = $settings->no_records_message ?? Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS');
            $noRecordsDescription = $settings->no_records_description ?? null;
            $output = '<div class="sppb-dynamic-content-collection ' . $class . '" id="' . $id . '">';
            $output .= '<div class="sppb-dynamic-content-no-records">';
            $output .= '<h4>' . $noRecordsMessage . '</h4>';

            if ($noRecordsDescription) {
                $output .= '<p>' . $noRecordsDescription . '</p>';
            }

            $output .= '</div>';
            $output .= '</div>';
            return $output;
        }

        $data = (new CollectionData())
            ->loadDataBySourceForArticlesTags($source)
            ->setData($items)
            ->applyArticleOrTagsFilter($source, $parentItem, $settings->filters)
            ->setLimit($limit)
            ->setDirection($direction);

        $this->setData($data);

        return $this->renderCollectionAddon($this->getDataArray(), $this->addon);
    }

    /**
     * Render the pagination.
     *
     * @return string The rendered content
     * 
     * @since 5.5.0
     */
    public function renderPagination()
    {
        $settings = $this->addon->settings;
        $isPaginationEnabled = $this->addon->settings->pagination ?? false;
        $page = 1;
        $numberOfPages = $this->data->getTotalPages();
        $parentItem = CollectionHelper::getDetailPageData();
        $output = '';

        if ($isPaginationEnabled) {

            $loadMoreButtonText = $settings->pagination_load_more_button_text ?? Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_LOAD_MORE');
            $loadMoreButtonType = $settings->pagination_load_more_button_type ?? 'dark';
            $paginationType = $settings->pagination_type ?? 'load-more';

            $output .= '<div class="sppb-dynamic-content-collection__pagination">';

            if ($page < $numberOfPages) {
                $output .= '<input type="hidden" name="sppb-dc-pagination-type" value="' . $paginationType . '">';
                if ($paginationType === 'infinite-scroll') {
                    $output .= '<div class="sppb-dynamic-content-collection__pagination-sentinel" data-total-pages="' . $numberOfPages . '">Loading...</div>';
                } else {
                    $output .= '<button type="button" data-text="' . $loadMoreButtonText . '" data-sppb-load-more-button data-parent-item="' . htmlspecialchars(json_encode($parentItem), ENT_QUOTES, 'UTF-8') . '" data-total-pages="' . $numberOfPages . '" class="sppb-btn btn-sm sppb-btn-' . $loadMoreButtonType . '">' . $loadMoreButtonText . '</button>';
                }
                
                $output .= '<input type="hidden" name="sppb-dynamic-addon-id" value="' . $this->addon->id . '">';
                /** @var CMSApplication */
                $app = Factory::getApplication();
                $app->getDocument()->addScriptOptions("sppb-dc-addon-" . $this->addon->id, $this->addon);
                $app->getDocument()->addScriptOptions("sppb-root", Uri::root());
            }

            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Render pagination for items.
     *
     * @return string The rendered content
     * 
     * @since 6.0.0
     */
    public function renderArticlesPagination()
    {
        $settings = $this->addon->settings;
        $isPaginationEnabled = $settings->pagination ?? false;
        $page = 1;
        $parentItem = CollectionHelper::getDetailPageDataFromArticles();
        
        if (empty($this->data)) {
            return '';
        }

        $numberOfPages = $this->data->getTotalPages();
        $output = '';

        if ($isPaginationEnabled) {
            $loadMoreButtonText = $settings->pagination_load_more_button_text ?? Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_LOAD_MORE');
            $loadMoreButtonType = $settings->pagination_load_more_button_type ?? 'dark';
            $paginationType = $settings->pagination_type ?? 'load-more';

            $output .= '<div class="sppb-dynamic-content-collection__pagination">';

            if ($page < $numberOfPages) {
                $output .= '<input type="hidden" name="sppb-dc-pagination-type" value="' . $paginationType . '">';
                if ($paginationType === 'infinite-scroll') {
                    $output .= '<div class="sppb-dynamic-content-collection__pagination-sentinel" data-total-pages="' . $numberOfPages . '">Loading...</div>';
                } else {
                    $output .= '<button type="button" data-text="' . $loadMoreButtonText . '" data-parent-item="' . htmlspecialchars(json_encode($parentItem), ENT_QUOTES, 'UTF-8') . '" data-sppb-load-more-button data-total-pages="' . $numberOfPages . '" class="sppb-btn btn-sm sppb-btn-' . $loadMoreButtonType . '">' . $loadMoreButtonText . '</button>';
                }
                
                $output .= '<input type="hidden" name="sppb-dynamic-addon-id" value="' . $this->addon->id . '">';
                /** @var CMSApplication */
                $app = Factory::getApplication();
                $app->getDocument()->addScriptOptions("sppb-dc-addon-" . $this->addon->id, $this->addon);
                $app->getDocument()->addScriptOptions("sppb-root", Uri::root());
            }

            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Generate the CSS content.
     *
     * @return string The generated CSS content
     * 
     * @since 5.5.0
     */
    public function generateCSS()
    {
        if (!empty(static::$cssContent)) {
            return '<style type="text/css">' . implode(" ", array_values(static::$cssContent)) . '</style>';
        }
    }
}
