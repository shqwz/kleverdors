<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// No direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/autoload.php';
require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/constants.php';

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/vendor/autoload.php';
}

if (file_exists(JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php')) {
	require_once JPATH_ROOT . '/administrator/components/com_sppagebuilder/dynamic-content/helper.php';
}

BuilderAutoload::loadClasses();
BuilderAutoload::loadHelperClasses();
BuilderAutoload::loadGlobalAssets();
BuilderAutoload::loadAliases();

$controller = BaseController::getInstance('Sppagebuilder');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
