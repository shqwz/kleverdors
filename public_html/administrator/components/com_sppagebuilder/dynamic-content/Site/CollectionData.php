<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Site;

use DateTime;
use DateTimeZone;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\Conditions;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionItemValue;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionItemsService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use Throwable;

class CollectionData
{
    /**
     * The collection items.
     *
     * @var array
     *
     * @since 5.5.0
     */
    protected $items;

    /**
     * The limit of the collection items.
     *
     * @var int
     *
     * @since 5.5.0
     */
    protected $limit = 20;

    /**
     * The page of the collection items.
     *
     * @var int
     *
     * @since 5.5.0
     */
    protected $page = 1;

    /**
     * Id of the field to sort the collection items by.
     *
     * @var int|null
     *
     * @since 6.2.3
     */
    protected $sortingColumn = null;

    /**
     * The direction of the collection items.
     *
     * @var string
     *
     * @since 5.5.0
     */
    protected $direction = 'ASC';

    /**
     * The current item ID.
     *
     * @var int|null
     *
     * @since 5.5.0
     */
    protected $currentItemId = null;

    /**
     * The total items.
     *
     * @var int
     *
     * @since 5.5.0
     */
    protected $totalItems = 0;

    /**
     * The primary key of the collection items.
     *
     * @var string
     *
     * @since 5.5.0
     */
    protected const PRIMARY_KEY = 'id';

    /**
     * The source collection ID.
     *
     * @var int|null
     *
     * @since 6.0.0
     */
    protected $sourceCollectionId = null;

    /**
     * The parent item for reference filtering.
     *
     * @var array|null
     *
     * @since 6.0.0
     */
    protected $parentItem = null;

    /**
     * The filters to apply.
     *
     * @var object|null
     *
     * @since 6.0.0
     */
    protected $filters = null;

    /**
     * Class Constructor.
     *
     * @since 5.5.0
     */
    public function __construct()
    {
        $this->currentItemId = CollectionHelper::getCollectionItemIdFromUrl();
    }

    /**
     * Set the current item ID.
     * This item id is use for the reference filters.
     *
     * @param int $itemId The item ID to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setCurrentItemId($itemId)
    {
        $this->currentItemId = $itemId;
        return $this;
    }

    /**
     * Set the parent item.
     *
     * @param array $parentItem The parent item to set.
     * @return self
     *
     * @since 6.0.0
     */
    public function setParentItem($parentItem)
    {
        $this->parentItem = $parentItem;
        return $this;
    }

    /**
     * Set data from outside.
     *
     * @param array $data The data to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setData($data)
    {
        $this->items = $data;
        return $this;
    }

    /**
     * Partition the filters by reference filters.
     *
     * @param object $filters The filters to partition.
     * @return array
     *
     * @since 5.5.0
     */
    public static function partitionByReferenceFilters($filters)
    {
        if (empty($filters) || empty($filters->conditions)) {
            return [null, null, false];
        }

        $hasReferenceFilters = false;
        $conditions = Arr::make($filters->conditions);
        $referenceConditions = $conditions->filter(function ($condition) {
            return $condition->condition === Conditions::IS_INCLUDE_PARENT && !empty($condition->variable);
        })->toArray();

        $regularConditions = $conditions->filter(function ($condition) {
            return $condition->condition !== Conditions::IS_INCLUDE_PARENT;
        })->toArray();

        $referenceFilters = (object) [
            'match' => $filters->match,
            'conditions' => $referenceConditions,
        ];

        $regularFilters = (object) [
            'match' => $filters->match,
            'conditions' => $regularConditions,
        ];

        $hasReferenceFilters = !empty($referenceConditions);

        return [$referenceFilters, $regularFilters, $hasReferenceFilters];
    }

    /**
     * Apply reference filters for all conditions.
     *
     * @param object $filters The filters to apply.
     * @param array $parentItem The parent item to apply the filters to.
     * @return array
     *
     * @since 5.5.0
     */
    public static function applyReferenceFiltersForMatchingAllConditions($filters, $parentItem)
    {
        if (empty($filters) || empty($filters->conditions) || empty($parentItem)) {
            return [];
        }

        $conditions = Arr::make($filters->conditions);
        $variables = $conditions->pluck('variable')->map(function($variable) {
            return CollectionItemsService::createFieldKey($variable);
        });

        $referenceValues = $variables->reduce(function ($carry, $variable) use ($parentItem) {
            $carry[$variable] = $parentItem[$variable] ?? [];
            return $carry;
        }, []);

        $length = $referenceValues->count();
        $counter = $referenceValues->reduce(function ($carry, $value) {
            foreach ($value as $item) {
                $carry[$item['id']] ??= 0;
                $carry[$item['id']]++;
            }
            return $carry;
        }, []);

        $referenceValues = $referenceValues->reduce(function ($carry, $value) {
            return array_merge($carry, $value);
        }, [])->filter(function ($value) use ($counter, $length) {
            return $counter[$value['id']] === $length;
        })->reduce(function ($carry, $value) {
            $carry[$value['id']] = $value;
            return $carry;
        }, [])->toArray();

        return array_values($referenceValues);
    }

    /**
     * Apply reference filters for any conditions.
     *
     * @param object $filters The filters to apply.
     * @param array $parentItem The parent item to apply the filters to.
     * @return array
     *
     * @since 5.5.0
     */
    public static function applyReferenceFiltersForMatchingAnyConditions($filters, $parentItem)
    {
        if (empty($filters) || empty($filters->conditions) || empty($parentItem)) {
            return [];
        }

        $conditions = Arr::make($filters->conditions);
        $variables = $conditions->pluck('variable')->map(function($variable) {
            return CollectionItemsService::createFieldKey($variable);
        });

        $referenceValues = $variables->reduce(function ($carry, $variable) use ($parentItem) {
            $carry[$variable] = $parentItem[$variable] ?? [];
            return $carry;
        }, []);

        $counter = $referenceValues->reduce(function ($carry, $value) {
            foreach ($value as $item) {
                $carry[$item['id']] ??= 0;
                $carry[$item['id']]++;
            }
            return $carry;
        }, []);

        $referenceValues = $referenceValues->reduce(function ($carry, $value) {
            return array_merge($carry, $value);
        }, [])->filter(function ($value) use ($counter) {
            return $counter[$value['id']] > 0;
        })->reduce(function ($carry, $value) {
            $carry[$value['id']] = $value;
            return $carry;
        }, [])->toArray();

        return array_values($referenceValues);
    }

