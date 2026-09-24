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

class SppagebuilderAddonGallery extends SppagebuilderAddons
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
		$addon_id = 'sppb-addon-' . $this->addon->id;
		$class = (isset($settings->class) && $settings->class) ? $settings->class : '';
		$title = (isset($settings->title) && $settings->title) ? $settings->title : '';
		$heading_selector = (isset($settings->heading_selector) && $settings->heading_selector) ? $settings->heading_selector : 'h3';
		$item_alignment = (isset($settings->item_alignment) && $settings->item_alignment) ? $settings->item_alignment : '';
		$item_alignment = AddonUtils::parseDeviceData($item_alignment, SpPgaeBuilderBase::$defaultDevice);
		$show_thumb_desc = (isset($settings->show_thumb_desc) && $settings->show_thumb_desc) ? $settings->show_thumb_desc : 0;
		$show_full_desc = (isset($settings->show_full_desc) && $settings->show_full_desc) ? $settings->show_full_desc : 0;

		$output  = '<div class="sppb-addon sppb-addon-gallery ' . $class . '">';
		$output .= ($title) ? '<' . $heading_selector . ' class="sppb-addon-title">' . $title . '</' . $heading_selector . '>' : '';
		$output .= '<div class="sppb-addon-content">';
		$output .= '<ul class="sppb-gallery clearfix gallery-item-' . $item_alignment . '">';

		if (isset($settings->sp_gallery_item) && count((array) $settings->sp_gallery_item))
		{
			foreach ($settings->sp_gallery_item as $key => $value)
			{
				if (isset($value->item_visibility) && !$value->item_visibility) {
					continue;
				}
				$thumb_img = isset($value->thumb) && $value->thumb ? $value->thumb : '';
				$thumb_src = isset($thumb_img->src) ? $thumb_img->src : $thumb_img;
				$alt_text_fallback = isset($value->title) ? $value->title : '';
				$alt_text = isset($thumb_img->alt) ? $thumb_img->alt : $alt_text_fallback;
				$thumb_width = isset($thumb_img->width) && $thumb_img->width ? $thumb_img->width : '';
				$thumb_height = isset($thumb_img->height) && $thumb_img->height ? $thumb_img->height : '';
				$description = isset($value->description) && $value->description ? $value->description : '';

				$full_img = isset($value->full) && $value->full ? $value->full : '';
				$full_src = isset($full_img->src) ? $full_img->src : $full_img;

				if (!is_string($thumb_src)) {
					$thumb_src = '';
				}

				if (!empty($thumb_src))
				{
					if (strpos($thumb_src, "http://") !== false || strpos($thumb_src, "https://") !== false)
					{
						$thumb_src = $thumb_src;
					}
					else
					{
						$thumb_src = Uri::base(true) . '/' . $thumb_src;
					}

					$placeholder = $thumb_src == '' ? false : $this->get_image_placeholder($thumb_src);
					$title = $show_full_desc && isset($value->description) && $value->description ?  'data-title="<div id='. $addon_id . '>' . '<p class=sppb-gallery-desc>' . $description . '</p>' . '</div>"' : 'data-title=""';

					$output .= '<li>';
					$output .= ($full_src) ? '<a href="' . $full_src . '" class="sppb-gallery-btn">' : '';
					$output .= '<img ' . $title . ' class="sppb-img-responsive' . ($placeholder ? ' sppb-element-lazy' : '') . '" src="' . ($placeholder ? $placeholder : $thumb_src) . '" alt="' . $alt_text . '" ' . ($placeholder ? 'data-large="' . $thumb_src . '"' : '') . ' ' . ($thumb_width ? 'width="' . $thumb_width . '"' : '') . ' ' . ($thumb_height ? 'height="' . $thumb_height . '"' : '') . ' loading="lazy">';
					$output .= ($full_src) ? '</a>' : '';

					if($show_thumb_desc && $description)
					{
						$output .= '<p class="sppb-gallery-desc">' . $description . '</p>';
					}

					$output .= '</li>';
				}
			}
		}

		$output .= '</ul>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Attach inline stylesheet.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	public function stylesheets()
	{
		return array(Uri::base(true) . '/components/com_sppagebuilder/assets/css/magnific-popup.css');
	}

	/**
	 * Attach external scripts.
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	public function scripts()
	{
		return array(Uri::base(true) . '/components/com_sppagebuilder/assets/js/jquery.magnific-popup.min.js');
	}

	/**
	 * Attach inline JavaScript.
	 *
	 * @return  string  The JS string.
	 * @since   1.0.0
	 */
	public function js()
	{
		$addon_id = '#sppb-addon-' . $this->addon->id;
		$js = 'jQuery(function($){
			$("' . $addon_id . ' ul li").magnificPopup({
				delegate: "a",
				type: "image",
				mainClass: "mfp-no-margins mfp-with-zoom",
				gallery:{
					enabled:true
				},
				image: {
					verticalFit: true,
					titleSrc: function(item) {
						return item.el.find("img").data("title");
					}
				},
				zoom: {
					enabled: true,
					duration: 300
				}
			});
		})';

		return $js;
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

		$css = '';

		$border_radius = (isset($settings->border_radius) && $settings->border_radius) ? $settings->border_radius : 0;

		if ($border_radius) {
			$border_radius = explode(" ", $settings->border_radius);
		}

		$galleryImageDefaultStyle = '';

		if (!isset($settings->width) || empty($settings->width)) {
			$galleryImageDefaultStyle = $cssHelper->generateStyle('.sppb-gallery img', $settings, [], false, [], null, false, 'width: auto;');
		}

		if (is_array($border_radius) && (count($border_radius) > 2)) {
			$galleryImageStyle = $cssHelper->generateStyle('.sppb-gallery img', $settings, ['width' => 'width', 'height' => 'height', 'border_radius' => 'border-radius'],
			[
				'border_radius' => false
			],
			[
				'border_radius' => 'spacing'
			]);
		} else {
			$galleryImageStyle = $cssHelper->generateStyle('.sppb-gallery img', $settings, ['width' => 'width', 'height' => 'height', 'border_radius' => 'border-radius']);
		}

		$galleryStyle = $cssHelper->generateStyle('.sppb-gallery', $settings, ['item_gap' => 'margin: -%s', 'item_alignment' => 'justify-content'], ['item_alignment' => false]);
		$galleryItemStyle = $cssHelper->generateStyle('.sppb-gallery li', $settings, ['item_gap' => 'margin']);
		$transformCss = $cssHelper->generateTransformStyle('.sppb-gallery', $settings, 'transform');
		$descriptionAlignment = $cssHelper->generateStyle('.sppb-gallery-desc', $settings, ['description_alignment' => 'text-align'], false);
		$descriptionWidth = $cssHelper->generateStyle('.sppb-gallery-desc', $settings, ['width' => 'max-width'], 'px');
		

		$css .= $galleryStyle;
		$css .= $galleryItemStyle;
		$css .= $galleryImageDefaultStyle;
		$css .= $galleryImageStyle;
		$css .= $transformCss;
		$css .= $descriptionAlignment;
		$css .= $descriptionWidth;

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

		$output .= $lodash->alignment('justify-content', '.sppb-gallery', 'data.item_alignment');

		$output .= $lodash->alignment('text-align', '.sppb-gallery-desc', 'data.description_alignment');
		$output .= $lodash->unit('max-width', '.sppb-gallery-desc', 'data.width', 'px');

		$output .= $lodash->unit('width', '.sppb-gallery img', 'data.width', 'px');
		$output .= $lodash->unit('height', '.sppb-gallery img', 'data.height', 'px');

		$output .= '<# if((data.border_radius + "").split(" ").length < 2) { #>';
		$output .= $lodash->unit('border-radius', '.sppb-gallery img', 'data.border_radius', 'px');
		$output .= '<# } else { #>';
		$output .= '.sppb-gallery img {
			{{window.getSplitRadius(data.border_radius)}}	
		}';
		$output .= '<# } #>';

		$output .= $lodash->unit('margin', '.sppb-gallery li', 'data.item_gap', 'px');
		$output .= $lodash->unit('margin', '.sppb-gallery', 'data.item_gap', 'px', true, '-');

		// Title
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

		$output .= $lodash->typography('.sppb-addon-title', 'data.title_typography', $titleTypographyFallbacks);
		$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');
		$output .= $lodash->generateTransformCss('.sppb-gallery', 'data.transform');

		$output .= '
        </style>
		<div class="sppb-addon sppb-addon-gallery {{ data.class }}">
			<# if( !_.isEmpty( data.title ) ){ #><{{ data.heading_selector }} class="sppb-addon-title sp-inline-editable-element" data-id={{data.id}} data-fieldName="title" contenteditable="true">{{ data.title }}</{{ data.heading_selector }}><# } #>
			<div class="sppb-addon-content">
                <ul class="sppb-gallery clearfix gallery-item-{{data.item_alignment}}">
                
                <#
                _.each(data.sp_gallery_item, function (value, key) {
					if(typeof value.item_visibility !== "undefined" && !value.item_visibility){
						return;
					}
                    var thumbImg = {}
                    var fullImg = {}
					var desc = data.show_thumb_desc && value.description;
                    if (typeof value.thumb !== "undefined" && typeof value.thumb.src !== "undefined") {
                        thumbImg = value.thumb
                    } else {
                        thumbImg = {src: value.thumb}
                    }
                    if (typeof value.full !== "undefined" && typeof value.full.src !== "undefined") {
                        fullImg = value.full
                    } else {
                        fullImg = {src: value.full}
                    }
					if(thumbImg.src) {
                #>
						<li>
						<# if(fullImg.src && fullImg.src.indexOf("http://") == -1 && fullImg.src.indexOf("https://") == -1){ #>
							<a href=\'{{ pagebuilder_base + fullImg.src }}\' class="sppb-gallery-btn">
						<# } else if(fullImg.src){ #>
							<a href=\'{{ fullImg.src }}\' class="sppb-gallery-btn">
                        <# }
                        if(thumbImg.src && thumbImg.src.indexOf("http://") == -1 && thumbImg.src.indexOf("https://") == -1){
                        #>
								<img class="sppb-img-responsive" src=\'{{ pagebuilder_base + thumbImg.src }}\' alt="{{ value.thumb.alt ?? value.title }}">
							<# } else if(thumbImg.src){ #>
                                <img class="sppb-img-responsive" src=\'{{ thumbImg.src }}\' alt="{{ value.thumb.alt ?? value.title }}">
							<# } #>
						<# if(fullImg.src){ #>
							</a>
						<# } #>
						<# if(desc){ #>
							<p class="sppb-gallery-desc">{{ value.description }}</p>
						<# } #>
						</li>
					<# } #>
				<# }); #>
				</ul>
			</div>
		</div>
		';

		return $output;
	}
}
