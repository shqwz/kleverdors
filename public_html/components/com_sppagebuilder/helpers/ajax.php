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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use JoomShaper\SPPageBuilder\DynamicContent\Controllers\CollectionImportExportController;

$productListSeed = JPATH_ROOT . '/components/com_easystore/assets/product-list-seed.json';

$cParams = ComponentHelper::getParams('com_sppagebuilder');

$app = Factory::getApplication();
$input = $app->input;
$http = new Http;

// Load Page Template List
if ($action === 'pre-page-list')
{

	$cache_path = JPATH_CACHE . '/sppagebuilder';
	$cache_file = $cache_path . '/templates.json';

	$output = array('status' => false, 'data' => 'Templates not found.');
	$templates = array(); // All pre-defined templates list
	$templatesData = '';

	if (!is_dir($cache_path))
	{
		Folder::create($cache_path, 0755);
	}

	if (file_exists($cache_file) && (filemtime($cache_file) > (time()  - (24 * 60 * 60))))
	{
		$templatesData = file_get_contents($cache_file);
	}
	else
	{
		$templateApi = 'https://www.joomshaper.com/index.php?option=com_layouts&view=templates&layout=json&support=4beyond';
		$templatesResponse = $http->get($templateApi);
		$templatesData = $templatesResponse->body;

		if ($templatesResponse->code !== 200)
		{
			$output = ['status' => false, 'data' => $templatesData->error->message];
		}

		if (!empty($templatesData))
		{
			File::write($cache_file, $templatesData);
		}
	}

	if (!empty($templatesData))
	{
		$templates = json_decode($templatesData);
		$pages = [];

		foreach ($templates as $template)
		{
			if (!empty($template->templates))
			{
				foreach ($template->templates as $item)
				{
					if (!empty($item->layouts))
					{
						foreach ($item->layouts as $layout)
						{
							$key = strtolower($layout->title);
							$pages[$key] = (object) [
								'label' => $layout->title,
								'value' => $key
							];
						}
					}
				}
			}
		}

		if (!empty($templates))
		{
			$response = [
				'status' => true,
				'data' => [
					'pages' => array_values($pages),
					'layouts' => $templates
				]
			];

			echo json_encode($response);
			die();
		}
	}

	echo json_encode($output);
	die();
}

if ($action === 'popup-layouts'){
	$cache_path = JPATH_CACHE . '/sppagebuilder';
	$cache_file = $cache_path . '/popup_layouts.json';

	$output = array('status' => false, 'data' => Text::_('COM_SPPAGEBUILDER_ERROR_POPUP_LAYOUTS_NOT_FOUND'));
	$layouts = array();
	$layoutsData = '';
	

	if (!is_dir($cache_path))
	{
		
		Folder::create($cache_path, 0755);
	}

	if (file_exists($cache_file) && (filemtime($cache_file) > (time()  - (24 * 60 * 60))))
	{
		
		$layoutsData = json_decode(file_get_contents($cache_file));
	}
	
	else
	{	
		$layoutApi = 'https://www.joomshaper.com/index.php?option=com_layouts&view=popups';

		$layoutResponse = $http->get($layoutApi);
		$layoutsData = json_decode($layoutResponse->body);

		if ($layoutResponse->code !== 200)
		{
			$output = ['status' => false, 'data' => $layoutsData->error->message];
		}

		if (!empty($layoutsData))
		{
			File::write($cache_file, json_encode($layoutsData));
		}
	}

	
	if(!empty($layoutsData)){
		
		$response = [
			'status' => true,
			'data' => json_encode($layoutsData),
		];
		
		echo json_encode($response);
		die();
	}
}

// Load Page Template List
if ($action === 'get-pre-page-data')
{
	$layout_id = $input->post->get('layout_id', '', 'NUMBER');
	$output = array('status' => false, 'data' => 'Page not found.');
	$args = '&email=' . urlencode($cParams->get('joomshaper_email')) . '&api_key=' . $cParams->get('joomshaper_license_key');
	$pageApi = 'https://www.joomshaper.com/index.php?option=com_layouts&task=template.download&support=4beyond&id=' . $layout_id . $args;

	$pageResponse = $http->get($pageApi);
	$pageData = $pageResponse->body;

	if ($pageResponse->code !== 200)
	{
		$output = ['status' => false, 'data' => $pageData->error->message];
	}

	if (!empty($pageData))
	{
		$pageData = json_decode($pageData);

		if (isset($pageData->status) && $pageData->status)
		{
			$output['status'] = true;
			$output['data'] = $pageData->content;
			echo json_encode($output);
			die();
		}
		elseif (isset($pageData->authorised))
		{
			$output['status'] = false;
			$output['data'] = $pageData->authorised;
			echo json_encode($output);
			die();
		}
	}

	echo json_encode($output);
	die();
}

