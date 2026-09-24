<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionData;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionRenderer;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

class SppagebuilderControllerDynamic_content extends FormController
{
    /**
     * @var CollectionsService
     */
    private $collectionService;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->collectionService = new CollectionsService();

		if (!Session::checkToken())
		{
			$response = [
				'status' => false,
				'message' => Text::_('JLIB_ENVIRONMENT_SESSION_EXPIRED')
			];

			echo json_encode($response);
			die();
		}
    }

    public function list()
    {
        try {
            $includeArticleSources = $this->input->getInt('include_article_sources', 0);
            return response()->json($this->collectionService->fetchAll($includeArticleSources)); 
        } catch (Exception $error) {
            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function attributes()
    {
        $id = $this->input->getInt('collection_id');
        $allowedTypes = $this->input->getCmd('allowed_types');
        $allowedTypes = !empty($allowedTypes) ? $allowedTypes : [];

        if (empty($allowedTypes)) {
            $allowedTypes = FieldTypes::all();
        }

        if (!in_array(FieldTypes::REFERENCE, $allowedTypes)) {
            $allowedTypes[] = FieldTypes::REFERENCE;
        }

        if (!$id) {
            return response()->json(['message' => 'Collection ID is required']);
        }

        if ($id === CollectionIds::ARTICLES_COLLECTION_ID) {
            $attributes = $this->collectionService->fetchArticleAttributes($allowedTypes);
            return response()->json($attributes);
        }

        if ($id === CollectionIds::TAGS_COLLECTION_ID) {
            $attributes = $this->collectionService->fetchTagsAttributes($allowedTypes);
            return response()->json($attributes);
        }

        $attributes = $this->collectionService->fetchCollectionAttributes($id, $allowedTypes);

        return response()->json($attributes);
    }

    public function collectionFields()
    {
        $id = $this->input->getInt('collection_id', null);

        if (empty($id)) {
            return response()->json(['message' => 'Collection ID is required']);
        }

        if ($id === CollectionIds::ARTICLES_COLLECTION_ID) {
            $fields = $this->collectionService->fetchArticleFields();
            return response()->json($fields);
        }

        if ($id === CollectionIds::TAGS_COLLECTION_ID) {
            $fields = $this->collectionService->fetchTagsFields();
            return response()->json($fields);
        }

        $fields = $this->collectionService->fetchCollectionFields($id);

        return response()->json($fields);
    }

    public function totalFieldsByCollection(){
        $id = $this->input->getInt('collection_id', null);

        if (empty($id)) {
            return response()->json(['message' => 'Collection ID is required']);
        }

        $fields = $this->collectionService->fetchTotalFieldsByCollection($id);

        return response()->json($fields);
    }

    public function referenceCollectionFields()
    {
        $ownCollectionId = $this->input->getInt('own_collection_id');
        $parentCollectionId = $this->input->getInt('parent_collection_id');

        $fields = CollectionField::where('collection_id', $parentCollectionId)
            ->where('reference_collection_id', $ownCollectionId)
            ->where('type', FieldTypes::MULTI_REFERENCE)
            ->get(['id', 'name', 'type']);

        return response()->json($fields);
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

    public function getDynamicContentData()
    {
        $input = json_decode(file_get_contents('php://input'));
        $id = $input->collection_id;
        $filters = $input->filters;
        $limit = $input->limit ?? 20;
        $direction = $input->direction ?? 'ASC';
        $parentItem = $input->parent_item;
        $currentLink = $input->currentLink ?? '';
        $isSite = $input->isSite ?? true;

        if (!empty($isSite)) {
            // Get collection fields with proper handling for articles and tags
            if ($id === CollectionIds::ARTICLES_COLLECTION_ID) {
                $collectionFields = (new CollectionsService)->fetchArticleFields();
            } else if ($id === CollectionIds::TAGS_COLLECTION_ID) {
                $collectionFields = (new CollectionsService)->fetchTagsFields();
            } else {
                $collectionFields = (new CollectionsService)->fetchCollectionFields($id ?? -1);
            }

            $path = $this->collectPaths($input->addon->child_nodes ?? []);   
        }

        if (!empty($filters) && is_string($filters)) {
            $filters = json_decode($filters);;
        }

        if (!empty($parentItem) && is_string($parentItem)) {
            $parentItem = json_decode($parentItem, true);
        }

        if (empty($id)) {
            return response()->json(['message' => 'Collection ID is required']);
        }

        if ($id === CollectionIds::ARTICLES_COLLECTION_ID || $id === CollectionIds::TAGS_COLLECTION_ID) {
            if ($id === CollectionIds::ARTICLES_COLLECTION_ID) {
                if (!\class_exists('SppagebuilderHelperArticles')) {
                    require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
                }

                try {
                    $ordering = $direction === 'desc' ? 'latest' : 'oldest';
                    $articleCount = \SppagebuilderHelperArticles::getArticlesCount();
                    $articles = \SppagebuilderHelperArticles::getArticles($articleCount, $ordering);
                    
                    $items = array_map(function ($article) {
                        $article->collection_id = CollectionIds::ARTICLES_COLLECTION_ID;
                        $article->introtext = $article->introtext ?? '';
                        $article->fulltext = $article->fulltext ?? '';
                        $article->featured_image = $article->featured_image ?? '';
                        $article->image_thumbnail = $article->image_thumbnail ?? $article->featured_image ?? '';
                        $article->username = $article->username ?? '';
                        $article->category = $article->category ?? '';
                        return (array) $article;
                    }, $articles);
                } catch (\Exception $e) {
                    $items = [];
                }
            } else {
                try {
                    $db = \Joomla\CMS\Factory::getDbo();
                    $query = $db->getQuery(true)
                        ->select('*')
                        ->from('#__tags')
                        ->where('published = 1')
                        ->order('title ' . $direction);
                    $db->setQuery($query, 0, $limit);
                    $tags = $db->loadObjectList();
                    
                    $items = array_map(function ($tag) {
                        $tag->collection_id = CollectionIds::TAGS_COLLECTION_ID;
                        $tag->title = $tag->title ?? '';
                        $tag->alias = $tag->alias ?? '';
                        $tag->description = $tag->description ?? '';
                        return (array) $tag;
                    }, $tags);
                } catch (\Exception $e) {
                    $items = [];
                }
            }
            
            $data = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyArticleOrTagsFilter($id, $parentItem, $filters)
                ->getData();
            
            return response()->json($data);
        }

        if (!empty($isSite)) {
            $allPaths = array_map(function ($item) {
                return CollectionItemsService::createFieldKey($item['path']);
            }, array_filter($collectionFields, function ($item) {
            return $item['type'] !== 'self';
            }));
        }

        [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($filters);

        if ($hasReferenceFilters) {
            $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($parentItem, $referenceFilters, $direction);

            if (!empty($isSite)) {
            $data = (new CollectionData())
                ->setData($items)
                ->setLimit($limit)
                ->setDirection($direction)
                ->applyFilters($regularFilters, $allPaths)
                ->applyUserFilters($allPaths, $currentLink, false)
                ->applyUserSearchFilters($id, $path, $allPaths, false)
                ->getData();
            } else {
            $data = (new CollectionData())
                ->setDirection($direction)
                ->loadDataBySource($id)
                ->setLimit($limit)
                ->applyFilters($filters)
                ->getData();
            }
        } else {
            if (!empty($isSite)) {
                          $data = (new CollectionData())
                ->setDirection($direction)
                ->loadDataBySource($id)
                ->setLimit($limit)
                ->setParentItem($parentItem ?? null)
                ->applyFilters($filters, $allPaths)
                ->applyUserFilters($allPaths, $currentLink, false)
                ->applyUserSearchFilters($id, $path, $allPaths, false)
                ->getData();  
            } else {
            $data = (new CollectionData())
                ->setDirection($direction)
                ->loadDataBySource($id)
                ->setLimit($limit)
                ->setParentItem($parentItem ?? null)
                ->applyFilters($filters)
                ->getData();
            }
        }

        return response()->json($data);
    }


    public function loadMoreCollectionData()
    {
        $data = json_decode(file_get_contents('php://input'));
        $addon = $data->addon;
        $filters = $data->filters;
        $id = $data->collection_id;
        $limit = $data->limit ?? 20;
        $page = $data->page ?? 1;
        $sortingColumn = $data->sortingColumn ?? null;
        $direction = $data->direction ?? 'ASC';
        $currentLink = $data->currentLink;
        $isSite = $data->isSite ?? true;
        $parentItem = $data->parent_item ?? null;

        if (!empty($parentItem) && is_string($parentItem)) {
            $parentItem = json_decode($parentItem, true);
        }

        // Get collection fields with proper handling for articles and tags
        if ($id === CollectionIds::ARTICLES_COLLECTION_ID) {
            $collectionFields = (new CollectionsService)->fetchArticleFields();
        } else if ($id === CollectionIds::TAGS_COLLECTION_ID) {
            $collectionFields = (new CollectionsService)->fetchTagsFields();
        } else {
            $collectionFields = (new CollectionsService)->fetchCollectionFields($id ?? -1);
        }

        $path = $this->collectPaths(json_decode($addon)->child_nodes);

        if (!empty($filters) && is_string($filters)) {
            $filters = json_decode($filters);
        }

        if (!empty($addon) && is_string($addon)) {
            $addon = json_decode($addon);
        }

        // Handle articles and tags sources differently
        if ($id === CollectionIds::ARTICLES_COLLECTION_ID || $id === CollectionIds::TAGS_COLLECTION_ID) {

            if (!empty($isSite)) {
                $data = (new CollectionData())
                    ->setLimit($limit)
                    ->setDirection($direction)
                    ->setPage($page)
                    ->loadDataBySourceForArticlesTags($id)
                    ->applyArticleOrTagsFilter($id, $parentItem, $filters)
                    ->getData();
            } else {
                $data = (new CollectionData())
                ->setLimit($limit)
                    ->loadDataBySourceForArticlesTags($id)
                    ->setDirection($direction)
                    ->setPage($page)
                    ->applyArticleOrTagsFilter($id, $parentItem, $filters)
                    ->getData();
            }
        } else {
            $allPaths = array_map(function ($item) {
                return CollectionItemsService::createFieldKey($item['path']);
            }, array_filter($collectionFields, function ($item) {
                return $item['type'] !== 'self';
            }));
            
            // Handle regular collections
            if (!empty($isSite)) {
                [$referenceFilters, $regularFilters, $hasReferenceFilters] = CollectionData::partitionByReferenceFilters($filters);

                if ($hasReferenceFilters) {
                    $items = (new CollectionDataService)->getCollectionReferenceItemsOnDemand($parentItem, $referenceFilters, $direction);
                    $data = (new CollectionData())
                        ->setData($items)
                        ->setLimit($limit)
                        ->setSortingColumn($sortingColumn)
                        ->setDirection($direction)
                        ->setPage($page)
                        ->applyFilters($regularFilters, $allPaths)
                        ->applyUserFilters($allPaths, $currentLink)
                        ->applyUserSearchFilters($id, $path, $allPaths, $currentLink)
                        ->getData();
                } else {
            $data = (new CollectionData())
                    ->setSortingColumn($sortingColumn)
                    ->setDirection($direction)
                    ->loadDataBySource($id)
                    ->setLimit($limit)
                    ->setPage($page)
                    ->applyFilters($filters, $allPaths)
                    ->applyUserFilters($allPaths, $currentLink)
                    ->applyUserSearchFilters($id, $path, $allPaths, $currentLink)
                    ->getData();
                }
            } else {
                $data = (new CollectionData())
                    ->setSortingColumn($sortingColumn)
                    ->setDirection($direction)
                    ->loadDataBySource($id)
                    ->setLimit($limit)
                    ->setPage($page)
                    ->setParentItem($parentItem ?? null)
                    ->applyFilters($filters)
                    ->getData();
            }
        }

        $renderer = new CollectionRenderer($addon);
        $renderer->setData($data);
        $output = '';

        foreach ($data as $index => $item) {
            $output .= $renderer->renderCollectionItem($addon->child_nodes, $item, $index);
        }

        return response()->json($output);
    }

    public function getReferenceValueByPath()
    {
        $itemId = $this->input->getInt('item_id', null);
        $fieldId = $this->input->getInt('reference_item_id', null);

        if (empty($itemId) || empty($fieldId)) {
            return null;
        }

        $item = CollectionItemValue::where('item_id', $itemId)
            ->where('field_id', $fieldId)
            ->first(['value', 'reference_item_id']);

        if ($item->isEmpty()) {
            return null;
        }

        $item->id = $item->reference_item_id ?? null;

        return response()->json($item->toArray());
    }
}

