<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Controllers\CollectionImportExportController;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Trait for managing bulk export API endpoint.
 */
trait BulkExportTrait
{
    public function bulkExport()
    {
        $method = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'DELETE', 'PUT', 'PATCH'], $method);

        if ($method === 'POST') {
            $this->exportBulk();
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
	private function checkDynamicContentData($data, $key = 'type', $value = 'dynamic-content') 
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
				if ($this->checkDynamicContentData($item, $key, $value)) {
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
	private function getDynamicContentExportData($content)
	{
		$hasDynamicContent = false;
		$dynamicContentData = '';

		if (isset($content->content) && is_string($content->content)) {
			$hasDynamicContent = $this->checkDynamicContentData(json_decode($content->content));
		}

		if($hasDynamicContent) {
			$dynamicContentExportImportController = new CollectionImportExportController();
			$dynamicContentData = $dynamicContentExportImportController->exportDynamicContent();
		}

		return $dynamicContentData;
	}

    /**
     * Bulk export pages.
     *
     * @return void
     * @since 5.2.10
     */
    public function exportBulk()
    {
        $config = ApplicationHelper::getAppConfig();
        $user = Factory::getUser();
        $authorised = $user->authorise('core.edit', 'com_sppagebuilder');
        $canEditOwn = $user->authorise('core.edit.own', 'com_sppagebuilder');

        $pageIds = $this->getInput('pageIds', '', 'STRING');
        $isSeoChecked = $this->getInput('isSeoChecked', '', 'STRING');
        $isExportMedia = $this->getInput('isMediaChecked', '', 'STRING');

        if (empty($pageIds)) {
            $this->sendResponse(['message' => 'Page Ids missing.'], 400);
        }

        $pageIdsArray = explode(',', $pageIds);

        $pageContents = new stdClass;
        $model = $this->getModel('Editor');
        

        if($isExportMedia){
            $this->exportBulkWithMedia($pageIdsArray, $isSeoChecked, $model, $user, $authorised, $canEditOwn, $config);
            die();
        }

        foreach ($pageIdsArray as $pageId) {
            $trimmedPageId = trim($pageId);
            
            if (!$trimmedPageId) {
                continue;
            }

            if ($canEditOwn && !empty($trimmedPageId)) {
                JLoader::register('SppagebuilderModelPage', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models/page.php');

                $item_info  = SppagebuilderModelPage::getPageInfoById($trimmedPageId);
                $canEditOwn = $item_info->created_by == $user->id;
            }

            if (!$authorised && !$canEditOwn) {
                die('Restricted Access');
            }

            $content = $model->getPageContent($trimmedPageId);

            if (empty($content)) {
                $this->sendResponse(['message' => 'Requesting page not found!'], 404);
            }

            $content = ApplicationHelper::preparePageData($content);

            $dynamicContentData = $this->getDynamicContentExportData($content);

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

            if(isset($content->extension_view)){
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

            $title = $pageId . '_' . $this->generateRandomId() . '.json';
            $pageContents->$title = $pageContent;
        }

        $zip = new ZipArchive();
        $zipFileName = 'sp-page-builder-pages-' . $this->generateRandomId() . '.zip';
        $tmpPath = $config->get('tmp_path');
        $zipFilePath = $tmpPath . '/' . $zipFileName;

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($pageContents as $fileName => $content) {
                $stringContent = json_encode($content);

                try {
                    $zip->addFromString($fileName, $stringContent);
                } catch (\Throwable $th) {
                    $this->sendResponse([
                        'message' => 'Failed to add file to the ZIP archive',
                    ], 500);
                }
            }

            $zip->close();

            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");
            header('Content-Disposition: attachment; filename="' . basename($zipFilePath) . '"');
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: binary ");
            header('Content-Length: ' . filesize($zipFilePath));

            echo file_get_contents($zipFilePath);
            unlink($zipFilePath);

            die();
        }

        $this->sendResponse([
            'message' => 'Failed to create zip file',
        ], 500);
    }

    function generateRandomId($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $randomId = '';
        $maxIndex = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomId .= $characters[mt_rand(0, $maxIndex)];
        }

        return $randomId;
    }

    private function exportBulkWithMedia($pageIdsArray, $isSeoChecked, $model, $user, $authorised, $canEditOwn, $config)
{
    $zip = new ZipArchive();
    $zipFileName = 'sp-page-builder-pages-' . $this->generateRandomId() . '.zip';
    $tmpPath = $config->get('tmp_path');
    $zipFilePath = $tmpPath . '/' . $zipFileName;
    $commonExportDir = $tmpPath . '/common_export_' . $this->generateRandomId();

    if ((!Folder::create($commonExportDir, 0755))){
        $this->sendResponse([
            'message' => 'Failed to create common export directory',
        ], 500);
    }

    foreach ($pageIdsArray as $pageId)
    {
        $trimmedPageId = trim($pageId);

        if (!$trimmedPageId)
        {
            continue;
        }

        if ($canEditOwn && !empty($trimmedPageId))
        {
            JLoader::register('SppagebuilderModelPage', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models/page.php');

            $item_info  = SppagebuilderModelPage::getPageInfoById($trimmedPageId);
            $canEditOwn = $item_info->created_by == $user->id;
        }

        if (!$authorised && !$canEditOwn)
        {
            die('Restricted Access');
        }

        $content = $model->getPageContent($trimmedPageId);

        if (empty($content))
        {
            $this->sendResponse(['message' => 'Requesting page not found!'], 404);
        }
        
        $fieldContent = isset($content->content) ? $content->content : $content->text;
        $contentParsed = [];

        if(!empty($fieldContent) && is_string($fieldContent)){
            $contentParsed = json_decode($fieldContent, true);
        }

        if($isSeoChecked){
            $ogImageSrc = $content->og_image->src ?? '';
            array_push($contentParsed, ['src' => $ogImageSrc]);
        }
        
        $localMediaSources = $this->getSrcValues($contentParsed);
        $mediaSources = [];
        $content = ApplicationHelper::preparePageData($content);

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

        $dynamicContentData = $this->getDynamicContentExportData($content);

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

        if(isset($content->extension_view)){
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

        $mediaTempDir = $commonExportDir . '/media_' . $this->generateRandomId();
        $parentDir = $commonExportDir . '/page_'. $content->title . '_' . $this->generateRandomId();

        if ((!Folder::create($mediaTempDir, 0755)) ||
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
                
                $options = new \Joomla\Registry\Registry;
                $http = HttpFactory::getHttp($options);
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
        $fileName = $pageId . '_' . $this->generateRandomId() . '.json';
        file_put_contents($parentDir . '/' . $fileName, $stringContent);

        rename($mediaTempDir, $parentDir . '/media');
    }

    if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE)
    {
        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($commonExportDir),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $name => $file)
            {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($commonExportDir) + 1);

                if ($file->isDir())
                {
                    $zip->addEmptyDir($relativePath);
                }
                else
                {
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

    Folder::delete($commonExportDir);

    die();
}

private function getSrcValues($data)
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
                $srcValues = array_merge($srcValues, $this->getSrcValues($value));
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
}
