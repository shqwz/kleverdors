<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\FileLayout;

class SppagebuilderAddonFeature extends SppagebuilderAddons
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
		list($link, $target) = AddonHelper::parseLink($settings, 'title_url', ['url' => 'title_url', 'new_tab' => 'link_open_new_window']);

		$url_appear = (isset($settings->url_appear) && $settings->url_appear) ? $settings->url_appear : 'title';
		$title_position = (isset($settings->title_position) && $settings->title_position) ? $settings->title_position : 'after';
		$feature_type = (isset($settings->feature_type) && $settings->feature_type) ? $settings->feature_type : 'icon';
		$feature_image = (isset($settings->feature_image) && $settings->feature_image) ? $settings->feature_image : '';
		$feature_image_src = isset($feature_image->src) ? $feature_image->src : $feature_image;
		$feature_image_width = (isset($feature_image->width) && $feature_image->width) ? $feature_image->width : '';
		$feature_image_height = (isset($feature_image->height) && $feature_image->height) ? $feature_image->height : '';
		$icon_name = (isset($settings->icon_name) && $settings->icon_name) ? $settings->icon_name : '';
		$text = (isset($settings->text) && $settings->text) ? $settings->text : '';
		$feature_image_alt = (isset($settings->feature_image_alt) && $settings->feature_image_alt) ? $settings->feature_image_alt : $title;


		// Button options
		$btn_text = (isset($settings->btn_text) && trim($settings->btn_text)) ? $settings->btn_text : '';
		$btn_class = '';
		$btn_class .= (isset($settings->btn_type) && $settings->btn_type) ? ' sppb-btn-' . $settings->btn_type : '';
		$btn_class .= (isset($settings->btn_size) && $settings->btn_size) ? ' sppb-btn-' . $settings->btn_size : '';
		$btn_class .= (isset($settings->btn_shape) && $settings->btn_shape) ? ' sppb-btn-' . $settings->btn_shape : ' sppb-btn-rounded';
		$btn_class .= (isset($settings->btn_appearance) && $settings->btn_appearance) ? ' sppb-btn-' . $settings->btn_appearance : '';
		$btn_class .= (isset($settings->btn_block) && $settings->btn_block) ? ' ' . $settings->btn_block : '';

		$btn_icon = (isset($settings->btn_icon) && $settings->btn_icon) ? $settings->btn_icon : '';
		$btn_icon_position = (isset($settings->btn_icon_position) && $settings->btn_icon_position) ? $settings->btn_icon_position : 'left';

		list($btn_link, $btn_link_target) = AddonHelper::parseLink($settings, 'btn_url', ['url' => 'btn_url', 'new_tab' => 'btn_target']);


		$attribs = (isset($btn_link_target) && $btn_link_target) ? $btn_link_target : '';
		$attribs .= (!empty($btn_link)) ? ' href="' . $btn_link . '"' : '';
		$attribs .= ' id="btn-' . $this->addon->id . '"';

		$attribs .= !empty($settings->btn_aria_label)  ? ' aria-label="' . $settings->btn_aria_label . '"' : '';

		$btn_aria_label = !empty($settings->btn_aria_label) ? $settings->btn_aria_label : '';

		$visually_hidden_text = $btn_aria_label ? '<span class="sppb-visually-hidden">' . $btn_aria_label . '' : '';

		$icon_arr = array_filter(explode(' ', $btn_icon));

		if (count($icon_arr) === 1) {
			$btn_icon = 'fas ' . $btn_icon;
		}

		if ($btn_icon_position === 'left') {
			$btn_text = ($btn_icon) ? '<i class="' . $btn_icon . '" aria-hidden="true"></i> ' . $btn_text : $btn_text;
		} else {
			$btn_text = ($btn_icon) ? $btn_text . ' <i class="' . $btn_icon . '" aria-hidden="true"></i>' : $btn_text;
		}


				// Second Button options
				$is_second_button = (isset($settings->is_second_button) && $settings->is_second_button) ? $settings->is_second_button : '';
				$second_btn_text = (isset($settings->second_btn_text) && trim($settings->second_btn_text)) ? $settings->second_btn_text : '';
				$second_btn_class = '';
				$second_btn_class .= isset($settings->second_btn_type) ? ' sppb-btn-' . (!empty($settings->second_btn_type) ? $settings->second_btn_type : 'custom') : '';
				$second_btn_class .= (isset($settings->second_btn_size) && $settings->second_btn_size) ? ' sppb-btn-' . $settings->second_btn_size : '';
				$second_btn_class .= (isset($settings->second_btn_shape) && $settings->second_btn_shape) ? ' sppb-btn-' . $settings->second_btn_shape : ' sppb-btn-rounded';
				$second_btn_class .= (isset($settings->second_btn_appearance) && $settings->second_btn_appearance) ? ' sppb-btn-' . $settings->second_btn_appearance : '';
				$second_btn_class .= (isset($settings->second_btn_block) && $settings->second_btn_block) ? ' ' . $settings->second_btn_block : '';
		
				$second_btn_icon = (isset($settings->second_btn_icon) && $settings->second_btn_icon) ? $settings->second_btn_icon : '';
				$second_btn_icon_position = (isset($settings->second_btn_icon_position) && $settings->second_btn_icon_position) ? $settings->second_btn_icon_position : 'left';
		
				list($second_btn_link, $second_btn_link_target) = AddonHelper::parseLink($settings, 'second_btn_url', ['url' => 'second_btn_url', 'new_tab' => 'second_btn_target']);
		
		
				$second_attribs = (isset($second_btn_link_target) && $second_btn_link_target) ? $second_btn_link_target : '';
				$second_attribs .= (!empty($second_btn_link)) ? ' href="' . $second_btn_link . '"' : '';
				$second_attribs .= ' id="btn-' . $this->addon->id . '-2' . '"';
		
				$second_attribs .= !empty($settings->second_btn_aria_label)  ? ' aria-label="' . $settings->second_btn_aria_label . '"' : '';
		
				$second_btn_aria_label = !empty($settings->second_btn_aria_label) ? $settings->second_btn_aria_label : '';
		
				$second_visually_hidden_text = $second_btn_aria_label ? '<span class="sppb-visually-hidden">' . $second_btn_aria_label . '' : '';
		
				$second_icon_arr = array_filter(explode(' ', $second_btn_icon));
		
				if (count($second_icon_arr) === 1) {
					$second_btn_icon = 'fas ' . $second_btn_icon;
				}
		
				if ($second_btn_icon_position === 'left') {
					$second_btn_text = ($second_btn_icon) ? '<i class="' . $second_btn_icon . '" aria-hidden="true"></i> ' . $second_btn_text : $second_btn_text;
				} else {
					$second_btn_text = ($second_btn_icon) ? $second_btn_text . ' <i class="' . $second_btn_icon . '" aria-hidden="true"></i>' : $second_btn_text;
				}


		if (strpos($feature_image_src, "http://") !== false || strpos($feature_image_src, "https://") !== false) {
			$feature_image_src = $feature_image_src;
		} elseif ($feature_image_src) {
			$feature_image_src = Uri::base(true) . '/' . $feature_image_src;
		}

		// Lazy image loading
		$placeholder = $feature_image_src === '' ? false : $this->get_image_placeholder($feature_image_src);

		// Image or icon position
		$icon_image_position = '';

		// if ($title_position === 'before') {
		// 	$icon_image_position = 'after';
		// } else if ($title_position === 'after') {
		// 	$icon_image_position = 'before';
		// } else {
			$icon_image_position = isset($settings->title_position_original) ? $settings->title_position_original : $title_position;
		// }

		// Reset Alignment for left and right style
		$alignment = '';

		if (($icon_image_position === 'left') || ($icon_image_position === 'right')) {
			$alignment = 'sppb-text-' . $icon_image_position;
		}

		// Icon or Image
		$media = '';

		if ($feature_type === 'icon') {
			if ($icon_name) {
				$media  .= '<div class="sppb-icon">';

				if (($link && $url_appear == 'icon') || ($link && $url_appear == 'both')) {
					$media .= '<a href="' . $link . '" ' . $target . '>';
				}

				$media  .= '<span class="sppb-icon-container" role="img" aria-label="' . strip_tags($title) . '">';

				$icon_arr = array_filter(explode(' ', $icon_name));

				if (count($icon_arr) === 1) {
					$icon_name = 'fa ' . $icon_name;
				}

				$media  .= '<i class="' . $icon_name . '" aria-hidden="true"></i>';
				$media  .= '</span>';

				if (($link && $url_appear == 'icon') || ($link && $url_appear == 'both')) {
					$media .= '</a>';
				}

				$media  .= '</div>';
			}
		} else {
			if ($feature_image_src) {
				$media  .= '<span class="sppb-img-container">';

				if (($link && $url_appear == 'icon') || ($link && $url_appear == 'both')) {
					$media .= '<a href="' . $link . '" ' . $target . '>';
				}

				$media  .= '<img class="sppb-img-responsive' . ($placeholder ? ' sppb-element-lazy' : '') . '" style="display: inline-block" src="' . ($placeholder ? $placeholder : $feature_image_src) . '" alt="' . strip_tags($feature_image_alt) . '" ' . ($placeholder ? 'data-large="' . $feature_image_src . '"' : '') . ' ' . ($feature_image_width ? 'width="' . $feature_image_width . '"' : '') . ' ' . ($feature_image_height ? 'height="' . $feature_image_height . '"' : '') . ' loading="lazy">';

				if (($link && $url_appear == 'icon') || ($link && $url_appear == 'both')) {
					$media .= '</a>';
				}

				$media  .= '</span>';
			}
		}


		if ($feature_type === "both" && $icon_name) {
			$media  .= '<div class="sppb-icon">';

			if (($link && $url_appear == 'icon') || ($link && $url_appear == 'both')) {
				$media .= '<a href="' . $link . '" ' . $target . '>';
			}

			$media  .= '<span class="sppb-icon-container" role="img" aria-label="' . strip_tags($title) . '">';

			$icon_arr = array_filter(explode(' ', $icon_name));

			if (count($icon_arr) === 1) {
				$icon_name = 'fa ' . $icon_name;
			}

			$media  .= '<i class="' . $icon_name . '" aria-hidden="true"></i>';
			$media  .= '</span>';

			if (($link && $url_appear == 'icon') || ($link && $url_appear == 'both')) {
				$media .= '</a>';
			}

			$media  .= '</div>';
		}

		// Title
		$feature_title = '';

		if ($title) {
			$heading_class = '';

			if (\in_array($icon_image_position, ['left', 'right'])) {
				$heading_class = ' sppb-media-heading';
			}

			$feature_title .= '<' . $heading_selector . ' class="sppb-addon-title sppb-feature-box-title' . $heading_class . '">';

			if (($link && $url_appear === 'title') || ($link && $url_appear === 'both')) {
				$feature_title .= '<a href="' . $link . '" ' . $target . '>';
			}

			$feature_title .= $title;

			if (($link && $url_appear === 'title') || ($link && $url_appear == 'both')) {
				$feature_title .= '</a>';
			}

			$feature_title .= '</' . $heading_selector . '>';
		}

		// Feature Text
		$feature_text = '';
		if(!empty($text)) {
			$feature_text  = '<div class="sppb-addon-text">';
			$feature_text .= $text;
			$feature_text .= '</div>';
		}

		// Output
		$output = '';

		$feature_layout_path = JPATH_ROOT . '/components/com_sppagebuilder/layouts';
		$content_path = new FileLayout('addon.html.feature', $feature_layout_path);

		$imageProps = [
			'feature_image_margin' => 'margin',

		];
		$imageUnits = [
			'feature_image_margin' => false,

		];

		$output .= $content_path->render(array('icon_image_position' => $icon_image_position, 'media' => $media, 'title' => $title, 'feature_title' => $feature_title, 'visually_hidden_text' => $visually_hidden_text, 'second_visually_hidden_text' => $second_visually_hidden_text, 'attribs' => $attribs, 'second_attribs' => $second_attribs, 'btn_class' => $btn_class, 'second_btn_class' => $second_btn_class, 'btn_text' => $btn_text, 'second_btn_text' => $second_btn_text, 'feature_text' => $feature_text, 'is_second_button' => $is_second_button, 'addon_id' => '#sppb-addon-' . $this->addon->id, 'class' => $class, 'settings' => $settings, 'imageProps' => $imageProps, 'imageUnits' => $imageUnits));

		return $output;
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

		$feature_type = (isset($settings->feature_type) && $settings->feature_type) ? $settings->feature_type : 'icon';
		$feature_image = (isset($settings->feature_image) && $settings->feature_image) ? $settings->feature_image : '';
		$feature_image_src = isset($feature_image->src) ? $feature_image->src : $feature_image;
		$icon_name = (isset($settings->icon_name) && $settings->icon_name) ? $settings->icon_name : '';
		$title_position = (isset($settings->title_position) && $settings->title_position) ? $settings->title_position : '';

		// Css start
		$css = '';

		$addonProps = ['background_color' => 'background-color'];
		$addonBgStyle = $cssHelper->generateStyle(':self', $settings, $addonProps, "");
		$css .= $addonBgStyle;


		$css .= $cssHelper->generateStyle('.sppb-addon-text', $settings, ['addon_color' => 'color'], false);


		$textProps = ['text_background' => 'background-color', 'text_padding' => 'padding'];
		$textUnits = ['text_background' => false, 'text_padding' => false];
		$textModifiers = ['text_padding' => 'spacing'];

		$textStyle = $cssHelper->generateStyle('.sppb-media-content', $settings, $textProps, $textUnits, $textModifiers);
		$css .= $textStyle;

		$contentFallbacks = [
			'font'           => 'text_font_family',
			'size'           => 'text_fontsize',
			'line_height' 	 => 'text_lineheight',
			'weight'         => 'text_fontweight',
		];

		$contentTypography = $cssHelper->typography('.sppb-addon-text', $settings, 'content_typography', $contentFallbacks);
		$css .= $contentTypography;

		$titleFallbacks = [
			'font'           => 'title_font_family',
			'size'           => 'title_fontsize',
			'line_height'    => 'title_lineheight',
			'letter_spacing' => 'title_letterspace',
			'uppercase'      => 'title_font_style.uppercase',
			'italic'         => 'title_font_style.italic',
			'underline'      => 'title_font_style.underline',
			'weight'         => 'title_font_style.weight',
		];

		$textTitleTypography = $cssHelper->typography('.sppb-feature-box-title', $settings, 'title_typography', $titleFallbacks);
		$css .= $textTitleTypography;


		if ($feature_type === 'icon' || $feature_type === 'both') {
			if (!empty($icon_name)) {
				$settings->icon_boxshadow = CSSHelper::parseBoxShadow($settings, 'icon_boxshadow');

				$props = [
					'icon_boxshadow'     => 'box-shadow',
					'icon_padding'       => 'padding',
					'icon_color'         => 'color',
					'icon_background'    => 'background-color',
					'icon_border_color'  => 'border-style: solid; border-color',
					'icon_border_width'  => 'border-width',
					'icon_border_radius' => 'border-radius'
				];
				$units = [
					'icon_boxshadow'    => false,
					'icon_padding'      => false,
					'icon_color'        => false,
					'icon_border_color' => false,
					'icon_background'   => false,
				];
				$modifiers = ['icon_padding' => 'spacing'];
				$iconStyle = $cssHelper->generateStyle('.sppb-icon .sppb-icon-container', $settings, $props, $units, $modifiers, null, false, 'display:inline-block;text-align:center;');
				$iconMarginStyle = $cssHelper->generateStyle('.sppb-icon', $settings, ['icon_margin_top' => 'margin-top', 'icon_margin_bottom' => 'margin-bottom']);
				$iconSizeStyle = $cssHelper->generateStyle('.sppb-icon .sppb-icon-container > i', $settings, ['icon_size' => ['font-size','width','height','line-height']]);


				$css .= $iconStyle;
				$css .= $iconMarginStyle;
				$css .= $iconSizeStyle;
			}
		}

		$settings->text_alignment = $cssHelper->parseAlignment($settings, 'alignment');
		$textAlignment = $cssHelper->generateStyle('.sppb-addon-content', $settings, ['text_alignment' => 'text-align'], false);
		$css .= $textAlignment;
		
		if ($feature_image_src && ($feature_type === 'image' || $feature_type === 'both')) {
			$imageMarginSelector = '';
			$isContainer = false;
			$imageProps = [
				'feature_image_margin' => 'margin',

			];
			$imageUnits = [
				'feature_image_margin' => false,

			];

			if (\in_array($title_position, ['left', 'right'])) {
				$imageMarginSelector = '.sppb-media .pull-left, .sppb-media .pull-right';
				$css .= $cssHelper->generateStyle($imageMarginSelector, $settings, ['feature_image_width' => 'width'], ['feature_image_width' => '%']);
			} elseif (\in_array($title_position, ['before', 'after'])) {
				$imageMarginSelector = '.sppb-img-container';
				$isContainer = true;
			}

			if (!empty($imageMarginSelector)) {
				$featureImageStyle = $cssHelper->generateStyle($imageMarginSelector, $settings, $imageProps, $imageUnits, ['feature_image_margin' => 'spacing'], null, false, $isContainer ? 'display: block;' : '');
			}

			$device = SpPgaeBuilderBase::$defaultDevice;

			if (!empty($settings->feature_image_width->$device) && $settings->feature_image_width->$device === '100') {
				$mediaBodyStyle = $cssHelper->generateStyle('.sppb-media .sppb-media-body', $settings, ['feature_image_width' => 'width'], '%');
			}

			$imageHoverStyle = '';

			$isZoomOnScale = isset($settings->is_zoom_enabled) ? $settings->is_zoom_enabled : 0;
			$zoomScale = isset($settings->zoom_scale) ? $settings->zoom_scale : 1.2;

			if($isZoomOnScale && $zoomScale) {
				$settings->container_overflow = 'hidden';
				$settings->container_display = 'inline-block';
				$settings->zoom_scale_transition = 'transform 0.5s ease';
	
				if (!empty($settings->image_transform)) {
					$settings->transform->scale = (object) [
						'x' => $zoomScale,
						'y' => $zoomScale
					];
				} else {
					$settings->image_transform = (object) [
						'scale' => (object) [
							'x' => $zoomScale,
							'y' => $zoomScale
						]
					];
				}
	
				$imageHoverStyle .= $cssHelper->generateStyle(
					'.sppb-img-container',
					$settings,
					['container_overflow' => 'overflow',
					'container_display' => 'display'],
					false
				);
				$imageHoverStyle .= $cssHelper->generateStyle(
					'.sppb-img-container img',
					$settings,
					['zoom_scale_transition' => 'transition'],
					false
				);
				$imageHoverStyle .= $cssHelper->generateTransformStyle(
					'.sppb-img-container:hover img',
					$settings,
					'image_transform',
				);
				$imageHoverStyle .= $cssHelper->generateStyle(
					'.sppb-img-container:hover img',
					$settings,
					['image_radius' => 'border-radius'],
				);

				if(isset($settings->preserve_border_radius) && $settings->preserve_border_radius) {
					$settings->image_radius = !empty($settings->image_radius) ? $settings->image_radius : 0;
					$imageHoverStyle .= $cssHelper->generateStyle(
						'.sppb-img-container',
						$settings,
						['image_radius' => 'border-radius'],
					);
					$imageHoverStyle .= $cssHelper->generateStyle(
						'.sppb-img-container img',
						[],[],[],[],[],[], 'width: 100%;'
					);
				}
			}

			$settings->image_radius = !empty($settings->image_radius) ? $settings->image_radius : 0;

			$imageRadiusStyle = $cssHelper->generateStyle('.sppb-img-container img', $settings, ['image_radius' => 'border-radius']);

			$css .= !empty($featureImageStyle) ? $featureImageStyle : '';
			$css .= !empty($mediaBodyStyle) ? $mediaBodyStyle : '';
			$css .= !empty($imageRadiusStyle) ? $imageRadiusStyle : '';
			$css .= !empty($imageHoverStyle) ? $imageHoverStyle : '';
		}

		//Button style
		$layout_path = JPATH_ROOT . '/components/com_sppagebuilder/layouts';
		$css_path = new FileLayout('addon.css.button', $layout_path);
		$options = new stdClass;
		$options->button_type = (isset($settings->btn_type) && $settings->btn_type) ? $settings->btn_type : '';
		$options->button_appearance = (isset($settings->btn_appearance) && $settings->btn_appearance) ? $settings->btn_appearance : '';
		$options->button_color = (isset($settings->btn_color) && $settings->btn_color) ? $settings->btn_color : '';
		$options->button_color_hover = (isset($settings->btn_color_hover) && $settings->btn_color_hover) ? $settings->btn_color_hover : '';
		$options->button_background_color = (isset($settings->btn_background_color) && $settings->btn_background_color) ? $settings->btn_background_color : '';
		$options->button_background_color_hover = (isset($settings->btn_background_color_hover) && $settings->btn_background_color_hover) ? $settings->btn_background_color_hover : '';
		$options->button_font_style = (isset($settings->btn_font_style) && $settings->btn_font_style) ? $settings->btn_font_style : '';
		if (isset($settings->button_padding_original))
		{
			$options->button_padding = $settings->button_padding_original;
		}
		elseif (isset($settings->button_padding))
		{
			$options->button_padding = $settings->button_padding;
		}
		$options->fontsize = (isset($settings->btn_fontsize) && $settings->btn_fontsize) ? $settings->btn_fontsize : '';
		$options->button_background_gradient = (isset($settings->btn_background_gradient) && $settings->btn_background_gradient) ? $settings->btn_background_gradient : new stdClass();
		$options->button_background_gradient_hover = (isset($settings->btn_background_gradient_hover) && $settings->btn_background_gradient_hover) ? $settings->btn_background_gradient_hover : new stdClass();
		$options->button_size = !empty($settings->btn_size) ? $settings->btn_size : null;
		$options->button_typography = !empty($settings->btn_typography) ? $settings->btn_typography : null;

		$buttonMarginStyle = $cssHelper->generateStyle('.sppb-media-content .sppb-btn', $settings, ['button_margin' => 'margin'], false, ['button_margin' => 'spacing']);
		$css .= $buttonMarginStyle;

		$css .= $css_path->render(array('addon_id' => $addon_id, 'options' => $options, 'id' => 'btn-' . $this->addon->id));



		$options2 = new stdClass;
		$options2->button_type = (isset($settings->second_btn_type) && $settings->second_btn_type) ? $settings->second_btn_type : 'custom';
		$options2->button_appearance = (isset($settings->second_btn_appearance) && $settings->second_btn_appearance) ? $settings->second_btn_appearance : '';
		$options2->button_color = (isset($settings->second_btn_color) && $settings->second_btn_color) ? $settings->second_btn_color : '';
		$options2->button_color_hover = (isset($settings->second_btn_color_hover) && $settings->second_btn_color_hover) ? $settings->second_btn_color_hover : '';
		$options2->button_background_color = (isset($settings->second_btn_background_color) && $settings->second_btn_background_color) ? $settings->second_btn_background_color : '';
		$options2->button_background_color_hover = (isset($settings->second_btn_background_color_hover) && $settings->second_btn_background_color_hover) ? $settings->second_btn_background_color_hover : '';
		$options2->button_font_style = (isset($settings->second_btn_font_style) && $settings->second_btn_font_style) ? $settings->second_btn_font_style : '';
		if (isset($settings->second_button_padding_original))
		{
			$options2->button_padding = $settings->second_button_padding_original;
		}
		elseif (isset($settings->second_button_padding))
		{
			$options2->button_padding = $settings->second_button_padding;
		}
		$options2->fontsize = (isset($settings->second_btn_fontsize) && $settings->second_btn_fontsize) ? $settings->second_btn_fontsize : '';
		$options2->button_background_gradient = (isset($settings->second_btn_background_gradient) && $settings->second_btn_background_gradient) ? $settings->btn_background_gradient : new stdClass();
		$options2->button_background_gradient_hover = (isset($settings->second_btn_background_gradient_hover) && $settings->second_btn_background_gradient_hover) ? $settings->second_btn_background_gradient_hover : new stdClass();
		$options2->button_size = !empty($settings->second_btn_size) ? $settings->second_btn_size : null;
		$options2->button_typography = !empty($settings->second_btn_typography) ? $settings->second_btn_typography : null;

		$secondButtonMarginStyle = $cssHelper->generateStyle('.sppb-media-content .sppb-btn-2', $settings, ['second_button_margin' => 'margin'], false);

		$css .= $secondButtonMarginStyle;

		$css .= $css_path->render(array('addon_id' => $addon_id, 'options' => $options2, 'id' => 'btn-' . $this->addon->id . '-2'));


		$settings->addon_hover_boxshadow = CSSHelper::parseBoxShadow($settings, 'addon_hover_boxshadow');
		$settings->addon_mock_transition = '.3s';

		$hoverProps = [
			'addon_hover_bg'        => 'background-color',
			'addon_hover_color'     => 'color',
			'addon_hover_boxshadow' => 'box-shadow'
		];
		$addonTransitionStyle = $cssHelper->generateStyle(':self', $settings, ['addon_mock_transition' => 'transition'], false);
		$addonHoverStyle = $cssHelper->generateStyle('&:hover', $settings, $hoverProps, false);

		if(empty($settings->text)) {
			$css .= $addon_id . ' .sppb-addon-title.sppb-feature-box-title {display: block;}';
		}
		$css .= $cssHelper->generateStyle('.sppb-feature-box-title, .sppb-feature-box-title a', $settings, ['title_text_color' => 'color'], false);
		$css .= $cssHelper->generateStyle('&:hover .sppb-feature-box-title, &:hover .sppb-feature-box-title a', $settings, ['title_hover_color' => 'color'], false);
		$css .= $cssHelper->generateStyle('&:hover .sppb-addon-text', $settings, ['addon_hover_color' => 'color'], false);
		$css .= $cssHelper->generateStyle('&:hover .sppb-icon-container', $settings, ['icon_hover_color' => 'color', 'icon_hover_bg' => 'background-color'], false);
		$transformCss = $cssHelper->generateTransformStyle(':self', $settings, 'transform');


		$css .= $addonTransitionStyle;
		$css .= $addonHoverStyle;
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
		$output = '
		<#
			let classList = " sppb-btn-" + data.btn_type;
			classList += " sppb-btn-" + data.btn_size;
			classList += " sppb-btn-" + data.btn_shape;

			if (!_.isEmpty(data.btn_appearance)) {
				classList += " sppb-btn-"+data.btn_appearance;
			}

			let icon_arr = (typeof data.icon_name !== "undefined" && data.icon_name) ? data.icon_name.split(" ") : "";
			let icon_name = icon_arr.length === 1 ? "fa "+data.icon_name : data.icon_name;

			let icon_image_position = "";
			if (data.title_position == "before") {
				icon_image_position = "after";
			} else if(data.title_position == "after") {
				icon_image_position = "before";
			} else {
				icon_image_position = data.title_position;
			}

            let alignment = "";
			if( ( icon_image_position == "left" ) || ( icon_image_position == "right" ) ) {
				alignment = "sppb-text-" + icon_image_position;
			}

			let media = "";
			const isMenu = _.isString(data.title_url?.menu) && data.title_url.type === "menu" && data.title_url?.menu;
			const isPage = _.isString(data.title_url?.page) && data.title_url.type === "page" && data.title_url?.page;

			const isObject = _.isObject(data.title_url) && ((data.title_url.type === "url" && data.title_url?.url !== "") ||  isMenu || isPage);
			const isString = _.isString(data.title_url) && data.title_url !== "";
			
			let urlObj = {};
			let url = "";
			let target = "";
			let rel = "";
			
			
			if(isObject || isString) {
				urlObj = _.isObject(data.title_url) ? data.title_url : window.getSiteUrl(data.title_url, data.link_open_new_window === 1 ? "_blank": "");
				if(urlObj.type === "url") {	
					url = urlObj.url;
				} else if(urlObj.type === "menu") {
					url = urlObj.menu;
				} else if(urlObj.type === "page") {
					url = `index.php?option=com_sppagebuilder&view=page&id=${urlObj.page}`
				}
				target = urlObj.new_tab ? "_blank": "";
				rel += urlObj.nofollow ? "nofollow": "";
				rel += urlObj.noopener ? " noopener": "";
				rel += urlObj.noreferrer ? " noreferrer": "";

			}

			if (data.feature_type == "icon") {
				if (data.icon_name) {
					media += \'<div class="sppb-icon">\';
						if( ((isObject || isString) && data.url_appear == "icon") || ((isObject || isString) && data.url_appear == "both" ) ){
							media += \'<a href="\'+ url +\'" target="\' + target + \'" rel="\' + rel + \'" >\';
						}
						media  += \'<span class="sppb-icon-container">\';
							media  += \'<i class="\'+icon_name+\'"></i>\';
						media  += \'</span>\';
						if( ((isObject || isString) && data.url_appear == "icon") || ((isObject || isString) && data.url_appear == "both" ) ){
							media += \'</a>\';
						}
					media += \'</div>\';
				}
			} else {
				var feature_image = {}
				if (typeof data.feature_image !== "undefined" && typeof data.feature_image.src !== "undefined") {
					feature_image = data.feature_image
				} else {
					feature_image = {src: data.feature_image}
				}

				if (feature_image.src) {
					media  += \'<span class="sppb-img-container">\';
					if ( ((isObject || isString) && data.url_appear == "icon") || ((isObject || isString) && data.url_appear == "both" ) ) {
						media += \'<a href="\'+ url +\'" target="\' + target + \'" rel="\' + rel + \'">\';
					}

					if (feature_image.src.indexOf("http://") != -1 || feature_image.src.indexOf("https://") != -1) {
						media  += \'<img class="sppb-img-responsive" style="display: inline-block" src="\'+feature_image.src+\'" \'+"alt"+\'="\'+data.title+\'">\';
					} else {
						media  += \'<img class="sppb-img-responsive" style="display: inline-block" src="\'+pagebuilder_base+feature_image.src+\'" \'+"alt"+\'="\'+data.title+\'">\';
					}

					if ( ((isObject || isString) && data.url_appear == "icon") || ((isObject || isString) && data.url_appear == "both" ) ) {
						media += \'</a>\';
					}

					media  += \'</span>\';
				}
            } 
				

			if (data.feature_type == "both" && data.icon_name) {
				media += \'<div class="sppb-icon">\';
						if( ((isObject || isString) && data.url_appear == "icon") || ((isObject || isString) && data.url_appear == "both" ) ){
							media += \'<a href="\'+ url +\'" target="\' + target + \'" rel="\' + rel + \'" >\';
						}
						media  += \'<span class="sppb-icon-container">\';
							media  += \'<i class="\'+icon_name+\'"></i>\';
						media  += \'</span>\';
						if( ((isObject || isString) && data.url_appear == "icon") || ((isObject || isString) && data.url_appear == "both" ) ){
							media += \'</a>\';
						}
					media += \'</div>\';
			}

			let feature_title = "";
			if (data.title) {
				var heading_class = "";
				if ( ( icon_image_position == "left" ) || ( icon_image_position == "right" ) ) {
					heading_class = " sppb-media-heading";
				}

				let heading_selector = data.heading_selector || "h3";

				feature_title += \'<\'+heading_selector+\' class="sppb-addon-title sppb-feature-box-title  \'+heading_class+\'">\';
				if (((isObject || isString) && data.url_appear == "title") || ((isObject || isString) && data.url_appear == "both" )) {
					feature_title += \'<a href="\'+ url +\'" target="\' + target + \'" rel="\' + rel + \'" class="sp-inline-editable-element" data-id="\'+data.id+\'" data-fieldName="title" contenteditable="true">\';
				}

				if (_.isEmpty(data.title_url)) {
					feature_title += \'<span class="sp-inline-editable-element" data-id="\'+data.id+\'" data-fieldName="title" contenteditable="true">\';
				}

				feature_title += data.title;

				if (_.isEmpty(data.title_url)) {
					feature_title += \'</span>\';
				}

				if (((isObject || isString) && data.url_appear == "title") || ((isObject || isString) && data.url_appear == "both")) {
					feature_title += \'</a>\';
				}

				feature_title += \'</\'+heading_selector+\'>\';
			}

			let feature_text = "";
			if(data.text) {
				feature_text  = \'<div id="addon-text-\'+data.id+\'" class="sppb-addon-text sp-editable-content" data-id="\'+data.id+\'" data-fieldName="text">\';
				feature_text += data.text;
				feature_text += \'</div>\';	
			}

			data.image_radius = data.image_radius || 0;

			let zoom_scale = data.zoom_scale || 0;
			let transform = data.transform || {};

			let transform_zoom = { ...transform, scale: {x: zoom_scale, y: zoom_scale} }

				data.second_btn_type = _.isEmpty(data.second_btn_type) ? "custom" : data.second_btn_type;

				if (data.btn_type == "custom") {
					if (_.isEmpty(data.second_btn_color)) {
						data.second_btn_color = "#224faa";
					}
					if (_.isEmpty(data.second_btn_color_hover)) {
						data.second_btn_color_hover = "#224faa";
					}
					
					if (_.isEmpty(data.second_btn_background_color)) {
						data.second_btn_background_color = "unset";
					}
					if (_.isEmpty(data.second_btn_background_color_hover)) {
						data.second_btn_background_color_hover = "unset";
					}
				}
		#>

		<style type="text/css">';

		// button start
		$buttonTypographyFallbacks = [
			'font'           => 'data.btn_font_family',
			'size'           => 'data.btn_fontsize',
			'letter_spacing' => 'data.btn_letterspace',
			'uppercase'      => 'data.btn_font_style?.uppercase',
			'italic'         => 'data.btn_font_style?.italic',
			'underline'      => 'data.btn_font_style?.underline',
			'weight'         => 'data.btn_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-btn', 'data.btn_typography', $buttonTypographyFallbacks);
		$output .= '<# if (data.btn_size == "custom") { #>';
		$output .= $lodash->spacing('padding', '.sppb-btn', 'data.button_padding');
		$output .= '<# } #>';
		$output .= $lodash->spacing('margin', '.sppb-btn', 'data.button_margin');
		$output .= '<# if (data.btn_block == "sppb-btn-block") { #>';
		$output .= '#sppb-addon-{{ data.id }} .sppb-btn {display: block;}';
		$output .= '<# } #>';

		$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');

		// custom style
		$output .= '<# if (data.btn_type == "custom") { #>';
		$output .= $lodash->color('color', '.sppb-btn', 'data.btn_color');
		$output .= $lodash->color('color', '.sppb-btn:hover', 'data.btn_color_hover');

		$output .= '<# if (data.btn_appearance == "outline") { #>';
		$output .= '#sppb-addon-{{ data.id }} .sppb-btn {background-color: transparent;}';
		$output .= $lodash->unit('border-color', '.sppb-btn', 'data.btn_background_color', '', false);
		$output .= $lodash->unit('border-color', '.sppb-btn:hover', 'data.btn_background_color_hover', '', false);
		$output .= $lodash->unit('background-color', '.sppb-btn:hover', 'data.btn_background_color_hover', '', false);
		$output .= '<# } else if (data.btn_appearance == "gradient") { #>';
		$output .= '#sppb-addon-{{ data.id }} .sppb-btn {border: none;}';
		$output .= $lodash->color('background-color', '.sppb-btn', 'data.btn_background_gradient');
		$output .= $lodash->color('background-color', '.sppb-btn:hover', 'data.btn_background_gradient_hover');
		$output .= '<# } else { #>';
		$output .= $lodash->color('background-color', '.sppb-btn', 'data.btn_background_color');
		$output .= $lodash->color('background-color', '.sppb-btn:hover', 'data.btn_background_color_hover');
		$output .= '<# } #>';
		$output .= '<# } #>';

		// link
		$output .= '<# if (data.btn_type == "link") { #>';
		$output .= '#sppb-addon-{{ data.id }} .sppb-btn {padding: 0; border-width: 0; text-decoration: none; border-radius: 0;}';
		$output .= $lodash->color('color', '.sppb-btn', 'data.link_btn_color');
		$output .= $lodash->unit('border-color', '.sppb-btn', 'data.link_btn_border_color', '', false);
		$output .= $lodash->unit('border-bottom-width', '.sppb-btn', 'data.link_btn_border_width', 'px');
		$output .= $lodash->unit('padding-bottom', '.sppb-btn', 'data.link_btn_padding_bottom', 'px');
		$output .= $lodash->color('color', '.sppb-btn:hover, .sppb-btn:focus', 'data.link_btn_hover_color');
		$output .= $lodash->unit('border-color', '.sppb-btn:hover, .sppb-btn:focus', 'data.link_btn_border_hover_color', '', false);
		$output .= '<# } #>';
		// button end

				// second button start
				$secondButtonTypographyFallbacks = [
					'font'           => 'data.second_btn_font_family',
					'size'           => 'data.second_btn_fontsize',
					'letter_spacing' => 'data.second_btn_letterspace',
					'uppercase'      => 'data.second_btn_font_style?.uppercase',
					'italic'         => 'data.second_btn_font_style?.italic',
					'underline'      => 'data.second_btn_font_style?.underline',
					'weight'         => 'data.second_btn_font_style?.weight'
				];
				$output .= $lodash->typography('.sppb-btn-2', 'data.second_btn_typography', $secondButtonTypographyFallbacks);
				$output .= '<# if (data.second_btn_size == "custom") { #>';
				$output .= $lodash->spacing('padding', '.sppb-btn-2', 'data.second_button_padding');
				$output .= '<# } #>';
				$output .= $lodash->spacing('margin', '.sppb-btn-2', 'data.second_button_margin');
				$output .= '<# if (data.second_btn_block == "sppb-btn-block") { #>';
				$output .= '#sppb-addon-{{ data.id }}-2 .sppb-btn-2 {display: block;}';
				$output .= '<# } #>';
		
				$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
				$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');
		
				// custom style
				$output .= '<# if (data.second_btn_type == "custom") { #>';
				$output .= $lodash->color('color', '.sppb-btn-2', 'data.second_btn_color');
				$output .= $lodash->color('color', '.sppb-btn-2:hover', 'data.second_btn_color_hover');
		
				$output .= '<# if (data.second_btn_appearance == "outline") { #>';
				$output .= '#sppb-addon-{{ data.id }}-2 .sppb-btn-2 {background-color: transparent;}';
				$output .= $lodash->unit('border-color', '.sppb-btn-2', 'data.second_btn_background_color', '', false);
				$output .= $lodash->unit('border-color', '.sppb-btn-2:hover', 'data.second_btn_background_color_hover', '', false);
				$output .= $lodash->unit('background-color', '.sppb-btn-2:hover', 'data.second_btn_background_color_hover', '', false);
				$output .= '<# } else if (data.second_btn_appearance == "gradient") { #>';
				$output .= '#sppb-addon-{{ data.id }}-2 .sppb-btn-2 {border: none;}';
				$output .= $lodash->color('background-color', '.sppb-btn-2', 'data.second_btn_background_gradient');
				$output .= $lodash->color('background-color', '.sppb-btn-2:hover', 'data.second_btn_background_gradient_hover');
				$output .= '<# } else { #>';
				$output .= $lodash->color('background-color', '.sppb-btn-2', 'data.second_btn_background_color');
				$output .= $lodash->color('background-color', '.sppb-btn-2:hover', 'data.second_btn_background_color_hover');
				$output .= '<# } #>';
				$output .= '<# } #>';
		
				// link
				$output .= '<# if (data.second_btn_type == "link") { #>';
				$output .= '#sppb-addon-{{ data.id }}-2 .sppb-btn-2 {padding: 0; border-width: 0; text-decoration: none; border-radius: 0;}';
				$output .= $lodash->color('color', '.sppb-btn-2', 'data.second_link_btn_color');
				$output .= $lodash->unit('border-color', '.sppb-btn-2', 'data.second_link_btn_border_color', '', false);
				$output .= $lodash->unit('border-bottom-width', '.sppb-btn-2', 'data.second_link_btn_border_width', 'px');
				$output .= $lodash->unit('padding-bottom', '.sppb-btn-2', 'data.second_link_btn_padding_bottom', 'px');
				$output .= $lodash->color('color', '.sppb-btn-2:hover, .sppb-btn-2:focus', 'data.second_link_btn_hover_color');
				$output .= $lodash->unit('border-color', '.sppb-btn-2:hover, .sppb-btn-2:focus', 'data.second_link_btn_border_hover_color', '', false);
				$output .= '<# } #>';
				// second button end

		// icon
		$output .= '#sppb-addon-{{ data.id }} .sppb-icon .sppb-icon-container {display: inline-block; text-align: center; transition: 300ms;}';
		$output .= $lodash->color('color', '.sppb-icon .sppb-icon-container', 'data.icon_color');
		$output .= $lodash->spacing('padding', '.sppb-icon .sppb-icon-container', 'data.icon_padding');
		$output .= $lodash->color('background-color', '.sppb-icon .sppb-icon-container', 'data.icon_background');
		$output .= '<# if (data.icon_border_color) { #>';
		$output .= '#sppb-addon-{{ data.id }} .sppb-icon .sppb-icon-container {border-style: solid;}';
		$output .= $lodash->unit('border-color', '.sppb-icon .sppb-icon-container', 'data.icon_border_color', '', false);
		$output .= $lodash->unit('border-width', '.sppb-icon .sppb-icon-container', 'data.icon_border_width', 'px');
		$output .= '<# } #>';
		$output .= $lodash->unit('border-radius', '.sppb-icon .sppb-icon-container', 'data.icon_border_radius', 'px');
		$output .= $lodash->boxShadow('.sppb-icon .sppb-icon-container', 'data.icon_boxshadow');

		$output .= $lodash->unit('margin-top', '.sppb-icon', 'data.icon_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-icon', 'data.icon_margin_bottom', 'px');

		$output .= $lodash->unit('font-size', '.sppb-icon .sppb-icon-container > i', 'data.icon_size', 'px');
		$output .= $lodash->unit('width', '.sppb-icon .sppb-icon-container > i', 'data.icon_size', 'px');
		$output .= $lodash->unit('height', '.sppb-icon .sppb-icon-container > i', 'data.icon_size', 'px');
		$output .= $lodash->unit('line-height', '.sppb-icon .sppb-icon-container > i', 'data.icon_size', 'px');

		$output .= $lodash->color('color', '&:hover .sppb-icon .sppb-icon-container', 'data.icon_hover_color');
		$output .= $lodash->color('background-color', '&:hover .sppb-icon .sppb-icon-container', 'data.icon_hover_bg');

		// image
		$output .= '#sppb-addon-{{ data.id }} .sppb-img-container {display: block;}';
		$output .= '<# if (!_.isEmpty(data.feature_image_margin) && (data.title_position == "left" || data.title_position == "right")) { #>';
		$output .= $lodash->spacing('margin', '.sppb-media .pull-left, .sppb-media .pull-right', 'data.feature_image_margin');
		$output .= '<# } #>';

		$output .= '<# if (!_.isEmpty(data.feature_image_margin) && (data.title_position == "after" || data.title_position == "before")) { #>';
		$output .= $lodash->spacing('margin', '.sppb-img-container', 'data.feature_image_margin');
		$output .= '<# } #>';
		$output .= '<# if (data.feature_type !== "icon") { #>';
		$output .= $lodash->unit('width', '.sppb-media .pull-left, .sppb-media .pull-right', 'data.feature_image_width', '%');
		$output .= '<# } #>';

		$output .= $lodash->unit('border-radius', '.sppb-img-container', 'data.image_radius', 'px');

		$output .= '.sppb-img-container { overflow: hidden;
		display: inline-block; }';
		$output .= '.sppb-img-container img { transition: transform 0.5s ease; }';

		$output .= '<# if(data.is_zoom_enabled && zoom_scale) { #>';
		$output .= $lodash->generateTransformCss('.sppb-img-container:hover img', 'transform_zoom');
		$output .= '<# } #>';

		$output .= '#sppb-addon-{{ data.id }} {transition: 300ms;}';
		$output .= '#sppb-addon-{{ data.id }} .sppb-feature-box-title {transition: 300ms;}';

		// title
		$titleTypographyFallbacks = [
			'font'           => 'data.title_font_family',
			'size'           => 'data.title_fontsize',
			'line_height'    => 'data.title_lineheight',
			'letter_spacing' => 'data.title_letterspace',
			'uppercase'      => 'data.title_font_style?.uppercase',
			'italic'         => 'data.title_font_style?.italic',
			'underline'      => 'data.title_font_style?.underline',
			'weight'         => 'data.title_font_style?.weight',
		];
		$output .= $lodash->typography('.sppb-feature-box-title', 'data.title_typography', $titleTypographyFallbacks);

		$output .= '<# if (!data.text) { #>';
		$output .= '#sppb-addon-{{ data.id }} .sppb-addon-title.sppb-feature-box-title {display: block;}';
		$output .= '<# } #>';

		$output .= $lodash->color('color', '.sppb-feature-box-title, .sppb-feature-box-title a', 'data.title_text_color');
		$output .= $lodash->color('color', '.sppb-addon-text', 'data.addon_color');


		// content
		$textTypographyFallbacks = [
			'font'           => 'data.text_font_family',
			'size'           => 'data.text_fontsize',
			'line_height'    => 'data.text_lineheight',
			'weight'         => 'data.text_fontweight'
		];
		$output .= $lodash->typography('.sppb-addon-text', 'data.content_typography', $textTypographyFallbacks);
		$output .= $lodash->spacing('padding', '.sppb-media-content', 'data.text_padding');
		$output .= $lodash->color('background-color', '.sppb-media-content', 'data.text_background');
		$output .= $lodash->alignment('text-align', '.sppb-addon-content', 'data.alignment');

		//Addon Background Color
		$output .= $lodash->color('background-color', ':self', 'data.background_color');


		// Hover
		$output .= $lodash->color('color', '&:hover .sp-editable-content', 'data.addon_hover_color');
		$output .= $lodash->color('background-color', '&:hover', 'data.addon_hover_bg');
		$output .= $lodash->boxShadow('&:hover', 'data.addon_hover_boxshadow');
		$output .= $lodash->color('color', '&:hover .sppb-feature-box-title, &:hover .sppb-feature-box-title a', 'data.title_hover_color');
		$output .= $lodash->generateTransformCss('', 'data.transform');

		$output .= '
		</style>
		<div class="sppb-addon sppb-addon-feature {{ data.class }} {{ alignment }}">
			<div class="sppb-addon-content">
			<# 
			const isMenuBtn = _.isString(data.btn_url?.menu) && data.btn_url.type === "menu" && data.btn_url?.menu;
			const isPageBtn = _.isString(data.btn_url?.page) && data.btn_url.type === "page" && data.btn_url?.page;

			const isObjectBtn = _.isObject(data.btn_url) && ((data.btn_url.type === "url" && data.btn_url?.url !== "") ||  isMenu || isPage);
			
			let urlObjBtn = {};
			let urlBtn = "";
			let targetBtn= "";
			let relBtn = "";
			let ariaLabel = data.btn_aria_label || "";
			const visuallyHiddenText = ariaLabel ? "<span class=\'sppb-visually-hidden\'>" + ariaLabel + "</span>" : "";
			
				urlObjBtn = isObjectBtn ? data.btn_url : window.getSiteUrl(data.btn_url, data.btn_target);

				if(urlObjBtn.type === "url") {	
					urlBtn = urlObjBtn.url;
				}

				if(urlObjBtn.type === "menu") {
					urlBtn = urlObjBtn.menu || "";
				}
				
				if(urlObjBtn.type === "page") {
					urlBtn = urlObjBtn.page ? `index.php?option=com_sppagebuilder&view=page&id=${urlObjBtn.page}` : "";
				}
				targetBtn = urlObjBtn.new_tab ? "_blank": "";
				relBtn += urlObjBtn.nofollow ? "nofollow": "";
				relBtn += urlObjBtn.noopener ? " noopener": "";
				relBtn += urlObjBtn.noreferrer ? " noreferrer": "";

			const isSecondMenuBtn = _.isString(data.second_btn_url?.menu) && data.second_btn_url.type === "menu" && data.second_btn_url?.menu;
			const isSecondPageBtn = _.isString(data.second_btn_url?.page) && data.second_btn_url.type === "page" && data.second_btn_url?.page;

			const isSecondObjectBtn = _.isObject(data.second_btn_url) && ((data.second_btn_url.type === "url" && data.second_btn_url?.url !== "") ||  isSecondMenuBtn || isSecondPageBtn);
			
			let secondUrlObjBtn = {};
			let secondUrlBtn = "";
			let secondTargetBtn= "";
			let secondRelBtn = "";
			let secondAriaLabel = data.second_btn_aria_label || "";
			const secondVisuallyHiddenText = secondAriaLabel ? "<span class=\'sppb-visually-hidden\'>" + secondAriaLabel + "</span>" : "";
			
				secondUrlObjBtn = isSecondObjectBtn ? data.second_btn_url : window.getSiteUrl(data.second_btn_url, data.second_btn_target);

				if(secondUrlObjBtn.type === "url") {	
					secondUrlBtn = secondUrlObjBtn.url;
				}

				if(secondUrlObjBtn.type === "menu") {
					secondUrlBtn = urlObjBtn.menu || "";
				}
				
				if(secondUrlObjBtn.type === "page") {
					secondUrlBtn = secondUrlObjBtn.page ? `index.php?option=com_sppagebuilder&view=page&id=${secondUrlObjBtn.page}` : "";
				}
				secondTargetBtn = secondUrlObjBtn.new_tab ? "_blank": "";
				secondRelBtn += secondUrlObjBtn.nofollow ? "nofollow": "";
				secondRelBtn += secondUrlObjBtn.noopener ? " noopener": "";
				secondRelBtn += secondUrlObjBtn.noreferrer ? " noreferrer": "";

			let secondClassList = " sppb-btn-2 sppb-btn-" + (data.second_btn_type || "custom");
			secondClassList += " sppb-btn-" + data.second_btn_size;
			secondClassList += " sppb-btn-" + data.second_btn_shape;

			if (!_.isEmpty(data.second_btn_appearance)) {
				secondClassList += " sppb-btn-"+data.second_btn_appearance;
			}
			
			#>'
			. 
			'
				<# if (!_.isString(icon_image_position)) { 
					var icon_image_position_lg = icon_image_position.xl || "after";
					var icon_image_position_md = icon_image_position.lg || icon_image_position_lg;
					var icon_image_position_sm = icon_image_position.md || icon_image_position_md;
					var icon_image_position_xs = icon_image_position.sm || icon_image_position_sm;
					var icon_image_position_nano = icon_image_position.xs || icon_image_position_xs;
				#>
					<style>
						' . 
						
						$lodash->generate_layout_position_css('icon_image_position_lg', 'data.id') 
						. 
						'
						@media (max-width: 1198px) {' .
						$lodash->generate_layout_position_css('icon_image_position_md', 'data.id') .
						'
												@media (max-width: 990px) {' .
						$lodash->generate_layout_position_css('icon_image_position_sm', 'data.id') . '
												@media (max-width: 765px) {' .
						$lodash->generate_layout_position_css('icon_image_position_xs', 'data.id') . '
												@media (max-width: 573px) {' .
						$lodash->generate_layout_position_css('icon_image_position_nano', 'data.id') . '
					</style>
				<# } #>
			'
			.

			'
				<# if (!_.isString(icon_image_position) || icon_image_position == "before") { #>
				<div class="sppb-addon-content-align-after">
					<# if(media){ #>
						{{{ media }}}
					<# } #>
                    <div class="sppb-media-content">
                        <# if(data.title){ #>
                            {{{ feature_title }}}
                        <# } #>
						{{{ feature_text }}}
						<# if(data.btn_text && _.trim(data.btn_text)){
							let icon_arr = (typeof data.btn_icon !== "undefined" && data.btn_icon) ? data.btn_icon.split(" ") : "";
							let icon_name = icon_arr.length === 1 ? "fa "+data.btn_icon : data.btn_icon;
						#>
							<a href=\'{{ urlBtn }}\' target=\'{{ targetBtn }}\' rel=\'{{ relBtn }}\' id="btn-{{ data.id }}" aria-label="{{ ariaLabel }}" class="sppb-btn {{ classList }}"><# if(data.btn_icon_position == "left" && !_.isEmpty(data.btn_icon)) { #><i class="{{ icon_name }}"></i> <# } #>{{ data.btn_text }}<# if(data.btn_icon_position == "right" && !_.isEmpty(data.btn_icon)) { #> <i class="{{ icon_name }}"></i><# } #>{{{ visuallyHiddenText }}}</a>
						<# } #>
						<# if(data.second_btn_text && _.trim(data.second_btn_text)){
							let second_icon_arr = (typeof data.second_btn_icon !== "undefined" && data.second_btn_icon) ? data.second_btn_icon.split(" ") : "";
							let second_icon_name = second_icon_arr.length === 1 ? "fa "+data.second_btn_icon : data.second_btn_icon;
							let second_btn_icon_position = data.second_btn_icon_position || "left";
						#>
							<a href=\'{{ secondUrlBtn }}\' target=\'{{ secondTargetBtn }}\' rel=\'{{ secondRelBtn }}\' id="btn-{{ data.id }}-2" aria-label="{{ secondAriaLabel }}" class="sppb-btn {{ secondClassList }}"><# if(second_btn_icon_position == "left" && !_.isEmpty(data.second_btn_icon)) { #><i class="{{ second_icon_name }}"></i> <# } #>{{ data.second_btn_text }}<# if(second_btn_icon_position == "right" && !_.isEmpty(data.second_btn_icon)) { #> <i class="{{ second_icon_name }}"></i><# } #>{{{ secondVisuallyHiddenText }}}</a>
						<# } #>
                    </div>
				</div>
				<# } if (!_.isString(icon_image_position) || icon_image_position == "after") { #>
				<div class="sppb-addon-content-align-before">
					<# if(data.title){ #>
						{{{ feature_title }}}
					<# } #>
					<# if(media){ #>
						{{{ media }}}
					<# } #>
                    <div class="sppb-media-content">
					{{{ feature_text }}}
					<# if(data.btn_text && _.trim(data.btn_text)){
						let icon_arr = (typeof data.btn_icon !== "undefined" && data.btn_icon) ? data.btn_icon.split(" ") : "";
						let icon_name = icon_arr.length === 1 ? "fa "+data.btn_icon : data.btn_icon;
					#>
						<a href=\'{{ urlBtn }}\' target=\'{{ targetBtn }}\' rel=\'{{ relBtn }}\' id="btn-{{ data.id }}" class="sppb-btn {{ classList }}"><# if(data.btn_icon_position == "left" && !_.isEmpty(data.btn_icon)) { #><i class="{{ icon_name }}"></i> <# } #>{{ data.btn_text }}<# if(data.btn_icon_position == "right" && !_.isEmpty(data.btn_icon)) { #> <i class="{{ icon_name }}"></i><# } #>{{{ visuallyHiddenText }}}</a>
					<# } #>
						<# if(data.second_btn_text && _.trim(data.second_btn_text)){
							let second_icon_arr = (typeof data.second_btn_icon !== "undefined" && data.second_btn_icon) ? data.second_btn_icon.split(" ") : "";
							let second_icon_name = second_icon_arr.length === 1 ? "fa "+data.second_btn_icon : data.second_btn_icon;
							let second_btn_icon_position = data.second_btn_icon_position || "left";
						#>
							<a href=\'{{ secondUrlBtn }}\' target=\'{{ secondTargetBtn }}\' rel=\'{{ secondRelBtn }}\' id="btn-{{ data.id }}-2" aria-label="{{ secondAriaLabel }}" class="sppb-btn {{ secondClassList }}"><# if(second_btn_icon_position == "left" && !_.isEmpty(data.second_btn_icon)) { #><i class="{{ second_icon_name }}"></i> <# } #>{{ data.second_btn_text }}<# if(second_btn_icon_position == "right" && !_.isEmpty(data.second_btn_icon)) { #> <i class="{{ second_icon_name }}"></i><# } #>{{{ secondVisuallyHiddenText }}}</a>
						<# } #>
                    </div>
					</div>
				<# } if (!_.isString(icon_image_position) || icon_image_position == "left") { #>
				<div class="sppb-addon-content-align-left">
					<# if(media) { #>
						<div class="sppb-media">
							<div class="pull-left">{{{ media }}}</div>
							<div class="sppb-media-body">
                                <div class="sppb-media-content">
                                    
                                    <# if(data.title){ #>
                                        {{{ feature_title }}}
                                    <# } #>
									{{{ feature_text }}}
									<# if(data.btn_text && _.trim(data.btn_text)){
										let icon_arr = (typeof data.btn_icon !== "undefined" && data.btn_icon) ? data.btn_icon.split(" ") : "";
										let icon_name = icon_arr.length === 1 ? "fa "+data.btn_icon : data.btn_icon;
									#>
										<a href=\'{{ urlBtn }}\' target=\'{{ targetBtn }}\' rel=\'{{ relBtn }}\' id="btn-{{ data.id }}" aria-label="{{ ariaLabel }}" class="sppb-btn {{ classList }}"><# if(data.btn_icon_position == "left" && !_.isEmpty(data.btn_icon)) { #><i class="{{ icon_name }}"></i> <# } #>{{ data.btn_text }}<# if(data.btn_icon_position == "right" && !_.isEmpty(data.btn_icon)) { #> <i class="{{ icon_name }}"></i><# } #>{{{ visuallyHiddenText }}}</a>
									<# } #>
									<# if(data.second_btn_text && _.trim(data.second_btn_text)){
							let second_icon_arr = (typeof data.second_btn_icon !== "undefined" && data.second_btn_icon) ? data.second_btn_icon.split(" ") : "";
							let second_icon_name = second_icon_arr.length === 1 ? "fa "+data.second_btn_icon : data.second_btn_icon;
							let second_btn_icon_position = data.second_btn_icon_position || "left";
						#>
							<a href=\'{{ secondUrlBtn }}\' target=\'{{ secondTargetBtn }}\' rel=\'{{ secondRelBtn }}\' id="btn-{{ data.id }}-2" aria-label="{{ secondAriaLabel }}" class="sppb-btn {{ secondClassList }}"><# if(second_btn_icon_position == "left" && !_.isEmpty(data.second_btn_icon)) { #><i class="{{ second_icon_name }}"></i> <# } #>{{ data.second_btn_text }}<# if(second_btn_icon_position == "right" && !_.isEmpty(data.second_btn_icon)) { #> <i class="{{ second_icon_name }}"></i><# } #>{{{ secondVisuallyHiddenText }}}</a>
						<# } #>
                                </div>
							</div>
						</div>
					<# } else { #>
						<div class="sppb-media">
							<div class="sppb-media-body">
                                <div class="sppb-media-content">
                                  
                                    <# if(data.title){ #>
                                        {{{ feature_title }}}
                                    <# } #>
									{{{ feature_text }}}
									<# if(data.btn_text && _.trim(data.btn_text)){
										let icon_arr = (typeof data.btn_icon !== "undefined" && data.btn_icon) ? data.btn_icon.split(" ") : "";
										let icon_name = icon_arr.length === 1 ? "fa "+data.btn_icon : data.btn_icon;
									#>
										<a href=\'{{ urlBtn }}\' target=\'{{ targetBtn }}\' rel=\'{{ relBtn }}\' id="btn-{{ data.id }}" aria-label="{{ ariaLabel }}" class="sppb-btn {{ classList }}"><# if(data.btn_icon_position == "left" && !_.isEmpty(data.btn_icon)) { #><i class="{{ icon_name }}"></i> <# } #>{{ data.btn_text }}<# if(data.btn_icon_position == "right" && !_.isEmpty(data.btn_icon)) { #> <i class="{{ icon_name }}"></i><# } #>{{ visuallyHiddenText }}</a>
									<# } #>
							<# if(data.second_btn_text && _.trim(data.second_btn_text)){
							let second_icon_arr = (typeof data.second_btn_icon !== "undefined" && data.second_btn_icon) ? data.second_btn_icon.split(" ") : "";
							let second_icon_name = second_icon_arr.length === 1 ? "fa "+data.second_btn_icon : data.second_btn_icon;
							let second_btn_icon_position = data.second_btn_icon_position || "left";
						#>
							<a href=\'{{ secondUrlBtn }}\' target=\'{{ secondTargetBtn }}\' rel=\'{{ secondRelBtn }}\' id="btn-{{ data.id }}-2" aria-label="{{ secondAriaLabel }}" class="sppb-btn {{ secondClassList }}"><# if(second_btn_icon_position == "left" && !_.isEmpty(data.second_btn_icon)) { #><i class="{{ second_icon_name }}"></i> <# } #>{{ data.second_btn_text }}<# if(second_btn_icon_position == "right" && !_.isEmpty(data.second_btn_icon)) { #> <i class="{{ second_icon_name }}"></i><# } #>{{{ secondVisuallyHiddenText }}}</a>
						<# } #>
                                </div>
							</div>
						</div>
					<# } #>
				</div>
				<# } if (!_.isString(icon_image_position) || icon_image_position == "right") { #>
				<div class="sppb-addon-content-align-right">
					<# if(media) { #>
						<div class="sppb-media">
							<div class="pull-right">{{{ media }}}</div>
							<div class="sppb-media-body">
                                <div class="sppb-media-content">
                                    
                                    <# if(data.title){ #>
                                        {{{ feature_title }}}
                                    <# } #>
									{{{ feature_text }}}
									<# if(data.btn_text && _.trim(data.btn_text)){
										let icon_arr = (typeof data.btn_icon !== "undefined" && data.btn_icon) ? data.btn_icon.split(" ") : "";
										let icon_name = icon_arr.length === 1 ? "fa "+data.btn_icon : data.btn_icon;
									#>
										<a href=\'{{ urlBtn }}\' target=\'{{ targetBtn }}\' rel=\'{{ relBtn }}\' id="btn-{{ data.id }}" aria-label="{{ ariaLabel }}" class="sppb-btn {{ classList }}"><# if(data.btn_icon_position == "left" && !_.isEmpty(data.btn_icon)) { #><i class="{{ icon_name }}"></i> <# } #>{{ data.btn_text }}<# if(data.btn_icon_position == "right" && !_.isEmpty(data.btn_icon)) { #> <i class="{{ icon_name }}"></i><# } #>{{{ visuallyHiddenText }}}</a>
									<# } #>
							<# if(data.second_btn_text && _.trim(data.second_btn_text)){
							let second_icon_arr = (typeof data.second_btn_icon !== "undefined" && data.second_btn_icon) ? data.second_btn_icon.split(" ") : "";
							let second_icon_name = second_icon_arr.length === 1 ? "fa "+data.second_btn_icon : data.second_btn_icon;
							let second_btn_icon_position = data.second_btn_icon_position || "left";
						#>
							<a href=\'{{ secondUrlBtn }}\' target=\'{{ secondTargetBtn }}\' rel=\'{{ secondRelBtn }}\' id="btn-{{ data.id }}-2" aria-label="{{ secondAriaLabel }}" class="sppb-btn {{ secondClassList }}"><# if(second_btn_icon_position == "left" && !_.isEmpty(data.second_btn_icon)) { #><i class="{{ second_icon_name }}"></i> <# } #>{{ data.second_btn_text }}<# if(second_btn_icon_position == "right" && !_.isEmpty(data.second_btn_icon)) { #> <i class="{{ second_icon_name }}"></i><# } #>{{{ secondVisuallyHiddenText }}}</a>
						<# } #>
                                </div>
							</div>
						</div>
					<# } else { #>
						<div class="sppb-media">
							<div class="sppb-media-body">
                                <div class="sppb-media-content">
                                  
                                    <# if(data.title){ #>
                                        {{{ feature_title }}}
                                    <# } #>
									{{{ feature_text }}}
									<# if(data.btn_text && _.trim(data.btn_text)){
										let icon_arr = (typeof data.btn_icon !== "undefined" && data.btn_icon) ? data.btn_icon.split(" ") : "";
										let icon_name = icon_arr.length === 1 ? "fa "+data.btn_icon : data.btn_icon;
									#>
										<a href=\'{{ urlBtn }}\' target=\'{{ targetBtn }}\' rel=\'{{ relBtn }}\' id="btn-{{ data.id }}" aria-label="{{ ariaLabel }}" class="sppb-btn {{ classList }}"><# if(data.btn_icon_position == "left" && !_.isEmpty(data.btn_icon)) { #><i class="{{ icon_name }}"></i> <# } #>{{ data.btn_text }}<# if(data.btn_icon_position == "right" && !_.isEmpty(data.btn_icon)) { #> <i class="{{ icon_name }}"></i><# } #>{{ visuallyHiddenText }}</a>
									<# } #>
                                </div>
							</div>
						</div>
					<# } #>
				</div>
				<# } #>
			</div>
		</div>';

		return $output;
	}
}
