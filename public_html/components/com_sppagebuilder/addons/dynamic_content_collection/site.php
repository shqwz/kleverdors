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
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionRenderer;

class SppagebuilderAddonDynamic_content_collection extends SppagebuilderAddons
{
    protected static $cssContent = [];

    public function render()
    {
        $addon = $this->addon;
        
        if(empty($addon->settings->source)) {
            return '';
        }

        $renderer = new CollectionRenderer($addon);
        if ($addon->settings->source === CollectionIds::ARTICLES_COLLECTION_ID || $addon->settings->source === CollectionIds::TAGS_COLLECTION_ID) {
            $output = $renderer->renderArticles();
            $pagination = $renderer->renderArticlesPagination();
        } else {
            $output = $renderer->render();
            $pagination = $renderer->renderPagination();
        }
        
        $css = $renderer->generateCSS();

        return $output . $pagination . $css;
    }

    /**
     * Generate the CSS for the collection addon
     *
     * @return string The generated CSS
     * 
     * @since 5.5.0
     */
    public function css()
    {
        $settings = $this->addon->settings;
        $addon_id = '#sppb-addon-' . $this->addon->id;
        $idSelector = '#sppb-dynamic-content-' . $this->addon->id;
        $cssHelper = new CSSHelper($addon_id);
        $css = '';

        $display = $settings->display ?? 'grid';
        $flexDirection = $settings->flex_direction ?? 'row';

        $props = [
            'display' => 'display',
            'column_gap' => 'column-gap',
            'row_gap' => 'row-gap'
        ];

        $units = [
            'display' => false,
            'column_gap' => 'px',
            'row_gap' => 'px'
        ];

        if ($display === 'grid') {
            $props['grid_columns'] = 'grid-template-columns: repeat(%s, 1fr)';
            $units['grid_columns'] = false;
        }

        if ($display === 'flex') {
            $props['flex_direction'] = 'flex-direction';
            $props['flex_wrap'] = 'flex-wrap';

            if ($flexDirection === 'row') {
                $props['vertical_alignment'] = 'align-items';
                $props['horizontal_alignment'] = 'justify-content';
            } else {
                $props['vertical_alignment'] = 'justify-content';
                $props['horizontal_alignment'] = 'align-items';
            }

            $units['flex_direction'] = false;
            $units['flex_wrap'] = false;
            $units['vertical_alignment'] = false;
            $units['horizontal_alignment'] = false;
        }

        $css .= $cssHelper->generateStyle(
            ':no-parent' . $idSelector . '.sppb-dynamic-content-collection',
            $settings,
            $props,
            $units
        );

        $settings->item_box_shadow = CSSHelper::parseBoxShadow($settings, 'item_box_shadow');
        $settings->item_transition = 'all 300ms ease-in-out';

        $itemBorderStyle = $cssHelper->border(':no-parent' . $idSelector . ' > .sppb-dynamic-content-collection__item', $settings, 'item_border');
        $itemStyle = $cssHelper->generateStyle(':no-parent' . $idSelector . ' > .sppb-dynamic-content-collection__item', $settings, [
            'item_padding' => 'padding',
            'item_background' => 'background-color',
            'item_border_radius' => 'border-radius',
            'item_box_shadow' => 'box-shadow',
            'item_transition' => 'transition',
            'item_width' => 'width',
        ], [
            'item_padding' => false,
            'item_background' => false,
            'item_box_shadow' => false,
            'item_transition' => false,
            'item_width' => false
        ]);

        $settings->item_box_shadow_hover = CSSHelper::parseBoxShadow($settings, 'item_box_shadow_hover');
        $itemHoverStyle = $cssHelper->generateStyle(':no-parent' . $idSelector . ' > .sppb-dynamic-content-collection__item:hover', $settings, [
            'item_padding_hover'       => 'padding',
            'item_background_hover'    => 'background-color',
            'item_border_radius_hover' => 'border-radius',
            'item_box_shadow_hover'    => 'box-shadow',
        ], [
            'item_padding_hover' => false,
            'item_background_hover' => false,
            'item_box_shadow_hover' => false
        ]);

        $paginationCss = $cssHelper->generateStyle('.sppb-dynamic-content-collection__pagination', $settings, [
            'pagination_buttons_position' => 'justify-content',
            'pagination_padding' => 'padding',
            'pagination_margin' => 'margin',
        ], [
            'pagination_buttons_position' => false,
            'pagination_padding' => false,
            'pagination_margin' => false,
        ]);


        $css .= $itemStyle;
        $css .= $itemHoverStyle;
        $css .= $itemBorderStyle;
        $css .= $paginationCss;

        return $css;
    }

    public static function getTemplate() {
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output  = '<style type="text/css">';

		$output .= $lodash->generateTransformCss('', 'data.transform');

		$output .= '</style>';

		return $output;
	}
}
