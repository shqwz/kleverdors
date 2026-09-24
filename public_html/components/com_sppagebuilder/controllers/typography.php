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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;

/**
 * Typography Controller class
 *
 * @since 5.5.5
 */
class SppagebuilderControllerTypography extends FormController
{
   /**
     * Retrieves all published typography configurations from the database.
     * 
     * This method:
     * 1. Queries the database for all typography records with published status
     * 2. Loads typography data including id, name, and typography content
     * 3. Decodes the JSON typography data for each record
     * 4. Sends the response as JSON to the client
     *
     * @return void
     * 
     * @since 5.5.5
     */

	public function globalTypographies()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['id', 'name', 'typography'])
			->from($db->quoteName('#__sppagebuilder_typography'))
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);

		$typographies = [];

		try
		{
			$typographies = $db->loadObjectList();
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!empty($typographies))
		{
			foreach ($typographies as &$typography)
			{
				$typography->typography = \json_decode($typography->typography);
			}

			unset($typography);
		}

		$this->sendResponse($typographies);
	}

	public function getGlobalTypographiesLocally() {
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(['id', 'name', 'typography'])
			->from($db->quoteName('#__sppagebuilder_typography'))
			->where($db->quoteName('published') . ' = 1');
		$db->setQuery($query);

		$typographies = [];

		try
		{
			$typographies = $db->loadObjectList();

			if (empty($typographies)) {
				return null;
			}
		}
		catch (\Exception $e)
		{
			return [];
		}

		if (!empty($typographies))
		{
			foreach ($typographies as &$typography)
			{
				$typography->typography = \json_decode($typography->typography);
			}

			unset($typography);
		}
		return $typographies;
	}

	 /**
     * Sends JSON response to the client with appropriate headers.
     * 
     * This helper method:
     * 1. Sets the HTTP status code header
     * 2. Sends all headers to the client
     * 3. Outputs the response data as JSON
     * 4. Closes the application to prevent further output
     * 
     * @param mixed $response The data to be sent as JSON response
     * @param int $statusCode HTTP status code to include in the response
     * 
     * @return void
     * 
     * @since 5.5.5
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
