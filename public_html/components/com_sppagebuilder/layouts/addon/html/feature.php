<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

$icon_image_position = $displayData['icon_image_position'];
$addon_id = $displayData['addon_id'];
$media = $displayData['media'];
$title = $displayData['title'];
$feature_title = $displayData['feature_title'];
$visually_hidden_text = $displayData['visually_hidden_text'];
$second_visually_hidden_text = $displayData['second_visually_hidden_text'];
$attribs = $displayData['attribs'];
$second_attribs = $displayData['second_attribs'];
$btn_class = $displayData['btn_class'];
$second_btn_class = $displayData['second_btn_class'];
$btn_text = $displayData['btn_text'];
$second_btn_text = $displayData['second_btn_text'];
$feature_text = $displayData['feature_text'];
$is_second_button = $displayData['is_second_button'];
$class = $displayData['class'];
$settings = $displayData['settings'];
$imageProps = $displayData['imageProps'];
$imageUnits = $displayData['imageUnits'];

$cssHelper = new CSSHelper($addon_id);

$output = '';

if (!is_string($icon_image_position)) {
    $icon_image_position_lg = (isset($icon_image_position) && $icon_image_position->xl) ? $icon_image_position->xl : 'after';
    $icon_image_position_md = (isset($icon_image_position->lg) && $icon_image_position->lg) ? $icon_image_position->lg : null;
    $icon_image_position_sm = (isset($icon_image_position->md) && $icon_image_position->md) ? $icon_image_position->md : null;
    $icon_image_position_xs = (isset($icon_image_position->sm) && $icon_image_position->sm) ? $icon_image_position->sm : null;
    $icon_image_position_nano = (isset($icon_image_position->xs) && $icon_image_position->xs) ? $icon_image_position->xs : null;

    $output .= '<style>';
    $output .= $cssHelper->generate_layout_position_css($icon_image_position_lg, $addon_id, $settings, $imageProps, $imageUnits);
    $output .= '@media (max-width: 1200px) {';
    $output .= $cssHelper->generate_layout_position_css($icon_image_position_md, $addon_id, $settings, $imageProps, $imageUnits);
    $output .= ' } ';
    $output .= '@media (max-width: 992px) {';
    $output .= $cssHelper->generate_layout_position_css($icon_image_position_sm, $addon_id, $settings, $imageProps, $imageUnits);
    $output .= ' } ';
    $output .= '@media (max-width: 768px) {';
    $output .= $cssHelper->generate_layout_position_css($icon_image_position_xs, $addon_id, $settings, $imageProps, $imageUnits);
    $output .= ' } ';
    $output .= '@media (max-width: 575px) {';
    $output .= $cssHelper->generate_layout_position_css($icon_image_position_nano, $addon_id, $settings, $imageProps, $imageUnits);
    $output .= ' } ';
    $output .= '</style>';
}
if ((!is_string($icon_image_position) && $cssHelper->objectHasString($icon_image_position, 'after')) || $icon_image_position === 'after') {
    $output .= '<div class="sppb-addon-content-align-before sppb-addon sppb-addon-feature ' . $class . '">';
    $output .= '<div class="sppb-addon-content">';
    $output .= ($media) ? $media : '';
    $output .= '<div class="sppb-media-content">';
    $output .= ($title) ? $feature_title : '';
    $output .= $feature_text;

    if ($btn_text) {
        $output .= '<a ' . $attribs . ' class="sppb-btn ' . $btn_class . '">' . $btn_text . $visually_hidden_text . '</a>';
    }
    if ($is_second_button && $second_btn_text) {
        $output .= '<a ' . $second_attribs . ' class="sppb-btn sppb-btn-2 ' . $second_btn_class . '">' . $second_btn_text . $second_visually_hidden_text . '</a>';
    }

    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
} 
if ((!is_string($icon_image_position) && $cssHelper->objectHasString($icon_image_position, 'before')) || $icon_image_position === 'before') {
    $output .= '<div class="sppb-addon-content-align-after sppb-addon sppb-addon-feature ' . $class . '">';
    $output .= '<div class="sppb-addon-content">';
    $output .= ($title) ? $feature_title : '';
    $output .= ($media) ? $media : '';
    $output .= '<div class="sppb-media-content">';
    $output .= $feature_text;

    if ($btn_text) {
        $output .= '<a ' . $attribs . ' class="sppb-btn ' . $btn_class . '">' . $btn_text . $visually_hidden_text . '</a>';
    }
    if ($is_second_button && $second_btn_text) {
        $output .= '<a ' . $second_attribs . ' class="sppb-btn sppb-btn-2 ' . $second_btn_class . '">' . $second_btn_text . $second_visually_hidden_text . '</a>';
    }

    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
} 
if ((!is_string($icon_image_position) && $cssHelper->objectHasString($icon_image_position, 'left')) || $icon_image_position === 'left') {
    $output .= '<div class="sppb-addon-content-align-left sppb-addon sppb-addon-feature sppb-text-left ' . $class . '">';
    $output .= '<div class="sppb-addon-content">';
    $output .= '<div class="sppb-media">';
    $output .= '<div class="pull-left">';
    $output .= $media ?? '';
    $output .= '</div>';
    $output .= '<div class="sppb-media-body">';
    $output .= '<div class="sppb-media-content">';
    $output .= ($title) ? $feature_title : '';
    $output .= $feature_text;

    if ($btn_text) {
        $output .= '<a ' . $attribs . ' class="sppb-btn ' . $btn_class . '">' . $btn_text . $visually_hidden_text . '</a>';
    }
    if ($is_second_button && $second_btn_text) {
        $output .= '<a ' . $second_attribs . ' class="sppb-btn sppb-btn-2 ' . $second_btn_class . '">' . $second_btn_text . $second_visually_hidden_text . '</a>';
    }

    $output .= '</div>'; //.sppb-media-content
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
} 
if ((!is_string($icon_image_position) && $cssHelper->objectHasString($icon_image_position, 'right')) || $icon_image_position === 'right') {
    $output .= '<div class="sppb-addon-content-align-right sppb-addon sppb-addon-feature sppb-text-right ' . $class . '">';
    $output .= '<div class="sppb-addon-content">';
    $output .= '<div class="sppb-media">';
    $output .= '<div class="pull-right">';
    $output .= $media ?? '';
    $output .= '</div>';
    $output .= '<div class="sppb-media-body">';
    $output .= '<div class="sppb-media-content">';
    $output .= ($title) ? $feature_title : '';
    $output .= $feature_text;

    if ($btn_text) {
        $output .= '<a ' . $attribs . ' class="sppb-btn ' . $btn_class . '">' . $btn_text . $visually_hidden_text . '</a>';
    }
    if ($is_second_button && $second_btn_text) {
        $output .= '<a ' . $second_attribs . ' class="sppb-btn sppb-btn-2 ' . $second_btn_class . '">' . $second_btn_text . $second_visually_hidden_text . '</a>';
    }

    $output .= '</div>'; //.sppb-media-content
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
}

echo $output;