<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Controllers;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Language\Text;
use JoomShaper\SPPageBuilder\DynamicContent\Concerns\Validator;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Controller;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Request;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

class CollectionsController extends Controller
{
    use Validator;

    /**
     * The collection service instance.
     * 
     * @var CollectionsService
     * @since 5.5.0
     */
    protected $service;

    /**
     * The collection presets path.
     * 
     * @var string
     * @since 5.5.0
     */
    protected const COLLECTION_PRESETS_PATH = JPATH_ROOT . '/administrator/components/com_sppagebuilder/assets/data/collection-presets.json';

    /**
     * Constructor method to initialize the collection service
     * 
     * @since 5.5.0
     */
    public function __construct(?CollectionsService $service = null)
    {
        $this->service = $service;
        $model = new Collection();

        parent::__construct($model);
    }

    /**
     * Retrieve all collections.
     *
     * @return void
     * @since 5.5.0
     */
    public function list(Request $request)
    {
        try {
            $includeArticleSources = $request->getInt('include_article_sources', 0);
            return response()->json($this->service->fetchAll($includeArticleSources)); 
        } catch (Exception $error) {
            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new collection.
     *
     * @param Input $input
     * @return void
     * @since 5.5.0
     */
    public function create(Request $request)
    {
        $data = [
            'title'     => $request->getString('title', ''),
            'alias'     => $request->getString('alias', ''),
            'fields'    => $request->getRaw('fields'),
            'published' => $request->getInt('published', 1),
            'access'    => $request->getInt('access', 1), 
            'language'  => $request->getString('language', '*'),
        ];

        withException($this->service, function ($service) use ($data) {
            $collectionId = $service->createRecord($data);
            return response()->json($collectionId, Response::HTTP_CREATED);
        });
    }

    public function form(Request $request)
    {
        $presetName = $request->getString('preset_name');
        $presetPath = self::COLLECTION_PRESETS_PATH;
        $presetData = json_decode(file_get_contents($presetPath), true);
        $preset = $presetData[$presetName];
        return response()->json($preset);
    }

    /**
     * Update a collection by ID.
     *
     * @param Input $input
     * @return void
     * @since 5.5.0
     */
    public function update(Request $request)
    {
        $data = [
            'id'        => $request->getInt('id'),
            'title'     => $request->getString('title', ''),
            'alias'     => $request->getString('alias', ''),
            'fields'    => $request->getRaw('fields'),
            'published' => $request->getInt('published', 1),
            'access'    => $request->getInt('access', 1),
            'language'  => $request->getString('language', '*'),
        ];

        withException($this->service, function ($service) use ($data) {
            $service->updateRecord($data);
            return response()->json(true, Response::HTTP_OK);
        });
    }

    /**
     * Rename a collection by ID.
     *
     * @param Input $input
     * @return void
     * @since 5.5.0
     */
    public function rename(Request $request)
    {
        $data = [
            'title'         => $request->getString('title'),
            'collection_id' => $request->getInt('id'),
        ];

        $this->validate($data, [
            'title'         => 'required|string|min:3|max:255',
            'collection_id' => 'required|integer',
        ]);

        if ($this->hasErrors()) {
            return response()->json($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        try
        {
            $collectionId = $data['collection_id'];
            $updatingData = [
                'title' => $data['title']
            ];
            Collection::where('id', $collectionId)->update($updatingData);
            return response()->json(true);
        }
        catch (Exception $error)
        {
            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a collection by ID.
     *
     * @param Input $input
     * @return void
     * @since 5.5.0
     */
    public function delete(Request $request)
    {
        $collectionId = $request->getInt('id');
        $force = $request->getInt('force', 0);

        if (!$collectionId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        try
        {
            if ($force) {
                Collection::where('id', $collectionId)->delete();
                return response()->json(true);
            }
            
            if ($this->service->isCollectionUsedInPage($collectionId)) {
                return response()->json([
                    'stop_reason' => 'collection_used_in_page',
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_DELETE_TITLE'),
                    'message' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_USED_IN_PAGE_MESSAGE'),
                ]);
            }

            if ($this->service->isCollectionReferenceToOtherCollection($collectionId)) {
                return response()->json([
                    'stop_reason' => 'collection_reference_to_other_collection',
                    'title' => 'Delete Collection',
                    'message' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_REFERENCED_BY_OTHER_COLLECTION_MESSAGE'),
                ]);
            }

            if ($this->service->isCollectionHasItems($collectionId)) {
                return response()->json([
                    'stop_reason' => 'collection_has_items',
                    'title' => 'Delete Collection',
                    'message' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_HAS_ITEMS_MESSAGE')
                ]);
            }

            return response()->json([
                'stop_reason' => 'empty_collection',
                'title' => 'Delete Collection',
                'message' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_EMPTY_COLLECTION_DELETE_MESSAGE')
            ]);
        }
        catch (Exception $error)
        {
            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Duplicate a collection by ID.
     *
     * @param Input $input
     * @return void
     * @since 5.5.0
     */
    public function duplicate(Request $request)
    {
        $collectionId = $request->getInt('id');

        if (!$collectionId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        withException($this->service, function ($service) use ($collectionId) {
            $newCollectionId = $service->duplicateCollection($collectionId);
            return response()->json($newCollectionId, Response::HTTP_CREATED);
        });
    }

    /**
     * Get the collection schema.
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function schema(Request $request)
    {
        $collectionId = $request->getInt('id');

        if (!$collectionId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(
            $this->service->fetchCollectionSchema($collectionId),
            Response::HTTP_OK
        );
    }

    /**
     * Get the collection attributes.
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function attributes(Request $request)
    {
        $id = $request->getInt('collection_id');
        $allowedTypes = $request->getCmd('allowed_types');
        $allowedTypes = !empty($allowedTypes) ? $allowedTypes : [];

        if (empty($allowedTypes)) {
            $allowedTypes = FieldTypes::all();
        }

        if (!in_array(FieldTypes::REFERENCE, $allowedTypes)) {
            $allowedTypes[] = FieldTypes::REFERENCE;
        }

        if (!$id) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        if ($id === CollectionIds::ARTICLES_COLLECTION_ID) {
            return response()->json($this->service->fetchArticleAttributes($allowedTypes));
        }

        if ($id === CollectionIds::TAGS_COLLECTION_ID) {
            return response()->json($this->service->fetchTagsAttributes($allowedTypes));
        }

        return response()->json($this->service->fetchCollectionAttributes($id, $allowedTypes));
    }

    /**
     * Get the collection fields.
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function collectionFields(Request $request)
    {
        $id = $request->getInt('collection_id', null);

        if (!$id) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        if ($id === CollectionIds::ARTICLES_COLLECTION_ID) {
            return response()->json($this->service->fetchArticleFields());
        }

        if ($id === CollectionIds::TAGS_COLLECTION_ID) {
            return response()->json($this->service->fetchTagsFields());
        }

        $fields = $this->service->fetchCollectionFields($id);

        return response()->json($fields);
    }

    /**
     * Get the reference collection fields.
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function referenceCollectionFields(Request $request)
    {
        $ownCollectionId = $request->getInt('own_collection_id');
        $parentCollectionId = $request->getInt('parent_collection_id');

        $fields = CollectionField::where('collection_id', $parentCollectionId)
            ->where('reference_collection_id', $ownCollectionId)
            ->where('type', FieldTypes::MULTI_REFERENCE)
            ->get(['id', 'name', 'type']);

        return response()->json($fields);
    }

    public function totalFieldsByCollection(Request $request)
    {
        $collectionId = $request->getInt('collection_id');

        if (!$collectionId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(
            $this->service->fetchTotalFieldsByCollection($collectionId),
            Response::HTTP_OK
        );
    }

    /**
     * Get the created dynamic content pages.
     * 
     * @return array
     * @since 5.5.0
     */
    protected static function getCreatedDynamicContentPages()
    {
        $pages = Page::where('extension', 'com_sppagebuilder')
            ->whereLike('extension_view', 'dynamic_content%')
            ->get(['extension_view', 'view_id']);
        $pages = Arr::make($pages)->map(function ($item) {
            return $item->toArray();
        })->reduce(function ($carry, $item) {
            $carry[$item['view_id']] ??= [];
            $carry[$item['view_id']][] = $item;
            return $carry;
        }, []);
        return $pages->toArray();
    }

    /**
     * Create collection object for dynamic content pages
     * 
     * @param array $pages Existing pages array for checking page availability
     * @return object The collection object
     * @since 6.0.0
     */
    protected function createArticlesCollection(array $pages)
    {

        if (!\class_exists('SppagebuilderHelperArticles')) {
            require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
        }

        try {
            $articles = \SppagebuilderHelperArticles::getArticles(1);
            $articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
            $hasArticles = !empty($articles);
        } catch (\Exception $e) {
            $hasArticles = false;
        }


        $articlePages = $pages[CollectionIds::ARTICLES_COLLECTION_ID] ?? [];
        $articlePages = Arr::make($articlePages);
        
        $indexPage = $articlePages->find(function($item) {
            return $item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;
        });
        
        $detailPage = $articlePages->find(function($item) {
            return $item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
        });

        $articlesCollection = (object)[
            'id' => CollectionIds::ARTICLES_COLLECTION_ID,
            'title' => Text::_('COM_SPPAGEBUILDER_ARTICLES_SOURCE'),
            'alias' => 'articles',
            'disabled' => ($articlePages->count() === 2),
            'total_items' => $hasArticles ? $articlesCount : 0,
            'pages' => [
                [
                    'id' => (string)CollectionIds::ARTICLES_COLLECTION_ID,
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_INDEX_PAGE'),
                    'disabled' => !empty($indexPage),
                    'page_type' => Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX,
                    'collection_id' => CollectionIds::ARTICLES_COLLECTION_ID,
                ],
                [
                    'id' => (string)CollectionIds::ARTICLES_COLLECTION_ID,
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_DETAIL_PAGE'),
                    'disabled' => !empty($detailPage),
                    'page_type' => Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL,
                    'collection_id' => CollectionIds::ARTICLES_COLLECTION_ID,
                ],
            ]
        ];

        return $articlesCollection;
    }

    /**
     * Create collection object for dynamic content pages
     * 
     * @param array $pages Existing pages array for checking page availability
     * @return object The collection object
     * @since 6.0.0
     */
    protected function createTagsCollection(array $pages)
    {

        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__tags')
            ->where('published = 1');
        $db->setQuery($query);
        $tagCount = $db->loadResult();
        $hasTags = $tagCount > 0;


        $tagPages = $pages[CollectionIds::TAGS_COLLECTION_ID] ?? [];
        $tagPages = Arr::make($tagPages);
        
        $indexPage = $tagPages->find(function($item) {
            return $item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;
        });
        
        $detailPage = $tagPages->find(function($item) {
            return $item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
        });

        $tagsCollection = (object)[
            'id' => CollectionIds::TAGS_COLLECTION_ID,
            'title' => 'Tags',
            'alias' => 'tags',
            'disabled' => !$hasTags || ($tagPages->count() === 2),
            'total_items' => $hasTags ? $tagCount : 0,
            'pages' => [
                [
                    'id' => (string)CollectionIds::TAGS_COLLECTION_ID,
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_INDEX_PAGE'),
                    'disabled' => !$hasTags || !empty($indexPage),
                    'page_type' => Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX,
                    'collection_id' => CollectionIds::TAGS_COLLECTION_ID,
                ],
                [
                    'id' => (string)CollectionIds::TAGS_COLLECTION_ID,
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_DETAIL_PAGE'),
                    'disabled' => !$hasTags || !empty($detailPage),
                    'page_type' => Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL,
                    'collection_id' => CollectionIds::TAGS_COLLECTION_ID,
                ],
            ]
        ];

        return $tagsCollection;
    }

    /**
     * Get the dynamic content pages.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function dynamicContentPages()
    {
        $collections = Collection::where('published', 1)->get(['id', 'title', 'alias']);
        $pages = static::getCreatedDynamicContentPages();


        $articlesCollection = $this->createArticlesCollection($pages);


        $tagsCollection = $this->createTagsCollection($pages);

        $collections = Arr::make($collections)->map(function ($item) use($pages) {
            $collectionPage = $pages[$item->id] ?? [];
            $collectionPage = Arr::make($collectionPage);
            $indexPage = $collectionPage->find(function($item) {
                return $item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;
            });
            $detailPage = $collectionPage->find(function($item) {
                return $item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
            });

            $item->disabled = $collectionPage->count() === 2;
            $item->title = $item->title;
            $item->pages = [
                [
                    'id' => $item->id,
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_INDEX_PAGE'),
                    'disabled' => !empty($indexPage),
                    'page_type' => Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX,
                    'collection_id' => $item->id,
                ],
                [
                    'id' => $item->id,
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_DETAIL_PAGE'),
                    'disabled' => !empty($detailPage),
                    'page_type' => Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL,
                    'collection_id' => $item->id,
                ],
            ];
            return $item;
        });


        if (!$tagsCollection->disabled || $tagsCollection->total_items > 0) {
            $collections->prepend($tagsCollection);
        }


        if (!$articlesCollection->disabled || $articlesCollection->total_items > 0) {
            $collections->prepend($articlesCollection);
        }

        return response()->json($collections->toArray());
    }
}

