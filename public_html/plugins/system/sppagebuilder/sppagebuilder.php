<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Table\AfterLoadEvent;
use Joomla\CMS\Event\Table\AfterStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Menu\AdministratorMenuItem;
use Joomla\CMS\Version;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;

JLoader::register('SppagebuilderHelper', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/helpers/sppagebuilder.php');

require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/integration-helper.php';
require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/autoload.php';
require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/route.php';
require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/constants.php';

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
    require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
}

BuilderAutoload::loadClasses();
BuilderAutoload::loadHelperClasses();

class  plgSystemSppagebuilder extends CMSPlugin
{

	protected $autoloadLanguage = true;
	protected $popupContents = [];
	protected $articleDetailsPageContent = '';
	protected $articleIndexPageContent = '';

	private function getPopupsByIds(array $ids)
	{
	    if (empty($ids)) {
			return [];
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('*')
			->from($db->quoteName('#__sppagebuilder'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')')
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('extension_view') . ' = ' . $db->quote('popup'));

		$db->setQuery($query);

		try {
			return $db->loadObjectList();
		} catch (Exception $error) {
			return [];
		}
	}

	protected function getPageContentById($ids)
	{
		$idArray = array_map(
			function ($item) {
				return $item->id;
			},
			$ids ?? []
		);

		if (empty($idArray)) {
			return [];
		}

		$popups = $this->getPopupsByIds($idArray);

		if (empty($popups)) {
			return [];
		}

		return array_map(
			function ($popup) {
				return AddonParser::viewAddons(json_decode($popup->content), 0, 'page-popups', 1, true, [], true);
			},
			$popups
		);
	}

	function getPopupIds()
	{
		$popupIds = [];

		$params = ComponentHelper::getParams('com_sppagebuilder');
		$popupVisibility = $params->get('popup_visibility', []);

		/** @var CMSApplication $app */
		$app = Factory::getApplication();

		$input = $app->input;
		$pageId = $input->get('id', '', 'INT');
		$menus = $app->getMenu();
		$activeMenu = $menus->getActive();
		$activeMenuId = $activeMenu->id ?? null;

		if (!empty($popupVisibility))
		{
			foreach ($popupVisibility as $value) {
				
				if (!empty($value->popup_type) && $value->popup_type === 'specific_pages')
				{
					$selectedPages = !empty($value->selected_pages) ? $value->selected_pages : [];
					$isItemFound = in_array($pageId, $selectedPages);

					if ($isItemFound)
					{
						$popupIds[] = $value;
					}
				}
				elseif (!empty($value->popup_type) && $value->popup_type === 'specific_menus')
				{
					$selectedMenus = !empty($value->selected_menus) ? $value->selected_menus : [];
					$selectedMenuIds = array_map(
						function ($item) {
							return (new Uri($item))->getVar('Itemid');
						},
						$selectedMenus
					);

					$isItemFound = in_array($activeMenuId, $selectedMenuIds);

					if ($isItemFound)
					{
						$popupIds[] = $value;
					}
				}
				elseif (!empty($value->popup_type) && $value->popup_type === 'entire_site')
				{
					$currentValue = $value;
					if (!empty($value->is_excluded_pages))
					{
						$excludedPages = !empty($value->excluded_pages) ? $value->excluded_pages : [];
						if (in_array($pageId, $excludedPages)) {
							$currentValue = null;
						}
					}
					
					if (!empty($value->is_excluded_menus))
					{
						$excludedMenus = !empty($value->excluded_menus) ? $value->excluded_menus : [];
						$menuIds = array_map(
							function ($item) {
								return (new Uri($item))->getVar('Itemid');
							},
							$excludedMenus
						);

						if (in_array($activeMenuId, $menuIds)) {
							$currentValue = null;
						}
					}

					if (!empty($currentValue)) {
						$popupIds[] = $value;
					}
				}
			}
		}
		return $popupIds;
	}

	/**
	 * $moduleData
	 * Holds the page content of pagebuilder after requesting for duplication
	 *
	 * @var stdClass
	 * @since 5.4
	 */
	public static $moduleData = null;

	private function loadPopupContent()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$pageId = $input->get('id', '', 'INT');
		$view = $input->get('view', '', 'STRING');

		$option = $input->get('option', '', 'STRING');

		try {	
			if ($option === 'com_sppagebuilder' && $view === 'form') 
			{
				return;
			}

			$doc = Factory::getDocument();
			$doc->addScriptDeclaration('
				document.addEventListener("DOMContentLoaded", () =>{
					window.htmlAddContent = window?.htmlAddContent || "";
					if (window.htmlAddContent) {
        				document.body.insertAdjacentHTML("beforeend", window.htmlAddContent);
					}
				});
			');

			if ($option === 'com_sppagebuilder' && !empty($pageId)) {

				$db = Factory::getDbo();

				$query = $db->getQuery(true);
	
				$query->select($db->quoteName(array('extension_view')))
					->from($db->quoteName('#__sppagebuilder'))
					->where($db->quoteName('id') . ' = ' . $pageId);
	
				$db->setQuery($query);
				$result = $db->loadObject();
	
				if (!empty($result->extension_view) && $result->extension_view === 'popup') {
					return;
				}
			}

			$popupContent = [];
			$popupIds = $this->getPopupIds();
			$popupContent = $this->getPageContentById($popupIds);
			$hasPopupContent = !empty($popupContent);

			if ($option !== 'com_sppagebuilder' && $view !== 'page' && $hasPopupContent)
			{
				$params = ComponentHelper::getParams('com_sppagebuilder');

				if ($params->get('fontawesome', 1))
				{
					SppagebuilderHelperSite::addStylesheet('font-awesome-6.min.css');
					SppagebuilderHelperSite::addStylesheet('font-awesome-v4-shims.css');
				}

				if (!$params->get('disableanimatecss', 0))
				{
					SppagebuilderHelperSite::addStylesheet('animate.min.css');
				}

				if (!$params->get('disablecss', 0))
				{
					SppagebuilderHelperSite::addStylesheet('sppagebuilder.css');
					if (!$params->get('disableanimatecss', 0))
					{
						SppagebuilderHelperSite::addStylesheet('animate.min.css');
					}
					SppagebuilderHelperSite::addContainerMaxWidth();
				}

				// load font assets form database
				SppagebuilderHelperSite::loadAssets();

				HTMLHelper::_('jquery.framework');
				HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/jquery.parallax.js', ['version' => SppagebuilderHelperSite::getVersion(true)]);

				HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/es5_interaction.js', ['version' => SppagebuilderHelperSite::getVersion(true)], ['defer' => true]);
				HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/sppagebuilder.js', ['version' => SppagebuilderHelperSite::getVersion(true)], ['defer' => true]);
			}
		
			$this->popupContents = $popupContent;
		} catch (RuntimeException $e) {
			$app->enqueueMessage($e->getMessage(), 'error');
		}
	}

	function onBeforeRender()
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();

		if ($app->isClient('administrator'))
		{
			$integration = self::getIntegration();

			if (!$integration)
			{
				return;
			}

			$input = $app->input;
			$option = $input->get('option', '', 'STRING');
			$view = $input->get('view', '', 'STRING');
			$id = $input->get($integration['id_alias'], 0, 'INT');
			$layout = $input->get('layout', '', 'STRING');

			if (!($option == 'com_' . $integration['group'] && $view == $integration['view']))
			{
				return;
			}

			SppagebuilderHelper::loadAssets('css');
			$doc = Factory::getDocument();
			$doc->addScript(Uri::root(true) . '/plugins/system/sppagebuilder/assets/js/init.js?' . SppagebuilderHelper::getVersion(true));

			$pagebuilder_enabled = 0;

			if ($page_content = self::getPageContent($option, $view, $id))
			{
				$page_content = ApplicationHelper::preparePageData($page_content);
				$pagebuilder_enabled = (int) $page_content->active;
			}

			$integration_element = '.adminform';

			if ($option == 'com_content')
			{
				$integration_element = '.adminform';
			}
			else if ($option == 'com_k2')
			{
				$integration_element = '.k2ItemFormEditor';
			}

			$doc->addScriptdeclaration('var spIntergationElement="' . $integration_element . '";');
			$doc->addScriptdeclaration('var spPagebuilderEnabled=' . $pagebuilder_enabled . ';');
		}
		else
		{
			$input  = $app->input;
			$option = $input->get('option', '', 'STRING');
			$view   = $input->get('view', '', 'STRING');
			$task   = $input->get('task', '', 'STRING');
			$id     = $input->get('id', 0, 'INT');
			$pageName = '';

			if ($option == 'com_content' && $view == 'article')
			{
				$pageName = "{$view}-{$id}.css";
			}
			elseif ($option == 'com_j2store' && $view == 'products' && $task == 'view')
			{
				$pageName = "article-{$id}.css";
			}
			elseif ($option == 'com_k2' && $view == 'item')
			{
				$pageName = "item-{$id}.css";
			}
			elseif ($option == 'com_sppagebuilder' && $view == 'page')
			{
				$pageName = "{$view}-{$id}.css";
			}

			$file_path  = JPATH_ROOT . '/media/sppagebuilder/css/' . $pageName;
			$file_url   = Uri::base(true) . '/media/sppagebuilder/css/' . $pageName;

			if (file_exists($file_path))
			{
				$doc = Factory::getDocument();
				$doc->addStyleSheet($file_url);
			}
		}

		if ($app->isClient('site')) {
			$this->loadPopupContent();

			if ($option === 'com_content' && ($view === 'article' || $view === 'category' || $view === 'featured' || $view === 'archive')) {
				self::loadPageBuilderSiteLanguage();
				SppagebuilderHelperSite::addStylesheet('dynamic-content.css');
				SppagebuilderHelperSite::addScript('dynamic-content.js');
			}

			if ($option === 'com_content' && $view === 'article') {
				$this->loadArticleDetailsPage();
			} else if ($option === 'com_content' && ($view === 'category' || $view === 'featured' || $view === 'archive')) {
				$this->loadArticleIndexPage();
			}
		}
	}

	private function adjustedMargin($originalData) {
		$marginElems = explode(' ', $originalData);
		$topBottomMargin = "calc({$marginElems[0]} - {$marginElems[2]})";
		$leftRightMargin = "calc({$marginElems[3]} - {$marginElems[1]})";
	
		return "{$topBottomMargin} {$leftRightMargin}";
	}

