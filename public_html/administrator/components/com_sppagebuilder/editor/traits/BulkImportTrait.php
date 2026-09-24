<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\Archive\Archive;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Models\Page;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Trait for managing bulk import API endpoint.
 */
trait BulkImportTrait
{
    use CommonTrait;
    
    public function bulkImport()
    {
        $method = $this->getInputMethod();
        $this->checkNotAllowedMethods(['GET', 'DELETE', 'PUT', 'PATCH'], $method);

        if ($method === 'POST') {
            $this->importBulk();
        }
    }

    /**
     * Update page data with latest dynamic ids.
     * 
     * @param mixed $pageData
     *
     * @return mixed
     * @since 5.7.0
     */
    protected function updateDynamicContentPageData($pageData) {
        if(isset($pageData->dynamicContentData) && !empty($pageData->dynamicContentData)) {
            $updatedData = $this->importDynamicContentData($pageData->dynamicContentData);
            $updatedFieldIds = $updatedData['globalFieldsMap'] ?? [];
            $updatedCollectionIds = $updatedData['globalCollectionsIdMap'] ?? [];

            $isDetailPage = isset($pageData->type) && $pageData->type === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
            $isIndexPage = isset($pageData->type) && $pageData->type === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;

            if (($isDetailPage || $isIndexPage) && isset($pageData->view_id) && !empty($pageData->view_id)) {
                if (isset($updatedCollectionIds[$pageData->view_id])) {
                    $pageData->view_id = $updatedCollectionIds[$pageData->view_id];
                }
            }

            $templateContent = !is_string($pageData->template) ? json_encode($pageData->template) : $pageData->template;

            $templateContent = $this->updateDynamicIds(json_decode($templateContent), $updatedFieldIds, $updatedCollectionIds);

            $pageData->template = json_encode($templateContent);
        }

        return $pageData;
    }

    /**
     * Bulk import pages.
     *
     * @return void
     * @since 5.2.10
     */
    public function importBulk()
    {
        $config = ApplicationHelper::getAppConfig();
        $pagesZip = $this->getFilesInput('pagesZip', null);
        $tmpPath = $config->get('tmp_path');

        $zipPath = $tmpPath . '/pagesImport.zip';
        $extractPath = $tmpPath . '/unpack-pages';
        

        if (empty($pagesZip)) {
            $this->sendResponse([
                'status' => false,
                'message' => 'Pages Zip file is required.'
            ], 400);
        }

        $user = Factory::getUser();
        $canCreate = $user->authorise('core.create', 'com_sppagebuilder');

		if (!$canCreate)
		{
			$this->sendResponse([
				'message' => Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_CREATE_ACCESS')
			], 403);
		}

        if (file_exists($zipPath))
        {
            File::delete($zipPath);
        }

        if (Folder::exists($extractPath))
        {
            Folder::delete($extractPath);
        }

        if (File::upload($pagesZip['tmp_name'], $zipPath))
        {
            try {
                $archive = new Archive(['tmp_path' => $tmpPath]);
                $extractFile = $archive->extract($zipPath,  $extractPath);

                if(!$extractFile) {
                    $this->sendResponse([
                        'status' => false,
                        'message' => 'File extract failed.'
                    ], 500);
                }

                $extractedFiles = (array) Folder::files($extractPath, '');
                $extractedFolders = (array) Folder::folders($extractPath, '', true);

                if(!empty($extractedFolders)){
                    $this->bulkImportWithMedia($extractedFolders, $extractPath);
                    die();
                }

                // check if extracted files are json and data format is correct
                $pattern = '/^[a-zA-Z0-9_.\s-]+\.json$/';
                foreach($extractedFiles as $pageJson) {
                    if(!preg_match($pattern, $pageJson)) {
                        $this->sendResponse([
                            'status' => false,
                            'message' => 'File format is not supported.'
                        ], 400);
                    }

                    $pageJsonFullPath = $extractPath . '/' . $pageJson;
                    $pageData = json_decode(file_get_contents($pageJsonFullPath));

                    if(!isset($pageData->template) || !isset($pageData->css)) {
                        $this->sendResponse([
                            'status' => false,
                            'message' => 'File is corrupted.'
                        ], 400);
                    }
                }

                $isAllPagesImported = true;

                foreach($extractedFiles as $pageJson) {
                    $pageJsonFullPath = $extractPath . '/' . $pageJson;
                    $pageData = json_decode(file_get_contents($pageJsonFullPath));

                    $pageData = $this->updateDynamicContentPageData($pageData);

                    $isSuccess =  $this->createSinglePage($pageData);

                    $isAllPagesImported = $isAllPagesImported && $isSuccess;
                }

                if(!$isAllPagesImported) {
                    $this->sendResponse([
                        'status' => true,
                        'message' => 'Some of the pages not imported.'
                    ], 200);
                }
            } catch (\Exception $error) {
                $this->sendResponse([
                    'status' => false,
                    'message' => $error->getMessage()
                ], 500);
            }

            $this->sendResponse([
                'status' => true,
                'message' => 'Pages imported successfully.'
            ], 200);
        }

        $this->sendResponse([
            'status' => false,
            'message' => 'Pages import failed.'
        ], 500);
    }

