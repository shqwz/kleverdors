<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent;

defined('_JEXEC') or die;

use Closure;
use RuntimeException;
use Throwable;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Concerns\HasEagerLoading;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\Operators;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Expression;

class QueryBuilder
{
    use HasEagerLoading;

    /**
     * The database driver instance.
     *
     * @var DatabaseDriver
     * @since 5.5.0
     */
    private $db;

    /**
     * The database query instance.
     *
     * @var DatabaseQuery
     * @since 5.5.0
     */
    private $query;

    /**
     * The limit of the query.
     *
     * @var int
     * @since 5.5.0
     */
    private $limit = 0;

    /**
     * The offset of the query.
     *
     * @var int
     * @since 5.5.0
     */
    private $offset = 0;

    /**
     * The columns of the query.
     *
     * @var array
     * @since 5.5.0
     */
    private $columns = [];

    /**
     * The model instance.
     *
     * @var Model
     * @since 5.5.0
     */
    protected $model;

    /**
     * The data of the query.
     *
     * @var array
     * @since 5.5.0
     */
    protected $data;

    /**
     * The static database driver instance.
     *
     * @var DatabaseDriver
     * @since 5.5.0
     */
    protected static $staticDb = null;

    /**
     * The operators that do not require a value.
     *
     * @var array
     * @since 5.5.0
     */
    protected const NO_VALUE_OPERATORS = [Operators::IS_NULL, Operators::IS_NOT_NULL];

    /**
     * The operators that require a value.
     *
     * @var array
     * @since 5.5.0
     */
    protected const IN_OPERATORS = [Operators::IN, Operators::NOT_IN];

