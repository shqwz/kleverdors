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
use JoomShaper\SPPageBuilder\DynamicContent\Controller;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Request;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

class CollectionItemsController extends Controller
{
    use Validator;

    /**
     * The collection item service instance.
     * 
     * @var CollectionItemService
     * @since 5.5.0
     */
    protected $service;

    public function __construct(?CollectionItemsService $service = null)
    {
        $this->service = $service;

        $model = new CollectionItem();
        parent::__construct($model);
    }

    /**
     * Create a new item with values.
     * 
     * @param Input $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function create(Request $request)
    {
        $payload = [
            'collection_id' => $request->getInt('collection_id'),
            'values'        => $request->post->getRaw('values'), // The value should be get from the $_POST otherwise it removes the html tags
            'published'     => $request->getInt('published', 1),
            'access'        => $request->getInt('access', 1), 
            'language'      => $request->getString('language', '*'),
            'created_by'    => $request->getInt('created_by', null),
            'association'   => $request->getRaw('association'),
        ];

        if (empty($payload['created_by'])) {
            $payload['created_by'] = getCurrentLoggedInUser()->id;
        }

        withException($this->service, function ($service) use ($payload) {
            $itemId = $service->createItem($payload);
            return response()->json($itemId, Response::HTTP_CREATED);
        });
    }

    /**
     * Update an existing item with values.
     * 
     * @param Input $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function update(Request $request)
    {
        $payload = [
            'collection_id' => $request->getInt('collection_id'),
            'item_id'       => $request->getInt('item_id'),
            'values'        => $request->post->getRaw('values'), // The value should be get from the $_POST otherwise it removes the html tags
            'published'     => $request->getInt('published', 1),
            'access'        => $request->getInt('access', 1),
            'language'      => $request->getString('language', '*'),
            'created_by'    => $request->getInt('created_by', getCurrentLoggedInUser()->id),
            'modified'      => Date::sqlSafeDate(),
            'modified_by'   => getCurrentLoggedInUser()->id,
            'association'   => $request->getRaw('association'),
        ];

        withException($this->service, function ($service) use ($payload) {
            $itemId = $service->updateItem($payload);
            return response()->json($itemId, Response::HTTP_OK);
        });
    }

    /**
     * Delete an existing item.
     * 
     * @param Input $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function delete(Request $request)
    {
        $itemId = $request->getInt('id');

        if (!$itemId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        try
        {
            CollectionItem::where('id', $itemId)->delete();
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }
        catch (Exception $error)
        {
            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a single item by ID.
     *
     * @param Input $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function single(Request $request)
    {
        $itemId = $request->getInt('item_id');

        if (!$itemId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(
            $this->service->fetchSingleItem($itemId),
            Response::HTTP_OK
        );
    }

    /**
     * Prepare the form data for the collection item form.
     * This method will be used for the collection item form for the first time.
     * 
     * @param Input $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function form(Request $request)
    {
        $collectionId = $request->getInt('collection_id');
        $mode = $request->getString('mode');
        $itemId = $request->getInt('item_id');

        $this->validate(['collection_id' => $collectionId, 'mode' => $mode], [
            'collection_id' => 'integer',
            'mode' => 'required|in:create,edit',
            'item_id' => 'integer',
        ]);

        if ($this->hasErrors()) {
            return response()->json($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $data = $mode === 'create'
            ? $this->service->prepareFormData($collectionId)
            : $this->service->fetchSingleItem($itemId);

        return response()->json($data, Response::HTTP_OK);
    }

    /**
     * Get items by collection ID.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function list(Request $request)
    {
        $payload = [
            'collection_id'  => $request->getInt('collection_id'),
            'current_page'   => $request->getInt('current_page', 1),
            'per_page'       => $request->getInt('per_page', 20),
            'search'         => $request->getString('search', ''),
            'status'         => $request->getString('status', '*'),
            'created'        => $request->getString('created', '*'),
            'modified'       => $request->getString('modified', '*'),
        ];

        if ($payload['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID) {
            $collectionsService = new \JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService();
            return response()->json(
                $collectionsService->fetchArticleItems($payload),
                Response::HTTP_OK
            );
        }

        if ($payload['collection_id'] === CollectionIds::TAGS_COLLECTION_ID) {
            $collectionsService = new \JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService();
            return response()->json(
                $collectionsService->fetchTagsItems($payload),
                Response::HTTP_OK
            );
        }

        withException($this->service, function ($service) use ($payload) {
            return response()->json(
                $service->fetchAll($payload),
                Response::HTTP_OK
            );
        });
    }

    /**
     * Batch update the items.
     * 
     * @param Request $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function batchUpdate(Request $request)
    {
        $itemIds  = $request->getRaw('item_ids');
        $key      = $request->getString('key');
        $value    = $request->getCmd('value');

        $itemIds = Str::toArray($itemIds);

        $data = [
            'item_ids'  => $itemIds,
            'key'       => $key,
            'value'     => $value,
        ];

        $this->validate($data, [
            'item_ids'  => 'required|array',
            'key'       => 'required|in:' . implode(',', CollectionItemsService::BATCH_UPDATE_KEYS),
        ]);

        if ($this->hasErrors()) {
            return response()->json($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        if ($key === 'delete') {
            try {
                $this->service->deleteItems($itemIds);
                return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_DELETED_SUCCESSFULLY')], Response::HTTP_OK);
            } catch (Exception $error) {
                return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        withException($this->service, function ($service) use ($itemIds, $key, $value) {
            $service->updateItems($itemIds, $key, $value);
            return response()->json($itemIds, Response::HTTP_OK);
        });
    }

    /**
     * Get the collection schema.
     * 
     * @param Request $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function schema(Request $request)
    {
        $collectionId = $request->getInt('collection_id');

        if (!$collectionId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
            $schema = [
                ['key' => 'title', 'name' => 'Title', 'type' => 'text'],
                ['key' => 'published', 'name' => 'Status', 'type' => 'switch'],
                ['key' => 'created', 'name' => 'Created', 'type' => 'date-time'],
                ['key' => 'username', 'name' => 'Author', 'type' => 'text'],
                ['key' => 'category', 'name' => 'Category', 'type' => 'text'],
                ['key' => 'hits', 'name' => 'Hits', 'type' => 'number'],
            ];
            return response()->json($schema, Response::HTTP_OK);
        }

        if ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
            $schema = [
                ['key' => 'title', 'name' => 'Title', 'type' => 'text'],
                ['key' => 'alias', 'name' => 'Alias', 'type' => 'text'],
            ];
            return response()->json($schema, Response::HTTP_OK);
        }

        withException($this->service, function ($service) use ($collectionId) {
            $schema = $service->fetchCollectionSchema($collectionId);
            return response()->json(
                $schema,
                Response::HTTP_OK
            );
        });
    }

    /**
     * Duplicate an existing item.
     * 
     * @param Request $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function duplicate(Request $request)
    {
        $itemId = $request->getInt('id');

        if (!$itemId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        withException($this->service, function ($service) use ($itemId) {
            $newItemId = $service->duplicateItem($itemId);
            return response()->json($newItemId, Response::HTTP_OK);
        });
    }

    /**
     * Get the collection items as options.
     * 
     * @param Request $request The input object.
     *
     * @return JsonResponse
     * @since 5.5.0
     */
    public function itemsAsOptions(Request $request)
    {
        $collectionId = $request->getInt('collection_id');

        if (!$collectionId) {
            return response()->json(['message' => Text::_('COM_SPPAGEBUILDER_COLLECTION_ITEMS_COLLECTION_ID_REQUIRED')], Response::HTTP_BAD_REQUEST);
        }

        withException($this->service, function ($service) use ($collectionId) {
            $items = $service->fetchCollectionItemsAsOptions($collectionId);
            return response()->json($items, Response::HTTP_OK);
        });
    }
}
