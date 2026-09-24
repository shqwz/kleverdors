<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Application Settings traits
 */
trait ApplicationSettingsTrait
{
	public function applicationSettings()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'PATCH', 'DELETE'], $method);

		if ($method === 'GET')
		{
			$this->getComponentSettings();
		}
		else if ($method === 'PUT')
		{
			$this->saveApplicationSettings();
		}
	}

	private function getComponentSettings()
	{
		$params = ComponentHelper::getParams('com_sppagebuilder');

		if ($params->exists('ig_token'))
		{
			$params->set('ig_token', \json_decode($params->get('ig_token', '')));
		}

		if (!$params->exists('enable_frontend_editing'))
		{
			$params->set('enable_frontend_editing', '1');
		}

		if(!$params->get('lazyplaceholder')) 
		{	
			$params->set('lazyplaceholder', '/components/com_sppagebuilder/assets/images/lazyloading-placeholder.svg');
		}

		$colors = $this->getColors();
		$params->set('colors', $colors);

		$typography = $this->getTypography();
		$params->set('typography', $typography);

		$this->sendResponse($params);
	}

	private function saveApplicationSettings()
	{
		$productionMode = $this->getInput('production_mode', 0, 'INT');
		$gmapApi = $this->getInput('gmap_api', '', 'STRING');
		$igToken = $this->getInput('ig_token', '', 'RAW');
		$fontAwesome = $this->getInput('fontawesome', 1, 'INT');
		$disableGoogleFonts = $this->getInput('disable_google_fonts', 0, 'INT');
		$lazyLoadimg = $this->getInput('lazyloadimg', 0, 'INT');
		$lazyPlaceholder = $this->getInput('lazyplaceholder', '', 'STRING');
		$disableAnimateCSS = $this->getInput('disableanimatecss', 0, 'INT');
		$disableCSS = $this->getInput('disablecss', 0, 'INT');
		$disableOG = $this->getInput('disable_og', 0, 'INT');
		$fbAppID = $this->getInput('fb_app_id', '', 'STRING');
		$disableTc = $this->getInput('disable_tc', 0, 'INT');
		$enableGravatar = $this->getInput('enable_gravatar', 0, 'INT');
		$joomshaperEmail = $this->getInput('joomshaper_email', '', 'STRING');
		$joomshaperLicenseKey = $this->getInput('joomshaper_license_key', '', 'STRING');
		$colors = $this->getInput('colors', '', 'RAW');
		$typography = $this->getInput('typography', '', 'RAW');
		$googleFontsApiKey = $this->getInput('google_font_api_key', '', 'STRING');
		$enableAI = $this->getInput('enable_ai', 1, 'INT');
		$openaiApiKey = $this->getInput('openai_api_key', '', 'STRING');
		$openaiModel = $this->getInput('openai_model', 'gpt-3.5-turbo', 'STRING');
		$enableFrontendEditing = $this->getInput('enable_frontend_editing', 1, 'INT');
		$containerMaxWidth = $this->getInput('container_max_width', 0, 'INT');
		$containerMaxWidth = max(1140, $containerMaxWidth);
		$colorVariables = $this->getInput('sppb_color_variables', '', 'RAW');
		$showColorSwitcher = $this->getInput('show_color_switcher', 0, 'INT');
		$manualCommentApproval = $this->getInput('manual_comment_approval', 0, 'INT');
		$previouslyApprovedComment = $this->getInput('previously_approved_comment', 0, 'INT');
		$showArticleDetailsPageAsDefault = $this->getInput('show_article_details_page_as_default', 0, 'INT');
		
		$params = ComponentHelper::getParams('com_sppagebuilder');
		$componentId = ComponentHelper::getComponent('com_sppagebuilder')->id;

		$joomshaperLicenseKey = trim($joomshaperLicenseKey);
		$joomshaperEmail = trim($joomshaperEmail);

		$params->set('production_mode', $productionMode);
		$params->set('gmap_api', trim($gmapApi));
		$params->set('ig_token', trim($igToken));
		$params->set('fontawesome', $fontAwesome);
		$params->set('disable_google_fonts', $disableGoogleFonts);
		$params->set('lazyloadimg', $lazyLoadimg);
		$params->set('lazyplaceholder', $lazyPlaceholder);
		$params->set('disableanimatecss', $disableAnimateCSS);
		$params->set('disablecss', $disableCSS);
		$params->set('disable_og', $disableOG);
		$params->set('fb_app_id', $fbAppID);
		$params->set('disable_tc', $disableTc);
		$params->set('enable_gravatar', $enableGravatar);
		$params->set('joomshaper_email', $joomshaperEmail);
		$params->set('joomshaper_license_key', $joomshaperLicenseKey);
		$params->set('google_font_api_key', trim($googleFontsApiKey));
		$params->set('enable_ai', $enableAI);
		$params->set('openai_api_key', trim($openaiApiKey));
		$params->set('openai_model', $openaiModel);
		$params->set('enable_frontend_editing', $enableFrontendEditing);
		$params->set('container_max_width', $containerMaxWidth);
		$params->set('show_color_switcher', $showColorSwitcher);
		$params->set('manual_comment_approval', $manualCommentApproval);
		$params->set('previously_approved_comment', $previouslyApprovedComment);
		$params->set('show_article_details_page_as_default', $showArticleDetailsPageAsDefault);

		if(!empty($colorVariables))
		{
			if(is_string($colorVariables))
			{
				$params->set('sppb_color_variables', \json_decode($colorVariables));
			}
			else
			{
				$params->set('sppb_color_variables', $colorVariables);
			}
		}

		if (!empty($joomshaperEmail) && !empty($joomshaperLicenseKey))
		{
			if (!$this->updateLicenseKey($joomshaperEmail, $joomshaperLicenseKey))
			{
				$response['message'] = Text::_("COM_SPPAGEBUILDER_ERROR_MSG_FOR_FAILED_LICESE_KEY");
				$this->sendResponse($response, 500);
			}
		}

		if (!empty($colors))
		{
			$this->saveColors($colors);
		}

		if(!empty($typography)){
			$this->saveTypography($typography);
		}

		$table = Table::getInstance('extension');

		if (!$table->load($componentId))
		{
			$response['message'] = Text::_("COM_SPPAGEBUILDER_ERROR_MSG_FOR_FAILED_LOAD_EXTENSION");
			$this->sendResponse($response, 500);
		}

		$table->params = \json_encode($params);

		if (!$table->store())
		{
			$response['message'] = Text::_("COM_SPPAGEBUILDER_ERROR_MSG_FOR_FAILED_STORE_EXTENSION");
			$this->sendResponse($response, 500);
		}

		if (JVERSION >= 4.0) {
            /** @var CallbackController $cache */
            $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('callback', ['defaultgroup' => '_system']);
            $cache->clean();
        } else {
			$cache = Factory::getCache('_system', 'callback');
			$cache->clean();
		}

		$this->sendResponse(true);
	}

	/**
	 * Update license key.
	 *
	 * @param string $email
	 * @param string $key
	 * @return void
	 * 
	 * @since 4.0.0
	 */
	private function updateLicenseKey($email, $key)
	{
		$value = 'joomshaper_email=' . urlencode($email);
		$value .= '&amp;joomshaper_license_key=' . urlencode($key);

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$fields = [
			$db->quoteName('extra_query') . ' = ' . $db->quote($value),
			$db->quoteName('last_check_timestamp') . ' = ' . $db->quote('0'),
		];

		$query->update($db->quoteName('#__update_sites'))
			->set($fields)
			->where($db->quoteName('name') . ' = ' . $db->quote('SP Page Builder'));

		$db->setQuery($query);

		try
		{
			$db->execute();

			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	private function getDefaultColors()
	{
		$colorPrefix = 'sppb-';

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
						'id' => uniqid(),
						'value' => $value,
						'name' => $colorPrefix . str_replace('_', '-', strtolower
						($key))
					]);
				}
			}

			return json_encode($colorValues);
		} catch (\Exception $e) {
			return "{}";
		}
	}

	private function getColors()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['id', 'name', 'colors'])
			->from($db->quoteName('#__sppagebuilder_colors'))
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);

		$colors = [];
		$ext = "{}";

		try
		{
			$colors = $db->loadObjectList();
			$ext = $this->getDefaultColors();
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!empty($colors))
		{
			foreach ($colors as &$color)
			{
				$color->colors = \json_decode($color->colors);
			}

			unset($color);
		}

		if ($ext !== '[]' && $ext !== '{}') {
			array_push($colors, \json_decode('{ "id": -1, "name": "' . Text::_("COM_SPPAGEBUILDER_EDITOR_SETTINGS_PAGE_DEFAULT_GLOBAL_THEME_COLOR_TITLE") . '", "colors": ' . $ext . ' }'));
		}

		return $colors;
	}

	private function saveColors(string $colorGroups)
	{
		if (!empty($colorGroups))
		{
			$colorGroups = \json_decode($colorGroups);
		}


		$savedColors = $this->getColors();

		if (!empty($savedColors))
		{
			$savedColorsIds = array_map(function ($item)
			{
				return $item->id;
			}, $savedColors);


			$payloadIds = array_map(function ($item)
			{
				return $item->id;
			}, $colorGroups);


			$removedColorsIds = array_filter($savedColorsIds, function ($item) use ($payloadIds)
			{
				return !\in_array($item, $payloadIds);
			});

			if (!empty($removedColorsIds))
			{
				$this->removeColor(array_values($removedColorsIds));
			}
		}

		if (!empty($colorGroups))
		{
			foreach ($colorGroups as $group)
			{
				$this->updateOrCreateColor($group, 'id');
			}
		}
	}


	/**
	 * Saves typography settings by processing provided typography groups.
	 * 
	 * This method handles the creation, update, and deletion of typography settings:
	 * 1. Processes the JSON string input into typography group objects
	 * 2. Compares with existing typography to determine which items need to be removed
	 * 3. Removes typography items that are no longer in the payload
	 * 4. Creates or updates typography items based on their ID presence
	 *
	 * @param string $typographyGroups JSON string containing typography configuration data
	 * 
	 * @return void
	 * 
	 * @since 5.5.5
	 */

	private function saveTypography(string $typographyGroups)
	{
		if (!empty($typographyGroups))
		{
			$typographyGroups = \json_decode($typographyGroups);
		}

		$savedTypography = $this->getTypography();

		if (!empty($savedTypography))
		{
			$savedTypographyIds = array_map(function ($item)
			{
				return $item->id;
			}, $savedTypography);

			$payloadIds = array_map(function ($item)
			{
				return $item->id;
			}, $typographyGroups);

			$removedTypographyIds = array_filter($savedTypographyIds, function ($item) use ($payloadIds)
			{
				return !\in_array($item, $payloadIds);
			});
			
			if (!empty($removedTypographyIds))
			{
				$this->removeTypography(array_values($removedTypographyIds));
			}
		}

		if (!empty($typographyGroups))
		{
			foreach ($typographyGroups as $group)
			{
				$this->updateOrCreateTypography($group, 'id');
			}
		}
	}

	/**
	 * Retrieves all published typography configurations from the database.
	 * 
	 * This method:
	 * 1. Queries the database for all typography records with published status
	 * 2. Loads typography data including id, name, and typography content
	 * 3. Decodes the JSON typography data for each record
	 * 
	 * @return array An array of typography objects with decoded typography data,
	 *               or empty array if no records found or on database error
	 * 
	 * @since 5.5.5
	 */

	private function getTypography()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['id', 'name', 'typography'])
			->from($db->quoteName('#__sppagebuilder_typography'))
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);

		$typography = [];

		try
		{
			$typography = $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!empty($typography))
		{
			foreach ($typography as &$item)
			{
				if (!empty($item->typography))
				{
					$item->typography = \json_decode($item->typography);
				} else if ($item->typography === '')
				{
					$item->typography = [];
				}
			}

			unset($item);
		}

		return $typography;
	}

	/**
	 * Removes typography records from the database by their IDs.
	 *
	 * This method:
	 * 1. Constructs a database delete query targeting the typography table
	 * 2. Specifies which typography records to delete using provided IDs
	 * 3. Executes the query to permanently remove the typography configurations
	 *
	 * @param array $ids An array of typography record IDs to be deleted
	 * 
	 * @return bool True on successful deletion, false if an exception occurs
	 * 
	 * @since 5.5.5
	 */

	private function removeTypography(array $ids)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->delete($db->quoteName('#__sppagebuilder_typography'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

		$db->setQuery($query);

		try
		{
			$db->execute();
			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}
	
	/**
	 * Creates a new typography record or updates an existing one in the database.
	 *
	 * This method:
	 * 1. Determines whether to insert a new record or update an existing one based on primary key presence
	 * 2. Prepares the typography data by encoding it as JSON
	 * 3. Sets up a record object with all required fields for database storage
	 * 4. Executes either an insert or update database operation
	 *
	 * @param object $data The typography data object containing configuration properties
	 * @param string $primaryKey The name of the primary key field to check for existence determination
	 * 
	 * @return bool|int Returns the record ID on successful operation, or false if an exception occurs
	 * 
	 * @since 5.5.5
	 */

	private function updateOrCreateTypography($data, string $primaryKey)
	{
		$isNew = true;

		if (!empty($data->$primaryKey))
		{
			$isNew = false;
		}

		$typography = !empty($data->typography) ? json_encode($data->typography) : '';
		$record = (object) [
			'id' => !$isNew ? $data->$primaryKey : null,
			'name' => $data->name,
			'typography' => $typography,
			'created_by' => Factory::getUser()->id,
			'created' => Factory::getDate()->toSql(),
			'published' => 1
		];

		$db = Factory::getDbo();

		if ($isNew)
		{
			try
			{
				return $db->insertObject('#__sppagebuilder_typography', $record, 'id');
			}
			catch (\Exception $e)
			{
				return false;
			}
		}
		else
		{
			try
			{
				return $db->updateObject('#__sppagebuilder_typography', $record, 'id', true);
			}
			catch (\Exception $e)
			{
				return false;
			}
		}
	}

	private function updateOrCreateColor($data, string $primaryKey)
	{
		$isNew = true;

		if (!empty($data->$primaryKey))
		{
			$isNew = false;
		}

		$name = $data->name;
		$colors = !empty($data->colors) ? json_encode($data->colors) : '';
		$record = (object) [
			'id' => !$isNew ? $data->$primaryKey : null,
			'name' => $name,
			'colors' => $colors,
			'created_by' => Factory::getUser()->id,
			'created' => Factory::getDate()->toSql(),
			'published' => 1
		];

		if ($isNew)
		{
			try
			{
				$db = Factory::getDbo();
				return $db->insertObject('#__sppagebuilder_colors', $record, 'id');
			}
			catch (\Exception $e)
			{
				return false;
			}
		}
		else
		{
			try
			{
				$db = Factory::getDbo();
				return $db->updateObject('#__sppagebuilder_colors', $record, 'id', true);
			}
			catch (\Exception $e)
			{
				return false;
			}
		}
	}

	private function removeColor(array $ids)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query->delete($db->quoteName('#__sppagebuilder_colors'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');

		$db->setQuery($query);

		try
		{
			$db->execute();
			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}

	public function typographyInUse(){
		$groupIndex = $this->getInput('group_index', -1, 'INT');
		$typographyIndex = $this->getInput('typography_index', -1, 'INT');
		$typographiesInUse = $this->getTypographiesInUse();

		if($typographyIndex === -1) {
			$isInUse = isset($typographiesInUse[(string) $groupIndex]);
			if($isInUse){
				$response['message'] = Text::_("COM_SPPAGEBUILDER_ERROR_TYPOGRAPHY_IN_USE");
			}
			$response['data'] = [
				'is_in_use' => $isInUse
			];
			$this->sendResponse($response);
		} else {
			$isInUse = false;
			if (isset($typographiesInUse[(string) $groupIndex])) {
				$isInUse = in_array($typographyIndex, $typographiesInUse[(string) $groupIndex]);
			}
			if($isInUse){
				$response['message'] = Text::_("COM_SPPAGEBUILDER_ERROR_TYPOGRAPHY_IN_USE");
			}
			$response['data'] = [
				'is_in_use' => $isInUse
			];
			$this->sendResponse($response);
		}
	}

private function getTypographiesInUse()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('content')
			->from($db->quoteName('#__sppagebuilder'));
		$db->setQuery($query);
		
		$typographyMap = [];
		
		try
		{
			$pages = $db->loadObjectList();
			
			if (!empty($pages))
			{
				foreach ($pages as $page)
				{
					if (!empty($page->content))
					{
						$content = json_decode($page->content);
						
						if (!empty($content))
						{
							$this->extractTypographyPresets($content, $typographyMap);
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			return [];
		}
		
		foreach ($typographyMap as $groupIndex => &$typographyIndices)
		{
			$typographyIndices = array_values(array_unique($typographyIndices));
			sort($typographyIndices);
		}
		unset($typographyIndices);
		
		return $typographyMap;
	}

	private function extractTypographyPresets($data, &$map)
	{
		if (is_object($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_object($value) && isset($value->preset) && !empty($value->preset))
				{
					$preset = $value->preset;
					
					$parts = explode('.', $preset);
					
					if (count($parts) === 2)
					{
						$groupIndex = $parts[0];
						$typographyIndex = (int) $parts[1];
						
						if (!isset($map[$groupIndex]))
						{
							$map[$groupIndex] = [];
						}
						
						if (!in_array($typographyIndex, $map[$groupIndex]))
						{
							$map[$groupIndex][] = $typographyIndex;
						}
					}
				}
				else
				{
					$this->extractTypographyPresets($value, $map);
				}
			}
		}
		else if (is_array($data))
		{
			foreach ($data as $value)
			{
				$this->extractTypographyPresets($value, $map);
			}
		}
	}
	
}
