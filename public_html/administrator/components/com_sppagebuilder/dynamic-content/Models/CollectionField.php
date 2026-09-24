<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Models;

use JoomShaper\SPPageBuilder\DynamicContent\Model;

class CollectionField extends Model
{
    /**
     * The table name associated with the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $table = '#__sppagebuilder_collection_fields';

    /**
     * The name of the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $name = 'collection_field';

    /**
     * The primary key for the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $primaryKey = 'id';

    /**
     * The key used for ordering the collection.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $orderingKey = 'ordering';

    /**
     * The casts for the model properties.
     * 
     * @var array
     * @since 5.5.0
     */
    protected $casts = [
        'id'              => 'integer',
        'name'            => 'string',
        'type'            => 'string',
        'file_extensions' => 'array',
        'collection_id'   => 'integer',
        'created'         => 'datetime:M d, Y',
        'modified'        => 'datetime:M d, Y',
    ];

    /**
     * The common columns for the model.
     * 
     * @var array
     * @since 5.5.0
     */
    public const COMMON_COLUMNS = [
        'id',
        'collection_id',
        'name',
        'type',
        'description',
        'options',
        'max_length',
        'min_length',
        'default_value',
        'placeholder',
        'required',
        'reference_collection_id',
        'is_textarea',
        'show_time',
        'file_extensions',
        'number_format',
        'allow_negative',
        'number_unit',
        'number_step',
    ];

    /**
     * Get the value of the field.
     * 
     * @return HasOne
     * @since 5.5.0
     */
    public function value()
    {
        return $this->hasOne(CollectionItemValue::class, 'field_id', 'id');
    }
}
