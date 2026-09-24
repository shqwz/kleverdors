<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Services;

use Exception;
use Joomla\CMS\Factory;
use Throwable;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Associations;
use JoomShaper\SPPageBuilder\DynamicContent\Concerns\Validator;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\DateRange;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\Status;
use JoomShaper\SPPageBuilder\DynamicContent\Exceptions\ValidatorException;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Model;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Access;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Language;
use JoomShaper\SPPageBuilder\DynamicContent\Models\User;
use JoomShaper\SPPageBuilder\DynamicContent\QueryBuilder;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;
use ReflectionClass;

defined('_JEXEC') or die;

class CollectionItemsService
{
    use Validator;

    /**
     * The common properties for all the collection items.
     * 
     * @var array
     * @since 5.5.0
     */
    public const COMMON_PROPERTIES = ['published', 'access', 'language', 'created', 'created_by'];

    /**
     * The common properties for the collection item form.
     * 
     * @var array
     * @since 5.5.0
     */
    public const COMMON_PROPERTIES_FOR_FORM = [
        'published' => [
            'name' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_STATUS',
            'description' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_STATUS_DESCRIPTION',
            'placeholder' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_STATUS_PLACEHOLDER',
            'type' => 'status',
            'key' => 'published',
            'required' => false,
            'options' => 'getStatusOptions',
            'default_value' => 1,
        ],
        'access' => [
            'name' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ACCESS',
            'description' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ACCESS_DESCRIPTION',
            'placeholder' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ACCESS_PLACEHOLDER',
            'type' => 'access',
            'key' => 'access',
            'required' => false,
            'options' => 'getAccessOptions',
            'default_value' => 1,
        ],
        'language' => [
            'name' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_LANGUAGE',
            'description' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_LANGUAGE_DESCRIPTION',
            'placeholder' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_LANGUAGE_PLACEHOLDER',
            'type' => 'language',
            'key' => 'language',
            'required' => false,
            'options' => 'getLanguageOptions',
            'default_value' => '*',
        ],
        'created_by' => [
            'name' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_CREATED_BY',
            'description' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_CREATED_BY_DESCRIPTION',
            'placeholder' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_CREATED_BY_PLACEHOLDER',
            'type' => 'created_by',
            'key' => 'created_by',
            'required' => false,
            'options' => 'getCreatedByOptions',
            'default_value' => null,
        ],
        'association' => [
            'name' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ASSOCIATION',
            'description' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ASSOCIATION_DESCRIPTION',
            'placeholder' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ASSOCIATION_PLACEHOLDER',
            'type' => 'association',
            'key' => 'association',
            'required' => false,
            'options' => 'getAssociationOptions',
            'default_value' => null,
        ]
    ];

    /**
     * The common property names.
     * 
     * @var array
     * @since 5.5.0
     */
    public const COMMON_PROPERTY_NAMES = [
        'published'   => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_STATUS',
        'access'      => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ACCESS', 
        'language'    => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_LANGUAGE',
        'created'     => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_CREATED',
        'created_by'  => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_CREATED_BY',
        'association' => 'COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ASSOCIATION',
    ];

    /**
     * The valid batch update keys that are allowed to be updated.
     * 
     * @var array
     * @since 5.5.0
     */
    public const BATCH_UPDATE_KEYS = ['published', 'access', 'language', 'delete'];

    /**
     * Cache for primary field IDs by reference field ID.
     * 
     * @var array
     * @since 6.2.0
     */
    protected $primaryFieldCache = [];

    /**
     * Cache for reference values by serialized parameters.
     * 
     * @var array
     * @since 6.2.0
     */
    protected $referenceValuesCache = [];

    /**
     * The field key prefix. This prefix will be used for collection item object properties.
     * 
     * @var string
     * @since 5.5.0
     */
    public const FIELD_KEY_PREFIX = 'field_';

    /**
     * The primary field type from the fields table.
     * Currently we are using the title field as the primary field.
     * 
     * @var string
     * @since 5.5.0
     */
    public const PRIMARY_FIELD_TYPE = FieldTypes::TITLE;

    /**
     * The available status filters.
     *
     * @var array
     * @since 5.5.0
     */
    public const AVAILABLE_STATUS_FILTERS = ['1', '0', '-2', '*'];

    /**
     * The available date filters.
     * 
     * @var array
     * @since 5.5.0
     */
    public const AVAILABLE_DATE_FILTERS = [
        DateRange::LAST_24_HOURS,
        DateRange::LAST_7_DAYS,
        DateRange::LAST_30_DAYS,
        '*',
    ];

    /**
     * Clear all caches to ensure fresh data.
     * 
     * @return void
     * @since 6.2.0
     */
    public function clearCaches()
    {
        $this->primaryFieldCache = [];
        $this->referenceValuesCache = [];
    }

    /**
     * Pre-load primary field IDs for all reference fields to optimize batch processing.
     * This method analyzes all items and pre-fetches primary field IDs for reference fields.
     * 
     * @param array $items The collection items.
     * 
     * @return void
     * @since 6.2.0
     */
    protected function preloadPrimaryFieldIds(array $items)
    {
        $referenceFieldIds = [];
        
        foreach ($items as $item) {
	            if (empty($item->values)) {
	                continue;
	            }
	
	            foreach ($item->values as $value) {
	                    if ($value->field_type === FieldTypes::REFERENCE) {
	                        $referenceFieldIds[] = $value->field_id;
	                    }
	                }
	        }
        
        $referenceFieldIds = array_unique($referenceFieldIds);
        
        if (!empty($referenceFieldIds)) {
            $referenceFields = CollectionField::whereIn('id', $referenceFieldIds)
                ->whereNotNull('reference_collection_id')
                ->get(['id', 'reference_collection_id']);
            
            $collectionIds = [];
            $fieldMap = [];
            
            foreach ($referenceFields as $field) {
                $collectionIds[] = $field->reference_collection_id;
                $fieldMap[$field->reference_collection_id][] = $field->id;
            }
            
            if (!empty($collectionIds)) {
                $titleFields = CollectionField::whereIn('collection_id', array_unique($collectionIds))
                    ->where('type', static::PRIMARY_FIELD_TYPE)
                    ->get(['id', 'collection_id']);
                
                foreach ($titleFields as $titleField) {
                    if (!isset($fieldMap[$titleField->collection_id])) {
                        continue;
                    }
                    
                    foreach ($fieldMap[$titleField->collection_id] as $referenceFieldId) {
                            $this->primaryFieldCache[$referenceFieldId] = $titleField->id;
                    }
                }
            }
        }
    }

