<?php

/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

SpAddonsConfig::addonConfig(
    [
        'type'       => 'dynamic-content',
        'addon_name' => 'dynamic_content_filter',
        'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_FILTER'),
        'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_FILTER_DESC'),
        'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_DYNAMIC_CONTENT'),
        'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none"><path stroke="currentColor" stroke-width="1.333" d="m8.625 14.66 6.37 6.289a.644.644 0 0 1 .193.458v5.448c0 .232.125.445.328.561l2.625 1.496a.656.656 0 0 0 .984-.56v-8.24c0-.172.07-.337.192-.459l5.058-4.992m-2.625-3.887V8.182h-2.625v2.592M6 11.422v2.59c0 .358.294.649.656.649h19.688c.362 0 .656-.29.656-.648v-2.591a.652.652 0 0 0-.656-.648H6.656a.652.652 0 0 0-.656.648Zm2.625-7.126h3.938v3.886H8.624V4.296ZM23.063 3h2.625v2.591h-2.625V3Z"/></svg>',
        'settings'   => [
            'general' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_FILTER_GENERAL'),
                'fields' => [
                    'source' => [
                        'type'   => 'dynamic_source',
                        'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SOURCE_TITLE'),
                        'desc'   => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SOURCE_DESC'),
                    ],

                    'filter_layout' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_TITLE'),
                        'values' => [
                            'accordion' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_ACCORDION'),
                            'dropdown' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_DROPDOWN'),
                            'flat' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_FLAT'),
                        ],
                        'std' => 'flat',
                    ],
                ],
            ],

            'content' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_FILTER_OPTIONS'),
                'fields' => [
                    'filter_items' => [
                        'type'  => 'repeatable',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_PROPERTIES'),
                        'attr'  => [
                            'filter_model' => [
                                'type'  => 'radio',
                                'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_FILTER_MODEL'),
                                'std'   => 'search_filter',
                                'values' => [
                                    'field_filter' => Text::_('COM_SPPAGEBUILDER_GLOBAL_FIELD_WISE_FILTER'),
                                    'search_filter' => Text::_('COM_SPPAGEBUILDER_GLOBAL_FILTER_SEARCH'),
                                ],
                            ],

                            'title' => [
                                'type'  => 'text',
                                'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
                                'std'   => '',
                            ],

                            'field_name' => [
                                'type'   => 'dynamic_field',
                                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SCHEMA_FIELD_TITLE'),
                                'desc'   => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_SCHEMA_FIELD_DESC'),
                                'depends' => [
                                    ['filter_model', '=', 'field_filter'],
                                ],
                            ],

                            'filter_layout_override' => [
                                'type'   => 'select',
                                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_TITLE'),
                                'values' => [
                                    'accordion' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_ACCORDION'),
                                    'dropdown' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_DROPDOWN'),
                                    'flat' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_LAYOUT_FLAT'),
                                ],
                            ],


                            'filter_control_type' => [
                                'type'   => 'filter_control',
                                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_CONTROL_TYPE_TITLE'),
                                'depends' => [
                                    ['filter_model', '=', 'field_filter'],
                                ],
                            ],

                            'hide_title' => [
                                'type' => 'checkbox',
                                'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_HIDE_TITLE'),
                                'std' => 0,
                            ],

                            'show_count' => [
                                'type' => 'checkbox',
                                'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_SHOW_COUNT_TITLE'),
                                'std' => 1,
                                'depends' => [
                                    ['filter_model', '=', 'field_filter'],
                                ],
                            ],
                    ],
                ],

                    'direction' => [
                            'type'   => 'select',
                            'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DIRECTION'),
                            'values' => [
                                'row'    => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DIRECTION_ROW'),
                                'column' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DIRECTION_COLUMN'),
                            ],
                            'std' => 'row',
                    ],

                    'align_items' => [
                        'type' => 'buttons',
                        'title' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_ALIGN"),
                        'std'        => ['xl' => 'center', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                        'responsive' => true,
                        'values' => [
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_START"),
                                    'icon' => 'alignStart'
                                ],
                                'value' => 'flex-start'
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_CENTER"),
                                    'icon' => 'alignCenter'
                                ],
                                'value' => 'center'
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_END"),
                                    'icon' => 'alignEnd'
                                ],
                                'value' => 'flex-end'
                            ],
                        ],
				    ],

                    'gap' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],

                    'title_separator' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_TITLE_SEPARATOR'),
                    ],

                    'title_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                    ],

                    'title_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                        'fallbacks' => [
                            'font'           => 'title_font_family',
                            'size'           => 'title_fontsize',
                            'line_height'    => 'title_lineheight',
                            'letter_spacing' => 'title_letterspace',
                            'uppercase'      => 'title_font_style.uppercase',
                            'italic'         => 'title_font_style.italic',
                            'underline'      => 'title_font_style.underline',
                            'weight'         => 'title_font_style.weight',
                        ],
                    ],

                    'title_margin' => [
                        'type'  => 'margin',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'responsive' => true,
                        'std' => [
                            'xl' => '0px 0px 8px 0px',
                            'lg' => '',
                            'md' => '',
                            'sm' => '',
                            'xs' => '',
                        ],
                    ],

                    'reset_btn_separator' => [
                        'type'  => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_RESET_BUTTON'),
                    ],

                    'reset_label' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_RESET_BUTTON_LABEL'),
                    ],

                    'match_button_style' => [
                        'type' => 'checkbox',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_MATCH_BUTTON_STYLE'),
                    ],

                    'match_button_behavior' => [
                        'type' => 'checkbox',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_MATCH_BUTTON_BEHAVIOR'),
                        'std' => 0,
                        'depends' => [
                            ['match_button_style', '=', '1'],
                        ],
                    ],

                    'reset_btn_position' => [
                        'type' => 'radio',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_RESET_BUTTON_POSITION'),
                        'values' => [
                            'start' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_RESET_BUTTON_START'),
                            'end'   => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_RESET_BUTTON_END'),
                        ],
                        'std' => 'end',
                    ],

                    'reset_btn_alignment' => [
                        'type' => 'buttons',
                        'title' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_ALIGN"),
                        'std'        => ['xl' => 'center', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                        'responsive' => true,
                        'values' => [
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_START"),
                                    'icon' => 'alignStart'
                                ],
                                'value' => 'flex-start'
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_CENTER"),
                                    'icon' => 'alignCenter'
                                ],
                                'value' => 'center'
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_END"),
                                    'icon' => 'alignEnd'
                                ],
                                'value' => 'flex-end'
                            ],
                        ],
                        'depends' => [
                            ['match_button_style', '=', '0'],
                        ],
                    ],

                    'reset_btn_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'depends' => [
                            ['match_button_style', '=', '0'],
                        ],
                    ],
                    
                ],
            ],

            'list_item' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_LIST_ITEM'),
                'fields' => [

                    'item_direction' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DIRECTION'),
                        'values' => [
                            'row'    => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DIRECTION_ROW'),
                            'column' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DIRECTION_COLUMN'),
                        ],
                        'std' => 'row',
                    ],

                    'align_list_items' => [
                        'type' => 'buttons',
                        'title' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_ALIGN"),
                        'std'        => ['xl' => 'center', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
                        'responsive' => true,
                        'values' => [
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_START"),
                                    'icon' => 'alignStart'
                                ],
                                'value' => 'flex-start'
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_CENTER"),
                                    'icon' => 'alignCenter'
                                ],
                                'value' => 'center'
                            ],
                            [
                                'label' => [
                                    'tooltip' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_ALIGN_END"),
                                    'icon' => 'alignEnd'
                                ],
                                'value' => 'flex-end'
                            ],
                        ],
                    ],

                    'list_item_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                    ],

                    'list_item_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                        'fallbacks' => [
                            'font'           => 'list_item_font_family',
                            'size'           => 'list_item_fontsize',
                            'line_height'    => 'list_item_lineheight',
                            'letter_spacing' => 'list_item_letterspace',
                            'uppercase'      => 'list_item_font_style.uppercase',
                            'italic'         => 'list_item_font_style.italic',
                            'underline'      => 'list_item_font_style.underline',
                            'weight'         => 'list_item_font_style.weight',
                        ],
                    ],

                    'list_item_gap' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],

                    'list_item_count_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_LIST_COUNT_COLOR'),
                        'std'   => '#000000',
                    ],
                ],
            ],

             'checkbox_radio' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_CHECKBOX_OR_RADIO'),
                'fields' => [
                    'checkbox_radio_size' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'   => 8,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],

                    'checkbox_radio_check_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_CHECK_COLOR'),
                        'std' => '#000000',
                    ],

                    'checkbox_radio_margin' => [
                        'type'  => 'margin',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                        'responsive' => true,
                    ],

                    'checkbox_radio_options_separator' => [
                        'type' => 'separator',
                    ],

                    'checkbox_radio_options' => [
                        'type'   => 'buttons',
                        'values' => [
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'), 'value' => 'normal'],
                            ['label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ACTIVE'), 'value' => 'active'],
                        ],
                        'std' => 'normal',
                    ],

                    'checkbox_radio_background_color' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'depends' => [
                            ['checkbox_radio_options', '=', 'normal'],
                        ],
                        'std' => '#FFFFFF',
                    ],

                    'checkbox_radio_background_color_active' => [
                        'type'    => 'color',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'depends' => [
                            ['checkbox_radio_options', '=', 'active'],
                        ],
                        'std' => '#FFFFFF',
                    ],

                    'checkbox_radio_border_separator' => [
                        'type'    => 'separator',
                    ],

                    'checkbox_radio_border' => [
                        'type'    => 'border',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'std' => [
                            'border_width' => '1px',
                            'border_style' => 'solid',
                            'border_color' => '#000000',
                        ]
                    ],

                    'checkbox_radio_border_radius' => [
                        'type'    => 'slider',
                        'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'min'     => 0,
                        'max'     => 100,
                        'std'     => [
                            'xl' => 4,
                        ],
                        'responsive' => true,
                        'std' => [
                            'xl' => 4,
                        ],
                    ],

                ],
            ],

            'input' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_INPUT'),
                'fields' => [
                    'input_background' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'std'   => '#FFFFFF',
                    ],

                    'input_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                    ],

                    'input_border_separator' => [
                        'type' => 'separator',
                    ],

                    'input_border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'std'   => [
                            'border_width' => '1px',
                            'border_style' => 'solid',
                            'border_color' => '#7A7C85',
                        ],
                    ],

                    'input_border_radius_separator' => [
                        'type' => 'separator',
                    ],

                    'input_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'std' => '6px',
                    ],

                    'input_padding_separator' => [
                        'type' => 'separator',
                    ],

                    'input_padding' => [
                        'type'  => 'padding',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                        'std' => '15px 15px 15px 15px'
                    ],
                ],
            ],

                'search' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_SEARCH'),
                'fields' => [
                    'search_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                    ],
                    'search_background_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                    ],
                    'search_border_separator_start' => [
                        'type' => 'separator',
                    ],
                    'search_border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'std' => [
                            'border_width'=> '1px',
                            'border_style'=> 'solid',
                            'border_color'=> '#CDCFD5',
                        ],
                    ],
                    'search_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 6,
                        ],
                        'responsive' => true,
                    ],
                    'search_border_separator_end' => [
                        'type' => 'separator',
                    ],
                    'search_padding' => [
                        'type'  => 'padding',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    ],
                    'search_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],
                    'icon_separator' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_SEARCH_ICON'),
                    ],
                    'search_icon' => [
                        'type'   => 'icon',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
                        'std'    => 'fa fa-search',
                    ],
                    'search_icon_size' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SIZE'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 14,
                        ],
                        'responsive' => true,
                    ],
                    'search_icon_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#AEB1BD', 
                    ],
                    'search_icon_spacing' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_SPACING'),
                        'min'   => 0,
                        'max'   => 100,
                        'responsive' => true,
                    ],
                    'placeholder_separator' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_SEARCH_PLACEHOLDER'),
                    ],
                    'search_placeholder' => [
                        'type'  => 'text',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_SEARCH_PLACEHOLDER_TEXT'),
                        'std'   => 'Type to search...',
                    ],
                    'search_placeholder_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#7A7C85',
                    ], 
                ],
            ],

            'button' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_BUTTON'),
                'fields' => [
                    'button_type' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_STYLE'),
                        'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_STYLE_DESC'),
                        'values' => [
                            'default'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_DEFAULT'),
                            'custom'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
                        ],
                        'std'    => 'custom',
                    ],

                    'button_appearance' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE'),
                        'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_DESC'),
                        'values' => [
                            ''         => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_FLAT'),
                            'gradient' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_GRADIENT'),
                            'outline'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_OUTLINE'),
                        ],
                        'std'     => 'outline',
                    ],

                    'button_shape' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE'),
                        'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_DESC'),
                        'values' => [
                            'rounded' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUNDED'),
                            'square'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_SQUARE'),
                            'round'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUND'),
                        ],
                        'std'   => 'rounded',
                    ],

                    'button_block' => [
                        'type'   => 'select',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_BLOCK'),
                        'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_BLOCK_DESC'),
                        'values' => [
                            ''               => Text::_('JNO'),
                            'dc-filter-btn-block' => Text::_('JYES'),
                        ],
                        'std'   => '',
                    ],

                    'state_separator' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_BUTTON_STATE'),
                        'depends' => [
                            ['button_type', '=', 'custom'],
                        ],
                    ],

                    'button_style_state' => [
                        'type'   => 'radio',
                        'values' => [
                            'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
                            'hover' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
                        ],
                        'std' => 'normal',
                        'depends' => [
                            ['button_type', '=', 'custom'],
                        ],
                    ],

                    'button_color' => [
                        'type'   => 'color',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'    => '#000000',
                        'depends' => [
                            ['button_style_state', '=', 'normal'],
                            ['button_type', '=', 'custom'],
                        ],
                    ],

                    'button_color_hover' => [
                        'type'   => 'color',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'    => '#FFFFFF',
                        'depends' => [
                            ['button_style_state', '=', 'hover'],
                            ['button_type', '=', 'custom'],
                        ],
                    ],

                    'button_background_color' => [
                        'type'   => 'color',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'std'    => '#FFFFFF',
                        'depends' => [
                            ['button_style_state', '=', 'normal'],
                            ['button_appearance', '!=', 'gradient'],
                            ['button_type', '=', 'custom'],
                        ],
                    ],

                    'button_background_color_hover' => [
                        'type'    => 'color',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'std'     => '#000000',
                        'depends' => [
                            ['button_style_state', '=', 'hover'],
                            ['button_appearance', '!=', 'gradient'],
                            ['button_type', '=', 'custom'],
                        ],
                    ],

                    
                    'button_background_gradient' => [
                        'type' => 'gradient',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'std'  => [
                            "color"  => "#3366FF",
                            "color2" => "#0037DD",
                            "deg"    => "45",
                            "type"   => "linear"
                        ],
                        'depends' => [
                            ['button_style_state', '=', 'normal'],
                            ['button_appearance', '=', 'gradient'],
                            ['button_type', '=', 'custom'],
                        ],
                    ],

                    'button_background_gradient_hover' => [
                        'type'  => 'gradient',
                        'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
                        'std'   => [
                            "color"  => "#0037DD",
                            "color2" => "#3366FF",
                            "deg"    => "45",
                            "type"   => "linear"
                        ],
                        'depends' => [
                            ['button_style_state', '=', 'hover'],
                            ['button_appearance', '=', 'gradient'],
                            ['button_type', '=', 'custom'],
                        ],
                    ],
                ],
            ],

            'slider' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_SLIDER'),
                'fields' => [
                    'slider_track_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_TRACK_COLOR'),
                        'std' => '#CDCFD5',
                    ],
                    'slider_track_height' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_TRACK_HEIGHT'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 4,
                        ],
                        'responsive' => true,
                    ],
                    'active_segment_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_ACTIVE_SEGMENT_COLOR'),
                        'std' => '#000000',
                    ],

                    'slider_separator' => [
                        'type' => 'separator',
                    ],

                    'slider_thumb_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_COLOR'),
                        'std'   => '#FFFFFF',
                    ],

                    'slider_thumb_size' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_SIZE'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],
                    'slider_thumb_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_BORDER_RADIUS'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],
                    'label_separator' => [
                        'type' => 'separator',
                    ],
                    'slider_label_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_SLIDER_LABEL_COLOR'),
                    ],
                    'slider_label_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_SLIDER_LABEL_TYPOGRAPHY'),
                        'fallbacks' => [
                            'font'           => 'slider_label_font_family',
                            'size'           => 'slider_label_fontsize',
                            'line_height'    => 'slider_label_lineheight',
                            'letter_spacing' => 'slider_label_letterspace',
                            'uppercase'      => 'slider_label_font_style.uppercase',
                            'italic'         => 'slider_label_font_style.italic',
                            'underline'      => 'slider_label_font_style.underline',
                            'weight'         => 'slider_label_font_style.weight',
                        ],
                    ],
                    'editable_label_field' => [
                        'type'  => 'checkbox',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_EDITABLE_LABEL_FIELD'),
                        'std'   => 0,
                    ],
                ],
            ],

            'switch' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_SWITCH'),
                'fields' => [
                    'track_separatror' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_TRACK_SEPARATOR'),
                    ],
                    'switch_track' => [
                        'type'   => 'radio',
                        'values' => [
                            'on' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_TRACK_ON'),
                            'off' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_TRACK_OFF'),
                        ],
                        'std' => 'off',                       
                    ],
                    'switch_track_on_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                        'depends' => [
                            ['switch_track', '=', 'on'],
                        ],
                    ],
                    'switch_track_off_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#CDCFD5',
                        'depends' => [
                            ['switch_track', '=', 'off'],
                        ],
                    ],
                    'switch_track_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_TRACK_BORDER_RADIUS'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],
                    'thumb_separator' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_SEPARATOR'),
                    ],
                    'switch_thumb' => [
                        'type'   => 'radio',
                        'values' => [
                            'on' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_ON'),
                            'off' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_OFF'),
                        ],
                        'std' => 'off',                       
                    ],
                    'switch_thumb_on_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#FFFFFF',
                        'depends' => [
                            ['switch_thumb', '=', 'on'],
                        ],
                    ],
                    'switch_thumb_off_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#FFFFFF',
                        'depends' => [
                            ['switch_thumb', '=', 'off'],
                        ],
                    ],
                    'switch_thumb_size' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_SIZE'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],
                    'switch_thumb_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_THUMB_BORDER_RADIUS'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 16,
                        ],
                        'responsive' => true,
                    ],
                ],
            ],

            'date' => [
                'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_OPTION_DATE'),
                'fields' => [
                    'date_background_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'std'   => '#FFFFFF',
                    ],
                    'date_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                    ],
                    'date_border_separator_start' => [
                        'type' => 'separator',
                    ],
                    'date_border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                    ],
                    'date_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 10,
                        ],
                        'responsive' => true,
                    ],
                    'date_border_separator_end' => [
                        'type' => 'separator',
                    ],
                    'date_typography' => [
                        'type'  => 'typography',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    ],
                    'cell_state_separator' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DATE_CELL_STATE'),
                    ],
                    'date_cell_state' => [
                        'type'   => 'radio',
                        'values' => [
                            'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
                            'hover'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
                        ],
                        'std' => 'normal',
                    ],
                    'date_cell_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                        'depends' => [
                            ['date_cell_state', '=', 'normal'],
                        ],
                    ],
                    'date_cell_color_hover' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#FFFFFF',
                        'depends' => [
                            ['date_cell_state', '=', 'hover'],
                        ],
                    ],
                    'date_cell_background_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'std'   => '#FFFFFF',
                        'depends' => [
                            ['date_cell_state', '=', 'normal'],
                        ],
                    ],
                    'date_cell_background_color_hover' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'std'   => '#000000',
                        'depends' => [
                            ['date_cell_state', '=', 'hover'],
                        ],
                    ],
                    'date_cell_border_separator' => [
                        'type' => 'separator',
                    ],
                    'selected_range_highlight_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DATE_SELECTED_RANGE_HIGHLIGHT_COLOR'),
                        'std'   => '#F6F6F6',
                    ],
                    'date_input_separator' => [
                        'type' => 'separator',
                        'title' => Text::_('COM_SPPAGEBUILDER_ADDON_DYNAMIC_CONTENT_COLLECTION_FILTER_DATE_INPUT_SEPARATOR'),
                    ],
                    'date_input_background_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND'),
                        'std'   => '#FFFFFF',
                    ],
                    'date_input_color' => [
                        'type'  => 'color',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                        'std'   => '#000000',
                    ],
                    'date_input_border_separator' => [
                        'type' => 'separator',
                    ],
                    'date_input_border' => [
                        'type'  => 'border',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                        'std'   => [
                            'border_width' => '1px',
                            'border_style' => 'solid',
                            'border_color' => '#CDCFD5',
                        ],
                    ],
                    'date_input_border_radius' => [
                        'type'  => 'slider',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_RADIUS'),
                        'min'   => 0,
                        'max'   => 100,
                        'std'   => [
                            'xl' => 6,
                        ],
                        'responsive' => true,
                    ],
                    'date_input_padding_separator' => [
                        'type' => 'separator',
                    ],
                    'date_input_padding' => [
                        'type'  => 'padding',
                        'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    ],
                ],
            ],
        ],
    ],
        
    
);