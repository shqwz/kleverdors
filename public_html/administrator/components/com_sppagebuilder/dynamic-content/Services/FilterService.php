<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Services;

use Joomla\CMS\Factory;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\Status;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItem;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;

/**
 * Collection Filter Service
 * This class is responsible for filtering and processing collection items for displaying in the frontend.
 *
 * @since 5.5.8
 */
class FilterService
{

    /**
     * Fetch field values from all items in a collection by field ID.
     *
     * @param int $fieldId The field ID.
     * 
     * @since 5.5.8
     */
    public function fetchFieldValuesById(int $fieldId)
    {
       $langTag = Factory::getLanguage()->getTag();
       $values = CollectionItemValue::where('field_id', $fieldId)
       ->leftJoin(CollectionItem::class, 'collection_item_value.item_id', 'collection_item.id')
       ->whereIn('collection_item.language', [$langTag, '*'])
       ->where('collection_item.published', Status::PUBLISHED)
       ->get(['value']);

       return Arr::make($values)->map(function ($item) {
           return $item->toArray()['value'];
       })->toArray('value');
    }

    /**
     * Get the collection option fields data.
     *
     * @param int $fieldId The field ID.
     * @return array The collection option fields data.
     *
     * @since 5.5.0
     */
    public function getCollectionOptionFieldsData(int $fieldId)
    {
        $field = CollectionField::where('id', $fieldId)->where('type', FieldTypes::OPTION)->first(['options']);
        if ($field->isEmpty()) {
            return [];
        }

        if (empty($field->options)) {
            return [];
        }

        $options = Str::toArray($field->options);

        if (empty($options)) {
            return [];
        }

        return Arr::make($options)->reduce(function ($carry, $option) {
            $carry[$option['value']] = $option['label'];
            return $carry;
        }, [])->toArray();

        return $options ?? [];
    }

    
    public function getCollectionItemsFromReferenceField($fieldId, $collectionId)
    {
        $db = \Joomla\CMS\Factory::getDbo();
        $langTag = Factory::getLanguage()->getTag();
        $query = $db->getQuery(true)
            ->select(['id', 'collection_id'])
            ->from($db->quoteName('#__sppagebuilder_collection_fields'))
            ->where('id = ' . (int)$fieldId);
        
        $db->setQuery($query);
        $field = $db->loadObject();
        
        if (!$field) {
            return [];
        }
        
        $query = $db->getQuery(true)
            ->select(['id', 'reference_collection_id'])
            ->from($db->quoteName('#__sppagebuilder_collection_fields'))
            ->where('collection_id = ' . (int)$collectionId)
            ->whereIn($db->quoteName('type'), ['reference', 'multi-reference'])
            ->where('reference_collection_id = ' . (int)$field->collection_id);
        
        $db->setQuery($query);
        $referenceFields = $db->loadObjectList();
        
        if (empty($referenceFields)) {
            return [];
        }
        
        $query = $db->getQuery(true)
            ->select([
                'a.item_id AS main_item_id',
                'a.reference_item_id',
                'b.value'
            ])
            ->from($db->quoteName('#__sppagebuilder_collection_item_values', 'a'))
            ->join('INNER', $db->quoteName('#__sppagebuilder_collection_item_values', 'b') . ' ON a.reference_item_id = b.item_id')
            ->join('INNER', $db->quoteName('#__sppagebuilder_collection_items', 'c') . ' ON a.item_id = c.id')
            ->whereIn('a.field_id', array_column($referenceFields, 'id'))
            ->where('b.field_id = ' . (int)$fieldId)
            ->where('a.reference_item_id IS NOT NULL')
            ->where('a.reference_item_id != 0')
            ->where('c.collection_id = ' . (int)$collectionId)
            ->whereIn('c.language', [$langTag, '*'])
            ->where('c.published = 1');
        
        $db->setQuery($query);
        $results = $db->loadAssocList();

        if (empty($results)) {
            return [];
        }

        $valueCountMap = [];
        
        foreach ($results as $result) {
            $value = $result['value'];
            
            if (!isset($valueCountMap[$value])) {
                $valueCountMap[$value] = [];
            }
            
            $valueCountMap[$value][$result['main_item_id']] = true;
        }

        $counts = [];
        foreach ($valueCountMap as $value => $items) {
            $counts[$value] = count($items);
        }

        return $counts;
    }

    public function isReferenceField($fieldId, $collectionId) {
        $db = \Joomla\CMS\Factory::getDbo();
        
        $query = $db->getQuery(true)
            ->select(['id', 'collection_id'])
            ->from($db->quoteName('#__sppagebuilder_collection_fields'))
            ->where('id = ' . (int)$fieldId);
        
        $db->setQuery($query);
        $field = $db->loadObject();
        
        if (!$field) {
            return false;
        }
        
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__sppagebuilder_collection_fields'))
            ->where('collection_id = ' . (int)$collectionId)
            ->whereIn($db->quoteName('type'), ['reference', 'multi-reference'])
            ->where('reference_collection_id = ' . (int)$field->collection_id);
        
        $db->setQuery($query);
        $count = $db->loadResult();
        
        return $count > 0;
    }
}