if ($action === 'pre-section-list')
{

	$cache_path = JPATH_CACHE . '/sppagebuilder';
	$cache_file = $cache_path . '/sections.json';

	$output = array('status' => false, 'data' => 'Sections not found.');
	$sections = array(); // All pre-defined templates list
	$sectionsData = '';

	if (!is_dir($cache_path))
	{
		Folder::create($cache_path, 0755);
	}

	if (file_exists($cache_file) && (filemtime($cache_file) > (time()  - (24 * 60 * 60))))
	{
		$sectionsData = file_get_contents($cache_file);
	}
	else
	{
		$args = '&email=' . urlencode($cParams->get('joomshaper_email')) . '&api_key=' . $cParams->get('joomshaper_license_key');
		$sectionApi = 'https://www.joomshaper.com/index.php?option=com_layouts&task=block.list&support=4beyond' . $args;

		$sectionResponse = $http->get($sectionApi);
		$sectionsData = $sectionResponse->body;

		if ($sectionResponse->code !== 200)
		{
			$output = ['status' => false, 'data' => $sectionsData->error->message];
		}

		if (!empty($sectionsData))
		{
			File::write($cache_file, $sectionsData);
		}
	}

	if (!empty($sectionsData))
	{
		$sections = json_decode($sectionsData);

		/** Sanitize the blocks data before sending. */
		if (!empty($sections->blocks))
		{
			foreach ($sections->blocks as $i => &$groups)
			{
				if (!empty($groups->blocks))
				{
					foreach ($groups->blocks as $j => &$block)
					{
						if (!empty($block->json))
						{
							$content = json_decode($block->json);

							if (\is_object($content))
							{
								$content = json_encode([$content]);
							}
							elseif (\is_array($content))
							{
								$content = json_encode($content);
							}

							$json = SppagebuilderHelperSite::sanitize($content);
							// $parse = json_decode($json);

							// if (\is_array($parse) && !empty($parse))
							// {
							// 	$json = json_encode($parse[0]);
							// }

							$block->json = $json;
						}
					}

					unset($block);
				}
			}

			unset($groups);
		}

		if ((is_array($sections) && count($sections)) || is_object($sections))
		{
			$output['status'] = true;
			$output['data'] = $sections;
			echo json_encode($output);
			die();
		}
	}

	echo json_encode($output);
	die();
}

// Load page from uploaded page
if ($action === 'upload-page')
{
	if (isset($_FILES['page']) && $_FILES['page']['error'] === 0)
	{
		$file_name = $_FILES['page']['name'];
		$fileExtensionExploded = explode('.', $file_name);
		$fileExtension = ($fileExtensionExploded)[count($fileExtensionExploded) - 1];
		$file_extension_lower = strtolower($fileExtension);

		if ($file_extension_lower === 'json')
		{
			$content = file_get_contents($_FILES['page']['tmp_name']);

			$importingContent = (object)['template' => '', 'css' => '', 'seo' => ''];
			$updatedFieldIds = [];
			$updatedCollectionIds = [];

			if (!empty($content))
			{
				$parsedContent = json_decode($content);

				if(isset($parsedContent->dynamicContentData) && !empty($parsedContent->dynamicContentData))
				{
					$updatedData = importDynamicContentData($parsedContent->dynamicContentData);
					$updatedFieldIds = $updatedData['globalFieldsMap'] ?? [];
					$updatedCollectionIds = $updatedData['globalCollectionsIdMap'] ?? [];
				}

				if (!isset($parsedContent->template))
				{
					$importingContent->template = json_decode($content);
				}
				else
				{
					$importingContent = $parsedContent;
				}
			}

			if (!empty($importingContent))
			{
				require_once JPATH_COMPONENT_SITE . '/builder/classes/addon.php';
				$content = ApplicationHelper::sanitizePageText(json_encode($importingContent->template));
				$content = updateDynamicIds($content, $updatedFieldIds, $updatedCollectionIds);

				if($content !== "[]") {
					$content = json_encode($content);
				}

				/** Sanitize the old data with new data format. */
				$importingContent->template = SppagebuilderHelperSite::sanitizeImportJSON($content);

				echo json_encode(array('status' => true, 'data' => $importingContent));
				die;
			}
		}
		else if ($file_extension_lower === 'zip')
		{
			$file = Factory::getApplication()->input->files->get('page');
			importLayoutWithMedia(
				$file['tmp_name'],
				$file['name']
			);
		}
	}

	echo json_encode(array('status' => false, 'data' => 'Something wrong there.'));
	die;
}

