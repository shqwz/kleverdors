<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Form\FormHelper;

if (!Factory::getUser()->authorise('core.manage', 'com_sppagebuilder'))
{
	throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

FormHelper::addFieldPath(__DIR__ . '/models/fields');

// Require helper file
JLoader::register('SppagebuilderHelper', __DIR__ . '/helpers/sppagebuilder.php');

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
BuilderAutoload::loadAliases();

SppagebuilderHelperSite::loadLanguage();

$controller = BaseController::getInstance('sppagebuilder');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
