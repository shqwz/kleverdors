<?php
/*
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Controllers;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\Component\Finder\Administrator\Indexer\Query;
use JoomShaper\SPPageBuilder\DynamicContent\Concerns\Validator;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Controller;
use JoomShaper\SPPageBuilder\DynamicContent\Exceptions\ValidatorException;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Request;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Collection;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\QueryBuilder;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

use function PHPSTORM_META\map;

class CollectionImportExportController extends Controller
{
    use Validator;




    /**
     * Perform a depth-first search to find the topological order of collections.
     * 
     * @param string $collectionId The collection ID to start the search from.
     * @param array $adjacencyList The adjacency list representing the graph.
     * @param array $visited An array to keep track of visited nodes.
     * @param array $stack The stack to store the topological order.
     */
    protected function dfs($collectionId, array &$adjacencyList, array &$visited, array &$stack)
    {
        $visited[$collectionId] = true;

        if (isset($adjacencyList[$collectionId])) {
            foreach ($adjacencyList[$collectionId] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    $this->dfs($neighbor, $adjacencyList, $visited, $stack);
                }
            }
        }

        $stack[] = $collectionId;
    }

    /**
     * Perform a topological sort on the collection IDs based on their dependencies.
     * 
     * @param array $collectionIds The collection IDs to be sorted.
     * @param array $dependencies The dependencies between collections.
     * 
     * @return array The sorted collection IDs.
     */
    protected function topologicalSort(array $collectionIds, array $dependencies): array
    {
        $adjacencyList = [];
        $visited = [];
        $stack = [];

        foreach($dependencies as $dependency) {
            $from = $dependency[0];
            $to = $dependency[1];

            if (!isset($adjacencyList[$from])) {
                $adjacencyList[$from] = [];
            }

            if (!isset($adjacencyList[$to])) {
                $adjacencyList[$to] = [];
            }

            $adjacencyList[$from][] = $to;
        }

        foreach ($collectionIds as $collectionId) {
            if(!isset($visited[$collectionId])) {
                $this->dfs($collectionId, $adjacencyList, $visited, $stack);
            }
        }

        return $stack;
    }

    /**
     * Export the dynamic content.
     * 
     * @return void
     * @since 5.7.0
     */
    public function exportDynamicContent()
    {
        $collectionsService = new CollectionsService();
        $dependentCollections = $collectionsService->fetchAllCollectionFields();

        $dependentCollections = Arr::make($dependentCollections)->map(function($field) {
            return [
                $field->collection_id, $field->reference_collection_id
            ];
        })->filter(function($item) {
            return $item[0] !== $item[1];
        })->toArray();

        $collections = $collectionsService->fetchAll();

        $collectionIds = Arr::make($collections)->map(function($collection) {
            return $collection['id'];
        })->toArray();

        $collectionIds = $this->topologicalSort($collectionIds, $dependentCollections);
        
        $exportData = [];

        foreach($collectionIds as $collectionId) {
            if (empty($collectionId)) {
                return response()->json(['message' => 'No collection ID provided'], Response::HTTP_BAD_REQUEST);
            }
    
            $collectionItemsService = new CollectionItemsService();
    
            $collection = Collection::where('id', $collectionId)->with(['fields', 'items'])->first();
    
            if (empty($collection)) {
                return response()->json(['message' => 'Collection not found'], Response::HTTP_NOT_FOUND);
            }

            $fields = Arr::make($collection->fields)->map(function($field) {
                return [
                    'id'                     => $field->id,
                    'collection_id'          => $field->collection_id,
                    'name'                   => $field->name,
                    'type'                   => $field->type,
                    'description'            => !empty($field->description) ? $field->description : null,
                    'options'                => !empty($field->options) ? $field->options : null,
                    'max_length'             => !empty($field->max_length) ? $field->max_length : null,
                    'min_length'             => !empty($field->min_length) ? $field->min_length : null,
                    'default_value'          => !empty($field->default_value) ? $field->default_value : null,
                    'placeholder'            => !empty($field->placeholder) ? $field->placeholder : null,
                    'required'               => !empty($field->required) ? intval($field->required) : 0,
                    'reference_collection_id'=> !empty($field->reference_collection_id) ? $field->reference_collection_id : null,
                    'is_textarea'            => !empty($field->is_textarea) ? intval($field->is_textarea) : 0,
                    'show_time'              => !empty($field->show_time) ? intval($field->show_time) : 0,
                    'file_extensions'        => !empty($field->file_extensions) ? $field->file_extensions : null,
                    'number_format'          => !empty($field->number_format) ? $field->number_format : null,
                    'allow_negative'         => !empty($field->allow_negative) ? intval($field->allow_negative) : 0,
                    'number_unit'            => !empty($field->number_unit) ? $field->number_unit : null,
                    'number_step'            => !empty($field->number_step) ? $field->number_step : null,
                    'created'                => Date::sqlSafeDate(),
                    'created_by'             => getCurrentLoggedInUser()->id,
                ];
            })->toArray();
    
            $items = $collectionItemsService->fetchItemsByCollectionId($collectionId);

            $items = Arr::make($items)->map(function($item) {
                return [
                    'id' => $item["id"],
                    'published' => intval($item["published"]),
                    'access' => intval($item["access"]),
                    'language' => $item["language"],
                    'created' => Date::sqlSafeDate(),
                    'created_by' => $item["created_by"],
                ];
            })->toArray();
    
            if(!empty($items)) {
                foreach ($items as &$item) {
                    $itemValues = CollectionItemValue::where('item_id', $item['id'])->get();
                    $item['itemValues'] = Arr::make($itemValues)->map(function($item) {
                        return [
                            'item_id' => $item['item_id'],
                            'field_id' => $item['field_id'],
                            'value' => $item['value'],
                            'reference_item_id' => $item['reference_item_id'] ?? null,
                        ];
                    })->toArray();
                    
                }
                unset($item);
            }
    
            $exportData[] = [
                'id' => $collection->id,
                'title' => $collection->title,
                'alias' => $collection->alias,
                'fields' => $fields,
                'items' => $items,
            ];
        }

        return $exportData;
    }

    /**
     * Export the collection items.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function export()
    {
        $exportData = $this->exportDynamicContent();

        return response()->json($exportData, Response::HTTP_OK);
    }

    /**
     * Check self export import
     * 
     * @param mixed $data
     * @param mixed $existingCollections
     *
     * @return Boolean
     * @since 5.7.0
     */
    private function checkSelfExportImport($data, $existingCollections)
    {
        $existingCollectionData = [];
        $importedCollectionData = [];

        foreach($existingCollections as $existingCollection) {
            $existingCollection = Collection::where('id', $existingCollection['id'])->with(['fields'])->first();

            $existingCollectionData[$existingCollection['id']] = Arr::make($existingCollection['fields'])->map(function($item) {
                return $item['id'];
            })->toArray();
        }

        foreach($data as $importedData) {
            $importedCollectionData[$importedData['id']] = Arr::make($importedData['fields'])->map(function($item) {
                return $item['id'];
            })->toArray();
        }

        foreach($importedCollectionData as $importedCollectionId => $importedFieldIds) {
            if(isset($existingCollectionData[$importedCollectionId]) && !empty($existingCollectionData[$importedCollectionId])) {
                $existingFieldIds = $existingCollectionData[$importedCollectionId];
                $copyExistingFieldIds = $existingFieldIds;
                $copyImportedFieldsIds = $importedFieldIds;
                
                sort($copyExistingFieldIds);
                sort($copyImportedFieldsIds);

                if($copyExistingFieldIds != $copyImportedFieldsIds) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Import the dynamic content.
     * 
     * @param array $data The data to be imported.
     * 
     * @return array
     * @throws ValidatorException
     * @since 5.7.0
     */
    public function importDynamicContent(array $data)
    {
        $collectionsService = new CollectionsService();
        $existingCollections = $collectionsService->fetchAll();
        $existingCollectionIds = Arr::make($existingCollections)->map(function($existingCollection) {
            return $existingCollection['id'];
        })->toArray();

        $isSelfExportImport = $this->checkSelfExportImport($data, $existingCollections);
        
        if(empty($data) || $isSelfExportImport) {
            return [];
        }

        $globalFieldsMap = [];
        $globalItemsMap = [];
        $globalCollectionsIdMap = [];
        $collectionsService = new CollectionsService();

        $collectionIdMaps = $collectionsService->fetchImportedIdsMap();
        $dbCollectionIdMap = [];
        $dbFieldIdMap = [];
        $alreadyImported = true;

        if(isset($collectionIdMaps->data)) {
            $dbData = Str::toArray($collectionIdMaps->data);

            if(isset($dbData['globalCollectionsIdMap'])) {
                $dbCollectionIdMap = $dbData['globalCollectionsIdMap'];
            }

            if(isset($dbData['globalFieldsMap'])) {
                $dbFieldIdMap = $dbData['globalFieldsMap'];
            }
        }

        foreach($data as $importStructure) {
            if(isset($dbCollectionIdMap[$importStructure['id']])) {
                $newCollectionId = $dbCollectionIdMap[$importStructure['id']];

                if(!in_array($newCollectionId, $existingCollectionIds)) {
                    $alreadyImported = false;
                }
            } else {
                $alreadyImported = false;
            }
        }

        if($alreadyImported) {
            return [
                'globalFieldsMap' => $dbFieldIdMap,
                'globalCollectionsIdMap' => $dbCollectionIdMap,
            ];
        }

        foreach ($data as $importStructure) {
            $importStructure = Str::toArray($importStructure);
            
            $oldCollectionId = $importStructure['id'];
            $items = $importStructure['items'];

            $collectionTitle = $collectionsService->createUniqueTitleForExport($importStructure['title']);
            $collectionAlias = $collectionsService->createUniqueAliasForExport($collectionTitle, $importStructure['alias']);

            $collectionData = [
                'title' => $collectionTitle,
                'alias' => $collectionAlias,
                'published' => 1,
                'access'    => 1,
                'language'  => '*',
                'created'    => Date::sqlSafeDate(),
                'modified'   => Date::sqlSafeDate(),
                'created_by' => getCurrentLoggedInUser()->id
            ];

            $collectionId = Collection::create($collectionData);

            if (!$collectionId) {
                throw new Exception(Text::_('COM_SPPAGEBUILDER_COLLECTION_IMPORT_EXPORT_FAILED_TO_CREATE_COLLECTION'));
            }

            $globalCollectionsIdMap[$oldCollectionId] = $collectionId;

            $fieldsMap = $collectionsService->cloneCollectionFields($importStructure['fields'], $collectionId, $globalCollectionsIdMap);
            
            foreach ($fieldsMap as $key => $value) {
                $globalFieldsMap[$key] = $value;
            }

            $itemsMap = $collectionsService->cloneCollectionItems($items, $collectionId);
            
            foreach ($itemsMap as $key => $value) {
                $globalItemsMap[$key] = $value;
            }

            $itemValuesMap = [];

            foreach ($items as $item) {
                $itemValuesMap[$item['id']] = $item['itemValues'] ?? [];
            }

            $collectionsService->cloneCollectionItemValues($globalItemsMap, $globalFieldsMap, $itemValuesMap);
        }
        
        $collectionsService->createImportedIdsMap([
            'globalFieldsMap' => $globalFieldsMap,
            'globalCollectionsIdMap' => $globalCollectionsIdMap,
        ]);

        return [
            'globalFieldsMap' => $globalFieldsMap,
            'globalCollectionsIdMap' => $globalCollectionsIdMap,
        ];
    }

    /**
     * Import the collection items.
     * 
     * @param Request $request The request object.
     * 
     * @return JsonResponse
     * @since 5.5.0
     */
    public function import(Request $request)
    {
        $data = $request->getRaw('data');

        if (empty($data)) {
            return response()->json(['message' => 'No data provided'], Response::HTTP_BAD_REQUEST);
        }

        $data = Str::toArray($data);

        QueryBuilder::beginTransaction();

        try {
            $this->importDynamicContent($data);
            QueryBuilder::commit();
            return response()->json(true, Response::HTTP_OK);
        } catch (Exception $error) {
            QueryBuilder::rollback();

            if ($error instanceof ValidatorException) {
                return response()->json($error->getData(), $error->getCode());
            }

            return response()->json(['message' => $error->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}