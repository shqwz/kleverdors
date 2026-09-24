<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Http\Http;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Trait for managing popup template list
 */
trait PopupTemplateTrait
{
    /**
     * @return void
     * @since 5.4.0
     */
    public function popupTemplateList()
    {
        $method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'DELETE','PATCH', 'PUT'], $method);

        switch ($method)
        {
            case 'GET':
                $this->getPopupTemplateList();
                break;
        }
    }
    
    /**
     * @return void
     * @since 5.4.0
     */
    public function getPopupTemplateList()
    {
        $cache_path = JPATH_CACHE . '/sppagebuilder';
        $cache_file = $cache_path . '/popup_templates.json';
        $templates = array(); // All pre-defined popup templates list
        $popupTemplatesData = '';

        $response = new stdClass();

        $http = new Http;

        if (!Folder::exists($cache_path))
        {
            Folder::create($cache_path, 0755);
        }

        if (File::exists($cache_file) && (filemtime($cache_file) > (time()  - (24 * 60 * 60))))
        {
            $popupTemplatesData = file_get_contents($cache_file);
        }
        else
        {
            $templateApi = 'https://www.joomshaper.com/index.php?option=com_layouts&view=popups';
         
            $templatesResponse = $http->get($templateApi);
            $popupTemplatesData = $templatesResponse->body;

            if ($templatesResponse->code !== 200)
            {
                $response = 'Templates not found.';
            }

            if (!empty($popupTemplatesData))
            {
                File::write($cache_file, $popupTemplatesData);
            }
        }

        if (!empty($popupTemplatesData))
        {
            $popupTemplates = json_decode($popupTemplatesData);

            if (!empty($popupTemplates))
            {
                $this->sendResponse($popupTemplates);
            }
        }

        $response['message'] = 'No template found.';
        $this->sendResponse($response, 400);
    }
}
