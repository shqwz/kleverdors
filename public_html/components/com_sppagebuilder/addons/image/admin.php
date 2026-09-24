<?php
/**
 * @package SP Page Builder
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

SpAddonsConfig::addonConfig([
    'type' => 'content',
    'addon_name' => 'image',
    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE'),
    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_DESC'),
    'category' => 'Media',
    'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path opacity=".5" fill-rule="evenodd" clip-rule="evenodd" d="M31.288 17.393l-9.718 5.9a5 5 0 01-5.8-.435l-3.622-3.024a3 3 0 00-3.583-.197l-6.781 4.504-1.106-1.666 6.78-4.504a5 5 0 015.971.327l3.623 3.025a3 3 0 003.48.261l9.718-5.9 1.038 1.71z" fill="currentColor"/><path opacity=".5" fill-rule="evenodd" clip-rule="evenodd" d="M19.077 10.154a2.077 2.077 0 100 4.154 2.077 2.077 0 000-4.154zM15 12.23a4.077 4.077 0 118.154 0 4.077 4.077 0 01-8.154 0z" fill="currentColor"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29 4H3a1 1 0 00-1 1v22.308a1 1 0 001 1h26a1 1 0 001-1V5a1 1 0 00-1-1zM3 2a3 3 0 00-3 3v22.308a3 3 0 003 3h26a3 3 0 003-3V5a3 3 0 00-3-3H3z" fill="currentColor"/></svg>',
    'settings' => [
        'content' => [
            'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CONTENT'),
            'fields' => [
                'image' => [
                    'type' => 'media',
                    'hide_alt_text' => true,
                    'std' => [
                        'src' => 'https://sppagebuilder.com/addons/image/image1.jpg',
                        'height' => '',
                        'width' => '',
                        'alt' => ''
                    ],
                ],

                'image_2x' => [
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_IMAGE_2X_TEXT'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_GLOBAL_IMAGE_2X_TEXT_DESC'),
                    'type' => 'media',
                    'hide_preview' => true,
                    'hide_alt_text' => true,
                    'std' => [
                        'src' => '',
                        'height' => '',
                        'width' => '',
                    ],
                ],

                'alt_text' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALT_TEXT'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALT_TEXT_DESC'),
                    'std' => 'Image',
                    'inline' => true,
                ],

                'image_title' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_TITLE_DESC'),
                    'std' => '',
                ],

                'image_fit' => [
                    'type'  => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT'),
                    'std'   => '',
                    'desc'  => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_DESC'),
                    'values' => [
                        'none'          => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_NONE'),
                        'cover'         => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_COVER'),
                        'contain'       => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_CONTAIN'),
                        'fill'          => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_FILL'),
                        'scale-down'    => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_SCALE_DOWN'),
                    ],
                ],

                'position' => [
                    'type' => 'alignment',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
                    'responsive' => true,
                    'available_options' => ['left', 'center', 'right'],
                ],
            ],
        ],

        'image_shape' => [
            'title' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_SHAPE'),
            'fields' => [
                'is_image_shape_enabled' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_SHAPE'),
                    'std' => 0,
                    'is_header' => 1
                ],

                'image_shape' => [
                    'type' => 'image_shape',
                    'columns' => 3,
                    'values' => [
                        'circle' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 80 80"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><rect width="56" height="56" x="12" y="12" fill="currentColor" opacity="0.2" rx="28"/></svg>'],
                        'quarter_slice' => ['svg' => '<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="80" height="80" rx="6" fill="#F5F7F9"/><path d="M12 12C42.9279 12 68 37.0721 68 68H12V12Z" fill="currentColor" opacity="0.2" /></svg>'],
                        'half_circle' => ['svg' => '<svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="80" height="80" rx="6" fill="#F5F7F9"/><path d="M39.9911 36C22.3113 36 8 50.3331 8 68H72C71.9822 50.3331 57.6708 36 39.9911 36Z" fill="currentColor" opacity="0.2"/></svg>'],
                        'bevel' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="M22.956 12 12 22.723v35.745L21.74 68h36.52L68 57.277V21.532L57.044 12H22.956Z" opacity=".2"/></svg>'],
                        'star' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="m40 12 6.286 19.348H66.63L50.172 43.305l6.286 19.348L40 50.695 23.542 62.653l6.286-19.348L13.37 31.348h20.344L40 12Z" opacity=".2"/></svg>'],
                        'pentagon' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="M40.174 13 12 33.629 22.768 67h34.828l10.752-33.371L40.174 13Z" opacity=".2"/></svg>'],
                        'right_point' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="M52.51 13H12v54h40.51L68 40.6 52.51 13Z" opacity=".2"/></svg>'],
                        'triangle' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="m12 12 56 56H12V12Z" opacity=".2"/></svg>'],
                        'trapezoid' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="m12 68 11.667-56h32.666L68 68H12Z" opacity=".2"/></svg>'],
                        'right_chevron' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="M52.51 13H12l14.298 27.6L12 67h40.51L68 40.6 52.51 13Z" opacity=".2"/></svg>'],
                        'right_arrow' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="M41.19 25.106H15v32.17h26.19V68L65 40.596 41.19 12v13.106Z" opacity=".2"/></svg>'],
                        'rabbet' => ['svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="none"><rect width="80" height="80" fill="#F5F7F9" rx="6"/><path fill="currentColor" d="M21.333 21.143H12v36.571h9.333V68h37.334V57.714H68V21.143h-9.333V12H21.333v9.143Z" opacity=".2"/></svg>'],
                    ],
                    'std' => '',
                    'depends' => [
                        ['is_image_shape_enabled', '=', 1]
                    ]
                ],

                'image_shape_scale' => [
                    'type' => 'slider',
                    'title' => 'Scale Shape',
                    'min' => 0,
                    'max' => 10,
                    'step' => 0.1,
                    'std' => 1.2,
                    'depends' => [
                        ['is_image_shape_enabled', '=', 1],
                        ['image_shape', '!=', ''],
                        ['image_shape', '!=', 'circle'],
                        ['image_shape', '!=', 'quarter_slice'],
                        ['image_shape', '!=', 'half_circle'],
                        ['image_shape', '!=', 'bevel'],
                        ['image_shape', '!=', 'star'],
                        ['image_shape', '!=', 'pentagon'],
                        ['image_shape', '!=', 'right_point'],
                        ['image_shape', '!=', 'triangle'],
                        ['image_shape', '!=', 'trapezoid'],
                        ['image_shape', '!=', 'right_chevron'],
                        ['image_shape', '!=', 'right_arrow'],
                        ['image_shape', '!=', 'rabbet'],
                    ]
                ],
            ],
        ],

        'effects' => [
            'title' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_EFFECTS'),
            'fields' => [
                'is_effects_enabled' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_EFFECTS'),
                    'std' => 0,
                    'is_header' => 1
                ],

                'image_effects' => [
                    'type' => 'effects',
                    'depends' => [
                        ['is_effects_enabled', '=', 1]
                    ]
                ],
            ],
        ],


        'options' => [
            'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS'),
            'fields' => [
                'image_width' => [
                    'type' => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                    'max' => 2000,
                    'min' => 0,
                    'responsive' => true,
                ],

                'image_height' => [
                    'type' => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HEIGHT'),
                    'max' => 2000,
                    'min' => 0,
                    'responsive' => true,
                ],

                'border_radius' => [
                    'type' => 'advancedslider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                    'std' => 0,
                    'max' => 1200,
                    'depends' => [
                        ['image_shape', '!=', 'quarter_slice'],
                        ['image_shape', '!=', 'half_circle'],
                    ]
                ],

                'fetch_priority' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_FETCH_PRIORITY'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_FETCH_PRIORITY_DESC'),
                    'std' => '',
                    'values' => [
                        'high' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_FETCH_PRIORITY_HIGH'),
                        'low' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_FETCH_PRIORITY_LOW'),
                    ]
                ],

                'open_lightbox' => [
                    'type' => 'checkbox',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_OPEN_LIGHTBOX'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_OPEN_LIGHTBOX_DESC'),
                    'std' => 0,
                ],

                'overlay_color' => [
                    'type' => 'color',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_OVERLAY'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_OVERLAY_DESC'),
                    'std' => 'rgba(119, 219, 31, .5)',
                    'depends' => [['open_lightbox', '!=', 0]],
                ],

                'is_zoom_enabled' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_ZOOM'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_ZOOM_DESC'),
                    'std' => 0,
                ],

                'zoom_scale' => [
                    'type' => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_SCALE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_ZOOM_SCALE_DESC'),
                    'min' => 0,
                    'std' => '1.2',
                    'depends' => [
                        ['is_zoom_enabled', '=', 1]
                    ]
                ],

                'link' => [
                    'type' => 'link',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK_DESC'),
                    'std' => '',
                    'depends' => [['open_lightbox', '!=', 1]],
                ],
            ],
        ],

        'title' => [
            'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
            'fields' => [
                'title' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_TITLE'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_TITLE_DESC'),
                ],

                'heading_selector' => [
                    'type' => 'headings',
                    'title' => Text::_('COM_SPPAGEBUILDER_ADDON_HEADINGS'),
                    'desc' => Text::_('COM_SPPAGEBUILDER_ADDON_HEADINGS_DESC'),
                    'std' => 'h3',
                ],

                'title_typography' => [
                    'type' => 'typography',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
                    'fallbacks' => [
                        'font' => 'title_font_family',
                        'size' => 'title_fontsize',
                        'line_height' => 'title_lineheight',
                        'letter_spacing' => 'title_letterspace',
                        'uppercase' => 'title_font_style.uppercase',
                        'italic' => 'title_font_style.italic',
                        'underline' => 'title_font_style.underline',
                        'weight' => 'title_font_style.weight',
                    ],
                ],

                'title_text_color' => [
                    'type' => 'color',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
                ],

                'title_position' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_POSITION'),
                    'values' => [
                        'top' => 'Top',
                        'bottom' => 'Bottom',
                    ],
                    'std' => 'top',
                ],

                'title_margin_top' => [
                    'type' => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN_TOP'),
                    'max' => 400,
                    'responsive' => true,
                ],

                'title_margin_bottom' => [
                    'type' => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN_BOTTOM'),
                    'max' => 400,
                    'responsive' => true,
                ],
            ],
        ],
    ],
]);