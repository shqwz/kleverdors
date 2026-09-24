<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Controllers\CollectionImportExportController;

/**
 * SP Page Builder Base Controller class
 * 
 * @since 1.0.0
 */
class SppagebuilderController extends BaseController
{
	/**
	 * Display function
	 *
	 * @param  boolean $cachable
	 * @param  boolean $urlparams
	 * @return void
	 * @since 1.0.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$apps = Factory::getApplication();
		$viewStatus = false;

		$id    		= $this->input->getInt('id');
		$vName 		= $this->input->getCmd('view');

		$validViewNames = ['page', 'form', 'ajax', 'media', 'systemeditor', 'dynamic'];
		$viewStatus = \in_array($vName, $validViewNames);

		if (!$viewStatus)
		{
			throw new Exception(Text::_('COM_SPPAGEBUILDER_ERROR_PAGE_NOT_FOUND'), 404);
		}

		$this->input->set('view', $vName);

		if ($vName == 'page')
		{
			$cachable = true;
		}

		$safeURLParams = array(
			'catid'  => 'INT',
			'id'     => 'INT',
			'cid'    => 'ARRAY',
			'return' => 'BASE64',
			'print'  => 'BOOLEAN',
			'lang'   => 'CMD',
			'Itemid' => 'INT'
		);

		$user = Factory::getUser();
		$isIgnoreView = ($this->input->getMethod() === 'POST' && (($vName === 'form' && ($this->input->get('layout') !== 'edit') || $this->input->get('layout') !== 'edit-iframe')));

		if ($user->get('id') || $isIgnoreView)
		{
			$cachable = false;
		}

		if ($vName === 'page')
		{
			$model = $this->getModel($vName);
			$model->hit();
		}

		parent::display($cachable, $safeURLParams);
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
	public function export()
	{
		$input  = Factory::getApplication()->input;

		$pageId = $input->get('pageId', '', 'STRING');
		$isSeoChecked = $input->get('isSeoChecked', '', 'STRING');
		$isMediaChecked = $input->get('isMediaChecked', '', 'STRING');

		// check have access
		$user = Factory::getUser();
		$canEdit = $user->authorise('core.edit', 'com_sppagebuilder');

		$canEditOwn = $user->authorise('core.edit.own', 'com_sppagebuilder');

		if($canEditOwn) {
			JLoader::register('SppagebuilderModelPage', JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models/page.php');
			
			$item_info  = SppagebuilderModelPage::getPageInfoById($pageId);

			$canEditOwn = $item_info->created_by == $user->id;
		}

		if (!$canEdit && !$canEditOwn)
		{
			die('Restricted Access');
		}

		$model = $this->getModel('page');
		$content = $model->getItem($pageId);

		if (empty($content)) {
			die('Requesting page not found!');
		}

		$localMediaSources = [];

		if($isMediaChecked){
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

		$decodedAttribs = isset($content->attribs) ? json_decode($content->attribs) : null;

		if ($isSeoChecked) {
			$seoSettings = [
				'og_description' => isset($content->og_description) ? $content->og_description : '',
				'og_image' => '',
				'og_title' => isset($content->og_title) ? $content->og_title : '',
				'meta_description' => isset($decodedAttribs) && isset($decodedAttribs->meta_description) ?  $decodedAttribs->meta_description : '',
				'meta_keywords' => isset($decodedAttribs) && isset($decodedAttribs->meta_keywords) ?  $decodedAttribs->meta_keywords : '',
				'og_type' => isset($decodedAttribs) && isset($decodedAttribs->og_type) ?  $decodedAttribs->og_type : '',
				'robots' => isset($decodedAttribs) && isset($decodedAttribs->robots) ?  $decodedAttribs->robots : '',
				'seo_spacer' => isset($decodedAttribs) && isset($decodedAttribs->seo_spacer) ?  $decodedAttribs->seo_spacer : '',
				'author' => isset($decodedAttribs) && isset($decodedAttribs->author) ?  $decodedAttribs->author : '',
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

		$filename = 'template' . rand(10000, 99999) . '.json';
		$filename = strlen($filename) <= PHP_MAXPATHLEN ? $filename : 'template' . SppagebuilderHelperSite::nanoid(6) . '.json';

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=$filename");
		header("Content-Type: application/json");
		header("Content-Transfer-Encoding: binary ");

		if (isset($content->extension_view) && $content->extension_view === 'popup') {
			$pageContent->attribs = isset($decodedAttribs) ? json_encode($decodedAttribs) : '';
			$pageContent->type = 'popup';
		}

		echo json_encode($pageContent);
		die();
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
				'og_image' => '',
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
			$this->deleteDirectory($exportDir);
		}
	
		die();
	}

	private function deleteDirectory($dir)
	{
		$items = array_diff(scandir($dir), ['.', '..']);
		foreach ($items as $item) {
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if (is_dir($path)) {
				$this->deleteDirectory($path);
			} else {
				unlink($path);
			}
		}
	
		return rmdir($dir);
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

	/**
	 * AJAX function
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function ajax()
	{
		$app 	 = Factory::getApplication();
		$input   = $app->input;
		$format  = ($input->getWord('format')) ? strtolower($input->getWord('format')) : '';
		$results = null;
		$addon 	 = $input->get('addon', '', 'STRING');

		if ($addon)
		{
			$function = 'sp_' . $addon . '_get_ajax';
			$addon_class = ApplicationHelper::generateSiteClassName($addon);
			$method = $input->get('method', 'get', 'STRING');

			require_once JPATH_ROOT . '/components/com_sppagebuilder/parser/addon-parser.php';

			$core_path 		= JPATH_ROOT . '/components/com_sppagebuilder/addons/' . $input->get('addon') . '/site.php';
			$template_path 	= JPATH_ROOT . '/templates/' . SppagebuilderHelperSite::getTemplateName() . '/sppagebuilder/addons/' . $input->get('addon') . '/site.php';

			if (file_exists($template_path))
			{
				require_once $template_path;
			}
			else
			{
				require_once $core_path;
			}

			if (class_exists($addon_class))
			{

				if (method_exists($addon_class, $method . 'Ajax'))
				{
					try
					{
						$results = call_user_func($addon_class . '::' . $method . 'Ajax');
					}
					catch (Exception $e)
					{
						$results = $e;
					}
				}
				else
				{
					$results = new LogicException(Text::sprintf('COM_AJAX_METHOD_NOT_EXISTS', $method . 'Ajax'), 404);
				}
			}
			else
			{
				if (function_exists($function))
				{
					try
					{
						$results = call_user_func($function);
					}
					catch (Exception $e)
					{
						$results = $e;
					}
				}
				else
				{
					$results = new LogicException(Text::sprintf('Function %s does not exist', $function), 404);
				}
			}
		}

		echo new JsonResponse($results, null, false, $input->get('ignoreMessages', true, 'bool'));
		die;
	}

	/**
	 * Send JSON Response to the client.
	 * {"success":true,"message":"ok","messages":null,"data":[{"key":"value"}]}
	 *
	 * @param	mixed	$response	The response array or data.
	 * @param	int		$statusCode	The status code of the HTTP response.
	 *
	 * @return	void
	 * @since	4.1.0
	 */
	
