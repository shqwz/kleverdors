<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/
//no direct access
defined ('_JEXEC') or die ('Restricted access');

class SppagebuilderAddonEmpty_space extends SppagebuilderAddons
{

	public function render()
	{

		$class  = (isset($this->addon->settings->class) && $this->addon->settings->class) ? $this->addon->settings->class : '';

		return '<div class="sppb-empty-space ' . $class . ' clearfix"></div>';
	}

	public function css()
	{
		$addon_id = '#sppb-addon-' . $this->addon->id;
		$settings = $this->addon->settings;
		$cssHelper = new CSSHelper($addon_id);
		$css = '';
		$gapSettings = $cssHelper->generateStyle('.sppb-empty-space', $settings, ['gap' => 'height']);
		$transformCss = $cssHelper->generateTransformStyle('.sppb-empty-space', $settings, 'transform');
		
		$css .= $gapSettings;
		$css .= $transformCss;

		return $css;
	}

	public static function getTemplate()
	{
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output = '<style type="text/css">';
		$output .= $lodash->unit('height', '.sppb-empty-space', 'data.gap', 'px');
		$output .= $lodash->generateTransformCss('.sppb-empty-space', 'data.transform');
		$output .= '
		</style>

		<div class="sppb-empty-space sppb-empty-space-edit {{ data.class }} clearfix"></div>';
		return $output;
	}

}
