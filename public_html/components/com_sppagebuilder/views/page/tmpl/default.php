<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

/** @var CMSApplication */
$app = Factory::getApplication();
$doc = Factory::getDocument();
$user = Factory::getUser();

$params = ComponentHelper::getParams('com_sppagebuilder');

if ($params->get('fontawesome', 1))
{	SppagebuilderHelperSite::addStylesheet('font-awesome-6.min.css');
	SppagebuilderHelperSite::addStylesheet('font-awesome-v4-shims.css');
}

if (!$params->get('disableanimatecss', 0))
{
	SppagebuilderHelperSite::addStylesheet('animate.min.css');
}

if (!$params->get('disablecss', 0))
{
	SppagebuilderHelperSite::addStylesheet('sppagebuilder.css');
	if (!$params->get('disableanimatecss', 0))
	{
		SppagebuilderHelperSite::addStylesheet('animate.min.css');
	}
	SppagebuilderHelperSite::addContainerMaxWidth();
}

SppagebuilderHelperSite::addStylesheet('dynamic-content.css');
SppagebuilderHelperSite::addScript('dynamic-content.js');



// load font assets form database
SppagebuilderHelperSite::loadAssets();

HTMLHelper::_('jquery.framework');
HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/jquery.parallax.js', ['version' => SppagebuilderHelperSite::getVersion(true)]);

HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/es5_interaction.js', ['version' => SppagebuilderHelperSite::getVersion(true)], ['defer' => true]);
HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/sppagebuilder.js', ['version' => SppagebuilderHelperSite::getVersion(true)], ['defer' => true]);

$menus = $app->getMenu();
$menu = $menus->getActive();
$menuClassPrefix = '';
$showPageHeading = 0;

// check active menu item
if ($menu)
{
	$menuClassPrefix 	= $menu->getParams()->get('pageclass_sfx');
	$showPageHeading 	= $menu->getParams()->get('show_page_heading');
	$menuHeading 		= $menu->getParams()->get('page_heading');
}

function adjustedMargin($originalData) {
    $marginElems = explode(' ', $originalData);
    $topBottomMargin = "calc({$marginElems[0]} - {$marginElems[2]})";
    $leftRightMargin = "calc({$marginElems[3]} - {$marginElems[1]})";

    return "{$topBottomMargin} {$leftRightMargin}";
}


$page = $this->item;

require_once JPATH_ROOT . '/components/com_sppagebuilder/parser/addon-parser.php';
require_once JPATH_ROOT . '/components/com_sppagebuilder/builder/classes/addon.php';

$content = $page->text;

$page->text = AddonParser::viewAddons($content, 0, 'page-' . $page->id, ...$this->additionalAttributes);

PluginHelper::importPlugin('content', 'sppagebuilderloadmodule');
Factory::getApplication()->triggerEvent('onContentPrepare', ['com_sppagebuilder.page', $page, null, 0]);

// Add page css
if (isset($page->css) && $page->css)
{
	$doc->addStyledeclaration($page->css);
}

?>