    /**
     * Set the limit.
     *
     * @param int $limit The limit to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setLimit($limit)
    {
        $this->limit = intval($limit ?: -1);
        return $this;
    }

    /**
     * Set the page.
     *
     * @param int $page The page to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setPage($page)
    {
        $this->page = (int) $page;
        return $this;
    }

    /**
     * Set the sorting column.
     *
     * @param int|null $sortingColumn The sorting column to set.
     * @return self
     *
     * @since 6.2.3
     */
    public function setSortingColumn($sortingColumn)
    {
        $this->sortingColumn = $sortingColumn;
        return $this;
    }

    /**
     * Set the direction.
     *
     * @param string $direction The direction to set.
     * @return self
     *
     * @since 5.5.0
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * Check a linear condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkLinearCondition($item, $condition, $allPaths = [])
    {
        $key = $condition->field->path ?? '';
        $key = !empty($key) ? CollectionItemsService::createFieldKey($key) : $key;
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $isCaseSensitive = $condition->is_case_sensitive ?? 0;
        $value = $item[$key] ?? null;
        $fieldType = $condition->field->type ?? '';

        if (!isset($value)) {
            return $checker === Conditions::IS_NOT_SET;
        }

        if (is_array($value)) {
            foreach ($value as $singleValue) {
                $testValue = $singleValue;
                $testConditionValue = $conditionValue;
                
                if (!$isCaseSensitive) {
                    $testValue = !empty($testValue) ? strtolower($testValue) : $testValue;
                    $testConditionValue = !empty($testConditionValue) ? strtolower($testConditionValue) : $testConditionValue;
                }
                
                $matches = $this->checkSingleValue($testValue, $testConditionValue, $checker, $key, $item, $fieldType);
                if ($matches) {
                    return true;
                }
            }
            return false;
        }

        if (!$isCaseSensitive && !is_object($conditionValue)) {
            $value = !empty($value) ? strtolower($value) : $value;
            $conditionValue = !empty($conditionValue) ? strtolower($conditionValue) : $conditionValue;
        }

        return $this->checkSingleValue($value, $conditionValue, $checker, $key, $item, $fieldType);
    }

    /**
     * Check a single value against a condition.
     *
     * @param mixed $value The value to check.
     * @param mixed $conditionValue The condition value to check against.
     * @param string $checker The condition checker.
     * @param string $key The field key.
     * @param array $item The item being checked.
     * @return bool
     *
     * @since 6.0.0
     */
    protected function checkSingleValue($value, $conditionValue, $checker, $key, $item, $fieldType = '')
    {
        switch ($checker) {
            case Conditions::IS_SET:
                return isset($item[$key]);
            case Conditions::IS_NOT_SET:
                return !isset($item[$key]);
            case Conditions::IS_YES:
                return (int) $value === 1;
            case Conditions::IS_NO:
                return (int) $value === 0;
            case Conditions::EQUALS:
                if($conditionValue === 'current-date' && $fieldType === 'date-time') {
                    [$visitorNow, $valueDate] = $this->getComparableDates($value);
                    return $visitorNow->format('Y-m-d') === $valueDate->format('Y-m-d');
                }
                return $value === $conditionValue;
            case Conditions::NOT_EQUALS:
                if($conditionValue === 'current-date' && $fieldType === 'date-time') {
                    [$visitorNow, $valueDate] = $this->getComparableDates($value);
                    return $visitorNow->format('Y-m-d') !== $valueDate->format('Y-m-d');
                }
                return $value !== $conditionValue;
            case Conditions::CONTAINS:
                return strpos($value, $conditionValue) !== false;
            case Conditions::NOT_CONTAINS:
                return strpos($value, $conditionValue) === false;
            case Conditions::STARTS_WITH:
                return strpos($value, $conditionValue) === 0;
            case Conditions::NOT_STARTS_WITH:
                return strpos($value, $conditionValue) !== 0;
            case Conditions::ENDS_WITH:
                return substr($value, -strlen($conditionValue)) === $conditionValue;
            case Conditions::NOT_ENDS_WITH:
                return substr($value, -strlen($conditionValue)) !== $conditionValue;
            case Conditions::IS_GREATER_THAN:
                return $value > $conditionValue;
            case Conditions::IS_LESS_THAN:
                return $value < $conditionValue;
            case Conditions::IS_GREATER_THAN_OR_EQUAL_TO:
                return $value >= $conditionValue;
            case Conditions::IS_LESS_THAN_OR_EQUAL_TO:
                return $value <= $conditionValue;
            case Conditions::IS_BEFORE:
                if($conditionValue === 'current-date' && $fieldType === FieldTypes::DATETIME) {
                    [$visitorNow, $valueDate] = $this->getComparableDates($value);
                    return $valueDate < $visitorNow;
                }
                return strtotime($value) < strtotime($conditionValue);
            case Conditions::IS_BEFORE_OR_EQUAL:
                return strtotime($value) <= strtotime($conditionValue);
            case Conditions::IS_AFTER:
                if($conditionValue === 'current-date' && $fieldType === FieldTypes::DATETIME) {
                    [$visitorNow, $valueDate] = $this->getComparableDates($value);
                    return $valueDate > $visitorNow;
                }
                return strtotime($value) > strtotime($conditionValue);
            case Conditions::IS_AFTER_OR_EQUAL:
                return strtotime($value) >= strtotime($conditionValue);
            case Conditions::IS_BETWEEN_DATE:
                if(!isset($conditionValue->from) || !isset($conditionValue->to)) {
                    return false;
                }
                $valueDate = date('Y-m-d', strtotime($value));
                $fromDate  = date('Y-m-d', strtotime($conditionValue->from));
                $toDate    = date('Y-m-d', strtotime($conditionValue->to));
                return strtotime($valueDate) >= strtotime($fromDate) && strtotime($valueDate) <= strtotime($toDate);
            case Conditions::IS_NOT_BETWEEN_DATE:
                return strtotime($value) < strtotime($conditionValue[0]) || strtotime($value) > strtotime($conditionValue[1]);       
            default:
                return true;
        }
    }

