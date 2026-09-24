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
use Joomla\CMS\Uri\Uri;
use Joomla\Plugin\System\Sef\Extension\Sef;

class SppagebuilderAddonImage extends SppagebuilderAddons
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
		
		$image_shape = !empty($settings->image_shape) ? $settings->image_shape : 0;
		$class = (isset($settings->class) && $settings->class) ? $settings->class : '';
		$title = (isset($settings->title) && $settings->title) ? $settings->title : '';
		$title_position = (isset($settings->title_position) && $settings->title_position) ? $settings->title_position : 'top';
		$heading_selector = (isset($settings->heading_selector) && $settings->heading_selector) ? $settings->heading_selector : 'h3';

		// Options
		$image = (isset($settings->image) && $settings->image) ? $settings->image : '';
		$image_title = (isset($settings->image_title) && $settings->image_title) ? $settings->image_title : '';
		$fetch_priority = (isset($settings->fetch_priority) && $settings->fetch_priority) ? 'fetchpriority="' . $settings->fetch_priority . '"' : '';

		$final_image_title = empty($image_title) ? $title : $image_title;

		$image_src = isset($image->src) ? $image->src : $image;
		$image_width = (isset($image->width) && $image->width) ? $image->width : '';
		$image_height = (isset($image->height) && $image->height) ? $image->height : '';
		$image_fit = (isset($settings->image_fit) && $settings->image_fit) ? $settings->image_fit : '';

		$custom_image_width = (isset($settings->image_width) && $settings->image_width) ? true : false;
		$custom_image_height = (isset($settings->image_height) && $settings->image_height) ? true : false;
		$noCustomImageDimension = !$custom_image_width && !$custom_image_height;

		// Image 2x
		$image_2x = (isset($settings->image_2x) && $settings->image_2x) ? $settings->image_2x : '';
		$image_2x_src = isset($image_2x->src) ? $image_2x->src : $image_2x;
		$image_2x_src = ctype_space($image_2x_src) ? "" : $image_2x_src;

		$alt_text = (isset($settings->alt_text) && $settings->alt_text) ? $settings->alt_text : '';
		$open_lightbox = (isset($settings->open_lightbox) && $settings->open_lightbox) ? $settings->open_lightbox : 0;
		$image_overlay = (isset($settings->overlay_color) && $settings->overlay_color) ? 1 : 0;
		$is_image_shape_enabled = isset($settings->is_image_shape_enabled) ? $settings->is_image_shape_enabled : 0;
		$image_shape = !empty($settings->image_shape) ? $settings->image_shape : 0;
		$image_shape_scale = !empty($settings->image_shape_scale) ? $settings->image_shape_scale : 1;

		list($link, $target) = AddonHelper::parseLink($settings, 'link', ['url' => 'link', 'new_tab' => 'target']);

		// Lazy image loading
		$placeholder = $image_src === '' ? false : $this->get_image_placeholder($image_src);

		if (strpos($image_src, "http://") !== false || strpos($image_src, "https://") !== false) {
			$image_src = $image_src;
		} else {
			$original_src = Uri::base(true) . '/' . $image_src;
			$image_src = SppagebuilderHelperSite::cleanPath($original_src);
		}

		$output = '';

		

		if ($image_src) {
			$output .= '<div class="sppb-addon sppb-addon-single-image ' . ' ' . $class . ' sppb-addon-image-shape">';
			$output .= ($title && $title_position != 'bottom') ? '<' . $heading_selector . ' class="sppb-addon-title">' . $title . '</' . $heading_selector . '>' : '';
			$output .= '<div class="sppb-addon-content">';
			$output .= '<div class="sppb-addon-single-image-container">';

			if (empty($alt_text)) {
				if (!empty($final_image_title)) {
					$alt_text = $final_image_title;
				} else {
					$alt_text = basename($image_src);
				}
			}

			if ($image_overlay && $open_lightbox) {
				$output .= '<div class="sppb-addon-image-overlay">';
				$output .= '</div>';
			}

			if ($open_lightbox) {
				$output .= '<a class="sppb-magnific-popup sppb-image-lightbox sppb-addon-image-overlay-icon" data-popup_type="image" data-mainclass="mfp-no-margins mfp-with-zoom" href="' . $image_src . '">+</a>';
			}

			if (!$open_lightbox) {
				$output .= !empty($link) ? '<a href="' . $link . '" ' . $target . '>' : '';
			}

			$dimension = '';

			if ($placeholder) {
				$dimension = $this->get_image_dimension($image_src);
				$dimension = !empty($dimension) ? \implode(' ', $dimension) : '';
			}

			$image2x = empty($image_2x_src) ? "" : 'srcset="' . $image_2x_src . ' 2x"';

			$image_shape_class = "";

			if($is_image_shape_enabled && $image_shape) {
				$image_shape_value = str_replace('_', '-', $image_shape);
				$image_shape_class = 'sppb-addon-image-shape-' . $image_shape_value;

				$default_shapes = ['circle', 'quarter_slice', 'half_circle', 'bevel', 'star', 'pentagon', 'right_point', 'triangle', 'trapezoid', 'right_chevron', 'right_arrow', 'rabbet'];
				
				if(in_array($image_shape, $default_shapes)) {
					$output .= '<img ' .$fetch_priority. ' class="sppb-img-responsive' . ($placeholder ? ' sppb-element-lazy ' : '') . ' ' . $image_shape_class . '" src="' . ($placeholder ? $placeholder : $image_src)  . '" ' . $image2x . ($placeholder ? 'data-large="' . $image_src . '"' : '') . ' alt="' . $alt_text . '" title="' . $final_image_title . '" ' . ($noCustomImageDimension ? 'width="' . $image_width . '"' : '') . ' ' . ($noCustomImageDimension ? 'height="' . $image_height . '"' : '') . ($placeholder ? 'loading="lazy"' : '') . ' ' . $dimension . ($image_fit ? ('style="object-fit: ' . $image_fit . '";') : '')  . '/>';
				} else {
					$decoded_shape = base64_decode($image_shape);
					$pattern = '/<path.*?d="(.*?)".*?>/s';

					if(preg_match($pattern, $decoded_shape, $matches)) {
						$shape_data = $matches[1];
						$output .= '<img ' .$fetch_priority. ' data-scale="'. $image_shape_scale .'" style="clip-path: url(#svg-shape-' . $this->addon->id . '); visibility: hidden;" class="sppb-img-responsive' . ($placeholder ? ' sppb-element-lazy ' : '') . ' " src="' . ($placeholder ? $placeholder : $image_src)  . '" ' . $image2x . ($placeholder ? 'data-large="' . $image_src . '"' : '') . ' alt="' . $alt_text . '" title="' . $final_image_title . '" ' . ($noCustomImageDimension ? 'width="' . $image_width . '"' : '') . ' ' . ($noCustomImageDimension ? 'height="' . $image_height . '"' : '') . ($placeholder ? 'loading="lazy"' : '') . ' ' . $dimension . ($image_fit ? ('style="object-fit: ' . $image_fit . '";') : '') . '/>';
						$output .= '<svg>
							<defs>
							<clipPath id="svg-shape-' . $this->addon->id . '">
								<path fill="currentColor" d="' . $shape_data . '" />
							</clipPath>
							</defs>
						</svg>';
					}

				}

			} else {
				$output .= '<img ' .$fetch_priority. ' class="sppb-img-responsive' . ($placeholder ? ' sppb-element-lazy ' : '') . ' ' . $image_shape_class . '" src="' . ($placeholder ? $placeholder : $image_src)  . '" ' . $image2x . ($placeholder ? 'data-large="' . $image_src . '"' : '') . ' alt="' . $alt_text . '" title="' . $final_image_title . '" ' . ($noCustomImageDimension ? 'width="' . $image_width . '"' : '') . ' ' . ($noCustomImageDimension ? 'height="' . $image_height . '"' : '') . ($placeholder ? 'loading="lazy"' : '') . ' ' . $dimension . ($image_fit ? ('style="object-fit: ' . $image_fit . '";') : '') . '/>';
			}


			if (!$open_lightbox) {
				$output .= !empty($link) ? '</a>' : '';
			}

			$output  .= '</div>';
			$output .= ($title && $title_position === 'bottom') ? '<' . $heading_selector . ' class="sppb-addon-title" style="display: block;">' . $title . '</' . $heading_selector . '>' : '';
			$output  .= '</div>';
			$output  .= '</div>';
		}

		return $output;
	}

	public function js() {
		$settings = $this->addon->settings;

		$open_lightbox = (isset($settings->open_lightbox) && $settings->open_lightbox) ? $settings->open_lightbox : 0;

		if ($open_lightbox === 0) {
			return '';
		}

		$addon_id = '#sppb-addon-' . $this->addon->id;
		$image_shape = !empty($settings->image_shape) ? $settings->image_shape : 0;
		$image_shape_value = str_replace('_', '-', $image_shape);
		$image_shape_class = 'sppb-addon-image-shape-' . $image_shape_value;
		$is_image_shape_enabled = isset($settings->is_image_shape_enabled) ? $settings->is_image_shape_enabled : 0;
		$default_shapes = ['circle', 'quarter_slice', 'half_circle', 'bevel', 'star', 'pentagon', 'right_point', 'triangle', 'trapezoid', 'right_chevron', 'right_arrow', 'rabbet'];
		$css_class = '';
		$clip_path_url = '';
	
		if (in_array($image_shape, $default_shapes)) {
			$css_class .= $image_shape_class;
		} else {
			$clip_path_url .= 'svg-shape-' . $this->addon->id;
		}

	
		$js = 'jQuery(document).ready(function ($) {
			var cssClass = "' . $css_class . '";
			var clipPathUrl = "' . $clip_path_url . '";
			var shapeEnabled = "'. $is_image_shape_enabled .'";
			
			$(document).on("click", "' . $addon_id . ' .sppb-image-lightbox", function (event) {
				event.preventDefault();
				var $this = $(this);

				function applyStyles(img){
						var figure = $(".mfp-figure");
						var height = img[0].naturalHeight;
									
						if(shapeEnabled == 1){

						figure.attr("has-shape", "");
						img.css("max-height", height);
						img.css("padding", 0);

						if(cssClass){
							img.addClass(cssClass);
						}
	
						if (clipPathUrl) {
							img.css("clip-path", "url(#" + clipPathUrl + ")");
						}
					}
				}

				if ($.magnificPopup.instance) {
            		$.magnificPopup.close();
        		}
	
				if ($this.magnificPopup) {
					$this.magnificPopup({
						type: $this.data("popup_type"),
						mainClass: $this.data("mainclass"),
						callbacks: {
							imageLoadComplete: function () {
									var img = this.currItem.img;
									applyStyles(img);
							}
						}
					}).magnificPopup("open");
				}
			});
		});';
	
		return $js;
	}
	
	

	/**
	 * Load external scripts.
	 *
	 * @return 	array
	 * @since 	1.0.0
	 */
	public function scripts()
	{
		return [
			Uri::base(true) . '/components/com_sppagebuilder/assets/js/jquery.magnific-popup.min.js', 
			Uri::base(true) . '/components/com_sppagebuilder/assets/js/addons/image.js'
		];
	}

	/**
	 * Load external stylesheets.
	 *
	 * @return 	array
	 * @since 	1.0.0
	 */
	public function stylesheets()
	{
		return array(Uri::base(true) . '/components/com_sppagebuilder/assets/css/magnific-popup.css');
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

		$isEffectsEnabled = (isset($settings->is_effects_enabled) && $settings->is_effects_enabled) ? $settings->is_effects_enabled : 0;
		$isZoomOnScale = isset($settings->is_zoom_enabled) ? $settings->is_zoom_enabled : 0;
		$zoomScale = isset($settings->zoom_scale) ? $settings->zoom_scale : 0;

		$open_lightbox = (isset($settings->open_lightbox) && $settings->open_lightbox) ? $settings->open_lightbox : 0;
		$border_radius = (isset($settings->border_radius) && $settings->border_radius) ? $settings->border_radius : 0;
		$settings->position = CSSHelper::parseAlignment($settings, 'position');

		$is_image_shape_enabled = isset($settings->is_image_shape_enabled) ? $settings->is_image_shape_enabled : 0;
		$image_shape = !empty($settings->image_shape) ? $settings->image_shape : 0;
		$settings->image_effects = isset($settings->image_effects) ? $settings->image_effects : '';

		$settings->container_overflow = 'hidden';
		$settings->container_display = 'inline-block';

		$radiusStyle = '';

		$imageStyle = $cssHelper->generateStyle(
			'img',
			$settings,
			[
				'image_width' => ['width', 'max-width'],
				'image_height' => 'height'
			],
		);
	
		$imageOverlayStyle = $cssHelper->generateStyle(
			'.sppb-addon-image-overlay',
			$settings,
			[
				'overlay_color' => 'background-color',
				'image_width' => ['width', 'max-width'],
				'image_height' => 'height'
			],
			[
				'overlay_color' => false
			]
		);

		$shape_with_radius = ["quarter_slice", "half_circle"];

		if(!$is_image_shape_enabled || ($is_image_shape_enabled && $image_shape && !in_array($image_shape, $shape_with_radius))) {
			if ($border_radius) {
				$border_radius = explode(" ", $settings->border_radius);
			}

			$border_unit = [];
			$border_modifier = [];

			if (is_array($border_radius) && (count($border_radius) > 2)) {
				$border_unit = [
					'border_radius' => false
				];

				$border_modifier = [
					'border_radius' => 'spacing'
				];
			}

			$imageStyle .= $cssHelper->generateStyle(
				'.sppb-addon-single-image-container',
				$settings,
				[
					'border_radius' => 'border-radius',
				],
				$border_unit,
				$border_modifier
			);

			$imageOverlayStyle .= $cssHelper->generateStyle(
				'.sppb-addon-image-overlay',
				$settings,
				[
					'border_radius' => 'border-radius',
				],
				[
					'border_radius' => false
				],
				[
					'border_radius' => 'spacing'
				]
			);
		}

		$imagePositionStyle = $cssHelper->generateStyle(':self', $settings, ['position' => 'text-align'], false);

		if($isEffectsEnabled) {
			$settings->image_effects = $cssHelper::parseCssEffects($settings, 'image_effects');
			
			$imageStyle .= $cssHelper->generateStyle(
				'img',
				$settings,
				[
					'image_effects' => 'filter',
				],
				false
			);
		}

		$imageHoverStyle = '';
		$transformCss = $cssHelper->generateTransformStyle('img', $settings, 'transform');

		if($isZoomOnScale &&  $zoomScale) {
			$settings->container_overflow = 'hidden';
			$settings->container_display = 'inline-block';
			$settings->zoom_scale_transition = 'transform 0.5s ease';

			if (!empty($settings->transform)) {
				$settings->transform->scale = (object) [
					'x' => $zoomScale,
					'y' => $zoomScale
				];
			} else {
				$settings->transform = (object) [
					'scale' => (object) [
						'x' => $zoomScale,
						'y' => $zoomScale
					]
				];
			}

			$imageHoverStyle .= $cssHelper->generateStyle(
				'.sppb-addon-single-image-container',
				$settings,
				['container_overflow' => 'overflow',
				'container_display' => 'display'],
				false
			);
			$imageHoverStyle .= $cssHelper->generateStyle(
				'.sppb-addon-single-image-container img',
				$settings,
				['zoom_scale_transition' => 'transition'],
				false
			);
			$imageHoverStyle .= $cssHelper->generateStyle(
				'.sppb-addon-single-image-container .sppb-addon-image-overlay',
				$settings,
				['zoom_scale_transition' => 'transition'],
				false
			);
			$imageHoverStyle .= $cssHelper->generateTransformStyle(
				'.sppb-addon-single-image-container:hover img',
				$settings,
				'transform'
			);

			$imageHoverStyle .= $cssHelper->generateTransformStyle(
				'.sppb-addon-single-image-container:hover .sppb-addon-image-overlay',
				$settings,
				'transform'
			);
		} else {
			$border_unit = [];
			$border_modifier = [];

			if (is_array($border_radius) && (count($border_radius) > 2)) {
				$border_unit = [
					'border_radius' => false
				];

				$border_modifier = [
					'border_radius' => 'spacing'
				];
			}

			$radiusStyle = $cssHelper->generateStyle(
				'.sppb-addon-single-image-container img',
				$settings,
				[
					'border_radius' => 'border-radius',
				],
				$border_unit,
				$border_modifier
			);
		}

		$css = '';
		$css .= $radiusStyle;
		$css .= $transformCss;
		$css .= $imageStyle;
		$css .= $imageHoverStyle;
		$css .= $imagePositionStyle;
		$css .= $open_lightbox ? $imageOverlayStyle : '';

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
			let image_overlay = 0;
			let zoom_scale = data.zoom_scale;
			let transform = data.transform;
			let transform_zoom = { ...transform, scale: {x: zoom_scale, y: zoom_scale} }

			let image_fit = data.image_fit;
			if(_.isEmpty(image_fit)){
				image_fit = "";
			}
			let object_fit = image_fit ? "object-fit: " + image_fit + ";" : "";
			
			if(!_.isEmpty(data.overlay_color)){
				image_overlay = 1;
			}
			let open_lightbox = data.open_lightbox;
			let is_effects_enabled = data.is_effects_enabled;
			let is_image_shape_enabled = data.is_image_shape_enabled;
			let image_shape = _.isString(data.image_shape) && !_.isEmpty(data.image_shape) ? data.image_shape : "";
			let image_shape_scale = !_.isEmpty(data.image_shape_scale) ? data.image_shape_scale : 1;
			
			let image_shape_class = "";
			let clip_path_url = null;
			let shape_data = "";
			let cssClass = null;
			const default_shapes = ["circle", "quarter_slice", "half_circle", "bevel", "star", "pentagon", "right_point", "triangle", "trapezoid", "right_chevron", "right_arrow", "rabbet"];

			if(is_image_shape_enabled && image_shape) {
				let image_shape_value = data.image_shape.replaceAll("_", "-");
				image_shape_class = `sppb-addon-image-shape-${image_shape_value}`;

			
				if(!default_shapes.includes(image_shape)) {
					let decoded_shape = atob(image_shape);

					let pattern = /<path.*?d="(.*?)".*?>/s;
					let matches = decoded_shape.match(pattern);
					shape_data = matches ? matches[1] : null;
					clip_path_url = "svg-shape-" + data.id;
				}
			}

			let shape_with_radius = ["quarter_slice", "half_circle"];

			let alt_text = data.alt_text;
			let addon_title = data.title || "";
			let image_title = data.image_title || "";

			let final_image_title = _.isEmpty(image_title) ? addon_title : image_title;

			if(_.isEmpty(alt_text)){
				if(!_.isEmpty(final_image_title)) {
					alt_text = final_image_title;
				}
			}

			if(default_shapes.includes(image_shape) && is_image_shape_enabled && image_shape) {
				cssClass = "sppb-addon-image-shape-" + data.image_shape.replaceAll("_", "-");
			}

			var addonId = "#sppb-addon-" + data.id;
			
		#>
		
		<style type="text/css">';

		// global
		$output .= $lodash->alignment('text-align', '', 'data.position');

		// title
		$typographyFallbacks = [
			'font'           => 'data.title_font_family',
			'size'           => 'data.title_fontsize',
			'line_height'    => 'data.title_lineheight',
			'letter_spacing' => 'data.title_letterspace',
			'uppercase'      => 'data.title_font_style?.uppercase',
			'italic'         => 'data.title_font_style?.italic',
			'underline'      => 'data.title_font_style?.underline',
			'weight'         => 'data.title_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-addon-title', 'data.title_typography', $typographyFallbacks);
		$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');
		$output .= $lodash->spacing('padding', '.sppb-addon-title', 'data.title_padding');

		$output .= '<# if(is_effects_enabled) { #>';
		$output .= $lodash->effects('img', 'data.image_effects');
		$output .= '<# } #>';

		// image
		$output .= '<# if(!is_image_shape_enabled || (is_image_shape_enabled && image_shape && !shape_with_radius.includes(image_shape))) { #>';
		$output .= '<# if((data.border_radius + "").split(" ").length < 2) { #>';
		$output .= $lodash->unit('border-radius', '.sppb-addon-single-image-container', 'data.border_radius', 'px');
		$output .= '<# } else { #>';
		$output .= '#sppb-addon-{{data.id}} .sppb-addon-single-image-container {
			{{window.getSplitRadius(data.border_radius)}}	
		}';
		$output .= '<# } } #>';
		$output .= $lodash->unit('height', 'img', 'data.image_height', 'px');
		$output .= $lodash->unit('width', 'img', 'data.image_width', 'px');
		$output .= $lodash->unit('max-width', 'img', 'data.image_width', 'px');
		$output .= $lodash->color('background-color', '.sppb-addon-image-overlay', 'data.overlay_color');
		$output .= '<# if((data.border_radius + "").split(" ").length < 2) { #>';
		$output .= $lodash->unit('border-radius', '.sppb-addon-image-overlay', 'data.border_radius', 'px');
		$output .= '<# } else { #>';
		$output .= $lodash->unit('border-radius', '.sppb-addon-image-overlay', 'data.border_radius', '');
		$output .= '<# } #>';
		
		$output .= $lodash->generateTransformCss('img', 'transform');

		$output .= '.sppb-addon-single-image-container {  overflow: hidden; display: block; }';
		$output .= '.sppb-addon-single-image-container img{transition: transform 0.5s ease;}';
		$output .= '.sppb-addon-single-image-container .sppb-addon-image-overlay{transition: transform 0.5s ease;}';
		$output .= '<# if(data.is_zoom_enabled && zoom_scale) { #>';
		$output .= $lodash->generateTransformCss('.sppb-addon-single-image-container:hover img, .sppb-addon-single-image-container:hover .sppb-addon-image-overlay', 'transform_zoom');
		$output .= '<# } #>';

		$output .= '
		</style>
		
		<# if(data.image) { 
			let media = {}
			if (typeof data.image !== "undefined" && typeof data.image.src !== "undefined") {
				media = data.image
			} else {
				media = {src: data.image, height:"", width:""}
			}
				var regex = /\bhttps?:\/\//;
				if(!regex.test(media.src)){
					media.src = window.pagebuilder_base + media.src;
				}
			#>
			<div class="sppb-addon sppb-addon-single-image {{ data.class }} sppb-addon-image-shape">
				<# if( !_.isEmpty( data.title ) && data.title_position != "bottom" ){ #><{{ data.heading_selector }} class="sppb-addon-title sp-inline-editable-element" data-id={{data.id}} data-fieldName="title" contenteditable="true">{{ data.title }}</{{ data.heading_selector }}><# } #>
				<div class="sppb-addon-content">
					<div class="sppb-addon-single-image-container">
						<# if(image_overlay && open_lightbox) { #>
							<div class="sppb-addon-image-overlay"></div>
						<# } #>
						
						<# if(open_lightbox) { #>
							
							<a class="sppb-magnific-popup sppb-image-lightbox sppb-addon-image-overlay-icon" data-popup_type="image" data-mainclass="mfp-no-margins mfp-with-zoom" data-clip-path={{clip_path_url ?? false}} data-shape-enabled={{data.is_image_shape_enabled === 0 ? false : true}} data-shape-css={{cssClass ?? false}} href=\'{{ media.src }}\'>+</a>
						<# } #>
			
						<# if(!open_lightbox) {  #>
							<# 
							const isUrlObject = _.isObject(data.link) && (data.link.url || data.link.menu || data.link.page );
							const isUrlString = _.isString(data.link) && data.link !== "";
							

							if(isUrlObject || isUrlString ) {
								const urlObj = data?.link?.url ? data.link : window.getSiteUrl(data.link, data.target);
								const {url, new_tab, nofollow, noopener, noreferrer, type} = urlObj;
								const target = new_tab ? "_blank" : "";
								
								let rel="";
								rel += nofollow ? "nofollow": "";
								rel += noopener ? " noopener": "";
								rel += noreferrer ? " noreferrer": "";
							
								const clickUrl = (type === "url" && url) || (type ==="menu" && urlObj.menu) || ( (type === "page" && !!urlObj.page) && "index.php/component/sppagebuilder/index.php?option=com_sppagebuilder&view=page&id=" +urlObj.page) || "";
								#>
								  <# if(clickUrl) {#>
									<a href=\'{{clickUrl.trim()}}\' target=\'{{target}}\' rel=\'{{rel}}\'  >
								  <#  }#>
							  <#} #>
  
						  <#  }#>
						
						  <# if(is_image_shape_enabled && image_shape && !default_shapes.includes(image_shape)) { #>
							<# if(media.src.indexOf("http://") == -1 && media.src.indexOf("https://") == -1){ #>
								<img data-scale="{{{image_shape_scale}}}" style="clip-path: url(#svg-shape-{{{data.id}}}); visibility: hidden;" class="sppb-img-responsive" src=\'{{ pagebuilder_base + media.src }}\' alt="{{ alt_text }}" title="{{ final_image_title }}" style="{{object_fit}}">
							<# } else { #>
								<img data-scale="{{{image_shape_scale}}}" style="clip-path: url(#svg-shape-{{{data.id}}}); visibility: hidden;" class="sppb-img-responsive" src=\'{{ media.src }}\' alt="{{ alt_text }}" title="{{ final_image_title }}" style="{{object_fit}}">
							<# } #>

							<svg>
								<defs>
								<clipPath id="svg-shape-{{{data.id}}}">
									<path fill="currentColor" d="{{{shape_data}}}" />
								</clipPath>
								</defs>
							</svg>
							<# } else { #>
								<# if(media.src.indexOf("http://") == -1 && media.src.indexOf("https://") == -1){ #>
									<img class="sppb-img-responsive {{image_shape_class}}" src=\'{{ pagebuilder_base + media.src }}\' alt="{{ alt_text }}" title="{{ final_image_title }}" style="{{object_fit}}">
								<# } else { #>
									<img class="sppb-img-responsive {{image_shape_class}}" src=\'{{ media.src }}\' alt="{{ alt_text }}" title="{{ final_image_title }}" style="{{object_fit}}">
								<# } #>
							<# } #>
						
						<# if(!open_lightbox) { #>
							</a>
						<# } #>

					</div>
					<# if( !_.isEmpty( data.title ) && data.title_position == "bottom" ){ #><{{ data.heading_selector }} class="sppb-addon-title" style="display: block;">{{ data.title }}</{{ data.heading_selector }}><# } #>
				</div>
			</div>
		<# } else { #>
			<div class="builder-canvas-placeholder-image">
				<div class="builder-icon">
					<svg fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M25.884 2H5.046C3.366 2 2 3.406 2 5.139v19.726C2 26.595 3.366 28 5.046 28h20.838c1.68 0 3.045-1.405 3.045-3.135V5.139C28.929 3.406 27.564 2 25.884 2zm-6.47 4.668c1.624 0 2.941 1.357 2.941 3.03 0 1.673-1.317 3.03-2.942 3.03-1.624 0-2.941-1.357-2.941-3.03 0-1.673 1.317-3.03 2.941-3.03zM24.928 25.1H6.421c-.813 0-1.175-.605-.808-1.352l5.048-10.3c.366-.747 1.063-.813 1.557-.149l5.076 6.833c.494.665 1.357.722 1.928.126l1.242-1.295c.57-.596 1.412-.522 1.877.163l3.216 4.732c.465.686.185 1.242-.628 1.242z" fill="currentColor"/></svg>
				</div>
			</div>
		<# } #>';

		return $output;
	}

	static function getImageShape($shape_name)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('a.shape');
		$query->from($db->quoteName('#__sppagebuilder_image_shapes', 'a'));
		$query->where($db->quoteName('a.name') . " = " . $db->quote($shape_name));
		$db->setQuery($query);
		$shape = $db->loadResult();

		return $shape;
	}
}