<?php
	$popupAttribs = json_decode($this->item->attribs, true);
	$responsive_class = '';

	$responsive_class .= (isset($popupAttribs['hidden_xl']) && filter_var($popupAttribs['hidden_xl'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xl ' : '';
	$responsive_class .= (isset($popupAttribs['hidden_lg']) && filter_var($popupAttribs['hidden_lg'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-lg ' : '';
	$responsive_class .= (isset($popupAttribs['hidden_md']) && filter_var($popupAttribs['hidden_md'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-md ' : '';
	$responsive_class .= (isset($popupAttribs['hidden_sm']) && filter_var($popupAttribs['hidden_sm'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-sm ' : '';
	$responsive_class .= (isset($popupAttribs['hidden_xs']) && filter_var($popupAttribs['hidden_xs'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xs ' : '';

	if ($this->item->extension_view === 'popup') : ?>
		<div class="<?php echo $responsive_class; ?>" id="sp-pagebuilder-overlay" data-isoverlay=<?php echo !empty(json_decode($this->item->attribs, true)['overlay']) ? 'true' : 'false' ?> style="position: fixed; inset: 0; z-index: 9999;"></div>
	<?php endif; ?>

<div id="sp-page-builder" class="sp-page-builder <?php echo $menuClassPrefix; ?> page-<?php echo $page->id; ?> <?php echo $page->extension_view === 'popup' ? " sp-pagebuilder-popup " . $responsive_class : ""; ?>" x-data="easystoreProductList">

	<?php if ($showPageHeading && $this->item->extension_view !== 'popup') : ?>
		<div class="page-header">
			<h1 itemprop="name">
				<?php echo $menuHeading ? $menuHeading : $page->title; ?>
			</h1>
		</div>
	<?php endif; ?>

	<?php 
	if ($this->item->extension_view === 'popup') : ?>
	<style type="text/css">
		<?php 
			$popupAttribs = json_decode($this->item->attribs, true);
			if (!empty($popupAttribs['custom_css'])){
				echo ' ' . $popupAttribs['custom_css'] . ' ';
			}
		?>

	<?php 

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
	
		$responsiveStr = ' .sp-pagebuilder-popup .builder-container {
		' . (!empty($width_xl) ? ('width: ' . $width_xl . ';') : '') . '
		' . (!empty($max_width_xl) ? ('max-width: ' . $max_width_xl . ';') : '') . '
		' . (!empty($height_xl) ? ('height: ' . $height_xl . ';') : '') . '
		' . (!empty($max_height_xl) ? ('max-height: ' . $max_height_xl . ';') : '') . '
		' . (!empty($border_radius_xl) ? ('border-radius: ' . $border_radius_xl . ';') : '') . '
	}
	@media (max-width: 1200px) {
		.sp-pagebuilder-popup .builder-container {
			' . (!empty($width_lg) ? ('width: ' . $width_lg . ';') : '') . '
			' . (!empty($max_width_lg) ? ('max-width: ' . $max_width_lg . ';') : '') . '
			' . (!empty($height_lg) ? ('height: ' . $height_lg . ';') : '') . '
			' . (!empty($max_height_lg) ? ('max-height: ' . $max_height_lg . ';') : '') . '
			' . (!empty($border_radius_lg) ? ('border-radius: ' . $border_radius_lg . ';') : '') . '
		}
	}
	@media (max-width: 992px) {
		.sp-pagebuilder-popup .builder-container {
			' . (!empty($width_md) ? ('width: ' . $width_md) . ';' : '') . '
			' . (!empty($max_width_md) ? ('max-width: ' . $max_width_md . ';') : '') . '
			' . (!empty($height_md) ? ('height: ' . $height_md . ';') : '') . '
			' . (!empty($max_height_md) ? ('max-height: ' . $max_height_md . ';') : '') . '
			' . (!empty($border_radius_md) ? ('border-radius: ' . $border_radius_md . ';') : '') . '
		}
	}
	@media (max-width: 768px) {
		.sp-pagebuilder-popup .builder-container {
			' . (!empty($width_sm) ? ('width: ' . $width_sm . ';') : '') . '
			' . (!empty($max_width_sm) ? ('max-width: ' . $max_width_sm . ';') : '') . '
			' . (!empty($height_sm) ? ('height: ' . $height_sm . ';') : '') . '
			' . (!empty($max_height_sm) ? ('max-height: ' . $max_height_sm . ';') : '') . '
			' . (!empty($border_radius_sm) ? ('border-radius: ' . $border_radius_sm . ';') : '') . '
		}
	}
	@media (max-width: 575px) {
		.sp-pagebuilder-popup .builder-container {
			' . (!empty($width_xs) ? ('width: ' . $width_xs . ';') : '') . '
			' . (!empty($max_width_xs) ? ('max-width: ' . $max_width_xs . ';') : '') . '
			' . (!empty($height_xs) ? ('height: ' . $height_xs . ';') : '') . '
			' . (!empty($max_height_xs) ? ('max-height: ' . $max_height_xs . ';') : '') . '
			' . (!empty($border_radius_xs) ? ('border-radius: ' . $border_radius_xs . ';') : '') . '
		}
	} ';

		echo $responsiveStr;

	?>

	.sp-pagebuilder-popup .builder-container {
		position: absolute;
		animation-duration: <?php echo isset(json_decode($this->item->attribs, true)['enter_animation_duration']) ? (json_decode($this->item->attribs, true)['enter_animation_duration'] / 1000) . 's' : '2s' ?>;
		display: none;
	}
	
	.sp-pagebuilder-popup .builder-container {
		padding: <?php echo !empty(json_decode($this->item->attribs, true)['padding']) ? json_decode($this->item->attribs, true)['padding'] : 'initial' ?>;
		margin: <?php echo !empty(json_decode($this->item->attribs, true)['margin']) ? adjustedMargin(json_decode($this->item->attribs, true)['margin']) : 'initial' ?>;

		border-width: <?php echo !empty(json_decode($this->item->attribs, true)['border']['border_width']) ? json_decode($this->item->attribs, true)['border']['border_width'] : 'initial' ?>;
		border-style: <?php echo !empty(json_decode($this->item->attribs, true)['border']['border_style']) ? json_decode($this->item->attribs, true)['border']['border_style'] : 'initial' ?>;
		border-color: <?php echo !empty(json_decode($this->item->attribs, true)['border']['border_color']) ? json_decode($this->item->attribs, true)['border']['border_color'] : 'initial' ?>;
	}

	<?php 
		$popupAttribs = json_decode($this->item->attribs, true);
		if (!empty($popupAttribs['boxshadow']) && $popupAttribs['boxshadow']['enabled'] === true)
		{
			echo ' .sp-pagebuilder-popup .builder-container {
				box-shadow: ' . ((bool)($popupAttribs['boxshadow']['ho']) ? $popupAttribs['boxshadow']['ho'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['vo']) ? $popupAttribs['boxshadow']['vo'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['blur']) ? $popupAttribs['boxshadow']['blur'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['spread']) ? $popupAttribs['boxshadow']['spread'] : '0') . 'px ' . ((bool)($popupAttribs['boxshadow']['color']) ? $popupAttribs['boxshadow']['color'] : 'initial') . ';
			} ';
		}
			echo ' .sp-pagebuilder-popup .builder-container {
				background-color: ' . (!empty($popupAttribs['bg_color']) ? $popupAttribs['bg_color'] : 'white') . ';
			} ';

		if (!empty($popupAttribs['background_type']) && !empty($popupAttribs['bg_media']) && $popupAttribs['background_type'] === 'image')
		{
			$bgImageSrc = $popupAttribs['bg_media']['src'];
			if (!preg_match('#^(https?://|//)#', $bgImageSrc) && substr($bgImageSrc, 0, 1) !== '/') {
				$bgImageSrc = Uri::root(true) . '/' . ltrim($bgImageSrc, '/');
			}
			
			echo ' .sp-pagebuilder-popup .builder-container {
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
				echo ' .sp-pagebuilder-popup .builder-container {
					background-color: unset;
					background-image: linear-gradient(' . $deg . 'deg, ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
				}';
			}
			else if ($type === 'radial')
			{
				echo ' .sp-pagebuilder-popup .builder-container {
					background-color: unset;
					background-image: radial-gradient(' . $radialPos . ', ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
				}';
			}
		}

		if (!isset($popupAttribs['overlay']) || ($popupAttribs['overlay'] === 1))
		{
			echo ' #sp-pagebuilder-overlay {
				display: block;
				background-color: ' . (!empty($popupAttribs['overlay_bg_color']) && (bool)$popupAttribs['overlay_bg_color'] ? $popupAttribs['overlay_bg_color'] : 'rgba(0, 0, 0, 0.7)') . ';
			} ';

			if (!empty($popupAttribs['overlay']) && !empty($popupAttribs['overlay_bg_media']) && !empty($popupAttribs['overlay_background_type']) && $popupAttribs['overlay_background_type'] === 'image')
			{
				$overlayBgImageSrc = $popupAttribs['overlay_bg_media']['src'];
				if (!preg_match('#^(https?://|//)#', $overlayBgImageSrc) && substr($overlayBgImageSrc, 0, 1) !== '/') {
					$overlayBgImageSrc = Uri::root(true) . '/' . ltrim($overlayBgImageSrc, '/');
				}
				
				echo ' #sp-pagebuilder-overlay {
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
					echo ' #sp-pagebuilder-overlay {
						background-color: unset;
						background-image: linear-gradient(' . $deg . 'deg, ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
					}';
				}
				else if ($type === 'radial')
				{
					echo ' #sp-pagebuilder-overlay {
						background-color: unset;
						background-image: radial-gradient(' . $radialPos . ', ' . $color . ' ' . $pos . '%, ' . $color2 . ' ' . $pos2 . '%);
					}';
				}
			}
		}
		else if (isset($popupAttribs['overlay']) && $popupAttribs['overlay'] === 0)
		{
			echo ' #sp-pagebuilder-overlay {
				display: none;
			} ';
		}
	?>

		.sp-pagebuilder-popup {
			display: none;
		}

		#sp-pagebuilder-popup-close-btn {
		display: flex;
		justify-content: center;
		align-items: center;
	}

		<?php 
	if (true) : ?>
		#sp-pagebuilder-popup-close-btn {
			color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_color']) ? json_decode($this->item->attribs, true)['close_btn_color'] : 'initial' ?>;

			border-width: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border']['border_width']) ? json_decode($this->item->attribs, true)['close_btn_border']['border_width'] : 'initial' ?>;
			border-style: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border']['border_style']) ? json_decode($this->item->attribs, true)['close_btn_border']['border_style'] : 'initial' ?>;
			border-color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border']['border-color']) ? json_decode($this->item->attribs, true)['close_btn_border']['border_color'] : 'initial' ?>;

			border-radius: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border_radius']) ? (json_decode($this->item->attribs, true)['close_btn_border_radius'] . 'px') : '0px' ?>;
		}
		#sp-pagebuilder-popup-close-btn {
			background-color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_bg_color']) ? json_decode($this->item->attribs, true)['close_btn_bg_color'] : 'initial' ?>;
		}
		#sp-pagebuilder-popup-close-btn {
			padding: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_padding']) ? json_decode($this->item->attribs, true)['close_btn_padding'] : 'initial' ?>;
		}
		.sp-pagebuilder-popup-close-btn-hover:hover {
			color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_color_hover']) ? json_decode($this->item->attribs, true)['close_btn_color_hover'] : 'initial' ?> !important;
			background-color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_bg_color_hover']) ? json_decode($this->item->attribs, true)['close_btn_bg_color_hover'] : 'initial' ?> !important;
		}
	<?php endif; ?>

	<?php 
	if (!empty(json_decode($this->item->attribs, true)['close_btn_icon_size'])) : ?>
	<?php
	$fontSizeMap = [
		'small' => '0.75',
		'medium' => '1',
		'large' => '1.2',
		'x-large' => '1.6',
	]
	?>
	<?php endif; ?>

	<?php
	if (empty(json_decode($this->item->attribs, true)['close_btn_position']) || json_decode($this->item->attribs, true)['close_btn_position'] === 'inside' || json_decode($this->item->attribs, true)['close_btn_position'] === 0 || json_decode($this->item->attribs, true)['close_btn_position'] === '' || empty(json_decode($this->item->attribs, true)['close_btn_position'])) 
	{
		echo '#sp-pagebuilder-popup-close-btn {
			transform: scale(1.2);
			right: 25px;
			top: 20px;
		}';
	}
	else if (json_decode($this->item->attribs, true)['close_btn_position'] === 'outside' || json_decode($this->item->attribs, true)['close_btn_position'] === 'outside')
	{
		echo '#sp-pagebuilder-popup-close-btn {
			transform: scale(1.2);
			right: 5px;
			top: -30px;
		}';
	}
	else if (json_decode($this->item->attribs, true)['close_btn_position'] === 'outside' || json_decode($this->item->attribs, true)['close_btn_position'] === 'custom')
	{
		$btn_position_x_xl = !empty($popupAttribs['close_btn_position_x']['xl']) ? $popupAttribs['close_btn_position_x']['xl'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['xl']) && $popupAttribs['close_btn_position_x']['xl'] == '0' ? '0' : '25px');
		$btn_position_x_lg = !empty($popupAttribs['close_btn_position_x']['lg']) ? $popupAttribs['close_btn_position_x']['lg'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['lg']) && $popupAttribs['close_btn_position_x']['lg'] == '0' ? '0' : $btn_position_x_xl);
		$btn_position_x_md = !empty($popupAttribs['close_btn_position_x']['md']) ? $popupAttribs['close_btn_position_x']['md'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['md']) && $popupAttribs['close_btn_position_x']['md'] == '0' ? '0' : $btn_position_x_lg);
		$btn_position_x_sm = !empty($popupAttribs['close_btn_position_x']['sm']) ? $popupAttribs['close_btn_position_x']['sm'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['sm']) && $popupAttribs['close_btn_position_x']['sm'] == '0' ? '0' : $btn_position_x_md);
		$btn_position_x_xs = !empty($popupAttribs['close_btn_position_x']['xs']) ? $popupAttribs['close_btn_position_x']['xs'] . $popupAttribs['close_btn_position_x']['unit'] : (isset($popupAttribs['close_btn_position_x']['xs']) && $popupAttribs['close_btn_position_x']['xs'] == '0' ? '0' : $btn_position_x_sm);

		$btn_position_y_xl = !empty($popupAttribs['close_btn_position_y']['xl']) ? $popupAttribs['close_btn_position_y']['xl'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (isset($popupAttribs['close_btn_position_y']['xl']) && $popupAttribs['close_btn_position_y']['xl'] == '0' ? '0' : '20px');
		$btn_position_y_lg = !empty($popupAttribs['close_btn_position_y']['lg']) ? $popupAttribs['close_btn_position_y']['lg'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (isset($popupAttribs['close_btn_position_y']['lg']) && $popupAttribs['close_btn_position_y']['lg'] == '0' ? '0' : $btn_position_y_xl);
		$btn_position_y_md = !empty($popupAttribs['close_btn_position_y']['md']) ? $popupAttribs['close_btn_position_y']['md'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : ((isset($popupAttribs['close_btn_position_y']['md']) && $popupAttribs['close_btn_position_y']['md'] == '0' ? '0' : $btn_position_y_lg));
		$btn_position_y_sm = !empty($popupAttribs['close_btn_position_y']['sm']) ? $popupAttribs['close_btn_position_y']['sm'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (((isset($popupAttribs['close_btn_position_y']['sm']) && $popupAttribs['close_btn_position_y']['sm'] == '0' ? '0' : $btn_position_y_md)));
		$btn_position_y_xs = !empty($popupAttribs['close_btn_position_y']['xs']) ? $popupAttribs['close_btn_position_y']['xs'] . ($popupAttribs['close_btn_position_y']['unit'] !== '%' ? $popupAttribs['close_btn_position_y']['unit'] : 'vh') : (((isset($popupAttribs['close_btn_position_y']['xs']) && $popupAttribs['close_btn_position_y']['xs'] == '0' ? '0' : $btn_position_y_sm)));

		echo ' #sp-pagebuilder-popup-close-btn {
			transform: scale(1.2);

			right: ' . $btn_position_x_xl . '; 
			top: ' . $btn_position_y_xl . ';
		}
		@media (max-width: 1200px) {
			#sp-pagebuilder-popup-close-btn {
				right: ' . $btn_position_x_lg . '; 
				top: ' . $btn_position_y_lg . ';
			}
		}
		@media (max-width: 992px) {
			#sp-pagebuilder-popup-close-btn {
				right: ' . $btn_position_x_md . '; 
				top: ' . $btn_position_y_md . ';
			}
		}
		@media (max-width: 768px) {
			#sp-pagebuilder-popup-close-btn {
				right: ' . $btn_position_x_sm . '; 
				top: ' . $btn_position_y_sm . ';
			}
		}
		@media (max-width: 575px) {
			#sp-pagebuilder-popup-close-btn {
				right: ' . $btn_position_x_xs . '; 
				top: ' . $btn_position_y_xs . ';
			}
		} ';	
	}
	
	?>
