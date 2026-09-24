<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;

/**
 * Trait for gettings plugins
 */
trait PluginsTrait
{
	/**
	 * API endpoint for the backend plugin field
	 * Send back list of plugins depending on the type
	 * @return void
	 * @since  5.3.6
	 */
	
	public function pluginsList()
	{
		$method = $this->getInputMethod();
		$this->checkNotAllowedMethods(['POST', 'PUT', 'DELETE', 'PATCH'], $method);

		$this->getPlugins();
	}

	/**
	 * Plugin List function
	 * This function gets the type of plugin we want to send to the get request
	 * getPlugins function is called to get the plugins from the defined model
	 * Before sending the response the raw plugin data is properly formatted using the converPluginsToOptions function
	 * @return void
	 * @since  5.3.6
	 */

	private function getPlugins()
	{
        $app = Factory::getApplication();

		$input = $app->input;

		$pluginsType = $input->get('pluginsType', '');

		$model = $this->getModel('Appconfig');
		$pluginsList = $model->getPlugins($pluginsType);

		$response = (object) [
			'plugins' => $this->convertPluginsToOptions($pluginsList, $pluginsType),
		];

		$this->sendResponse($response);
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
}
