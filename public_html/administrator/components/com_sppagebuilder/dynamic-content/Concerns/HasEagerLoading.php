<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

namespace JoomShaper\SPPageBuilder\DynamicContent\Concerns;

use Closure;
use JoomShaper\SPPageBuilder\DynamicContent\Model;
use JoomShaper\SPPageBuilder\DynamicContent\QueryBuilder;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;

defined('_JEXEC') or die;

trait HasEagerLoading
{
    /**
     * The eager relations of the query.
     *
     * @var array
     * @since 5.5.0
     */
    protected $eagerRelations;

    /**
     * The cached primary key values.
     * If the eager loading is used multiple times, it will cache the primary key values to avoid multiple queries.
     *
     * @var array
     * @since 5.5.0
     */
    protected $cachedOwnerKeyValues = [];

    /**
     * Check if the query has eager relations.
     *
     * @return bool
     * @since 5.5.0
     */
    protected function hasEagerRelations()
    {
        return !empty($this->eagerRelations);
    }

    /**
     * Get the eager relations of the query.
     *
     * @return array
     * @since 5.5.0
     */
    protected function getEagerRelations()
    {
        return $this->eagerRelations;
    }

    /**
     * Set the eager relations of the query.
     *
     * @param array $relations
     * @since 5.5.0
     */
    protected function setEagerRelations($relations)
    {
        if (!is_array($relations)) {
            $relations = [$relations => null];
        }

        $relations = Arr::make($relations)->map(
            function ($relation, $key) {
                if (is_numeric($key)) {
                    return [$relation => null];
                } else {
                    return [$key => $relation];
                }
            }
        )->reduce(function ($carry, $item) {
            return array_merge($carry, $item);
        }, []);

        foreach ($relations as $name => $callback) {
            $this->eagerRelations[$name] = $callback;
        }

        return $this;
    }

    /**
     * Eager load the relations of the models.
     *
     * @param array<Model> $models
     * @return array<Model>
     * @since 5.5.0
     */
    protected function eagerLoadRelations(array $models)
    {
        $relations = $this->getEagerRelations();

        foreach ($relations as $relation => $callback) {
            $this->eagerLoadRelation($models, $relation, $callback);
        }

        return $models;
    }

    /**
     * Eager load the relation of the models.
     *
     * @param array<Model> $models The parent models to eager load the relation.
     * @param string $relation The eager relation name defined in the model.
     * @param Closure|null $callback The callback function to get the related model.
     *
     * @return void
     * @since 5.5.0
     */
    protected function eagerLoadRelation(array $models, $relation, $callback)
    {
        $relationInstance = $this->model->getRelationResolver($this->model, $relation);
        $foreignKey = $relationInstance->getForeignKey();
        $ownerKey = $relationInstance->getOwnerKey();
        $relatedModel = $relationInstance->getRelatedModel();

        if ($relationInstance->isInverseRelation()) {
            [$ownerKey, $foreignKey] = [$foreignKey, $ownerKey];
        }

        if (empty($models)) {
            return;
        }

        if (empty($this->cachedOwnerKeyValues)) {
            foreach ($models as $model) {
                $this->cachedOwnerKeyValues[] = $model->{$ownerKey};
            }
        }

        if (empty($this->cachedOwnerKeyValues)) {
            return;
        }

        if ($callback instanceof Closure) {
            $relatedModel = $callback($relatedModel);
        }

        if ($relationInstance->isSingleRelation()) {
            $ownerForeignKeyValue = $relationInstance->getOwnerForeignKeyValue();
            $relationData = $relatedModel->whereIn($foreignKey, $ownerForeignKeyValue)->first();
            foreach ($models as $model) {
                $model->setAttribute($relation, $relationData);
            }
        } else {
            $relationData = $relatedModel->whereIn($foreignKey, $this->cachedOwnerKeyValues)->get();
            $groupedData = $this->groupByProperty($relationData, $foreignKey);

            foreach ($models as $model) {
                $ownerKeyValue = $model->{$ownerKey};
                $data = $groupedData[$ownerKeyValue] ?? [];
                $model->setAttribute($relation, $data);
            }
        }
    }

    /**
     * Group the data by the property.
     *
     * @param array<Model> $data The data to group.
     * @param string $property The property to group the data by.
     *
     * @return array<int, Model>
     * @since 5.5.0
     */
    protected function groupByProperty(array $data, string $property)
    {
        $result = [];

        foreach ($data as $item) {
            if (!isset($result[$item->{$property}])) {
                $result[$item->{$property}] = [];
            }

            $result[$item->{$property}][] = $item;
        }

        return $result;
    }
}
