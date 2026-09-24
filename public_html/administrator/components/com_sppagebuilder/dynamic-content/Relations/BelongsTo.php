<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Relations;

use JoomShaper\SPPageBuilder\DynamicContent\Model;

defined('_JEXEC') or die;

class BelongsTo extends Relations
{
    /** @inheritDoc */
    public function __construct(Model $parent, Model $related, string $foreignKey, string $ownerKey)
    {
        parent::__construct($parent, $related, $foreignKey, $ownerKey);
    }

    /** @inheritDoc */
    public function getResults()
    {
        $ownerKey = $this->getOwnerKey();
        $foreignKey = $this->getForeignKey();

        return $this->getQuery()->where($ownerKey, $this->parent->{$foreignKey})->first();
    }
}
