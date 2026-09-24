<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderAddonAudio extends SppagebuilderAddons
{
	/**
	 * The addon frontend render method.
	 * The returned HTML string will render to the frontend page.
	 *
	 * @return  string  The HTML string.
	 * @since   1.0.0
	 */
	public function render()
	{

		$class = (isset($this->addon->settings->class) && $this->addon->settings->class) ? $this->addon->settings->class : '';
		$style = (isset($this->addon->settings->style) && $this->addon->settings->style) ? $this->addon->settings->style : 'panel-default';
		$title = (isset($this->addon->settings->title) && $this->addon->settings->title) ? $this->addon->settings->title : '';
		$heading_selector = (isset($this->addon->settings->heading_selector) && $this->addon->settings->heading_selector) ? $this->addon->settings->heading_selector : 'h3';

		// Addon options
		$mp3_link = (isset($this->addon->settings->mp3_link) && $this->addon->settings->mp3_link) ? $this->addon->settings->mp3_link : '';
		$ogg_link = (isset($this->addon->settings->ogg_link) && $this->addon->settings->ogg_link) ? $this->addon->settings->ogg_link : '';
		$autoplay = (isset($this->addon->settings->autoplay) && $this->addon->settings->autoplay) ? $this->addon->settings->autoplay : 0;
		$repeat = (isset($this->addon->settings->repeat) && $this->addon->settings->repeat) ? $this->addon->settings->repeat : 0;
		$aria_label = (isset($this->addon->settings->aria_label) && $this->addon->settings->aria_label) ? $this->addon->settings->aria_label : '';
		$preload = (isset($this->addon->settings->preload) && $this->addon->settings->preload) ? $this->addon->settings->preload : 'none';

		$output  = '<div class="sppb-addon sppb-addon-audio ' . $class . '">';

		if ($title)
		{
			$output .= '<' . $heading_selector . ' class="sppb-addon-title">' . $title . '</' . $heading_selector . '>';
		}

		$output .= '<div class="sppb-addon-content">';
		$output .= '<audio controls' . ($autoplay ? ' autoplay' : '') . ($repeat ? ' loop' : '') . ' preload="' . $preload . '" aria-label="' . htmlspecialchars($aria_label, ENT_QUOTES, 'UTF-8') . '">';
		$output .= '<source src="' . EditorUtils::stringifyMediaItem($mp3_link) . '" type="audio/mp3">';
		$output .= '<source src="' . EditorUtils::stringifyMediaItem($ogg_link) . '" type="audio/ogg">';
		$output .= 'Your browser does not support the audio element.';
		$output .= '</audio>';
		$output .= '</div>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Generate the CSS string for the frontend page.
	 *
	 * @return 	string 	The CSS string for the page.
	 * @since 	1.0.0
	 */
	public function css(){
		$addon_id = '#sppb-addon-' . $this->addon->id;
		$settings = $this->addon->settings;
		$cssHelper = new CSSHelper($addon_id);
		$css = '';

		$transformCss = $cssHelper->generateTransformStyle('.sppb-addon-content', $settings, 'transform');
		$css .= $transformCss;

		return $css;
	}

	/**
	 * Generate the lodash template string for the frontend editor.
	 *
	 * @return 	string 	The lodash template string.
	 * @since 	1.0.0
	 */
	public static function getTemplate()
	{
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output = '<style type="text/css">';

		// title
		$titleTypographyFallbacks = [
			'font'           => 'data.title_font_family',
			'size'           => 'data.title_fontsize',
			'line_height'    => 'data.title_lineheight',
			'letter_spacing' => 'data.title_letterspace',
			'uppercase'      => 'data.title_font_style?.uppercase',
			'italic'         => 'data.title_font_style?.italic',
			'underline'      => 'data.title_font_style?.underline',
			'weight'         => 'data.title_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-addon-title', 'data.title_typography', $titleTypographyFallbacks);
		$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');
		$output .= $lodash->color('color', '.sppb-addon-title', 'data.title_text_color');
		$output .= $lodash->generateTransformCss('.sppb-addon-content', 'data.transform');
		$output .= '
		</style>
		<div class="sppb-addon sppb-addon-audio {{ data.class }}">
			<# if( !_.isEmpty( data.title ) ){ #><{{ data.heading_selector }} class="sppb-addon-title sp-inline-editable-element" data-id={{data.id}} data-fieldName="title" contenteditable="true">{{ data.title }}</{{ data.heading_selector }}><# } #>
			<div class="sppb-addon-content">
				<audio controls<# if(data.autoplay) { #> autoplay<# } #><# if(data.repeat) { #> loop<# } #>>
					<source src=\'{{ (data.mp3_link && data.mp3_link.src) ? data.mp3_link.src : data.mp3_link }}\' type="audio/mp3">
					<source src=\'{{ (data.ogg_link && data.ogg_link.src) ? data.ogg_link.src : data.ogg_link }}\' type="audio/ogg">
				</audio>
			</div>
		</div>';

		return $output;
	}
}
