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

class Language extends Model
{
    /**
     * The table name associated with the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $table = '#__languages';

    /**
     * The primary key for the model.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $primaryKey = 'lang_code';

    /**
     * The name of the model. This name is important and used for renaming the table in the sql queries.
     * 
     * @var string
     * @since 5.5.0
     */
    protected $name = 'language';
}