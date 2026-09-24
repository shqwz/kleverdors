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
use Joomla\CMS\Uri\Uri;

/** @var CMSApplication */
$app = Factory::getApplication();
$doc = Factory::getDocument();
$params = ComponentHelper::getParams('com_sppagebuilder');

$doc->addScriptdeclaration('var disableGoogleFonts = ' . $params->get('disable_google_fonts', 0) . ';');

if ($params->get('fontawesome', 1)) {
	SppagebuilderHelperSite::addStylesheet('font-awesome-6.min.css');
	SppagebuilderHelperSite::addStylesheet('font-awesome-v4-shims.css');
}

// assets
SppagebuilderHelperSite::loadAssets();

if (!$params->get('disableanimatecss', 0)) {
	SppagebuilderHelperSite::addStylesheet('animate.min.css');
}

if (!$params->get('disablecss', 0)) {
	SppagebuilderHelperSite::addStylesheet('sppagebuilder.css');
	SppagebuilderHelperSite::addContainerMaxWidth();
}

SppagebuilderHelperSite::addStylesheet('canvas.css');

SppagebuilderHelperSite::addStylesheet('dynamic-content.css');
SppagebuilderHelperSite::addScript('dynamic-content.js');


HTMLHelper::_('jquery.framework');
$doc->addScriptdeclaration('var pagebuilder_base="' . Uri::root() . '";');
SppagebuilderHelperSite::addScript('jquery.parallax.js');
SppagebuilderHelperSite::addScript('sppagebuilder.js');

$menus = $app->getMenu();
$menu = $menus->getActive();
$menuClassPrefix = '';
$showPageHeading = 0;

// check active menu item
if ($menu) {
	$menuClassPrefix 	= $menu->getParams()->get('pageclass_sfx');
	$showPageHeading 	= $menu->getParams()->get('show_page_heading');
	$menuheading 		= $menu->getParams()->get('page_heading');
}

require_once JPATH_COMPONENT . '/builder/classes/base.php';
require_once JPATH_COMPONENT . '/builder/classes/config.php';
require_once JPATH_COMPONENT . '/builder/classes/addon.php';


$this->item = ApplicationHelper::preparePageData($this->item);

SpPgaeBuilderBase::loadAddons();
$addons_list = SpAddonsConfig::$addons;

$addons_list = array_map(function ($addon) {
	return AddonsHelper::modernizeAddonStructure($addon);
}, $addons_list);

SpPgaeBuilderBase::loadAssets($addons_list);
$addon_cats = SpPgaeBuilderBase::getAddonCategories($addons_list);
$doc->addScriptdeclaration('var addonsJSON=' . json_encode($addons_list) . ';');
$doc->addScriptdeclaration('var addonsFromDB=' . json_encode(SpAddonsConfig::loadAddonList()) . ';');
$doc->addScriptdeclaration('var addonCats=' . json_encode($addon_cats) . ';');
$doc->addScriptdeclaration('var sppbVersion="' . SppagebuilderHelperSite::getVersion() . '";');

function getSanitizedSrc($image_src)
{
	if (strpos($image_src, "http://") !== false || strpos($image_src, "https://") !== false) {
		$image_src = $image_src;
	} else {
		$original_src = Uri::base(true) . '/' . $image_src;
		$image_src = SppagebuilderHelperSite::cleanPath($original_src);
	}
}

function adjustedMargin($originalData) {
    $marginElems = explode(' ', $originalData);
    $topBottomMargin = "calc({$marginElems[0]} - {$marginElems[2]})";
    $leftRightMargin = "calc({$marginElems[3]} - {$marginElems[1]})";

    return "{$topBottomMargin} {$leftRightMargin}";
}

