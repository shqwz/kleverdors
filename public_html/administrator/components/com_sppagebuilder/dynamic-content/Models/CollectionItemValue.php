<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Models;

use JoomShaper\SPPageBuilder\DynamicContent\Model;

class CollectionItemValue extends Model
{
    /**
     * The table name associated with the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $table = '#__sppagebuilder_collection_item_values';

    /**
     * The name of the model. This name is important and used for renaming the table in the sql queries.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $name = 'collection_item_value';

    /**
     * The casts for the model properties.
     * 
     * @var array
     * @since 5.5.0
     */
    protected $casts = [
        'item_id'    => 'integer', 
        'field_id'   => 'integer',
        'value'      => 'string',
        'created'    => 'datetime:M d, Y',
        'modified'   => 'datetime:M d, Y',
    ];

    /**
     * Get the field associated with the collection item value.
     * 
     * @return BelongsTo
     * @since 5.5.0
     */
    public function field()
    {
        return $this->belongsTo(CollectionField::class, 'field_id', 'id');
    }

    /**
     * Get the reference item associated with the collection item value.
     * 
     * @return BelongsTo
     * @since 5.5.0
     */
    public function references()
    {
        return $this->belongsTo(CollectionItem::class, 'reference_item_id', 'id');
    }
}