    /**
     * Get comparable dates for date-time field type.
     *
     * @param string $value The date value to compare.
     * @return array An array containing visitor's current date and the value date in visitor's timezone.
     *
     * @since 6.2.3
     */
    protected function getComparableDates($value){
        $app = Factory::getApplication();

        $visitorTimeZone = $app->input->cookie->get(
            'sppb_user_timezone',
            'UTC',
            'STRING'
        );

        if(!$visitorTimeZone){
            $visitorTimeZone = 'UTC';
        }
        
        $serverTZ = new DateTimeZone(Factory::getConfig()->get('offset', 'UTC'));
        $visitorTZ = new DateTimeZone($visitorTimeZone);
        $visitorNow = new DateTime('now', $visitorTZ);
        $valueDate = new DateTime($value, $serverTZ);
        $valueDate->setTimezone($visitorTZ);

        return [$visitorNow, $valueDate];
    }

    /**
     * Check a non-linear condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkNonLinearCondition($item, $condition)
    {
        $fieldType = $condition->field->type ?? '';

        if (empty($fieldType) || !in_array($fieldType, ['self', 'reference', 'multi-reference'])) {
            return false;
        }

        if ($fieldType === 'self') {
            return $this->checkForSelfReference($item, $condition);
        }

        if ($fieldType === 'multi-reference') {
            return $this->checkForMultiReference($item, $condition);
        }

        return $this->checkForSingleReference($item, $condition);
    }

    /**
     * Check for multi-reference condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkForMultiReference($item, $condition)
    {
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $referenceItemIds = $this->getReferenceItemIdList($item['id'], $condition->field->id);

        if (empty($referenceItemIds)) {
            return false;
        }

        switch ($checker) {
            case Conditions::IS_INCLUDE:
                return count(array_intersect($conditionValue, $referenceItemIds)) > 0;
            case Conditions::IS_NOT_INCLUDE:
                return count(array_intersect($conditionValue, $referenceItemIds)) === 0;
            case Conditions::EQUALS_IN_REFERENCE:
                return in_array($conditionValue, $referenceItemIds);
            case Conditions::NOT_EQUALS_IN_REFERENCE:
                return !in_array($conditionValue, $referenceItemIds);
            case Conditions::IS_ASSOCIATED_WITH:
                return in_array($this->currentItemId, $referenceItemIds);
        }
    }

    /**
     * Get the reference item ID.
     *
     * @param int $itemId The item ID.
     * @param int $fieldId The field ID.
     * @return int|null
     *
     * @since 5.5.0
     */
    protected function getReferenceItemId($itemId, $fieldId)
    {
        $value = CollectionItemValue::where('item_id', $itemId)
            ->where('field_id', $fieldId)
            ->first(['reference_item_id']);

        if ($value->isEmpty()) {
            return null;
        }

        return $value->reference_item_id ?? null;
    }

    /**
     * Get the reference item ID list.
     *
     * @param int $itemId The item ID.
     * @param int $fieldId The field ID.
     * @return array
     *
     * @since 5.5.0
     */
    protected function getReferenceItemIdList($itemId, $fieldId)
    {
        $values = CollectionItemValue::where('item_id', $itemId)
            ->where('field_id', $fieldId)
            ->get(['reference_item_id']);

        if (empty($values)) {
            return [];
        }

        return Arr::make($values)->pluck('reference_item_id')->toArray();
    }

