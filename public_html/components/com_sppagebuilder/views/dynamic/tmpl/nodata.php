<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

?>

<div class="sppb-dynamic-content-no-data">
    <h1><?php echo Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_DATA_TITLE'); ?></h1>
    <p><?php echo Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_DATA_DESCRIPTION'); ?></p>
</div>

<style>
    .sppb-dynamic-content-no-data {
        max-width: 600px;
        margin: 100px auto;
        background-color: #F6F6F7;
        border: 1px dashed #D3D7EB;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }

    .sppb-dynamic-content-no-data h1 {
        font-size: 24px;
        line-height: 1.5;
        font-weight: 600;
        color: #484F66;
    }

    .sppb-dynamic-content-no-data p {
        font-size: 16px;
        line-height: 1.5;
        color: #6B7699;
    }
</style>