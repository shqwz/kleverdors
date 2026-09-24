<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Models;

use JoomShaper\SPPageBuilder\DynamicContent\Concerns\HasAssets;
use JoomShaper\SPPageBuilder\DynamicContent\Model;

class CollectionItem extends Model
{
    use HasAssets;

    /**
     * The context for the collection item.
     * This is required for creating the asset_id.
     *
     * @var string
     * @since 5.5.0
     */
    protected $context = 'com_sppagebuilder.collection_item';

    /**
     * The table name associated with the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $table = '#__sppagebuilder_collection_items';

    /**
     * The primary key for the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $primaryKey = 'id';

    /**
     * The name of the model. This name is important and used for renaming the table in the sql queries.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $name = 'collection_item';

    /**
     * The key used for ordering the collection items.
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
        'id'            => 'integer',
        'collection_id' => 'integer', 
        'modified'      => 'datetime:M d, Y',
    ];

    /**
     * Get the values associated with the collection item.
     * 
     * @return HasMany
     * @since 5.5.0
     */
    public function values()
    {
        return $this->hasMany(CollectionItemValue::class, 'item_id', 'id');
    }

    /**
     * Get the field associated with the collection item.
     * 
     * @return BelongsTo
     * @since 5.5.0
     */
    public function field()
    {
        return $this->belongsTo(CollectionField::class, 'field_id', 'id');  
    }

    /**
     * Get the collection associated with the collection item.
     * 
     * @return BelongsTo
     * @since 5.5.0
     */
    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id', 'id');
    }
}