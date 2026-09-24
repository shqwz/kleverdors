<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Filesystem\File;
use JoomShaper\SPPageBuilder\DynamicContent\Controllers\CollectionImportExportController;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Trait for managing export layout API endpoint.
 */
trait ExportTrait
{
	public function export()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['GET', 'DELETE', 'PUT', 'PATCH'], $method);

		if ($method === 'POST') {
			$this->exportLayout();
		}
	}

	/**
	 * Recursively checks if dynamic content exists in the data structure
	 *
	 * @param mixed $data The data to check
	 * @param string $key The key to look for (default: 'type')
	 * @param string $value The value to match (default: 'dynamic-content')
	 * @return bool Returns true if dynamic content is found
	 */
	private function checkDynamicContent($data, $key = 'type', $value = 'dynamic-content') 
	{
		if ($data === null) {
			return false;
		}

		if (is_object($data)) {
			$data = (array) $data;
		}

		if (is_array($data)) {
			if (isset($data[$key]) && $data[$key] === $value) {
				return true;
			}

			foreach ($data as $item) {
				if ($this->checkDynamicContent($item, $key, $value)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get dynamic content data if it exists in the page content.
	 *
	 * @return string
	 * @since 5.7.0
	 */
	private function getDynamicContentData($content)
	{
		$hasDynamicContent = false;
		$dynamicContentData = '';

		if (isset($content->content) && is_string($content->content)) {
			$hasDynamicContent = $this->checkDynamicContent(json_decode($content->content));
		}

		if($hasDynamicContent) {
			$dynamicContentExportImportController = new CollectionImportExportController();
			$dynamicContentData = $dynamicContentExportImportController->exportDynamicContent();
		}

		return $dynamicContentData;
	}

	/**
	 * Export template layout.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function exportLayout()
	{
		$user = Factory::getUser();
		$authorised = $user->authorise('core.edit', 'com_sppagebuilder');
		$canEditOwn = $user->authorise('core.edit.own', 'com_sppagebuilder');

		$model = $this->getModel('Editor');

		$pageId = $this->getInput('pageId', '', 'STRING');
		$isSeoChecked = $this->getInput('isSeoChecked', '', 'STRING');
		$isExportMedia = $this->getInput('isExportMedia', '', 'STRING');

		if(empty($pageId)) {
			$this->sendResponse(['message' => 'Page Id missing.'], 400);
		}

		if ($canEditOwn && !empty($pageId)) {
			JLoader::register('SppagebuilderModelPage', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models/page.php');

			$item_info  = SppagebuilderModelPage::getPageInfoById($pageId);

			$canEditOwn = $item_info->created_by == $user->id;
		}

		if (!$authorised && !$canEditOwn) {
			die('Restricted Access');
		}

		$content = $model->getPageContent($pageId);

		if (empty($content)) {
			$this->sendResponse(['message' => 'Requesting page not found!'], 404);
		}

		$localMediaSources = [];

		if($isExportMedia){
			$contentParsed = json_decode($content->content, true);

			if($isSeoChecked){
				$ogImageSrc = $content->og_image->src ?? '';
				array_push($contentParsed, ['src' => $ogImageSrc]);
			}

			$localMediaSources = $this->extractSrcValues($contentParsed);
			$this->exportWithMedia($localMediaSources, $content, $isSeoChecked);
			die();
		}

		$content = ApplicationHelper::preparePageData($content);

		$dynamicContentData = $this->getDynamicContentData($content);

		$seoSettings = [];

		if ($isSeoChecked) {
			$seoSettings = [
				'og_description' => isset($content->og_description) ? $content->og_description : '',
				'og_image' => isset($content->og_image) ? $content->og_image : '',
				'og_title' => isset($content->og_title) ? $content->og_title : '',
				'meta_description' => isset($content->attribs) && isset($content->attribs->meta_description) ?  $content->attribs->meta_description : '',
				'meta_keywords' => isset($content->attribs) && isset($content->attribs->meta_keywords) ?  $content->attribs->meta_keywords : '',
				'og_type' => isset($content->attribs) && isset($content->attribs->og_type) ?  $content->attribs->og_type : '',
				'robots' => isset($content->attribs) && isset($content->attribs->robots) ?  $content->attribs->robots : '',
				'seo_spacer' => isset($content->attribs) && isset($content->attribs->seo_spacer) ?  $content->attribs->seo_spacer : '',
				'author' => isset($content->attribs) && isset($content->attribs->author) ?  $content->attribs->author : '',
			];
		}

		$pageContent = (object) [
			'template' => isset($content->content) ? $content->content : $content->text,
			'css' => isset($content->css) ? $content->css : '',
			'seo' => json_encode($seoSettings),
			'title' => $content->title,
			'language' => isset($content->language) ? $content->language : '*',
		];

		if (!empty($dynamicContentData)) {
			$pageContent->dynamicContentData = json_encode($dynamicContentData);
		}

		if(isset($content->extension_view)) {
			switch ($content->extension_view) {
				case Page::PAGE_TYPE_POPUP:
					$pageContent->attribs = isset($content->attribs) ? json_encode($content->attribs) : '';
					$pageContent->type = Page::PAGE_TYPE_POPUP;
					break;
				case Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL:
					$pageContent->type = Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
					$pageContent->view_id = isset($content->view_id) ? (string)$content->view_id : '';
					break;
				case Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX:
					$pageContent->type = Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;
					$pageContent->view_id = isset($content->view_id) ? (string)$content->view_id : '';
					break;
			}
		}


		$this->sendResponse($pageContent);
	}

	private function extractSrcValues($data)
	{
		$accepted_file_formats = array(
			'image' => array('jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif'),
			'video' => array('mp4', 'mov', 'wmv', 'avi', 'mpg', 'ogv', '3gp', '3g2'),
			'audio' => array('mp3', 'm4a', 'ogg', 'wav'),
			'attachment' => array('pdf', 'doc', 'docx', 'key', 'ppt', 'pptx', 'pps', 'ppsx', 'odt', 'xls', 'xlsx', 'zip', 'json'),
		);
		
		$srcValues = [];
		
		foreach ($data as $key => $value)
		{
			if (is_array($value))
			{
				$srcValues = array_merge($srcValues, $this->extractSrcValues($value));
			}
			elseif ($key === 'src' && !empty($value))
			{
				$path_info = pathinfo($value);
				$extension = isset($path_info['extension']) ? strtolower($path_info['extension']) : '';
				
				foreach ($accepted_file_formats as $formats)
				{
					if (in_array($extension, $formats))
					{
						if (!preg_match('/^(http:\/\/|https:\/\/|\/\/)/i', $value)) {
							$srcValues[] = $value;
						}
						break;
					}
				}
			}
		}

		return $srcValues;
	}

	private function exportWithMedia($localMediaSources, $pageData, $isSeoChecked)
	{
		$config = ApplicationHelper::getAppConfig();
		$mediaSources = [];
		$content = ApplicationHelper::preparePageData($pageData);

		foreach ($localMediaSources as $source)
		{
			$sourcePath = Uri::root() . $source;
			array_push($mediaSources, $sourcePath);
		}

		$seoSettings = [];

		if ($isSeoChecked) 
		{
			$seoSettings = 
			[
				'og_description' => isset($content->og_description) ? $content->og_description : '',
				'og_image' => isset($content->og_image) ? $content->og_image : '',
				'og_title' => isset($content->og_title) ? $content->og_title : '',
				'meta_description' => isset($content->attribs) && isset($content->attribs->meta_description) ?  $content->attribs->meta_description : '',
				'meta_keywords' => isset($content->attribs) && isset($content->attribs->meta_keywords) ?  $content->attribs->meta_keywords : '',
				'og_type' => isset($content->attribs) && isset($content->attribs->og_type) ?  $content->attribs->og_type : '',
				'robots' => isset($content->attribs) && isset($content->attribs->robots) ?  $content->attribs->robots : '',
				'seo_spacer' => isset($content->attribs) && isset($content->attribs->seo_spacer) ?  $content->attribs->seo_spacer : '',
				'author' => isset($content->attribs) && isset($content->attribs->author) ?  $content->attribs->author : '',
			];
		}

		$dynamicContentData = $this->getDynamicContentData($content);
	
		$pageContent = (object)
		[
			'template' => isset($content->content) ? $content->content : $content->text,
			'css' => isset($content->css) ? $content->css : '',
			'title' => $content->title,
			'seo' => json_encode($seoSettings),
			'language' => isset($content->language) ? $content->language : '*',
			'localMediaSources' => $localMediaSources ? $localMediaSources : '[]',
		];

		if (!empty($dynamicContentData)) {
			$pageContent->dynamicContentData = json_encode($dynamicContentData);
		}

		if(isset($content->extension_view)) {
			switch ($content->extension_view) {
				case Page::PAGE_TYPE_POPUP:
					$pageContent->attribs = isset($content->attribs) ? json_encode($content->attribs) : '';
					$pageContent->type = Page::PAGE_TYPE_POPUP;
					break;
				case Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL:
					$pageContent->type = Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
					$pageContent->view_id = isset($content->view_id) ? (string)$content->view_id : '';
					break;
				case Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX:
					$pageContent->type = Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;
					$pageContent->view_id = isset($content->view_id) ? (string)$content->view_id : '';
					break;
			}
		}
		
		$zip = new ZipArchive();
		$zipFileName = 'sp-page-builder-pages-' . $this->generateRandomId() . '.zip';
		$tmpPath = $config->get('tmp_path');
		$zipFilePath = $tmpPath . '/' . $zipFileName;
		$mediaTempDir = $tmpPath . '/media_' . $this->generateRandomId();
		$exportDir = $tmpPath . '/export_' . $this->generateRandomId();
		$parentDir = $exportDir . '/page_'. $content->title . '_' . $this->generateRandomId();
		
		if ((!Folder::create($mediaTempDir, 0755)) ||
    	(!Folder::create($exportDir, 0755)) ||
    	(!Folder::create($parentDir, 0755)))
		{
			$this->sendResponse([
				'message' => 'Failed to create necessary directories',
			], 500);
		}

		$options = new \Joomla\Registry\Registry;
		$http = HttpFactory::getHttp($options);
	
		foreach ($mediaSources as $source)
		{
			$encodedBaseName = rawurlencode(basename($source));
			$destination = $mediaTempDir . '/' . $encodedBaseName;

			try {
				$encodedUrl = dirname($source) . '/' . $encodedBaseName;
				$response = $http->get($encodedUrl);

				if ($response->code === 200) {
					File::write($destination, $response->body);
				} else {
					$this->sendResponse([
						'message' => 'Failed to copy media file: ' . $source,
						'error' => 'HTTP response code: ' . $response->code
					], 500);
				}
			} catch (Exception $e) {
				$this->sendResponse([
					'message' => 'Failed to copy media file: ' . $source,
					'error' => $e->getMessage()
				], 500);
			}
		}
	
		$stringContent = json_encode($pageContent);
		$fileName = $content->title . '_' . $this->generateRandomId() . '.json';
		file_put_contents($parentDir . '/' . $fileName, $stringContent);
	
		rename($mediaTempDir, $parentDir . '/media');
	
		if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE)
		{
			try {
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($exportDir),
					RecursiveIteratorIterator::LEAVES_ONLY
				);
	
				foreach ($files as $name => $file)
				{
					if (!$file->isDir())
					{
						$filePath = $file->getRealPath();
						$relativePath = substr($filePath, strlen($exportDir) + 1);
						$zip->addFile($filePath, $relativePath);
					}
				}
			} catch (\Throwable $th)
			{
				$this->sendResponse([
					'message' => 'Failed to add file to the ZIP archive',
				], 500);
			}
		}
	
		$zip->close();
	
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/zip");
		header('Content-Disposition: attachment; filename="' . basename($zipFilePath) . '"');
		header('Content-Length: ' . filesize($zipFilePath));
	
		echo file_get_contents($zipFilePath);

		if (is_file($zipFilePath))
		{
			unlink($zipFilePath);
		}
		
		if (is_dir($exportDir))
		{
			Folder::delete($exportDir);
		}
	
		die();
	}
}
