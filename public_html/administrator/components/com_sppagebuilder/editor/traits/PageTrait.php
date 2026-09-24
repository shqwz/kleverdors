<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Trait for managing page list
 */
trait PageTrait
{
	public function pages()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['DELETE'], $method);

		switch ($method)
		{
			case 'GET':
				$this->getPageList();
				break;
			case 'PUT':
				$this->savePage();
				break;
			case 'PATCH':
				$this->applyBulkActions();
				break;
			case 'POST':
				$this->createPage();
				break;
		}
	}

	public function getPageList()
	{
		$pageData = (object) [
			'limit' => $this->getInput('limit', 10, 'INT'),
			'offset' => $this->getInput('offset', 0, 'INT'),
			'search' => $this->getInput('search', '', 'STRING'),
			'sortBy' => $this->getInput('sortBy', '', 'STRING'),
			'access' => $this->getInput('access', '', 'STRING'),
			'category' => $this->getInput('category', 0, 'INT'),
			'language' => $this->getInput('language', '', 'STRING'),
			'status' => $this->getInput('status', '', 'STRING'),
			'type' => $this->getInput('type', '', 'STRING'),
			'extension' => 'com_sppagebuilder',
			'extension_view' => $this->getInput('extension_view', Page::PAGE_TYPE_REGULAR, 'STRING'),
			'page_types' => [
				Page::PAGE_TYPE_REGULAR,
				Page::PAGE_TYPE_POPUP,
				Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX,
				Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL,
			],
		];

		$model = $this->getModel('Editor');
		$response = $model->getPages($pageData);

		if (is_array($response->results) && count($response->results) > 0)
		{
			foreach ($response->results as $key => $page)
			{
				$page->permissions = $this->getPagePermissions($page->id);
			}
		}

		$this->sendResponse($response, $response->code);
	}

	public function getPagePermissions($pageId)
	{
		$model = $this->getModel('Appconfig');

		return $model->getUserPermissions($pageId);
	}

	public function savePage()
	{
		$model = $this->getModel('Editor');

		$id = $this->getInput('id', 0, 'INT');
		$title = $this->getInput('title', '', 'STRING');
		$text = $this->getInput('text', '[]', 'RAW');
		$published = $this->getInput('published', 0, 'INT');
		$language = $this->getInput('language', '*', 'STRING');
		$catid = $this->getInput('catid', 0, 'INT');
		$view_id = $this->getInput('view_id', 0, 'INT');
		$extension_view = $this->getInput('extension_view', '', 'STRING');
		$access = $this->getInput('access', 1, 'INT');
		$attributes = $this->getInput('attribs', '', 'STRING');
		$openGraphTitle = $this->getInput('og_title', '', 'STRING');
		$openGraphDescription = $this->getInput('og_description', '', 'STRING');
		$openGraphImage = $this->getInput('og_image', '', 'STRING');
		$customCss = $this->getInput('css', '', 'RAW');
		$popupType = !empty(json_decode($attributes)->visibility) ? json_decode($attributes)->visibility : null;
		$isExcludedPages = !empty(json_decode($attributes)->exclude_pages_toggle) ? json_decode($attributes)->exclude_pages_toggle : null;
		$isExcludedMenus = !empty(json_decode($attributes)->exclude_menus_toggle) ? json_decode($attributes)->exclude_menus_toggle : null;
		$excludedPages = !empty(json_decode($attributes)->excluded_pages) ? json_decode($attributes)->excluded_pages : null;
		$excludedMenus = !empty(json_decode($attributes)->excluded_menus) ? json_decode($attributes)->excluded_menus : null;
		$selectedPages = !empty(json_decode($attributes)->selected_pages) ? json_decode($attributes)->selected_pages : null;
		$selectedMenus = !empty(json_decode($attributes)->selected_menus) ? json_decode($attributes)->selected_menus : null;
		$version = SppagebuilderHelper::getVersion();

		$pageCreator = $model->getPageCreator($id);

		$user = Factory::getUser();
		$canEdit = $user->authorise('core.edit', 'com_sppagebuilder');
		$canEditOwn = $user->authorise('core.edit.own', 'com_sppagebuilder');
		$canEditState = $user->authorise('core.edit.state', 'com_sppagebuilder');

		$canEditPage = $canEdit || ($canEditOwn && $user->id === $pageCreator);

		if (!$canEditPage)
		{
			$this->sendResponse(['message' => Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_EDIT_ACCESS')], 403);
		}

		$content = !empty($text) ? $text : '[]';
		$content = json_encode(json_decode($content));
		$isDetailsPage = $extension_view === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
		$isIndexPage = $extension_view === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;

		$data = [
			'id' => $id,
			'title' => $title,
			// 'content' => !empty($text) ? EditorUtils::cleanXSS($text) : '[]',
			'content' => $content,
			'published' => $published,
			'language' => $language,
			'catid' => $catid,
			'access' => $access,
			'attribs' => $attributes,
			'og_title' => $openGraphTitle,
			'og_description' => $openGraphDescription,
			'og_image' => $openGraphImage,
			'css' => $customCss ?? '',
			'version' => $version,
			'modified' => Factory::getDate()->toSql(),
			'modified_by' => $user->id,
		];

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
		if (isset($view_id) && !empty($view_id) && ($isDetailsPage || $isIndexPage))
		{
			$data['view_id'] = $view_id;
		}

		if (!$canEditState)
		{
			unset($data['published']);
		}

		try
		{
			$model->savePage($data);
		}
		catch (Exception $error)
		{
			$this->sendResponse(['message' => $error->getMessage()], 500);
		}

		$this->sendResponse($id);
	}

	public function applyBulkActions()
	{
		$params = (object) [
			'ids' => $this->getInput('ids', '', 'STRING'),
			'type' => $this->getInput('type', '', 'STRING'),
			'value' => $this->getInput('value', '', 'STRING')
		];

		$user = Factory::getUser();
		$canEditState = $user->authorise('core.edit.state', 'com_sppagebuilder');
		$canDelete = $user->authorise('core.delete', 'com_sppagebuilder');

		$stateTypes = ['published', 'unpublished', 'check-in', 'rename'];
		$deleteTypes = ['trash', 'delete'];

		if (in_array($params->type, $stateTypes) && !$canEditState)
		{
			$this->sendResponse(['message' => Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_EDIT_STATE_ACCESS')], 403);
		}

		if (in_array($params->type, $deleteTypes) && !$canDelete)
		{
			$this->sendResponse(['message' => Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_DELETE_STATE')], 403);
		}

		$model = $this->getModel('Editor');
		$response = $model->applyBulkActions($params);

		$this->sendResponse($response);
	}

	public function createPage()
	{
		$title = $this->getInput('title', '', 'STRING');
		$type = $this->getInput('type', '', 'STRING');
		$pageType = $this->getInput('page_type', 'page', 'STRING');
		$collectionId = $this->getInput('collection_id', 0, 'INT');

		$model = $this->getModel('Editor');
		$data = [];
		$user = Factory::getUser();
		$version = SppagebuilderHelper::getVersion();

		$user = Factory::getUser();
		$canCreate = $user->authorise('core.create', 'com_sppagebuilder');

		if (!$canCreate)
		{
			$this->sendResponse([
				'message' => Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_CREATE_ACCESS')
			], 403);
		}

		$extension = 'com_sppagebuilder';
		$extensionView = $pageType;
		$viewId = $collectionId;

		if (!empty($type))
		{
			if (in_array($type, ['single', 'storefront', 'collection'], true))
			{
				$extension = 'com_easystore';
				$extensionView = explode('-', $type)[0];

				$title = ucwords(str_replace('-', ' ', $type));

				if ($pageId = $this->isStorePageExist($extension, $extensionView))
				{
					$this->sendResponse(['id' => $pageId], 200);
				}
			}
			elseif ($type === 'popup')
			{
				$extension = 'com_sppagebuilder';
				$extensionView = 'popup';
			}
		}

		$data = [
			'id' => 0,
			'title' => $title,
			'text' => '[]',
			'css' => '',
			'catid' => 0,
			'language' => '*',
			'access' => 1,
			'published' => 1,
			'extension' => $extension,
			'extension_view' => $extensionView,
			'view_id' => $viewId,
			'created_on' => Factory::getDate()->toSql(),
			'created_by' => $user->id,
			'modified' => Factory::getDate()->toSql(),
			'version' => $version,
		];

		$result = $model->createPage($data);

		if (!empty($result['message']))
		{
			$this->sendResponse($result, 500);
		}

		$response = (object) [
			'id' => $result
		];

		$this->sendResponse($response, 201);
	}

	public function isStorePageExist($extension, $view)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id')
			->from($db->quoteName('#__sppagebuilder'))
			->where($db->quoteName('extension') . ' = ' . $db->quote($extension))
			->where($db->quoteName('extension_view') . ' = ' . $db->quote($view));
		$db->setQuery($query);

		try
		{
			return $db->loadResult();
		}
		catch (Exception $error)
		{
			return false;
		}

		return false;
	}

	public function previewUrl()
	{
		$pageId = $this->getInput('id', 0, 'INT');
		$model = $this->getModel('Editor');
		$content = $model->getPageContent($pageId);
		$response = $model->getPreviewUrl($pageId, $content->language);
		$this->sendResponse($response);
	}

	public function pagePadlock(){
		$method = $this->getInputMethod();

		if($method !== 'PATCH'){
			$this->sendResponse(['message' => 'Invalid request method'], 405);
		}

		$pageId = $this->getInput('id', 0, 'INT');
		$action = $this->getInput('action', '', 'STRING');
		$model = $this->getModel('Editor');

		if ($action === 'check-in') {
			$response = $model->checkInPage($pageId);
			$this->sendResponse($response);
		} elseif ($action === 'check-out') {
			$response = $model->checkOutPage($pageId);
			$this->sendResponse($response);
		} else {
			$this->sendResponse(['message' => 'Invalid action'], 400);
		}

	}
}
