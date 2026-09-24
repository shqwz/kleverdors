<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderAddonRaw_html extends SppagebuilderAddons
{

	public function render()
	{
		$class = (isset($this->addon->settings->class) && $this->addon->settings->class) ? $this->addon->settings->class : '';
		$title = (isset($this->addon->settings->title) && $this->addon->settings->title) ? $this->addon->settings->title : '';
		$heading_selector = (isset($this->addon->settings->heading_selector) && $this->addon->settings->heading_selector) ? $this->addon->settings->heading_selector : 'h3';

		// Options
		$html = (isset($this->addon->settings->html) && $this->addon->settings->html) ? $this->addon->settings->html : '';

		// Output
		if ($html)
		{
			$output  = '<div class="sppb-addon sppb-addon-raw-html ' . $class . '">';
			$output .= ($title) ? '<' . $heading_selector . ' class="sppb-addon-title">' . $title . '</' . $heading_selector . '>' : '';
			$output .= '<div class="sppb-addon-content">';
			$output .= $html;
			$output .= '</div>';
			$output .= '</div>';

			return $output;
		}

		return;
	}

	/**
	 * Generate the CSS string for the frontend page.
	 *
	 * @return 	string 	The CSS string for the page.
	 * @since 	1.0.0
	 */
	public function css()
	{
		$settings = $this->addon->settings;
		$addon_id    = '#sppb-addon-' . $this->addon->id;
		$cssHelper = new CSSHelper($addon_id);
		$css = '';
		$transformCss = $cssHelper->generateTransformStyle('.sppb-addon-content', $settings, 'transform');
		$css .= $transformCss;

		return $css;
	}

	public static function getTemplate()
	{

		$lodash  = new Lodash('#sppb-addon-{{ data.id }}');

		// Title
		$rewHTMLTypographyFallbacks = [
			'font'           => 'data.title_font_family',
			'size'           => 'data.title_fontsize',
			'line_height'    => 'data.title_lineheight',
			'letter_spacing' => 'data.title_letterspace',
			'uppercase'      => 'data.title_font_style?.uppercase',
			'italic'         => 'data.title_font_style?.italic',
			'underline'      => 'data.title_font_style?.underline',
			'weight'         => 'data.title_font_style?.weight',
		];

		$output = '<style type="text/css">';
		$output .= $lodash->typography('.sppb-addon-raw-html .sppb-addon-title', 'data.title_typography', $rewHTMLTypographyFallbacks);
		$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');
		$output .= $lodash->generateTransformCss('.sppb-addon-content', 'data.transform');
		
		$output .= '</style>';
		$output .= '
			<div class="sppb-addon sppb-addon-raw-html {{ data.class }}">
				<# if( !_.isEmpty( data.title ) ){ #><{{ data.heading_selector }} class="sppb-addon-title sp-inline-editable-element" data-id={{data.id}} data-fieldName="title" contenteditable="true">{{{ data.title }}}</{{ data.heading_selector }}><# } #>
				<div id="builder-raw-html" class="sppb-addon-content sp-inline-editable-element" data-id={{data.id}} data-fieldName="html" contenteditable="true">
					{{{ data.html }}}
				</div>
			</div>
		';

		return $output;
	}
}
