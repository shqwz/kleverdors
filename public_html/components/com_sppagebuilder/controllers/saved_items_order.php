<?php

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Utilities\ArrayHelper;

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderControllerSaved_items_order extends FormController
{

    /**
     * Update Saved Items Order
     */
    public function updateSavedItemsOrder()
    {
        $input = Factory::getApplication()->input;
        $pks = $input->json->get('ids', '', 'STRING');
        $orders = $input->json->get('orders', '', 'STRING');
        $type = $input->json->get('type', '', 'STRING');

        if (empty($pks) || empty($orders) || empty($type)) {
            $response['message'] = 'Missing ids or orders';
            $this->sendResponse($response, 400);
        }

        BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sppagebuilder/models');

        $model = $this->getModel($type, 'SppagebuilderModel');

        $pks = ArrayHelper::toInteger($pks);
        $orders = ArrayHelper::toInteger($orders);

        try {
            $model->saveorder($pks, $orders);
            $this->sendResponse(true);
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
            $this->sendResponse($response, 500);
        }
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
    private function sendResponse($response, int $statusCode = 200): void
    {
        $app = Factory::getApplication();
        $app->setHeader('status', $statusCode, true);
        $app->sendHeaders();
        echo new JsonResponse($response);
        $app->close();
    }
}
