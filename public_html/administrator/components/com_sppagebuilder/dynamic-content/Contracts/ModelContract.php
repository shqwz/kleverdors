<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Contracts;

defined('_JEXEC') or die;

interface ModelContract
{
    /**
     * Convert the model to an array
     * 
     * @return array
     * @since 5.5.0
     */
    public function toArray(): array;

    /**
     * Convert the model to a JSON string
     * 
     * @return string
     * @since 5.5.0
     */
    public function toJson(): string;

    /**
     * Get the casts
     * 
     * @return array
     * @since 5.5.0
     */
    public function getCasts(): array;

    /**
     * Get the table name
     * 
     * @return string
     * @since 5.5.0
     */
    public function getTable(): string;

    /**
     * Create a new instance of the model
     * 
     * @param mixed $item The item
     * 
     * @return self
     * @since 5.5.0
     */
    public function newInstance($item = null): self;

    /**
     * Set the item
     * 
     * @param mixed $item The item
     * 
     * @return self
     * @since 5.5.0
     */
    public function setItem($item): self;

    /**
     * Get the item
     * 
     * @return mixed
     * @since 5.5.0
     */
    public function getItem();

    /**
     * Get the name
     * 
     * @return string
     * @since 5.5.0
     */
    public function getName(): string;

    /**
     * Get the primary key
     * 
     * @return string
     * @since 5.5.0
     */
    public function getPrimaryKey(): string;
}