    /**
     * Check for single-reference condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkForSingleReference($item, $condition)
    {
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $referenceItemId = $this->getReferenceItemId($item['id'], $condition->field->id);

        if (empty($referenceItemId)) {
            return false;
        }

        switch ($checker) {
            case Conditions::IS_INCLUDE:
                return in_array($referenceItemId, $conditionValue);
            case Conditions::IS_NOT_INCLUDE:
                return !in_array($referenceItemId, $conditionValue);
            case Conditions::EQUALS_IN_REFERENCE:
                return (int) $referenceItemId === (int) $conditionValue;
            case Conditions::NOT_EQUALS_IN_REFERENCE:
                return (int) $referenceItemId !== (int) $conditionValue;
            case Conditions::IS_ASSOCIATED_WITH:
                return (int) $referenceItemId === (int) $this->currentItemId;
        }
    }

    /**
     * Check for self-reference condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function checkForSelfReference($item, $condition)
    {
        $key = static::PRIMARY_KEY;
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $value = $item[$key] ?? null;

        if ($checker === Conditions::RELATED) {
            return $this->checkRelatedCondition($item, $condition, $this->parentItem);
        }

        if (empty($value) || !is_array($conditionValue)) {
            return false;
        }

        switch ($checker) {
            case Conditions::IS_INCLUDE:
                return in_array($value, $conditionValue);
            case Conditions::IS_NOT_INCLUDE:
                return !in_array($value, $conditionValue);
        }
    }

    /**
     * Check related condition for details pages.
     * This condition filters items based on matching field values with the current details page item.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 6.0.0
     */
    protected function checkRelatedCondition($item, $condition, $parentItem = null)
    {
        $associatedFieldPath = $condition->variable ?? '';
        if (empty($associatedFieldPath)) {
            return false;
        }

        if ($item['collection_id'] !== CollectionIds::ARTICLES_COLLECTION_ID) {
            $currentItem = $this->getCurrentDetailsPageItem();
            
            if (empty($currentItem) || $item['id'] === $currentItem['id']) {
                return false;
            }

            $currentItemValue = $this->getFieldValueFromItem($currentItem, $associatedFieldPath);
            
            if ($currentItemValue === null) {
                return false;
            }

            $itemValue = $this->getFieldValueFromItem($item, $associatedFieldPath);
            
            if ($itemValue === null) {
                return false;
            }

            return $currentItemValue == $itemValue;
        } elseif ($item['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID) {
            $currentItem = $this->getArticlesDetailsPageItem($parentItem);

            if (empty($currentItem) || $item['id'] === $currentItem['id']) {
                return false;
            }

            $articleFields = (new CollectionsService)->fetchArticleFields();

            $articleField = array_values(array_filter($articleFields, function ($field) use ($associatedFieldPath) {
                return $field['id'] == $associatedFieldPath;
            }));

            $associatedFieldPath = !empty($articleField[0]['path']) ? $articleField[0]['path'] : null;

            if (empty($associatedFieldPath)) {
                return false;
            }

            $currentItemValue = $this->getArticleFieldValueFromItem($currentItem, $associatedFieldPath);

            if (empty($currentItemValue)) {
                return false;
            }

            $itemValue = $this->getArticleFieldValueFromItem($item, $associatedFieldPath);

            if (empty($itemValue)) {
                return false;
            }

            return $currentItemValue == $itemValue;
        }
    }

    /**
     * Get the article field value from item.
     *
     * @param array $item The item to get value from.
     * @param string $fieldPath The field path.
     * @return mixed The field value or null if not found.
     */
    protected function getArticleFieldValueFromItem($item, $fieldPath)
    {
        if (empty($fieldPath) || empty($item)) {
            return null;
        }

        if (empty($item[$fieldPath])) {
            return null;
        }
        return $item[$fieldPath];
    }

    /**
     * Get the articles details page item.
     *
     * @param int $itemId The item ID.
     * @return array|null The articles details page item or null if not available.
     *
     * @since 6.0.0
     */
    protected function getArticlesDetailsPageItem($parentItem = null)
    {
        $currentItemId = $this->currentItemId ?? null;
        if (empty($currentItemId) && !empty($parentItem)) {
            $currentItemId = $parentItem['id'];
        }

        if (empty($currentItemId)) {
            return null;
        }

        if (!\class_exists('SppagebuilderHelperArticles')) {
            require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
        }

        $articlesCount = \SppagebuilderHelperArticles::getArticlesCount();
        $articles = \SppagebuilderHelperArticles::getArticles($articlesCount);

        $articles = array_values(array_filter($articles, function ($article) use ($currentItemId) {
            return $article->id == $currentItemId;
        }));

        $articles = !empty($articles[0]) ? $articles[0] : null;

        $articles = (array) $articles;

        return $articles;
    }

    /**
     * Get the current details page item.
     *
     * @return array|null The current details page item or null if not available.
     *
     * @since 6.0.0
     */
    protected function getCurrentDetailsPageItem()
    {
        if (empty($this->currentItemId) && empty($this->parentItem)) {
            return null;
        }

        if (empty($this->currentItemId) && !empty($this->parentItem)) {
            return $this->parentItem;
        }

        try {
            $service = new \JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService();
            return $service->fetchCollectionItemById($this->currentItemId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get field value from an item by field path.
     *
     * @param array $item The item to get value from.
     * @param string $fieldPath The field path.
     * @return mixed The field value or null if not found.
     *
     * @since 6.0.0
     */
    protected function getFieldValueFromItem($item, $fieldPath)
    {
        $fieldPath = CollectionItemsService::createFieldKey($fieldPath);
        if (empty($fieldPath) || empty($item)) {
            return null;
        }

        if (isset($item[$fieldPath])) {
            return $item[$fieldPath];
        }

        $pathParts = explode('.', $fieldPath);
        $value = $item;
        
        foreach ($pathParts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Check a condition.
     *
     * @param array $item The item to check.
     * @param object $condition The condition to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function check($item, $condition, $allPaths)
    {
        $checker = $condition->condition ?? '';

        if (in_array($checker, Conditions::getLinearConditions())) {
            return $this->checkLinearCondition($item, $condition, $allPaths);
        }

        return $this->checkNonLinearCondition($item, $condition);
    }

    /**
     * Check if the item matches all conditions.
     *
     * @param array $item The item to check.
     * @param array $conditions The conditions to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function isMatchForAllConditions($item, $conditions, $allPaths = [])
    {
        $shouldPick = true;
        foreach ($conditions as $condition) {
            $shouldPick = $shouldPick && static::check($item, $condition, $allPaths);
        }

        return $shouldPick;
    }

    /**
     * Check if the item matches any conditions.
     *
     * @param array $item The item to check.
     * @param array $conditions The conditions to check.
     * @return bool
     *
     * @since 5.5.0
     */
    protected function isMatchForAnyConditions($item, $conditions, $allPaths = [])
    {
        $shouldPick = false;
        foreach ($conditions as $condition) {
            $shouldPick = $shouldPick || static::check($item, $condition, $allPaths);
        }

        return $shouldPick;
    }

    /**
     * Load data by source.
     *
     * @param int $collectionId The collection ID to load data from.
     * @return self
     *
     * @since 5.5.0
     */
    public function loadDataBySource($collectionId)
    {
        try {
            $items = (new CollectionDataService)->fetchCollectionItems($collectionId, $this->direction, $this->sortingColumn);
        } catch (Throwable $error) {
            $items = [];
        }

        $this->items = $items;

        // Set the item count before slicing by limit.
        $this->totalItems = count($items);

        return $this;
    }

    /**
     * Apply filters to the data.
     *
     * @param object $filters The filters to apply.
     * @return self
     *
     * @since 5.5.0
     */
    public function applyFilters($filters, $allPaths = [])
    {
        $items = $this->items;

        if (empty($filters)) {
            return $this;
        }

        $match = $filters->match ?? Conditions::MATCH_ALL;
        $conditions = $filters->conditions ?? [];

        if (empty($conditions)) {
            return $this;
        }

        $items = Arr::make($items)->filter(function ($item) use ($conditions, $match, $allPaths) {
            return $match === Conditions::MATCH_ALL
                ? $this->isMatchForAllConditions($item, $conditions, $allPaths)
                : $this->isMatchForAnyConditions($item, $conditions, $allPaths);
        })->toArray();

        $this->items = $items;

        // Set the item count before slicing by limit.
        $this->totalItems = count($items);

        return $this;
    }

    public function applyUserFilters($allPaths = [], $currentLink = '', $resume = true)
    {
        if (empty($resume)) {
            return $this;
        }
        $items = $this->items;

        $query = !empty($currentLink) ? $currentLink : Uri::getInstance()->getQuery();
        parse_str($query, $query);
        $queryArray = array_filter($query, function ($key) {
            return strpos($key, 'dc_filter_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $match = Conditions::MATCH_ANY;
        $conditions = [];

        foreach ($queryArray as $key => $value) {
            $fieldPath = str_replace('dc_filter_', '', $key);

            $values = array_map('trim', explode(',', $value));

            foreach ($values as $val) {
                if ($val === '') continue;

                if (strpos($val, 'l-r') !== false) {
                    $range = array_map('trim', explode('l-r', $val));

                    if (count($range) === 2 && is_numeric($range[0]) && is_numeric($range[1])) {
                        $min = $range[0];
                        $max = $range[1];
                        $match = Conditions::MATCH_ALL;

                        $conditions[] = (object)[
                            'field' => (object)[
                                'path' => $fieldPath,
                            ],
                            'condition' => Conditions::IS_GREATER_THAN_OR_EQUAL_TO,
                            'value' => $min,
                            'is_case_sensitive' => 1,
                        ];

                        $conditions[] = (object)[
                            'field' => (object)[
                                'path' => $fieldPath,
                            ],
                            'condition' => Conditions::IS_LESS_THAN_OR_EQUAL_TO,
                            'value' => $max,
                            'is_case_sensitive' => 1,
                        ];

                            continue;
                    }
                }

                if (strpos($val, 'l-to-r') !== false) {
                    $range = array_map('trim', explode('l-to-r', $val));
                    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

                    if (count($range) === 2 && preg_match($datePattern, $range[0]) && preg_match($datePattern, $range[1])) {
                        $min = $range[0];
                        $max = $range[1];

                        $match = Conditions::MATCH_ALL;

                        $conditions[] = (object)[
                            'field' => (object)[
                                'path' => $fieldPath,
                            ],
                            'condition' => Conditions::IS_AFTER_OR_EQUAL,
                            'value' => $min,
                            'is_case_sensitive' => 1,
                        ];

                        $endDate = $max;
                        $max = $endDate . ' 23:59:59';

                        $conditions[] = (object)[
                            'field' => (object)[
                                'path' => $fieldPath,
                            ],
                            'condition' => Conditions::IS_BEFORE_OR_EQUAL,
                            'value' => $max,
                            'is_case_sensitive' => 1,
                        ];

                        continue;
                    }
                }

                if (strpos($val, 'dateval-') !== false) {
                    $dateVal = array_map('trim', explode('dateval-', $val));
                    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

                    if (!empty($dateVal) && count($dateVal) === 2 && empty($dateVal[0]) && !empty($dateVal[1]) && preg_match($datePattern, $dateVal[1])) {
                        $dateVal = $dateVal[1];

                        if (preg_match($datePattern, $dateVal)) {
                            $min = $dateVal . ' 00:00:00';
                            $max = $dateVal . ' 23:59:59';

                            $match = Conditions::MATCH_ALL;

                            $conditions[] = (object)[
                                'field' => (object)[
                                    'path' => $fieldPath,
                                ],
                                'condition' => Conditions::IS_AFTER_OR_EQUAL,
                                'value' => $min,
                                'is_case_sensitive' => 1,
                            ];

                            $conditions[] = (object)[
                                'field' => (object)[
                                    'path' => $fieldPath,
                                ],
                                'condition' => Conditions::IS_BEFORE_OR_EQUAL,
                                'value' => $max,
                                'is_case_sensitive' => 1,
                            ];

                            continue;
                        }
                    }
                }

                $match = Conditions::MATCH_ANY;

                $conditions[] = (object)[
                    'field' => (object)[
                        'path' => $fieldPath,
                    ],
                    'condition' => Conditions::EQUALS,
                    'value' => $val,
                    'is_case_sensitive' => 1,
                ];
            }
            if (empty($conditions)) {
                continue;
            }

            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select([
                    'a.item_id AS original_item_id',
                    'a.reference_item_id',
                    'b.field_id',
                    'b.value'
                ])
                ->from($db->quoteName('#__sppagebuilder_collection_item_values', 'a'))
                ->join('INNER', $db->quoteName('#__sppagebuilder_collection_item_values', 'b') . ' ON a.reference_item_id = b.item_id')
                ->where('a.reference_item_id IS NOT NULL')
                ->where('a.reference_item_id != 0');
            $db->setQuery($query);
            $results = $db->loadAssocList();

            $referenceMap = [];
            foreach ($results as $result) {
                $itemId = $result['original_item_id'];
                $refId = $result['reference_item_id'];
                $fieldId = $result['field_id'];
                $value = $result['value'];
                
                if (!isset($referenceMap[$itemId]['references'][$refId])) {
                    $referenceMap[$itemId]['references'][$refId] = [];
                }
                $referenceMap[$itemId]['references'][$refId][$fieldId] = $value;
            }

            $itemToReferenceFieldsMap = [];
            foreach (array_keys($referenceMap) as $itemId) {
                $visited = [];
                $itemToReferenceFieldsMap[$itemId] = $this->accumulateReferenceFields($itemId, $referenceMap, $visited);
            }

            $items = Arr::make($items)->filter(function ($item) use ($conditions, $match, $allPaths, $itemToReferenceFieldsMap) {
                if($item['id'] && isset($itemToReferenceFieldsMap[$item['id']])){
                    foreach ($itemToReferenceFieldsMap[$item['id']] as $fieldId => $value) {
                        $fieldKey = CollectionItemsService::createFieldKey($fieldId);
                        $item[$fieldKey] = $value;
                    }
                }
                
                return $match === Conditions::MATCH_ALL
                    ? $this->isMatchForAllConditions($item, $conditions, $allPaths)
                    : $this->isMatchForAnyConditions($item, $conditions, $allPaths);
                })->toArray();
            $conditions = [];
        }

        $this->items = $items;

        $this->totalItems = count($items);

        return $this;
    }

    private function accumulateReferenceFields($itemId, &$referenceMap, &$visited = []) {
        if (isset($visited[$itemId])) {
            return [];
        }

        $visited[$itemId] = true;
        $allFields = [];
        
        if (isset($referenceMap[$itemId]['references'])) {
            foreach ($referenceMap[$itemId]['references'] as $refId => $fields) {
                foreach ($fields as $fieldId => $value) {
                    if (isset($allFields[$fieldId])) {
                        if (!is_array($allFields[$fieldId])) {
                            $allFields[$fieldId] = [$allFields[$fieldId]];
                        }
                        $allFields[$fieldId][] = $value;
                    } else {
                        $allFields[$fieldId] = $value;
                    }
                }
                
                $nestedFields = $this->accumulateReferenceFields($refId, $referenceMap, $visited);
                foreach ($nestedFields as $fieldId => $value) {
                    if (isset($allFields[$fieldId])) {
                        if (!is_array($allFields[$fieldId])) {
                            $allFields[$fieldId] = [$allFields[$fieldId]];
                        }
                        if (is_array($value)) {
                            $allFields[$fieldId] = array_merge($allFields[$fieldId], $value);
                        } else {
                            $allFields[$fieldId][] = $value;
                        }
                    } else {
                        $allFields[$fieldId] = $value;
                    }
                }
            }
        }

        return $allFields;
    }

    public function applyUserSearchFilters($collectionId, $path, $allPaths = [], $currentLink = '', $resume = true)
    {
        if (empty($resume)) {
            return $this;
        }
        $items = $this->items;

        $query = !empty($currentLink) ? $currentLink : Uri::getInstance()->getQuery();
        parse_str($query, $query);
        $queryArray = array_filter($query, function ($key) {
            return strpos($key, 'dc_query_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $queryArray = array_filter($queryArray, function ($value, $key) use ($collectionId) {
            return str_replace('dc_query_', '', $key) == $collectionId;
        }, ARRAY_FILTER_USE_BOTH);

        $matchedValue = null;

        foreach ($queryArray as $key => $value) {
            if (str_replace('dc_query_', '', $key) == $collectionId) {
                $matchedValue = (object) [
                    $key => $value,
                ];
                break;
            }
        }

        if (empty($matchedValue)) {
            return $this;
        }

        $queryArray = $matchedValue;
        $cId = str_replace('dc_query_', '', array_keys(get_object_vars($queryArray))[0]);
        $searchValue = array_values(get_object_vars($queryArray))[0];

        $match = Conditions::MATCH_ANY;
        $conditions = [];
        $collectionSchema = (new CollectionsService)->fetchCollectionSchema($cId ?? -1);

        $collectionFields = array_map(function ($field) { 
            return $field->getItem()->id;
        }, $collectionSchema);

        $appliedFields = array_values(array_intersect($collectionFields, $path));

        foreach ($appliedFields as $key => $value) {
            $conditions[] = (object)[
                'field' => (object)[
                    'path' => $value,
                ],
                'condition' => Conditions::CONTAINS,
                'value' => $searchValue,
            ];
        }

        if (empty($conditions)) {
            return $this;
        }

        $items = Arr::make($items)->filter(function ($item) use ($conditions, $match, $allPaths) {
            return $match === Conditions::MATCH_ALL
                ? $this->isMatchForAllConditions($item, $conditions, $allPaths)
                : $this->isMatchForAnyConditions($item, $conditions, $allPaths);
        })->toArray();

        $this->items = $items;

        $this->totalItems = count($items);

        return $this;
    }

    /**
     * Apply filtering for articles or tags sources
     *
     * @param int $source The source type (-2 for articles, -3 for tags)
     * @param array|null $parentItem The parent item for context filtering
     * @param object|null $filters The filter settings to apply
     * @return self
     * 
     * @since 6.0.0
     */
    public function applyArticleOrTagsFilter($source, $parentItem = null, $filters = null)
    {
        $this->sourceCollectionId = $source;
        $this->parentItem = $parentItem;
        $this->filters = $filters;

        if (empty($this->items) || empty($filters)) {
            return $this;
        }

        $match = $filters->match ?? Conditions::MATCH_ALL;
        $conditions = $filters->conditions ?? [];

        if (empty($conditions)) {
            return $this;
        }

        $this->items = Arr::make($this->items)->filter(function ($item) use ($conditions, $match, $parentItem) {
            return $match === Conditions::MATCH_ALL
                ? $this->isMatchForAllConditionsArticlesTags($item, $conditions, $parentItem)
                : $this->isMatchForAnyConditionsArticlesTags($item, $conditions, $parentItem);
        })->toArray();

        $this->totalItems = count($this->items);

        return $this;
    }

    /**
     * Check if an item matches all conditions
     *
     * @param array $item The item to check
     * @param array $conditions The conditions to check
     * @param array $parentItem The parent item for reference context
     * @return bool Whether all conditions are met
     * 
     * @since 6.0.0
     */
    protected function isMatchForAllConditionsArticlesTags($item, $conditions, $parentItem = null)
    {
        $shouldPick = true;
        foreach ($conditions as $condition) {
            $shouldPick = $shouldPick && $this->checkArticleTagCondition($item, $condition, $parentItem);
        }

        return $shouldPick;
    }

    /**
     * Check if an item matches any conditions
     *
     * @param array $item The item to check
     * @param array $conditions The conditions to check
     * @param array $parentItem The parent item for reference context
     * @return bool Whether any condition is met
     * 
     * @since 6.0.0
     */
    protected function isMatchForAnyConditionsArticlesTags($item, $conditions, $parentItem = null)
    {
        $shouldPick = false;
        foreach ($conditions as $condition) {
            $shouldPick = $shouldPick || $this->checkArticleTagCondition($item, $condition, $parentItem);
        }

        return $shouldPick;
    }

    /**
     * Check a condition for items
     *
     * @param array $item The item to check
     * @param object $condition The condition to check
     * @param array $parentItem The parent item for reference context
     * @return bool Whether the condition is met
     * 
     * @since 6.0.0
     */
    protected function checkArticleTagCondition($item, $condition, $parentItem = null)
    {
        $checker = $condition->condition ?? '';

        if (in_array($checker, Conditions::getLinearConditions())) {
            return $this->checkLinearConditionArticlesTags($item, $condition);
        }

        return $this->checkNonLinearConditionArticlesTags($item, $condition, $parentItem);
    }

    /**
     * Check a linear condition for items
     *
     * @param array $item The item to check
     * @param object $condition The condition to check
     * @return bool Whether the condition is met
     * 
     * @since 6.0.0
     */
    protected function checkLinearConditionArticlesTags($item, $condition)
    {
        $key = $condition->field->path ?? '';
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $isCaseSensitive = $condition->is_case_sensitive ?? 0;
        
        $value = $this->getArticleTagFieldValue($item, $key);

        if (!isset($value)) {
            if (in_array($checker, [Conditions::IS_SET, Conditions::IS_NOT_SET])) {
                return $checker === Conditions::IS_NOT_SET;
            }
            return false;
        }

        if (!$isCaseSensitive && is_string($value) && is_string($conditionValue)) {
            $value = strtolower($value);
            $conditionValue = strtolower($conditionValue);
        }

        switch ($checker) {
            case Conditions::IS_SET:
                return isset($value);
            case Conditions::IS_NOT_SET:
                return !isset($value);
            case Conditions::IS_YES:
                return (int) $value === 1;
            case Conditions::IS_NO:
                return (int) $value === 0;
            case Conditions::EQUALS:
                return $value == $conditionValue;
            case Conditions::NOT_EQUALS:
                return $value != $conditionValue;
            case Conditions::CONTAINS:
                return is_string($value) && is_string($conditionValue) && strpos($value, $conditionValue) !== false;
            case Conditions::NOT_CONTAINS:
                return is_string($value) && is_string($conditionValue) && strpos($value, $conditionValue) === false;
            case Conditions::STARTS_WITH:
                return is_string($value) && is_string($conditionValue) && strpos($value, $conditionValue) === 0;
            case Conditions::NOT_STARTS_WITH:
                return is_string($value) && is_string($conditionValue) && strpos($value, $conditionValue) !== 0;
            case Conditions::ENDS_WITH:
                return is_string($value) && is_string($conditionValue) && substr($value, -strlen($conditionValue)) === $conditionValue;
            case Conditions::NOT_ENDS_WITH:
                return is_string($value) && is_string($conditionValue) && substr($value, -strlen($conditionValue)) !== $conditionValue;
            case Conditions::IS_GREATER_THAN:
                return is_numeric($value) && is_numeric($conditionValue) && $value > $conditionValue;
            case Conditions::IS_LESS_THAN:
                return is_numeric($value) && is_numeric($conditionValue) && $value < $conditionValue;
            case Conditions::IS_GREATER_THAN_OR_EQUAL_TO:
                return is_numeric($value) && is_numeric($conditionValue) && $value >= $conditionValue;
            case Conditions::IS_LESS_THAN_OR_EQUAL_TO:
                return is_numeric($value) && is_numeric($conditionValue) && $value <= $conditionValue;
            case Conditions::IS_BEFORE:
                return strtotime($value) < strtotime($conditionValue);
            case Conditions::IS_BEFORE_OR_EQUAL:
                return strtotime($value) <= strtotime($conditionValue);
            case Conditions::IS_AFTER:
                return strtotime($value) > strtotime($conditionValue);
            case Conditions::IS_AFTER_OR_EQUAL:
                return strtotime($value) >= strtotime($conditionValue);
            default:
                return false;
        }
    }

    /**
     * Check a non-linear condition for items
     *
     * @param array $item The item to check
     * @param object $condition The condition to check
     * @param array $parentItem The parent item for reference context
     * @return bool Whether the condition is met
     * 
     * @since 6.0.0
     */
    protected function checkNonLinearConditionArticlesTags($item, $condition, $parentItem = null)
    {
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';
        $fieldType = $condition->field->type ?? '';

        switch ($checker) {
            case Conditions::IS_INCLUDE:
                if ($fieldType === 'self') {
                    return is_array($conditionValue) && in_array($item['id'], $conditionValue);
                }
                return $this->checkArticleTagReferenceCondition($item, $condition, $parentItem);
                
            case Conditions::IS_NOT_INCLUDE:
                if ($fieldType === 'self') {
                    return !is_array($conditionValue) || !in_array($item['id'], $conditionValue);
                }
                return !$this->checkArticleTagReferenceCondition($item, $condition, $parentItem);
                
            case Conditions::IS_INCLUDE_PARENT:
                return $this->checkParentChildRelationship($item, $parentItem);
                
            case Conditions::EQUALS_IN_REFERENCE:
            case Conditions::NOT_EQUALS_IN_REFERENCE:
                return $this->checkArticleTagReferenceCondition($item, $condition, $parentItem);
                
            case Conditions::IS_ASSOCIATED_WITH:
                return $this->checkArticleTagAssociation($item, $condition, $parentItem);

            case Conditions::RELATED:
                return $this->checkArticleTagRelatedCondition($item, $condition, $parentItem);
                
            default:
                return false;
        }
    }

    /**
     * Check related condition for items
     *
     * @param array $item The item to check
     * @param object $condition The condition to check
     * @return bool Whether the condition is met
     */
    protected function checkArticleTagRelatedCondition($item, $condition, $parentItem = null)
    {
        return $this->checkRelatedCondition($item, $condition, $parentItem);
    }

    /**
     * Get field value from item
     *
     * @param array $item The item to get value from
     * @param string $fieldPath The field path
     * @return mixed The field value
     * 
     * @since 6.0.0
     */
    protected function getArticleTagFieldValue($item, $fieldPath)
    {
        if (empty($fieldPath)) {
            return null;
        }

        if (isset($item[$fieldPath])) {
            return $item[$fieldPath];
        }

        $pathParts = explode('.', $fieldPath);
        $value = $item;
        
        foreach ($pathParts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Check reference condition for items
     *
     * @param array $item The item to check
     * @param object $condition The condition to check
     * @param array $parentItem The parent item for reference context
     * @return bool Whether the condition is met
     * 
     * @since 6.0.0
     */
    protected function checkArticleTagReferenceCondition($item, $condition, $parentItem = null)
    {
        $conditionValue = $condition->value ?? '';
        $checker = $condition->condition ?? '';

        if ($this->sourceCollectionId === CollectionIds::ARTICLES_COLLECTION_ID && !empty($parentItem) && $parentItem['collection_id'] === CollectionIds::TAGS_COLLECTION_ID) {
            $articleId = $parentItem['id'];
            return $this->isTagAssociatedWithArticle($item['id'], $articleId);
        }

        if ($this->sourceCollectionId === CollectionIds::TAGS_COLLECTION_ID && !empty($parentItem) && $parentItem['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID) {
            $tagId = $parentItem['id'];
            return $this->isArticleAssociatedWithTag($item['id'], $tagId);
        }

        return false;
    }

    /**
     * Check association condition for items
     *
     * @param array $item The item to check
     * @param object $condition The condition to check
     * @param array $parentItem The parent item for reference context
     * @return bool Whether the association exists
     * 
     * @since 6.0.0
     */
    protected function checkArticleTagAssociation($item, $condition, $parentItem = null)
    {
        if (empty($parentItem)) {
            return false;
        }

        $currentItemId = $this->currentItemId;
        
        if (empty($currentItemId)) {
            return false;
        }

        if ($this->sourceCollectionId === CollectionIds::TAGS_COLLECTION_ID) {
            return $this->isArticleAssociatedWithTag($currentItemId, $item['id']);
        }

        if ($this->sourceCollectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
            return $this->isTagAssociatedWithArticle($item['id'], $currentItemId);
        }

        return false;
    }

    /**
     * Check parent-child relationship between items
     *
     * @param array $item The child item to check
     * @param array|null $parentItem The parent item for reference
     * @return bool Whether the relationship exists
     * 
     * @since 6.0.0
     */
    protected function checkParentChildRelationship($item, $parentItem)
    {
        if (empty($parentItem) || empty($item)) {
            return false;
        }

        $childCollectionId = $item['collection_id'] ?? null;
        $parentCollectionId = $parentItem['collection_id'] ?? null;

        if ($childCollectionId === CollectionIds::TAGS_COLLECTION_ID && $parentCollectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
            return $this->isTagAssociatedWithArticle($item['id'], $parentItem['id']);
        }

        if ($childCollectionId === CollectionIds::ARTICLES_COLLECTION_ID && $parentCollectionId === CollectionIds::TAGS_COLLECTION_ID) {
            return $this->isArticleAssociatedWithTag($item['id'], $parentItem['id']);
        }

        return false;
    }

    /**
     * Check if multiple reference condition exists
     *
     * @param array $conditions The conditions to check
     * @param array $parentItem The parent item
     * @return bool Whether the condition exists
     * 
     * @since 6.0.0
     */
    protected function checkArticleMultiReferenceCondition($conditions, $parentItem)
    {
        foreach ($conditions as $condition) {
            $conditionType = $condition->condition ?? '';
            $conditionFieldType = $condition->field->type ?? '';

            if ($conditionType === Conditions::IS_INCLUDE_PARENT && $conditionFieldType === 'self') {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter items by parent item
     *
     * @param array $tags The items to filter
     * @param array $parentArticle The parent item
     * @return array Filtered items
     * 
     * @since 6.0.0
     */
    protected function filterTagsByParentArticle($tags, $parentArticle)
    {
        if (empty($tags) || empty($parentArticle) || !isset($parentArticle['id'])) {
            return $tags;
        }

        $articleId = $parentArticle['id'];

        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('tag_id')
            ->from('#__contentitem_tag_map')
            ->where('content_item_id = ' . (int) $articleId)
            ->where('type_alias = ' . $db->quote('com_content.article'));
        $db->setQuery($query);

        try {
            $articleTagIds = $db->loadColumn();
            if (empty($articleTagIds)) {
                return [];
            }

            return array_filter($tags, function($tag) use ($articleTagIds) {
                return in_array($tag['id'], $articleTagIds);
            });
        } catch (\Exception $e) {
            return $tags;
        }
    }

    /**
     * Check if item is associated with another item
     *
     * @param int $tagId The item ID
     * @param int $articleId The other item ID
     * @return bool Whether association exists
     * 
     * @since 6.0.0
     */
    protected function isTagAssociatedWithArticle($tagId, $articleId)
    {
        if (empty($tagId) || empty($articleId)) {
            return false;
        }

        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__contentitem_tag_map')
            ->where('content_item_id = ' . (int) $articleId)
            ->where('tag_id = ' . (int) $tagId)
            ->where('type_alias = ' . $db->quote('com_content.article'));
        $db->setQuery($query);

        try {
            $count = $db->loadResult();
            return $count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if item is associated with another item
     *
     * @param int $articleId The item ID  
     * @param int $tagId The other item ID
     * @return bool Whether association exists
     * 
     * @since 6.0.0
     */
    protected function isArticleAssociatedWithTag($articleId, $tagId)
    {
        if (empty($articleId) || empty($tagId)) {
            return false;
        }

        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__contentitem_tag_map')
            ->where('content_item_id = ' . (int) $articleId)
            ->where('tag_id = ' . (int) $tagId)
            ->where('type_alias = ' . $db->quote('com_content.article'));
        $db->setQuery($query);

        try {
            $count = $db->loadResult();
            return $count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Load data by source for special collections
     *
     * @param int $collectionId The collection ID
     * @return self
     * 
     * @since 6.0.0
     */
    public function loadDataBySourceForArticlesTags($collectionId)
    {
        try {
            if ($collectionId === CollectionIds::ARTICLES_COLLECTION_ID) {
                $items = $this->fetchArticleItems($this->limit, $this->direction, -1);
            } elseif ($collectionId === CollectionIds::TAGS_COLLECTION_ID) {
                $items = $this->fetchTagItems($this->limit, $this->direction);
            } else {
                $items = [];
            }
        } catch (Throwable $error) {
            $items = [];
        }

        $this->items = $items;
        $this->totalItems = count($items);

        return $this;
    }

    /**
     * Fetch items from source
     *
     * @param int $limit The limit
     * @param string $direction The direction
     * @return array The items
     * 
     * @since 6.0.0
     */
    protected function fetchArticleItems($limit, $direction, $page = 1)
    {
        if (!\class_exists('SppagebuilderHelperArticles')) {
            require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
        }

        try {
            $ordering = strtoupper($direction) === 'DESC' ? 'latest' : 'oldest';
            $articles = \SppagebuilderHelperArticles::getArticles(\SppagebuilderHelperArticles::getArticlesCount(), $ordering, '', true, '', [], 1, $page);
            
            return array_map(function ($article) {
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
            return [];
        }
    }

    /**
     * Fetch items from source
     *
     * @param int $limit The limit
     * @param string $direction The direction
     * @return array The items
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
            \Joomla\CMS\Factory::getApplication()->enqueueMessage('Error fetching tags for dynamic content: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get the data.
     *
     * @return array
     *
     * @since 5.5.0
     */
    public function getData()
    {
        return $this->applyPagination($this->items);
    }

    /**
     * Apply pagination to the items.
     *
     * @param array $items The items to apply pagination to.
     * @return array
     *
     * @since 5.5.0
     */
    public function applyPagination($items)
    {
        $limit = $this->limit;

        if ($limit < 0) {
            return $items;
        }

        $page = $this->page;
        $offset = $limit * ($page - 1);

        return array_slice($items, $offset, $limit);
    }

    /**
     * Get the total items.
     *
     * @return int
     *
     * @since 5.5.0
     */
    public function getItemCount()
    {
        return $this->totalItems;
    }

    /**
     * Get the total pages.
     *
     * @return int
     *
     * @since 5.5.0
     */
    public function getTotalPages()
    {
        return ceil($this->totalItems / $this->limit);
    }
}
