<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Models;

defined('_JEXEC') or die;

use JoomShaper\SPPageBuilder\DynamicContent\Concerns\HasAssets;
use JoomShaper\SPPageBuilder\DynamicContent\Model;

class Collection extends Model
{
    use HasAssets;

    /**
     * The context for the collection item.
     * This is required for creating the asset_id.
     *
     * @var string
     * @since 5.5.0
     */
    protected $context = 'com_sppagebuilder.collection';

    /**
     * The table name associated with the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $table = '#__sppagebuilder_collections';

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
    protected $name = 'collection';

    /**
     * The key used for ordering the collection.
     * If the table has ordering column, this property should be set to the column name.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $orderingKey = 'ordering';

    /**
     * The casts for the model properties.
     * The output data will be casted to the types specified in the array.
     * 
     * @var array
     * @since 5.5.0
     */
    protected $casts = [
        'id'       => 'integer',
        'title'    => 'string', 
        'created'  => 'datetime:M d, Y',
        'modified' => 'datetime:M d, Y',
    ];

    /**
     * Get the fields associated with the collection
     * 
     * @return HasMany
     * @since 5.5.0
     */
    public function fields()
    {
        return $this->hasMany(CollectionField::class, 'collection_id', 'id');
    }

    /**
     * Get the items associated with the collection.
     * 
     * @return HasMany
     * @since 5.5.0
     */
    public function items()
    {
        return $this->hasMany(CollectionItem::class, 'collection_id', 'id');
    }
}