    public function createSinglePage($pageData)
	{
		$model = $this->getModel('Editor');
		$user = Factory::getUser();
		$version = SppagebuilderHelper::getVersion();
        
		$extension = 'com_sppagebuilder';
		$extensionView = Page::PAGE_TYPE_REGULAR;
        $view_id = 0;

        if(!empty($pageData->type)){
            switch($pageData->type){
                case Page::PAGE_TYPE_POPUP:
                    $extensionView = Page::PAGE_TYPE_POPUP;
                    break;

                case Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL:
                    $detailPageExists = false;
                    $pages = $this->getDynamicContentPages();

                    if(!empty($pages)){
                        $pages = isset($pages[$pageData->view_id]) ? Arr::make($pages[$pageData->view_id]) : [];
                        foreach ($pages as $item) {
                            if ($item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL) {
                                $detailPageExists = true;
                                break;
                            }
                        }
                    }
                    
                    if(!$detailPageExists){
                        $extensionView = Page::PAGE_TYPE_DYNAMIC_CONTENT_DETAIL;
                        if(isset($pageData->view_id) && !empty($pageData->view_id)) {
                            $view_id = $pageData->view_id;
                        }
                    }
                    break;

                case Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX:
                    $indexPageExists = false;
                    $pages = $this->getDynamicContentPages();

                    if(!empty($pages)){
                        $pages = isset($pages[$pageData->view_id]) ? Arr::make($pages[$pageData->view_id]) : [];
                        foreach ($pages as $item) {
                            if ($item['extension_view'] === Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX) {
                                $indexPageExists = true;
                                break;
                            }
                        }
                    }
                    
                    if(!$indexPageExists){
                        $extensionView = Page::PAGE_TYPE_DYNAMIC_CONTENT_INDEX;
                        if(isset($pageData->view_id) && !empty($pageData->view_id)) {
                            $view_id = $pageData->view_id;
                        }
                    }
                    break;
            }
        }

		$data = [];

        $title = !empty($pageData->title) ? $pageData->title . uniqid('-imported-') : uniqid('Imported-');
        $language = !empty($pageData->language) ? $pageData->language : '*';
        $css = !empty($pageData->css) ? $pageData->css : '';
        $pageContent = !empty($pageData->template) ? $pageData->template : '[]';
        $seoData = !empty($pageData->seo) ? $pageData->seo : null;

		$data = [
			'id' => 0,
			'title' => $title,
			'text' => '[]',
			'content' => json_encode($pageContent),
			'css' => $css,
			'catid' => 0,
			'language' => $language,
			'access' => 1,
			'published' => 1,
			'extension' => $extension,
			'extension_view' => $extensionView,
			'created_on' => Factory::getDate()->toSql(),
			'created_by' => $user->id,
			'modified' => Factory::getDate()->toSql(),
			'version' => $version,
		];

        if($view_id) {
            $data['view_id'] = $view_id;
        }
        
        if($seoData) {
            $data['attribs'] = $seoData;
        }

        if (!empty($pageData->type) && $pageData->type === 'popup') {
            $data['attribs'] = isset($pageData->attribs) ? $pageData->attribs : null;
        }

		$result = $model->createPage($data);

		if (!empty($result['message']))
		{
			return false;
		}

        return true;
	}

    private function getDynamicContentPages (){
        $pages = Page::where('extension', 'com_sppagebuilder')
                        ->whereLike('extension_view', 'dynamic_content%')
                        ->get(['extension_view', 'view_id']);

        $pages = Arr::make($pages)->map(function ($item) {
            return $item->toArray();
        })->reduce(function ($carry, $item) {
            $carry[$item['view_id']] ??= [];
            $carry[$item['view_id']][] = $item;
            return $carry;
        }, []);

        return $pages->toArray();
    }

    private function bulkImportWithMedia($extractedFolders, $extractPath)
    {
        $isAllPagesImported = true;

        foreach($extractedFolders as $folder)
        {
            $folderPath = $extractPath . '/' . $folder;

            if($folder === 'media')
            {
                continue;
            }

            $pageData = $this->getPageData($folderPath);
            $localMediaSources = $pageData->localMediaSources;
            $extractedMediaSources = $this->scanFolder($folderPath);
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

            if(!empty($matchedSourcesMap))
            {
                $this->uploadMediaFromSource($matchedSourcesMap);
            }
            Folder::delete($folderPath);
            }

            if (!empty($pageData))
            {
                unset($pageData->localMediaSources);
                $isSuccess = $this->createSinglePage($pageData);
                $isAllPagesImported = $isAllPagesImported && $isSuccess;
            }

        }

        if(!$isAllPagesImported) {
            $this->sendResponse([
                'status' => true,
                'message' => 'Some of the pages not imported.'
            ], 200);
        }

        $this->sendResponse([
            'status' => true,
            'message' => 'Pages imported successfully.'
        ], 200);
    }

    private function getPageData($extractedPath)
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

                        $parsedContent = $this->updateDynamicContentPageData($parsedContent);
    
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

    private function scanFolder($directory)
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

    private function uploadMediaFromSource($mediaItemSources)
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
    
}
