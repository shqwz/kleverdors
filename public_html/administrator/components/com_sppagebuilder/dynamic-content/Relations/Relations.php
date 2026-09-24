<?php

namespace JoomShaper\SPPageBuilder\DynamicContent\Relations;

use JoomShaper\SPPageBuilder\DynamicContent\Model;

abstract class Relations
{
    /**
     * The parent model.
     *
     * @var Model
     * @since 5.5.0
     */
    protected Model $parent;

    /**
     * The related model.
     *
     * @var Model
     * @since 5.5.0
     */
    protected Model $related;

    /** 
     * The foreign key.
     *
     * @var string
     * @since 5.5.0
     */
    protected string $foreignKey;

    /**
     * The local key.
     *
     * @var string
     * @since 5.5.0
     */
    protected string $ownerKey;

    /**
     * The constructor.
     *
     * @param Model $parent The parent model.
     * @param Model $related The related model.
     * @param string $foreignKey The foreign key.
     * @param string $ownerKey The owner key.
     *
     * @since 5.5.0
     */
    public function __construct(Model $parent, Model $related, string $foreignKey, string $ownerKey)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    /**
     * Get the foreign key.
     *
     * @return string
     * @since 5.5.0
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the local key.
     *
     * @return string
     * @since 5.5.0
     */
    public function getOwnerKey()
    {
        return $this->ownerKey;
    }

    /**
     * Get the value of the parent model by the owner key.
     *
     * @return mixed
     * @since 5.5.0
     */
    public function getOwnerForeignKeyValue()
    {
        return $this->parent->{$this->getOwnerKey()};
    }

    /**
     * Get the parent model.
     *
     * @return Model
     * @since 5.5.0
     */
    public function getParentModel()
    {
        return $this->parent;
    }

    /**
     * Get the related model.
     *
     * @return Model
     * @since 5.5.0
     */
    public function getRelatedModel()
    {
        return $this->related;
    }

    /**
     * Check if the relation is a single relation.
     *
     * @return bool
     * @since 5.5.0
     */
    public function isSingleRelation()
    {
        if ($this instanceof HasOne || $this instanceof BelongsTo) {
            return true;
        }

        return false;
    }

    /**
     * Check if the relation is an inverse relation.
     *
     * @return bool
     * @since 5.5.0
     */
    public function isInverseRelation()
    {
        return $this instanceof BelongsTo;
    }

    /**
     * Get the query builder instance for the related model.
     *
     * @return Model
     * @since 5.5.0
     */
    public function getQuery()
    {
        return $this->related;
    }

    /**
     * Call the query builder methods for the related model.
     *
     * @param string $name The method name.
     * @param array $arguments The arguments.
     *
     * @return mixed
     * @since 5.5.0
     */
    public function __call($method, $arguments)
    {
        return $this->getQuery()->$method(...$arguments);
    }

    /**
     * Get the results from the relation.
     *
     * @return Model
     * @since 5.5.0
     */
    abstract public function getResults();
}
