<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Global Colors traits
 */
trait GlobalColorsTrait
{
	/**
	 * Get the default colors from the template style (Helix)
	 * 
	 * @return mixed
	 * @since 5.7.0
	 */
	private function getDefaultThemeColors()
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

	public function globalColors()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'PUT', 'PATCH', 'DELETE'], $method);

		if ($method === 'GET')
		{
			$this->getColorVariables();
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
		$colorVariables = [];

		if ($params->exists('sppb_color_variables'))
		{
			$colorVariables = $params->get('sppb_color_variables');
		}

		$themeColors = $this->getDefaultThemeColors();
		$colorVariables = array_merge($colorVariables, json_decode($themeColors, true));

		$this->sendResponse($colorVariables);

	}

	public function importColorPresets()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['GET', 'PUT', 'PATCH', 'DELETE'], $method);

		if ($method === 'POST')
		{
			$this->importColorPresetsHandler();
		}
	}

	public function exportColorPresets()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'DELETE', 'PUT', 'PATCH'], $method);

		if ($method === 'GET') {
			$this->exportColorPresetsHandler();
		}
	}

	/**
	 * Import color presets handler
	 *
	 * @return void
	 *
	 * @since 6.1.3
	 */
	private function importColorPresetsHandler()
	{
		$input = Factory::getApplication()->input;
		$file = $input->files->get('file');
		$override = $input->post->get('override', 'false', 'STRING');
		$override = in_array($override, ['true', '1', 1], true);
		
		if (!$file || $file['error'] !== UPLOAD_ERR_OK)
		{
			$this->sendResponse(['success' => false, 'message' => Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_COLOR_PRESETS_FILE')], 400);
			return;
		}

		$importedColors = json_decode(file_get_contents($file['tmp_name']), true);

		if (!$this->validateColorData($importedColors))
		{
			$this->sendResponse(['success' => false, 'message' => Text::_('COM_SPPAGEBUILDER_EDITOR_INVALID_COLOR_PRESETS_FILE')], 400);
			return;
		}

		$params = ComponentHelper::getParams('com_sppagebuilder');
		$finalColors = $override ? $importedColors : $this->mergeColorPresets($params, $importedColors);

		$params->set('sppb_color_variables', $finalColors);
		$table = \Joomla\CMS\Table\Table::getInstance('extension');
		$table->load(['element' => 'com_sppagebuilder']);
		$table->bind(['params' => $params->toString()]);
		$table->store();

		$this->sendResponse(['success' => true, 'message' => Text::_('COM_SPPAGEBUILDER_EDITOR_IMPORT_COLOR_PRESETS_SUCCESS_MESSAGE')]);
	}

	private function validateColorData($colors)
	{
		if (!is_array($colors)) return false;

		foreach ($colors as $color)
		{
			if (!is_array($color) || !isset($color['path'], $color['value'])) return false;
			if (!is_array($color['path']) || !is_string($color['value'])) return false;
		}

		return true;
	}

	private function ensureArray($data)
	{
		if (is_object($data)) return json_decode(json_encode($data), true);
		return is_array($data) ? $data : [];
	}

	private function groupColorsByVariable($colors, $buildMaps = false)
	{
		$grouped = [];
		$variableMap = [];
		$presetMap = [];
		
		foreach ($colors as $color)
		{
			$color = $this->ensureArray($color);
			if (!isset($color['path']) || !is_array($color['path']) || count($color['path']) < 2) continue;

			$variable = $color['path'][0];
			$preset = $color['path'][1];
			$variableLower = strtolower($variable);
			$presetLower = strtolower($preset);
			
			if ($buildMaps)
			{
				if (!isset($variableMap[$variableLower])) $variableMap[$variableLower] = $variable;
				if (!isset($presetMap[$presetLower])) $presetMap[$presetLower] = $preset;
			}
			
			$grouped[$variable][$preset] = $color['value'];
		}

		return $buildMaps ? [$grouped, $variableMap, $presetMap] : $grouped;
	}

	private function mergeColorPresets($params, $importedColors)
	{
		$existingColors = $this->ensureArray($params->get('sppb_color_variables', []));
		list($existing, $existingVarMap, $existingPresetMap) = $this->groupColorsByVariable($existingColors, true);
		$imported = $this->groupColorsByVariable($importedColors);

		foreach ($imported as $variableName => $presets)
		{
			$variableLower = strtolower($variableName);
			$newName = $variableName;
			$suffix = 1;
			
			while (isset($existingVarMap[$variableLower]))
			{
				$newName = $variableName . '_' . $suffix++;
				$variableLower = strtolower($newName);
			}
			
			$mappedPresets = [];
			foreach ($presets as $preset => $value)
			{
				$presetLower = strtolower($preset);
				$canonicalPreset = isset($existingPresetMap[$presetLower]) ? $existingPresetMap[$presetLower] : $preset;
				$mappedPresets[$canonicalPreset] = $value;
				
				if (!isset($existingPresetMap[$presetLower]))
				{
					$existingPresetMap[$presetLower] = $canonicalPreset;
				}
			}
			
			$existing[$newName] = $mappedPresets;
			$existingVarMap[$variableLower] = $newName;
		}

		$allPresets = array_values($existingPresetMap);
		foreach ($existing as $variable => $presets)
		{
			foreach ($allPresets as $preset)
			{
				if (!isset($presets[$preset])) $existing[$variable][$preset] = '#7A7C85';
			}
		}

		$result = [];
		foreach ($existing as $variable => $presets)
		{
			foreach ($presets as $preset => $value)
			{
				$result[] = ['path' => [$variable, $preset], 'value' => $value];
			}
		}

		return $result;
	}

	/**
	 * Export color presets handler
	 *
	 * @return void
	 *
	 * @since 6.1.3
	 */
	private function exportColorPresetsHandler(){
		$params = ComponentHelper::getParams('com_sppagebuilder');
		$colorVariables = [];

		if ($params->exists('sppb_color_variables'))
		{
			$colorVariables = $params->get('sppb_color_variables');
		}

		$this->sendResponse($colorVariables);
	}
}