// get easystore product list
if($action === 'get-easystore-product-list')
{
	if (!file_exists($productSeed))
	{
		echo json_encode(array('status' => false, 'data' => 'data not found.'));
		die;
	}

	$easystoreList = file_get_contents($productListSeed);
	if (!$easystoreList)
	{
		echo json_encode(array('status' => false, 'data' => 'data not found.'));
		die;
	}

	echo json_encode(array('status' => true, 'data' => json_decode($easystoreList)));
	die;
}

/**
 * Import dynamic content data.
 *
 * @param string $data
 * @return array
 */
function importDynamicContentData($data)
{
	$dynamicContentExportImportController = new CollectionImportExportController();
	return $dynamicContentExportImportController->importDynamicContent(json_decode($data, true));
}

/**
 * Update dynamic ids in the content.
 *
 * @param string $content
 * @param array $updatedFieldIds
 * @param array $updatedCollectionIds
 * @return string
 */
function updateDynamicIds($content, $updatedFieldIds = [], $updatedCollectionIds = [])
{
	if (empty($updatedFieldIds) || empty($updatedCollectionIds)) {
		return $content;
	}

	$data = $content;

	recursivelyUpdateIds($data, $updatedFieldIds, $updatedCollectionIds);

	return $data;
}

/**
 * Recursively update ids in the data.
 *
 * @param mixed $data
 * @param array $updatedFieldIds
 * @param array $updatedCollectionIds
 */
function recursivelyUpdateIds(&$data, $updatedFieldIds, $updatedCollectionIds)
{
	if (!is_array($data) && !is_object($data)) {
		return;
	}

	if (is_object($data)) {
		$data = json_decode(json_encode($data), true);
	}

	foreach ($data as $key => &$value) {
		if($key === 'source') {
			if(isset($value) && isset($updatedCollectionIds[$value])) {
				$value = $updatedCollectionIds[$value];
			}
		} elseif($key === 'field_name') {
			if(isset($value) && isset($updatedFieldIds[$value])) {
				$value = $updatedFieldIds[$value];
			}
		} elseif (is_array($value)) {
			if ($key === 'attribute') {
				if(isset($value['id']) && isset($updatedFieldIds[$value['id']])) {
					$value['id'] = $updatedFieldIds[$value['id']];
				}

				if(isset($value['path'])) {
					$path = $value['path'];
					$pathExploded = explode('.', $path);
					$updatedIds = [];

					foreach ($pathExploded as $attributeId) {
						if(isset($updatedFieldIds[$attributeId])) {
							$updatedIds[] = $updatedFieldIds[$attributeId];
						} else {
							$updatedIds[] = $attributeId;
						}
					}

					$value['path'] = implode('.', $updatedIds);
				}
			}

			recursivelyUpdateIds($value, $updatedFieldIds, $updatedCollectionIds);
		} elseif (is_object($value)) {
			$arrayValue = json_decode(json_encode($value), true);

			if ($key === 'attribute') {
				if(isset($arrayValue['id']) && isset($updatedFieldIds[$arrayValue['id']])) {
					$arrayValue['id'] = $updatedFieldIds[$arrayValue['id']];
				}  
				
				if(isset($arrayValue['path'])) {
					$path = $arrayValue['path'];
					$pathExploded = explode('.', $path);
					$updatedIds = [];

					foreach ($pathExploded as $attributeId) {
						if(isset($updatedFieldIds[$attributeId])) {
							$updatedIds[] = $updatedFieldIds[$attributeId];
						} else {
							$updatedIds[] = $attributeId;
						}
					}

					$value['path'] = implode('.', $updatedIds);
				}
			} 
			
			recursivelyUpdateIds($arrayValue, $updatedFieldIds, $updatedCollectionIds);
			
			$value = json_decode(json_encode($arrayValue));
		}
	}

	if (is_object($data)) {
		$data = json_decode(json_encode($data));
	}
}