    /**
     * Create the collection item record
     * 
     * @param array $data The data
     * 
     * @return int
     * @since 5.5.0
     */
    public function createItem(array $data)
    {
        $this->validateItemData($data);

        if ($this->hasErrors()) {
            throw new ValidatorException($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $associationValue = $data['association'];
        unset($data['association']);

        $values = Str::toArray($data['values']);
        $values = Arr::make($values);
        $values = $this->sanitizeValues($values);
        unset($data['values']);

        QueryBuilder::beginTransaction();

        try
        {
            $data = array_merge($data, [
                'created' => Date::sqlSafeDate(),
                'modified'  => Date::sqlSafeDate(),
            ]);

            $itemId = CollectionItem::create($data);
            $values = $this->populateValuesWithAlias($values, $data['collection_id'], $itemId);
            $this->createItemValues($values, $itemId);

            if (isset($associationValue) && !empty($associationValue)) {
                $this->handleAssociations($itemId, $associationValue, $data);
            }

            QueryBuilder::commit();

            return $itemId;
        }
        catch (Throwable $error)
        {
            QueryBuilder::rollback();
            throw $error;
        }
    }

    /**
     * Update the collection item record
     * 
     * @param array $payload The payload
     * 
     * @return int
     * @since 5.5.0
     */
    public function updateItem(array $payload)
    {
        $this->validateItemData($payload);

        if ($this->hasErrors()) {
            return response()->json($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $associationValue = $payload['association'] ?? null;
        unset($payload['association']);

        $values = Str::toArray($payload['values']);
        $values = Arr::make($values);
        $values = $this->sanitizeValues($values);
        unset($payload['values']);

        $values = $this->populateValuesWithAlias($values, $payload['collection_id'], $payload['item_id']);
        QueryBuilder::beginTransaction();

        try
        {
            $itemId = (int) $payload['item_id'];
            unset($payload['item_id']);
            CollectionItem::where('id', $itemId)->update($payload);

            $this->deleteItemValues($itemId);
            $this->createItemValues($values, $itemId);

            if (isset($associationValue) && !empty($associationValue)) {
                $this->handleAssociations($itemId, $associationValue, $payload);
            }

            QueryBuilder::commit();

            return $itemId;
        }
        catch (Throwable $error)
        {
            QueryBuilder::rollback();
            throw $error;
        }
    }

    /**
     * Get a single item by ID.
     * 
     * @param int $itemId The item ID.
     *
     * @return array
     * @since 5.5.0
     */
    public function fetchSingleItem(int $itemId)
    {
        $item = CollectionItem::find($itemId);

        if (empty($item)) {
            return null;
        }

        $dynamicValues  = $this->prepareCollectionItemValues($item->values);
        $commonValues   = Arr::make(static::COMMON_PROPERTIES)
            ->reduce(function ($carry, $property) use ($item) {
                $carry[$property] = $item[$property] ?? null;
                return $carry;
            }, [])->toArray();

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('`key`')
            ->from('#__associations')
            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
            ->where('id = ' . (int) $itemId);

        $db->setQuery($query);
        $associationKey = $db->loadResult();

        $queryAgain = $db->getQuery(true)
            ->select('id')
            ->from('#__associations')
            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
            ->where('`key` = ' . $db->quote($associationKey))
            ->where('id != ' . (int) $itemId);

        $db->setQuery($queryAgain);
        $associationValue = $db->loadColumn() ?? [];

        $associationValues = [];

        foreach ($associationValue as $itemId) {
            $item = CollectionItem::where('id', $itemId)->first();
            if ($item) {
                $language = $item->language;
                $languageTitle = Language::where('lang_code', $language)->first()->title ?? $language;
                $associationValues[$languageTitle][] = $itemId;
            }
        }

        $commonValues['association'] = $associationValues;

        $data = array_merge(['id' => $item->id], $dynamicValues, $commonValues);

        return $this->populateFieldsWithData(
            $this->prepareDataForCollectionItemForm($item->collection_id),
            $data
        );
    }

    /**
     * Get a single collection item by its ID.
     *
     * @param int $itemId The item ID.
     * @return array|null The collection item data or null if not found.
     *
     * @since 5.5.0
     */
    public function getCollectionItem($itemId)
    {
        $item = CollectionItem::where('id', $itemId)
            ->leftJoin(Language::class, 'language.lang_code', 'collection_item.language')
            ->leftJoin(Access::class, 'access.id', 'collection_item.access')
            ->leftJoin(User::class, 'user.id', 'collection_item.created_by')
            ->with(['values' => function ($query) {
                return $query->leftJoin(CollectionField::class, 'collection_field.id', 'collection_item_value.field_id')
                    ->rawQuery(function ($query) {
                        return $query->select([
                            'field_name' => 'collection_field.name',
                            'field_type' => 'collection_field.type',
                            'field_options' => 'collection_field.options',
                        ]
                    );
                });
            }])
            ->rawQuery(function ($query) {
                return $query->select([
                    'language_title' => 'language.title', 
                    'access_title' => 'access.title',
                    'user' => 'user.name',
                ]);
            })->first();

        if ($item->isEmpty()) {
            return null;
        }

        return $this->processSingleCollectionItem($item);
    }

    /**
     * Prepare the form data for the collection item form.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return array
     * @since 5.5.0
     */
    public function prepareFormData(int $collectionId)
    {
        $considerDefaultAsValue = true;

        return $this->populateFieldsWithData(
            $this->prepareDataForCollectionItemForm($collectionId), [], $considerDefaultAsValue
        );
    }

    /**
     * Fetch all items by the collection ID.
     * 
     * @param array $payload The payload.
     *
     * @return array
     * @since 5.5.0
     */
    public function fetchAll(array $payload)
    {
        $this->validate($payload, [
            'collection_id'  => 'required|integer',
            'current_page'   => 'required|integer', 
            'per_page'       => 'required|integer',
            'status'         => 'string|in:' . implode(',', static::AVAILABLE_STATUS_FILTERS),
            'created'        => 'string|in:' . implode(',', static::AVAILABLE_DATE_FILTERS),
            'modified'       => 'string|in:' . implode(',', static::AVAILABLE_DATE_FILTERS),
        ]);

        $collectionId = $payload['collection_id'];
        $currentPage  = $payload['current_page'];
        $perPage      = $payload['per_page'];
        $status       = $payload['status'];
        $created      = $payload['created'];
        $modified     = $payload['modified'];
        $search       = $payload['search'];
        $search       = trim($search);
        $search       = addcslashes($search, '%_\\');

        if ($this->hasErrors()) {
            throw new ValidatorException($this->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $searchedItemIds = [];

        // Search by the keyword in the collection item values.
        if (!empty($search)) {
            $matchingItemIds = CollectionItemValue::where('value', 'LIKE', '%' . $search . '%')->get(['item_id']);

            foreach ($matchingItemIds as $itemId) {
                $searchedItemIds[$itemId->item_id] = true;
            }

            $searchedItemIds = array_keys($searchedItemIds);
        }

        $searchedItemIds = !empty($search) && empty($searchedItemIds) ? [-1] : $searchedItemIds;

        $items = CollectionItem::where('collection_id', $collectionId)
            ->leftJoin(Language::class, 'language.lang_code', 'collection_item.language')
            ->leftJoin(Access::class, 'access.id', 'collection_item.access')
            ->leftJoin(User::class, 'user.id', 'collection_item.created_by')
            ->with(['values' => function ($query) {
                return $query->leftJoin(CollectionField::class, 'collection_field.id', 'collection_item_value.field_id')
                    ->rawQuery(function ($query) {
                        return $query->select([
                            'field_name' => 'collection_field.name',
                            'field_type' => 'collection_field.type',
                            'field_options' => 'collection_field.options',
                        ]
                    );
                });
            }])
            ->rawQuery(function ($query) {
                return $query->select([
                    'language_title' => 'language.title', 
                    'access_title' => 'access.title',
                    'user' => 'user.name',
                ]);
            });

        if (!empty($searchedItemIds)) {
            $items = $items->whereIn('id', $searchedItemIds);
        }

        $status = $status === Status::ALL
            ? [Status::PUBLISHED, Status::UNPUBLISHED]
            : [$status];

        $items = $items->whereIn('published', $status);

        if ($created !== '*') {
            [$previous, $today] = Date::generateRange($created);
            $items = $items->where('created', 'BETWEEN', [$previous->toSql(), $today->toSql()]);
        }

        if ($modified !== '*') {
            [$previous, $today] = Date::generateRange($modified);
            $items = $items->where('modified', 'BETWEEN', [$previous->toSql(), $today->toSql()]);
        }

        $items = $items->orderBy('ordering', 'ASC')->paginate($perPage, $currentPage);

        $response = [
            'results'     => [],
            'totalItems'  => $items['total'],
            'totalPages'  => $items['total_pages'], 
            'perPage'     => $items['per_page'],
            'currentPage' => $items['current_page'],
        ];

        foreach ($items['data'] as $item) {
            $response['results'][] = $this->processSingleCollectionItem($item);
        }

        return $response;
    }

    /**
     * Fetch items by collection ID.
     *
     * @param int $collectionId The collection ID.
     * @return array The fetched items.
     *
     * @since 5.5.0
     */
    public function fetchItemsByCollectionId(int $collectionId)
    {
        $items = CollectionItem::where('collection_id', $collectionId)
            ->leftJoin(Language::class, 'language.lang_code', 'collection_item.language')
            ->leftJoin(Access::class, 'access.id', 'collection_item.access')
            ->leftJoin(User::class, 'user.id', 'collection_item.created_by')
            ->with(['values' => function ($query) {
                return $query->leftJoin(CollectionField::class, 'collection_field.id', 'collection_item_value.field_id')
                    ->rawQuery(function ($query) {
                        return $query->select([
                            'field_name' => 'collection_field.name',
                            'field_type' => 'collection_field.type',
                            'field_options' => 'collection_field.options',
                        ]
                    );
                });
            }])
            ->rawQuery(function ($query) {
                return $query->select([
                    'language_title' => 'language.title', 
                    'access_title' => 'access.title',
                    'user' => 'user.name',
                ]);
            })->get();

        $this->preloadPrimaryFieldIds($items);
        
        $items = Arr::make($items)->map(function ($item) {
            return $this->processSingleCollectionItem($item);
        })->toArray();

        return $items;
    }
    
    /**
     * Sanitize the values.
     * 
     * @param Arr $values The values.
     *
     * @return Arr
     * @since 5.5.0
     */
    protected function sanitizeValues(Arr $values)
    {
        return $values->map(function ($item) {
            if (Str::isHtmlString($item['value'])) {
                $item['value'] = Str::sanitizeHtmlString($item['value']);
                return $item;
            }

            if ($item['field_type'] === FieldTypes::GALLERY) {
                $item['value'] = json_encode($item['value']);
                return $item;
            }

            return $item;
        });
    }

    /**
     * Process a single collection item.
     * 
     * @param CollectionItem $item The item.
     *
     * @return array
     * @since 5.5.0
     */
    protected function processSingleCollectionItem(CollectionItem $item)
    {
        $item['language'] = $item['language'] === '*' ? Text::_('COM_SPPAGEBUILDER_ALL') : $item['language_title'];
        $item['access']   = $item['access_title'];
        $item['created_by']  = $item['user'];
        unset($item['language_title'], $item['access_title'], $item['user']);
        $collectionItemValues = $this->makeCollectionItem($item);
        return $collectionItemValues;
    }

    /**
     * Fetch the collection schema.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return array
     * @since 5.5.0
     */
    public function fetchCollectionSchema(int $collectionId)
    {
        return $this->makeCollectionSchema($collectionId);
    }

    /**
     * Duplicate an existing item along with the values.
     * 
     * @param int $itemId The item ID.
     *
     * @return int
     * @since 5.5.0
     */
    public function duplicateItem(int $itemId)
    {
        $item = CollectionItem::where('id', $itemId)->with('values')->first();

        if (empty($item)) {
            throw new Exception('Item not found', Response::HTTP_NOT_FOUND);
        }

        $newItemData = [
            'collection_id' => $item->collection_id,
            'published'     => 0,
            'access'        => $item->access,
            'language'      => $item->language,
            'created'       => Date::sqlSafeDate(),
            'created_by'    => $item->created_by,
        ];

        QueryBuilder::beginTransaction();

        try
        {
            $newItemId = CollectionItem::create($newItemData);
            [$titleFieldId, $aliasFieldId] = $this->getBasicFieldIDs($item->collection_id);

            if (!empty($item->values)) {
                $values = $item->values;
                $values = Arr::make($values);

                $title = $values->find(function ($value) use ($titleFieldId) {
                    return $value->field_id === $titleFieldId;
                })->value ?? null;
                $alias = $values->find(function ($value) use ($aliasFieldId) {
                    return $value->field_id === $aliasFieldId;
                })->value ?? null;

                if (!empty($title)) {
                    $alias = $this->createUniqueAlias($aliasFieldId, $title, $alias, $newItemId);
                }

                $newValues = $values->map(function ($value) use ($newItemId, $titleFieldId, $aliasFieldId, $alias, $title) {
                    $itemValue = $value['value'];

                    if ($value['field_id'] === $titleFieldId) {
                        $itemValue = $title;
                    }

                    if ($value['field_id'] === $aliasFieldId) {
                        $itemValue = $alias;
                    }

                    return [
                        'item_id'           => $newItemId,
                        'field_id'          => $value['field_id'], 
                        'value'             => $itemValue,
                        'reference_item_id' => $value['reference_item_id'] ?? null,
                    ];
                });

                CollectionItemValue::createMany($newValues->toArray());
            }

            QueryBuilder::commit();
            return $newItemId;
        }
        catch (Throwable $error)
        {
            QueryBuilder::rollback();
            throw $error;
        }
    }

    /**
     * Delete the items by the item IDs.
     * 
     * @param array $itemIds The item IDs.
     *
     * @return void
     * @throws Throwable
     * @since 5.5.0
     */
    public function deleteItems(array $itemIds)
    {
        try {
            return CollectionItem::whereIn('id', $itemIds)->delete();
        } catch (Throwable $error) {
            throw $error;
        }
    }

    /**
     * Update the items by the item IDs.
     * 
     * @param array $itemIds The item IDs.
     * @param string $key The key to update.
     * @param mixed $value The value to update.
     *
     * @return void
     * @since 5.5.0
     */
    public function updateItems(array $itemIds, string $key, $value)
    {
        try
        {
            CollectionItem::whereIn('id', $itemIds)->update([$key => $value]);
        }
        catch (Exception $error)
        {
            return response()->json($error->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch the collection items as options.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return array
     * @since 5.5.0
     */
    public function fetchCollectionItemsAsOptions(int $collectionId)
    {
        try {
            $primaryField = CollectionField::where('collection_id', $collectionId)
                ->where('type', static::PRIMARY_FIELD_TYPE)
                ->first(['id']);
            $primaryFieldId = $primaryField->id ?? 0;
            $items = CollectionItem::where('collection_id', $collectionId)
                ->with(['values' => function ($query) use ($primaryFieldId) {
                    return $query->where('field_id', $primaryFieldId);
                }])->get();

            if (empty($items)) {
                return [];
            }

            $items = Arr::make($items)->map(function ($item) {
                return [
                    'value'    => $item->id,
                    'label' => !empty($item->values) ? $item->values[0]->value : null,
                ];
            })->filter(function ($item) {
                return !empty($item['label']);
            })->toArray();

            return $items;
        } catch (Throwable $error) {
            throw $error;
        }
    }


    /**
     * Create the field key with prefix and the field id.
     * 
     * @param int $fieldId The field ID.
     *
     * @return string
     * @since 5.5.0
     */
    public static function createFieldKey(int $fieldId)
    {
        return static::FIELD_KEY_PREFIX . $fieldId;
    }

    /**
     * Validate item data
     * 
     * @param array $data The item data to validate.
     *
     * @return void
     * @since 5.5.0
     */
    protected function validateItemData(array $data)
    {
        $data['values'] = Str::toArray($data['values']);
        $this->validate($data, [
            'collection_id' => 'required|integer',
            'published'     => 'required|integer',
            'access'        => 'required|integer',
            'language'      => 'required|string',
            'created_by'    => 'required|integer',
            'values'        => 'required|array',
        ]);

        $values = Arr::make($data['values']);
        unset($data['values']);

        foreach ($values as $value) {
            $this->validate($value, [
                'field_id' => 'required|integer',
                'field_type' => 'required|string',
            ]);
        }
    }

    /**
     * Check if the field type is a reference field.
     * 
     * @param string $fieldType The field type to check.
     *
     * @return bool
     * @since 5.5.0
     */
    protected function isReferenceField(string $fieldType)
    {
        return in_array($fieldType, [FieldTypes::REFERENCE, FieldTypes::MULTI_REFERENCE], true);
    }

    /**
     * Create item values.
     * 
     * @param Arr $values The values to create.
     * @param int $itemId The item ID.
     *
     * @return void
     * @since 5.5.0
     */
    protected function createItemValues(Arr $values, int $itemId)
    {
        $values = $values->map(function($value) use ($itemId) {
            return [
                'item_id'           => (int) $itemId,
                'field_id'          => (int) $value['field_id'],
                'field_type'        => $value['field_type'],
                'value'             => $value['value'] ?? null,
                'reference_item_id' => null,
            ];
        })->reduce(
            function ($result, $item) {
                if ($this->isReferenceField($item['field_type'])) {
                    $item['value']      = Str::toArray($item['value']);

                    if (!is_array($item['value'])) {
                        $item['value'] = [$item['value']];
                    }

                    $referenceItemIds   = array_map('intval', $item['value']);

                    foreach ($referenceItemIds as $referenceItemId) {
                        $item['value']              = null;
                        $item['reference_item_id']  = $referenceItemId;

                        unset($item['field_type']);
                        $result[] = $item;
                    }

                    unset($item['field_type']);
                } else {
                    unset($item['field_type']);
                    $result[] = $item;
                }

                return $result;
            },
            []
        );

        try
        {
            return CollectionItemValue::createMany($values->toArray());
        }
        catch (Throwable $error)
        {
            throw $error;
        }
    }

    /**
     * Delete item values.
     * 
     * @param int $itemId The item ID.
     *
     * @return void
     * @since 5.5.0
     */
    protected function deleteItemValues(int $itemId)
    {
        try
        {
            return CollectionItemValue::where('item_id', $itemId)->delete();
        }
        catch (Throwable $error)
        {
            throw $error;
        }
    }

    /**
     * Prepare the item values for the reference fields.
     * 
     * @param Model $values The values to prepare.
     * @param array $fieldsMap The fields map.
     *
     * @return array
     * @since 5.5.0
     */
    protected function prepareCollectionItemValues($values)
    {
        $referenceIds = [];
        $result = [];

        foreach ($values as $value) {
            $fieldKey        = static::createFieldKey($value->field_id);
            $itemId          = $value->item_id;
            $fieldId         = $value->field_id; 
            $referenceItemId = $value->reference_item_id;

            $key = $itemId . '_' . $fieldId;

            if (!isset($referenceIds[$key]) && !empty($referenceItemId)) {
                $referenceIds[$key] = [
                    'type'          => FieldTypes::REFERENCE,
                    'field_id'      => $fieldId,
                    'value'         => [],
                ];
            }

            if (!empty($referenceItemId)) {
                $referenceIds[$key]['value'][]  = $referenceItemId;
                $result[$fieldKey]              = $referenceIds[$key];
                continue;
            }

            $result[$fieldKey] = $value->value ?? null;
        }

        return $result;
    }

    /**
     * Populate the fields with data.
     * 
     * @param array $fields The fields.
     * @param array $data The data.
     * @param bool $isDefaultAsValue The flag to consider the default value as value.
     *
     * @return array
     * @since 5.5.0
     */
    protected function populateFieldsWithData($fields, $data, bool $isDefaultAsValue = false)
    {
        foreach ($fields as &$field) {
            $defaultValue = $field->default_value ?? null;
            $field->value = $isDefaultAsValue ? $defaultValue : ($data[$field->key] ?? null);

            if (in_array($field->type, [FieldTypes::REFERENCE, FieldTypes::MULTI_REFERENCE], true)) {
                $field->value = $isDefaultAsValue ? $defaultValue : ($field->value['value'] ?? []);
                [$referenceItems] = wrapErrorSafe(function () use ($field) {
                    return $this->fetchCollectionItemsAsOptions($field->reference_collection_id);
                });

                $field->reference_items = !empty($referenceItems) ? $referenceItems : [];
            }

            if ($field->type === FieldTypes::GALLERY) {
                $field->value = $isDefaultAsValue ? $defaultValue : Str::toArray( $field->value);
            }
        }

        unset($field);

        return $fields;
    }

    /**
     * Get the status options.
     * 
     * @return array
     * @since 5.5.0
     */
    protected function getStatusOptions()
    {
        return [
            [
                'label' => Text::_('COM_SPPAGEBUILDER_EDITOR_STATUS_PUBLISHED'),
                'value' => 1,
            ],
            [
                'label' => Text::_('COM_SPPAGEBUILDER_EDITOR_STATUS_UNPUBLISHED'),
                'value' => 0,
            ],
            [
                'label' => Text::_('COM_SPPAGEBUILDER_EDITOR_STATUS_TRASHED'),
                'value' => -2,
            ],
        ];
    }

    /**
     * Get the access options.
     * 
     * @return array
     * @since 5.5.0
     */
    protected function getAccessOptions()
    {
        return Arr::make(Access::orderBy('ordering', 'ASC')->get(['id', 'title']))->map(function ($item) {
            return [
                'label' => $item->title,
                'value' => $item->id,
            ];
        })->toArray();
    }

    /**
     * Get the language options.
     * 
     * @return array
     * @since 5.5.0
     */
    protected function getLanguageOptions()
    {
        $languages = Arr::make(Language::where('published', 1)->orderBy('ordering', 'ASC')->get(['lang_code', 'title']))->map(function ($item) {
            return [
                'label' => $item->title,
                'value' => $item->lang_code,
            ];
        });

        $languages->prepend([
            'label' => Text::_('COM_SPPAGEBUILDER_ALL'),
            'value' => '*',
        ]);

        return $languages->toArray();
    }

    /**
     * Get the created by options.
     * 
     * @return array
     * @since 5.5.0
     */
    protected function getCreatedByOptions()
    {
        return Arr::make(User::orderBy('name', 'ASC')->get(['id', 'name']))->map(function ($item) {
            return [
                'label' => $item->name,
                'value' => $item->id,
            ];
        })->toArray();
    }

    /**
     * Get the association options.
     *
     * Auto-detects current item and collection from request context.
     *
     * @return array
     * @since 6.1.0
     */
    protected function getAssociationOptions()
    {
        $currentItemId = Factory::getApplication()->input->getInt('item_id');
        if (empty($currentItemId)) {
            return [];
        }
		$collectionId = CollectionItem::where('id', $currentItemId)->first(['collection_id'])->collection_id ?? null;

        $currentItem = null;
        if ($currentItemId) {
            $currentItem = CollectionItem::find($currentItemId);
        }

        if ($currentItem && $currentItem->language === '*') {
            return [];
        }

        $query = CollectionItem::orderBy('language', 'ASC')->orderBy('id', 'DESC');

        if ($collectionId) {
            $query->where('collection_id', $collectionId);
        }

        $query->where('language', '!=', '*');

        if ($currentItemId) {
            $query->where('id', '!=', $currentItemId);
            $query->where('language', '!=', $currentItem->language);
        }

        $query->where('published', 1);

        $items = $query->get(['id', 'collection_id', 'language', 'published']);

        $options = [];
        $currentLanguage = $currentItem ? $currentItem->language : null;

        foreach ($items as $item) {
            $titleField = CollectionField::where('collection_id', $item->collection_id)
                ->where('type', 'title')
                ->first();

            if ($titleField) {
                $valueRecord = CollectionItemValue::where('item_id', $item->id)
                    ->where('field_id', $titleField->id)
                    ->first();

                $displayTitle = $valueRecord ? $valueRecord->value : 'Item #' . $item->id;
            } else {
                $displayTitle = 'Item #' . $item->id;
            }

            $languageInfo = $this->getLanguageInfo($item->language);

            $languageDisplayTitle = $languageInfo['displayTitle'] ?? $languageInfo['title'];
            
            if (!isset($options[$languageDisplayTitle])) {
                $options[$languageDisplayTitle] = [];
            }
            
            $options[$languageDisplayTitle][] = [
                'label' => $displayTitle,
                'value' => $item->id,
                'language' => $item->language,
                'flag' => $languageInfo['flag'],
                'languageTitle' => $languageInfo['title'],
                'isCurrentLanguage' => $currentLanguage === $item->language
            ];
        }

        return $options;
    }

    /**
     * Get language information including title and flag.
     *
     * @param string $languageCode The language code
     *
     * @return array
     */
    protected function getLanguageInfo($languageCode)
    {
        static $languages = null;

        if ($languages === null) {
            $languages = \Joomla\CMS\Language\LanguageHelper::getLanguages();
        }

        if (isset($languages) && count($languages) > 0) {
            $lang = Arr::make($languages)->find(function ($item) use ($languageCode) {
                return $item->lang_code === $languageCode;
            }) ?? null;

            if ($lang) {
                return [
                    'code' => $lang->lang_code,
                    'title' => $lang->title_native,
                    'displayTitle' => $lang->title,
                    'flag' => $lang->image
                ];
            }

        }

        return [
            'code' => $languageCode,
            'title' => ucfirst($languageCode),
            'displayTitle' => ucfirst($languageCode),
            'flag' => ''
        ];
    }

    /**
     * Prepare the common fields for the collection item form.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return array
     * @since 5.5.0
     */
    protected function prepareCommonFieldsForCollectionItemForm(int $collectionId)
    {
        $reflection = new ReflectionClass($this);

        return Arr::make(array_keys(static::COMMON_PROPERTIES_FOR_FORM))->map(function ($property) use ($collectionId, $reflection) {
            $field = static::COMMON_PROPERTIES_FOR_FORM[$property];
            $field['id'] = Str::uuid();
            $field['collection_id'] = $collectionId;
            $field['name'] = Text::_($field['name']);
            $field['description'] = Text::_($field['description']);
            $field['placeholder'] = Text::_($field['placeholder']);
            $options = [];

            if (isset($field['options']) && $reflection->hasMethod($field['options'])) {
                $options = $this->{$field['options']}();
            }

            $field['options'] = $options;

            return (object) $field;
        })->toArray();
    }

    /**
     * Prepare the data for the collection item form.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return array
     * @since 5.5.0
     */
    protected function prepareDataForCollectionItemForm(int $collectionId)
    {
        $fields = CollectionField::where('collection_id', $collectionId)
            ->orderBy('ordering', 'ASC')
            ->get(CollectionField::COMMON_COLUMNS);

        foreach ($fields as &$field) {
            $field->options = Str::toArray($field->options);
            $field->key = static::createFieldKey($field->id);

            if ($field->type === FieldTypes::MULTI_REFERENCE) {
                $field->default_value = Str::toArray($field->default_value);
            }

            if ($field->type === FieldTypes::GALLERY) {
                $field->default_value = Str::toArray($field->default_value);
            }
        }

        unset($field);

        $dynamicFields = $fields ?? [];
        $commonFields = $this->prepareCommonFieldsForCollectionItemForm($collectionId);

        return array_merge($dynamicFields, $commonFields);
    }

    /**
     * Make the collection schema for a specific collection.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return array
     * @throws Throwable
     * @since 5.5.0
     */
    protected function makeCollectionSchema(int $collectionId)
    {
        try {
            $fields = CollectionField::where('collection_id', $collectionId)
                ->whereNot('type', FieldTypes::RICH_TEXT)
                ->orderBy('ordering', 'ASC')->get();
        } catch (Throwable $error) {
            throw $error;
        }

        $schema = [];

        $idProperty = [
            'key'   => 'id',
            'name'  => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_COLLECTION_ITEM_ID'),
            'type'  => 'common',
        ];

        $schema[] = $idProperty;

        foreach ($fields as $field) {
            $schema[] = [
                'key'   => static::createFieldKey($field->id),
                'name'  => $field->name,
                'type'  => $field->type,
            ];
        }

        foreach (static::COMMON_PROPERTIES as $property) {
            $schema[] = [
                'key' => $property,
                'name' => Text::_(static::COMMON_PROPERTY_NAMES[$property]),
                'type' => 'common',
            ];
        }

        return $schema;
    }

    /**
     * Get the title field's ID.
     *
     * @param int $fieldId The field ID.
     *
     * @return int
     * @since 5.5.0
     */
    protected function getPrimaryFieldForReferenceCollection(int $fieldId)
    {
        if (isset($this->primaryFieldCache[$fieldId])) {
            return $this->primaryFieldCache[$fieldId];
        }

        $field                  = CollectionField::find($fieldId);
        $referenceCollectionId  = intval($field->reference_collection_id ?? 0);

        if (empty($field) || empty($referenceCollectionId)) {
            $this->primaryFieldCache[$fieldId] = 0;
            return 0;
        }

        $titleField = CollectionField::where('collection_id', $referenceCollectionId)
            ->where('type', static::PRIMARY_FIELD_TYPE)
            ->first(['id']);

        $primaryFieldId = $titleField->id ?? 0;
        
        $this->primaryFieldCache[$fieldId] = $primaryFieldId;

        return $primaryFieldId;
    }

    /**
     * Get the reference values.
     *
     * @param array $itemIds The item IDs.
     * @param int $primaryFieldId The primary field ID.
     *
     * @return string
     * @since 5.5.0
     */
    protected function getReferenceValues(array $itemIds, int $primaryFieldId)
    {
        sort($itemIds);
        $cacheKey = implode(',', $itemIds) . '_' . $primaryFieldId;
        
        if (isset($this->referenceValuesCache[$cacheKey])) {
            return $this->referenceValuesCache[$cacheKey];
        }

        $values = CollectionItemValue::whereIn('item_id', $itemIds)
            ->where('field_id', $primaryFieldId)
            ->get(['value']);

        $valuesArray = [];

        foreach ($values as $value) {
            $valuesArray[] = $value->value;
        }

        $this->referenceValuesCache[$cacheKey] = $valuesArray;

        return $valuesArray;
    }

    /**
     * Make the collection item for the collection items response.
     *
     * @param CollectionItem $item The item object.
     *
     * @return array
     * @since 5.5.0
     */
    protected function makeCollectionItem(CollectionItem $item)
    {
        $itemId = $item->id;
        $values = $item->values;

        foreach ($values as $value) {
            $value->field_options = Str::toArray($value->field_options);

            if ($value->field_type === FieldTypes::OPTION) {
                $value->value = Arr::make($value->field_options)->find(function ($option) use ($value) {
                    return $option['value'] === $value->value;
                })['label'] ?? null;
            }

            if ($value->field_type === FieldTypes::IMAGE) {
                $value->value = CollectionHelper::getImageUrl($value->value);
            }
        }

        $collectionItemIdValue          = ['id' => $itemId];
        $collectionItemCommonValues     = Arr::make(static::COMMON_PROPERTIES)->reduce(
            function ($carry, $property) use ($item) {
                $carry[$property] = isset($item[$property]) ? $item[$property] : null;
                return $carry;
            },
            []
        )->toArray();

        if (empty($values)) {
            return array_merge($collectionItemIdValue, $collectionItemCommonValues);
        }

        $result = [];
        $collectionItemDynamicValues    = $this->prepareCollectionItemValues($values);
        $result = array_merge($collectionItemIdValue, $collectionItemDynamicValues, $collectionItemCommonValues);

        foreach ($result as $index => $item) {
            if (is_array($item) && !empty($item['type']) && $item['type'] === FieldTypes::REFERENCE && is_array($item['value'])) {
                $primaryFieldId = $this->getPrimaryFieldForReferenceCollection($item['field_id']);
                $result[$index] = $this->getReferenceValues($item['value'], $primaryFieldId);
            }
        }

        return $result;
    }

    /**
     * Get the basic field IDs from the collection_fields table for a specific collection.
     * 
     * @param int $collectionId The collection ID.
     *
     * @return array<int, int> The [titleFieldId, aliasFieldId]
     * @since 5.5.0
     */
    protected function getBasicFieldIDs(int $collectionId)
    {
        $basicFields = CollectionField::where('collection_id', $collectionId)
                ->whereIn('type', [FieldTypes::TITLE, FieldTypes::ALIAS])
                ->get(['id', 'type']);
        $basicFields = Arr::make($basicFields);
        $titleFieldId = $basicFields->find(function ($item) {
            return $item->type === FieldTypes::TITLE;
        })->id ?? 0;
        $aliasFieldId = $basicFields->find(function ($item) {
            return $item->type === FieldTypes::ALIAS;
        })->id ?? 0;

        return [$titleFieldId, $aliasFieldId];
    }

    /**
     * Check if an alias exists in a Joomla table.
     * 
     * @param string $table The table name.
     * @param string $alias The alias to check.
     *
     * @return bool
     * @since 6.1.3
     */
    protected function aliasExistsInTable(string $table, string $alias)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName($table))
            ->where('alias = ' . $db->quote($alias));
        $db->setQuery($query);
        return (int) $db->loadResult() > 0;
    }

    /**
     * Create a unique alias.
     * 
     * @param int $aliasId The alias ID.
     * @param string $title The title.
     * @param string $alias The alias.
     *
     * @return string
     * @since 5.5.0
     */
    protected function createUniqueAlias(int $aliasId, string $title, ?string $alias = null, ?int $itemId = null)
    {
        if (!empty($alias)) {
            $alias = Str::safeUrl($alias);
        } else {
            $alias = Str::safeUrl($title);
        }

        $joomlaTables = ['#__content', '#__tags', '#__menu'];
        foreach ($joomlaTables as $table) {
            if ($this->aliasExistsInTable($table, $alias)) {
                $alias = Str::increment($alias, 'dash');
            }
        }

        if (!CollectionItemValue::where('field_id', $aliasId)->where('value', $alias)->where('item_id', '!=', $itemId)->first()->isEmpty()) {
            $alias = Str::increment($alias, 'dash');
        }

        if (str_replace('-', '', $alias) === '') {
            $alias = Date::format('now', 'Y-m-d-H-i-s');
        }

        return $alias;
    }

    /**
     * Populate the values with an alias.
     * 
     * @param Arr $values The values.
     * @param int $collectionId The collection ID.
     * @param int $itemId The item ID.
     *
     * @return Arr
     * @since 5.5.0
     */
    protected function populateValuesWithAlias(Arr $values, int $collectionId, ?int $itemId = null)
    {
        [$titleFieldId, $aliasFieldId] = $this->getBasicFieldIDs($collectionId);

        $title = $values->find(function ($value) use ($titleFieldId) {
            return $value['field_id'] === $titleFieldId;
        });
        $alias = $values->find(function ($value) use ($aliasFieldId) {
            return $value['field_id'] === $aliasFieldId;
        });

        $title = $title['value'] ?? null;
        $alias = $alias['value'] ?? null;

        if (!empty($title)) {
            $alias = $this->createUniqueAlias($aliasFieldId, $title, $alias, $itemId);
        }

        return $values->map(function ($value) use ($aliasFieldId, $alias) {
            $value['value'] = $value['field_id'] === $aliasFieldId ? $alias : $value['value'];
            return $value;
        });
    }

    /**
     * Handle associations for collection items
     * 
     * @param int $itemId The item ID
     * @param int $associatedItemId The associated item ID
     * @param array $data The item data
     * 
     * @return void
     * @since 6.1.0
     */
    protected function handleAssociations($itemId, $associations, $data)
    {
        try {
            $currentItem = CollectionItem::where('id', $itemId)->first();
            if (!$currentItem) {
                return;
            }

            $currentLanguage = $currentItem->language;

            if ($currentLanguage === '*') {
                $this->removeAllAssociationsForItem($itemId);
                return;
            }

            $associations = Str::toArray($associations);
            
            $allAssociatedItemIds = [];
            
            if (is_array($associations)) {
                foreach ($associations as $language => $itemIds) {
                    if (is_array($itemIds)) {
                        $allAssociatedItemIds = array_merge($allAssociatedItemIds, $itemIds);
                    }
                }
            }

            $this->removeAssociationsForLanguages($itemId, array_keys($associations));

            foreach ($allAssociatedItemIds as $associatedId) {
                $associatedItem = CollectionItem::where('id', $associatedId)->first();
                if ($associatedItem && $associatedItem->language === $currentLanguage) {
                    $this->removeAssociationBetweenItems($itemId, $associatedId);
                }
            }

            $filteredAssociatedItemIds = [];
            foreach ($allAssociatedItemIds as $associatedId) {
                $associatedItem = CollectionItem::where('id', $associatedId)->first();
                if ($associatedItem && $associatedItem->language !== $currentLanguage) {
                    $filteredAssociatedItemIds[] = $associatedId;
                }
            }

            $allItemIds = array_merge([$itemId], $filteredAssociatedItemIds);
            
            $allItemIds = array_unique(array_filter($allItemIds));

            if (count($allItemIds) <= 1) {
                return;
            }

            $minId = min($allItemIds);
            $maxId = max($allItemIds);
            $associationKey = 'sppb_assoc_' . $minId . '_' . $maxId;

            $existingKey = $this->getAssociationKeyFromItem($itemId);
            if ($existingKey) {
                $this->removeAssociation($existingKey);
            }

            foreach ($allItemIds as $id) {
                $this->storeAssociation($id, $associationKey);
            }

        } catch (Exception $e) {
            error_log('Association error: ' . $e->getMessage());
        }
    }

    /**
     * Remove association from Joomla's association table
     * 
     * @param int $itemId The item ID
     * @param string $associationKey The association key
     * 
     * @return void
     * @since 6.1.0
     */
    protected function removeAssociation($associationKey)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->delete('#__associations')
            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
            ->where('`key` = ' . $db->quote($associationKey));

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Get association key from an existing item
     * 
     * @param int $itemId The item ID
     * 
     * @return string|null
     * @since 6.1.0
     */
    protected function getAssociationKeyFromItem($itemId)
    {
        $db = Factory::getDbo();
        
        $query = $db->getQuery(true)
            ->select('`key`')
            ->from('#__associations')
            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
            ->where('id = ' . (int) $itemId);
        
        $db->setQuery($query);
        return $db->loadResult();
    }

    /**
     * Create a new association key
     * 
     * @param int $itemId1 The first item ID
     * @param int $itemId2 The second item ID
     * 
     * @return string
     * @since 6.1.0
     */
    protected function createAssociationKey($itemId1, $itemId2)
    {
        return 'sppb_assoc_' . min($itemId1, $itemId2) . '_' . max($itemId1, $itemId2);
    }

    /**
     * Store association in Joomla's association table
     * 
     * @param int $itemId The item ID
     * @param string $associationKey The association key
     * 
     * @return void
     * @since 6.1.0
     */
    protected function storeAssociation($itemId, $associationKey)
    {
        $db = Factory::getDbo();
        
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__associations')
            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
            ->where('id = ' . (int) $itemId);
        
        $db->setQuery($query);
        $existing = $db->loadResult();
        
        if ($existing) {
            $query = $db->getQuery(true)
                ->update('#__associations')
                ->set('`key` = ' . $db->quote($associationKey))
                ->where('id = ' . (int) $itemId)
                ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'));
        } else {
            $query = $db->getQuery(true)
                ->insert('#__associations')
                ->set('id = ' . (int) $itemId)
                ->set('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                ->set('`key` = ' . $db->quote($associationKey));
        }
        
        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Remove all associations for a specific item
     * 
     * @param int $itemId The item ID
     * 
     * @return void
     * @since 6.1.0
     */
    protected function removeAllAssociationsForItem($itemId)
    {
        $db = Factory::getDbo();
        
        $associationKey = $this->getAssociationKeyFromItem($itemId);
        
        if ($associationKey) {
            $query = $db->getQuery(true)
                ->select('id')
                ->from('#__associations')
                ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                ->where('`key` = ' . $db->quote($associationKey));
            
            $db->setQuery($query);
            $associatedItemIds = $db->loadColumn() ?? [];
            
            $remainingCount = count($associatedItemIds) - 1;
            
            $query = $db->getQuery(true)
                ->delete('#__associations')
                ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                ->where('id = ' . (int) $itemId);
            
            $db->setQuery($query);
            $db->execute();
            
            if ($remainingCount <= 1) {
                foreach ($associatedItemIds as $id) {
                    if ($id != $itemId) {
                        $query = $db->getQuery(true)
                            ->delete('#__associations')
                            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                            ->where('id = ' . (int) $id);
                        
                        $db->setQuery($query);
                        $db->execute();
                        break;
                    }
                }
            }
        }
    }

    /**
     * Remove associations for specific languages
     * 
     * @param int $itemId The item ID
     * @param array $languages Array of language display titles
     * 
     * @return void
     * @since 6.1.0
     */
    protected function removeAssociationsForLanguages($itemId, $languages)
    {
        $db = Factory::getDbo();
        
        $associationKey = $this->getAssociationKeyFromItem($itemId);
        
        if (!$associationKey) {
            return;
        }
        
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__associations')
            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
            ->where('`key` = ' . $db->quote($associationKey));
        
        $db->setQuery($query);
        $associatedItemIds = $db->loadColumn() ?? [];
        
        $languageMap = [];
        foreach (Language::where('published', 1)->get(['lang_code', 'title']) as $lang) {
            $languageMap[$lang->title] = $lang->lang_code;
        }
        
        $itemsToRemove = [];
        foreach ($associatedItemIds as $associatedId) {
            if ($associatedId == $itemId) {
                continue;
            }
            
            $associatedItem = CollectionItem::where('id', $associatedId)->first();
            if ($associatedItem) {
                $langTitle = Language::where('lang_code', $associatedItem->language)->first();
                if ($langTitle && in_array($langTitle->title, $languages)) {
                    $itemsToRemove[] = $associatedId;
                }
            }
        }
        
        foreach ($itemsToRemove as $id) {
            $query = $db->getQuery(true)
                ->delete('#__associations')
                ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                ->where('id = ' . (int) $id);
            
            $db->setQuery($query);
            $db->execute();
        }
        
        $remainingQuery = $db->getQuery(true)
            ->select('id')
            ->from('#__associations')
            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
            ->where('`key` = ' . $db->quote($associationKey));
        
        $db->setQuery($remainingQuery);
        $remainingItems = $db->loadColumn() ?? [];
        
        if (count($remainingItems) <= 1) {
            foreach ($remainingItems as $id) {
                $query = $db->getQuery(true)
                    ->delete('#__associations')
                    ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                    ->where('id = ' . (int) $id);
                
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    /**
     * Remove association between two specific items
     * 
     * @param int $itemId1 The first item ID
     * @param int $itemId2 The second item ID
     * 
     * @return void
     * @since 6.1.0
     */
    protected function removeAssociationBetweenItems($itemId1, $itemId2)
    {
        $db = Factory::getDbo();
        
        $key1 = $this->getAssociationKeyFromItem($itemId1);
        $key2 = $this->getAssociationKeyFromItem($itemId2);
        
        if ($key1 && $key1 === $key2) {
            $query = $db->getQuery(true)
                ->select('id')
                ->from('#__associations')
                ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                ->where('`key` = ' . $db->quote($key1));
            
            $db->setQuery($query);
            $associatedItemIds = $db->loadColumn() ?? [];
            
            $remainingCount = count($associatedItemIds) - 1;
            
            $query = $db->getQuery(true)
                ->delete('#__associations')
                ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                ->where('id = ' . (int) $itemId2);
            
            $db->setQuery($query);
            $db->execute();
            
            if ($remainingCount <= 1) {
                foreach ($associatedItemIds as $id) {
                    if ($id != $itemId2) {
                        $query = $db->getQuery(true)
                            ->delete('#__associations')
                            ->where('context = ' . $db->quote('com_sppagebuilder.collection_item'))
                            ->where('id = ' . (int) $id);
                        
                        $db->setQuery($query);
                        $db->execute();
                        break;
                    }
                }
            }
        }
    }

    public static function fetchItemIdsByCollectionId($collectionId)
    {
        $items = CollectionItem::where('collection_id', $collectionId)->get(['id']);
        return Arr::make($items)->pluck('id')->toArray();
    }
}
