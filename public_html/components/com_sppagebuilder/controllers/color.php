<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;

/**
 * Color Controller class
 *
 * @since 5.0.0
 */
class SppagebuilderControllerColor extends FormController
{
	/**
	 * Get the default colors from the template style (Helix)
	 * 
	 * @return mixed
	 * @since 5.7.0
	 */
	private function getDefaultColors()
	{
		$colorPrefix = 'sppb';

		$keysToExtract = [
			"topbar_bg_color",
			"topbar_text_color",
			"header_bg_color",
			"logo_text_color",
			"menu_text_color",
			"menu_text_hover_color",
			"menu_text_active_color",
			"menu_dropdown_bg_color",
			"menu_dropdown_text_color",
			"menu_dropdown_text_hover_color",
			"menu_dropdown_text_active_color",
			"offcanvas_menu_icon_color",
			"offcanvas_menu_bg_color",
			"offcanvas_menu_items_and_items_color",
			"offcanvas_menu_active_menu_item_color",
			"text_color",
			"bg_color",
			"link_color",
			"link_hover_color",
			"footer_bg_color",
			"footer_text_color",
			"footer_link_color",
			"footer_link_hover_color",
		];

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['params'])
			->from($db->quoteName('#__template_styles'))
			->where($db->quoteName('client_id') . ' = 0')
			->where($db->quoteName('home') . ' = 1');
		$db->setQuery($query);

		try
		{
			$ext = $db->loadObject();

			$styleObj = !empty($ext->params) ? $ext->params : "{}";

			$styleObjDecoded = \json_decode($styleObj);

			$isCustomTemplateStyle = isset($styleObjDecoded->custom_style) && $styleObjDecoded->custom_style == 1;

			if(!$isCustomTemplateStyle && isset($styleObjDecoded->preset) && !empty($styleObjDecoded->preset)) {
				$styleObjDecoded = json_decode($styleObjDecoded->preset);
			}

			$newStyleObj = new \stdClass();

			foreach ($keysToExtract as $key) {
				if (isset($styleObjDecoded->$key)) {
					$newStyleObj->$key = $styleObjDecoded->$key;
				}
			}

			$styleObjDecoded = $newStyleObj;

			if (empty($styleObjDecoded->custom_style) && !empty($styleObjDecoded->preset)) {
				$styleObjDecoded = json_decode($styleObjDecoded->preset);
			}

			$colorValues = [];

			foreach ($styleObjDecoded as $key => $value) {
				if (is_string($value) && !empty($value)) {
					array_push($colorValues, [
						'path' => [$colorPrefix . '-'  . str_replace('_', '-', strtolower
						($key)), ''],
						'value' => $value,
						'isTemplateColor' => true,
					]);
				}
			}

			return json_encode($colorValues);
		} catch (\Exception $e) {
			return "{}";
		}
	}

	/**
	 * Get color variables
	 *
	 * @return mixed
	 *
	 * @since 5.7.0
	 */
	private function getColorVariables()
	{
		$params = ComponentHelper::getParams('com_sppagebuilder');

		if ($params->exists('sppb_color_variables'))
		{
			return $params->get('sppb_color_variables');
		}

		return [];

	}

    /**
     * Get global colors
     */
	public function globalColors()
	{
		$colorVariables = $this->getColorVariables();
		$themeColors = $this->getDefaultColors();
		$colorVariables = array_merge($colorVariables, json_decode($themeColors, true));

		$this->sendResponse($colorVariables);
	}

	/**
	 * Send JSON Response to the client.
	 *
	 * @param	array	$response	The response array or data.
	 * @param	int		$statusCode	The status code of the HTTP response.
	 *
	 * @return	void
	 * @since	5.0.0
	 */
	private function sendResponse($response, int $statusCode = 200) : void
	{
		$app = Factory::getApplication();
		$app->setHeader('status', $statusCode, true);
		$app->sendHeaders();
		echo new JsonResponse($response);
		$app->close();
	}
}
