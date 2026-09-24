<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionDataService;
use JoomShaper\SPPageBuilder\DynamicContent\Site\PageSeoSettings;

//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderViewDynamic extends HtmlView
{
    protected $item;
    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->canEdit = false;
        $app = Factory::getApplication();
        $input = $app->input;

		// If a page is unpublished/trashed and a user tries to preview it. 
		if (is_string($this->item))
		{
            $collectionItemId = $input->get('collection_item_id');

            // if the collection_item_id param exists in the url but is empty, display the no data layout
            if (isset($collectionItemId)) {
                if (empty($collectionItemId) || (is_array($collectionItemId) && empty($collectionItemId[0]))) {
                    $this->setLayout('nodata');
                    return parent::display($tpl);
                }
            }

            throw new Exception($this->item, 404);
		}

		$this->item = ApplicationHelper::preparePageData($this->item);

        PageSeoSettings::make($this->item)->run();

        parent::display($tpl);
    }
}
