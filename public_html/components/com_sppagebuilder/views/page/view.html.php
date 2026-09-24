<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Site\PageSeoSettings;

class SppagebuilderViewPage extends HtmlView
{

	protected $item;
	protected $canEdit;
	protected $additionalAttributes = [];

	function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();
		$this->item = $this->get('Item');

		// If a page is unpublished/trashed and a user tries to preview it. 
		if (is_string($this->item))
		{
			throw new Exception($this->item, 404);
		}

		$this->item = ApplicationHelper::preparePageData($this->item);

		$this->canEdit = $user->authorise('core.edit', 'com_sppagebuilder') ||
			$user->authorise('core.edit', 'com_sppagebuilder.page.' . $this->item->id) ||
			($user->authorise('core.edit.own', 'com_sppagebuilder.page.' . $this->item->id) && $this->item->created_by == $user->id);
		$this->checked_out = ($this->item->checked_out == 0 || $this->item->checked_out == $user->id);

		if (count($errors = (array) $this->get('Errors')))
		{
			Log::add(implode('<br />', $errors), Log::WARNING, 'jerror');
			return false;
		}

		// Temporary disabled
		if ($this->item->access_view == false)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}

		$this->_prepareDocument($this->item->title);

		// EasyStore Single Page View
		if (ComponentHelper::isEnabled('com_easystore') && file_exists(JPATH_ROOT . '/components/com_easystore/src/Helper/EasyStoreHelper.php'))
		{
			$extension = $this->item->extension ?? 'com_sppagebuilder';
			$extension_view = $this->item->extension_view ?? 'page';

			if ($extension == 'com_easystore')
			{
				$this->additionalAttributes = JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper::initEasyStore($extension_view);
			}
		}

		parent::display($tpl);
	}

	protected function _prepareDocument($title = '')
	{
		PageSeoSettings::make($this->item)->run();
	}
}
