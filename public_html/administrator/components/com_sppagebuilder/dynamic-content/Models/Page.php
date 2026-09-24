<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Models;

defined('_JEXEC') or die;

use JoomShaper\SPPageBuilder\DynamicContent\Model;

class Page extends Model
{
    /**
     * The regular page type.
     * @var string
     * @since 5.5.0
     */
    public const PAGE_TYPE_REGULAR = 'page';

    /**
     * The dynamic content index page type.
     * @var string
     * @since 5.5.0
     */
    public const PAGE_TYPE_DYNAMIC_CONTENT_INDEX = 'dynamic_content:index';

    /**
     * The dynamic content detail page type.
     * @var string
     * @since 5.5.0
     */
    public const PAGE_TYPE_DYNAMIC_CONTENT_DETAIL = 'dynamic_content:detail';

    /**
     * The popup page type.
     * @var string
     * @since 5.5.0
     */
    public const PAGE_TYPE_POPUP = 'popup';

    /**
     * The module page type.
     * @var string
     * @since 5.5.0
     */ 
    public const PAGE_TYPE_MODULE = 'module';

    /**
     * The article page type.
     * @var string
     * @since 5.5.0
     */
    public const PAGE_TYPE_ARTICLE = 'article';

    /**
     * The easy store storefront page type.
     * @var string
     * @since 5.5.0
     */
    public const PAGE_TYPE_EASYSTORE_STOREFRONT = 'storefront';

    /**
     * The easy store single page type.
     * @var string
     * @since 5.5.0
     */
    public const PAGE_TYPE_EASYSTORE_SINGLE = 'single';

    /**
     * The table name associated with the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $table = '#__sppagebuilder';

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
    protected $name = 'page';
}