    /**
     * The constructor of the class.
     *
     * @param Model $model
     * @since 5.5.0
     */
    public function __construct(Model $model)
    {
        $this->db = Factory::getDbo();
        $this->query = $this->db->getQuery(true);
        $this->model = $model;
        $this->columns = [];
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the database query instance.
     *
     * @return DatabaseQuery
     * @since 5.5.0
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get the database driver instance.
     *
     * @return DatabaseDriver
     * @since 5.5.0
     */
    public function getDatabase()
    {
        return $this->db;
    }

    /**
     * Get the static database driver instance.
     *
     * @return DatabaseDriver
     * @since 5.5.0
     */
    protected static function getStaticDb()
    {
        if (is_null(self::$staticDb)) {
            self::$staticDb = Factory::getDbo();
        }

        return self::$staticDb;
    }

    /**
     * Clear the query.
     *
     * @param string $clause The clause
     * @since 5.5.0
     */
    public function clearQuery($clause = null)
    {
        if (is_null($clause)) {
            $this->query->clear();
        } elseif (is_array($clause)) {
            foreach ($clause as $item) {
                $this->query->clear($item);
            }
        } else {
            $this->query->clear($clause);
        }

        return $this;
    }

    /**
     * Get the prefix of the query.
     *
     * @return string
     * @since 5.5.0
     */
    public function getPrefix()
    {
        return $this->model->getName();
    }

    /**
     * Quote the column name with the prefix.
     *
     * @param string $column
     * @param string $as
     * 
     * @return string
     * @since 5.5.0
     */
    public function quoteNameWithPrefix($column, $as = null)
    {   
        $hasPrefix = strpos($column, '.') !== false;

        if ($hasPrefix) {
            return $this->db->quoteName($column, $as);
        }

        $prefix = $this->getPrefix();
        $column = $prefix . '.' . $column;

        return $this->db->quoteName($column, $as);
    }

    /**
     * Quote the column name.
     *
     * @param string $column
     * @param string $as
     * @return string
     * @since 5.5.0
     */
    public function quoteName($column, $as = null)
    {
        return $this->db->quoteName($column, $as);
    }

    /**
     * Quote the value.
     *
     * @param string $value
     * @return string
     * @since 5.5.0
     */
    public function quote($value)
    {
        return $this->db->quote($value);
    }

    /**
     * Set the limit of the query for getting a limited number of records.
     *
     * @param int $limit
     * 
     * @return self
     * @since 5.5.0
     */
    public function take(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the offset of the query for skipping a number of records.
     *
     * @param int $offset
     * 
     * @return self
     * @since 5.5.0
     */
    public function skip(int $offset = 0)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set the columns of the query.
     *
     * @param array $columns
     * 
     * @return self
     * @since 5.5.0
     */
    public function setColumns(array $columns = [])
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Get the columns of the query.
     *
     * @return array
     * @since 5.5.0
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Eager load the relations of the query.
     *
     * @param string|array $relations
     * 
     * @return self
     * @since 5.5.0
     */
    public function with($relations)
    {
        return $this->setEagerRelations($relations);
    }
    /**
     * Performing the AND where conditions.
     *
     * @param array $conditions
     *
     * @return self
     * @since 5.5.0
     */
    public function where($column, $operator = Operators::EQUAL, $value = null)
    {   
        $this->query->where($this->buildWhere($column, $operator, $value));
        return $this;
    }

    /**
     * Performing the NOT where conditions.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return self
     * @since 5.5.0
     */
    public function whereNot($column, $value)
    {
        return $this->where($column, Operators::NOT_EQUAL, $value);
    }

    /**
     * Performing the greater than where conditions.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return self
     * @since 5.5.0
     */
    public function whereGreaterThan($column, $value)
    {
        return $this->where($column, Operators::GREATER_THAN, $value);
    }

    /**
     * Performing the less than where conditions.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return self
     * @since 5.5.0
     */
    public function whereLessThan($column, $value)
    {
        return $this->where($column, Operators::LESS_THAN, $value);
    }

    /**
     * Performing the greater than or equal where conditions.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return self
     * @since 5.5.0
     */
    public function whereGreaterThanOrEqual($column, $value)
    {
        return $this->where($column, Operators::GREATER_THAN_OR_EQUAL, $value);
    }

    /**
     * Performing the less than or equal where conditions.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return self
     * @since 5.5.0
     */
    public function whereLessThanOrEqual($column, $value)
    {
        return $this->where($column, Operators::LESS_THAN_OR_EQUAL, $value);
    }

    /**
     * Performing the IN where conditions.
     *
     * @param string $column
     * @param array $values
     *
     * @return self
     * @since 5.5.0
     */
    public function whereIn($column, array $values)
    {
        return $this->where($column, Operators::IN, $values);
    }

    /**
     * Performing the NOT IN where conditions.
     *
     * @param string $column
     * @param array $values
     *
     * @return self
     * @since 5.5.0
     */
    public function whereNotIn($column, array $values)
    {
        return $this->where($column, Operators::NOT_IN, $values);
    }

    /**
     * Performing the IS NULL where conditions.
     *
     * @param string $column
     *
     * @return self
     * @since 5.5.0
     */
    public function whereNull($column)
    {
        return $this->where($column, Operators::IS_NULL);
    }

    /**
     * Performing the IS NOT NULL where conditions.
     *
     * @param string $column
     *
     * @return self
     * @since 5.5.0
     */
    public function whereNotNull($column)
    {
        return $this->where($column, Operators::IS_NOT_NULL);
    }

    /**
     * Performing the BETWEEN where conditions.
     *
     * @param string $column
     * @param array $values
     *
     * @return self
     * @since 5.5.0
     */
    public function whereBetween($column, array $values)
    {
        return $this->where($column, Operators::BETWEEN, $values);
    }

    /**
     * Performing the LIKE where conditions.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return self
     * @since 5.5.0
     */
    public function whereLike($column, $value)
    {
        return $this->where($column, Operators::LIKE, $value);
    }

    /**
     * Performing the NOT LIKE where conditions.
     *
     * @param string $column
     * @param mixed $value
     *
     * @return self
     * @since 5.5.0
     */
    public function whereNotLike($column, $value)
    {
        return $this->where($column, Operators::NOT_LIKE, $value);
    }

    /**
     * Performing the EXISTS where conditions.
     *
     * @param Closure $callback
     *
     * @return self
     * @since 5.5.0
     */
    public function whereExists(Closure $callback)
    {
        $db = $this->getDatabase();
        $query = $callback($db);
        $this->query->where('EXISTS (' . $query->__toString() . ')');

        return $this;
    }

    /**
     * Performing the raw where conditions.
     *
     * @param Closure $callback
     *
     * @return self
     * @since 5.5.0
     */
    public function whereRaw(Closure $callback)
    {
        $db = $this->getDatabase();
        $queryString = $callback($db);
        $this->query->where($queryString);
        return $this;
    }

    /**
     * Performing the OR where conditions.
     *
     * @param array $conditions
     *
     * @return self
     * @since 5.5.0
     */
    public function orWhere($column, $operator = Operators::EQUAL, $value = null)
    {
        $this->query->orWhere($this->buildWhere($column, $operator, $value));
        return $this;
    }

    /**
     * Performing the raw where conditions.
     *
     * @param Closure $callback
     *
     * @return self
     * @since 5.5.0
     */
    public function rawQuery(Closure $callback)
    {
        return tap($this, $callback);
    }

    /**
     * Set the columns of the query.
     *
     * @param array $columns
     * 
     * @return self
     * @since 5.5.0
     */
    public function select($columns)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $columns = array_map(function ($column, $key) {
            if (is_numeric($key)) {
                return $this->quoteNameWithPrefix($column);
            }

            return $this->quoteNameWithPrefix($column, $key);
        }, $columns, array_keys($columns));

        $this->query->select($columns);
        return $this;
    }

    /**
     * Find the record by the primary key value.
     *
     * @param mixed $primaryKeyValue
     *
     * @return Model
     * @since 5.5.0
     */
    public function find($primaryKeyValue)
    {
        return $this->where($this->model->getPrimaryKey(), $primaryKeyValue)->first();
    }

    /**
     * Set the order of the query.
     *
     * @param string $column
     * @param string $direction
     * 
     * @return self
     * @since 5.5.0
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->query->order($this->db->escape($this->quoteNameWithPrefix($column)) . ' ' . strtoupper($direction));

        return $this;
    }

    /**
     * Join the related model to the query.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * @param string $type
     * 
     * @return self
     * @since 5.5.0
     */
    public function join($related, $foreignKey = null, $localKey = null, $type = 'INNER')
    {
        $relatedModel = new $related();
        $relatedQueryProvider = new static($relatedModel);
        $foreignKey = $foreignKey ?? $this->model->getName() . '_' . $relatedModel->getPrimaryKey();
        $localKey = $localKey ?? $relatedModel->getPrimaryKey();

        $this->query->join(
            $type,
            $this->db->quoteName($relatedModel->getTable(), $relatedModel->getName()) . ' ON (' .
            $relatedQueryProvider->quoteNameWithPrefix($foreignKey) .  ' = ' . $this->quoteNameWithPrefix($localKey) . ')'
        );

        return $this;
    }

    /**
     * Join the related model to the query as a left join.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * 
     * @return self
     * @since 5.5.0
     */
    public function leftJoin($related, $foreignKey = null, $localKey = null)
    {
        return $this->join($related, $foreignKey, $localKey, 'LEFT');
    }

    /**
     * Join the related model to the query as a right join.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * 
     * @return self
     * @since 5.5.0
     */
    public function rightJoin($related, $foreignKey = null, $localKey = null)
    {
        return $this->join($related, $foreignKey, $localKey, 'RIGHT');
    }

    /**
     * Join the related model to the query as a full join.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * 
     * @return self
     * @since 5.5.0
     */
    public function fullJoin($related, $foreignKey = null, $localKey = null)
    {
        return $this->join($related, $foreignKey, $localKey, 'FULL');
    }

    /**
     * Join the related model to the query as an inner join.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * 
     * @return self
     * @since 5.5.0
     */
    public function innerJoin($related, $foreignKey = null, $localKey = null)
    {
        return $this->join($related, $foreignKey, $localKey, 'INNER');
    }

    /**
     * Get the data records from the database.
     *
     * @param array $columns
     * @return array
     * @since 5.5.0
     */
    public function get(array $columns = [])
    {
        $this->setColumns($columns);
        $this->prepareQuery();
        $results = $this->db->loadObjectList();

        if (empty($results)) {
            return [];
        }

        $models = array_map([$this->model, 'newInstance'], $results);

        if ($this->hasEagerRelations()) {
            return $this->eagerLoadRelations($models);
        }

        return $models;
    }

    /**
     * Get the first record from the database.
     *
     * @param array $columns
     * 
     * @return Model
     * @since 5.5.0
     */
    public function first(array $columns = [])
    {
        $this->setColumns($columns);
        $this->prepareQuery();

        $data = $this->db->loadObject();
        $this->model->setItem($data);

        // No need to load relations eagerly if the model is empty
        if ($this->model->isEmpty()) {
            return $this->model;
        }

        if ($this->hasEagerRelations()) {
            $this->eagerLoadRelations([$this->model]);
        }

        return $this->model;
    }

    /**
     * Check if the record exists.
     *
     * @return bool
     * @since 5.5.0
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Get the total number of records from the database.
     *
     * @return int
     * @since 5.5.0
     */
    public function count()
    {
        $this->clearQuery([
            'select',
            'from',
            'limit',
            'offset',
        ]);

        $primaryKey = $this->model->getPrimaryKey();
        $this->buildSelect("COUNT({$primaryKey}) as total");
        $this->buildFrom($this->model->getTable());

        try
        {
            $this->db->setQuery($this->query);
            $this->db->execute();
        }
        catch (Throwable $error)
        {
            return 0;
        }

        return $this->db->loadResult();
    }

    /**
     * Get the total number of records from the database for pagination.
     *
     * @return int
     * @since 5.5.0
     */
    public function runPaginationCountQuery()
    {
        $new = $this->newInstance();
        $new->query = $this->cloneWithout(['select', 'from', 'limit', 'offset', 'join']);

        $primaryKey = $new->model->getPrimaryKey();
        $new->buildSelect("COUNT({$primaryKey}) as total");
        $new->buildFrom($new->model->getTable());

        try
        {
            $new->db->setQuery($new->query);
            $new->db->execute();
        }
        catch (Throwable $error)
        {
            return 0;
        }

        return $new->db->loadResult();
    }

    /**
     * Get the paginated data from the database.
     *
     * @param int $perPage
     * @param int $currentPage
     * @param array $columns
     *
     * @return array
     * @since 5.5.0
     */
    public function paginate($perPage = 10, $currentPage = 1, array $columns = [])
    {
        $this->take($perPage);
        $this->skip(($currentPage - 1) * $perPage);
        $results = $this->get($columns);
        $count = $this->runPaginationCountQuery();

        return [
            'data' => $results,
            'total' => $count,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => ceil($count / $perPage),
            'total_pages' => ceil($count / $perPage),
        ];
    }

    /**
     * Create a new record in the database.
     *
     * @param array $data
     *
     * @return int
     * @since 5.5.0
     */
    public function create(array $data)
    {
        // If the model has an ordering key, set the ordering value to the latest ordering value + 1
        if (!empty($this->model->getOrderingKey())) {
            $data[$this->model->getOrderingKey()] = $this->getLatestOrderingValue() + 1;
        }

        $this->query->insert($this->db->quoteName($this->model->getTable()))
            ->columns($this->getRecordColumns($data))
            ->values($this->prepareInsertionValue($data));

        try
        {
            $this->db->setQuery($this->query);
            $this->db->execute();
            $insertId = $this->db->insertId();

            if ($this->model->hasAsset()) {
                $this->model->newQuery()
                    ->find($insertId)
                    ->manageAsset();
            }

            return $insertId;
        }
        catch (Throwable $error)
        {
            throw $error;
        }
    }

    /**
     * Create multiple records in the database.
     *
     * @param array $data
     *
     * @return int
     * @since 5.5.0
     */
    public function createMany(array $data)
    {
        if (!empty($orderingKey = $this->model->getOrderingKey())) {
            $lastOrderingValue = $this->getLatestOrderingValue();

            foreach ($data as &$item) {
                $item[$orderingKey] = ++$lastOrderingValue;
            }

            unset($item);
        }

        $this->query
            ->insert($this->db->quoteName($this->model->getTable()))
            ->columns($this->db->quoteName($this->getRecordColumns($data)));

        $values = Arr::make($data)->map(function($record) {
            return $this->prepareInsertionValue($record);
        });

        $this->query->values($values->toArray());

        try
        {
            $this->db->setQuery($this->query);
            $this->db->execute();
        }
        catch (Throwable $error)
        {
            throw $error;
        }

        return $this->db->getAffectedRows();
    }

    /**
     * Update a record in the database.
     *
     * @param array|callable $data
     *
     * @return bool
     * @since 5.5.0
     */
    public function update($data)
    {
        if (is_callable($data)) {
            $fields = $data($this);
        } else {
            $fields = $this->prepareUpdateValues($data);
        }

        $this->query->update(
            $this->db->quoteName(
                $this->model->getTable(),
                $this->model->getName()
            )
        )->set($fields);

        try
        {
            $this->db->setQuery($this->query);
            return $this->db->execute();
        }
        catch (Throwable $error)
        {
            throw $error;
        }
    }

    /**
     * Delete a record from the database.
     *
     * @return bool
     * @since 5.5.0
     */
    public function delete()
    {
        $this->query->delete(
            $this->quoteName(
                $this->model->getTable(),
                $this->hasJoinClause() ? $this->model->getName() : null
            )
        );

        if (!$this->hasJoinClause()) {
            $this->removeAliasFromWhereClauses();
        }

        try
        {
            $this->db->setQuery($this->query);
            return $this->db->execute();
        }
        catch (Throwable $error)
        {
            throw $error;
        }

        return false;
    }

    /**
     * Begin the transaction.
     *
     * @return void
     * @since 5.5.0
     */
    public static function beginTransaction()
    {
        $db = static::getStaticDb();
        $db->transactionStart();
    }

    /**
     * Commit the transaction.
     *
     * @return void
     * @since 5.5.0
     */
    public static function commit()
    {
        $db = static::getStaticDb();
        $db->transactionCommit();
    }

    /**
     * Rollback the transaction.
     *
     * @return void
     * @since 5.5.0
     */
    public static function rollback()
    {
        $db = static::getStaticDb();
        $db->transactionRollback();
    }

    /**
     * Execute a callback within a transaction.
     *
     * @param callable $callback
     * 
     * @return void
     * @since 5.5.0
     */
    public static function transaction(callable $callback)
    {
        static::beginTransaction();

        try
        {
            $callback();
        }
        catch (Throwable $error)
        {
            static::rollback();
            throw $error;
        }

        static::commit();
    }

    /**
     * Create a raw expression.
     *
     * @param mixed $value
     *
     * @return Expression
     * @since 5.5.0
     */
    public static function raw($value)
    {
        return new Expression($value);
    }

    /**
     * Check if the query has a join clause.
     *
     * @return bool
     * @since 5.5.1
     */
    protected function hasJoinClause()
    {
        return !empty($this->query->join) && count($this->query->join->getElements()) > 0;
    }

    /**
     * Remove the alias from the where clauses.
     *
     * @return self
     * @since 5.5.1
     */
    protected function removeAliasFromWhereClauses()
    {
        $whereElements = $this->query->where->getElements();
        $whereElements = Arr::make($whereElements);

        $whereElementsWithoutAlias = $whereElements->map(function($element) {
            $dotIndex = strpos($element, '.');

            if ($dotIndex !== false) {
                return substr($element, $dotIndex + 1);
            }

            return $element;
        });

        $this->query->clear('where');

        foreach ($whereElementsWithoutAlias as $whereClause) {
            $this->query->where($whereClause);
        }

        return $this;
    }

    /**
     * Set the table of the query.
     *
     * @param string $table
     * @since 5.5.0
     */
    protected function buildFrom(string $table)
    {
        $this->query->from(
            $this->db->quoteName($table, $this->model->getName())
        );

        return $this;
    }

    /**
     * Rearrange the select clause.
     * Make sure the '*' is always the first element.
     *
     * @return self
     * @since 5.5.0
     */
    protected function rearrangeSelectClause()
    {
        $select = $this->query->select->getElements();
        $elements = Arr::make($select);

        $starIndex = $elements->findIndex(function ($element) {
            return strpos($element, '*') !== false;
        });

        if ($starIndex > -1) {
            $starElement = $elements[$starIndex];
            unset($elements[$starIndex]);
            $elements->prepend($starElement);
        }

        $this->clearQuery('select');
        $this->query->select($elements->toArray());

        return $this;
    }

    /**
     * Build the select query.
     *
     * @param array $columns
     * @since 5.5.0
     */
    protected function buildSelect($columns)
    {
        if (empty($columns)) {
            $this->query->select($this->model->getName() . '.*');
            return $this->rearrangeSelectClause();
        }

        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $this->query->select($columns);
        return $this->rearrangeSelectClause();
    }

    /**
     * Build the where query using Joomla quoteName and quote methods.
     *
     * @param array $conditions
     * @since 5.5.0
     */
    protected function buildWhere($column, $operator = Operators::EQUAL, $value = null, $boolean = 'AND')
    {
        // Handle array format: ['name' => 'value', 'age' => 20]
        if (is_array($column) && !isset($column[0])) {
            $conditions = [];

            foreach ($column as $key => $item) {
                $conditions[] = $this->quoteNameWithPrefix($key) . ' ' . Operators::EQUAL . ' ' . $this->serialize($item);
            }

            return implode(" $boolean ", $conditions);
        }

        // Handle operators that do not require a value, e.g. IS NULL, IS NOT NULL
        if (in_array(strtoupper($operator), static::NO_VALUE_OPERATORS, true)) {
            return $this->quoteNameWithPrefix($column) . ' ' . $operator;
        }

        // Handle format: where('name', 'value')
        if (is_null($value)) {
            $value = $operator;
            $operator = Operators::EQUAL;
        }

        // Handle IN or NOT IN conditions
        if (in_array(strtoupper($operator), static::IN_OPERATORS, true)) {
            if (!is_array($value) || empty($value)) {
                throw new RuntimeException(sprintf('The %s operator requires a non-empty array.', strtoupper($operator)));
            }

            $quotedValues = array_map([$this, 'serialize'], $value);
            $value = '(' . implode(',', $quotedValues) . ')';
            return $this->quoteNameWithPrefix($column) . " {$operator} " . $value;
        }

        if (strtoupper($operator) === Operators::BETWEEN) {
            if (!is_array($value) || count($value) !== 2) {
                throw new RuntimeException('The BETWEEN operator requires an array with two values.');
            }

            $operator = Operators::BETWEEN;
            return $this->quoteNameWithPrefix($column) . " {$operator} " . $this->serialize($value[0]) . ' AND ' . $this->serialize($value[1]);
        }

        // Handle where('name', 'like', '%value%')
        return $this->quoteNameWithPrefix($column) . ' ' . $operator . ' ' . $this->serialize($value);
    }

    /**
     * Prepare the query for getting data from database.
     *
     * @return self
     * @since 5.5.0
     */
    protected function prepareQuery()
    {
        $this->buildSelect($this->getColumns());
        $this->buildFrom($this->model->getTable());

        $this->query->setLimit($this->limit, $this->offset);
        $this->db->setQuery($this->query);
        return $this;
    }

    /**
     * Get the record columns.
     *
     * @param array $data The data
     *
     * @return array
     * @since 5.5.0
     */
    protected function getRecordColumns(array $data)
    {
        return !Arr::isAssociative($data) && !empty($data) ? array_keys($data[0]) : array_keys($data);
    }

    /**
     * Serialize the value.
     *
     * @param mixed $value The value
     *
     * @return mixed
     * @since 5.5.0
     */
    protected function serialize($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_integer($value) || is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            $isFloat = strpos(strval($value), '.') !== false;
            return $isFloat ? floatval($value) : intval($value);
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        return $this->db->quote($value);
    }

    /**
     * Serialize the values.
     *
     * @param array $data The data
     *
     * @return Arr
     * @since 5.5.0
     */
    protected function serializeValues(array $data)
    {
        return Arr::make($data)->map(function($item) {
            return $this->serialize($item);
        });
    }

    /**
     * Prepare the insertion value.
     *
     * @param array $data The data
     *
     * @return string
     * @since 5.5.0
     */
    protected function prepareInsertionValue(array $data)
    {
        return $this->serializeValues($data)->join(',');
    }

    /**
     * Prepare the update values.
     *
     * @param array $data The data
     *
     * @return array
     * @since 5.5.0
     */
    protected function prepareUpdateValues(array $data)
    {
        $data = Arr::make($data);
        return $data->map(function($item, $key) {
            $value = $item instanceof Expression ? (string) $item : $this->serialize($item);
            return $this->quoteNameWithPrefix($key) . ' = ' . $value;
        })->toArray();
    }

    /**
     * Get the latest ordering value.
     *
     * @return int
     * @since 5.5.0
     */
    protected function getLatestOrderingValue()
    {
        $instance = $this->model->newInstance();
        $orderingKey = $this->model->getOrderingKey();

        return $instance->orderBy($orderingKey, 'DESC')->first([$orderingKey])->$orderingKey ?? 0;
    }

    /**
     * Create a new instance of the class.
     *
     * @return static
     * @since 5.5.0
     */
    protected function newInstance()
    {
        return new static($this->model);
    }

    /**
     * Clone the query without the specified clauses.
     *
     * @param array $without
     * @return DatabaseQuery
     * @since 5.5.0
     */
    protected function cloneWithout(array $without)
    {
        $query = clone $this->getQuery();

        foreach ($without as $clause) {
            $query->clear($clause);
        }

        return $query;
    }

    /**
     * Convert the query to a string.
     *
     * @return string
     * 
     * @return mixed
     * @since 5.5.0
     */
    public function __toString()
    {
        return $this->query->dump();
    }

    /**
     * Get the query string.
     *
     * @return string
     * @since 5.5.0
     */
    public function queryString()
    {
        return (string) $this->query;
    }
}