function importLayoutWithMedia($zipFilePath, $zipFileName)
{
	$zip = new ZipArchive();

	if ($zip->open($zipFilePath) === true)
	{
		$config = ApplicationHelper::getAppConfig();
		$tmpPath = $config->get('tmp_path');
		$extractedPath = $tmpPath . '/extracted_' . generateRandomId();
		$zip->extractTo($extractedPath);
		$zip->close();

		$updatedFieldIds = [];
		$updatedCollectionIds = [];

		$pageData = getPageDataFromZip($extractedPath, $updatedFieldIds, $updatedCollectionIds);

		$localMediaSources = $pageData->localMediaSources;
		$extractedMediaSources = scanDirectory($extractedPath);
		$extractedMediaSources = array_filter($extractedMediaSources, function($path) {
			return pathinfo($path, PATHINFO_EXTENSION) !== 'json';
		});

		

		$matchedSourcesMap = [];

		if (is_array($localMediaSources) && !empty($localMediaSources))
		{
			foreach ($localMediaSources as $source)
		{
			$sourceBasename = basename($source);

			foreach ($extractedMediaSources as $extractedSource)
			{
				$extractedSourceBasename = basename($extractedSource);

				if ($sourceBasename === $extractedSourceBasename)
				{
					$matchedSourcesMap[$source] = $extractedSource;
				}
			}
		}

		uploadMediaItems($matchedSourcesMap);
		}

		Folder::delete($extractedPath);

		if (!empty($pageData))
			{
				require_once JPATH_COMPONENT_SITE . '/builder/classes/addon.php';
				require_once JPATH_COMPONENT_SITE . '/helpers/helper.php';
				
				$importingContent = (object)['template' => '', 'css' => '', 'seo' => ''];
				$templateContent = !is_string($pageData->template) ? json_encode($pageData->template) : $pageData->template;
				$content = ApplicationHelper::sanitizePageText($templateContent);
				$content = updateDynamicIds($content, $updatedFieldIds, $updatedCollectionIds);
				$content = json_encode($content);
				/** Sanitize the old data with new data format. */
				$importingContent->template = SppagebuilderHelperSite::sanitizeImportJSON($content);
				$importingContent->seo = $pageData->seo;
				$importingContent->css = $pageData->css;
				
				echo json_encode(array('status' => true, 'data' => $importingContent));
				die;
				
			}
		
	}
	

	
	echo json_encode(array('status' => false, 'data' => 'Failed to open the zip file.'));
}

function getPageDataFromZip($extractedPath, &$updatedFieldIds, &$updatedCollectionIds)
{
	$pageData = [];

	if (is_dir($extractedPath))
	{
		$files = scanDirectory($extractedPath);

		foreach ($files as $file)
		{
			$fileExtension = pathinfo($file, PATHINFO_EXTENSION);
			$fileExtensionLower = strtolower($fileExtension);

			if ($fileExtensionLower === 'json')
			{
				$content = file_get_contents($file);

				if (!empty($content))
				{
					$parsedContent = json_decode($content);

					if(isset($parsedContent->dynamicContentData) && !empty($parsedContent->dynamicContentData))
					{
						$updatedData = importDynamicContentData($parsedContent->dynamicContentData);
						$updatedFieldIds = $updatedData['globalFieldsMap'] ?? [];
						$updatedCollectionIds = $updatedData['globalCollectionsIdMap'] ?? [];
					}

					if (!isset($parsedContent->template))
					{
						$pageData = $content;
					}
					else
					{
						$pageData = $parsedContent;
					}
				}
			}
		}
	}

	return $pageData;
}

function scanDirectory($directory)
{
	$files = [];

	$items = scandir($directory);

	foreach ($items as $item)
	{
		if ($item !== '.' && $item !== '..')
		{
			$path = $directory . '/' . $item;

			if (is_dir($path))
			{
				$files = array_merge($files, scanDirectory($path));
			}
			else
			{
				$files[] = $path;
			}
		}
	}

	return $files;
}