$popupAttribs = json_decode($this->item->attribs, true);
$responsive_class = '';
$responsive_class .= (isset($popupAttribs['hidden_xl']) && filter_var($popupAttribs['hidden_xl'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xl ' : '';
$responsive_class .= (isset($popupAttribs['hidden_lg']) && filter_var($popupAttribs['hidden_lg'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-lg ' : '';
$responsive_class .= (isset($popupAttribs['hidden_md']) && filter_var($popupAttribs['hidden_md'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-md ' : '';
$responsive_class .= (isset($popupAttribs['hidden_sm']) && filter_var($popupAttribs['hidden_sm'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-sm ' : '';
$responsive_class .= (isset($popupAttribs['hidden_xs']) && filter_var($popupAttribs['hidden_xs'], FILTER_VALIDATE_BOOLEAN)) ? ' sppb-hidden-xs ' : '';

if (!$this->item->text) {
	$doc->addScriptdeclaration('var initialState=[];');
} else {
	$doc->addScriptdeclaration('var initialState=' . json_encode($this->item->text) . ';');
}

?>

<script>
	window.addEventListener("DOMContentLoaded", (event) => {
		function getImageSrc(imageSrc) {
			if (!imageSrc?.src) return imageSrc;
			if (imageSrc.src.includes('http://') || imageSrc.src.includes('https://')) {
				return { ...imageSrc, src: imageSrc?.src };
			} else {
				const baseUrl = window.location.origin;
				const originalSrc = `${baseUrl}/${imageSrc?.src}`;
				const formattedSrc = originalSrc.replace(/\\/g, '/');
				return { ...imageSrc, src: formattedSrc };
			}
		}

		const popupData = <?php echo $this->item->attribs ?>;
		const newCloseElement = document.createElement('div');
		newCloseElement.setAttribute('id', 'sp-pagebuilder-popup-close-btn');
		newCloseElement.setAttribute('class', 'sp-pagebuilder-popup-close-btn sp-pagebuilder-popup-close-btn-hover');
		newCloseElement.setAttribute('role', 'button');

		if (popupData?.close_btn_text && !popupData?.close_btn_is_icon) {
			newCloseElement.style.gap = "5px";
		}

		newCloseElement.innerHTML = `
			<span class="close-btn-text" style="display: inline-block;">${popupData?.close_btn_text || ""}</span>
			<span class="close-btn-icon ${(popupData?.close_btn_icon !== undefined) ? popupData?.close_btn_icon : "fas fa-times"}" style="display: inline-block;" title="<?php echo Text::_('COM_SPPAGEBUILDER_TOP_PANEL_CLOSE') ?>"></span>
		`;

		function onElementLoaded(element) {
					const container = element;

					const getResponsivePosition = (activeDevice = 'xl') => {

						const data = <?php echo $this->item->attribs ?>;

						const containerDivPos = container.getAttribute('data-position') ? JSON.parse(container.getAttribute('data-position')) : null;
						const containerDivWidth = container.getAttribute('data-width') ? JSON.parse(container.getAttribute('data-width')) : null;
						const containerDivMaxWidth = container.getAttribute('data-max_width') ? JSON.parse(container.getAttribute('data-max_width')) : null;
						const containerDivMaxHeight = container.getAttribute('data-max_height') ? JSON.parse(container.getAttribute('data-max_height')) : null;
						const containerDivHeight = container.getAttribute('data-height') ? JSON.parse(container.getAttribute('data-height')) : null;
						const containerBorderRadius = container.getAttribute('data-border_radius') ? JSON.parse(container.getAttribute('data-border_radius')) : null;
						const closeXPosition = container.getAttribute('data-close_position_x') ? JSON.parse(container.getAttribute('data-close_btn_position_x')) : null;
						const closeYPosition = container.getAttribute('data-close_position_y') ? JSON.parse(container.getAttribute('data-close_btn_position_y')) : null;

						if (!data?.position) {
							data.position = { top: { xl: '', lg: '', md: '', sm: '', unit: '%' }, left: { xl: '', lg: '', md: '', sm: '', unit: '%' } };
						}
						if (!data?.width) {
							data.width = { xl: '', lg: '', md: '', sm: '', unit: '%' };
						}
						if (!data?.max_width) {
							data.max_width = { xl: '', lg: '', md: '', sm: '', unit: '%' };
						}
						if (!data?.height) {
							data.height = { xl: '', lg: '', md: '', sm: '', unit: '%' };
						}
						if (!data?.max_height) {
							data.max_height = { xl: '', lg: '', md: '', sm: '', unit: '%' };
						}
						if (!data?.border_radius) {
							data.border_radius = { xl: '', lg: '', md: '', sm: '', unit: '%' };
						}
						if (!data?.close_btn_position_x) {
							data.close_btn_position_x = { xl: '', lg: '', md: '', sm: '', unit: '%' };	
						}
						if (!data?.close_btn_position_y) {
							data.close_btn_position_y = { xl: '', lg: '', md: '', sm: '', unit: '%' };	
						}
						
						
						const respHeight = containerDivHeight === null
							? {
								xl: data?.height?.xl,
								lg: data?.height?.lg || data?.height?.xl,
								md: data?.height?.md || data?.height?.lg || data?.height?.xl,
								sm: data?.height?.sm || data?.height?.md || data?.height?.lg || data?.height?.xl,
								xs: data?.height?.xs || data?.height?.sm || data?.height?.md || data?.height?.lg || data?.height?.xl,
								unit: data?.height?.unit,
							}
							: { ...containerDivHeight };

						const respWidth =
							containerDivWidth === null
								? {
									xl: data?.width?.xl,
									lg: data?.width?.lg || data?.width?.xl,
									md: data?.width?.md || data?.width?.lg || data?.width?.xl,
									sm: data?.width?.sm || data?.width?.md || data?.width?.lg || data?.width?.xl,
									xs: data?.width?.xs || data?.width?.sm || data?.width?.md || data?.width?.lg || data?.width?.xl,
									unit: data?.width?.unit,
								}
								: { ...containerDivWidth };

						const respMaxWidth =
							containerDivMaxWidth === null
								? {
									xl: data?.max_width?.xl,
									lg: data?.max_width?.lg || data?.max_width?.xl,
									md: data?.max_width?.md || data?.max_width?.lg || data?.max_width?.xl,
									sm: data?.max_width?.sm || data?.max_width?.md || data?.max_width?.lg || data?.max_width?.xl,
									xs: data?.max_width?.xs || data?.max_width?.sm || data?.max_width?.md || data?.max_width?.lg || data?.max_width?.xl,
									unit: data?.max_width?.unit,
								}
								: { ...containerDivMaxWidth };

						const respMaxHeight =
							containerDivMaxHeight === null
								? {
									xl: data?.max_height?.xl,
									lg: data?.max_height?.lg || data?.max_height?.xl,
									md: data?.max_height?.md || data?.max_height?.lg || data?.max_height?.xl,
									sm: data?.max_height?.sm || data?.max_height?.md || data?.max_height?.lg || data?.max_height?.xl,
									xs: data?.max_height?.xs || data?.max_height?.sm || data?.max_height?.md || data?.max_height?.lg || data?.max_height?.xl,
									unit: data?.max_height?.unit,
								}
								: { ...containerDivMaxHeight };

						const respBorder =
							containerBorderRadius === null
								? {
									xl: data?.border_radius?.xl,
									lg: data?.border_radius?.lg || data?.border_radius?.xl,
									md: data?.border_radius?.md || data?.border_radius?.lg || data?.border_radius?.xl,
									sm:
									data?.border_radius?.sm || data?.border_radius?.md || data?.border_radius?.lg || data?.border_radius?.xl,
									xs:
									data?.border_radius?.xs ||
									data?.border_radius?.sm ||
									data?.border_radius?.md ||
									data?.border_radius?.lg ||
									data?.border_radius?.xl,
									unit: data?.border_radius?.unit,
								}
								: { ...containerBorderRadius };

						
						const closeBtnPositionX = closeXPosition === null ? {
							xl: data?.close_btn_position_x?.xl,
							lg: data?.close_btn_position_x?.lg || data?.close_btn_position_x?.xl,
							md: data?.close_btn_position_x?.md || data?.close_btn_position_x?.lg || data?.close_btn_position_x?.xl,
							sm:
							data?.close_btn_position_x?.sm ||
							data?.close_btn_position_x?.md ||
							data?.close_btn_position_x?.lg ||
							data?.close_btn_position_x?.xl,
							xs:
							data?.close_btn_position_x?.xs ||
							data?.close_btn_position_x?.sm ||
							data?.close_btn_position_x?.md ||
							data?.close_btn_position_x?.lg ||
							data?.close_btn_position_x?.xl,
							unit: data?.close_btn_position_x?.unit,
						} : { ...closeXPosition };
						const closeBtnPositionY = closeYPosition === null ? {
							xl: data?.close_btn_position_y?.xl,
							lg: data?.close_btn_position_y?.lg || data?.close_btn_position_y?.xl,
							md: data?.close_btn_position_y?.md || data?.close_btn_position_y?.lg || data?.close_btn_position_y?.xl,
							sm:
							data?.close_btn_position_y?.sm ||
							data?.close_btn_position_y?.md ||
							data?.close_btn_position_y?.lg ||
							data?.close_btn_position_y?.xl,
							xs:
							data?.close_btn_position_y?.xs ||
							data?.close_btn_position_y?.sm ||
							data?.close_btn_position_y?.md ||
							data?.close_btn_position_y?.lg ||
							data?.close_btn_position_y?.xl,
							unit: data?.close_btn_position_y?.unit,
						} : { ...closeYPosition };

						const selectedTopPosition = containerDivPos === null ? data?.position?.top : containerDivPos?.top;
						const selectedLeftPosition = containerDivPos === null ? data?.position?.left : containerDivPos?.left;

						const topPosition = {
							xl: selectedTopPosition?.xl,
							lg: selectedTopPosition?.lg || selectedTopPosition?.xl,
							md: selectedTopPosition?.md || selectedTopPosition?.lg || selectedTopPosition?.xl,
							sm: selectedTopPosition?.sm || selectedTopPosition?.md || selectedTopPosition?.lg || selectedTopPosition?.xl,
							xs: selectedTopPosition?.xs || selectedTopPosition?.sm || selectedTopPosition?.md || selectedTopPosition?.lg || selectedTopPosition?.xl,
							unit: selectedTopPosition?.unit,
						};
						const leftPosition = {
							xl: selectedLeftPosition?.xl,
							lg: selectedLeftPosition?.lg || selectedLeftPosition?.xl,
							md: selectedLeftPosition?.md || selectedLeftPosition?.lg || selectedLeftPosition?.xl,
							sm: selectedLeftPosition?.sm || selectedLeftPosition?.md || selectedLeftPosition?.lg || selectedLeftPosition?.xl,
							xs: selectedLeftPosition?.xs || selectedLeftPosition?.sm || selectedLeftPosition?.md || selectedLeftPosition?.lg || selectedLeftPosition?.xl,
							unit: selectedLeftPosition?.unit,
						};

						const windowDocument = document.querySelector('.sp-pagebuilder-popup') || window.iWindow?.document.querySelector('.sp-pagebuilder-popup');

						const windowHeight = windowDocument?.clientHeight;
						const containerHeight = container?.clientHeight;
						const windowWidth = windowDocument?.clientWidth;
						const containerWidth = container?.clientWidth;

						container.style['width'] = respWidth === null || respWidth[activeDevice] === undefined
												? '60%'
												: respWidth[activeDevice]
												? `${respWidth[activeDevice]}${respWidth?.unit}`
												: '60%';

						container.style['max-width'] = respMaxWidth === null || respMaxWidth[activeDevice] === undefined
												? 'initial'
												: respMaxWidth[activeDevice]
												? `${respMaxWidth[activeDevice]}${respMaxWidth?.unit}`
												: 'initial';
						container.style['height'] = respHeight === null || respHeight[activeDevice] === undefined
												? 'auto'
												: respHeight[activeDevice]
												? `${respHeight[activeDevice]}${respHeight?.unit !== '%' ? respHeight?.unit : 'vh'}`
												: 'auto';
						container.style['max-height'] = respMaxHeight === null || respMaxHeight[activeDevice] === undefined
												? 'unset'
												: respMaxHeight[activeDevice]
												? `${respMaxHeight[activeDevice]}${respMaxHeight?.unit !== '%' ? respMaxHeight?.unit : 'vh'}`
												: 'unset';

						container.style['border-radius'] = respBorder === null ? 0 : respBorder[activeDevice] === '' ? 0 : respBorder[activeDevice] + respBorder?.unit;

						if (topPosition?.unit !== '%') {
							container.style['top'] = topPosition[activeDevice] + topPosition?.unit;
						} else if (topPosition?.unit === '%') {
							if (topPosition[activeDevice] !== '') {
							if (topPosition[activeDevice] != 50) {
								container.style['top'] = `calc(${topPosition[activeDevice]}${topPosition?.unit} - ${
								(topPosition[activeDevice] * containerHeight) / 100
								}px)`;
							}
							}
						}

						if (leftPosition?.unit !== '%') {
							container.style['left'] = leftPosition[activeDevice] + leftPosition?.unit;
						} else if (leftPosition?.unit === '%') {
							if (leftPosition[activeDevice] !== '') {
							if (leftPosition[activeDevice] != 50) {
								container.style['left'] = `calc(${leftPosition[activeDevice]}${leftPosition?.unit} - ${
								(leftPosition[activeDevice] * containerWidth) / 100
								}px)`;
							}
							}
						}

						if (
							(topPosition[activeDevice] === '' || topPosition[activeDevice] == 50) &&
							topPosition?.unit === '%'
						) {
							const isTop = windowHeight - containerHeight <= 0 ? '0' : null;
							container.style['top'] = isTop ? isTop : `calc(50% - ${containerHeight / 2}px)`;
						}
						if (
							(leftPosition[activeDevice] === '' || leftPosition[activeDevice] == 50) &&
							leftPosition?.unit === '%'
						) {
							const isLeft = windowWidth - containerWidth <= 0 ? '0' : null;
							container.style['left'] = isLeft ? isLeft : `calc(50% - ${containerWidth / 2}px)`;
						}
						if (topPosition[activeDevice] == 100 && topPosition?.unit === '%') {
							container.style['top'] = `calc(100% - ${containerHeight}px)`;
						}
						if (leftPosition[activeDevice] == 100 && leftPosition?.unit === '%') {
							container.style['left'] = `calc(100% - ${containerWidth}px)`;
						}
					}


					const mediaLG = window.matchMedia('(max-width: 1197.98px)');
					const mediaMD = window.matchMedia('(max-width: 989.98px)');
					const mediaSM = window.matchMedia('(max-width: 765.98px)');
					const mediaXS = window.matchMedia('(max-width: 573.98px)');

					function handleTabletChange() {
							if (mediaXS.matches) {
								getResponsivePosition("xs");
							} else if (mediaSM.matches) {
							 	getResponsivePosition("sm");
							} else if (mediaMD.matches) {
							 	getResponsivePosition("md");
							} else if (mediaLG.matches) {
							 	getResponsivePosition("lg");
							} else {
								getResponsivePosition("xl");
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

		const elementSelector = '.builder-container';

		const observerOptions = {
			childList: true,
			subtree: true
		};

		const observerCallback = (mutationsList, observer) => {
		for (let mutation of mutationsList) {
			if (mutation.type === 'childList') {
			const element = document.querySelector(elementSelector);
			if (element) {
				onElementLoaded(element);
				window.onresize = () => onElementLoaded(element);
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
		}

		window.addEventListener('beforeunload', function() {
			observer.disconnect();
			window.onresize = null;
		});

		setTimeout(() => {
			const builder = document.querySelector(".sp-pagebuilder-container-popup");
			builder?.children[0]?.insertBefore(newCloseElement, builder?.children[0]?.children[0]);
		}, 500);
	});
</script>

<div class="<?php echo $responsive_class; ?>" id="sp-pagebuilder-overlay" data-isoverlay=<?php echo !empty(json_decode($this->item->attribs, true)['overlay']) ? 'true' : 'false' ?> style="position: fixed; inset: 0; z-index: 1;"></div>
<div id="sp-page-builder" class="sp-pagebuilder <?php echo $menuClassPrefix; ?> page-<?php echo $this->item->id; ?> sp-pagebuilder-popup <?php echo $responsive_class; ?>" data-isbg=<?php echo !empty(json_decode($this->item->attribs, true)['background_type']) ? 'true' : 'false' ?> x-data="easystoreProductList">
	<div id="sp-pagebuilder-container" class="sp-pagebuilder-container-popup" x-data="easystoreProductDetails">
		<div class="sp-pagebuilder-loading-wrapper">
			<div class="sp-pagebuilder-loading">
				<svg width="28" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M23.028 17.741c.855-.89 2.358-.856 3.219 0 .88.88.85 2.33 0 3.219-.89.929-1.79 1.848-2.743 2.719-5.846 5.35-13.194 8.56-21.204 8.292-1.235-.039-2.276-1.007-2.276-2.276 0-1.202 1.045-2.315 2.276-2.276 2.524.082 4.426-.083 6.765-.677a25.837 25.837 0 0 0 2.694-.846c.222-.083.443-.17.662-.262l.292-.132c.379-.174.758-.355 1.133-.544a29.604 29.604 0 0 0 4.192-2.612c.097-.072.19-.146.287-.213-.015.01-.31.242-.112.087.136-.107.273-.214.408-.325.264-.21.52-.429.774-.648.491-.424.967-.855 1.44-1.303a59.718 59.718 0 0 0 2.193-2.203Zm-12.85-1.124c.732-.39 1.431-.837 2.092-1.336a.424.424 0 0 0-.01-.681l-5.228-3.77a.424.424 0 0 0-.67.345l.018 6.288a.423.423 0 0 0 .531.409 14.164 14.164 0 0 0 3.268-1.255Z" fill="#2684FF" />
					<path d="M19.959 12.932 5.476 1.853C4.8 1.337 4.324.973 3.647.458L.35 1.124c-.686 1.172-.175 2.362.808 3.111 4.824 3.69 9.653 7.388 14.482 11.078.676.516 1.352 1.032 2.024 1.547.977.749 2.548.15 3.111-.817.687-1.172.166-2.364-.816-3.112Z" fill="url(#a)" />
					<path d="M9.226 23.655c1.42-.326 2.82-.934 4.12-1.571 2.703-1.318 5.208-3.214 7.075-5.579.389-.49.666-.952.666-1.609 0-.56-.248-1.225-.666-1.61-.822-.753-2.432-.997-3.219 0a16.981 16.981 0 0 1-2.407 2.495c-.15.127-.307.254-.458.38-.374.306.355-.258 0 0a22.162 22.162 0 0 1-3.282 1.993c-.249.123-.496.234-.744.346-.16.072-.536.184.17-.068-.122.043-.239.097-.355.146-.36.141-.725.277-1.095.398-.33.107-.665.215-1.007.292-1.161.263-1.954 1.668-1.59 2.802.39 1.21 1.547 1.867 2.792 1.585ZM3.716.505A2.243 2.243 0 0 0 2.306 0C1.07 0 .03 1.04.03 2.276v27.35c0 1.231 1.044 2.277 2.275 2.277 1.236 0 2.276-1.04 2.276-2.277V1.167L3.715.505h.001Z" fill="#2684FF" />
					<defs>
						<linearGradient id="a" x1="4.57476" y1="5.7046" x2="15.8692" y2="14.5689" gradientUnits="userSpaceOnUse">
							<stop stop-color="#0052CC" />
							<stop offset="1" stop-color="#2684FF" />
						</linearGradient>
					</defs>
				</svg>
			</div>
		</div>
	</div>
</div>

<style id="sp-pagebuilder-css" type="text/css">
	<?php echo $this->item->css; ?>
</style>

<style type="text/css">
	<?php 
		$popupAttribs = json_decode($this->item->attribs, true);
		if (!empty($popupAttribs['custom_css']))
		{
			echo ' ' . $popupAttribs['custom_css'] . ' ';
		}
	?>
</style>

<style type="text/css">
	.sp-pagebuilder-popup .builder-container {
		position: absolute;
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
			else if (!empty($popupAttribs['overlay']) && !empty($popupAttribs['overlay_background_type']) && !empty($popupAttribs['overlay_background_type']) && $popupAttribs['overlay_background_type'] === 'gradient')
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

	#sp-pagebuilder-popup-close-btn {
		display: flex;
		justify-content: center;
		align-items: center;
	}

	.sp-pagebuilder-popup-close-btn-hover:hover {
		color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_color_hover']) ? json_decode($this->item->attribs, true)['close_btn_color_hover'] : 'initial' ?> !important;
		background-color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_bg_color_hover']) ? json_decode($this->item->attribs, true)['close_btn_bg_color_hover'] : 'initial' ?> !important;
	}

	<?php 
	if (true) : ?>
		#sp-pagebuilder-popup-close-btn {
			color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_color']) ? json_decode($this->item->attribs, true)['close_btn_color'] : 'initial' ?>;

			border-width: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border']) ? json_decode($this->item->attribs, true)['close_btn_border']['border_width'] : 'initial' ?>;
			border-style: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border']) ? json_decode($this->item->attribs, true)['close_btn_border']['border_style'] : 'initial' ?>;
			border-color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border']) ? json_decode($this->item->attribs, true)['close_btn_border']['border_color'] : 'initial' ?>;

			border-radius: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_border_radius']) ? (json_decode($this->item->attribs, true)['close_btn_border_radius'] . 'px') : '0px' ?>;
		}
		#sp-pagebuilder-popup-close-btn {
			background-color: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_bg_color']) ? json_decode($this->item->attribs, true)['close_btn_bg_color'] : 'initial' ?>;
		}
		#sp-pagebuilder-popup-close-btn {
			padding: <?php echo !empty(json_decode($this->item->attribs, true)['close_btn_padding']) ? json_decode($this->item->attribs, true)['close_btn_padding'] : 'initial' ?>;
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

	$border_radius_xl = !empty($popupAttribs['border_radius']['xl']) ? $popupAttribs['border_radius']['xl'] . $popupAttribs['border_radius']['unit'] : '0';
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
</style>

<?php
$doc->addScriptDeclaration('jQuery(document).ready(function($) {
	$(document).on("click", "a", function(e){
		e.preventDefault();
	});

	$(document).on("focus", ".sp-editable-content, .sp-inline-editable-element", function(e){
		e.preventDefault();

		const strippedText = e.target?.getAttribute("data-stripped-text") || "";
		const maxWords = Number(e.target?.getAttribute("data-max-words")) || strippedText?.length;

		if (!maxWords || maxWords === 0) return;

		const addonName = e.target?.getAttribute("data-addon") || null;

		if (addonName !== "text-block") return;
		
		const isTruncated = e.target?.getAttribute("data-is-truncated") || "false";
		const fullText = e.target?.getAttribute("data-full-text") || "";

		const isShowBtn = e.target?.querySelector(".sppb-btn-container");
		
		if (isTruncated === "false") return;

		if (!isShowBtn) return;

		if (fullText === e.target.innerHTML) return;
		
		if (isTruncated === "true") {
			const fullText = e.target?.getAttribute("data-full-text") || "";
			e.target.innerHTML = fullText;
		}
	});

	$(document).on("blur", ".sp-editable-content, .sp-inline-editable-element", function(e){
		e.preventDefault();

		const addonName = e.target?.getAttribute("data-addon") || null;

		if (addonName !== "text-block") return;
		
		const isTruncated = e.target?.getAttribute("data-is-truncated") || "false";
		const isShowBtn = e.target?.querySelector(".sppb-btn-container");
		
		if (isTruncated === "false") return;

		if (isShowBtn) return;
		
		if (isTruncated === "true") {
			const strippedText = e.target?.getAttribute("data-stripped-text") || "";
			const maxWords = Number(e.target?.getAttribute("data-max-words")) || strippedText?.length;
			const truncatedText = strippedText.split(" ").slice(0, maxWords).join(" ");
			const actionText = e.target?.getAttribute("data-action-text") || "";

			if (!maxWords || maxWords === 0) return;

			if (maxWords >= strippedText.split(" ").length) return;

			e.target.innerHTML = `
			${truncatedText}
			<div class="sppb-btn-container sppb-content-truncation-show"><div role="button" class="sppb-btn-show-more">${actionText}</div></div>
			`
		}
	});

	$(document).on("click", ".sp-editable-content, .sp-inline-editable-element", function(e){
		e.preventDefault();
		var ids = jQuery(this).attr("id");
		jQuery(this).attr("contenteditable", true);
		jQuery(this).focus();
	});
});');
