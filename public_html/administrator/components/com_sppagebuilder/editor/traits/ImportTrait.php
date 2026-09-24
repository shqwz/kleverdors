<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Layout Import Trait
 */
trait ImportTrait
{
    use CommonTrait;

    public function importJson()
    {
        $method = $this->getInputMethod();
        $this->checkNotAllowedMethods(['PUT', 'DELETE', 'PATCH'], $method);

        switch ($method)
        {
            case 'POST':
                $this->importLayout();
                break;
        }
    }

    private function importLayout()
    {   
        $file = Factory::getApplication()->input->files->get('page');

        if (isset($file) && $file['error'] === 0)
        {
            $fileName = $file['name'];
            $fileExtensionExploded = explode('.', $fileName);
            $fileExtension = ($fileExtensionExploded)[count($fileExtensionExploded) - 1];
            $fileExtensionLower = strtolower($fileExtension);

            if ($fileExtensionLower === 'json')
            {
                $content = file_get_contents($file['tmp_name']);
                $importingContent = (object)['template' => '', 'css' => '', 'seo' => ''];
                $updatedFieldIds = [];
                $updatedCollectionIds = [];

                if (!empty($content))
                {
                    $parsedContent = json_decode($content);

                    if(isset($parsedContent->dynamicContentData) && !empty($parsedContent->dynamicContentData))
                    {
                        $updatedData = $this->importDynamicContentData($parsedContent->dynamicContentData);
                        $updatedFieldIds = $updatedData['globalFieldsMap'] ?? [];
                        $updatedCollectionIds = $updatedData['globalCollectionsIdMap'] ?? [];

                        $isDetailPage = isset($parsedContent->type) && $parsedContent->type === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
                        $isIndexPage = isset($parsedContent->type) && $parsedContent->type === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;

                        if (($isDetailPage || $isIndexPage) && isset($parsedContent->view_id) && !empty($parsedContent->view_id)) {
                            if (isset($updatedCollectionIds[$parsedContent->view_id])) {
                                $parsedContent->view_id = $updatedCollectionIds[$parsedContent->view_id];
                            }
                        }
                    }

                    if (!isset($parsedContent->template))
                    {
                        $importingContent->template = $content;
                    }
                    else
                    {
                        $importingContent = $parsedContent;
                    }
                }

                if (!empty($importingContent))
                {
                    require_once JPATH_COMPONENT_SITE . '/builder/classes/addon.php';
                    require_once JPATH_COMPONENT_SITE . '/helpers/helper.php';

                    $templateContent = !is_string($importingContent->template) ? json_encode($importingContent->template) : $importingContent->template;
                    $content = ApplicationHelper::sanitizePageText($templateContent);
                    $content = $this->updateDynamicIds($content, $updatedFieldIds, $updatedCollectionIds);
                    $content = json_encode($content);

                    /** Sanitize the old data with new data format. */
                    $importingContent->template = SppagebuilderHelperSite::sanitizeImportJSON($content);

                    $this->sendResponse($importingContent, 200);
                }
            }

            else if ($fileExtensionLower === 'zip')
            {
                $this->importLayoutWithMedia(
                    $file['tmp_name'],
                    $file['name']
                );
            }
        }

        $response['message'] = 'Something wrong there.';
        $this->sendResponse($response, 500);
    }

    private function importLayoutWithMedia($zipFilePath, $zipFileName)
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath) === true)
        {
            $config = ApplicationHelper::getAppConfig();
            $tmpPath = $config->get('tmp_path');
            $extractedPath = $tmpPath . '/extracted_' . $this->generateRandomId();
            $zip->extractTo($extractedPath);
            $zip->close();

            $updatedFieldIds = [];
            $updatedCollectionIds = [];

            $pageData = $this->getPageDataFromZip($extractedPath, $updatedFieldIds, $updatedCollectionIds);

            $localMediaSources = $pageData->localMediaSources;
            $extractedMediaSources = $this->scanDirectory($extractedPath);
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

            $this->uploadMediaItems($matchedSourcesMap);
            }

            Folder::delete($extractedPath);

            if (!empty($pageData))
                {
                    require_once JPATH_COMPONENT_SITE . '/builder/classes/addon.php';
                    require_once JPATH_COMPONENT_SITE . '/helpers/helper.php';
                    
                    $importingContent = (object)['template' => '', 'css' => '', 'seo' => ''];
                    $templateContent = !is_string($pageData->template) ? json_encode($pageData->template) : $pageData->template;
                    $content = ApplicationHelper::sanitizePageText($templateContent);
                    $content = $this->updateDynamicIds($content, $updatedFieldIds, $updatedCollectionIds);
                    $content = json_encode($content);
                    /** Sanitize the old data with new data format. */
                    $importingContent->template = SppagebuilderHelperSite::sanitizeImportJSON($content);
                    $importingContent->seo = $pageData->seo;
                    $importingContent->css = $pageData->css;
                    
                    $this->sendResponse($importingContent, 200);
                    
                }
            
        }
        

        $response['message'] = 'Failed to open the zip file.';
        $this->sendResponse($response, 500);
    }

    private function getPageDataFromZip($extractedPath, &$updatedFieldIds, &$updatedCollectionIds)
    {
        $pageData = [];
    
        if (is_dir($extractedPath))
        {
            $files = $this->scanDirectory($extractedPath);
    
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
                            $updatedData = $this->importDynamicContentData($parsedContent->dynamicContentData);
                            $updatedFieldIds = $updatedData['globalFieldsMap'] ?? [];
                            $updatedCollectionIds = $updatedData['globalCollectionsIdMap'] ?? [];

                            $isDetailPage = isset($parsedContent->type) && $parsedContent->type === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
                            $isIndexPage = isset($parsedContent->type) && $parsedContent->type === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;

                            if (($isDetailPage || $isIndexPage) && isset($parsedContent->view_id) && !empty($parsedContent->view_id)) {
                                if (isset($updatedCollectionIds[$parsedContent->view_id])) {
                                    $parsedContent->view_id = $updatedCollectionIds[$parsedContent->view_id];
                                }
                            }
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
    
    private function scanDirectory($directory)
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
                    $files = array_merge($files, $this->scanDirectory($path));
                }
                else
                {
                    $files[] = $path;
                }
            }
        }
    
        return $files;
    }


    private function uploadMediaItems($mediaItemSources)
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
			$this->sendResponse($report, 403);
		}

        foreach ($mediaItemSources as $relativeSource => $extractedSource)
        {
           if(!$this->mediaItemExists($relativeSource))
           {

            if (!file_exists($extractedSource)) {
                $report['status'] = false;
                $report['message'] = Text::_('COM_SPPAGEBUILDER_MEDIA_MANAGER_MEDIA_NOT_FOUND');
                $this->sendResponse($report, 404);
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

						if (self::has_in_array($file_ext, $accepted_file_formats))
						{
							$media_type = self::array_search($file_ext, $accepted_file_formats);

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

							if (!Folder::exists(JPATH_ROOT . '/' . $folder))
							{
								Folder::create(JPATH_ROOT . '/' . $folder, 0755);
							}

							if ($media_type === 'image')
							{
								if (!Folder::exists(JPATH_ROOT . '/' . $folder . '/_spmedia_thumbs'))
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

							$dest = $this->resolveFilenameConflict($dest);
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
										$this->createMediaPlaceholder($dest, $base_name, $ext);
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
                $this->sendResponse($report, $statusCode);
            }
            
           }
        }
    }

    private function mediaItemExists($source)
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

    private static function has_in_array($needle, $haystack)
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
}
