<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Concerns;

use JoomShaper\SPPageBuilder\DynamicContent\Relations\BelongsTo;
use JoomShaper\SPPageBuilder\DynamicContent\Relations\HasMany;
use JoomShaper\SPPageBuilder\DynamicContent\Relations\HasOne;
use JoomShaper\SPPageBuilder\DynamicContent\Relations\Relations;

defined('_JEXEC') or die;

trait HasRelationships
{
    /**
     * The relation resolvers cache.
     *
     * @var array<string, array<string, Relations>>
     * @since 5.5.0
     */
    protected static $relationResolversCache = [];

    /**
     * Undocumented function
     *
     * @param Model $model
     * @param string $key
     * @return Relations|null
     */
    public function getRelationResolver($model, $key)
    {
        $className = get_class($model);

        if ($resolver = static::$relationResolversCache[$className][$key] ?? null) {
            return $resolver;
        }

        $response = method_exists($model, $key) ? $model::$key() : null;

        if (is_null($response)) {
            return null;
        }

        static::$relationResolversCache[$className][$key] = $response;
        return static::$relationResolversCache[$className][$key];
    }

    /**
     * Check if the relation exists.
     * 
     * @param string $key The key
     * 
     * @return bool
     * @since 5.5.0
     */
    public function isRelation($key)
    {
        return method_exists($this, $key)
            && $this->getRelationResolver($this, $key) instanceof Relations;
    }

    /**
     * Has one relationship
     * 
     * @param string $related The related model
     * @param string $foreignKey The foreign key
     * @param string $localKey The local key
     * 
     * @return HasOne
     * @since 5.5.0
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->getPrimaryKey();

        return new HasOne(
            $this,
            $this->newRelatedInstance($related),
            $foreignKey,
            $localKey
        );
    }

    /**
     * Has many relationship
     * 
     * @param string $related The related model
     * @param string $foreignKey The foreign key
     * @param string $localKey The local key
     * 
     * @return HasMany
     * @since 5.5.0
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->getPrimaryKey();

        return new HasMany(
            $this,
            $this->newRelatedInstance($related),
            $foreignKey,
            $localKey
        );
    }

    /**
     * Belongs to relationship
     * 
     * @param string $related The related model
     * @param string $foreignKey The foreign key
     * @param string $localKey The local key
     * 
     * @return BelongsTo
     * @since 5.5.0
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $ownerKey = $ownerKey ?: $this->getPrimaryKey();

        return new BelongsTo(
            $this,
            $this->newRelatedInstance($related),
            $foreignKey,
            $ownerKey
        );
    }

    /**
     * Create a new related instance
     * 
     * @param string $class The class name
     * 
     * @return self
     * @since 5.5.0
     */
    protected function newRelatedInstance($class)
    {
        return new $class();
    }
}