	private function sendResponse($response, int $statusCode = 200)
	{
		$this->app->setHeader('Content-Type', 'application/json');

		$this->app->setHeader('status', $statusCode, true);

		$this->app->sendHeaders();

		echo new JsonResponse($response);

		$this->app->close();
	}

	/**
	 * Plugin formatting and mapping function
	 * This function takes the raw list/array of the fetched extensions (plugins in this case) and the type of the plugins as parameters
	 * The raw extension list has names in non human readable format
	 * Joomla uses the plugin type and the name to generate human readable name from its language files
	 * Language instance is called and the plugin type and name is passed into it to generate the label
	 * The label is then mapped into the plugins with their values
	 * The mapped array is then returned
	 * @param  string $pluginsType type of the plugins we want to get
	 * @param  array  $extensions list of raw extensions we got from the database
	 * @return array
	 * @since  5.3.6
	 */

	private function convertPluginsToOptions(array $extensions, string $pluginsType)
	{
		$options = [];
		$lang    = Factory::getLanguage();

		foreach ($extensions as $extension)
		{	
			$source    = JPATH_PLUGINS . '/' . $pluginsType . '/' . $extension->element;
			$name = strtoupper($extension->name);


			$lang->load($extension->name, JPATH_ADMINISTRATOR) || $lang->load($extension->name, $source);

			if (!$lang->hasKey($name)) {
				$lang->load($name . '.sys', $source) || $lang->load($name . '.sys', JPATH_ADMINISTRATOR);
			}


			$label = Text::_($name);

			$value = $extension->element;
			$value = $value == 'recaptcha' ? 'gcaptcha' : $value;
			$value = $value == 'recaptcha_invisible' ? 'igcaptcha' : $value;
			
			$option = (object) [
				'label' => $label,
				'value' => $value
			];

			$options[] = $option;
		}

		return $options;
	}

	/**
	 * Plugin Model function
	 * This function takes the type of plugin we want to query for in the database, then queries in the db and returns the object
	 * @param  string $pluginsType type of the plugins we want to get
	 * @return mixed
	 * @since  5.3.6
	 */

	private function getPlugins(string $pluginsType)
	{
		
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		
		$query->select(['name', 'element'])
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote($pluginsType))
			->where($db->quoteName('enabled') . ' = 1')
			->where($db->quoteName('client_id') . ' = 0');

		$db->setQuery($query);

		try
		{
			return $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}
	}

	/**
	 * Plugin List function
	 * This function gets the type of plugin we want to send to the get request
	 * getPlugins function is called to get the plugins from the defined model
	 * Before sending the response the raw plugin data is properly formatted using the converPluginsToOptions function
	 * @return void
	 * @since  5.3.6
	 */

	public function getPluginsList()
	{
        $app = Factory::getApplication();
		$input = $app->input;
		$pluginsType = $input->get('pluginsType', '');
		
		$pluginsList = $this->getPlugins($pluginsType);

		$response = (object) [
			'plugins' => $this->convertPluginsToOptions($pluginsList, $pluginsType),
		];

		$this->sendResponse($response);
	}
	
}
