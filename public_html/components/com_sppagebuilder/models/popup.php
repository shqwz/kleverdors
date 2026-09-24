<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

 use Joomla\CMS\Factory;
 use Joomla\CMS\Table\Table;
 use Joomla\CMS\Language\Text;
 use Joomla\CMS\HTML\HTMLHelper;
 use Joomla\CMS\MVC\Model\ItemModel;
 use Joomla\CMS\Plugin\PluginHelper;
 use Joomla\CMS\Uri\Uri;
 
 //no direct access
 defined('_JEXEC') or die('Restricted access');

 class SppagebuilderModelPopup {
	private function adjustedMargin($originalData) {
		$marginElems = explode(' ', $originalData);
		$topBottomMargin = "calc({$marginElems[0]} - {$marginElems[2]})";
		$leftRightMargin = "calc({$marginElems[3]} - {$marginElems[1]})";
	
		return "{$topBottomMargin} {$leftRightMargin}";
	}

	public function getCssOutput($popupAttribs, $popupId)
	{
		/** @var CMSApplication */
		$cssOutput = '';

		if (!empty($popupAttribs['custom_css'])) {
			$cssOutput .= $popupAttribs['custom_css'];
		}

		$cssOutput .= ' ';

		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$width_xl = !empty($popupAttribs['width']['xl']) ? $popupAttribs['width']['xl'] . $popupAttribs['width']['unit'] : '';
		$width_lg = !empty($popupAttribs['width']['lg']) ? $popupAttribs['width']['lg'] . $popupAttribs['width']['unit'] : $width_xl;
		$width_md = !empty($popupAttribs['width']['md']) ? $popupAttribs['width']['md'] . $popupAttribs['width']['unit'] : $width_lg;
		$width_sm = !empty($popupAttribs['width']['sm']) ? $popupAttribs['width']['sm'] . $popupAttribs['width']['unit'] : $width_md;
		$width_xs = !empty($popupAttribs['width']['xs']) ? $popupAttribs['width']['xs'] . $popupAttribs['width']['unit'] : $width_sm;

		$max_width_xl = !empty($popupAttribs['max_width']['xl']) ? $popupAttribs['max_width']['xl'] . $popupAttribs['max_width']['unit'] : '';
		$max_width_lg = !empty($popupAttribs['max_width']['lg']) ? $popupAttribs['max_width']['lg'] . $popupAttribs['max_width']['unit'] : $max_width_xl;
		$max_width_md = !empty($popupAttribs['max_width']['md']) ? $popupAttribs['max_width']['md'] . $popupAttribs['max_width']['unit'] : $max_width_lg;
		$max_width_sm = !empty($popupAttribs['max_width']['sm']) ? $popupAttribs['max_width']['sm'] . $popupAttribs['max_width']['unit'] : $max_width_md;
		$max_width_xs = !empty($popupAttribs['max_width']['xs']) ? $popupAttribs['max_width']['xs'] . $popupAttribs['max_width']['unit'] : $max_width_sm;

		$height_xl = !empty($popupAttribs['height']['xl']) ? $popupAttribs['height']['xl'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : '';
		$height_lg = !empty($popupAttribs['height']['lg']) ? $popupAttribs['height']['lg'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : $height_xl;
		$height_md = !empty($popupAttribs['height']['md']) ? $popupAttribs['height']['md'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : $height_lg;
		$height_sm = !empty($popupAttribs['height']['sm']) ? $popupAttribs['height']['sm'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] :'vh') : $height_md;
		$height_xs = !empty($popupAttribs['height']['xs']) ? $popupAttribs['height']['xs'] . ($popupAttribs['height']['unit'] !== '%' ? $popupAttribs['height']['unit'] : 'vh') : $height_sm;

		$max_height_xl = !empty($popupAttribs['max_height']['xl']) ? $popupAttribs['max_height']['xl'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : '';
		$max_height_lg = !empty($popupAttribs['max_height']['lg']) ? $popupAttribs['max_height']['lg'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : $max_height_xl;
		$max_height_md = !empty($popupAttribs['max_height']['md']) ? $popupAttribs['max_height']['md'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : $max_height_lg;
		$max_height_sm = !empty($popupAttribs['max_height']['sm']) ? $popupAttribs['max_height']['sm'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max-height']['unit'] :'vh') : $max_height_md;
		$max_height_xs = !empty($popupAttribs['max_height']['xs']) ? $popupAttribs['max_height']['xs'] . ($popupAttribs['max_height']['unit'] !== '%' ? $popupAttribs['max_height']['unit'] : 'vh') : $max_height_sm;

		$border_radius_xl = !empty($popupAttribs['border_radius']['xl']) ? $popupAttribs['border_radius']['xl'] . $popupAttribs['border_radius']['unit'] : '';
		$border_radius_lg = !empty($popupAttribs['border_radius']['lg']) ? $popupAttribs['border_radius']['lg'] . $popupAttribs['border_radius']['unit'] : $border_radius_xl;
		$border_radius_md = !empty($popupAttribs['border_radius']['md']) ? $popupAttribs['border_radius']['md'] . $popupAttribs['border_radius']['unit'] : $border_radius_lg;
		$border_radius_sm = !empty($popupAttribs['border_radius']['sm']) ? $popupAttribs['border_radius']['sm'] . $popupAttribs['border_radius']['unit'] : $border_radius_md;
		$border_radius_xs = !empty($popupAttribs['border_radius']['xs']) ? $popupAttribs['border_radius']['xs'] . $popupAttribs['border_radius']['unit'] : $border_radius_sm;

	
		$responsiveStr = ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
		' . (!empty($width_xl) ? ('width: ' . $width_xl . ';') : '') . '
		' . (!empty($max_width_xl) ? ('max-width: ' . $max_width_xl . ';') : '') . '
		' . (!empty($height_xl) ? ('height: ' . $height_xl . ';') : '') . '
		' . (!empty($max_height_xl) ? ('max-height: ' . $max_height_xl . ';') : '') . '
		' . (!empty($border_radius_xl) ? ('border-radius: ' . $border_radius_xl . ';') : '') . '
		}
		@media (max-width: 1200px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_lg) ? ('width: ' . $width_lg . ';') : '') . '
				' . (!empty($max_width_lg) ? ('max-width: ' . $max_width_lg . ';') : '') . '
				' . (!empty($height_lg) ? ('height: ' . $height_lg . ';') : '') . '
				' . (!empty($max_height_lg) ? ('max-height: ' . $max_height_lg . ';') : '') . '
				' . (!empty($border_radius_lg) ? ('border-radius: ' . $border_radius_lg . ';') : '') . '
			}
		}
		@media (max-width: 992px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_md) ? ('width: ' . $width_md) . ';' : '') . '
				' . (!empty($max_width_md) ? ('max-width: ' . $max_width_md . ';') : '') . '
				' . (!empty($height_md) ? ('height: ' . $height_md . ';') : '') . '
				' . (!empty($max_height_md) ? ('max-height: ' . $max_height_md . ';') : '') . '
				' . (!empty($border_radius_md) ? ('border-radius: ' . $border_radius_md . ';') : '') . '
			}
		}
		@media (max-width: 768px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_sm) ? ('width: ' . $width_sm . ';') : '') . '
				' . (!empty($max_width_sm) ? ('max-width: ' . $max_width_sm . ';') : '') . '
				' . (!empty($height_sm) ? ('height: ' . $height_sm . ';') : '') . '
				' . (!empty($max_height_sm) ? ('max-height: ' . $max_height_sm . ';') : '') . '
				' . (!empty($border_radius_sm) ? ('border-radius: ' . $border_radius_sm . ';') : '') . '
			}
		}
		@media (max-width: 575px) {
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				' . (!empty($width_xs) ? ('width: ' . $width_xs . ';') : '') . '
				' . (!empty($max_width_xs) ? ('max-width: ' . $max_width_xs . ';') : '') . '
				' . (!empty($height_xs) ? ('height: ' . $height_xs . ';') : '') . '
				' . (!empty($max_height_xs) ? ('max-height: ' . $max_height_xs . ';') : '') . '
				' . (!empty($border_radius_xs) ? ('border-radius: ' . $border_radius_xs . ';') : '') . '
			}
		} ';

		$cssOutput .= $responsiveStr;

		$cssOutput .= '
			.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				position: absolute;
				animation-duration: ' . (isset($popupAttribs['enter_animation_duration']) ? (($popupAttribs['enter_animation_duration'] / 1000) . 's;') : '2s;') . '
			}
		';

		$cssOutput .= ' 
		.page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
			padding: ' . (!empty($popupAttribs['padding']) ? $popupAttribs['padding'] : 'initial') . ';
			margin: ' . (!empty($popupAttribs['margin']) ? $this->adjustedMargin($popupAttribs['margin']) : 'initial') . ';
	
			border-width: ' . (!empty($popupAttribs['border']['border_width']) ? $popupAttribs['border']['border_width'] : 'initial') . ';
			border-style: ' . (!empty($popupAttribs['border']['border_style']) ? $popupAttribs['border']['border_style'] : 'initial') . ';
			border-color: ' . (!empty($popupAttribs['border']['border_color']) ? $popupAttribs['border']['border_color'] : 'initial') . ';
		} 
		';

		$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup {
			display: none;
		}';

		$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
			display: none;
		}';

		if (!empty($popupAttribs['boxshadow']) && $popupAttribs['boxshadow']['enabled'] === true)
		{
			$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				box-shadow: ' . ((bool)($popupAttribs['boxshadow']['ho']) ? $popupAttribs['boxshadow']['ho'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['vo']) ? $popupAttribs['boxshadow']['vo'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['blur']) ? $popupAttribs['boxshadow']['blur'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['spread']) ? $popupAttribs['boxshadow']['spread'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['color']) ? $popupAttribs['boxshadow']['color'] : 'initial') . ';
			} ';
		}
			$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				background-color: ' . (!empty($popupAttribs['bg_color']) ? $popupAttribs['bg_color'] : 'white') . ';
			} ';

		if (!empty($popupAttribs['background_type']) && !empty($popupAttribs['bg_media']) && $popupAttribs['background_type'] === 'image')
		{
			$bgImageSrc = $popupAttribs['bg_media']['src'];
			if (!preg_match('#^(https?://|//)#', $bgImageSrc) && substr($bgImageSrc, 0, 1) !== '/') {
				$bgImageSrc = Uri::root(true) . '/' . ltrim($bgImageSrc, '/');
			}
			$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
				background-image: url("' . $bgImageSrc . '");
				background-repeat: ' . (!empty($popupAttribs['bg_media_repeat']) ? $popupAttribs['bg_media_repeat'] : 'no-repeat') . ';
				background-attachment: ' . (!empty($popupAttribs['bg_media_attachment']) ? $popupAttribs['bg_media_attachment'] : 'initial') . ';
				background-position: ' . (!empty($popupAttribs['bg_media_position']) ? $popupAttribs['bg_media_position'] : 'initial') . ';
				background-size: ' . (!empty($popupAttribs['bg_media_size']) ? $popupAttribs['bg_media_size'] : 'cover') . ';' . 
				(!empty($popupAttribs['bg_media_overlay']) && $popupAttribs['bg_media_overlay'] === 1 ? 'background-blend-mode: ' . $popupAttribs['bg_media_overlay_blend_mode'] : 'normal') . ';
			} ';
		}
		else if (!empty($popupAttribs['background_type']) && $popupAttribs['background_type'] === 'gradient')
		{
			$deg = !empty($popupAttribs['bg_gradient']['deg']) ? $popupAttribs['bg_gradient']['deg'] : 45;
			$radialPos = !empty($popupAttribs['bg_gradient']['radialPos']) ? $popupAttribs['bg_gradient']['radialPos'] : 'center center';
			$color = !empty($popupAttribs['bg_gradient']['color']) ? $popupAttribs['bg_gradient']['color'] : '#00C6FB';
			$color2 = !empty($popupAttribs['bg_gradient']['color2']) ? $popupAttribs['bg_gradient']['color2'] : '#005BEA';
			$pos = !empty($popupAttribs['bg_gradient']['pos']) ? $popupAttribs['bg_gradient']['pos'] : 0;
			$pos2 = !empty($popupAttribs['bg_gradient']['pos2']) ? $popupAttribs['bg_gradient']['pos2'] : 100;
			$type = !empty($popupAttribs['bg_gradient']['type']) ? $popupAttribs['bg_gradient']['type'] : 'linear';

			if (!(bool)$deg) {
				$deg = 45;
			}
			if (!(bool)$radialPos) {
				$radialPos = 'center center';
			}
			if (!(bool)$color) {
				$color = '#00C6FB';
			}
			if (!(bool)$color2) {
				$color2 = '#005BEA';
			}
			if (!(bool)$pos) {
				$pos = 0;
			}
			if (!(bool)$pos2) {
				$pos2 = 100;
			}
			if (!(bool)$type) {
				$type = 'linear';
			}
			
			if ($type === 'linear')
			{
				$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
					background-color: unset;
					background-image: linear-gradient(' . $deg . 'deg, ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
				}';
			}
			else if ($type === 'radial')
			{
				$cssOutput .= ' .page-' . $popupId . '.sp-pagebuilder-popup .builder-container {
					background-color: unset;
					background-image: radial-gradient(' . $radialPos . ', ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
				}';
			}
		}

		if (!isset($popupAttribs['overlay']) || ($popupAttribs['overlay'] === 1))
		{
			$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
					background-color: ' . (!empty($popupAttribs['overlay_bg_color']) && (bool)$popupAttribs['overlay_bg_color'] ? $popupAttribs['overlay_bg_color'] : 'rgba(0, 0, 0, 0.7)') . ';
				} ';

			if (!empty($popupAttribs['overlay']) && !empty($popupAttribs['overlay_bg_media']) && !empty($popupAttribs['overlay_background_type']) && $popupAttribs['overlay_background_type'] === 'image')
			{
				$overlayBgImageSrc = $popupAttribs['overlay_bg_media']['src'];
				if (!preg_match('#^(https?://|//)#', $overlayBgImageSrc) && substr($overlayBgImageSrc, 0, 1) !== '/') {
					$overlayBgImageSrc = Uri::root(true) . '/' . ltrim($overlayBgImageSrc, '/');
				}
				$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
					background-image: url("' . $overlayBgImageSrc . '");
					background-repeat: ' . (!empty($popupAttribs['overlay_bg_media_repeat']) ? $popupAttribs['overlay_bg_media_repeat'] : 'no-repeat') . ';
					background-attachment: ' . (!empty($popupAttribs['overlay_bg_media_attachment']) ? $popupAttribs['overlay_bg_media_attachment'] : 'initial') . ';
					background-position: ' . (!empty($popupAttribs['overlay_bg_media_position']) ? $popupAttribs['overlay_bg_media_position'] : 'initial') . ';
					background-size: ' . (!empty($popupAttribs['overlay_bg_media_size']) ? $popupAttribs['overlay_bg_media_size'] : 'cover') . ';' . 
					(!empty($popupAttribs['overlay_bg_media_overlay']) && $popupAttribs['overlay_bg_media_overlay'] === 1 ? 'background-blend-mode: ' . $popupAttribs['overlay_bg_media_overlay_blend_mode'] : 'normal') . ';
				} ';
			}
			else if (!empty($popupAttribs['overlay']) && !empty($popupAttribs['overlay_background_type']) && $popupAttribs['overlay_background_type'] === 'gradient')
			{
				$deg = !empty($popupAttribs['overlay_bg_gradient']['deg']) ? $popupAttribs['overlay_bg_gradient']['deg'] : 45;
				$radialPos = !empty($popupAttribs['overlay_bg_gradient']['radialPos']) ? $popupAttribs['overlay_bg_gradient']['radialPos'] : 'center center';
				$color = !empty($popupAttribs['overlay_bg_gradient']['color']) ? $popupAttribs['overlay_bg_gradient']['color'] : '#00C6FB';
				$color2 = !empty($popupAttribs['overlay_bg_gradient']['color2']) ? $popupAttribs['overlay_bg_gradient']['color2'] : '#005BEA';
				$pos = !empty($popupAttribs['overlay_bg_gradient']['pos']) ? $popupAttribs['overlay_bg_gradient']['pos'] : 0;
				$pos2 = !empty($popupAttribs['overlay_bg_gradient']['pos2']) ? $popupAttribs['overlay_bg_gradient']['pos2'] : 100;
				$type = !empty($popupAttribs['overlay_bg_gradient']['type']) ? $popupAttribs['overlay_bg_gradient']['type'] : 'linear';

				if (!(bool)$deg) {
					$deg = 45;
				}
				if (!(bool)$radialPos) {
					$radialPos = 'center center';
				}
				if (!(bool)$color) {
					$color = '#00C6FB';
				}
				if (!(bool)$color2) {
					$color2 = '#005BEA';
				}
				if (!(bool)$pos) {
					$pos = 0;
				}
				if (!(bool)$pos2) {
					$pos2 = 100;
				}
				if (!(bool)$type) {
					$type = 'linear';
				}
				
				if ($type === 'linear')
				{
					$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
						background-color: unset;
						background-image: linear-gradient(' . $deg . 'deg, ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
					}';
				}
				else if ($type === 'radial')
				{
					$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
						background-color: unset;
						background-image: radial-gradient(' . $radialPos . ', ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
					}';
				}
			}
		}
		else if (isset($popupAttribs['overlay']) && $popupAttribs['overlay'] === 0)
		{
			$cssOutput .= ' #sp-pagebuilder-overlay-' . $popupId . ' {
				display: none;
			} ';
		}

		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			display: flex;
			justify-content: center;
			align-items: center;
		} ';

		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			color: ' . (!empty($popupAttribs['close_btn_color']) ? $popupAttribs['close_btn_color'] : 'initial') . ';
		} ';

		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ':hover {
			color: ' . (!empty($popupAttribs['close_btn_color_hover']) ? $popupAttribs['close_btn_color_hover'] : 'initial') . ' !important;
			background-color: ' . (!empty($popupAttribs['close_btn_bg_color_hover']) ? $popupAttribs['close_btn_bg_color_hover'] : 'initial') . ' !important;
		} ';

		$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			color: ' . (!empty($popupAttribs['close_btn_color']) ? $popupAttribs['close_btn_color'] : 'initial') . ';

			border-width: ' . (!empty($popupAttribs['close_btn_border']['border_width']) ? $popupAttribs['close_btn_border']['border_width'] : 'initial') . ';
			border-style: ' . (!empty($popupAttribs['close_btn_border']['border_style']) ? $popupAttribs['close_btn_border']['border_style'] : 'initial') . ';
			border-color: ' . (!empty($popupAttribs['close_btn_border']['border_color']) ? $popupAttribs['close_btn_border']['border_color'] : 'initial') . ';

			border-radius: ' . (!empty($popupAttribs['close_btn_border_radius']) ? ($popupAttribs['close_btn_border_radius'] . 'px') : '0px') . ';
		}
		#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			background-color: ' . (!empty($popupAttribs['close_btn_bg_color']) ? $popupAttribs['close_btn_bg_color'] : 'initial') . ';
		}
		#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
			padding: ' . (!empty($popupAttribs['close_btn_padding']) ? $popupAttribs['close_btn_padding'] : 'initial') . ';
		} ';

		if (empty($popupAttribs['close_btn_position']) || $popupAttribs['close_btn_position'] === 'inside' || $popupAttribs['close_btn_position'] === 0 || $popupAttribs['close_btn_position'] === '' || empty($popupAttribs['close_btn_position'])) 
		{
			$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId .' {
				transform: scale(1.2);
				right: 25px;
				top: 20px;
			}';
		}
		else if ($popupAttribs['close_btn_position'] === 'outside' || $popupAttribs['close_btn_position'] === 1)
		{
			$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId .' {
				transform: scale(1.2);
				right: 5px;
				top: -30px;
			}';
		}
		else if ($popupAttribs['close_btn_position'] === 'outside' || $popupAttribs['close_btn_position'] === 'custom')
		{
			$btn_position_x_xl = !empty($popupAttribs['close_btn_position_x']['xl']) ? $popupAttribs['close_btn_position_x']['xl'] . $popupAttribs['close_btn_position_x']['unit'] : '25px';
			$btn_position_x_lg = !empty($popupAttribs['close_btn_position_x']['lg']) ? $popupAttribs['close_btn_position_x']['lg'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['lg']) && $popupAttribs['close_btn_position_x']['lg'] == '0' ? '0' : $btn_position_x_xl);
			$btn_position_x_md = !empty($popupAttribs['close_btn_position_x']['md']) ? $popupAttribs['close_btn_position_x']['md'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['md']) && $popupAttribs['close_btn_position_x']['md'] == '0' ? '0' : $btn_position_x_lg);
			$btn_position_x_sm = !empty($popupAttribs['close_btn_position_x']['sm']) ? $popupAttribs['close_btn_position_x']['sm'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['sm']) && $popupAttribs['close_btn_position_x']['sm'] == '0' ? '0' : $btn_position_x_md);
			$btn_position_x_xs = !empty($popupAttribs['close_btn_position_x']['xs']) ? $popupAttribs['close_btn_position_x']['xs'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['xs']) && $popupAttribs['close_btn_position_x']['xs'] == '0' ? '0' : $btn_position_x_sm);
	
			$btn_position_y_xl = !empty($popupAttribs['close_btn_position_y']['xl']) ? $popupAttribs['close_btn_position_y']['xl'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : '20px';
			$btn_position_y_lg = !empty($popupAttribs['close_btn_position_y']['lg']) ? $popupAttribs['close_btn_position_y']['lg'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (isset($popupAttribs['close_btn_position_y']['lg']) && $popupAttribs['close_btn_position_y']['lg'] == '0' ? '0' : $btn_position_y_xl);
			$btn_position_y_md = !empty($popupAttribs['close_btn_position_y']['md']) ? $popupAttribs['close_btn_position_y']['md'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : ((isset($popupAttribs['close_btn_position_y']['md']) && $popupAttribs['close_btn_position_y']['md'] == '0' ? '0' : $btn_position_y_lg));
			$btn_position_y_sm = !empty($popupAttribs['close_btn_position_y']['sm']) ? $popupAttribs['close_btn_position_y']['sm'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (((isset($popupAttribs['close_btn_position_y']['sm']) && $popupAttribs['close_btn_position_y']['sm'] == '0' ? '0' : $btn_position_y_md)));
			$btn_position_y_xs = !empty($popupAttribs['close_btn_position_y']['xs']) ? $popupAttribs['close_btn_position_y']['xs'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (((isset($popupAttribs['close_btn_position_y']['xs']) && $popupAttribs['close_btn_position_y']['xs'] == '0' ? '0' : $btn_position_y_sm)));
	
			$cssOutput .= ' #sp-pagebuilder-popup-close-btn-' . $popupId . ' {
				transform: scale(1.2);
	
				right: ' . $btn_position_x_xl . '; 
				top: ' . $btn_position_y_xl . ';
			}
			@media (max-width: 1200px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_lg . '; 
					top: ' . $btn_position_y_lg . ';
				}
			}
			@media (max-width: 992px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_md . '; 
					top: ' . $btn_position_y_md . ';
				}
			}
			@media (max-width: 768px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_sm . '; 
					top: ' . $btn_position_y_sm . ';
				}
			}
			@media (max-width: 575px) {
				#sp-pagebuilder-popup-close-btn-' . $popupId . ' {
					right: ' . $btn_position_x_xs . '; 
					top: ' . $btn_position_y_xs . ';
				}
			} ';	
		}

		return $cssOutput;
	}

	private function getPositionScriptContent($popupId, $formattedPopupAttribs)
	{
		$scriptContent = '

			const data = ' . $formattedPopupAttribs . ';
			function onElementLoaded(element) {
					const container = element;

					const mediaQueryMap = {
						"default": "xl",
						"(max-width: 1200px)": "lg",
						"(max-width: 992px)": "md",
						"(max-width: 768px)": "sm",
						"(max-width: 575px)": "xs"
					};

					const getResponsivePosition = (size = "default") => {
						const activeDevice = mediaQueryMap[size];

						const windowHeight = window?.innerHeight;
						const containerHeight = container?.clientHeight;
						const windowWidth = window?.innerWidth;
						const containerWidth = container?.clientWidth;

						if (!data?.position) {
							data.position = { top: { xl: "", lg: "", md: "", sm: "", unit: "%" }, left: { xl: "", lg: "", md: "", sm: "", unit: "%" } };
						}

						data.position = {
							top: {
								xl: data?.position?.top?.xl,
								lg: data?.position?.top?.lg || data?.position?.top?.xl,
								md: data?.position?.top?.md || data?.position?.top?.lg || data?.position?.top?.xl,
								sm: data?.position?.top?.sm || data?.position?.top?.md || data?.position?.top?.lg || data?.position?.top?.xl,
								xs: data?.position?.top?.xs || data?.position?.top?.sm || data?.position?.top?.md || data?.position?.top?.lg || data?.position?.top?.xl,
								unit: data?.position?.top?.unit,
							},
							left: {
								xl: data?.position?.left?.xl,
								lg: data?.position?.left?.lg || data?.position?.top?.xl,
								md: data?.position?.left?.md || data?.position?.left?.lg || data?.position?.top?.xl,
								sm: data?.position?.left?.sm || data?.position?.left?.md || data?.position?.left?.lg || data?.position?.top?.xl,
								xs: data?.position?.left?.xs || data?.position?.left?.sm || data?.position?.left?.md || data?.position?.left?.lg || data?.position?.top?.xl,
								unit: data?.position?.left?.unit,
							},
						};

						if (data?.position?.top?.unit !== "%") {
							container.style["top"] = data?.position?.top[activeDevice] + data?.position?.top?.unit;
						} else if (data?.position?.top?.unit === "%") {
							if (data?.position?.top[activeDevice] !== "") {
							if (data?.position?.top[activeDevice] != 50) {
								container.style["top"] = `calc(${data?.position?.top[activeDevice]}${data?.position?.top?.unit} - ${
								(data?.position?.top[activeDevice] * containerHeight) / 100
								}px)`;
							}
							}
						}

						if (data?.position?.left?.unit !== "%") {
							container.style["left"] = data?.position?.left[activeDevice] + data?.position?.left?.unit;
						} else if (data?.position?.left?.unit === "%") {
							if (data?.position?.left[activeDevice] !== "") {
							if (data?.position?.left[activeDevice] != 50) {
								container.style["left"] = `calc(${data?.position?.left[activeDevice]}${data?.position?.left?.unit} - ${
								(data?.position?.left[activeDevice] * containerWidth) / 100
								}px)`;
							}
							}
						}

						if (
							(data?.position?.top[activeDevice] === "" || data?.position?.top[activeDevice] == 50) &&
							data?.position?.top?.unit === "%"
						) {
							const isTop = windowHeight - containerHeight <= 0 ? "0" : null;
							container.style["top"] = isTop ? isTop : `calc(50% - ${containerHeight / 2}px)`;
						}
						if (
							(data?.position?.left[activeDevice] === "" || data?.position?.left[activeDevice] == 50) &&
							data?.position?.left?.unit === "%"
						) {
							const isLeft = windowWidth - containerWidth <= 0 ? "0" : null;
							container.style["left"] = isLeft ? isLeft : `calc(50% - ${containerWidth / 2}px)`;
						}
						if (data?.position?.top[activeDevice] == 100 && data?.position?.top?.unit === "%") {
							container.style["top"] = `calc(100% - ${containerHeight}px)`;
						}
						if (data?.position?.left[activeDevice] == 100 && data?.position?.left?.unit === "%") {
							container.style["left"] = `calc(100% - ${containerWidth}px)`;
						}
					}

					const mediaLG = window.matchMedia("(max-width: 1200px)");
					const mediaMD = window.matchMedia("(max-width: 992px)");
					const mediaSM = window.matchMedia("(max-width: 768px)");
					const mediaXS = window.matchMedia("(max-width: 575px)");

					function handleTabletChange() {
						if (mediaXS.matches) {
							getResponsivePosition("(max-width: 575px)");
						} else if (mediaSM.matches) {
							getResponsivePosition("(max-width: 768px)");
						} else if (mediaMD.matches) {
							getResponsivePosition("(max-width: 992px)");
						} else if (mediaLG.matches) {
							getResponsivePosition("(max-width: 1200px)");
						} else {
							getResponsivePosition();
						}
					}
					mediaLG.addListener(handleTabletChange);
					mediaMD.addListener(handleTabletChange);
					mediaSM.addListener(handleTabletChange);
					mediaXS.addListener(handleTabletChange);

					handleTabletChange(mediaLG);
					handleTabletChange(mediaMD);
					handleTabletChange(mediaSM);
					handleTabletChange(mediaXS);
			};
	
			const elementSelector = " .page-' . $popupId . '.sp-pagebuilder-popup .builder-container";

			const observerOptions = {
				childList: true,
				subtree: true
			};
	
			const observerCallback = (mutationsList, observer) => {
			for (let mutation of mutationsList) {
				if (mutation.type === "childList") {
				const element = document.querySelector(elementSelector);
				if (element) {
					onElementLoaded(element);
					window.onresize = () => onElementLoaded(element);
					observer.disconnect();
					break;
				}
				}
			}
			};
	
			const observer = new MutationObserver(observerCallback);

			observer.observe(document.body, observerOptions);

			const element = document.querySelector(elementSelector);
			if (element) {
				onElementLoaded(element);
				window.onresize = () => onElementLoaded(element);
				observer.disconnect();
			}

			window.addEventListener("beforeunload", function() {
				window.onresize = null;
			});
			';
		return $scriptContent;
	}

	public function getVisibilityScriptContent($popupId, $popupAttribs)
	{
		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$dontShowScript = ' 
			function isRestricted(id) {
    			const restrictedIds = JSON.parse(localStorage.getItem("restricted-popup-ids"));

				if (!restrictedIds) return false;

    			return restrictedIds.includes(id);
			}

			function isPermitted(id) {
				const storedTime = localStorage.getItem("reappear-popup-' . $popupId . '");

				if (storedTime) {
					const currentTimestamp = new Date().getTime();

					if (currentTimestamp - storedTime > 0) return true;
					return false;
				}
				return true;
			}

			function isWithinDateRange() {
				const dateRange = ' . (!empty($popupAttribs['date_range']['from']) && !empty($popupAttribs['date_range']['to']) ? json_encode($popupAttribs['date_range']) : 'null') . ';
				if (dateRange === null) {
					return true;
				}

				if (new Date(dateRange?.from) <= new Date(new Date().toISOString().split("T")[0] + "T06:00:00") && new Date(dateRange?.to) >= new Date(new Date().toISOString().split("T")[0] + "T06:00:00")) {
					return true;
				}
				return false;
			}
		';

		$scriptContent = '';
		if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_scroll')
		{
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				// if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				let previousScrollPosition = window.scrollY;
	
				window.onscroll = function() {
	
					const scrollPercentage = ' . (!empty($popupAttribs['scroll_percentage']) ? $popupAttribs['scroll_percentage'] : 40) . ';
					const scrollDirection = "' . (!empty($popupAttribs['scroll_direction']) ? $popupAttribs['scroll_direction'] : 'down') . '";
	
					const scrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
					
					const scrollPosition = (window.scrollY / scrollableHeight) * 100;

					const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
					const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");
	
					if (scrollDirection === "down" && scrollPosition > previousScrollPosition) {
	
						if (scrollPosition > scrollPercentage) {
							setTimeout(() => {
								containerDiv.parentNode.style.display = "block";
								overlayDiv.style.display = "block";
								' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . '); 

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}
						} else if (scrollDirection === "up" && scrollPosition < previousScrollPosition) {
							if (scrollPosition < scrollPercentage) {
							setTimeout(() => {
								containerDiv.parentNode.style.display = "block";
								overlayDiv.style.display = "block"; 
								' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . '); 

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}
					}
	
					previousScrollPosition = scrollPosition;
				  };
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_landing')
		{
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				// if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				const landingAfter = ' . (!empty($popupAttribs['landing_after']) ? $popupAttribs['landing_after'] : 0) . ';
				const landingShowAfter = ' . (!empty($popupAttribs['landing_show_after']) ? $popupAttribs['landing_show_after'] : "null") . ';

				const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
				const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");

				function getCookie(name) {
					let nameEQ = name + "=";
					let cookies = document.cookie.split(";");
					for (let i = 0; i < cookies.length; i++) {
						let cookie = cookies[i].trim();
						if (cookie.indexOf(nameEQ) === 0) {
							return decodeURIComponent(cookie.substring(nameEQ.length, cookie.length));
						}
					}
					return null;
				}

				function setCookie(name, value, days = null) {
					let expires = "";
					if (days) {
						let date = new Date();
						date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
						expires = "; expires=" + date.toUTCString();
					}

					document.cookie = name + "=" + (encodeURIComponent(value) || "") + expires + "; path=/";
				}

				function deleteCookie(name, path = "/", domain = null) {
					let cookieString = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
					
					if (path) {
						cookieString += "; path=" + path;
					}
					
					if (domain) {
						cookieString += "; domain=" + domain;
					}
					
					document.cookie = cookieString;
				}

				if (landingShowAfter === null) {
					const cookieLanding = getCookie("landingShowAfter-' . $popupId . '");
					if (cookieLanding !== null) {
						deleteCookie("landingShowAfter-' . $popupId . '", "/");
					}

					setTimeout(() => {
						containerDiv.parentNode.style.display = "block";
						overlayDiv.style.display = "block";
						' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

						' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
					}, (landingAfter * 1000) + ' . $popupAttribs['enter_animation_delay'] . ');

				} else {
					let totalHits = 0;
					const cookieLanding = getCookie("landingShowAfter-' . $popupId . '");
					if (cookieLanding === null) {
						totalHits = 1;
						setCookie("landingShowAfter-' . $popupId . '", 1);
					} else {
						totalHits = Number(cookieLanding) + 1;
						setCookie("landingShowAfter-' . $popupId . '", totalHits);
					}

					if (landingShowAfter === totalHits) {
						setTimeout(() => {
							containerDiv.parentNode.style.display = "block";
							overlayDiv.style.display = "block"; 
							' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}, (landingAfter * 1000) + ' . $popupAttribs['enter_animation_delay'] . ');
					}
				}
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_click') {
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				// if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				const clickType = "' . (!empty($popupAttribs['click_type']) ? $popupAttribs['click_type'] : 'random') . '";
				const clickCount = ' . (!empty($popupAttribs['click_count']) ? $popupAttribs['click_count'] : 1) . ';
				const clickArea = ' . (!empty($popupAttribs['click_area']) ? '"' . $popupAttribs['click_area'] . '"' : "null") . ';

				let clicked = 0;
				let isShown = false;

				if (clickType === "random") {
					document.addEventListener("click", () => {
						if (isRestricted(' . $popupId . ')) return;
						if (!isWithinDateRange(' . $popupId . ')) return;

						let closePopupArea = "#sp-pagebuilder-popup-close-btn-' . $popupId . '";
						let targetNode = event.target;
						if (targetNode.closest(closePopupArea)) {
							return;
						}

						clicked++;
						if (clicked >= clickCount) {
							const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
							const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");

							setTimeout(() => {
							containerDiv.parentNode.style.display = "block";
							overlayDiv.style.display = "block"; 
							' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . ');

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
						}
					});
				} else if (clickType === "specific") {
					if (clickArea !== null && clickArea !== undefined) {
						const selectedArea = document.querySelectorAll(clickArea);
						if(selectedArea !== null && selectedArea !== undefined) {
							Array.from(selectedArea).forEach(area => {
								area.addEventListener("click", () => {
									if (isRestricted(' . $popupId . ')) return; 
									// if (!isPermitted(' . $popupId . ')) return;
									if (!isWithinDateRange(' . $popupId . ')) return;

									clicked++;
									if (clicked >= clickCount) {
										const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
										const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");
			
										setTimeout(() => {
										containerDiv.parentNode.style.display = "block";
										overlayDiv.style.display = "block";
										' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

										}, ' . $popupAttribs['enter_animation_delay'] . '); 

										' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
										isShown = true;
									}
								});
							});
						}
					}
				}
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_hover') {
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				// if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				const hoverArea = "' . (!empty($popupAttribs['hover_area']) ? $popupAttribs['hover_area'] . '"' : "null") . ';
				const selectedArea = document.querySelectorAll(hoverArea);
				Array.from(selectedArea).forEach(area => {
					area.addEventListener("mouseover", () => {
							const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
							const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");

							setTimeout(() => {
							containerDiv.parentNode.style.display = "block";
							overlayDiv.style.display = "block";
							' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

							}, ' . $popupAttribs['enter_animation_delay'] . ');

							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '	
					});
				});
				
			});
			';
		} else if (!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_inactivity') {
			$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
				if (isRestricted(' . $popupId . ')) return; 
				// if (!isPermitted(' . $popupId . ')) return;
				if (!isWithinDateRange(' . $popupId . ')) return;

				const inactivityDuration = ' . (!empty($popupAttribs['inactivity_duration']) ? $popupAttribs['inactivity_duration'] : 0) . ';

				let idleTimeCounter = 0;
				let idleInterval = null;

				function resetIdleTimer(idleInterval) {
					if (!idleInterval) {
						idleInterval = setInterval(() => {
							if (document.body.getAttribute("data-stop-timer") == "true") {
								document.removeEventListener("mousemove", stopIdleCounter, false);
								document.removeEventListener("keypress", stopIdleCounter, false);
								document.removeEventListener("scroll", stopIdleCounter, false);
								document.removeEventListener("click", stopIdleCounter, false);
								clearInterval(idleInterval);
							}
							idleTimeCounter++;
							if (idleTimeCounter >= inactivityDuration) {
								const containerDiv = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
								const overlayDiv = document.querySelector("#sp-pagebuilder-overlay-' . $popupId .'");
	
								setTimeout(() => {
								containerDiv.parentNode.style.display = "block";
								overlayDiv.style.display = "block";
								' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . '

								}, ' . $popupAttribs['enter_animation_delay'] . ');

								' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' containerDiv.children[0].style.animationDirection = "normal"; containerDiv.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '
							}
						}, 1000);
					}
				}

				function stopIdleCounter() {
					clearInterval(idleInterval);
					idleInterval = null;
					idleTimeCounter = 0;
				}

				window.onload = function() {
					resetIdleTimer(idleInterval);

					document.addEventListener("mousemove", stopIdleCounter, false);
					document.addEventListener("keypress", stopIdleCounter, false);
					document.addEventListener("scroll", stopIdleCounter, false);
					document.addEventListener("click", stopIdleCounter, false);
				};
			});
			';
		}

		$scriptContent .= ' 
		
		window.addEventListener("DOMContentLoaded", (event) => {
			if (isRestricted(' . $popupId . ')) return; 
			// if (!isPermitted(' . $popupId . ')) return;
			if (!isWithinDateRange(' . $popupId . ')) return;

			const containerBuilderDiv = document.querySelector(".page-' . $popupId . '.sp-pagebuilder-popup .builder-container");

			const displayValue = window.getComputedStyle(containerBuilderDiv, null).display;

			if (displayValue === "block") {
				' . $this->getPositionScriptContent($popupId, json_encode($popupAttribs)) . ';
			}
		});
		';

		return $dontShowScript . ' ' . $scriptContent;
	}

	public function getAdvancedScriptContent($popupId, $popupAttribs) 
	{
		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => { 
			const builder = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup"); 
			const builderOverlay = document.getElementById("sp-pagebuilder-overlay-' . $popupId . '"); 
			';
		if (!empty($popupAttribs['auto_close']) && $popupAttribs['auto_close'] === 1) {
			if (!empty($popupAttribs['auto_close_after'])) {

				$landingDelay = 0;
				$popupAutoClose = !empty($popupAttribs['auto_close_after']) ? $popupAttribs['auto_close_after'] : 10;

				if ((!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_landing')) {
					$landingDelay = !empty($popupAttribs['landing_after']) ? $popupAttribs['landing_after'] : 0;
				}

				$scriptContent .= '
					setTimeout(() => {
						' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ' builder.children[0].style.animationDirection = "reverse"; builder.children[0].style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's"; builder.children[0].style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's"; builder.children[0].setAttribute("class", "page-content builder-container ' . $popupAttribs['exit_animation'] . '");' : "") . '
					}, ' . ((($landingDelay + $popupAutoClose) * 1000)) . ');

				builder.parentNode.style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
				builder.parentNode.style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
				
				setTimeout(() => {
					builder.parentNode.style.display = "none";
					builderOverlay.style.display = "none";
				}, ' . ((($landingDelay + $popupAutoClose) * 1000) + $popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) . '); 
				';
			}
		}

		if (!empty($popupAttribs['close_outside_click']) && $popupAttribs['close_outside_click'] === 1)
		{
			$scriptContent .= '
			window.onclick = function (event) {
				if (!(event.target.getAttribute("class") === "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container" || event.target.querySelector(".builder-container") === null)) {
					builder.parentNode.style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
					builder.parentNode.style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
					setTimeout(() => {
						builder.parentNode.style.display = "none";
						builderOverlay.style.display = "none";
					}, ' . ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) . '); 
				}
			};
				';
		}

		if (!empty($popupAttribs['close_on_esc']) && $popupAttribs['close_on_esc'] === 1)
		{
			$scriptContent .= '
			window.addEventListener("keydown", function(e) {
				if (e.key === "Escape" || e.key === "Esc") {
				builder.parentNode.style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
				builder.parentNode.style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
				setTimeout(() => {
					builder.parentNode.style.display = "none";
					builderOverlay.style.display = "none";
				}, ' . ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) . '); 
				}
			  });
				';
		}

		if (!empty($popupAttribs['disable_page_scrolling']) && $popupAttribs['disable_page_scrolling'] === 1)
		{
			$scriptContent .= '
			document.body.style.overfloyY = "hidden";
				';
		}

		$scriptContent .= '
			var timeUnitMap = {
				sec: 1000,
				min: 60000,
				hr: 3600000,
				day: 86400000,
				never: 0,
			};

			var reappearAfter = new Date().getTime() + (' . (isset($popupAttribs['reappear_after']) && !empty($popupAttribs['reappear_after']['value']) ? $popupAttribs['reappear_after']['value'] : 0) . ' * ' . ((isset($popupAttribs['reappear_after'])) && !empty($popupAttribs['reappear_after']['value']) ? 'timeUnitMap["' . $popupAttribs['reappear_after']['unit'] . '"]' : 0) . ');

			if (' . (isset($popupAttribs['reappear_after']) && !empty($popupAttribs['reappear_after']['unit']) ? ('"' . $popupAttribs['reappear_after']['unit'] . '"') : "null") . ' == "never") {
				reappearAfter = new Date().getTime() + 3153600000000;
			}
			localStorage.setItem("reappear-popup-' . $popupId . '", reappearAfter);
		';

		$scriptContent .= ' });';

		return $scriptContent;
	}

	public function getScriptContent($popupId, $popupAttribs)
	{
		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;

		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		$scriptContent = 'window.addEventListener("DOMContentLoaded", (event) => {
			function getImageSrc(imageSrc) {
				if (!imageSrc?.src) return imageSrc;
				if (imageSrc.src.includes("http://") || imageSrc.src.includes("https://")) {
					return { ...imageSrc, src: imageSrc?.src };
				} else {
					const baseUrl = window.location.origin;
					const originalSrc = baseUrl + "/" + imageSrc?.src;
					const formattedSrc = originalSrc.replace(/\\\/g, `/`);
					return { ...imageSrc, src: formattedSrc };
				}
			}

			const popupData = ' . json_encode($popupAttribs) . ';

			const newCloseElement = document.createElement("div");
			newCloseElement.setAttribute("id", "sp-pagebuilder-popup-close-btn-' . $popupId . '");
			newCloseElement.setAttribute("class", "sp-pagebuilder-popup-close-btn");
			newCloseElement.setAttribute("role", "button");
			newCloseElement.setAttribute("role", "button");

				if (popupData?.close_btn_text && !popupData?.close_btn_is_icon) {
					newCloseElement.style.gap = "5px";
				}

				newCloseElement.innerHTML = `
					<span class="close-btn-text" style="display: inline-block;">${popupData?.close_btn_text || ""}</span>
					<span class="close-btn-icon ${(popupData?.close_btn_icon !== undefined) ? popupData?.close_btn_icon : "fas fa-times"}" style="display: inline-block;" title="' . (Text::_('COM_SPPAGEBUILDER_TOP_PANEL_CLOSE')) . '"></span>
				`;

				const setClosePopup = (selector = null) => {
					if (selector === null) return;
					Array.from(document.querySelectorAll(selector)).forEach(element => {
						element.addEventListener("click", () => {
							const builder = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup"); 
							' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ' builder.children[0].style.animationDirection = "reverse"; builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['exit_animation'] . '");' : "") . '
							builder.children[0].style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
							builder.children[0].style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
							setTimeout(() => {
								builder.parentNode.style.display = "none";
								document.getElementById("sp-pagebuilder-overlay-' . $popupId . '").style.display = "none";
								document.body.style.overflowY = "auto";
							}, ' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) : "") . ');

							window.onscroll = null;
							document.body.setAttribute("data-stop-timer", "true");
						});
					});
				};
		
				const builder = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup");
				builder?.children[0]?.insertBefore(newCloseElement, builder?.children[0]?.children[0]);

				builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container");

					let landingDelay = 0;

					if ( ' . ((!empty($popupAttribs['trigger_condition']) && $popupAttribs['trigger_condition'] === 'on_landing') ? 1 : 0) . ') {
						landingDelay = ' . (!empty($popupAttribs['landing_after']) ? ($popupAttribs['landing_after'] * 1000) : 0) . ';
					}

					if (' . ((!empty($popupAttribs['toggle_enter_animation']) && !empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['enter_animation'] === $popupAttribs['exit_animation'])) ? 1 : 0) . ') {
						setTimeout(() => {
							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container");' : "") . '
						}, landingDelay + ' . ((!empty($popupAttribs['enter_animation_delay']) ? $popupAttribs['enter_animation_delay'] : 0) + (!empty($popupAttribs['enter_animation_duration']) ? $popupAttribs['enter_animation_duration'] : 0)) . ');
					}
				
				setClosePopup("#sp-pagebuilder-popup-close-btn-' . $popupId . '");

				if (popupData?.close_on_click) {
					setClosePopup(popupData?.close_on_click);
				}

		});';
		return $scriptContent;
	}

	private function getSanitized($content, $n = 0) {
		if (!is_string($content)) {
			return $content;
		}
	
		$primaryContent = json_decode($content);
	
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $content;
		}
	
		if (is_array($primaryContent) || is_object($primaryContent)) {
			return $primaryContent;
		}
	
		if ($n < 10 && is_string($primaryContent)) {
			return $this->getSanitized($primaryContent, $n + 1);
		}
	
		return $primaryContent;
	}

	public function generatePopup($selector = null, $settings = null) {
		if (!isset($selector) || !isset($settings)) return '';

		if (isset($settings->url) && !empty($settings->url) && isset($settings->url->type) && $settings->url->type == 'popup' && isset($settings->url->action) && $settings->url->action == 'open' && isset($settings->url->popup) && !empty($settings->url->popup)) {
			$popupId = $settings->url->popup;

			$db = Factory::getDbo();

			$query = $db->getQuery(true);
	
			$query->select($db->quoteName(['id', 'extension_view', 'content', 'attribs', 'published']))
				->from($db->quoteName('#__sppagebuilder'))
				->where($db->quoteName('id') . ' = ' . $db->quote($popupId));
	
			$db->setQuery($query);
			$result = $db->loadObject();
	
			if ($result) 
			{
				if ($result->extension_view !== 'popup' || $result->published !== 1) {
					return;
				}
				
				$popupAttribs = json_decode($result->attribs, true);
	
				$popupAttribs['trigger_condition'] = 'on_click';
				$popupAttribs['click_type'] = 'specific';
				$popupAttribs['click_area'] = $selector;
	
				$contentString = AddonParser::viewAddons($this->getSanitized($result->content), 0, 'page-popups', 1, true, []);
	
				$scriptContent = $this->getScriptContent($popupId, $popupAttribs);
	
				$cssOutput = $this->getCssOutput($popupAttribs, $popupId);
	
				$visibilityScriptContent = '';
	
				$visibilityScriptContent = $this->getVisibilityScriptContent($popupId, $popupAttribs);
	
				$advancedScriptContent = $this->getAdvancedScriptContent($popupId, $popupAttribs);

				$responsive_class = '';

				$responsive_class .= (isset($popupAttribs['hidden_xl']) && filter_var($popupAttribs['hidden_xl'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xl ' : '';
				$responsive_class .= (isset($popupAttribs['hidden_lg']) && filter_var($popupAttribs['hidden_lg'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-lg ' : '';
				$responsive_class .= (isset($popupAttribs['hidden_md']) && filter_var($popupAttribs['hidden_md'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-md ' : '';
				$responsive_class .= (isset($popupAttribs['hidden_sm']) && filter_var($popupAttribs['hidden_sm'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-sm ' : '';
				$responsive_class .= (isset($popupAttribs['hidden_xs']) && filter_var($popupAttribs['hidden_xs'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xs ' : '';
	
				$popupDiv = '
				<div class="'.$responsive_class.'" id="sp-pagebuilder-overlay-'. $popupId . '" style="position: fixed; inset: 0; z-index: 9999;"></div>
				<div class="sp-page-builder  page-' . $popupId . '  sp-pagebuilder-popup '.$responsive_class.'">
				<div class="sp-pagebuilder-container-popup">
				<div class=" page-content builder-container">' . $contentString . '</div>
				</div>
				</div>
				';
	
				return [$popupDiv, $scriptContent, $cssOutput, $visibilityScriptContent, $advancedScriptContent];
				
			}
		} else if (isset($settings->url) && !empty($settings->url) && isset($settings->url->type) && $settings->url->type == 'popup' && isset($settings->url->action) && $settings->url->action == 'close' && isset($settings->url->close_popup) && !empty($settings->url->close_popup)) {
			$popupId = $settings->url->close_popup;

			$isNoRepeat = isset($settings->url->is_no_repeat) ? $settings->url->is_no_repeat : 0;

			$db = Factory::getDbo();

			$query = $db->getQuery(true);
	
			$query->select($db->quoteName(['id', 'extension_view', 'content', 'attribs', 'published']))
				->from($db->quoteName('#__sppagebuilder'))
				->where($db->quoteName('id') . ' = ' . $db->quote($popupId));
	
			$db->setQuery($query);
			$result = $db->loadObject();

			if ($result) {
				if ($result->extension_view !== 'popup' || $result->published !== 1) {
					return;
				}
				$popupAttribs = json_decode($result->attribs, true);

				$jquery = '
				
					function appendRestrictedId(id) {
						let restrictedIds = JSON.parse(localStorage.getItem("restricted-popup-ids"));

						if (!restrictedIds) {
							restrictedIds = [id];
						} else {
							if (!restrictedIds.includes(id)) {
								restrictedIds.push(id);
							} else {
								return;
							}
						}

						localStorage.setItem("restricted-popup-ids", JSON.stringify(restrictedIds));

					}
					
					function removeRestrictedId(id) {
						let restrictedIds = JSON.parse(localStorage.getItem("restricted-popup-ids"));

						if (restrictedIds && restrictedIds.includes(id)) {
							restrictedIds = restrictedIds.filter(item => item !== id);

							localStorage.setItem("restricted-popup-ids", JSON.stringify(restrictedIds));

						}
					}

					if (!' . $isNoRepeat . ') {
						removeRestrictedId(' . $popupId . ');
					}
				
				jQuery(document).ready(function ($) {
					const setOutsideClosePopup = (selector = null) => {
						if (selector === null) return;
						Array.from(document.querySelectorAll(selector)).forEach(element => {
							element.addEventListener("click", () => {
								const builder = document.querySelector(".page-' . $popupId . ' .sp-pagebuilder-container-popup"); 
								' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ' builder.children[0].style.animationDirection = "reverse"; builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['exit_animation'] . '");' : "") . '
								builder.children[0].style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's";
								builder.children[0].style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's";
								setTimeout(() => {
									builder.parentNode.style.display = "none";
									const overlayDivEl = document.getElementById("sp-pagebuilder-overlay-' . $popupId . '") || document.getElementById("sp-pagebuilder-overlay");
									overlayDivEl.style.display = "none";
								}, ' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) : "") . ');

								window.onscroll = null;
								document.body.setAttribute("data-stop-timer", "true");
							});
						});
					};
					setOutsideClosePopup("' . $selector . '");
					document.querySelector("' . $selector . '").addEventListener("click", () => {
						if (' . $isNoRepeat . ') {
							appendRestrictedId(' . $popupId . ');
						}
					});
				});';
				return $jquery;
			}
		} else {
			return '';
		}
	}
 }