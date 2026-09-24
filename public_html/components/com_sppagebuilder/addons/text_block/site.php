<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Uri\Uri;

//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderAddonText_block extends SppagebuilderAddons
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
		$settings = $this->addon->settings;
		$class = (isset($settings->class) && $settings->class) ? $settings->class : '';
		$title = (isset($settings->title) && $settings->title) ? $settings->title : '';
		$heading_selector = (isset($settings->heading_selector) && $settings->heading_selector) ? $settings->heading_selector : 'h3';

		// Options
		$text = (isset($settings->text) && $settings->text) ? $settings->text : '';
		$dropcap = (isset($settings->dropcap) && $settings->dropcap) ? $settings->dropcap : 0;
		$content_truncation = (isset($settings->content_truncation) && $settings->content_truncation) ? $settings->content_truncation : 0;
		$column_count = (isset($settings->column_count) && $settings->column_count) ? $settings->column_count : '';
		
		$dropcapCls = '';

		if ($dropcap) {
			$dropcapCls = ' sppb-dropcap';
		}

		// Output
		$output = '<div class="sppb-addon sppb-addon-text-block' . $dropcapCls . ' ' . $class . '" >';
		$output .= ($title) ? '<' . $heading_selector . ' class="sppb-addon-title">' . $title . '</' . $heading_selector . '>' : '';
		$output .= '<div class="sppb-addon-content '. ($column_count ? ' sppb-textblock-column ' : ' ') . '">';

		$plain_text = strip_tags($text);
		
		$text_block_text = $text;
  
		if($content_truncation && !empty($settings->content_truncation_max_word) && (int) $this->wordCount($plain_text) > (int) $settings->content_truncation_max_word) {
			$arrayString = explode(' ', $plain_text);
			$text_block_text = implode(' ', array_slice($arrayString, 0, (int) $settings->content_truncation_max_word));
			$text_block_text .= '<template class="sppb-addon-content-full-text">' . $text . '</template>';
			$text_block_text .= '<div class="sppb-btn-container sppb-content-truncation-show"><div role="button" class="sppb-btn-show-more">' . $settings->content_truncation_action_text . '</div></div>';
		}

		$output .= $text_block_text;

		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	private function wordCount($unicode_string) {
		$unicode_string = preg_replace('/[\p{P}\p{N}]/u', '', $unicode_string);

		$unicode_string = preg_replace('/\s+/u', ' ', $unicode_string);

		$words_array = preg_split('/\s+/u', trim($unicode_string), -1, PREG_SPLIT_NO_EMPTY);

		return count($words_array);
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
		$addon_id = '#sppb-addon-' . $this->addon->id;
		$cssHelper = new CSSHelper($addon_id);
		$dropCap = !empty($settings->dropcap);
		$contentTruncation = !empty($settings->content_truncation);

		$settings->alignment = CSSHelper::parseAlignment($settings, 'alignment');

		$css = '';

		$dropcapStyle = $cssHelper->generateStyle('.sppb-dropcap .sppb-addon-content:first-letter, .sppb-dropcap .sppb-addon-content p:first-letter', $settings, ['dropcap_color' => 'color', 'dropcap_font_size' => ['font-size', 'line-height']], ['dropcap_color' => false]);
		
		$columnStyle = $cssHelper->generateStyle('.sppb-textblock-column', $settings, ['column_count' => 'column-count', 'column_gap' => 'column-gap'], ['column_count' => false, 'column_gap' => 'px']);


		$textFontStyle = $cssHelper->typography('.sppb-addon-text-block .sppb-addon-content', $settings, 'text_typography', [
			'font'        => 'text_font_family',
			'size'        => 'text_fontsize',
			'line_height' => 'text_lineheight',
			'weight'      => 'text_fontweight'
		]);

		$typographyOverride = $cssHelper->typography('.sppb-addon-text-block .sppb-addon-content h1,
													.sppb-addon-text-block .sppb-addon-content h2,
													.sppb-addon-text-block .sppb-addon-content h3,
													.sppb-addon-text-block .sppb-addon-content h4,
													.sppb-addon-text-block .sppb-addon-content h5,
													.sppb-addon-text-block .sppb-addon-content h6', $settings, 'text_typography', [
																'font'        => 'text_font_family',
																'size'        => 'text_fontsize',
																'line_height' => 'text_lineheight',
																'weight'      => 'text_fontweight'
															]);

		if ($dropCap) {
			$css .= $dropcapStyle;
		}

		if ($contentTruncation) {
			$contentTruncationStyle = $cssHelper->generateStyle('.sppb-content-truncation-show', $settings, ['content_truncation_action_text_color' => 'color'], false);
			$contentTruncationStyle .= $cssHelper->typography('.sppb-content-truncation-show', $settings, 'content_truncation_action_typography');
			
			$css .= $contentTruncationStyle;
		}

		$css .= $cssHelper->generateStyle('.sppb-addon-text-block', $settings, ['alignment'  => 'text-align'], false);
		$transformCss = $cssHelper->generateTransformStyle('.sppb-addon-text-block', $settings, 'transform');


		$css .= $transformCss;
		$css .= $textFontStyle;
		$css .= $columnStyle;
		$css .= $typographyOverride;
		

		return $css;
	}

	/**
	 * Load external scripts.
	 *
	 * @return 	array
	 * @since 	5.0.0
	 */
	public function scripts()
	{
		return array(Uri::base(true) . '/components/com_sppagebuilder/assets/js/addons/text_block.js');
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
		$output = '
		<#
			var dropcap = "";
			var columnCount = "";

			if (data.column_count && Object.values(data.column_count).some(value => value)) {
    			columnCount = "sppb-textblock-column";
			}

			if(data.dropcap){
				dropcap = "sppb-dropcap";
			}

			if(!data.heading_selector){
				data.heading_selector = "h3";
			}
		#>
		<style type="text/css">';
		// Text
		$textFallbacks = [
			'font'        => 'data.text_font_family',
			'size'        => 'data.text_fontsize',
			'line_height' => 'data.text_lineheight',
			'weight'      => 'data.text_fontweight',
		];
		// Title
		$titleFallbacks = [
			'font'           => 'data.font_family',
			'size'           => 'data.title_fontsize',
			'line_height'    => 'data.title_lineheight',
			'letter_spacing' => 'data.title_letterspace',
			'uppercase'      => 'data.title_font_style?.uppercase',
			'italic'         => 'data.title_font_style?.italic',
			'underline'      => 'data.title_font_style?.underline',
			'weight'         => 'data.title_font_style?.weight',
		];

		$output .= $lodash->alignment('text-align', '.sppb-addon-text-block', 'data.alignment');
		$output .= $lodash->color('color', '.sppb-addon-text-block .sppb-addon-title', 'data.title_text_color');
		$output .= $lodash->color('color', '.sppb-dropcap .sppb-addon-content:first-letter', 'data.dropcap_color');
		$output .= $lodash->unit('font-size', '.sppb-dropcap .sppb-addon-content:first-letter', 'data.dropcap_font_size', 'px');
		$output .= $lodash->unit('line-height', '.sppb-dropcap .sppb-addon-content:first-letter', 'data.dropcap_font_size', 'px');
		$output .= $lodash->typography('.sppb-addon-text-block .sppb-addon-content', 'data.text_typography', $textFallbacks);
		$output .= $lodash->typography('.sppb-addon-text-block .sppb-addon-content h1, .sppb-addon-text-block .sppb-addon-content h2, .sppb-addon-text-block .sppb-addon-content h3, .sppb-addon-text-block .sppb-addon-content h4, .sppb-addon-text-block .sppb-addon-content h5, .sppb-addon-text-block .sppb-addon-content h6', 'data.text_typography', $textFallbacks);

		$output .= $lodash->typography('.sppb-addon-text-block .sppb-addon-title', 'data.title_typography', $titleFallbacks);
		$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');
		$output .= $lodash->generateTransformCss('.sppb-addon-text-block', 'data.transform');

		$output .= $lodash->color('color', '.sppb-content-truncation-show', 'data.content_truncation_action_text_color');
		$output .= $lodash->typography('.sppb-content-truncation-show', 'data.content_truncation_action_typography');

		$output .= $lodash->unit('column-count', '.sppb-textblock-column', 'data.column_count', '');
		$output .= $lodash->unit('column-gap', '.sppb-textblock-column', 'data.column_gap', 'px');

		$output .= '
		</style>
		<div class="sppb-addon sppb-addon-text-block {{ dropcap }} {{ data.class }} {{ columnCount }}" >
			<#
			let heading_selector = data.heading_selector || "h3";
			let content_truncation = data?.content_truncation ?? 0;
			let max_words = data?.content_truncation_max_word ?? data?.text?.length;
			let stripped_content = data?.text?.replace(/<\/?[^>]+(>|$)/g, "") || "";

			if( !_.isEmpty( data.title ) ){ #><{{ heading_selector }} class="sppb-addon-title sp-inline-editable-element" data-id={{data.id}} data-fieldName="title" contenteditable="true">{{{ data.title }}}</{{ heading_selector }}><# } #>
			<div id="addon-text-{{data.id}}" class="sppb-addon-content sp-editable-content" data-id={{data.id}} data-addon="text-block" data-is-truncated={{content_truncation}} data-max-words={{ max_words }} data-full-text="{{data.text}}" data-stripped-text="{{ stripped_content }}" data-action-text="{{data.content_truncation_action_text}}" data-fieldName="text">
			    <# if(!content_truncation || max_words >= stripped_content.split(" ").length || !max_words || max_words == 0) { #>
        			{{{ data.text }}}
    			<# } else { #>
        			<#
					let truncated_content = stripped_content.split(" ").slice(0, max_words).join(" ");
					#>
					{{truncated_content}}
					<div class="sppb-btn-container sppb-content-truncation-show"><div role="button" class="sppb-btn-show-more">{{data.content_truncation_action_text}}</div></div>
    			<# } #>
			</div>
		</div>';
		return $output;
	}
}
