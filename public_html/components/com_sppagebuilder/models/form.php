<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Models\CollectionField;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;

//no direct access
defined('_JEXEC') or die;

JLoader::register('SppagebuilderHelperRoute', JPATH_ROOT . '/components/com_sppagebuilder/helpers/route.php');

// Base this model on the backend version.
JLoader::register('SppagebuilderModelPage', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models/page.php');

class SppagebuilderModelForm extends SppagebuilderModelPage
{
	public function __construct($config = [])
    {
        parent::__construct($config);
		
        $this->setDispatcher(\Joomla\CMS\Factory::getApplication()->getDispatcher());
    }

	protected $_conText = 'com_sppagebuilder.page';
	protected $_item = array();

	protected function populateState()
	{
		$app = Factory::getApplication('site');

		$pageId = $app->input->getInt('id');
		$this->setState('page.id', $pageId);

		$user = Factory::getUser();

		if ((!$user->authorise('core.edit.state', 'com_sppagebuilder')) && (!$user->authorise('core.edit', 'com_sppagebuilder')))
		{
			$this->setState('filter.published', 1);
		}
	}

	public function getItem($pageId = null)
	{
		/** @var CMSApplication */
		$app = Factory::getApplication();
		$user = Factory::getUser();
		$pageId = (!empty($pageId)) ? $pageId : (int)$this->getState('page.id');

		if ($user->guest)
		{
			$return = 'index.php?option=com_sppagebuilder&view=form&layout=edit&tmpl=component&id=' . $pageId;
			$loginUrl = Route::_('index.php?option=com_users&view=login&return=' . base64_encode($return), false);
			$app->redirect($loginUrl);
		}

		$canEdit = $user->authorise('core.edit', 'com_sppagebuilder');
		$canEditOwn = $user->authorise('core.edit.own', 'com_sppagebuilder');

		if($canEditOwn) {
			$item_info  = SppagebuilderModelPage::getPageInfoById($pageId);

			$canEditOwn = $item_info->created_by == $user->id;
		}

		if (!$canEdit && !$canEditOwn)
		{
			throw new Exception(Text::_('COM_SPPAGEBUILDER_INVALID_EDIT_ACCESS'), 403);
		}

		if (!isset($this->_item[$pageId]))
		{
			try
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true);

				$query->select('a.*')
					->from($db->quoteName('#__sppagebuilder', 'a'))
					->where($db->quoteName('a.id') . ' = ' . (int) $pageId);

				$query->select($db->quoteName('l.title', 'language_title'))
					->join('LEFT', $db->quoteName('#__languages', 'l') . ' ON ' . $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

				$query->select($db->quoteName('ua.name', 'author_name'))
					->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ' . $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));

				$published = $this->getState('filter.published');

				if (is_numeric($published))
				{
					$query->where($db->quoteName('a.published') . ' = ' . (int) $published);
				}
				elseif ($published === '')
				{
					$query->where($db->quoteName('a.published') . ' IN (0, 1)');
				}

				$db->setQuery($query);
				$data = $db->loadObject();

				if (empty($data))
				{
					throw new Exception(Text::_('COM_SPPAGEBUILDER_ERROR_PAGE_NOT_FOUND'), 404);
				}

				if ($this->getState('filter.access'))
				{
					$data->access_view = true;
				}
				else
				{
					$user = Factory::getUser();
					$groups = $user->getAuthorisedViewLevels();

					$data->access_view = in_array($data->access, $groups);
				}

				if (isset($data->attribs))
				{
					$attribs = json_decode($data->attribs);
				}
				else
				{
					$attribs = new stdClass;
				}

				$data->link = SppagebuilderHelperRoute::getPageRoute($data->id, $data->language);
				$data->formLink = SppagebuilderHelperRoute::getFormRoute($data->id, $data->language);

				$data->meta_description = (isset($attribs->meta_description) && $attribs->meta_description) ? $attribs->meta_description : '';
				$data->meta_keywords = (isset($attribs->meta_keywords) && $attribs->meta_keywords) ? $attribs->meta_keywords : '';
				$data->robots = (isset($attribs->robots) && $attribs->robots) ? $attribs->robots : '';
				$data->og_type = (isset($attribs->og_type) && $attribs->og_type) ? $attribs->og_type : 'website';
				$data->author = (isset($attribs->author) && $attribs->author) ? $attribs->author : '';

				$menu = $this->getMenuByPageId($data->id);
				$data->menuid = (isset($menu->id) && $menu->id) ? $menu->id : 0;
				$data->menutitle = (isset($menu->title) && $menu->title) ? $menu->title : '';
				$data->menualias = (isset($menu->alias) && $menu->alias) ? $menu->alias : '';
				$data->menutype = (isset($menu->menutype) && $menu->menutype) ? $menu->menutype : '';
				$data->menuparent_id = (isset($menu->parent_id) && $menu->parent_id) ? $menu->parent_id : 0;
				$data->menuordering = (isset($menu->id) && $menu->id) ? $menu->id : -2;

				$this->_item[$pageId] = $data;
			}
			catch (Exception $e)
			{
				if ($e->getCode() == 404)
				{
					throw new Exception($e->getMessage(), 404);
				}
				else
				{
					$this->setError($e);
					$this->_item[$pageId] = false;
				}
			}
		}


		return $this->_item[$pageId];
	}

	public function getForm($data = array(), $loadData = true)
	{
		$app = Factory::getApplication();
		$user = Factory::getUser();

		// Get the form.
		$form = $this->loadForm('com_sppagebuilder.page', 'page', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		// Manually check-out
		$pageId = (!empty($pageId)) ? $pageId : (int)$this->getState('page.id');
		if ($user->id)
		{
			$this->checkout($pageId);
		}


		return parent::getForm();
	}

	public function savePage($data)
	{
		return parent::save($data);
	}

	public function save($data)
	{
		$attribs = array();

		if (isset($data['meta_description']) && $data['meta_description'])
		{
			$attribs['meta_description'] = $data['meta_description'];
		}

		if (isset($data['meta_keywords']) && $data['meta_keywords'])
		{
			$attribs['meta_keywords'] = $data['meta_keywords'];
		}

		if (isset($data['robots']) && $data['robots'])
		{
			$attribs['robots'] = $data['robots'];
		}

		if (isset($data['og_type']) && $data['og_type'])
		{
			$attribs['og_type'] = $data['og_type'];
		}

		if (isset($data['author']) && $data['author'])
		{
			$attribs['author'] = $data['author'];
		}

		$data['attribs'] = json_encode($attribs);

		return parent::save($data);
	}

	public function getMenuByPageId($pageId = 0)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select(array('a.*'));
		$query->from('#__menu as a');
		$query->where('a.link = ' . $db->quote('index.php?option=com_sppagebuilder&view=page&id=' . $pageId));
		$query->where('a.client_id = 0');
		$db->setQuery($query);

		return $db->loadObject();
	}

	public function getMenuById($menuId = 0)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select(array('a.*'));
		$query->from('#__menu as a');
		$query->where('a.id = ' . $menuId);
		$query->where('a.client_id = 0');
		$db->setQuery($query);

		return $db->loadObject();
	}

	public function getMenuByAlias($alias, $menuId = 0)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$query->select(array('a.id', 'a.title', 'a.alias', 'a.menutype', 'a.parent_id', 'a.component_id'));
		$query->from('#__menu as a');
		$query->where('a.alias = ' . $db->quote($alias));
		if ($menuId)
		{
			$query->where('a.id != ' . (int) $menuId);
		}
		$query->where('a.client_id = 0');
		$db->setQuery($query);

		return $db->loadObject();
	}

	public function createNewPage($title)
	{
		$user = Factory::getUser();
		$date = Factory::getDate();
		$db = $this->getDbo();
		$page = new stdClass();
		$page->title = $title;
		$page->Text = '[]';
		$page->extension = 'com_sppagebuilder';
		$page->extension_view = 'page';
		$page->published = 1;
		$page->created_by = (int) $user->id;
		$page->created_on = $date->toSql();
		$page->language = '*';
		$page->access = 1;
		$db->insertObject('#__sppagebuilder', $page);

		return $db->insertid();
	}

	public function deletePage($id = 0)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$conditions = array(
			$db->quoteName('id') . ' = ' . $id
		);
		$query->delete($db->quoteName('#__sppagebuilder'));
		$query->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();
		return $result;
	}

	public function getPageItem($id = 0)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('extension', 'extension_view', 'view_id', 'catid'));
		$query->from($db->quoteName('#__sppagebuilder'));
		$query->where($db->quoteName('id') . ' = ' . $db->quote($id));
		$db->setQuery($query);
		$result = $db->loadObject();

		if (count((array) $result))
		{
			return $result;
		}

		return false;
	}

	public function addArticleFullText($id, $data)
	{
		$article = new stdClass();
		$article->id = $id;
		$article->fulltext = SppagebuilderHelperSite::getPrettyText($data);

		Factory::getDbo()->updateObject('#__content', $article, 'id');
	}
	/**
	 * Save Data function
	 *
	 * @param  array $data
	 * @return void
	 * 
	 * @since 4.0.0
	 */
	public function saveData($data, $id)
	{
		$attrKeys = [
			'meta_description',
			'meta_keywords',
			'robots',
			'seo_spacer',
			'og_type',
			'author'
		];

		$popupKeys = [
			'width', 'max_width', 'height', 'max_height', 'position', 'overlay', 'toggle_enter_animation', 'toggle_exit_animation', 'enter_animation', 'enter_animation_duration', 'enter_animation_delay', 'exit_animation', 'exit_animation_duration', 'exit_animation_delay', 'background_type', 'bg_color', 'bg_media', 'bg_gradient', 'bg_media_repeat', 'bg_media_attachment', 'bg_media_position', 'bg_media_size', 'bg_media_overlay', 'bg_media_overlay_blend_mode', 'margin', 'padding', 'border', 'border_radius', 'boxshadow', 'overlay_background_type', 'overlay_bg_color', 'overlay_bg_media', 'overlay_bg_gradient', 'overlay_bg_media_repeat', 'overlay_bg_media_attachment', 'overlay_bg_media_position', 'overlay_bg_media_size', 'overlay_bg_media_overlay', 'overlay_bg_media_overlay_blend_mode', 'close_btn_position', 'close_btn_position_x', 'close_btn_position_y', 'close_btn_color', 'close_btn_bg_color', 'close_btn_color_hover', 'close_btn_bg_color_hover', 'close_btn_padding', 'close_btn_style', 'close_btn_media', 'close_btn_media_height', 'close_btn_media_width', 'close_btn_media_radius', 'close_btn_border_radius', 'close_btn_border', 'close_btn_text', 'close_btn_text_typography', 'close_btn_is_icon', 'close_btn_icon', 'close_btn_icon_color', 'close_btn_icon_size', 'close_btn_width', 'close_btn_height', 'auto_close', 'auto_close_after', 'close_outside_click', 'close_on_esc', 'close_on_click', 'disable_page_scrolling', 'css_class', 'custom_css'
		];

		$menuKeys = [
			'menuid',
			'menutitle',
			'menualias',
			'menutype',
			'menuparent_id',
			'menuordering'
		];


		$attribs = [];

		$existingData = $this->getData($id);


		if (!empty($existingData->extension_view) && $existingData->extension_view === 'popup') {
			$popupTriggerKeys = [
				'visibility',
				'exclude_pages_toggle',
				'exclude_menus_toggle',
				'excluded_pages',
				'excluded_menus',
				'selected_pages',
				'selected_menus',
				'trigger_condition',
				'scroll_direction',
				'scroll_percentage',
				'landing_after',
				'landing_show_after',
				'reappear_after',
				'exit_after',
				'exit_show_after',
				'click_type',
				'click_count',
				'click_area',
				'hover_area',
				'inactivity_duration',
				'reappear_after',
				'date_range'
			];
			
			$attribs = [];
			$savedAttributes = !empty($existingData->attribs) ? json_decode(json_encode($existingData->attribs), true) : [];

			foreach($popupTriggerKeys as $key => $value) {
				if (in_array($value, $popupTriggerKeys)) {
					if ($value !== 'isImported' && isset($savedAttributes[$value])) {
						$attribs[$value] = $savedAttributes[$value];
					}
				}
			}

			foreach($data as $key => $value) {
				if (in_array($key, $popupKeys)) {
					if ($key !== 'isImported') {
						$attribs[$key] = $value;
					}
					unset($data[$key]);
				}
			}
		}

		foreach ($data as $key => $value)
		{
			if (in_array($key, $attrKeys))
			{
				$attribs[$key] = $value;
				unset($data[$key]);
			}

			if (in_array($key, $menuKeys))
			{
				unset($data[$key]);
			}
		}

		$data['attribs'] = json_encode($attribs);

		if (!empty($data['og_image']) && !is_string($data['og_image']))
		{
			$data['og_image'] = json_encode($data['og_image']);
		}

		if (empty($data['catid']))
		{
			$data['catid'] = 0;
		}

		$data['view_id'] = $data['view_id'] ?: 0;
		$data = (object) $data;

		if (empty($id))
		{
			$response['status'] = false;
			$response['message'] = 'No ID Provided!';

			echo json_encode($response);
			die();
		}

		$this->setState('filter.access', $data->access);

		try
		{
			$db = Factory::getDbo();
			$db->updateObject('#__sppagebuilder', $data, 'id', true);

			$response['status'] = true;
			$response['message'] = 'Updated';
		}
		catch (Exception $e)
		{
			$response['status'] = false;
			$response['message'] = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Popup Triggers Save function
	 *
	 * @param  array $data
	 * @return void
	 * 
	 * @since 5.4.0
	 */
	public function savePopupSettingsData($data, $id){

    	$attribs = [];

		$popupKeys = ['visibility',
					  'exclude_pages_toggle',
					  'exclude_menus_toggle',
					  'excluded_pages',
					  'excluded_menus',
					  'selected_pages',
					  'selected_menus',
					  'trigger_condition',
					  'scroll_direction',
					  'scroll_percentage',
					  'landing_after',
					  'landing_show_after',
					  'reappear_after',
					  'exit_after',
					  'exit_show_after',
					  'click_type',
					  'click_count',
					  'click_area',
					  'hover_area',
					  'inactivity_duration',
					  'reappear_after',
					  'date_range'
            ];

		$existingData = $this->getData($id);


		if (!empty($existingData->extension_view) == 'popup') {
			
			$attribs = json_decode(json_encode($existingData->attribs), true);

			foreach($data as $key => $value) {
				if (in_array($key, $popupKeys))
				{
					$attribs[$key] = $value;
				}
			}

			$attributes = json_encode($data);

			$popupType = !empty(json_decode($attributes)->visibility) ? json_decode($attributes)->visibility : null;
			$isExcludedPages = !empty(json_decode($attributes)->exclude_pages_toggle) ? json_decode($attributes)->exclude_pages_toggle : null;
			$isExcludedMenus = !empty(json_decode($attributes)->exclude_menus_toggle) ? json_decode($attributes)->exclude_menus_toggle : null;
			$excludedPages = !empty(json_decode($attributes)->excluded_pages) ? json_decode($attributes)->excluded_pages : null;
			$excludedMenus = !empty(json_decode($attributes)->excluded_menus) ? json_decode($attributes)->excluded_menus : null;
			$selectedPages = !empty(json_decode($attributes)->selected_pages) ? json_decode($attributes)->selected_pages : null;
			$selectedMenus = !empty(json_decode($attributes)->selected_menus) ? json_decode($attributes)->selected_menus : null;

			if ($popupType)
			{
				$data['popup_type'] = $popupType;
			}
			if ($excludedPages)
			{
				$data['excluded_pages'] = $excludedPages;
			}
			if ($excludedMenus)
			{
				$data['excluded_menus'] = $excludedMenus;
			}
			if ($selectedPages)
			{
				$data['selected_pages'] = $selectedPages;
			}
			if ($selectedMenus)
			{
				$data['selected_menus'] = $selectedMenus;
			}
			if ($isExcludedPages)
			{
				$data['is_excluded_pages'] = $isExcludedPages;
			}
			if ($isExcludedMenus)
			{
				$data['is_excluded_menus'] = $isExcludedMenus;
			}
				// $data['id'] = 40;
	
				$popupSettingKeys = [
					'popup_type',
					'is_excluded_pages',
					'is_excluded_menus',
					'excluded_pages',
					'excluded_menus',
					'selected_pages',
					'selected_menus'
				];
	
				$popupSettings = [
					'id' => $id,
				];
				
				foreach ($popupSettingKeys as $popupKey) {
					if (!empty($data[$popupKey]))
					{
						$popupSettings[$popupKey] = $data[$popupKey];
						unset($data[$popupKey]);
					}
				}
	
				$params = ComponentHelper::getParams('com_sppagebuilder');
				
				$componentId = ComponentHelper::getComponent('com_sppagebuilder')->id;
	
				$visibility = $params->get('popup_visibility', null);
				$visibility = !empty($visibility) ? $visibility : null;
				$popupId = $id;
	
	
				if (is_null($visibility)) {
					$visibility = [$popupId => $popupSettings];
				} else {
					$visibility->$popupId = $popupSettings;
				}
	
				$params->set('popup_visibility', $visibility);
				// $params->remove('popup_visibility');
	
				$table = Table::getInstance('extension');
	
				$table->load($componentId);
				$table->params = json_encode($params);
	
				if (!$table->store()) {
					echo "Something went wrong";
					die;
				}

		}
    
    $data = (object) $data;
    $data->attribs = json_encode($attribs);
    $data->id = $id;


    if (empty($id))
		{
			$response['status'] = false;
			$response['message'] = 'No ID Provided!';

			echo json_encode($response);
			die();
		}

    try
		{
			$db = Factory::getDbo();
			$db->updateObject('#__sppagebuilder', $data, 'id', true);

			$response['status'] = true;
			$response['message'] = 'Updated';
		}
		catch (Exception $e)
		{
			$response['status'] = false;
			$response['message'] = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Get Data function
	 *
	 * @param  array $data
	 * @return mixed
	 * 
	 * @since 4.0.0
	 */
	public function getData($id)
	{
		try
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
	
			$query->select('*')
				->from($db->quoteName('#__sppagebuilder'))
				->where($db->quoteName('id') . ' = ' . $id);
	
			$db->setQuery($query);

			$result = $db->loadObject();

			if (!empty($result)) {
				if (!empty($result->attribs) && is_string($result->attribs)) {
					$result->attribs = json_decode($result->attribs);
				}

				$result->dynamic_fields = [];

				if ($result->extension_view === 'dynamic_content:detail' && !empty($result->view_id && $result->view_id !== -2)) {
					$result->dynamic_fields = $this->getCollectionFields($result->view_id);
				} else if ($result->extension_view === 'dynamic_content:detail' && !empty($result->view_id && $result->view_id == -2)) {
					$result->dynamic_fields = $this->getArticleFields();
				}
			}
			
			$result->status = true;
		}
		catch (Exception $error)
		{
			return (object) [
				'status' => false,
				'message' => $error->getMessage(),
			];
		}

		return $result;
	}

	private function getCollectionFields($collectionId)
	{
		if (empty($collectionId)) {
			return [];
		}

		$fields = CollectionField::where('collection_id', $collectionId)
			->whereIn('type', [
				FieldTypes::TITLE,
				FieldTypes::ALIAS,
				FieldTypes::TEXT,
				FieldTypes::IMAGE,
				FieldTypes::RICH_TEXT
			])
			->get();

		$fields = Arr::make($fields)->reduce(function($carry, $field) {
			if ($field->type === FieldTypes::IMAGE) {
				$carry['image'] ??= [];
				$carry['image'][] = ['value' => $field->id, 'label' => '{{' . $field->name . '}}', 'type' => $field->type];
			} else {
				$carry['text'] ??= [];
				$carry['text'][] = ['value' => $field->id, 'label' => '{{' . $field->name . '}}', 'type' => $field->type];
			}

			return $carry;
		}, []);

		return $fields->toArray();
	}

	private function getArticleFields()
    {
        $fields = (new CollectionsService)->fetchArticleFieldsForSeo();
        $fields = array_filter($fields, function ($field) {
            return $field['id'] !== 0;
        });

        $fields = Arr::make($fields)->reduce(function ($carry, $field) {
            if ($field['type'] === FieldTypes::IMAGE) {
                $carry['image'] ??= [];
                $carry['image'][] = ['value' => $field['id'], 'label' => '{{' . $field['name'] . '}}', 'type' => $field['type']];
            } else {
                $carry['text'] ??= [];
                $carry['text'][] = ['value' => $field['id'], 'label' => '{{' . $field['name'] . '}}', 'type' => $field['type']];
            }

            return $carry;
        }, []);

        return $fields->toArray();
    }

	public function getPageCreatorId($pageId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->select('created_by')
			->from($db->quoteName('#__sppagebuilder'))
			->where($db->quoteName('id') . ' = ' . $pageId);

		$db->setQuery($query);

		try
		{
			return $db->loadResult();
		}
		catch (\Exception $e)
		{
			return 0;
		}
	}
}
