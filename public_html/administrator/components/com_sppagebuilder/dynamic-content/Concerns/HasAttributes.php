<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Concerns;

trait HasAttributes
{
    /**
     * Check if the attribute exists.
     * 
     * @param mixed $key The key
     * 
     * @return bool
     * @since 5.5.0
     */
    public function hasAttribute($key)
    {
        if (empty($this->item)) {
            return false;
        }

        return property_exists($this->item, $key);
    }

    /**
     * Set the attribute value
     * 
     * @param mixed $key The key
     * @param mixed $value The value
     * 
     * @return self
     * @since 5.5.0
     */
    public function setAttribute($key, $value): self
    {
        $this->item->$key = $value;

        return $this;
    }

    /**
     * Get the attribute value
     * 
     * @param mixed $key The key
     * 
     * @return mixed
     * @since 5.5.0
     */
    public function getAttribute(mixed $key): mixed
    {
        if (!$key) {
            return null;
        }

        if ($this->hasAttribute($key)) {
            return $this->item->$key;
        }

        if ($this->isRelation($key)) {
            return $this->getRelationResolver($this, $key)->getResults();
        }

        throw new \Exception(sprintf('Attribute [%s] does not exist on this model: %s', $key, basename(get_class($this))));
    }
}
