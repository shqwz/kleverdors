<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent;

defined('_JEXEC') or die;

use ArrayAccess;
use ArrayIterator;
use DateTime;
use Exception;
use IteratorAggregate;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\String\StringHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Contracts\ModelContract;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Str;
use Traversable;

class Model implements ArrayAccess, IteratorAggregate, ModelContract
{
    use Concerns\HasRelationships;
    use Concerns\HasAttributes;

    /**
     * The context for the model.
     * This is required for creating the asset_id.
     *
     * @var string
     * @since 5.5.0
     */
    protected $context = null;

    /**
     * The table name associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key of the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The context of the model.
     * Or this is a name for the model.
     * This name will be used for renaming the table name.
     *
     * @var string
     * 
     */
    protected $name = null;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     * @since 5.5.0
     */
    protected $casts = [];

    /**
     * The item of the model.
     *
     * @var object
     * @since 5.5.0
     */
    protected $item;

    /**
     * The ordering column of the model.
     *
     * @var string
     * @since 5.5.0
     */
    protected $orderingKey = null;

    /**
     * The query builder instance.
     *
     * @var QueryBuilder
     * @since 5.5.0
     */
    protected $queryBuilder;

    /**
     * The constructor.
     */
    public function __construct($item = null)
    {
        $this->item = $item;
        $this->name = $this->name ?: strtolower(basename(str_replace('\\', '/', get_class($this))));
    }

    /**
     * Get the model item
     * 
     * @return object
     * @since 5.5.0
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Get the model name
     * 
     * @return string
     * @since 5.5.0
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the primary key
     * 
     * @return string
     * @since 5.5.0
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get the foreign key. The foreign key is constructed by the model name and the primary key.
     * 
     * @return string
     * @since 5.5.0
     */
    public function getForeignKey(): string
    {
        return $this->name . '_' . $this->getPrimaryKey();
    }

    /**
     * Get the ordering column name of the model.
     * 
     * @return string
     * @since 5.5.0
     */
    public function getOrderingKey()
    {
        return $this->orderingKey;
    }

    /**
     * Create a new instance of the model
     * 
     * @param object $item The item
     * 
     * @return self
     * @since 5.5.0
     */
    public function newInstance($item = null): self
    {
        return new static($this->formatData($item));
    }

    /**
     * Set the item values
     * 
     * @param object $item The item
     * 
     * @return self
     * @since 5.5.0
     */
    public function setItem($item): self
    {
        $this->item = $this->formatData($item);
        return $this;
    }

    /**
     * Get the table name associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        if (empty($this->table)) {
            throw new Exception('Table name is not set for the model.');
        }

        return $this->table;
    }

    /**
     * Get the casts of the model.
     * 
     * @return array
     * @since 5.5.0
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * Create a unique slug
     * 
     * @param string $title The title
     * @param string $alias The alias
     * 
     * @return string
     * @since 5.5.0
     */
    public static function createUniqueSlug(string $title, ?string $alias = null)
    {
        if (empty($title)) {
            throw new Exception('Title is required to create a unique alias.');
        }

        if (!empty($alias)) {
            return ApplicationHelper::stringURLSafe($alias);
        }

        $query = new QueryBuilder(new static);
        $alias = ApplicationHelper::stringURLSafe($title);

        while ($query->where('alias', $alias)->exists()) {
            $alias = StringHelper::increment($alias, 'dash');
            $query->clearQuery();
        }

        return $alias;
    }

