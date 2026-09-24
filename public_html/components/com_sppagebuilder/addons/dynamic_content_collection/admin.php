<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

SpAddonsConfig::addonConfig([
    'type'       => 'dynamic-content',
    'addon_name' => 'dynamic_content_collection',
    'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_LIST'),
    'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_LIST_DESC'),
    'category'   => Text::_('COM_SPPAGEBUILDER_ADDON_GROUP_DYNAMIC_CONTENT'),
    'allowed_addons' => ['collection_image', 'collection_text'],
    'icon'       => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.467 6.91c0-.083.059-.309.523-.638.445-.315 1.136-.63 2.056-.906 1.831-.549 4.404-.9 7.278-.9 2.874 0 5.447.351 7.278.9.92.276 1.612.59 2.056.906.464.329.524.555.524.639 0 .083-.06.31-.524.638-.444.316-1.136.63-2.056.906-1.83.55-4.404.9-7.278.9-2.874 0-5.447-.35-7.278-.9-.92-.276-1.611-.59-2.056-.906-.464-.329-.523-.555-.523-.638ZM16.324 3c-2.975 0-5.697.36-7.7.962-.996.298-1.856.669-2.483 1.114C5.533 5.506 5 6.116 5 6.91c0 .033 0 .066.003.099A.74.74 0 0 0 5 7.074h.008H5v16.567c0 .816.477 1.488 1.1 2 .628.516 1.492.948 2.495 1.296 2.012.7 4.745 1.12 7.73 1.12 2.983 0 5.716-.42 7.729-1.12 1.003-.349 1.867-.78 2.494-1.296.623-.512 1.1-1.184 1.1-2V7.075h-.007v-.001h.007a.73.73 0 0 0-.003-.065c.002-.032.003-.065.003-.098 0-.795-.533-1.404-1.141-1.835-.627-.445-1.487-.816-2.483-1.114C22.022 3.36 19.299 3 16.324 3Zm9.858 5.957c-.586.352-1.324.653-2.158.903-2.002.6-4.725.961-7.7.961-2.975 0-5.697-.36-7.7-.961-.833-.25-1.571-.55-2.157-.903v6.771c0 .084.062.31.523.636.445.316 1.136.63 2.056.906 1.831.55 4.404.9 7.278.9 2.874 0 5.447-.35 7.278-.9.92-.276 1.612-.59 2.056-.906.464-.329.524-.555.524-.638V8.957ZM6.467 23.641v-5.868c.586.352 1.324.652 2.158.902 2.002.6 4.724.961 7.7.961 2.974 0 5.697-.36 7.699-.961.834-.25 1.572-.55 2.158-.902v5.868c0 .2-.116.497-.565.867-.444.365-1.131.726-2.045 1.044-1.82.633-4.383 1.037-7.248 1.037s-5.427-.404-7.248-1.037c-.914-.318-1.6-.68-2.045-1.044-.449-.37-.564-.666-.564-.867Z" fill="currentColor"/></svg>',
    'settings'   => [
        'content' => [
            'title' => 'Content',
            'fields' => [
                'source' => [
                    'type'   => 'dynamic_source',
                    'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SOURCE_TITLE'),
                    'desc'   => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SOURCE_DESC'),
                ],
                'divider_1' => [
                    'type' => 'separator',
                ],
                'sorting_field_id' => [
                    'type'   => 'dynamic_field',
                    'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FIELD_SOURCE_TITLE'),
                    'desc'   => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FIELD_SOURCE_DESC'),
                    'excluded_types' => ['image', 'file', 'reference', 'multi-reference', 'gallery', 'switch', 'option', 'video'],
                    'augmentations' => [['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SORTING_DEFAULT'), 'value' => 'default']],
                    'is_clearable' => false,
                    'std' => 'default'
                ],
                'direction' => [
                    'type' => 'buttons',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SORTING_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SORTING_DESCRIPTION'),
                    'values' => [
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SORTING_ASC'), 'value' => 'asc'],
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SORTING_DESC'), 'value' => 'desc'],
                    ],
                    'std' => 'asc',
                    'is_clearable' => false,
                ],
                'divider_2' => [
                    'type' => 'separator',
                ],
                'filters' => [
                    'type' => 'filter',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DESC'),
                    'std' => '',
                ],
                'limit' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_LIMIT_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_LIMIT_DESC'),
                    'std' => '10',
                ],
                'no_records_message' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS_MESSAGE_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS_MESSAGE_DESCRIPTION'),
                ],
                'no_records_description' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS_DESCRIPTION_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_NO_RECORDS_DESCRIPTION_DESCRIPTION'),
                ],
            ]
        ],

        'pagination' => [
            'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TITLE'),
            'fields' => [
                'pagination' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TITLE'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_DESC'),
                    'std'     => 0,
                    'is_header' => 1
                ],
                'pagination_type' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_TITLE'),
                    'values' => [
                        'load-more' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_LOAD_MORE'),
                        'infinite-scroll' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_TYPE_INFINITE_SCROLL'),
                    ],
                    'std' => 'load-more',
                    'depends' => [['pagination', '=', 1]],
                ],
                'pagination_load_more_button_text' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_LOAD_MORE_BUTTON_TEXT'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_LOAD_MORE_BUTTON_TEXT_DESC'),
                    'std' => 'Load More',
                    'depends' => [['pagination', '=', 1], ['pagination_type', '=', 'load-more']],
                ],
                'pagination_buttons_position' => [
                    'type' => 'buttons',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_BUTTONS_POSITION_TITLE'),
                    'values' => [
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_BUTTONS_POSITION_LEFT'), 'value' => 'start'],
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_BUTTONS_POSITION_CENTER'), 'value' => 'center'],
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_PAGINATION_BUTTONS_POSITION_RIGHT'), 'value' => 'end'],
                    ],
                    'responsive' => true,
                    'std' => 'start',
                    'depends' => [['pagination', '=', 1], ['pagination_type', '=', 'load-more']],
                ],
                'pagination_padding' => [
                    'type' => 'padding',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    'responsive' => true,
                    'depends' => [['pagination', '=', 1], ['pagination_type', '=', 'load-more']],
                ],
                'pagination_margin' => [
                    'type' => 'margin',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                    'responsive' => true,
                    'depends' => [['pagination', '=', 1], ['pagination_type', '=', 'load-more']],
                ],
                'pagination_load_more_button_type' => [
                    'type'   => 'select',
                    'title'  => Text::_('COM_SPPAGEBUILDER_PAGINATION_LOAD_MORE_BUTTON_TYPE'),
                    'values' => [
                        'default'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_DEFAULT'),
                        'primary'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_PRIMARY'),
                        'secondary' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SECONDARY'),
                        'success'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_SUCCESS'),
                        'info'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_INFO'),
                        'warning'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_WARNING'),
                        'danger'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_DANGER'),
                        'dark'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_DARK'),
                        'link'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                    ],
                    'std'    => 'default',
                    'depends' => [['pagination', '=', 1], ['pagination_type', '=', 'load-more']],
                ],
            ]
        ],

        'layout' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_LAYOUT_TITLE'),
            'fields' => [
                'display' => [
                    'type' => 'buttons',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_DISPLAY_TITLE'),
                    'values' => [
                        [ 'label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_DISPLAY_GRID'), 'value' => 'grid' ],
                        [ 'label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_DISPLAY_STACK'), 'value' => 'flex' ],
                    ],
                    'std' => 'grid',
                ],
                'grid_columns' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_GRID_COLUMNS_TITLE'),
                    'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_GRID_COLUMNS_DESC'),
                    'min'        => 1,
                    'max'        => 6,
                    'responsive' => true,
                    'std'        => ['xl' => '3'],
                    'depends'    => [['display', '=', 'grid']],
                ],
                'flex_direction' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_DIRECTION_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_DIRECTION_DESC'),
                    'values' => [
                        'row' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_DIRECTION_ROW'),
                        'column' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_DIRECTION_COLUMN'),
                    ],
                    'std' => 'column',
                    'depends'    => [['display', '=', 'flex']],
                ],
                'flex_wrap' => [
                    'type' => 'buttons',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_WRAP_TITLE'),
                    'values' => [
                        [ 'label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_WRAP_YES'), 'value' => 'wrap' ],
                        [ 'label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_WRAP_NO'), 'value' => 'nowrap' ],
                        
                    ],
                    'std' => 'wrap',
                    'depends'    => [['display', '=', 'flex']],
                ],
                'vertical_alignment' => [
                    'type' => 'buttons',
                    'title' => 'Vertical Alignment',
                    'values' => [
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_VERTICAL_ALIGNMENT_START'), 'value' => 'start'],
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_VERTICAL_ALIGNMENT_CENTER'), 'value' => 'center'],
                        ['label' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_VERTICAL_ALIGNMENT_END'), 'value' => 'end'],
                    ],
                    'std' => 'start',
                    'depends' => [['display', '=', 'flex']],
                ],
                'horizontal_alignment' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_HORIZONTAL_ALIGNMENT_TITLE'),
                    'values' => [
                        'start' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_HORIZONTAL_ALIGNMENT_START'),
                        'center' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_HORIZONTAL_ALIGNMENT_CENTER'),
                        'end' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_HORIZONTAL_ALIGNMENT_END'),
                        'space-between' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_HORIZONTAL_ALIGNMENT_SPACE_BETWEEN'),
                        'space-around' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_HORIZONTAL_ALIGNMENT_SPACE_AROUND'),
                        'space-evenly' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FLEX_HORIZONTAL_ALIGNMENT_SPACE_EVENLY'),
                    ],
                    'std' => 'start',
                    'depends' => [['display', '=', 'flex']],
                ],
                'column_gap' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_COLUMN_GAP_TITLE'),
                    'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_COLUMN_GAP_DESC'),
                    'min'        => 0,
                    'max'        => 100,
                    'responsive' => true,
                    'std'        => ['xl' => '32'],
                ],
                'row_gap' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_ROW_GAP_TITLE'),
                    'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_ROW_GAP_DESC'),
                    'min'        => 0,
                    'max'        => 100,
                    'responsive' => true,
                    'std'        => ['xl' => '32'],
                    'depends'    => [['enable_scroller', '=', 0]],
                ],
            ],
        ],

        'item' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_ITEM_TITLE'),
            'fields' => [
                'item_width' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_ITEM_WIDTH_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_ITEM_WIDTH_DESC'),
                    'responsive' => true,
                ],
                'item_padding' => [
                    'type'       => 'padding',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    'responsive' => true,
                ],
                'item_link_separator' => [
                    'type' => 'separator',
                ],
                'link' => [
                    'type'  => 'link',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                    'default_tab' => 'page',
                ],
                'item_options_separator' => [
                    'type' => 'separator',
                ],
                'item_options' => [
                    'type'   => 'buttons',
                    'values' => [
                        [ 'label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'), 'value' => 'normal' ],
                        [ 'label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'), 'value' => 'hover' ],
                    ],
                    'std' => 'normal',
                ],
                'item_background' => [
                    'type'    => 'color',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    'depends' => [['item_options', '=', 'normal']],
                ],
                'item_border_separator' => [
                    'type'    => 'separator',
                    'depends' => [['item_options', '=', 'normal']],
                ],
                'item_border' => [
                    'type'       => 'border',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    'responsive' => true,
                    'depends'    => [['item_options', '=', 'normal']],
                ],
                'item_border_radius' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                    'responsive' => true,
                    'depends'    => [['item_options', '=', 'normal']],
                ],
                'item_box_shadow' => [
                    'type'    => 'boxshadow',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BOX_SHADOW'),
                    'depends' => [['item_options', '=', 'normal']],
                ],
                // hover state
                'item_background_hover' => [
                    'type'    => 'color',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    'depends' => [['item_options', '=', 'hover']],
                ],
                'item_border_separator_hover' => [
                    'type'    => 'separator',
                    'depends' => [['item_options', '=', 'hover']],
                ],
                'item_border_hover' => [
                    'type'    => 'border',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    'depends' => [['item_options', '=', 'hover']],
                ],
                'item_border_radius_hover' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                    'responsive' => true,
                    'depends'    => [['item_options', '=', 'hover']],
                ],
                'item_box_shadow_hover' => [
                    'type'    => 'boxshadow',
                    'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BOX_SHADOW'),
                    'depends' => [['item_options', '=', 'hover']],
                ],
            ],
        ],
    ],
]);
