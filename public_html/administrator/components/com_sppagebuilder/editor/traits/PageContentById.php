<?php

use JoomShaper\SPPageBuilder\DynamicContent\Constants\ArticleLayouts;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;

/**
 * Sample trait for managing API endpoints.
 */
trait PageContentById
{
	public function pageContentById()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'PUT', 'DELETE', 'PATCH'], $method);

		$this->getPageContentById();
	}

	/**
	 * @return void
	 * @since 5.4.0
	 */
	public function getPageContentById()
	{
		$id = $this->getInput('id', null, 'INT');
		

		$model = $this->getModel('Editor');

		if (!$id)
		{
			$response['message'] = 'Missing Page ID';
			$this->sendResponse($response, 400);
		}

		$content = $model->getPageContent($id);

		if (empty($content))
		{
			$this->sendResponse(['message' => 'Requesting page not found!'], 404);
		}

		$content = ApplicationHelper::preparePageData($content);

		$type = '';

		if (!empty($content->extension_view) && $content->extension_view === 'popup') {
			$type = 'popup';
		}

		$isPopup = false;

		if ($type === 'popup') {
			$isPopup = true;
		}

		$easyStoreRouteType = $content->extension === 'com_easystore' ? $content->extension_view : null;

		$content->url = SppagebuilderHelperRoute::getFormRoute($content->id, $content->language, 0, $easyStoreRouteType, $isPopup);

		unset($content->content);

		if (!empty($content->view_id) && $content->view_id === CollectionIds::ARTICLES_COLLECTION_ID && !empty($content->extension_view) && $content->extension_view === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX && empty($content->text)) {
			$content->text = json_decode(ArticleLayouts::DEFAULT_LAYOUT_ARTICLE_INDEX);
		} else if (!empty($content->view_id) && $content->view_id === CollectionIds::ARTICLES_COLLECTION_ID && !empty($content->extension_view) && $content->extension_view === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL && empty($content->text)) {
			$content->text = json_decode(ArticleLayouts::DEFAULT_LAYOUT_ARTICLE_DETAILS);
		}

		$this->sendResponse($content);
	}
}
