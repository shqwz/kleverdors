<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;

class SppagebuilderViewForm extends HtmlView
{
	protected $form;
	protected $item;
	protected $additionalAttributes = [];

	function display($tpl = null)
	{
		/** @var CMSApplication */
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		if (!$user->id)
		{
			$uri = Uri::getInstance();
			$pageURL = $uri->toString();
			$return_url = base64_encode($pageURL);
			$joomlaLoginUrl = 'index.php?option=com_users&view=login&return=' . $return_url;
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect(Uri::base() . $joomlaLoginUrl, 403);
		}

		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

		$this->item = ApplicationHelper::preparePageData($this->item);

		$input = $app->input;
		$pageid = $input->get('id', '', 'INT');
		$item_info  = SppagebuilderModelPage::getPageInfoById($pageid);
		$authorised = $user->authorise('core.edit', 'com_sppagebuilder.page.' . $pageid) || ($user->authorise('core.edit.own',   'com_sppagebuilder.page.' . $pageid) && $item_info->created_by == $user->id);

		// checkout
		if (!($this->item->checked_out == 0 || $this->item->checked_out == $user->id))
		{
			$app->enqueueMessage(Text::_('COM_SPPAGEBUILDER_ERROR_CHECKED_IN'), 'warning');
			$app->redirect($this->item->link, 403);
			return false;
		}

		if ($authorised !== true)
		{
			$app->enqueueMessage(Text::_('COM_SPPAGEBUILDER_ERROR_EDIT_PERMISSION'), 'warning');
			$app->redirect($this->item->link, 403);
			return false;
		}

		// Check for errors.
		if (count($errors = (array) $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// EasyStore Single Page View
		if (ComponentHelper::isEnabled('com_easystore') && file_exists(JPATH_ROOT . '/components/com_easystore/src/Helper/EasyStoreHelper.php'))
		{
			$extension = $this->item->extension ?? 'com_sppagebuilder';
			$extension_view = $this->item->extension_view ?? 'page';

			if ($extension === 'com_easystore')
			{
				$this->additionalAttributes = JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper::initEasyStore($extension_view);
				JoomShaper\Component\EasyStore\Site\Helper\EasyStoreHelper::attachRequiredAssets();
			}
		}

		/** Add some mock data for easystore pagination status addon. */
		if (ComponentHelper::isEnabled('com_easystore'))
		{
			$pagination = (object) [
				'total'       => 48,
				'page'        => 1,
				'total_pages' => 4,
				'limit'       => 12,
				'start'       => 1,
				'range_start' => 1,
				'range_end'   => 12,
				'range'       => '1-12',
				'loaded'      => true,
			];

			$app->getDocument()->addScriptOptions('easystore.pagination', $pagination);
		}

		$this->_prepareDocument($this->item->title);
		SppagebuilderHelperSite::loadLanguage();
		parent::display($tpl);
	}

	/**
	 * Prepare Page Title and Site Name
	 *
	 * @param string $title
	 * @return void
	 */
	protected function _prepareDocument($title = '')
	{
		/** @var CMSApplication */
		$app 		= Factory::getApplication();
		$config 	= Factory::getConfig();
		$doc 		= Factory::getDocument();
		$menus   	= $app->getMenu();
		$menu 		= $menus->getActive();

		if (isset($menu))
		{
			if ($menu->getParams()->get('page_title', ''))
			{
				$title = $menu->getParams()->get('page_title');
			}
			else
			{
				$title = $menu->title;
			}
		}

		//Include Site title
		$sitetitle = $title;
		if ($config->get('sitename_pagetitles') == 2)
		{
			$sitetitle = Text::sprintf('JPAGETITLE', $sitetitle, $app->get('sitename'));
		}
		elseif ($config->get('sitename_pagetitles') == 1)
		{
			$sitetitle = Text::sprintf('JPAGETITLE', $app->get('sitename'), $sitetitle);
		}

		$doc->setTitle($sitetitle);
	}
}