function uploadMediaItems($mediaItemSources)
{
	JLoader::register('SppagebuilderModelMedia', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models/media.php');
	$model = new SppagebuilderModelMedia;
	$user = Factory::getUser();
	$canCreate = $user->authorise('core.create', 'com_sppagebuilder');
	$mediaHelper = new MediaHelper;
	$postMaxSize = $mediaHelper->toBytes(ini_get('post_max_size'));
	$memoryLimit = $mediaHelper->toBytes(ini_get('memory_limit'));
	$contentLength = (int) $_SERVER['CONTENT_LENGTH'];
	$params = ComponentHelper::getParams('com_media');

	if (!$canCreate)
	{
		$report['status'] = false;
		$report['message'] = Text::_('JERROR_ALERTNOAUTHOR');
		echo json_encode($report);
		die;
	}

	foreach ($mediaItemSources as $relativeSource => $extractedSource)
	{
		if(!mediaItemExists($relativeSource))
		{

		if (!file_exists($extractedSource)) {
			$report['status'] = false;
			$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_MEDIA_NOT_FOUND');
			$report['code'] = 404;
			echo json_encode($report);
			die;
		}

		$file = file_get_contents($extractedSource);
		$fileSize = filesize($extractedSource);
		$fileName = basename($extractedSource);
		$dir = pathinfo($relativeSource, PATHINFO_DIRNAME);

		if(!empty($file))
		{
			$error = false;

			if (($postMaxSize > 0 && $contentLength > $postMaxSize) || ($memoryLimit != -1 && $contentLength > $memoryLimit))
				{
					$report['status'] = false;
					$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_MEDIA_TOTAL_SIZE_EXCEEDS');
					$error = true;
					$statusCode = 400;
				}

				$uploadMaxSize = $params->get('upload_maxsize', 0) * 1024 * 1024;
				$uploadMaxFileSize = $mediaHelper->toBytes(ini_get('upload_max_filesize'));

				if (($uploadMaxSize > 0 && $fileSize > $uploadMaxSize) || ($uploadMaxFileSize > 0 && $fileSize > $uploadMaxFileSize))
				{
					$report['status'] = false;
					$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_MEDIA_LARGE');
					$error = true;
					$statusCode = 400;
				}

				// File formats
				$accepted_file_formats = array(
					'image' => array('jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif'),
					'video' => array('mp4', 'mov', 'wmv', 'avi', 'mpg', 'ogv', '3gp', '3g2'),
					'audio' => array('mp3', 'm4a', 'ogg', 'wav'),
					'attachment' => array('pdf', 'doc', 'docx', 'key', 'ppt', 'pptx', 'pps', 'ppsx', 'odt', 'xls', 'xlsx', 'zip', 'json'),
				);

				if (!$error)
				{
					$date = Factory::getDate();
					$file_ext = strtolower(File::getExt($fileName));

					if (has_in_array($file_ext, $accepted_file_formats))
					{
						$media_type = findExtension($file_ext, $accepted_file_formats);

						if ($media_type == 'image')
						{
							$mediaParams = ComponentHelper::getParams('com_media');
							$folder_root = $mediaParams->get('file_path', 'images') . '/';
						}
						elseif ($media_type == 'video')
						{
							$folder_root = 'media/videos/';
						}
						elseif ($media_type == 'audio')
						{
							$folder_root = 'media/audios/';
						}
						elseif ($media_type == 'attachment')
						{
							$folder_root = 'media/attachments/';
						}
						elseif ($media_type == 'fonts')
						{
							$folder_root = 'media/fonts/';
						}

						$report['type'] = $media_type;

						$folder = $folder_root . HTMLHelper::_('date', $date, 'Y') . '/' . HTMLHelper::_('date', $date, 'm') . '/' . HTMLHelper::_('date', $date, 'd');

						if ($dir != '')
						{
							$folder = ltrim($dir, '/');
						}

						if (!is_dir(JPATH_ROOT . '/' . $folder))
						{
							Folder::create(JPATH_ROOT . '/' . $folder, 0755);
						}

						if ($media_type === 'image')
						{
							if (!is_dir(JPATH_ROOT . '/' . $folder . '/_spmedia_thumbs'))
							{
								Folder::create(JPATH_ROOT . '/' . $folder . '/_spmedia_thumbs', 0755);
							}
						}

						$name = $fileName;
						$path = $extractedSource;

						$media_file = preg_replace('#\s+#', "-", File::makeSafe(basename(strtolower($name))));
						$base_name  = File::stripExt($media_file);
						$ext        = File::getExt($media_file);
						$media_name = $base_name . '.' . $ext;
						$dest = Path::clean(JPATH_ROOT . '/' . $folder . '/' . $media_name);

						$dest = resolveFilenameConflict($dest);
						$fileInfo = pathinfo($dest);
						$base_name = $fileInfo['filename'];
						$media_name = $fileInfo['basename'];

						$src = $folder . '/' . $media_name;

						if (File::copy($path, $dest, false, true))
						{
							$media_attr = [];
							$thumb = '';

							if ($media_type === 'image')
							{
								if (strtolower($ext) === 'svg')
								{
									$report['src'] = Uri::root(true) . '/' . $src;
								}
								else if ($ext !== 'avif')
								{
									require_once JPATH_COMPONENT . '/helpers/image.php';
									$image = new SppagebuilderHelperImage($dest);
									$media_attr['full'] = ['height' => $image->height, 'width' => $image->width];

									if (($image->width > 300) || ($image->height > 225))
									{
										$thumbDestPath = dirname($dest) . '/_spmedia_thumbs';
										$created = $image->createThumb(array('300', '300'), $thumbDestPath, $base_name, $ext);

										if ($created == false)
										{
											$report['status'] = false;
											$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_FILE_NOT_SUPPORTED');
											$error = true;
											$statusCode = 400;
										}

										$report['src'] = Uri::root(true) . '/' . $folder . '/_spmedia_thumbs/' . $base_name . '.' . $ext;
										$thumb = $folder . '/_spmedia_thumbs/'  . $base_name . '.' . $ext;
										$thumb_dest = Path::clean($thumbDestPath . '/' . $base_name . '.' . $ext);
										list($width, $height) = getimagesize($thumb_dest);
										$media_attr['thumbnail'] = ['height' => $height, 'width' => $width];
										$report['thumb'] = $thumb;
									}
									else
									{
										$report['src'] = Uri::root(true) . '/' . $src;
										$report['thumb'] = $src;
									}

									// Create placeholder for lazy load
									create_media_placeholder($dest, $base_name, $ext);
								}
							}

							$insert_id = $model->insertMedia($base_name, $src, json_encode($media_attr), $thumb, $media_type);
							$report['media_attr'] = $media_attr;
							$report['status'] = true;
							$report['title'] = $base_name;
							$report['id'] = $insert_id;
							$report['path'] = $src;

							$layout_path = JPATH_ROOT . '/administrator/components/com_sppagebuilder/layouts';
							$format_layout = new FileLayout('media.format', $layout_path);
							$report['message'] = $format_layout->render(array('media' => $model->getMediaByID($insert_id), 'innerHTML' => true));
						}
						else
						{
							$report['status'] = false;
							$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_UPLOAD_FAILED');
							$statusCode = 400;
						}
					}
					else
					{
						$report['status'] = false;
						$report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_FILE_NOT_SUPPORTED');
						$statusCode = 400;
					}
				}
		}
		if ($error === true){
			$report['code'] = $statusCode;
			echo json_encode($report);
		}
		
		}
	}

	
}

function mediaItemExists($source)
{
	$db = Factory::getDbo();
	$query = $db->getQuery(true);
	$query->select($db->quoteName('id'));
	$query->from($db->quoteName('#__spmedia'));
	$query->where($db->quoteName('path') . ' = ' . $db->quote($source));
	$db->setQuery($query);
	$result = $db->loadResult();

	return !empty($result);
}

function create_media_placeholder($dest, $base_name, $ext)
{
	$placeholder_folder_path = JPATH_ROOT . '/media/com_sppagebuilder/placeholder';

	if (!is_dir($placeholder_folder_path))
	{
		Folder::create($placeholder_folder_path, 0755);
	}

	$image = new SppagebuilderHelperImage($dest);
	list($srcWidth, $srcHeight) = $image->getDimension();
	$width = 60;
	$height = $width / ($srcWidth / $srcHeight);
	$image->createThumb(array('60', $height), $placeholder_folder_path, $base_name, $ext, 20);
}

function resolveFilenameConflict(string $filePath)
{
	if (file_exists($filePath))
	{
		$fileInfo = pathinfo($filePath);
		$suffix = 1;

		while (file_exists($filePath))
		{
			$newFileName = $fileInfo['filename'] . '-' . ++$suffix . '.' . $fileInfo['extension'];
			$filePath = Path::clean($fileInfo['dirname'] . '/' . $newFileName);
		}
	}

	return $filePath;
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

function has_in_array($needle, $haystack)
{

	$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));

	foreach ($it as $element)
	{
		if ($element == $needle)
		{
			return true;
		}
	}

	return false;
}

function findExtension($needle, $haystack)
{

	foreach ($haystack as $key => $value)
	{
		$current_key = $key;

		if ($needle === $value or (is_array($value) && findExtension($needle, $value) !== false))
		{
			return $current_key;
		}
	}
	return false;
}

	