</style>
<?php endif; ?>

<?php 
	if ($this->item->extension_view === 'popup') : ?>
	<?php
		function getAdvancedScriptContent($popupId, $popupAttribs) 
		{
			$scriptContent = '<script>window.addEventListener("DOMContentLoaded", (event) => { 
				const builder = document.querySelector(".sp-pagebuilder-container-popup"); 
				const builderOverlay = document.getElementById("sp-pagebuilder-overlay"); 
				';
			if (!empty($popupAttribs['auto_close']) && $popupAttribs['auto_close'] === 1) {
				if (!empty($popupAttribs['auto_close_after'])) {

					$popupAutoClose = !empty($popupAttribs['auto_close_after']) ? $popupAttribs['auto_close_after'] : 10;
	
					$scriptContent .= '
					setTimeout(() => {
						' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ' builder.children[0].style.animationDirection = "reverse"; builder.children[0].style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's"; builder.children[0].style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's"; builder.children[0].setAttribute("class", "page-content builder-container ' . $popupAttribs['exit_animation'] . '");' : "") . '
					}, ' . ((($popupAutoClose) * 1000)) . ');
	
					setTimeout(() => {
						builder.parentNode.style.display = "none";
						builderOverlay.style.display = "none";
					}, ' . ((($popupAutoClose) * 1000) + $popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) . '); 
					';
				}
			}
	
			if (!empty($popupAttribs['close_outside_click']) && $popupAttribs['close_outside_click'] === 1)
			{
				$scriptContent .= '
				window.onclick = function (event) {
					if (!(event.target.getAttribute("class") === "page-content builder-container" || event.target.querySelector(".builder-container") === null)) {
						builder.parentNode.style.display = "none";
						builderOverlay.style.display = "none";
					}
				};
					';
			}
	
			if (!empty($popupAttribs['close_on_esc']) && $popupAttribs['close_on_esc'] === 1)
			{
				$scriptContent .= '
				window.addEventListener("keydown", function(e) {
					if (e.key === "Escape" || e.key === "Esc") {
						builder.parentNode.style.display = "none";
						builderOverlay.style.display = "none";
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
	
			$scriptContent .= ' }); </script>';
	
			return $scriptContent;
		}

		$app = Factory::getApplication();
		$input = $app->input;
		$popupId = $input->get('id', '', 'INT');

		$popupAttribs['enter_animation_duration'] = isset($popupAttribs['enter_animation_duration']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_duration'] : (isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? 2000 : 0);

		$popupAttribs['exit_animation_duration'] = isset($popupAttribs['exit_animation_duration']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_duration'] : (isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? 2000 : 0);

		$popupAttribs['enter_animation_delay'] = isset($popupAttribs['enter_animation_delay']) && isset($popupAttribs['toggle_enter_animation']) && $popupAttribs['toggle_enter_animation'] == 1 ? $popupAttribs['enter_animation_delay'] : 0;
		$popupAttribs['exit_animation_delay'] = isset($popupAttribs['exit_animation_delay']) && isset($popupAttribs['toggle_exit_animation']) && $popupAttribs['toggle_exit_animation'] == 1 ? $popupAttribs['exit_animation_delay'] : 0;
		$popupAttribs['enter_animation'] = isset($popupAttribs['enter_animation']) ? $popupAttribs['enter_animation'] : 'fadeIn';
		$popupAttribs['exit_animation'] = isset($popupAttribs['exit_animation']) ? $popupAttribs['exit_animation'] : 'rotateIn';

		echo getAdvancedScriptContent($popupId, $popupAttribs);

		echo '<script>
			window.addEventListener("DOMContentLoaded", (event) => {

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

				const popupData = ' . $this->item->attribs . ';
				const builder = document.querySelector(".sp-pagebuilder-container-popup");

				' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' builder.children[0].style.animationDirection = "normal"; builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['enter_animation'] . '");' : "") . '

				setTimeout(() => {
					builder.children[0].style.display = "block";

				}, ' . $popupAttribs['enter_animation_delay'] . ');

				const newCloseElement = document.createElement("div");
				newCloseElement.setAttribute("id", "sp-pagebuilder-popup-close-btn");
				newCloseElement.setAttribute("class", "sp-pagebuilder-popup-close-btn sp-pagebuilder-popup-close-btn-hover");
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
							const builder = document.querySelector(".sp-pagebuilder-container-popup"); 
							' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ' builder.children[0].style.animationDirection = "reverse"; builder.children[0].style.animationDuration = "' . ($popupAttribs['exit_animation_duration'] / 1000) . 's"; builder.children[0].style.animationDelay = "' . ($popupAttribs['exit_animation_delay'] / 1000) . 's"; builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container ' . $popupAttribs['exit_animation'] . '");' : "") . '
							setTimeout(() => {
								builder.parentNode.style.display = "none";
								document.getElementById("sp-pagebuilder-overlay").style.display = "none";
							}, ' . (!empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['toggle_exit_animation'] === 1 || $popupAttribs['toggle_exit_animation'] === 1) ? ($popupAttribs['exit_animation_duration'] + $popupAttribs['exit_animation_delay']) : "") . ');
						});
					});
				};

					let landingDelay = 0;

					if (' . ((!empty($popupAttribs['toggle_enter_animation']) && !empty($popupAttribs['toggle_exit_animation']) && ($popupAttribs['enter_animation'] === $popupAttribs['exit_animation'])) ? 1 : 0) . ') {
						setTimeout(() => {
							' . (!empty($popupAttribs['toggle_enter_animation']) && ($popupAttribs['toggle_enter_animation'] === 1 || $popupAttribs['toggle_enter_animation'] === 1) ? ' builder.children[0].setAttribute("class", "' . (!empty($popupAttribs['css_class']) ? $popupAttribs['css_class'] : "") . ' page-content builder-container");' : "") . '
						}, landingDelay + ' . ((!empty($popupAttribs['enter_animation_delay']) ? $popupAttribs['enter_animation_delay'] : 0) + (!empty($popupAttribs['enter_animation_duration']) ? $popupAttribs['enter_animation_duration'] : 0)) . ');
					}

				setTimeout(() => {
					const builder = document.querySelector(".sp-pagebuilder-container-popup");
					builder?.children[0]?.insertBefore(newCloseElement, builder?.children[0]?.children[0]);

					setClosePopup("#sp-pagebuilder-popup-close-btn");

					if (popupData?.close_on_click) {
						setClosePopup([popupData?.close_on_click]);
					}

					}, ' . $popupAttribs['enter_animation_delay'] . ');
			});
		</script>';
		
		echo '<script>
		window.addEventListener("DOMContentLoaded", (event) => {

			const popupWrapperDiv = document.querySelector(".sp-pagebuilder-popup") || window?.iWindow?.document?.querySelector(".sp-pagebuilder-popup");
			
			if (!popupWrapperDiv){
				return;
			}
			popupWrapperDiv.style.display = "block";

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
							const containerStyle = container?.style;

							const windowHeight = window?.innerHeight;
							const containerHeight = container?.clientHeight;
							const windowWidth = window?.innerWidth;
							const containerWidth = container?.clientWidth;
		
							const data = ' . $this->item->attribs . ';

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
	
			const elementSelector = ".builder-container";

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
	
			const popupData = ' . $this->item->attribs . ';
			' . ($popupAttribs['enter_animation_delay'] === 0 ? 'observer.observe(document.body, observerOptions);' : '
				setTimeout(() => {
					observer.observe(document.body, observerOptions);
				}, Math.max(' . $popupAttribs['enter_animation_delay'] . ' - 1, 0));
			') . '
			
			window.addEventListener("beforeunload", function() {
				window.onresize = null;
			});

			setTimeout(() => {
				const builder = document.getElementById("sp-pagebuilder-container");
				builder?.children[0]?.insertBefore(newCloseElement, builder?.children[0]?.children[0]);
			}, ' . $popupAttribs['enter_animation_delay'] . ');
		});
	</script>'
?>
<?php endif; ?>

	<?php 
	if ($this->item->extension_view === 'popup') : ?>
		<div class="sp-pagebuilder-container-popup">
	<?php endif; ?>

	<div class="page-content builder-container" x-data="easystoreProductDetails">

		<?php $pageName = 'page-' . $page->id; ?>
		<?php echo $page->text; ?>

		<?php if ($this->canEdit && $this->item->extension_view != 'popup') : ?>
			<a class="sp-pagebuilder-page-edit" href="<?php echo $this->checked_out ? $this->item->formLink : $this->item->link . '#'; ?>">
				<?php if (!$this->checked_out) : ?>
					<span class="fas fa-lock" area-hidden="true"></span> <?php echo Text::_('COM_SPPAGEBUILDER_PAGE_CHECKED_OUT'); ?>
				<?php else : ?>
					<span class="fas fa-edit" area-hidden="true"></span> <?php echo Text::_('COM_SPPAGEBUILDER_PAGE_EDIT'); ?>
				<?php endif; ?>
			</a>
		<?php endif; ?>
	</div>

	<?php 
	if ($this->item->extension_view === 'popup') : ?>
		</div>
	<?php endif; ?>
</div>