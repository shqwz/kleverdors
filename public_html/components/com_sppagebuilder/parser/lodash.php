<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

use Joomla\CMS\Uri\Uri;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Helper class for handling the lodash string.
 *
 * @since	4.0.0
 */
final class Lodash extends HelperBase
{
	/**
	 * Device Size
	 *
	 * @var array
	 */
	private $sizes = ["lg", "md", "sm", "xs"];
	/**
	 * The constructor function for the lodash.
	 *
	 * @param string $id 	The unique CSS ID.
	 * @param bool $force	Flag to set the ID as whatever it is provided, no sanitization is required.
	 */
	public function __construct(string $id = '', bool $force = false)
	{
		parent::__construct($id, $force);
	}

	/**
	 * Manage border style css property
	 *
	 * @param 	string 	$property	CSS Property.
	 * @param 	string 	$selector	CSS Selector.
	 * @param 	string 	$data		Value for CSS Property.
	 * @param 	string 	$static		Provided any static style value. If provided that would be contacted.
	 * 
	 * @return 	string 	CSS Value for Border.
	 * @since 	4.0.0
	 */
	public function border(string $property, string $selector, string $data, $static = ''): string
	{
		$selector = $this->generateSelector($selector);

		$css   = [];
		$css[] = $selector . ' {';
		if (!empty($static))
		{
			$css[] = $static;
		}
		$css[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';
		$css[] = $property . ': ' . $this->inlineBlock($data . '[window.builderDefaultDevice]') . ';';
		$css[] = '<# } else { #>';
		$css[] = $property . ': ' . $this->inlineBlock($data) . ';';
		$css[] = '<# } #>';
		$css[] = '}';

		return implode("\n", $css);
	}

	/**
	 * manage alignment css
	 *
	 * @param	string 	$property
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function alignment(string $property, string $selector, string $data): string
	{
		$selector = $this->generateSelector($selector);
		$css = [];

		if (isset($property) && isset($selector) && isset($data))
		{
			$css[] = $selector . ' {';
			$css[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';
			$css[] = $property . ': ' . $this->inlineBlock($data . '[window.builderDefaultDevice]') . ';';
			$css[] = '<# } else { #>';
			$css[] = '<# if ( ' . $data . '== "sppb-text-center" ||  ' . $data . '== "center" ) { #>';
			$css[] = $property . ': center;';
			$css[] = '<# } else if ( ' . $data . '== "sppb-text-right" || ' . $data . '== "right" ) { #>';
			$css[] = $property . ': right;';
			$css[] = '<# } else if ( ' . $data . '== "sppb-text-left" || ' . $data . '== "left") { #>';
			$css[] = $property . ': left;';
			$css[] = '<# } #>';
			$css[] = '<# } #>';
			$css[] = '}';

			// render media query css
			$css[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';
			$css[] = $this->mediaQuery($property, $selector, $data);
			$css[] = '<# } #>';
		}

		return implode("\n", $css);
	}

	/**
	 * Generate transform css
	 *
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * 
	 * @return 	string
	 * @since 	5.2.10
	 */
	public function generateTransformCss(string $selector, string $data): string
	{
		$selector = $this->generateSelector($selector);

		$css = [];
		$transformFunctions = [];

		$transformOriginFunctions = [];

		if (isset($selector) && isset($data))
		{
			$transformFunctions[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.move)) { #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.move) && !_.isEmpty(' . $data . '.move.x) && !_.isEmpty(' . $data . '.move.x.value) && !_.isEmpty(' . $data . '.move.x.unit)) { #>';
			$transformFunctions[] = 'translateX(' . $this->inlineBlock($data . '.move.x.value') . $this->inlineBlock($data . '.move.x.unit') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.move) && !_.isEmpty(' . $data . '.move.y) && !_.isEmpty(' . $data . '.move.y.value) && !_.isEmpty(' . $data . '.move.y.unit)) { #>';
			$transformFunctions[] = 'translateY(' . $this->inlineBlock($data . '.move.y.value') . $this->inlineBlock($data . '.move.y.unit') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.rotate)) { #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.rotate) && !_.isEmpty(' . $data . '.rotate.x) && !_.isEmpty(' . $data . '.rotate.x.value) && !_.isEmpty(' . $data . '.rotate.x.unit)) { #>';
			$transformFunctions[] = 'rotateX(' . $this->inlineBlock($data . '.rotate.x.value') . $this->inlineBlock($data . '.rotate.x.unit') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.rotate) && !_.isEmpty(' . $data . '.rotate.y) && !_.isEmpty(' . $data . '.rotate.y.value) && !_.isEmpty(' . $data . '.rotate.y.unit)) { #>';
			$transformFunctions[] = 'rotateY(' . $this->inlineBlock($data . '.rotate.y.value') . $this->inlineBlock($data . '.rotate.y.unit') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.rotate) && !_.isEmpty(' . $data . '.rotate.z) && !_.isEmpty(' . $data . '.rotate.z.value) && !_.isEmpty(' . $data . '.rotate.z.unit)) { #>';
			$transformFunctions[] = 'rotateZ(' . $this->inlineBlock($data . '.rotate.z.value') . $this->inlineBlock($data . '.rotate.z.unit') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.scale)) { #>';
				
			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.scale) && !_.isEmpty(' . $data . '.scale.x)) { #>';
			$transformFunctions[] = 'scaleX(' . $this->inlineBlock($data . '.scale.x') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.scale) && !_.isEmpty(' . $data . '.scale.y)) { #>';
			$transformFunctions[] = 'scaleY(' . $this->inlineBlock($data . '.scale.y') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.skew)) { #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.skew) && !_.isEmpty(' . $data . '.skew.x) && !_.isEmpty(' . $data . '.skew.x.value) && !_.isEmpty(' . $data . '.skew.x.unit)) { #>';
			$transformFunctions[] = 'skewX(' . $this->inlineBlock($data . '.skew.x.value') . $this->inlineBlock($data . '.skew.x.unit') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# if(!_.isEmpty(' . $data . '.skew) && !_.isEmpty(' . $data . '.skew.y) && !_.isEmpty(' . $data . '.skew.y.value) && !_.isEmpty(' . $data . '.skew.y.unit)) { #>';
			$transformFunctions[] = 'skewY(' . $this->inlineBlock($data . '.skew.y.value') . $this->inlineBlock($data . '.skew.y.unit') . ')';
			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# } #>';

			$transformFunctions[] = '<# } #>';


			// transform origin styles
			$transformOriginFunctions[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';

			$transformOriginFunctions[] = '<# if(!_.isEmpty(' . $data . '.transform_origin)) { #>';

			$transformOriginFunctions[] = '<# if(!_.isEmpty(' . $data . '.transform_origin) && !_.isEmpty(' . $data . '.transform_origin.left) && !_.isEmpty(' . $data . '.transform_origin.left.value) && !_.isEmpty(' . $data . '.transform_origin.left.unit)) { #>';
			$transformOriginFunctions[] = $this->inlineBlock($data . '.transform_origin.left.value') . $this->inlineBlock($data . '.transform_origin.left.unit');
			$transformOriginFunctions[] = '<# } #>';

			$transformOriginFunctions[] = '<# if(!_.isEmpty(' . $data . '.transform_origin) && !_.isEmpty(' . $data . '.transform_origin.top) && !_.isEmpty(' . $data . '.transform_origin.top.value) && !_.isEmpty(' . $data . '.transform_origin.top.unit)) { #>';
			$transformOriginFunctions[] = $this->inlineBlock($data . '.transform_origin.top.value') . $this->inlineBlock($data . '.transform_origin.top.unit');
			$transformOriginFunctions[] = '<# } #>';

			$transformOriginFunctions[] = '<# } #>';

			$transformOriginFunctions[] = '<# } #>';
		}
	
		// If there are transform functions, construct the CSS rule
		if (!empty($transformFunctions) || !empty($transformOriginFunctions)) {
			$css[] = $selector . ' {';
			if(!empty($transformFunctions)) {
				$css[] = '    transform: ' . implode(' ', $transformFunctions) . ';';
			}

			if(!empty($transformOriginFunctions)) {
				
				$css[] = '    transform-origin: ' . implode(' ', $transformOriginFunctions) . ';';
			}
			$css[] = '}';
		}

		return implode("\n", $css);
	}

	/**
	 * Generate effects css
	 *
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * 
	 * @return 	string
	 * @since 	5.2.8
	 */
	public function effects(string $selector, string $data): string
	{
		$selector = $this->generateSelector($selector);

		$css = [];
		$filterFunctions = [];

		if (isset($selector) && isset($data))
		{
			$filterFunctions[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';
			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.opacity)) { #>';
			$filterFunctions[] = 'opacity(' . $this->inlineBlock($data . '.opacity') . '%)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.blur)) { #>';
			$filterFunctions[] = 'blur(' . $this->inlineBlock($data . '.blur') . 'px)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.brightness)) { #>';
			$filterFunctions[] = 'brightness(' . $this->inlineBlock($data . '.brightness') . '%)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.contrast)) { #>';
			$filterFunctions[] = 'contrast(' . $this->inlineBlock($data . '.contrast') . '%)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.saturate)) { #>';
			$filterFunctions[] = 'saturate(' . $this->inlineBlock($data . '.saturate') . '%)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.grayscale)) { #>';
			$filterFunctions[] = 'grayscale(' . $this->inlineBlock($data . '.grayscale') . '%)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.invert)) { #>';
			$filterFunctions[] = 'invert(' . $this->inlineBlock($data . '.invert') . '%)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.sepia)) { #>';
			$filterFunctions[] = 'sepia(' . $this->inlineBlock($data . '.sepia') . '%)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# if(!_.isEmpty(' . $data . '.hue_rotate)) { #>';
			$filterFunctions[] = 'hue-rotate(' . $this->inlineBlock($data . '.hue_rotate') . 'deg)';
			$filterFunctions[] = '<# } #>';

			$filterFunctions[] = '<# } #>';
		}
	
		// If there are filter functions, construct the CSS rule
		if (!empty($filterFunctions)) {
			$css[] = $selector . ' {';
			$css[] = '    filter: ' . implode(' ', $filterFunctions) . ';';
			$css[] = '}';
		}

		return implode("\n", $css);
	}

	public function generate_layout_position_css(string $image_icon_position_device, string $addon_id): string
	{
		$css = [];

		$positions = ['before', 'after', 'left', 'right'];

		foreach ($positions as $position) {
			$css[] = '<# if (' . $image_icon_position_device . ' === "' . $position . '") { #>';
			$css[] = ' #sppb-addon-{{data.id}} .sppb-addon-content-align-' . $position . ' {';
			$css[] = '    display: block;';
			$css[] = '}';
			$css[] = '<# } #>';
			$css[] = '<# if (' . $image_icon_position_device . ' !== "' . $position . '") { #>';
			$css[] = ' #sppb-addon-{{data.id}} .sppb-addon-content-align-' . $position . ' {';
			$css[] = '    display: none;';
			$css[] = '}';
			$css[] = '<# } #>';
		}

		$isContainer = false;
		$cssStyle = '';
		$featureImageStyle = '';

		if (in_array($image_icon_position_device, ['left', 'right'])) {
			$imageMarginSelector = '.sppb-media .pull-left, .sppb-media .pull-right';
			$cssStyle .= $this->unit('width', $imageMarginSelector, 'data.feature_image_width', '%');
		}

		elseif (in_array($image_icon_position_device, ['before', 'after'])) {
			$imageMarginSelector = '.sppb-img-container';
			$isContainer = true;
		}

		if (!empty($imageMarginSelector)) {
			$featureImageStyle .= $this->unit('margin', $imageMarginSelector, 'data.feature_image_margin', '', true, '', $isContainer ? 'display: block;': '');
		}

		$css[] = $cssStyle;
		$css[] = $featureImageStyle;

		return implode("\n", $css);
	}
	

	/**
	 * Flex Alignment function
	 *
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function flexAlignment(string $selector, string $data): string
	{
		$selector = $this->generateSelector($selector);
		$css = [];

		$css[] = $selector . ' {';
		$css[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';
		$css[] = '<# if (' . $data . '[window.builderDefaultDevice] == "left") { #>';
		$css[] = '-webkit-box-pack: start;';
		$css[] = '-ms-flex-pack: start;';
		$css[] = 'justify-content: flex-start;';
		$css[] = '<# } else if (' . $data . '[window.builderDefaultDevice] == "right") { #>';
		$css[] = '-webkit-box-pack: end;';
		$css[] = '-ms-flex-pack: end;';
		$css[] = 'justify-content: flex-end;';
		$css[] = '<# } else if (' . $data . '[window.builderDefaultDevice] == "center") { #>';
		$css[] = '-webkit-box-pack: center;';
		$css[] = '-ms-flex-pack: center;';
		$css[] = 'justify-content: center;';
		$css[] = '<# } #>';
		$css[] = '<# } else {#>';
		$css[] = '<# if (' . $data . '== "left" || ' . $data . ' == "sppb-text-left")  { #>';
		$css[] = '-webkit-box-pack: start;';
		$css[] = '-ms-flex-pack: start;';
		$css[] = 'justify-content: flex-start;';
		$css[] = '<# } else if (' . $data . '== "right" || ' . $data . ' == "sppb-text-right") { #>';
		$css[] = '-webkit-box-pack: end;';
		$css[] = '-ms-flex-pack: end;';
		$css[] = 'justify-content: flex-end;';
		$css[] = '<# } else if (' . $data . '== "center" || ' . $data . ' == "sppb-text-center") { #>';
		$css[] = '-webkit-box-pack: center;';
		$css[] = '-ms-flex-pack: center;';
		$css[] = 'justify-content: center;';
		$css[] = '<# } #>';
		$css[] = '<# } #>';
		$css[] = '}';

		foreach ($this->sizes as $size)
		{
			$css[] = $this->mediaQueryDevice($size);
			$css[] = $selector . ' {';
			$css[] = '<# if (' . $data . '?.' . $size . ' == "left") { #>';
			$css[] = '-webkit-box-pack: start;';
			$css[] = '-ms-flex-pack: start;';
			$css[] = 'justify-content: flex-start;';
			$css[] = '<# } else if (' . $data . '?.' . $size . ' == "right") { #>';
			$css[] = '-webkit-box-pack: end;';
			$css[] = '-ms-flex-pack: end;';
			$css[] = 'justify-content: flex-end;';
			$css[] = '<# } else if (' . $data . '?.' . $size . ' == "center") { #>';
			$css[] = '-webkit-box-pack: center;';
			$css[] = '-ms-flex-pack: center;';
			$css[] = 'justify-content: center;';
			$css[] = '<# } #>';
			$css[] = '}';
			$css[] = '}';
		}

		return implode("\n", $css);
	}

	/**
	 * Flex function
	 *
	 * @param 	string 	$property
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * @param 	string 	$unit
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function flex(string $property, string $selector, string $data, $unit = ''): string
	{

		$selector = $this->generateSelector($selector);

		$css   = [];
		$css[] = $selector . ' {';
		$css[] = '<# if (_.isObject(' . $data . ') && !_.isEmpty(' . $data . ') ) { #>';
		$css[] = $property . ': 0 0 ' . $this->inlineBlock($data . '?.[window.builderDefaultDevice]') . $unit . ';';
		$css[] = '<# } else { #>';
		$css[] = $property . ': 0 0 ' . $this->inlineBlock($data) . $unit . ';';
		$css[] = '<# } #>';
		$css[] = '}';

		foreach ($this->sizes as $size)
		{
			$css[] = $this->mediaQueryDevice($size);
			$css[] = $selector . ' {';
			$css[] = '<# if (_.isObject(' . $data . ') && !_.isEmpty(' . $data . ') ) { #>';
			$css[] = $property . ': 0 0 ' . $this->inlineBlock($data . '?.' . $size) . $unit . ';';
			$css[] = '<# } else { #>';
			$css[] = $property . ': 0 0 ' . $this->inlineBlock($data) . $unit . ';';
			$css[] = '<# } #>';
			$css[] = '}';
			$css[] = '}';
		}
		return implode("\n", $css);
	}

	/**
	 * Manage spacing property
	 *
	 * @param 	string  $property 	CSS property name.
	 * @param 	string  $selector 	CSS selector name.
	 * @param 	string  $data		CSS value.
	 * @param 	boolean $isStatic 	Check value is static.
	 * 
	 * @return 	string 				The generated spacing string.
	 * @since 	4.0.0
	 */
	public function spacing(string $property, string $selector, string $data, $isStatic = false): string
	{
		$selector = $this->generateSelector($selector);

		$css   = [];
		$css[] = '
		<#
		var value = window.getMarginPadding( ' . $data . ', `' . $property . '`);
		
		#>
		';
		$css[] = $selector . ' {';

		if ($isStatic)
		{
			$css[] = $property . ': ' . $data . ';';
		}
		else
		{
			$css[] = '<# if(_.isObject(value) && !_.isEmpty(value)) { #>';
			$css[] =  $this->inlineBlock('value?.[window.builderDefaultDevice]');
			$css[] = '<# } else { #>';
			$css[] = $this->inlineBlock('value');
			$css[] = '<# } #>';
		}

		$css[] = '}';

		foreach ($this->sizes as $size)
		{
			$css[] = $this->mediaQueryDevice($size);
			$css[] = $selector . ' {';
			$css[] = '<# if(_.isObject(value) && !_.isEmpty(value)) { #>';
			$css[] =  $this->inlineBlock('value?.' . $size);
			$css[] = '<# } else { #>';
			$css[] = $this->inlineBlock('value');
			$css[] = '<# } #>';
			$css[] = '}';
			$css[] = '}'; // end selector
		}

		return implode("\n", $css);
	}

	/**
	 * manage unit
	 *
	 * @param	string 	$property 
	 * @param	string 	$selector
	 * @param	string 	$data
	 * @param	string 	$unit
	 * @param	boolean $responsive
	 * @param	string 	$prefix
	 * @param	string 	$static	Provided any static style value. If provided that would be contacted.
	 * 
	 * @return	string
	 * @since 4.0.0
	 */
	public function unit(string $property, string $selector, string $data, $unit = '', $responsive = true, $prefix = '', $static = ''): string
	{
		$selector = $this->generateSelector($selector);

		$css      = [];
		$css[]    = $selector . ' {';
		if (!empty($static))
		{
			$css[] = $static;
		}
		$css[]    = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';
		$css[]    = $property . ': ' . $prefix . $this->inlineBlock($data . '?.[window.builderDefaultDevice]') . $unit . ';';
		$css[]    = '<# } else { #>';
		$css[]    = $property . ': ' . $prefix . $this->inlineBlock($data) . $unit . ';';
		$css[]    = '<# } #>';
		$css[]    = '}';

		if ($responsive)
		{
			$css[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';
			$css[] = $this->mediaQuery($property, $selector, $data, $unit, false, $prefix);
			$css[] = '<# } #>';
		}

		return implode("\n", $css);
	}


	/**
	 * Generate CSS for a given selector and properties
	 * 
	 * @param 	string 	$selector 		The CSS selector.
	 * @param 	array 	$cssProperties 	An associative array of CSS properties and their values.
	 * @param 	array 	$cssUnits 		An associative array of CSS units for each property.
	 * @param 	array 	$defaultValues 	Default values for the properties.
	 * @param 	bool 	$responsive 		Whether to generate responsive CSS.
	 * @param 	string 	$static 			Static CSS value to be included.
	 * 
	 * @return 	string 	The generated CSS string.
	 */
	public function generateStyle(string $selector, array $cssProperties, array $cssUnits = ['default' => 'px'], bool $responsive = true, array $defaultValues = [], string $static = '')
	{
		$css = '';

		// call unit function for each property
		foreach ($cssProperties as $property => $value)
		{
			// if css property is color then call color function
			if ($property === 'color' || $property === 'background-color' || $property === 'background')
			{
				$css .= $this->color($property, $selector, 'data.' . $value, $defaultValues);
				continue;
			}

			// if css property is box shadow then call box shadow function
			if ($property === 'box-shadow')
			{
				$css .= $this->boxShadow($property, $selector, 'data.' . $value, $defaultValues);
				continue;
			}
			// if css property is text shadow then call text shadow function
			if ($property === 'text-shadow')
			{
				$css .= $this->textShadow($property, $selector, 'data.' . $value, $defaultValues);
				continue;
			}
			// if css property is alignment then call alignment function
			if ($property === 'text-align')
			{
				$css .= $this->alignment($property, $selector, 'data.' . $value);
				continue;
			}
			// if css property is border then call border function
			if ($property === 'border')
			{
				$css .= $this->border($property, $selector, 'data.' . $value, $static);
				continue;
			}
			// if css property is spacing then call spacing function
			if ($property === 'margin' || $property === 'padding')
			{
				$css .= $this->spacing($property, $selector, 'data.' . $value);
				continue;
			}

			$unit = isset($cssUnits[$value]) ? $cssUnits[$value] : $cssUnits['default'];
			$css .= $this->unit($property, $selector, 'data.' . $value, $unit, $responsive, '', $static);
		}

		if(!empty($static))
		{
			$selector = $this->generateSelector($selector);
			$css .= $selector . ' {';
			$css .= $static;
			$css .= '}';
		}

		return $css;

	}

	/**
	 * manage color
	 *
	 * @param 	string 	$property
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * @param 	array 	$dependencies
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function color(string $property, string $selector, string $data, $dependencies = []): string
	{
		$selector = $this->generateSelector($selector);

		$css = [];
		$css[]    = $selector . ' {';

		if ($property === 'background-color' || $property === 'background')
		{
			$css[] = '<# if (!_.isEmpty(' . $data . ')) { #>';
			$css[] = '<# if (_.isObject(' . $data . ') && ' . $data . '?.type == "solid") { #>';
			$css[] = 'background-color: ' . $this->inlineBlock($data . '.color') . ';';
			$css[] = '<# } else if (_.isObject(' . $data . ') && ' . $data . '?.type == "linear") { #>';
			$css[] = 'background: -webkit-linear-gradient(' . $this->inlineBlock($data . '?.deg || 0') . 'deg, ' .  $this->inlineBlock($data . '?.color || "#222"') . ' ' . $this->inlineBlock($data . '?.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#222"') . ' ' . $this->inlineBlock($data . '?.pos2 || 100') . '%' . ');';
			$css[] = 'background: linear-gradient(' . $this->inlineBlock($data . '?.deg || 0') . 'deg, ' .  $this->inlineBlock($data . '?.color || "#222"') . ' ' . $this->inlineBlock($data . '?.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#222"') . ' ' . $this->inlineBlock($data . '?.pos2 || 100') . '%' . ');';
			$css[] = '<# } else if (_.isObject(' . $data . ') && ' . $data . '?.type == "radial") { #>';
			$css[] = 'background: -webkit-radial-gradient(at ' . $this->inlineBlock($data . '?.radialPos || "center center"') . ', ' .  $this->inlineBlock($data . '?.color || "#222"') . ' ' . $this->inlineBlock($data . '?.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#222"') . ' ' . $this->inlineBlock($data . '?.pos2 || 100') . '%' . ');';
			$css[] = 'background: radial-gradient(at ' . $this->inlineBlock($data . '?.radialPos || "center center"') . ', ' .  $this->inlineBlock($data . '?.color || "#222"') . ' ' . $this->inlineBlock($data . '?.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#222"') . ' ' . $this->inlineBlock($data . '?.pos2 || 100') . '%' . ');';
			$css[] = '<# } else { #>';
			$css[] = 'background-color: ' . $this->inlineBlock($data) . ';';
			$css[] = '<# } #>';
			$css[] = '<# } #>';
		}
		else if ($property == 'color')
		{
			$css[] = '<# if (!_.isEmpty(' . $data . ')) { #>';
			$css[] = '<# if (_.isObject(' . $data . ') && ' . $data . '?.type == "solid") { #>';
			$css[] = 'color: ' . $this->inlineBlock($data . '.color') . ';';
			$css[] = '<# } else if (_.isObject(' . $data . ') && ' . $data . '?.type == "linear") { #>';
			$css[] = '-webkit-background-clip: text;';
			$css[] = 'background-clip: text;';
			$css[] = '-webkit-text-fill-color: transparent;';
			$css[] = 'background-image: -webkit-linear-gradient(' . $this->inlineBlock($data . '?.deg || 0') . 'deg, ' .  $this->inlineBlock($data . '?.color || "#398AF1"') . ' ' . $this->inlineBlock($data . '?.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#5EDCED"') . ' ' . $this->inlineBlock($data . '?.pos2 || 100') . '%' . ');';
			$css[] = 'background-image: linear-gradient(' . $this->inlineBlock($data . '?.deg || 0') . 'deg, ' .  $this->inlineBlock($data . '?.color || "#398AF1"') . ' ' . $this->inlineBlock($data . '?.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#5EDCED"') . ' ' . $this->inlineBlock($data . '?.pos2 || 100') . '%' . ');';
			$css[] = '<# } else if (_.isObject(' . $data . ') && ' . $data . '?.type == "radial") { #>';
			$css[] = '-webkit-background-clip: text;';
			$css[] = 'background-clip: text;';
			$css[] = '-webkit-text-fill-color: transparent;';
			$css[] = 'background-image: -webkit-radial-gradient(at ' . $this->inlineBlock($data . '?.radialPos || "center center"') . ', ' .  $this->inlineBlock($data . '?.color || "#398AF1"') . ' ' . $this->inlineBlock($data . '.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#5EDCED"') . ' ' . $this->inlineBlock($data . '.pos2 || 100') . '%' . ');';
			$css[] = 'background-image: radial-gradient(at ' . $this->inlineBlock($data . '?.radialPos || "center center"') . ', ' .  $this->inlineBlock($data . '?.color || "#398AF1"') . ' ' . $this->inlineBlock($data . '.pos || 0') . '%, ' . $this->inlineBlock($data . '?.color2 || "#5EDCED"') . ' ' . $this->inlineBlock($data . '.pos2 || 100') . '%' . ');';
			$css[] = '<# } else { #>';
			$css[] = 'color: ' . $this->inlineBlock($data) . ';';
			$css[] = '<# } #>';
			$css[] = '<# } #>';
		}

		$css[] = ' }';

		return implode("\n", $css);
	}

	/**
	 * Load google or local fonts
	 *
	 * @param	string 		$data
	 * @param	boolean 	$isFallbacks
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	private function loadFonts(string $data, $isFallbacks = false): string
	{
		$content = ($isFallbacks) ? $data . ';' : $data . '.font;';

		$installedGoogleFonts = SppagebuilderHelperSite::getInstalledGoogleFonts();

		$css  = '<# var sppb_head = window.frames["sp-pagebuilder-view"].window.document.head; #>';
		$css .= '<# var sppb_doc  = window.frames["sp-pagebuilder-view"].window.document;#>';
		$css .= '<# var sppb_fontFamily = ' . $content . ' #>';
		$css .= '<# const sppb_fontType = ' . $data . '?.type' . ' ?? "google" #>';
		$css .= '<# const installed_google_fonts = ' . json_encode($installedGoogleFonts) . ' #>';

		$css .= '<#
	
				const systemFonts = [
					"System",
					"Google Fonts",
					"Arial",
					"Tahoma",
					"Verdana",
					"Helvetica",
					"Times New Roman",
					"Trebuchet MS",
					"Georgia",
				];

				if (!_.isEmpty(sppb_fontFamily) && !systemFonts.includes(sppb_fontFamily) && !_.isEmpty(sppb_fontType) && (sppb_fontType !== "google" || (sppb_fontType === "google" && !disableGoogleFonts))){
					let linkTagIdPrefix = "sppagebuilder-google-font-";

					let fontLink = sppb_head.querySelector(
						`#${linkTagIdPrefix}${sppb_fontFamily.toLowerCase().replace(/\s+/g, "_")}`
					);

					linkTagLink = `' . Uri::root() . 'media/com_sppagebuilder/assets/google-fonts/${sppb_fontFamily}/stylesheet.css`;

					if(sppb_fontType === "google") {
						const isFontInstalled = installed_google_fonts?.includes(sppb_fontFamily);

						if(!isFontInstalled) {
							linkTagLink = `https://fonts.googleapis.com/css?family="${sppb_fontFamily}":100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic&display=swap`;
						}
					}

					if(sppb_fontType === "local") {
						linkTagIdPrefix = "sppagebuilder-local-font-";
						fontLink = sppb_head.querySelector(
							`#${linkTagIdPrefix}${sppb_fontFamily.toLowerCase().replace(/\s+/g, "_")}`
						);

						linkTagLink = `' . Uri::root() . 'media/com_sppagebuilder/assets/custom-fonts/${sppb_fontFamily}/stylesheet.css`;
					}

					if (!fontLink) {
						const fontLink = sppb_doc.createElement("link");
						fontLink.id = linkTagIdPrefix + sppb_fontFamily.toLowerCase().replace(/\s+/g, "_");
						fontLink.href = linkTagLink;
						fontLink.rel  = "stylesheet";
						fontLink.type = "text/css";
						sppb_head.appendChild(fontLink);
					}
				} #>';

		return $css;
	}

	/**
	 * Set css property with unit
	 *
	 * @param	string 		$data
	 * @param	string 		$property
	 * @param	string 		$key
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	private function setCssPropertyWithUnit(string $data, string $property, string $key): string
	{
		$css = [];

		$regex = '/\d+(px|em|rem|%)$/';
		$content = $data . '.' . $key . '[window.builderDefaultDevice].value';
		$condition = preg_match($regex, $this->inlineBlock($content)) ? false : true;

		$css[] = '<# if ( _.isObject(' . $data . '.' . $key . ') && ' . $data . '.' . $key . '[window.builderDefaultDevice].value && ' . $data . '.' . $key . '[window.builderDefaultDevice].unit) { #>';
		$css[] = $property . ': ' . $this->inlineBlock($content) . $this->inlineBlockWithCondition($condition, $data . '.' . $key . '[window.builderDefaultDevice].unit') . ';';
		$css[] = '<# } #>';

		return implode("\n", $css);
	}

	/**
	 * Manage typography
	 *
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * @param 	array 	$fallbacks
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function typography(string $selector, string $data,  array $fallbacks = []): string
	{
		$selector = $this->generateSelector($selector);

		$css = [];

		$font           = (!empty($fallbacks) && array_key_exists('font', $fallbacks)) ? $fallbacks['font'] : "undefined";
		$size           = (!empty($fallbacks) && array_key_exists('size', $fallbacks)) ? $fallbacks['size'] : "undefined";
		$line_height    = (!empty($fallbacks) && array_key_exists('line_height', $fallbacks)) ? $fallbacks['line_height'] : "undefined";
		$letter_spacing = (!empty($fallbacks) && array_key_exists('letter_spacing', $fallbacks)) ? $fallbacks['letter_spacing'] : "undefined";
		$custom_letter_spacing = (!empty($fallbacks) && array_key_exists('custom_letter_spacing', $fallbacks)) ? $fallbacks['custom_letter_spacing'] : "undefined";
		$uppercase      = (!empty($fallbacks) && array_key_exists('uppercase', $fallbacks)) ? $fallbacks['uppercase'] : "undefined";
		$italic         = (!empty($fallbacks) && array_key_exists('italic', $fallbacks)) ? $fallbacks['italic'] : "undefined";
		$underline      = (!empty($fallbacks) && array_key_exists('underline', $fallbacks)) ? $fallbacks['underline'] : "undefined";
		$weight         = (!empty($fallbacks) && array_key_exists('weight', $fallbacks)) ? $fallbacks['weight'] : "undefined";

		$css[] = '
		<# 
		var typography = ' . $data . ';
		var typographyPreset = typography?.preset;
		if (typographyPreset && globalTypographies && typeof typographyPreset === "string" && typographyPreset.includes(".")) {
			var typographyPresetGroup = typographyPreset.split(".")[0];
			var typographyPresetIndex = typographyPreset.split(".")[1];

			var typographyPresetValues = globalTypographies[typographyPresetGroup]?.typography?.[typographyPresetIndex];
			' . $data  . '= typographyPresetValues ?? ' . $data . ';
		} #>';

		$css[] = '<# if (!_.isEmpty(' . $data . ') && _.isObject(' . $data . ') ) { #>';

		$css[] = $this->loadFonts($data);

		$css[] = $selector . ' {';

		$css[] = '<# if ( typeof ' . $data . '.font !== "undefined" &&  ' . $data . '.font ) { #>';
		$css[] = 'font-family: "' . $this->inlineBlock($data . '.font') . '";';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $data . '.underline !== "undefined" &&  ' . $data . '.underline ) { #>';
		$css[] = 'text-decoration: underline;';
		$css[] = '<# } else {#>';
		$css[] = 'text-decoration: none;';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $data . '.italic !== "undefined" &&  ' . $data . '.italic ) { #>';
		$css[] = 'font-style: italic;';
		$css[] = '<# } else {#>';
		$css[] = 'font-style: normal;';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $data . '.uppercase !== "undefined" && ' . $data . '.uppercase) { #>';
		$css[] = 'text-transform: uppercase;';
		$css[] = '<# } #>';

		$css[] = '<# if ( ' . $data . '.weight) { #>';
		$css[] = 'font-weight: ' . $this->inlineBlock($data . '.weight') . ';';
		$css[] = '<# } #>';

		$css[] = $this->setCssPropertyWithUnit($data, 'font-size', 'size');
		$css[] = $this->setCssPropertyWithUnit($data, 'letter-spacing', 'letter_spacing');

		$css[] = '<# if ( _.isObject(' . $data . '.line_height) && ' . $data . '.line_height[window.builderDefaultDevice] && ' . $data . '.line_height[window.builderDefaultDevice].value) { #>';
		$css[] = 'line-height: ' . $this->inlineBlock($data . '.line_height[window.builderDefaultDevice].value') . $this->inlineBlock($data . '.line_height[window.builderDefaultDevice].unit ?? ""') . ';';
		$css[] = '<# } #>';

		$css[] = '}';


		$css[] = $this->mediaQuery('font-size', $selector, $data . '.size', '', true);
		$css[] = $this->mediaQuery('letter-spacing', $selector, $data . '.letter_spacing', '', true);
		$css[] = $this->mediaQuery('line-height', $selector, $data . '.line_height', '', true);


		$css[] = '<# } else { #>';

		$css[] = $this->loadFonts($font, true);

		// Fallback CSS
		$css[] = $selector . ' {';

		$css[] = '<# if ( typeof ' . $font . ' !== "undefined" &&  ' . $font . ' ) { #>';
		$css[] = 'font-family:"' . $this->inlineBlock($font) . '";';
		$css[] = '<# } #>';

		$css[] = '<# if(typeof ' . $size . ' !== "undefined" && _.isObject(' . $size . ') && !_.isString(' . $size . ') && !_.isEmpty(' . $size . ')) { #>';
		$css[] = 'font-size: ' . $this->inlineBlock($size . '[window.builderDefaultDevice]') . 'px;';
		$css[] = '<# } else if(typeof ' . $size . ' !== "undefined" && !_.isEmpty(' . $size . ')){ #>';
		$css[] = 'font-size: ' . $this->inlineBlock($size) . 'px;';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $line_height . ' !== "undefined" &&  ' . $line_height . ' ) { #>';
		$css[] = 'line-height: ' . $this->inlineBlock($line_height . '[window.builderDefaultDevice]') . 'px;';
		$css[] = '<# } else if(typeof ' . $size . ' !== "undefined" && !_.isEmpty(' . $size . ')){ #>';
		$css[] = 'line-height: ' . $this->inlineBlock($line_height) . 'px;';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $letter_spacing . ' !== "undefined" &&  ' . $letter_spacing . ' ) { #>';
		$css[] = '<# if (' . $letter_spacing . ' == "custom") {#>';
		$css[] = 'letter-spacing: ' . $this->inlineBlock($custom_letter_spacing) . ';';
		$css[] = '<# } else {#>';
		$css[] = 'letter-spacing: ' . $this->inlineBlock($letter_spacing) . ';';
		$css[] = '<# } #>';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $uppercase  . ' !== "undefined" &&  ' . $uppercase . ' ) { #>';
		$css[] = 'text-transform: uppercase;';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $italic  . ' !== "undefined" &&  ' . $italic  . ' ) { #>';
		$css[] = 'font-style: italic;';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $underline   . ' !== "undefined" &&  ' . $underline  . ' ) { #>';
		$css[] = 'text-decoration: "' . $this->inlineBlock($underline) . '";';
		$css[] = '<# } #>';

		$css[] = '<# if ( typeof ' . $weight . ' !== "undefined" &&  ' . $weight . ' ) { #>';
		$css[] = 'font-weight: ' . $this->inlineBlock($weight) . ';';
		$css[] = '<# } #>';

		$css[] = '}';

		$css[] = '<# } #>';


		return implode("\n", $css);
	}

	/**
	 * transform
	 *
	 * @param 	string 	$property
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function transform(string $property, string $selector, string $data, string $unit = ''): string
	{
		$selector = $this->generateSelector($selector);

		$css = [];
		$css[]    = '<# if(!_.isEmpty(' . $data . ')) { #>';
		$css[]    = $selector . ' {';
		$css[]    = '-webkit-transform: ' . $property . '(' . $this->inlineBlock($data) . $unit . ');';
		$css[]    = 'transform: ' . $property . '(' . $this->inlineBlock($data) . $unit . ');';
		$css[]    = '}';
		$css[]    = '<# } #>';

		return implode("\n", $css);
	}

	/**
	 * adjustment function
	 *
	 * @param 	string 	$property
	 * @param 	string 	$selector
	 * @param 	string 	$prefix
	 * @param 	string 	$data
	 * @param 	string 	$unit
	 * 
	 * @return 	void
	 * @since 	4.0.0
	 */
	public function adjustment(string $property, string $selector, string $prefix, string $data, string $unit)
	{
		$selector = $this->generateSelector($selector);

		$css = [];
		$css[]    = '<# if(!_.isEmpty(' . $data . ')) { #>';
		$css[]    = $selector . ' {';
		$css[]    = '<# if(_.isObject(data.' . $data . ') ) { #>';
		$css[]    = $property . ':' . $prefix . '(' . $this->inlineBlock($data) . $unit . ');';
		$css[]    = '}';
		$css[]    = '<# } #>';

		return implode("\n", $css);
	}


	/**
	 * box shadow
	 *
	 * @param 	string 	$selector
	 * @param 	string 	$data
	 * 
	 * @return 	string
	 * @since 	4.0.0
	 */
	public function boxShadow(string $selector, string $data): string
	{
		$selector = $this->generateSelector($selector);

		$css = [];
		$css[]    = '<# if(!_.isEmpty(' . $data . ')) { #>';
		$css[]    = $selector . ' {';
		$css[]    = '<# if(_.isObject(' . $data . ')) { #>';
		$css[] 	  = 'box-shadow: ' . $this->inlineBlock($data . '.ho || 0') . 'px ' .  $this->inlineBlock($data . '.vo || 0') . 'px ' . $this->inlineBlock($data . '.blur || 0') . 'px ' . $this->inlineBlock($data . '.spread || 0') . 'px ' . $this->inlineBlock($data . '.color || "transparent"') . ';';
		$css[]    = '<# } else { #>';
		$css[]    = 'box-shadow: ' . $this->inlineBlock($data) . ';';
		$css[]    = '<# } #>';
		$css[]    = '}';
		$css[]    = '<# } #>';

		return implode("\n", $css);
	}

	/**
	 * text shadow
	 *
	 * @param	string 	$selector
	 * @param	string 	$data
	 * 
	 * @return	string
	 * @since 	4.0.0
	 */
	public function textShadow(string $selector, string $data): string
	{
		$selector = $this->generateSelector($selector);

		$css = [];
		$css[]    = '<# if(!_.isEmpty(' . $data . ')) { #>';
		$css[]    = $selector . ' {';
		$css[]    = '<# if(_.isObject(' . $data . ') && ' . $data . '.enabled) { #>';
		$css[] 	  = 'text-shadow: ' . $this->inlineBlock($data . '.ho || 0') . 'px ' .  $this->inlineBlock($data . '.vo || 0') . 'px ' . $this->inlineBlock($data . '.blur || 0') . 'px ' . $this->inlineBlock($data . '.color || "transparent"') . ';';
		$css[]    = '<# } else { #>';
		$css[]    = 'text-shadow: ' . $this->inlineBlock($data) . ';';
		$css[]    = '<# } #>';
		$css[]    = '}';
		$css[]    = '<# } #>';

		return implode("\n", $css);
	}


	/**
	 * Return all css with media query for given size.
	 *
	 * @param	string 		$property
	 * @param	string 		$selector
	 * @param	string 		$data
	 * @param	string 		$unit
	 * @param	boolean 	$isTypography
	 * @param	boolean 	$prefix 
	 * 
	 * @return	string
	 * @since	4.0.0
	 */
	private function mediaQuery(string $property, string $selector, string $data,  string $unit = '', bool $isTypography = false, string $prefix  = ''): string
	{
		$css = [];

		foreach ($this->sizes as $size)
		{
			$css[] = $this->mediaQueryDevice($size);
			$css[] = $selector . ' {';
			$css[] = '<# if(_.isObject(' . $data . ') && !_.isEmpty(' . $data . ')) { #>';

			if ($isTypography)
			{
				$css[] = $property . ': ' . $prefix . $this->inlineBlock($data . '.' . $size . '.value') . $this->inlineBlock($data . '.' . $size . '.unit') . ';';
			}
			else
			{
				$css[] = $property . ': ' . $prefix . $this->inlineBlock($data . '.' . $size) . (!empty($unit) ? $unit : "") . ';';
			}

			$css[] = '<# } #>';
			$css[] = '}'; // closing bracket for selector.
			$css[] = '}'; // closing bracket for media query.
		}

		return implode("\n", $css);
	}

	/**
	 * Return media query 
	 *
	 * @param	string 	$device device size name
	 * 
	 * @return	string
	 * @since 	4.0.0
	 */
	private function mediaQueryDevice(string $device)
	{
		switch ($device)
		{
			case 'lg':
				return "@media ( max-width: 1199.98px ) { ";
			case 'md':
				return "@media ( max-width: 991.98px ) { ";
			case 'sm':
				return "@media ( max-width: 767.98px ) { ";
			case 'xs':
				return "@media ( max-width: 575.98px ) { ";
			default:
				return "";
		}
	}

	/**
	 * Generate the block of codes.
	 *
	 * @return	string
	 * @since	4.0.0
	 */
	private function block(string $content): string
	{
		return '<# ' . $content . ' #>';
	}


	/**
	 * Generate the inline block of codes.
	 *
	 * @return	string
	 * @since	4.0.0
	 */
	private function inlineBlock(string $content): string
	{
		return '{{' . $content . '}}';
	}

	/**
	 * Generate the inline block of codes if condition is true.
	 *
	 * @return	string
	 * @since	4.0.0
	 */
	private function inlineBlockWithCondition(bool $condition, string $content): string
	{
		return $condition ? '{{' .  $content  . '}}' : '';
	}

	/**
	 * Backdrop Filter
	 *
	 * @param string $property   CSS Property Name
	 * @param string $selector	 CSS Selector
	 * @param string $data		 CSS Value
	 * @param string $unit		 Unit of CSS Value
	 * @return string
	 * since 4.0.8
	 */
	public function backdrop_filter(string $property, string $selector, string $data, string $unit)
	{
		$selector = $this->generateSelector($selector);

		$css 	  = [];
		$css[]    = '<# if(!_.isEmpty(' . $data . ')) { #>';
		$css[]    = $selector . ' {';
		$css[]    = '-webkit-backdrop-filter: ' . $property . '(' . $this->inlineBlock($data) . $unit . ');';
		$css[]    = 'backdrop-filter: ' . $property . '(' . $this->inlineBlock($data) . $unit . ');';
		$css[]    = '}';
		$css[]    = '<# } #>';

		return implode("\n", $css);
	}

	/**
	 * Generate missing break points of field width for old layouts. 
	 *
	 * @param string $fieldWidth  given breakpoints of field width
	 * @return string
	 * @since 4.0.9
	 */
	public function generateMissingBreakpoints(string $fieldWidth)
	{
		$generateBreakPoints   = [];
		$generateBreakPoints[] = "const expectedBreakpoints = ['xl', 'lg'];";
		$generateBreakPoints[] = "_.forEach(expectedBreakpoints, (key) => {";
		$generateBreakPoints[] = "if(!_.has($fieldWidth, key)) {";
		$generateBreakPoints[] = "_.set($fieldWidth, key, _.get($fieldWidth, 'md', ''));";
		$generateBreakPoints[] = "}";
		$generateBreakPoints[] = "})";

		return implode("\n", $generateBreakPoints);
	}
}