	private function getCssOutput($popupAttribs, $popupId)
	{
		/** @var CMSApplication */
		$cssOutput = '';

		if (!empty($popupAttribs['custom_css'])) {
			$cssOutput .= $popupAttribs['custom_css'];
		}

		$cssOutput .= ' ';

		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$width_xl = !empty($popupAttribs['width']['xl']) ? $popupAttribs['width']['xl'] . $popupAttribs['width']['unit'] : '';
		$width_lg = !empty($popupAttribs['width']['lg']) ? $popupAttribs['width']['lg'] . $popupAttribs['width']['unit'] : $width_xl;
		$width_md = !empty($popupAttribs['width']['md']) ? $popupAttribs['width']['md'] . $popupAttribs['width']['unit'] : $width_lg;
		$width_sm = !empty($popupAttribs['width']['sm']) ? $popupAttribs['width']['sm'] . $popupAttribs['width']['unit'] : $width_md;
		$width_xs = !empty($popupAttribs['width']['xs']) ? $popupAttribs['width']['xs'] . $popupAttribs['width']['unit'] : $width_sm;

		$max_width_xl = !empty($popupAttribs['max_width']['xl']) ? $popupAttribs['max_width']['xl'] . $popupAttribs['max_width']['unit'] : '';
		$max_width_lg = !empty($popupAttribs['max_width']['lg']) ? $popupAttribs['max_width']['lg'] . $popupAttribs['max_width']['unit'] : $max_width_xl;
		$max_width_md = !empty($popupAttribs['max_width']['md']) ? $popupAttribs['max_width']['md'] . $popupAttribs['max_width']['unit'] : $max_width_lg;
		$max_width_sm = !empty($popupAttribs['max_width']['sm']) ? $popupAttribs['max_width']['sm'] . $popupAttribs['max_width']['unit'] : $max_width_md;
		$max_width_xs = !empty($popupAttribs['max_width']['xs']) ? $popupAttribs['max_width']['xs'] . $popupAttribs['max_width']['unit'] : $max_width_sm;

		$height_xl = !empty($popupAttribs['height']['xl']) ? $popupAttribs['height']['xl'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : '';
		$height_lg = !empty($popupAttribs['height']['lg']) ? $popupAttribs['height']['lg'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : $height_xl;
		$height_md = !empty($popupAttribs['height']['md']) ? $popupAttribs['height']['md'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : $height_lg;
		$height_sm = !empty($popupAttribs['height']['sm']) ? $popupAttribs['height']['sm'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] :'vh') : $height_md;
		$height_xs = !empty($popupAttribs['height']['xs']) ? $popupAttribs['height']['xs'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : $height_sm;

		$max_height_xl = !empty($popupAttribs['max_height']['xl']) ? $popupAttribs['max_height']['xl'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : '';
		$max_height_lg = !empty($popupAttribs['max_height']['lg']) ? $popupAttribs['max_height']['lg'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : $max_height_xl;
		$max_height_md = !empty($popupAttribs['max_height']['md']) ? $popupAttribs['max_height']['md'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : $max_height_lg;
		$max_height_sm = !empty($popupAttribs['max_height']['sm']) ? $popupAttribs['max_height']['sm'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max-height']['unit'] :'vh') : $max_height_md;
		$max_height_xs = !empty($popupAttribs['max_height']['xs']) ? $popupAttribs['max_height']['xs'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : $max_height_sm;

		$border_radius_xl = !empty($popupAttribs['border_radius']['xl']) ? $popupAttribs['border_radius']['xl'] . $popupAttribs['border_radius']['unit'] : '';
		$border_radius_lg = !empty($popupAttribs['border_radius']['lg']) ? $popupAttribs['border_radius']['lg'] . $popupAttribs['border_radius']['unit'] : $border_radius_xl;
		$border_radius_md = !empty($popupAttribs['border_radius']['md']) ? $popupAttribs['border_radius']['md'] . $popupAttribs['border_radius']['unit'] : $border_radius_lg;
		$border_radius_sm = !empty($popupAttribs['border_radius']['sm']) ? $popupAttribs['border_radius']['sm'] . $popupAttribs['border_radius']['unit'] : $border_radius_md;
		$border_radius_xs = !empty($popupAttribs['border_radius']['xs']) ? $popupAttribs['border_radius']['xs'] . $popupAttribs['border_radius']['unit'] : $border_radius_sm;

	
		$responsiveStr = ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
		' . (!empty($width_xl) ? ('width: ' . $width_xl . ';') : '') . '
		' . (!empty($max_width_xl) ? ('max-width: ' . $max_width_xl . ';') : '') . '
		' . (!empty($height_xl) ? ('height: ' . $height_xl . ';') : '') . '
		' . (!empty($max_height_xl) ? ('max-height: ' . $max_height_xl . ';') : '') . '
		' . (!empty($border_radius_xl) ? ('border-radius: ' . $border_radius_xl . ';') : '') . '
		}
		@media (max-width: 1200px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_lg) ? ('width: ' . $width_lg . ';') : '') . '
				' . (!empty($max_width_lg) ? ('max-width: ' . $max_width_lg . ';') : '') . '
				' . (!empty($height_lg) ? ('height: ' . $height_lg . ';') : '') . '
				' . (!empty($max_height_lg) ? ('max-height: ' . $max_height_lg . ';') : '') . '
				' . (!empty($border_radius_lg) ? ('border-radius: ' . $border_radius_lg . ';') : '') . '
			}
		}
		@media (max-width: 992px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_md) ? ('width: ' . $width_md) . ';' : '') . '
				' . (!empty($max_width_md) ? ('max-width: ' . $max_width_md . ';') : '') . '
				' . (!empty($height_md) ? ('height: ' . $height_md . ';') : '') . '
				' . (!empty($max_height_md) ? ('max-height: ' . $max_height_md . ';') : '') . '
				' . (!empty($border_radius_md) ? ('border-radius: ' . $border_radius_md . ';') : '') . '
			}
		}
		@media (max-width: 768px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_sm) ? ('width: ' . $width_sm . ';') : '') . '
				' . (!empty($max_width_sm) ? ('max-width: ' . $max_width_sm . ';') : '') . '
				' . (!empty($height_sm) ? ('height: ' . $height_sm . ';') : '') . '
				' . (!empty($max_height_sm) ? ('max-height: ' . $max_height_sm . ';') : '') . '
				' . (!empty($border_radius_sm) ? ('border-radius: ' . $border_radius_sm . ';') : '') . '
			}
		}
		@media (max-width: 575px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_xs) ? ('width: ' . $width_xs . ';') : '') . '
				' . (!empty($max_width_xs) ? ('max-width: ' . $max_width_xs . ';') : '') . '
				' . (!empty($height_xs) ? ('height: ' . $height_xs . ';') : '') . '
				' . (!empty($max_height_xs) ? ('max-height: ' . $max_height_xs . ';') : '') . '
				' . (!empty($border_radius_xs) ? ('border-radius: ' . $border_radius_xs . ';') : '') . '
			}
		} ';

		$cssOutput .= $responsiveStr;

		$cssOutput .= '
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				position: absolute;
				animation-duration: ' . (isset($popupAttribs['enter_animation_duration']) ? (($popupAttribs['enter_animation_duration'] / 1000) . 's;') : '2s;') . '
			}
		';

		$cssOutput .= ' 
		.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
			padding: ' . (!empty($popupAttribs['padding']) ? $popupAttribs['padding'] : 'initial') . ';
			margin: ' . (!empty($popupAttribs['margin']) ? $this->adjustedMargin($popupAttribs['margin']) : 'initial') . ';
	
			border-width: ' . (!empty($popupAttribs['border']['border_width']) ? $popupAttribs['border']['border_width'] : 'initial') . ';
			border-style: ' . (!empty($popupAttribs['border']['border_style']) ? $popupAttribs['border']['border_style'] : 'initial') . ';
			border-color: ' . (!empty($popupAttribs['border']['border_color']) ? $popupAttribs['border']['border_color'] : 'initial') . ';
		} 
		';

		$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup {
			display: none;
		}';

		$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
			display: none;
		}';

		if (!empty($popupAttribs['boxshadow']) && $popupAttribs['boxshadow']['enabled'] === true)
		{
			$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				box-shadow: ' . ((bool)($popupAttribs['boxshadow']['ho']) ? $popupAttribs['boxshadow']['ho'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['vo']) ? $popupAttribs['boxshadow']['vo'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['blur']) ? $popupAttribs['boxshadow']['blur'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['spread']) ? $popupAttribs['boxshadow']['spread'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['color']) ? $popupAttribs['boxshadow']['color'] : 'initial') . ';
			} ';
		}
			$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				background-color: ' . (!empty($popupAttribs['bg_color']) ? $popupAttribs['bg_color'] : 'white') . ';
			} ';

		if (!empty($popupAttribs['background_type']) && !empty($popupAttribs['bg_media']) && $popupAttribs['background_type'] === 'image')
		{
			$bgImageSrc = $popupAttribs['bg_media']['src'];
			if (!preg_match('#^(https?://|//)#', $bgImageSrc) && substr($bgImageSrc, 0, 1) !== '/') {
				$bgImageSrc = Uri::root(true) . '/' . ltrim($bgImageSrc, '/');
			}
			$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				background-image: url("' . $bgImageSrc . '");
				background-repeat: ' . (!empty($popupAttribs['bg_media_repeat']) ? $popupAttribs['bg_media_repeat'] : 'no-repeat') . ';
				background-attachment: ' . (!empty($popupAttribs['bg_media_attachment']) ? $popupAttribs['bg_media_attachment'] : 'initial') . ';
				background-position: ' . (!empty($popupAttribs['bg_media_position']) ? $popupAttribs['bg_media_position'] : 'initial') . ';
				background-size: ' . (!empty($popupAttribs['bg_media_size']) ? $popupAttribs['bg_media_size'] : 'cover') . ';' . 
				(!empty($popupAttribs['bg_media_overlay']) && $popupAttribs['bg_media_overlay'] === 1 ? 'background-blend-mode: ' . $popupAttribs['bg_media_overlay_blend_mode'] : 'normal') . ';
			} ';
		}
		else if (!empty($popupAttribs['background_type']) && $popupAttribs['background_type'] === 'gradient')
		{
			$deg = !empty($popupAttribs['bg_gradient']['deg']) ? $popupAttribs['bg_gradient']['deg'] : 45;
			$radialPos = !empty($popupAttribs['bg_gradient']['radialPos']) ? $popupAttribs['bg_gradient']['radialPos'] : 'center center';
			$color = !empty($popupAttribs['bg_gradient']['color']) ? $popupAttribs['bg_gradient']['color'] : '#00C6FB';
			$color2 = !empty($popupAttribs['bg_gradient']['color2']) ? $popupAttribs['bg_gradient']['color2'] : '#005BEA';
			$pos = !empty($popupAttribs['bg_gradient']['pos']) ? $popupAttribs['bg_gradient']['pos'] : 0;
			$pos2 = !empty($popupAttribs['bg_gradient']['pos2']) ? $popupAttribs['bg_gradient']['pos2'] : 100;
			$type = !empty($popupAttribs['bg_gradient']['type']) ? $popupAttribs['bg_gradient']['type'] : 'linear';

			if (!(bool)$deg) {
				$deg = 45;
			}
			if (!(bool)$radialPos) {
				$radialPos = 'center center';
			}
			if (!(bool)$color) {
				$color = '#00C6FB';
			}
			if (!(bool)$color2) {
				$color2 = '#005BEA';
			}
			if (!(bool)$pos) {
				$pos = 0;
			}
			if (!(bool)$pos2) {
				$pos2 = 100;
			}
			if (!(bool)$type) {
				$type = 'linear';
			}
			
			if ($type === 'linear')
			{
				$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
					background-color: unset;
					background-image: linear-gradient(' . $deg . 'deg, ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
				}';
			}
			else if ($type === 'radial')
			{
				$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
					background-color: unset;
					background-image: radial-gradient(' . $radialPos . ', ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
				}';
			}
		}

		if (!isset($popupAttribs['overlay']) || ($popupAttribs['overlay'] === 1))
		{
			$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
					background-color: ' . (!empty($popupAttribs['overlay_bg_color']) && (bool)$popupAttribs['overlay_bg_color'] ? $popupAttribs['overlay_bg_color'] : 'rgba(0, 0, 0, 0.7)') . ';
				} ';

			if (!empty($popupAttribs['overlay']) && !empty($popupAttribs['overlay_bg_media']) && !empty($popupAttribs['overlay_background_type']) && $popupAttribs['overlay_background_type'] === 'image')
			{
				$overlayBgImageSrc = $popupAttribs['overlay_bg_media']['src'];
				if (!preg_match('#^(https?://|//)#', $overlayBgImageSrc) && substr($overlayBgImageSrc, 0, 1) !== '/') {
					$overlayBgImageSrc = Uri::root(true) . '/' . ltrim($overlayBgImageSrc, '/');
				}
				$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
					background-image: url("' . $overlayBgImageSrc . '");
					background-repeat: ' . (!empty($popupAttribs['overlay_bg_media_repeat']) ? $popupAttribs['overlay_bg_media_repeat'] : 'no-repeat') . ';
					background-attachment: ' . (!empty($popupAttribs['overlay_bg_media_attachment']) ? $popupAttribs['overlay_bg_media_attachment'] : 'initial') . ';
					background-position: ' . (!empty($popupAttribs['overlay_bg_media_position']) ? $popupAttribs['overlay_bg_media_position'] : 'initial') . ';
					background-size: ' . (!empty($popupAttribs['overlay_bg_media_size']) ? $popupAttribs['overlay_bg_media_size'] : 'cover') . ';' . 
					(!empty($popupAttribs['overlay_bg_media_overlay']) && $popupAttribs['overlay_bg_media_overlay'] === 1 ? 'background-blend-mode: ' . $popupAttribs['overlay_bg_media_overlay_blend_mode'] : 'normal') . ';
				} ';
			}
			else if (!empty($popupAttribs['overlay']) && !empty($popupAttribs['overlay_background_type']) && $popupAttribs['overlay_background_type'] === 'gradient')
			{
				$deg = !empty($popupAttribs['overlay_bg_gradient']['deg']) ? $popupAttribs['overlay_bg_gradient']['deg'] : 45;
				$radialPos = !empty($popupAttribs['overlay_bg_gradient']['radialPos']) ? $popupAttribs['overlay_bg_gradient']['radialPos'] : 'center center';
				$color = !empty($popupAttribs['overlay_bg_gradient']['color']) ? $popupAttribs['overlay_bg_gradient']['color'] : '#00C6FB';
				$color2 = !empty($popupAttribs['overlay_bg_gradient']['color2']) ? $popupAttribs['overlay_bg_gradient']['color2'] : '#005BEA';
				$pos = !empty($popupAttribs['overlay_bg_gradient']['pos']) ? $popupAttribs['overlay_bg_gradient']['pos'] : 0;
				$pos2 = !empty($popupAttribs['overlay_bg_gradient']['pos2']) ? $popupAttribs['overlay_bg_gradient']['pos2'] : 100;
				$type = !empty($popupAttribs['overlay_bg_gradient']['type']) ? $popupAttribs['overlay_bg_gradient']['type'] : 'linear';

				if (!(bool)$deg) {
					$deg = 45;
				}
				if (!(bool)$radialPos) {
					$radialPos = 'center center';
				}
				if (!(bool)$color) {
					$color = '#00C6FB';
				}
				if (!(bool)$color2) {
					$color2 = '#005BEA';
				}
				if (!(bool)$pos) {
					$pos = 0;
				}
				if (!(bool)$pos2) {
					$pos2 = 100;
				}
				if (!(bool)$type) {
					$type = 'linear';
				}
				
				if ($type === 'linear')
				{
					$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
						background-color: unset;
						background-image: linear-gradient(' . $deg . 'deg, ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
					}';
				}
				else if ($type === 'radial')
				{
					$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
						background-color: unset;
						background-image: radial-gradient(' . $radialPos . ', ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
					}';
				}
			}
		}
		else if (isset($popupAttribs['overlay']) && $popupAttribs['overlay'] === 0)
		{
			$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
				display: none;
			} ';
		}

		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			display: flex;
			justify-content: center;
			align-items: center;
		} ';

		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			color: ' . (!empty($popupAttribs['close_btn_color']) ? $popupAttribs['close_btn_color'] : 'initial') . ';
		} ';

		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ':hover {
				color: ' . (!empty($popupAttribs['close_btn_color_hover']) ? $popupAttribs['close_btn_color_hover'] : 'initial') . ' !important;
				background-color: ' . (!empty($popupAttribs['close_btn_bg_color_hover']) ? $popupAttribs['close_btn_bg_color_hover'] : 'initial') . ' !important;
		} ';


		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			color: ' . (!empty($popupAttribs['close_btn_color']) ? $popupAttribs['close_btn_color'] : 'initial') . ';

			border-width: ' . (!empty($popupAttribs['close_btn_border']['border_width']) ? $popupAttribs['close_btn_border']['border_width'] : 'initial') . ';
			border-style: ' . (!empty($popupAttribs['close_btn_border']['border_style']) ? $popupAttribs['close_btn_border']['border_style'] : 'initial') . ';
			border-color: ' . (!empty($popupAttribs['close_btn_border']['border_color']) ? $popupAttribs['close_btn_border']['border_color'] : 'initial') . ';

			border-radius: ' . (!empty($popupAttribs['close_btn_border_radius']) ? ($popupAttribs['close_btn_border_radius'] . 'px') : '0px') . ';
		}
		#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			background-color: ' . (!empty($popupAttribs['close_btn_bg_color']) ? $popupAttribs['close_btn_bg_color'] : 'initial') . ';
		}
		#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			padding: ' . (!empty($popupAttribs['close_btn_padding']) ? $popupAttribs['close_btn_padding'] : 'initial') . ';
		} ';

		if (empty($popupAttribs['close_btn_position']) || $popupAttribs['close_btn_position'] === 'inside' || $popupAttribs['close_btn_position'] === 0 || $popupAttribs['close_btn_position'] === '' || empty($popupAttribs['close_btn_position'])) 
		{
			$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId .' {
				transform: scale(1.2);
				right: 25px;
				top: 20px;
			}';
		}
		else if ($popupAttribs['close_btn_position'] === 'outside' || $popupAttribs['close_btn_position'] === 1)
		{
			$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId .' {
				transform: scale(1.2);
				right: 5px;
				top: -30px;
			}';
		}
		else if ($popupAttribs['close_btn_position'] === 'outside' || $popupAttribs['close_btn_position'] === 'custom')
		{
			$btn_position_x_xl = !empty($popupAttribs['close_btn_position_x']['xl']) ? $popupAttribs['close_btn_position_x']['xl'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['xl']) && $popupAttribs['close_btn_position_x']['xl'] == '0' ? '0' : '25px');
			$btn_position_x_lg = !empty($popupAttribs['close_btn_position_x']['lg']) ? $popupAttribs['close_btn_position_x']['lg'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['lg']) && $popupAttribs['close_btn_position_x']['lg'] == '0' ? '0' : $btn_position_x_xl);
			$btn_position_x_md = !empty($popupAttribs['close_btn_position_x']['md']) ? $popupAttribs['close_btn_position_x']['md'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['md']) && $popupAttribs['close_btn_position_x']['md'] == '0' ? '0' : $btn_position_x_lg);
			$btn_position_x_sm = !empty($popupAttribs['close_btn_position_x']['sm']) ? $popupAttribs['close_btn_position_x']['sm'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['sm']) && $popupAttribs['close_btn_position_x']['sm'] == '0' ? '0' : $btn_position_x_md);
			$btn_position_x_xs = !empty($popupAttribs['close_btn_position_x']['xs']) ? $popupAttribs['close_btn_position_x']['xs'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['xs']) && $popupAttribs['close_btn_position_x']['xs'] == '0' ? '0' : $btn_position_x_sm);
	
			$btn_position_y_xl = !empty($popupAttribs['close_btn_position_y']['xl']) ? $popupAttribs['close_btn_position_y']['xl'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (isset($popupAttribs['close_btn_position_y']['xl']) && $popupAttribs['close_btn_position_y']['xl'] == '0' ? '0' : '20px');
			$btn_position_y_lg = !empty($popupAttribs['close_btn_position_y']['lg']) ? $popupAttribs['close_btn_position_y']['lg'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (isset($popupAttribs['close_btn_position_y']['lg']) && $popupAttribs['close_btn_position_y']['lg'] == '0' ? '0' : $btn_position_y_xl);
			$btn_position_y_md = !empty($popupAttribs['close_btn_position_y']['md']) ? $popupAttribs['close_btn_position_y']['md'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : ((isset($popupAttribs['close_btn_position_y']['md']) && $popupAttribs['close_btn_position_y']['md'] == '0' ? '0' : $btn_position_y_lg));
			$btn_position_y_sm = !empty($popupAttribs['close_btn_position_y']['sm']) ? $popupAttribs['close_btn_position_y']['sm'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (((isset($popupAttribs['close_btn_position_y']['sm']) && $popupAttribs['close_btn_position_y']['sm'] == '0' ? '0' : $btn_position_y_md)));
			$btn_position_y_xs = !empty($popupAttribs['close_btn_position_y']['xs']) ? $popupAttribs['close_btn_position_y']['xs'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (((isset($popupAttribs['close_btn_position_y']['xs']) && $popupAttribs['close_btn_position_y']['xs'] == '0' ? '0' : $btn_position_y_sm)));
	
			$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
				transform: scale(1.2);
	
				right: ' . $btn_position_x_xl . '; 
				top: ' . $btn_position_y_xl . ';
			}
			@media (max-width: 1200px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_lg . '; 
					top: ' . $btn_position_y_lg . ';
				}
			}
			@media (max-width: 992px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_md . '; 
					top: ' . $btn_position_y_md . ';
				}
			}
			@media (max-width: 768px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_sm . '; 
					top: ' . $btn_position_y_sm . ';
				}
			}
			@media (max-width: 575px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_xs . '; 
					top: ' . $btn_position_y_xs . ';
				}
			} ';	
		}

		return $cssOutput;
	}

	private function getPositionScriptContent($popupId, $formattedPopupAttribs)
	{
		$scriptContent = '

			const data = ' . $formattedPopupAttribs . ';
			function onElementLoaded(element) {
					const container = element;

					const mediaQueryMap = {
						"default": "xl",
						"(max-width: 1200px)": "lg",
						"(max-width: 992px)": "md",
						"(max-width: 768px)": "sm",
						"(max-width: 575px)": "xs"
					};

					const getResponsivePosition = (size = "default") => {
						const activeDevice = mediaQueryMap[size];

						const windowHeight = window?.innerHeight;
						const containerHeight = container?.clientHeight;
						const windowWidth = window?.innerWidth;
						const containerWidth = container?.clientWidth;

						if (!data?.position) {
							data.position = { top: { xl: "", lg: "", md: "", sm: "", unit: "%" }, left: { xl: "", lg: "", md: "", sm: "", unit: "%" } };
						}

						data.position = {
							top: {
								xl: data?.position?.top?.xl,
								lg: data?.position?.top?.lg || data?.position?.top?.xl,
								md: data?.position?.top?.md || data?.position?.top?.lg || data?.position?.top?.xl,
								sm: data?.position?.top?.sm || data?.position?.top?.md || data?.position?.top?.lg || data?.position?.top?.xl,
								xs: data?.position?.top?.xs || data?.position?.top?.sm || data?.position?.top?.md || data?.position?.top?.lg || data?.position?.top?.xl,
								unit: data?.position?.top?.unit,
							},
							left: {
								xl: data?.position?.left?.xl,
								lg: data?.position?.left?.lg || data?.position?.top?.xl,
								md: data?.position?.left?.md || data?.position?.left?.lg || data?.position?.top?.xl,
								sm: data?.position?.left?.sm || data?.position?.left?.md || data?.position?.left?.lg || data?.position?.top?.xl,
								xs: data?.position?.left?.xs || data?.position?.left?.sm || data?.position?.left?.md || data?.position?.left?.lg || data?.position?.top?.xl,
								unit: data?.position?.left?.unit,
							},
						};

						if (data?.position?.top?.unit !== "%") {
							container.style["top"] = data?.position?.top[activeDevice] + data?.position?.top?.unit;
						} else if (data?.position?.top?.unit === "%") {
							if (data?.position?.top[activeDevice] !== "") {
							if (data?.position?.top[activeDevice] != 50) {
								container.style["top"] = `calc(${data?.position?.top[activeDevice]}${data?.position?.top?.unit} - ${
								(data?.position?.top[activeDevice] * containerHeight) / 100
								}px)`;
							}
							}
						}

						if (data?.position?.left?.unit !== "%") {
							container.style["left"] = data?.position?.left[activeDevice] + data?.position?.left?.unit;
						} else if (data?.position?.left?.unit === "%") {
							if (data?.position?.left[activeDevice] !== "") {
							if (data?.position?.left[activeDevice] != 50) {
								container.style["left"] = `calc(${data?.position?.left[activeDevice]}${data?.position?.left?.unit} - ${
								(data?.position?.left[activeDevice] * containerWidth) / 100
								}px)`;
							}
							}
						}

						if (
							(data?.position?.top[activeDevice] === "" || data?.position?.top[activeDevice] == 50) &&
							data?.position?.top?.unit === "%"
						) {
							const isTop = windowHeight - containerHeight <= 0 ? "0" : null;
							container.style["top"] = isTop ? isTop : `calc(50% - ${containerHeight / 2}px)`;
						}
						if (
							(data?.position?.left[activeDevice] === "" || data?.position?.left[activeDevice] == 50) &&
							data?.position?.left?.unit === "%"
						) {
							const isLeft = windowWidth - containerWidth <= 0 ? "0" : null;
							container.style["left"] = isLeft ? isLeft : `calc(50% - ${containerWidth / 2}px)`;
						}
						if (data?.position?.top[activeDevice] == 100 && data?.position?.top?.unit === "%") {
							container.style["top"] = `calc(100% - ${containerHeight}px)`;
						}
						if (data?.position?.left[activeDevice] == 100 && data?.position?.left?.unit === "%") {
							container.style["left"] = `calc(100% - ${containerWidth}px)`;
						}
					}

					const mediaLG = window.matchMedia("(max-width: 1200px)");
					const mediaMD = window.matchMedia("(max-width: 992px)");
					const mediaSM = window.matchMedia("(max-width: 768px)");
					const mediaXS = window.matchMedia("(max-width: 575px)");

					function handleTabletChange() {
						if (mediaXS.matches) {
							getResponsivePosition("(max-width: 575px)");
						} else if (mediaSM.matches) {
							getResponsivePosition("(max-width: 768px)");
						} else if (mediaMD.matches) {
							getResponsivePosition("(max-width: 992px)");
						} else if (mediaLG.matches) {
							getResponsivePosition("(max-width: 1200px)");
						} else {
							getResponsivePosition();
						}
					}
					mediaLG.addListener(handleTabletChange);
					mediaMD.addListener(handleTabletChange);
					mediaSM.addListener(handleTabletChange);
					mediaXS.addListener(handleTabletChange);

					handleTabletChange(mediaLG);
					handleTabletChange(mediaMD);
					handleTabletChange(mediaSM);
					handleTabletChange(mediaXS);
			};
	
			const elementSelector = " .page-' . $popupId . '.sp-pagebuilder-popup .builder-container";

			const observerOptions = {
				childList: true,
				subtree: true
			};
	
			const observerCallback = (mutationsList, observer) => {
			for (let mutation of mutationsList) {
				if (mutation.type === "childList") {
				const element = document.querySelector(elementSelector);
				if (element) {
					onElementLoaded(element);
					window.onresize = () => onElementLoaded(element);
					observer.disconnect();
					break;
				}
				}
			}
			};
	
			const observer = new MutationObserver(observerCallback);

			observer.observe(document.body, observerOptions);

			const element = document.querySelector(elementSelector);
			if (element) {
				onElementLoaded(element);
				window.onresize = () => onElementLoaded(element);
				observer.disconnect();
			}

			window.addEventListener("beforeunload", function() {
				window.onresize = null;
			});
			';
		return $scriptContent;
	}

	private function getVisibilityScriptContent($popupId, $popupAttribs)
	{
		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$dontShowScript = ' 
			function isRestricted(id) {
    			const restrictedIds = JSON.parse(localStorage.getItem("restricted-popup-ids"));

				if (!restrictedIds) return false;

    			return restrictedIds.includes(id);
			}
			function isPermitted(id) {
				const storedTime = localStorage.getItem("reappear-popup-' . $popupId . '");

				if (storedTime) {
					const currentTimestamp = new Date().getTime();

					if (currentTimestamp - storedTime > 0) return true;
					return false;
				}
				return true;
			}
			function isWithinDateRange() {
				const dateRange = ' . (!empty($popupAttribs['date_range']['from']) && !empty($popupAttribs['date_range']['to']) ? json_encode($popupAttribs['date_range']) : 'null') . ';
				if (dateRange === null) {
					return true;
				}

				if (new Date(dateRange?.from) <= new Date(new Date().toISOString().split("T")[0] + "T06:00:00") && new Date(dateRange?.to) >= new Date(new Date().toISOString().split("T")[0] + "T06:00:00")) {
					return true;
				}
				return false;
			}
		';

		$scriptContent = '';
		if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_scroll')
		{
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				let previousScrollPosition = window.scrollY;
	
				window.onscroll = function() {
	
					const scrollPercentage = ' . (!empty($popupAttribs['scroll_percentage']) ? $popupAttribs['scroll_percentage'] : 40) . ';
					const scrollDirection = "' . (!empty($popupAttribs['scroll_direction']) ? $popupAttribs['scroll_direction'] : 'down') . '";
	
					const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
					
					const scrollPosition = (window.scrollY / scrollableHeight) * 100;

					const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
					const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");
	
					if (scrollDirection === "down" && scrollPosition > previousScrollPosition) {
	
						if (scrollPosition > scrollPercentage) {
							setTimeout(() => {
								containerDiv.parentNode.style.display = "block";
								overlayDiv.style.display = "block";
								' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . '); 

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}
						} else if (scrollDirection === "up" && scrollPosition < previousScrollPosition) {
							if (scrollPosition < scrollPercentage) {
							setTimeout(() => {
								containerDiv.parentNode.style.display = "block";
								overlayDiv.style.display = "block"; 
								' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . '); 

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}
					}
	
					previousScrollPosition = scrollPosition;
				  };
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_landing')
		{
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return;
				if (!isPermitted(' . $popupId . ')) return; 
				if (!isWithinDateRange(' . $popupId . ')) return;

				const landingAfter = ' . (!empty($popupAttribs['landing_after']) ? $popupAttribs['landing_after'] : 0) . ';
				const landingShowAfter = ' . (!empty($popupAttribs['landing_show_after']) ? $popupAttribs['landing_show_after'] : "null") . ';

				const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
				const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");

				function getCookie(name) {
					let nameEQ = name + "=";
					let cookies = document.cookie.split(";");
					for (let i = 0; i < cookies.length; i++) {
						let cookie = cookies[i].trim();
						if (cookie.indexOf(nameEQ) === 0) {
							return decodeURIComponent(cookie.substring(nameEQ.length, cookie.length));
						}
					}
					return null;
				}

				function setCookie(name, value, days = null) {
					let expires = "";
					if (days) {
						let date = new Date();
						date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
						expires = "; expires=" + date.toUTCString();
					}

					document.cookie = name + "=" + (encodeURIComponent(value) || "") + expires + "; path=/";
				}

				function deleteCookie(name, path = "/", domain = null) {
					let cookieString = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
					
					if (path) {
						cookieString += "; path=" + path;
					}
					
					if (domain) {
						cookieString += "; domain=" + domain;
					}
					
					document.cookie = cookieString;
				}

				if (landingShowAfter === null) {
					const cookieLanding = getCookie("landingShowAfter-' . $popupId . '");
					if (cookieLanding !== null) {
						deleteCookie("landingShowAfter-' . $popupId . '", "/");
					}

					setTimeout(() => {
						containerDiv.parentNode.style.display = "block";
						overlayDiv.style.display = "block";
						' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

						' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
					}, (landingAfter * 1000) + ' . $popupAttribs['enter_animation_delay'] . ');

				} else {
					let totalHits = 0;
					const cookieLanding = getCookie("landingShowAfter-' . $popupId . '");
					if (cookieLanding === null) {
						totalHits = 1;
						setCookie("landingShowAfter-' . $popupId . '", 1);
					} else {
						totalHits = Number(cookieLanding) + 1;
						setCookie("landingShowAfter-' . $popupId . '", totalHits);
					}

					if (landingShowAfter === totalHits) {
						setTimeout(() => {
							containerDiv.parentNode.style.display = "block";
							overlayDiv.style.display = "block"; 
							' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}, (landingAfter * 1000) + ' . $popupAttribs['enter_animation_delay'] . ');
					}
				}
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_click') {
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				const clickType = "' . (!empty($popupAttribs['click_type']) ? $popupAttribs['click_type'] : 'random') . '";
				const clickCount = ' . (!empty($popupAttribs['click_count']) ? $popupAttribs['click_count'] : 1) . ';
				const clickArea = ' . (!empty($popupAttribs['click_area']) ? '"' . $popupAttribs['click_area'] . '"' : "null") . ';

				let clicked = 0;
				let isShown = false;

				if (clickType === "random") {
					document.addEventListener("click", (event) => {
						if (isRestricted(' . $popupId . ')) return; 
						if (!isPermitted(' . $popupId . ')) return;
						if (!isWithinDateRange(' . $popupId . ')) return;

						let closePopupArea = "#sp-pagebuilder-popup-close-btn-' . $popupId . '";
						let targetNode = event.target;
						if (targetNode.closest(closePopupArea)) {
							return;
						}

						clicked++;
						if (clicked >= clickCount) {
							const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
							const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");

							setTimeout(() => {
							containerDiv.parentNode.style.display = "block";
							overlayDiv.style.display = "block"; 
							' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . ');

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}
					});
				} else if (clickType === "specific") {
					if (clickArea !== null && clickArea !== undefined) {
						const selectedArea = document.querySelectorAll(clickArea);
						if(selectedArea !== null && selectedArea !== undefined) {
							Array.from(selectedArea).forEach(area => {
								area.addEventListener("click", () => {
									if (isRestricted(' . $popupId . ')) return; 
									if (!isPermitted(' . $popupId . ')) return;
									if (!isWithinDateRange(' . $popupId . ')) return;

									clicked++;
									if (clicked >= clickCount) {
										const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
										const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");
			
										setTimeout(() => {
										containerDiv.parentNode.style.display = "block";
										overlayDiv.style.display = "block";
										' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

										}, ' . $popupAttribs['enter_animation_delay'] . '); 

										' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
										isShown = true;
									}
								});
							});
						}
					}
				}
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_hover') {
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				const hoverArea = "' . (!empty($popupAttribs['hover_area']) ? $popupAttribs['hover_area'] . '"' : "null") . ';
				const selectedArea = document.querySelectorAll(hoverArea);
				Array.from(selectedArea).forEach(area => {
					area.addEventListener("mouseover", () => {
							const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
							const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");

							setTimeout(() => {
							containerDiv.parentNode.style.display = "block";
							overlayDiv.style.display = "block";
							' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . ');

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '	
					});
				});
				
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_inactivity') {
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				const inactivityDuration = ' . (!empty($popupAttribs['inactivity_duration']) ? $popupAttribs['inactivity_duration'] : 0) . ';

				let idleTimeCounter = 0;
				let idleInterval = null;

				function resetIdleTimer(idleInterval) {
					if (!idleInterval) {
						idleInterval = setInterval(() => {
							if (document.body.getAttribute("data-stop-timer") == "true") {
								document.removeEventListener("mousemove", stopIdleCounter, false);
								document.removeEventListener("keypress", stopIdleCounter, false);
								document.removeEventListener("scroll", stopIdleCounter, false);
								document.removeEventListener("click", stopIdleCounter, false);
								clearInterval(idleInterval);
							}
							idleTimeCounter++;
							if (idleTimeCounter >= inactivityDuration) {
								const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
								const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");
	
								setTimeout(() => {
								containerDiv.parentNode.style.display = "block";
								overlayDiv.style.display = "block";
								' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

								}, ' . $popupAttribs['enter_animation_delay'] . ');

								' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
							}
						}, 1000);
					}
				}

				function stopIdleCounter() {
					clearInterval(idleInterval);
					idleInterval = null;
					idleTimeCounter = 0;
				}

				window.onload = function() {
					resetIdleTimer(idleInterval);

					document.addEventListener("mousemove", stopIdleCounter, false);
					document.addEventListener("keypress", stopIdleCounter, false);
					document.addEventListener("scroll", stopIdleCounter, false);
					document.addEventListener("click", stopIdleCounter, false);
				};
			});
			';
		}

		$scriptContent .= ' 
		
		window.addEventListener("DOMContentLoaded", (event) => {
			if (isRestricted(' . $popupId . ')) return; 
			if (!isPermitted(' . $popupId . ')) return;
			if (!isWithinDateRange(' . $popupId . ')) return;

			const containerBuilderDiv = document.querySelector(".page-' . $popupId . '.sp-pagebuilder-popup .builder-container");

			const displayValue = window.getComputedStyle(containerBuilderDiv, null).display;

			if (displayValue === "block") {
				' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . ';
			}
		});
		';

		return $dontShowScript . ' ' . $scriptContent;
	}

	private function getAdvancedScriptContent($popupId, $popupAttribs) 
	{
		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => { 
			const builder = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup"); 
			const builderOverlay = document.getElementById("sp-pagebuilder-overlay-' . $popupId . '"); 
			';
		if (!empty($popupAttribs['auto_close']) && $popupAttribs['auto_close'] === 1) {
			if (!empty($popupAttribs['auto_close_after'])) {

				$landingDelay = 0;
				$popupAutoClose = !empty($popupAttribs['auto_close_after']) ? $popupAttribs['auto_close_after'] : 10;

				if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_landing') {
					$landingDelay = !empty($popupAttribs['landing_after']) ? $popupAttribs['landing_after'] : 0;
				}

				$scriptContent .= '
					setTimeout(() => {
						' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ' builder.children[0].style.animationDirection = "reverse"; builder.children[0].style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's"; builder.children[0].style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's"; builder.children[0].setAttribute("class", "page-content builder-container ' . $popupAttribs['exit_animation'] . '");' : "") . '
					}, ' . ((($landingDelay + $popupAutoClose) * 1000)) . ');

				builder.parentNode.style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
				builder.parentNode.style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
				
				setTimeout(() => {
					builder.parentNode.style.display = "none";
					builderOverlay.style.display = "none";
				}, ' . ((($landingDelay + $popupAutoClose) * 1000) + $popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) . '); 
				';
			}
		}

		if (!empty($popupAttribs['close_outside_click']) && $popupAttribs['close_outside_click'] === 1)
		{
			$scriptContent .= '
			window.onclick = function (event) {
				if (!(event.target.getAttribute("class") === "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container" || event.target.querySelector(".builder-container") === null)) {
					builder.parentNode.style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
					builder.parentNode.style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
					setTimeout(() => {
						builder.parentNode.style.display = "none";
						builderOverlay.style.display = "none";
					}, ' . ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) . '); 
				}
			};
				';
		}

		if (!empty($popupAttribs['close_on_esc']) && $popupAttribs['close_on_esc'] === 1)
		{
			$scriptContent .= '
			window.addEventListener("keydown", function(e) {
				if (e.key === "Escape" || e.key === "Esc") {
				builder.parentNode.style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
				builder.parentNode.style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
				setTimeout(() => {
					builder.parentNode.style.display = "none";
					builderOverlay.style.display = "none";
				}, ' . ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) . '); 
				}
			  });
				';
		}

		if (!empty($popupAttribs['disable_page_scrolling']) && $popupAttribs['disable_page_scrolling'] === 1)
		{
			$scriptContent .= '
			document.body.style.overflowY = "hidden";
				';
		}

		$scriptContent .= '
			var timeUnitMap = {
				sec: 1000,
				min: 60000,
				hr: 3600000,
				day: 86400000,
				never: 0,
			};

			var reappearAfter = new Date().getTime() + (' . (isset($popupAttribs['reappear_after']) && !empty($popupAttribs['reappear_after']['value']) ? $popupAttribs['reappear_after']['value'] : 0) . ' * ' . ((isset($popupAttribs['reappear_after'])) && !empty($popupAttribs['reappear_after']['value']) ? 'timeUnitMap["' . $popupAttribs['reappear_after']['unit'] . '"]' : 0) . ');

			if (' . (isset($popupAttribs['reappear_after']) && !empty($popupAttribs['reappear_after']['unit']) ? ('"' . $popupAttribs['reappear_after']['unit'] . '"') : "null") . ' == "never") {
				reappearAfter = new Date().getTime() + 3153600000000;
			}
			localStorage.setItem("reappear-popup-' . $popupId . '", reappearAfter);
		';

		$scriptContent .= ' });';

		return $scriptContent;
	}

	private function getScriptContent($popupId, $popupAttribs)
	{
		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => { 
			function getImageSrc(imageSrc) {
				if (!imageSrc?.src) return imageSrc;
				if (imageSrc.src.includes("http://") || imageSrc.src.includes("https://")) {
					return { ...imageSrc, src: imageSrc?.src };
				} else {
					const baseUrl = window.location.origin;
					const originalSrc = baseUrl + "/" + imageSrc?.src;
					const formattedSrc = originalSrc.replace(/\\\/g, `/`);
					return { ...imageSrc, src: formattedSrc };
				}
			}

			const popupData = ' . json_encode($popupAttribs) . ';

			const newCloseElement = document.createElement("div");
			newCloseElement.setAttribute("id", "sp-pagebuilder-popup-close-btn-' . $popupId . '");
			newCloseElement.setAttribute("class", "sp-pagebuilder-popup-close-btn sp-pagebuilder-popup-close-btn-hover-' . $popupId . '");
			newCloseElement.setAttribute("role", "button");
			newCloseElement.setAttribute("role", "button");

				if (popupData?.close_btn_text && !popupData?.close_btn_is_icon) {
					newCloseElement.style.gap = "5px";
				}

				newCloseElement.innerHTML = `
					<span class="close-btn-text" style="display: inline-block;">${popupData?.close_btn_text || ""}</span>
					<span class="close-btn-icon ${(popupData?.close_btn_icon !== undefined) ? popupData?.close_btn_icon : "fas fa-times"}" style="display: inline-block;" title="' . (Text::_('COM_SPPAGEBUILDER_TOP_PANEL_CLOSE')) . '"></span>
				`;

				const setClosePopup = (selector = null) => {
					if (selector === null) return;
					Array.from(document.querySelectorAll(selector)).forEach(element => {
						element.addEventListener("click", () => {
							const builder = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup"); 
							' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ' builder.children[0].style.animationDirection = "reverse"; builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['exit_animation'] . '");' : "") . '
							builder.children[0].style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
							builder.children[0].style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
							setTimeout(() => {
								builder.parentNode.style.display = "none";
								document.getElementById("sp-pagebuilder-overlay-' . $popupId . '").style.display = "none";
								document.body.style.overflowY = "auto";
							}, ' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) : "") . ');

							window.onscroll = null;
							document.body.setAttribute("data-stop-timer", "true");
						});
					});
				};
		
				const builder = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
				builder?.children[0]?.insertBefore(newCloseElement, builder?.children[0]?.children[0]);

				builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container");

					let landingDelay = 0;

					if ( ' . ((!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_landing') ? 1 : 0) . ') {
						landingDelay = ' . (!empty($popupAttribs['landing_after']) ? ($popupAttribs['landing_after'] * 1000) : 0) . ';
					}

					if (' . ((!empty($popupAttribs['toggle_enter_animation']) && !empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['enter_animation'] === $popupAttribs['exit_animation'])) ? 1 : 0) . ') {
						setTimeout(() => {
							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container");' : "") . '
						}, landingDelay + ' . ((!empty($popupAttribs['enter_animation_delay']) ? $popupAttribs['enter_animation_delay'] : 0) + (!empty($popupAttribs['enter_animation_duration']) ? $popupAttribs['enter_animation_duration'] : 0)) . ');
					}
				
				setClosePopup("#sp-pagebuilder-popup-close-btn-' . $popupId . '");

				if (popupData?.close_on_click) {
					setClosePopup(popupData?.close_on_click);
				}

		});';
		return $scriptContent;
	}

	private function renderPopupByIds()
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$popupIds = $this->getPopupIds();
		$idArray = array_map(
			function ($item) {
				return $item->id;
			},
			$popupIds ?? []
		);
		
		$popups = $this->getPopupsByIds($idArray);

		if (empty($popups))
		{
			return;
		}
		
		foreach ($popups as $key => $popup)
		{
			$popupContent = $this->popupContents[$key] ?? '';
			$popupId = $popup->id;
			$body = $app->getBody();

			if (!$this->isPopupAccessLevelPermitted($popup->access)) {
				continue;
			}

			$popupAttribs = !empty($popup->attribs) && is_string($popup->attribs) ? json_decode($popup->attribs, true) : [];
			$scriptContent = $this->getScriptContent($popupId, $popupAttribs);
			$cssOutput = $this->getCssOutput($popupAttribs, $popupId);
			$visibilityScriptContent = '';

			if (!empty($popupAttribs['trigger_condition']))
			{
				$visibilityScriptContent = $this->getVisibilityScriptContent($popupId, $popupAttribs);
			}

			$advancedScriptContent = $this->getAdvancedScriptContent($popupId, $popupAttribs);

			$responsive_class = '';

			$responsive_class .= (isset($popupAttribs['hidden_xl']) && filter_var($popupAttribs['hidden_xl'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xl ' : '';
			$responsive_class .= (isset($popupAttribs['hidden_lg']) && filter_var($popupAttribs['hidden_lg'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-lg ' : '';
			$responsive_class .= (isset($popupAttribs['hidden_md']) && filter_var($popupAttribs['hidden_md'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-md ' : '';
			$responsive_class .= (isset($popupAttribs['hidden_sm']) && filter_var($popupAttribs['hidden_sm'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-sm ' : '';
			$responsive_class .= (isset($popupAttribs['hidden_xs']) && filter_var($popupAttribs['hidden_xs'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xs ' : '';

			$popupDiv = '
			<div class="' .$responsive_class. '" id="sp-pagebuilder-overlay-'. $popupId . '" style="position: fixed; inset: 0; z-index: 9999;"></div>
			<div class="sp-page-builder  page-' . $popupId . '  sp-pagebuilder-popup '. $responsive_class .'">
				<div class="sp-pagebuilder-container-popup">
					<div class=" page-content builder-container">' . $popupContent . '</div>
				</div>
				<script>' . $scriptContent . '</script>
				<style>' . $cssOutput . '</style>
				<script>' . $visibilityScriptContent . '</script>
				<script>' . $advancedScriptContent . '</script>
			</div>
			';

			$app->setBody($body . $popupDiv);
		}
	}

	/**
	 * Checks if the current user has permission to access the popup based on its access level.
	 * 
	 * This method retrieves the current user's authorized view levels and checks if the
	 * popup's access level is included in that list. If it is, the user is permitted to
	 * view the popup; otherwise, they are not.
	 * 
	 * @param int $popupAccessLevel The access level of the popup.
	 * 
	 * @return bool True if the user is permitted to view the popup, false otherwise.
	 */
	private function isPopupAccessLevelPermitted($popupAccessLevel)
	{
		$user = Factory::getUser();
		$userAccessLevels = $user->getAuthorisedViewLevels();

		if (in_array($popupAccessLevel, $userAccessLevels))
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks if the provided HTML content is a standard HTML document.
	 * 
	 * This method verifies whether the HTML content contains a DOCTYPE declaration
	 * and includes opening and closing `<html>` tags, which are the key components
	 * of a standard HTML document.
	 * 
	 * @param string $htmlContent
	 * 
	 * @return bool
	 */
	private function isStandardHTMLDocument($htmlContent) {
		$hasDoctype = stripos($htmlContent, '<!DOCTYPE') !== false;
		return $hasDoctype;
	}

	private function renderPopup()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$pageId = $input->get('id', '', 'INT');
		$view = $input->get('view', '', 'STRING');

		$option = $input->get('option', '', 'STRING');

		if ($option == 'com_sppagebuilder' && $view === 'form') 
		{
			return;
		}

		if ($this->isStandardHTMLDocument($app->getBody()) === false) {
			return;
		}

		try 
		{
			if ($option === 'com_sppagebuilder' && !empty($pageId)) {

				$db = Factory::getDbo();
				$query = $db->getQuery(true);
	
				$query->select($db->quoteName(array('extension_view')))
					->from($db->quoteName('#__sppagebuilder'))
					->where($db->quoteName('id') . ' = ' . $pageId);
	
				$db->setQuery($query);
				$result = $db->loadObject();
	
				if (!empty($result->extension_view) && $result->extension_view === 'popup') {
					return;
				}
			}

			$this->renderPopupByIds();
		} 
		catch (RuntimeException $e) {

			$app->enqueueMessage($e->getMessage(), 'error');
		}
	}


	function isShaperHelixUltimate()
	{
		/** @var CMSApplication $app */
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);	
		try {
			/** @var CMSApplication $app */
			$app = Factory::getApplication();
			$body = $app->getBody();
			$doc->loadHTML($body);
			libxml_clear_errors();
			$xpath = new DOMXPath($doc);
			/** @var DOMElement $bodyNode */
			$bodyNode = $xpath->query('//body')->item(0);
			return $bodyNode ? strpos($bodyNode->getAttribute('class'), 'helix-ultimate') !== false : false;
		} catch (Exception $e) {
			return false;
		}
	}

	function divWithHtml(DOMDocument $doc, string $html): DOMElement {
		$wrapper = $doc->createElement('div');
	
		$tmp = new DOMDocument();
		libxml_use_internal_errors(true);
		$tmp->loadHTML('<?xml encoding="UTF-8"><div id="__frag__">'.$html.'</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
	
		$xp  = new DOMXPath($tmp);
		$box = $xp->query('//*[@id="__frag__"]')->item(0);
	
		if ($box) {
			foreach (iterator_to_array($box->childNodes) as $child) {
				$wrapper->appendChild($doc->importNode($child, true));
			}
		}
		return $wrapper;
	}
	
	function onAfterRender()
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();
		$input = $app->input;
		$option = $input->get('option', '', 'STRING');
		$view = $input->get('view', '', 'STRING');
		
		if ($app->isClient('administrator'))
		{
			$integration = self::getIntegration();

			if (!$integration)
			{
				return;
			}

			$layout = $input->get('layout', '', 'STRING');
			$id = $input->get($integration['id_alias'], 0, 'INT');

			if (!($option === 'com_' . $integration['group'] && $view === $integration['view']))
			{
				return;
			}

			if (isset($integration['frontend_only']) && $integration['frontend_only'])
			{
				return;
			}

			// Page Builder state
			$pagebuilder_enabled = 0;
			$viewId = 0;
			$language = "*";

			if ($page_content = self::getPageContent($option, $view, $id))
			{
				$page_content = ApplicationHelper::preparePageData($page_content);
				$viewId = $page_content->id;
				$pagebuilder_enabled = $page_content->active;
				$language = $page_content->language;
			}

			// Add script
			$body = $app->getBody();

			$frontendEditorLink = 'index.php?option=com_sppagebuilder&view=form&tmpl=component&layout=edit&extension=com_content&extension_view=article&id=' . $viewId;
			$backendEditorLink = 'index.php?option=com_sppagebuilder&view=editor&extension=com_content&extension_view=article&article_id=' . $id;

			if ($language && $language !== '*' && Multilanguage::isEnabled())
			{

				$frontendEditorLink .= '&lang=' . $language;
				$backendEditorLink .= '&lang=' . $language;
			}

			$backendEditorLink .= '&tmpl=component#/editor/' . $viewId;

			$frontendEditorLink = str_replace('/administrator', '', SppagebuilderHelperRoute::buildRoute($frontendEditorLink));

			if (!$viewId || !$pagebuilder_enabled)
			{
				$dashboardHTML = '<div class="sp-pagebuilder-alert sp-pagebuilder-alert-info">' . Text::_('Save the article first for getting the editor!') . '</div>';
			}
			else
			{
				$dashboardHTML = '<a href="' . $backendEditorLink . '" class="sp-pagebuilder-button-outline">Edit with Backend Editor</a><a href="' . $frontendEditorLink . '" class="sp-pagebuilder-button">Edit with Frontend Editor</a>';
			}

			if ($option === 'com_k2')
			{
				$body = str_replace('<div class="k2ItemFormEditor">', '<div class="builder-integrations"><div class="builder-integration-toggler"><span class="builder-integration-button builder-integration-button-joomla" action-switch-builder data-action="editor" role="button">Joomla Editor</span><span class="builder-integration-button builder-integration-button-editor" action-switch-builder data-action="sppagebuilder" role="button">Edit with SP Page Builder</span></div></div><div class="builder-integration-component pagebuilder-' . str_replace('_', '-', $option) . '" style="display: none;"></div><div class="k2ItemFormEditor">', $body);
			}
			else
			{
				$body = str_replace('<fieldset class="adminform">', '<div class="builder-integrations"><div class="builder-integration-toggler"><span class="builder-integration-button builder-integration-button-joomla" action-switch-builder data-action="editor" role="button">Joomla Editor</span><span class="builder-integration-button builder-integration-button-editor" action-switch-builder data-action="sppagebuilder" role="button"><span class="builder-svg-icon"><svg fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 24"><path d="M17.718 13.306c.658-.668 1.814-.642 2.476 0 .677.66.655 1.747 0 2.414a43.761 43.761 0 0 1-2.11 2.04C13.586 21.77 7.932 24.178 1.77 23.977.82 23.95.019 23.223.019 22.271c0-.901.804-1.736 1.75-1.707 1.943.062 3.406-.062 5.206-.507a20.241 20.241 0 0 0 2.072-.635c.171-.062.341-.128.51-.197l.224-.098c.292-.131.584-.267.872-.408a22.872 22.872 0 0 0 3.225-1.96c.075-.054.146-.109.221-.16l-.086.066c.105-.08.21-.16.314-.244a32.013 32.013 0 0 0 1.703-1.463c.58-.533 1.137-1.09 1.688-1.652Zm-9.886-.843c.562-.292 1.1-.628 1.609-1.002a.32.32 0 0 0 .128-.258.312.312 0 0 0-.136-.253L5.411 8.123a.331.331 0 0 0-.47.092.312.312 0 0 0-.047.167l.015 4.716a.311.311 0 0 0 .127.25.33.33 0 0 0 .281.056 11.07 11.07 0 0 0 2.515-.941ZM15.356 9.699 4.213 1.39 2.806.343.27.843c-.527.879-.134 1.772.622 2.334 3.712 2.767 7.427 5.54 11.143 8.308.52.387 1.04.773 1.557 1.16.751.561 1.96.113 2.394-.612.528-.88.127-1.773-.629-2.334Z" fill="currentColor"/><path d="M7.098 17.74c1.093-.243 2.17-.7 3.17-1.177 2.08-.988 4.007-2.41 5.444-4.184.299-.368.513-.714.513-1.207 0-.42-.192-.92-.513-1.207-.632-.565-1.871-.748-2.477 0-.55.683-1.17 1.31-1.852 1.87-.116.096-.236.191-.352.286.273-.194-.288.23 0 0-.8.564-1.635 1.072-2.526 1.495-.19.091-.381.175-.572.259-.124.054-.412.138.13-.051-.093.033-.183.073-.272.11-.277.105-.558.207-.843.298-.253.08-.512.16-.774.219-.894.197-1.504 1.251-1.224 2.101.3.908 1.19 1.4 2.148 1.189ZM2.86.38A1.753 1.753 0 0 0 1.774 0C.824 0 .023.78.023 1.707V22.22c0 .923.804 1.707 1.75 1.707.952 0 1.752-.78 1.752-1.707V.875L2.859.38Z" fill="currentColor"/></svg></span> SP Page Builder</span></div></div><div class="builder-integration-component pagebuilder-' . str_replace('_', '-', $option) . '" style="display: none;">' . $dashboardHTML . '</div><fieldset class="adminform">', $body);
			}

			// Page Builder fields
			$body = str_replace('</form>', '<input type="hidden" id="jform_attribs_sppagebuilder_content" name="jform[attribs][sppagebuilder_content]"></form>' . "\n", $body);
			$body = str_replace('</form>', '<input type="hidden" id="jform_attribs_sppagebuilder_article_id" name="jform[attribs][sppagebuilder_article_id]" value="' . $id . '"></form>' . "\n", $body);
			$body = str_replace('</form>', '<input type="hidden" id="jform_attribs_sppagebuilder_active" name="jform[attribs][sppagebuilder_active]" value="' . $pagebuilder_enabled . '"></form>' . "\n", $body);

			$app->setBody($body);
		}

		if ($app->isClient('site'))
		{
			$this->renderPopup();

			if ($view !== 'form' && $this->isStandardHTMLDocument($app->getBody()) === true)
			{
				$this->renderColorSwitcher();
			}

			if ($option === 'com_content' && $view === 'article') {
				$body = $app->getBody();
				$doc = new DOMDocument();
				libxml_use_internal_errors(true);
				$doc->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
				libxml_clear_errors();

				if ($this->articleDetailsPageContent) {
					$xpath = new DOMXPath($doc);
					if (!empty($xpath->query('//main')) && $xpath->query('//main')->length > 0) {
						$querySelector = "//main";
						if ($this->isShaperHelixUltimate()) {
							$querySelector = "//*[@id='sp-main-body']";
						}

						foreach ($xpath->query($querySelector) as $node) {
							while ($node->firstChild) {
								$node->removeChild($node->firstChild);
							}
							$div = $this->divWithHtml($doc, $this->articleDetailsPageContent);
							$div->setAttribute('class', 'page-content');
							$divWrapper = $doc->createElement('div');
							$divWrapper->setAttribute('id', 'sp-page-builder');
							$divWrapper->setAttribute('class', 'sp-page-builder');
							$divWrapper->appendChild($div);
							$node->appendChild($divWrapper);
						}
						
						$out = $doc->saveHTML();
						$app->setBody($out);
					}
				}
			} else if ($option === 'com_content' && ($view === 'category' || $view === 'featured' || $view === 'archive')) {
				$body = $app->getBody();
				$doc = new DOMDocument();
				libxml_use_internal_errors(true);
				$doc->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
				libxml_clear_errors();

				if ($this->articleIndexPageContent) {
					$xpath = new DOMXPath($doc);
					if (!empty($xpath->query('//main')) && $xpath->query('//main')->length > 0) {
						$querySelector = "//main";
						if ($this->isShaperHelixUltimate()) {
							$querySelector = "//*[@id='sp-main-body']";
						}

						foreach ($xpath->query($querySelector) as $node) {
							while ($node->firstChild) {
								$node->removeChild($node->firstChild);
							}
							$div = $this->divWithHtml($doc, $this->articleIndexPageContent);
							$div->setAttribute('class', 'page-content');
							$divWrapper = $doc->createElement('div');
							$divWrapper->setAttribute('id', 'sp-page-builder');
							$divWrapper->setAttribute('class', 'sp-page-builder');
							$divWrapper->appendChild($div);
							$node->appendChild($divWrapper);
						}
		
						$out = $doc->saveHTML();
						$app->setBody($out);
					}
				}
			}
		}
	}

	/**
	 * Render the color switcher.
	 *
	 * @return 	void
	 * @since 	5.7.0
	 */
	private function renderColorSwitcher()
	{
		$params = ComponentHelper::getParams('com_sppagebuilder');
		$colorVariables = $params->get('sppb_color_variables', []);
		$isEnabledColoSwitcher = $params->get('show_color_switcher', 0);
		
		$modes = [];
		$colors = [];

		foreach($colorVariables as $colorVariable) {
			$path = $colorVariable->path;
			$mode = $path[1];
			$value = $colorVariable->value;
			
			if (!isset($colors[$mode])) {
				array_push($modes, $mode);
				$colors[$mode] = [$value];
			} else {
				array_push($colors[$mode], $value);
			}
		}

		if($isEnabledColoSwitcher && count($modes) > 1) {
			$app = Factory::getApplication();
			$body = $app->getBody();
			$colorSwitcherContent = '
				<div class="sppb-color-switcher-modes">
					<div class="sppb-color-switcher-toggle">
						<svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                			<path fill-rule="evenodd" clip-rule="evenodd" d="M0.25 0.75H7.25V12.75C7.25 14.683 5.683 16.25 3.75 16.25C1.817 16.25 0.25 14.683 0.25 12.75V0.75ZM1.75 2.25V12.75C1.75 13.8546 2.64543 14.75 3.75 14.75C4.85457 14.75 5.75 13.8546 5.75 12.75V2.25H1.75Z" fill="#415162"></path>
                			<path d="M4.25 12.716C4.25 12.9921 4.02614 13.216 3.75 13.216C3.47386 13.216 3.25 12.9921 3.25 12.716C3.25 12.4398 3.47386 12.216 3.75 12.216C4.02614 12.216 4.25 12.4398 4.25 12.716Z" fill="#415162"></path>
            				<path fill-rule="evenodd" clip-rule="evenodd" d="M9.89941 0.9375L14.8492 5.88725L8 13L7.96967 10.6454L12.7278 5.88725L9.89941 3.05882L8 5L7.96967 2.86724L9.89941 0.9375Z" fill="#415162"></path>
                			<path fill-rule="evenodd" clip-rule="evenodd" d="M15.75 9.25V16.25H6L7.5 14.75H14.25V10.75H11L12.5 9.25H15.75Z" fill="#415162"></path>
            			</svg>
						<span>
                			<i class="fas fa-chevron-up"></i>
                			<i class="fas fa-chevron-down"></i>
            			</span>
					</div>
					<div class="sppb-color-switcher-colors-wrapper">
					<div class="sppb-color-switcher-colors">
					' . implode('', array_map(function($mode) use ($colors) {
						$gradientColors = count($colors[$mode]) > 1 
							? $colors[$mode][0] . ' 50%, ' . $colors[$mode][1] . ' 50%'
							: $colors[$mode][0] . ' 100%';
						
						return sprintf(
							'<span class="sppb-color-switcher-color" data-mode="%s" style="background-image: linear-gradient(-45deg, %s)"></span>',
							$mode,
							$gradientColors
						);
					}, $modes)) . '
					</div>
					</div>
				</div>
			';
			$app->setBody($body . $colorSwitcherContent);
		}
	}

	private function getArticleDetailsPage($id) {
		if (empty($id)) {
			return '';
		}

		try {
			$db = Factory::getDbo();
			
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__sppagebuilder'))
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_sppagebuilder'))
				->where($db->quoteName('extension_view') . ' = ' . $db->quote('dynamic_content:detail'))
				->where($db->quoteName('view_id') . ' = ' . CollectionIds::ARTICLES_COLLECTION_ID)
				->where($db->quoteName('published') . ' = 1');
			
			$db->setQuery($query);
			$page = $db->loadObject();

			if (empty($page)) {
				return '';
			}
			
			if (!class_exists('ApplicationHelper')) {
				require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/editor/helpers/ApplicationHelper.php';
			}
			
			$page = ApplicationHelper::preparePageData($page);
			
			$app = Factory::getApplication();
			$input = $app->input;
			$input->set('collection_item_id', [$id]);
			$input->set('collection_type', 'articles');
			
			if (!class_exists('AddonParser')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/parser/addon-parser.php';
			}
			
			if (!class_exists('SppagebuilderHelperSite')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/helper.php';
			}
			
			SppagebuilderHelperSite::initView($page);
			
			$content = AddonParser::viewAddons($page->text, 0, 'page-' . $page->id);

			$css = '';
			if (isset($page->css) && $page->css) {
				$css = '<style type="text/css">' . $page->css . '</style>';
			}
			
			return $css . $content;
			
		} catch (Exception $e) {
			// Log error for debugging
			$app = Factory::getApplication();
			if ($app->isClient('administrator')) {
				$app->enqueueMessage('Error rendering article details page: ' . $e->getMessage(), 'error');
			}
			return '';
		}
	}

	private function getArticleIndexPage($id) {
		try {
			$db = Factory::getDbo();
			
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__sppagebuilder'))
				->where($db->quoteName('extension') . ' = ' . $db->quote('com_sppagebuilder'))
				->where($db->quoteName('extension_view') . ' = ' . $db->quote('dynamic_content:index'))
				->where($db->quoteName('view_id') . ' = ' . CollectionIds::ARTICLES_COLLECTION_ID)
				->where($db->quoteName('published') . ' = 1');
			
			$db->setQuery($query);
			$page = $db->loadObject();

			if (empty($page)) {
				return '';
			}
			
			
			if (!class_exists('ApplicationHelper')) {
				require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/editor/helpers/ApplicationHelper.php';
			}
			
			$page = ApplicationHelper::preparePageData($page);
			
			$app = Factory::getApplication();
			$input = $app->input;
			$input->set('collection_item_id', [$id]);
			$input->set('collection_type', 'articles');
			
			
			if (!class_exists('AddonParser')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/parser/addon-parser.php';
			}
			
			if (!class_exists('SppagebuilderHelperSite')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/helper.php';
			}
			
			SppagebuilderHelperSite::initView($page);
			
			$content = AddonParser::viewAddons($page->text, 0, 'page-' . $page->id);
			
			$css = '';
			if (isset($page->css) && $page->css) {
				$css = '<style type="text/css">' . $page->css . '</style>';
			}
			
			return $css . $content;
		} catch (Exception $e) {
			// Log error for debugging
			$app = Factory::getApplication();
			if ($app->isClient('administrator')) {
				$app->enqueueMessage('Error rendering article index page: ' . $e->getMessage(), 'error');
			}
			return '';
		}
	}

	private function loadArticleDetailsPage()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$id = $input->get('id', 0, 'INT');

		$params = ComponentHelper::getParams('com_sppagebuilder');
		$showArticleDetailsPageAsDefault = $params->get('show_article_details_page_as_default', 0);
		
		if (empty($id)) {
			return;
		}

		if (!$showArticleDetailsPageAsDefault) {
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select(['id, content'])
				->from($db->quoteName('#__sppagebuilder'))
				->where($db->quoteName('extension_view') . ' = ' . $db->quote('article'))
				->where($db->quoteName('view_id') . ' = ' . $db->quote($id))
				->where($db->quoteName('active') . ' = ' . $db->quote('1'))
				->where($db->quoteName('published') . ' = 1');
			$db->setQuery($query);

			$result = $db->loadObject();

			if (!empty($result->content)) {
				$articleContent = json_decode($result->content);

				if (!empty($articleContent)) {
					return null;
				}

			}
		}
		
		$detailsPage = $this->getArticleDetailsPage($id);
		
		if (!empty($detailsPage)) {
			$this->articleDetailsPageContent = $detailsPage;
		}
	}

	private function loadArticleIndexPage()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$id = $input->get('id', 0, 'INT');
		
		
		$indexPage = $this->getArticleIndexPage($id);
		
		if (!empty($indexPage)) {
			$this->articleIndexPageContent = $indexPage;
		}
	}

	/**
	 * Get the default colors from the template style (Helix)
	 * 
	 * @return mixed
	 * @since 5.7.0
	 */
	private function getDefaultThemeColors()
	{
		$colorPrefix = 'sppb';

		$keysToExtract = [
			"topbar_bg_color",
			"topbar_text_color",
			"header_bg_color",
			"logo_text_color",
			"menu_text_color",
			"menu_text_hover_color",
			"menu_text_active_color",
			"menu_dropdown_bg_color",
			"menu_dropdown_text_color",
			"menu_dropdown_text_hover_color",
			"menu_dropdown_text_active_color",
			"offcanvas_menu_icon_color",
			"offcanvas_menu_bg_color",
			"offcanvas_menu_items_and_items_color",
			"offcanvas_menu_active_menu_item_color",
			"text_color",
			"bg_color",
			"link_color",
			"link_hover_color",
			"footer_bg_color",
			"footer_text_color",
			"footer_link_color",
			"footer_link_hover_color",
		];

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['params'])
			->from($db->quoteName('#__template_styles'))
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('home') . ' = 1');
		$db->setQuery($query);

		try
		{
			$ext = $db->loadObject();

			$styleObj = !empty($ext->params) ? $ext->params : "{}";

			$styleObjDecoded = \json_decode($styleObj);

			$isCustomTemplateStyle = isset($styleObjDecoded->custom_style) && $styleObjDecoded->custom_style == 1;

			if(!$isCustomTemplateStyle && isset($styleObjDecoded->preset) && !empty($styleObjDecoded->preset)) {
				$styleObjDecoded = json_decode($styleObjDecoded->preset);
			}

			$newStyleObj = new \stdClass();

			foreach ($keysToExtract as $key) {
				if (isset($styleObjDecoded->$key)) {
					$newStyleObj->$key = $styleObjDecoded->$key;
				}
			}

			$styleObjDecoded = $newStyleObj;

			if (empty($styleObjDecoded->custom_style) && !empty($styleObjDecoded->preset)) {
				$styleObjDecoded = json_decode($styleObjDecoded->preset);
			}

			$colorValues = [];

			foreach ($styleObjDecoded as $key => $value) {
				if (is_string($value) && !empty($value)) {
					array_push($colorValues, [
						'path' => [$colorPrefix . '-'  . str_replace('_', '-', strtolower
						($key)), ''],
						'value' => $value,
						'isTemplateColor' => true,
					]);
				}
			}

			return json_encode($colorValues);
		} catch (\Exception $e) {
			return "{}";
		}
	}

	/**
	 * Remove the Joomla! default template styles for the editor view.
	 *
	 * @return 	void
	 * @since 	4.1.0
	 */
	public function onBeforeCompileHead()
	{
		/** @var CMSApplication */

		$app = Factory::getApplication();
		$input = $app->input;
		$option = $input->get('option');
		$view = $input->get('view', 'editor');

		$version = new Version();
		$JoomlaVersion = (float) $version->getShortVersion();

		$doc = $app->getDocument();

		$params = ComponentHelper::getParams('com_sppagebuilder');
		$colorVariables = $params->get('sppb_color_variables', []);
		$defaultColorMode = '';
		$themeColors = json_decode($this->getDefaultThemeColors());
		$themeColorVariables = [];
		$modes = [];

		if(!empty($themeColors)) {
			foreach($themeColors as $themeColor) {
				$variableName = '--' . $themeColor->path[0];
				$colorValue = $themeColor->value;
				array_push($themeColorVariables, $variableName . ": " . $colorValue);
			}

			$themeColorVariableString = ':root {'. implode("; ", $themeColorVariables) . '}';

			$doc->addStyleDeclaration($themeColorVariableString);
		}

		if(!empty($colorVariables)) {
			if(count($colorVariables) > 0) {
				foreach($colorVariables as $colorVariable) {
					$path = $colorVariable->path;
					$mode = $path[1];

					array_push($modes, $mode);
				}

				$modes = array_unique($modes);
				$defaultColorMode = $modes[0];
			}
		}

		$isEnabledColoSwitcher = $params->get('show_color_switcher', 0);

		if ($app->isClient('site')) {
            $cookie = $app->input->cookie->get('sppb_user_timezone', null, 'STRING');

			if(!$cookie){
				$timeZoneCookie = <<<JS
				(function () {
					try {
						var SPPB_TIME_ZONE = Intl.DateTimeFormat().resolvedOptions().timeZone;

						if (!SPPB_TIME_ZONE){
							return;
						}

						document.cookie =
							'sppb_user_timezone=' + encodeURIComponent(SPPB_TIME_ZONE) +
							'; path=/' +
							'; max-age=43200'; // 12 hours
					} catch (e) {}
				})();
				JS;

				$doc->addScriptDeclaration($timeZoneCookie);
			}
        }
		
		$doc->addScriptDeclaration('
			const initColorMode = () => {
				const colorVariableData = [];
				const sppbColorVariablePrefix = "--sppb";
				let activeColorMode = localStorage.getItem("sppbActiveColorMode") || "' . $defaultColorMode . '";
				' . (!$isEnabledColoSwitcher ? ('activeColorMode = "' . $defaultColorMode . '"') : '') . ';
				const modes = ' . json_encode($modes) . ';

				if(!modes?.includes(activeColorMode)) {
					activeColorMode = "' . $defaultColorMode . '";
					localStorage.setItem("sppbActiveColorMode", activeColorMode);
				}

				document?.body?.setAttribute("data-sppb-color-mode", activeColorMode);

				if (!localStorage.getItem("sppbActiveColorMode")) {
					localStorage.setItem("sppbActiveColorMode", activeColorMode);
				}

				if (window.sppbColorVariables) {
					const colorVariables = typeof(window.sppbColorVariables) === "string" ? JSON.parse(window.sppbColorVariables) : window.sppbColorVariables;

					for (const colorVariable of colorVariables) {
						const { path, value } = colorVariable;
						const variable = String(path[0]).trim().toLowerCase().replaceAll(" ", "-");
						const mode = path[1];
						const variableName = `${sppbColorVariablePrefix}-${variable}`;

						if (activeColorMode === mode) {
							colorVariableData.push(`${variableName}: ${value}`);
						}
					}

					document.documentElement.style.cssText += colorVariableData.join(";");
				}
			};

			window.sppbColorVariables = ' . json_encode($colorVariables) . ';
			
			initColorMode();

			document.addEventListener("DOMContentLoaded", initColorMode);
		');
		


		if($app->isClient('site') && $view !== 'form')
		{
			SppagebuilderHelper::addScript('color-switcher.js', '');
			SppagebuilderHelper::addStylesheet('color-switcher.css', '');
		}

		if ($app->isClient('administrator') && $option === 'com_sppagebuilder' && $view === 'editor')
		{
			if ($JoomlaVersion < 4)
			{
				$headData = Factory::getDocument()->getHeadData();
				$stylesheets = $headData['styleSheets'];

				foreach ($stylesheets as $url => $value)
				{
					if (stripos($url, 'template.css') !== false)
					{
						unset($stylesheets[$url]);
					}
				}

				$headData['styleSheets'] = $stylesheets;

				Factory::getDocument()->setHeadData($headData);
			}
			else
			{
				$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
				$wa->disablePreset('template.atum.ltr');
				$wa->disablePreset('template.atum.rtl');
				$wa->disableStyle('template.atum.ltr');
				$wa->disableStyle('template.atum.rtl');
				$wa->disableStyle('template.active.language');
				$wa->disableStyle('template.user');
			}
		}
	}

	/**
	 * Enforce the application to use tmpl=component if there is not.
	 *
	 * @return	void
	 * @since 	4.1.0
	 */
	public function onAfterDispatch()
	{
		$app = Factory::getApplication();
		$input = $app->input;

		$option = $input->get('option');
		$view = $input->get('view', 'editor');
		$tmpl = $input->get('tmpl');

		if ($app->isClient('administrator') && $option === 'com_sppagebuilder' && $view === 'editor')
		{
			if ($tmpl !== 'component')
			{
				$input->set('tmpl', 'component');
			}
		}
	}

	private static function loadPageBuilderSiteLanguage() {
		$lang = Factory::getLanguage();
		$lang->load('com_sppagebuilder', JPATH_SITE, 'en-GB', true);
		$lang->load('com_sppagebuilder', JPATH_SITE, null, true);
	}

	private static function loadPageBuilderLanguage()
	{
		$lang = Factory::getLanguage();
		$lang->load('com_sppagebuilder', JPATH_ADMINISTRATOR, $lang->getName(), true);
		$lang->load('tpl_' . self::getTemplate(), JPATH_SITE, $lang->getName(), true);
		require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/helpers/language.php';
	}

	private static function getPageContent($extension = 'com_content', $extension_view = 'article', $view_id = 0)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id', 'text', 'content', 'active', 'language', 'version')));
		$query->from($db->quoteName('#__sppagebuilder'));
		$query->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
		$query->where($db->quoteName('extension_view') . ' = ' . $db->quote($extension_view));
		$query->where($db->quoteName('view_id') . ' = ' . $view_id);
		$db->setQuery($query);
		$result = $db->loadObject();

		if ($result)
		{
			return $result;
		}

		return false;
	}

	private static function getIntegration()
	{
		$app = Factory::getApplication();
		$option = $app->input->get('option', '', 'STRING');
		$group = str_replace('com_', '', $option);
		$integrations = BuilderIntegrationHelper::getIntegrations();

		if (!isset($integrations[$group]))
		{
			return false;
		}

		$integration = $integrations[$group];
		$name = $integration['name'];
		$enabled = PluginHelper::isEnabled($group, $name);

		if ($enabled)
		{
			return $integration;
		}

		return false;
	}

	private static function getTemplate()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('template')));
		$query->from($db->quoteName('#__template_styles'));
		$query->where($db->quoteName('client_id') . ' = ' . $db->quote(0));
		$query->where($db->quoteName('home') . ' = ' . $db->quote(1));
		$db->setQuery($query);
		return $db->loadResult();
	}

	public function onExtensionAfterSave($option, $data)
	{
		if (($option === 'com_config.component') && ($data->element === 'com_sppagebuilder'))
		{
			$admin_cache = JPATH_ROOT . '/administrator/cache/sppagebuilder';

			if (\file_exists($admin_cache))
			{
				Folder::delete($admin_cache);
			}

			$site_cache = JPATH_ROOT . '/cache/sppagebuilder';

			if (\file_exists($site_cache))
			{
				Folder::delete($site_cache);
			}
		}
	}

	/**
	 * onTableAfterLoad
	 * This joomla event function is called during the initial phase of duplication
	 * It is used to capture the data related to the module which needs to be duplicated
	 * (function is run for every module in the list, but goes down the list from the initial order of the module in point, so the first time the function is called it always provides with the data of the module which is supposed to be duplicated)
	 * The function doesnt run for other irrelevant modules and returns if moduleData is already present, which happens when the function runs for the first time
	 * Using the information from the event, the page builder content is extracted from the module and stored in moduleData
	 * @param AfterLoadeEvent $event, the event provides us with the data of the modules
	 * @return void
	 * @since 5.4
	 */
	
	public function onTableAfterLoad(AfterLoadEvent $event)
	{
		if(!empty($this->moduleData))
		{
			return;
		}

		$app = Factory::getApplication();
		$input = $app->input;
		$option = $input->get('option');
		$view = $input->get('view', 'editor');
		$task = $input->get('task');

		if($app->isClient('administrator') && $option === 'com_modules' && $view === 'modules' && $task == 'duplicate')
		{
			$module = $event['subject'];

			if($module instanceof Joomla\CMS\Table\Module)
			{
				$id = $module->id;

				if($id)
				{
					$db = Factory::getDbo();
					$query = $db->getQuery();
					$query->clear();
					$query->select('*')->from($db->quoteName('#__sppagebuilder'))->where($db->quoteName('view_id') . '=' . $id);
					$db->setQuery($query);
					$result = $db->loadObject();

					if(!empty($result))
					{
						$status = $result->published;

						if($status === -2)
						{
							return;
						}

						$this->moduleData = $result;
					}
				}
				
			}
		}

	}

	/**
	 * onTableAfterStore
	 * This joomla event function is called after a module has been duplicated
	 * It is used to update the duplicated module with the page builder content data of the original module it was duplicated from
	 * The content data is from the duplication action is stored inside the moduleContent static variable
	 * Through the event we get the duplicated modules id by exploding the name property in the provided events subject section
	 * A new page builder entry is created and the title is passed from the duplicated modules title and the view id is updated to be the duplicated modules id
	 *
	 * @param AfterStoreEvent $event, the event provides us with the newly duplicated modules id
	 * @return void
	 * @since 5.4
	 */
	public function onTableAfterStore(AfterStoreEvent $event)
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$option = $input->get('option');
		$view = $input->get('view', 'editor');
		$task = $input->get('task');

		if($app->isClient('administrator') && $option === 'com_modules' && $view === 'modules' && $task == 'duplicate')
		{
			$module = $event['subject'];

			if($module instanceof Joomla\CMS\Table\Asset)
			{
				$moduleId = array_pop(explode('.', $module->name));
				$moduleContent = $this->moduleData->content ?? $this->moduleData->text ?? '[]';
				$moduleContentParsed = json_decode($moduleContent);

				foreach($moduleContentParsed as $section)
				{
					if(isset($section->id) && !empty($section->id))
					{
						$section->id = $this->uuid();
					}
				}

				$moduleContent = json_encode($moduleContentParsed);
				$user = Factory::getUser();
				$dateTime = Factory::getDate()->toSql();

				$values = [
					'title' => $module->title,
					'text' => '',
					'content' => $moduleContent,
					'option' => 'mod_sppagebuilder',
					'view' => 'module',
					'id' => $moduleId,
					'active' => 0,
					'published' => 1,
					'catid'		=> 0,
					'created_on' => $dateTime,
					'created_by' => $user->id,
					'modified' => $dateTime,
					'modified_by' => $user->id,
					'access' => $this->moduleData->access,
					'language' => '*',
					'action' => 'apply',
					'version' => SppagebuilderHelper::getVersion()
				];

				SppagebuilderHelper::onAfterSavingModule($values);
			}
			
		}

	}

	private function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, // Version 4 UUID
            mt_rand(0, 0x3fff) | 0x8000, // Variant
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

	public function onPreprocessMenuItems($context, &$items)
	{
		if (version_compare(JVERSION, '4.0.0', '<')) {
            return;
        }

		if (!Factory::getApplication()->isClient('administrator'))
		{
			return;
		}

		if ($context !== 'com_menus.administrator.module')
		{
			return;
		}

		static $isMenuItemAlreadyAdded = false;
		if ($isMenuItemAlreadyAdded) {
			return;
		}
		
		$isMenuItemAlreadyAdded = true;

		self::loadPageBuilderLanguage();

		$newItem = new AdministratorMenuItem([
			'id'     => 'custom-reports',
			'title'  => Text::_('COM_SPPAGEBUILDER_COMMENT_TITLE'),
			'link'   => 'index.php?option=com_sppagebuilder&view=comments',
			'access' => 1,
			'icon'   => 'fas fa-comment',
			'class'  => 'menu-item-icon icon-comment',
		]);

		foreach ($items as $item)
		{
			
			if ($item->title === 'COM_CONTENT_MENUS' && $item->hasChildren())
			{
				$item->addChild($newItem);
				break;
			}
		}
	}

}