    /**
     * Convert the model to an array
     * 
     * @return array
     * @since 5.5.0
     */
    public function toArray(): array
    {
        $array = (array) $this->item;

        foreach ($array as $key => $value) {
            if ($value instanceof Model) {
                $array[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $array[$key] = array_map(function ($item) {
                    return $item instanceof Model ? $item->toArray() : $item;
                }, $value);
            }
        }

        return $array;
    }

    /**
     * Convert the model to a JSON string
     * 
     * @return string
     * @since 5.5.0
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Get all the records from the table
     * 
     * @param array $columns The columns
     * 
     * @return array
     * @since 5.5.0
     */
    public static function all(array $columns = [])
    {
        return (new QueryBuilder(new static))->get($columns);
    }

    /**
     * Call the query builder methods
     * 
     * @param string $name The method name
     * @param array $arguments The arguments
     * 
     * @return QueryBuilder
     * @since 5.5.0
     */
    public function __call($name, $arguments)
    {
        return (new QueryBuilder(new static))->$name(...$arguments);
    }

    /**
     * Call the query builder static methods
     * 
     * @param string $name The method name
     * @param array $arguments The arguments
     * 
     * @return QueryBuilder
     * @since 5.5.0
     */
    public static function __callStatic($name, $arguments)
    {
        return (new static)->newQuery()->$name(...$arguments);
    }

    /**
     * Create a new query builder instance
     * 
     * @return QueryBuilder
     * @since 5.5.0
     */
    public function newQuery()
    {
        $this->queryBuilder = new QueryBuilder(new static);
        return $this->queryBuilder;
    }

    public function hasAsset()
    {
        return !is_null($this->context);
    }

    /**
     * Get the query builder instance
     * 
     * @return QueryBuilder
     * @since 5.5.0
     */
    public function getQuery()
    {
        return $this->queryBuilder;
    }

    /**
     * Pluck the key from the item
     * 
     * @param string $key The key
     * 
     * @return array
     * @since 5.5.0
     */
    public function pluck($key)
    {
        if (empty((array) $this->item)) {
            return [];
        }

        return Arr::make((array) $this->item)->pluck($key)->toArray();
    }

    /**
     * Check if the model is empty or not.
     * 
     * @return bool
     * @since 5.5.0
     */
    public function isEmpty(): bool
    {
        return empty($this->item) || empty((array) $this->item);
    }

    /**
     * Format the data to the model types.
     *
     * @param object $data
     * @return object
     * @since 5.4.0
     */
    protected function formatData($data)
    {
        if (empty($data)) {
            return $data;
        }

        $dataCopy = clone $data;
        $casts = $this->getCasts();

        foreach ($casts as $key => $type) {
            if (!isset($dataCopy->$key)) {
                continue;
            }

            if (stripos($type, ':')) {
                [$type, $format] = explode(':', $type, 2);
            }

            if ($type === 'array') {
                $dataCopy->$key = Str::toArray($dataCopy->$key);
            }

            if ($type === 'datetime') {
                $dataCopy->$key = (new DateTime($dataCopy->$key))->format($format);
            } else {
                settype($dataCopy->$key, $type);
            }
        }

        return $dataCopy;
    }

    /**
     * Check if the offset exists
     * 
     * @param mixed $offset The offset
     * 
     * @return bool
     * @since 5.5.0
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->item->$offset);
    }

    /**
     * Get the offset value
     * 
     * @param mixed $offset The offset
     * 
     * @return mixed
     * @since 5.5.0
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->item->$offset;
    }

    /**
     * Set the offset value
     * 
     * @param mixed $offset The offset
     * @param mixed $value The value
     * 
     * @return void
     * @since 5.5.0
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->item->$offset = $value;
    }

    /**
     * Unset the offset value
     * 
     * @param mixed $offset The offset
     * 
     * @return void
     * @since 5.5.0
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->item->$offset);
    }

    /**
     * Get the iterator
     * 
     * @return Traversable
     * @since 5.5.0
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->item);
    }

    /**
     * Get the property value
     * 
     * @param mixed $key The key
     * 
     * @return mixed
     * @since 5.5.0
     */
    public function __get(mixed $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Set the property value
     * 
     * @param mixed $key The key
     * @param mixed $value The value
     * 
     * @return void
     * @since 5.5.0
     */
    public function __set(mixed $key, mixed $value): void
    {
        $this->item->$key = $value;
    }

    /**
     * Check if the property is set
     * 
     * @param mixed $key The key
     * 
     * @return bool
     * @since 5.5.0
     */
    public function __isset(mixed $key): bool
    {
        return isset($this->item->$key);
    }

    /**
     * Serialize the model
     * 
     * @return array
     * @since 5.5.0
     */
    public function __serialize(): array
    {
        return (array) $this->item;
    }

    /**
     * Unserialize the model
     * 
     * @param array $data The data
     * 
     * @return void
     * @since 5.5.0
     */
    public function __unserialize(array $data): void
    {
        $this->item = (object) $data;
    }
}
