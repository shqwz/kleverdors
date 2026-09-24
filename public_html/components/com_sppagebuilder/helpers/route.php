<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

abstract class SppagebuilderHelperRoute
{
	public static function buildRoute($link)
	{
		// sh404sef
		if (defined('SH404SEF_IS_RUNNING'))
		{
			return Uri::root() . $link;
		}

		// 4SEF
		if (defined('4SEF_IS_RUNNING'))
		{
			return Uri::root() . $link;
		}

		return Route::link('site', $link, false, null);
	}

	// Get page route
	public static function getPageRoute($id, $language = 0, $layout = null, $isPopup = false)
	{
		// Create the link
		$link = 'index.php?option=com_sppagebuilder&view=page&id=' . $id;

		if ($isPopup)
		{
			$link .= '&popup=1';
		}

		if ($language && $language !== '*' && Multilanguage::isEnabled())
		{
			$link .= '&lang=' . $language;
		}

		if ($layout)
		{
			$link .= '&layout=' . $layout;
		}

		if ($Itemid = self::getMenuItemId($id))
		{
			$link .= '&Itemid=' . $Itemid;
		}

		return self::buildRoute($link);
	}

	// Get form route
	public static function getFormRoute($id, $language = 0, $Itemid = 0, $easyStoreRouteType = null, $isPopup = false)
	{
		$link = 'index.php?option=com_sppagebuilder&view=form&id=' . (int) $id;

		if ($isPopup)
		{
			$link .= '&popup=1';
		}

		if ($language && $language !== '*' && Multilanguage::isEnabled())
		{
			$link .= '&lang=' . $language;
		}

		if ($Itemid != 0)
		{
			$link .= '&Itemid=' . $Itemid;
		}
		else
		{
			if (self::getMenuItemId($id))
			{
				$link .= '&Itemid=' . self::getMenuItemId($id);
			}
		}

		if ($easyStoreRouteType !== null)
		{
			$link .= '&type=' . $easyStoreRouteType;
		}

		$link .= '&layout=edit&tmpl=component';

		return self::buildRoute($link);
	}

	public static function getArticleRoute($id, $language = 0, $layout = null, $isPopup = false)
	{
		// Create the link
		$link = 'index.php?option=com_content&view=article&id=' . $id;

		if ($isPopup)
		{
			$link .= '&popup=1';
		}

		if ($language && $language !== '*' && Multilanguage::isEnabled())
		{
			$link .= '&lang=' . $language;
		}

		if ($layout)
		{
			$link .= '&layout=' . $layout;
		}

		if ($Itemid = self::getMenuItemId($id))
		{
			$link .= '&Itemid=' . $Itemid;
		}

		return self::buildRoute($link);
	}

	// get menu ID
	public static function getMenuItemId($id)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('id')));
		$query->from($db->quoteName('#__menu'));
		$query->where(
			'(' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_sppagebuilder&view=page&id=' . (int) $id) .
			' OR ' . $db->quoteName('link') . ' LIKE ' . $db->quote('%option=com_sppagebuilder&view=page&id=' . (int) $id . '&%') . ')'
		);
		$query->where($db->quoteName('published') . ' = ' . $db->quote('1'));
		$db->setQuery($query);
		$result = $db->loadResult();

		if ($result)
		{
			return $result;
		}

		return;
	}
}
