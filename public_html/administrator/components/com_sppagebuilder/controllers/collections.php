<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\MVC\Controller\AdminController;
use JoomShaper\SPPageBuilder\DynamicContent\DynamicContent;

//no direct access
defined('_JEXEC') or die('Restricted access');


class SppagebuilderControllerCollections extends AdminController
{
    private $dynamicContent = null;

    /**
     * The constructor method
     * 
     * @since 5.5.0
     */
    public function __construct($config = [])
    {
        if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
            require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
        }

        if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
            require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
        }

        parent::__construct($config);
        $this->dispatchToDynamicContent();
    }

    /**
     * Create dynamic content instance for executing the tasks.
     * 
     * @since 5.5.0
     */
    public function dispatchToDynamicContent()
    {
        $this->dynamicContent = DynamicContent::create($this);
    }

    /**
     * Override the execute method to dispatch the task to the dynamic content.
     * 
     * @since 5.5.0
     */
    public function execute($task)
    {
        return call_user_func_array([$this->dynamicContent, 'dispatch'], [$task, $this->input]);
    }
}
