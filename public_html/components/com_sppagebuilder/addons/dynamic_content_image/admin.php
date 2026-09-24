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
    'addon_name' => 'dynamic_content_image',
    'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_IMAGE'),
    'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_IMAGE_DESC'),
    'category'   => Text::_('COM_EASYSTORE_ADDON_GROUP_DYNAMIC_CONTENT'),
    'icon'       => '<svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3 8.792C3 6.698 4.712 5 6.824 5h15.295c2.112 0 3.824 1.698 3.824 3.792v6.066a.758.758 0 0 1-.472.7.77.77 0 0 1-.833-.163l-3.23-3.202a.769.769 0 0 0-1.189.134l-5.446 8.64c-.854 1.356-2.814 1.439-3.781.16l-1.885-2.492a.768.768 0 0 0-1.268.064l-3.301 5.455a2.287 2.287 0 0 0 2.286 2.08h7.648c.422 0 .764.339.764.758a.762.762 0 0 1-.764.758H6.824C4.712 27.75 3 26.052 3 23.958V8.792ZM4.53 21.22l1.997-3.302c.839-1.385 2.826-1.487 3.803-.195l1.886 2.493a.768.768 0 0 0 1.26-.053l5.446-8.64a2.306 2.306 0 0 1 3.568-.404l1.924 1.908V8.792a2.285 2.285 0 0 0-2.295-2.275H6.824A2.285 2.285 0 0 0 4.53 8.792V21.22ZM9.118 9.55c-.845 0-1.53.679-1.53 1.517 0 .837.686 1.516 1.53 1.516.845 0 1.53-.679 1.53-1.516 0-.838-.685-1.517-1.53-1.517ZM6.06 11.067c0-1.676 1.37-3.034 3.06-3.034 1.689 0 3.058 1.358 3.058 3.034 0 1.675-1.37 3.033-3.059 3.033-1.69 0-3.059-1.358-3.059-3.033Zm18.579 5.59a.77.77 0 0 1 1.081 0l2.715 2.692.001.002.018.017c.095.093.214.209.303.323.108.14.244.363.244.667 0 .304-.136.527-.244.667-.09.114-.208.23-.303.322l-.018.018-.001.002-2.715 2.69a.77.77 0 0 1-1.081 0 .754.754 0 0 1 0-1.072l1.885-1.869h-5.942c-1.197 0-2.285 1.078-2.285 2.559 0 1.48 1.088 2.558 2.285 2.558h3.05c.422 0 .765.34.765.759a.762.762 0 0 1-.765.758h-3.05c-2.172 0-3.815-1.892-3.815-4.075 0-2.184 1.643-4.076 3.815-4.076h5.942l-1.885-1.869a.754.754 0 0 1 0-1.072Z" fill="currentColor"/></svg>',
    'settings'   => [
        'content' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_CONTENT'),
            'fields' => [
                'attribute' => [
                    'type'   => 'attribute',
                    'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_COLLECTION_TEXT_FIELD_SOURCE'),
                    'allowed_types' => ['image','gallery', 'video'],
                ],

                'item_alignment' => [
					'type' => 'alignment',
                    'depends' => [['attribute?.type', '=', 'gallery']],
					'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
					'responsive' => true,
					'available_options' => ['left', 'center', 'right'],
					'std' => [
						'xl' => 'left',
						'lg' => '',
						'md' => '',
						'sm' => '',
						'xs' => '',
					],
				],
            ],
        ],
       'options' => [
			'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS'),
            'depends' => [['attribute?.type', '=', 'gallery']],
			'fields' => [
				'enable_slider' => [
					'type'       => 'checkbox',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS_ENABLE_SLIDER'),
					'std'        => 0,
				],
                'enable_arrows' => [
                    'type'       => 'checkbox',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS_ENABLE_ARROWS'),
					'std'        => 0,
                    'depends'    => [['enable_slider', '=', '1']]
                ],
                'slider_style' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS_SLIDER_STYLE'),
                    'values' => [
                        'thumb' => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS_SLIDER_STYLE_THUMBNAIL'),
                        'carousel' => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS_SLIDER_STYLE_CAROUSEL'),
                    ],
                    'std' => 'thumb',
                    'depends'    => [['enable_slider', '=', '1']]
                ],
                'image_per_slide' => [
                    'type'       => 'slider',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_OPTIONS_IMAGE_PER_SLIDE'),
					'std'        => 1,
                    'depends'    => [['enable_slider', '=', '1'], ['slider_style', '=', 'carousel']]
                ],

				'gallery_width' => [
					'type'       => 'slider',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
					'responsive' => true,
					'std'        => ['xl' => 200],
				],

				'gallery_height' => [
					'type'       => 'slider',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_HEIGHT'),
					'responsive' => true,
					'std'        => ['xl' => 200],
				],

				'gallery_item_gap' => [
					'type'       => 'slider',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
					'responsive' => true,
					'std'        => ['xl' => 0],
					'max'        => 80,
				],

                'gallery_border_radius' => [
                    'type' => 'advancedslider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                    'std' => 0,
                    'max' => 1200,
                ],
			]
		],
        'title' => [
			'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
            'depends' => [['attribute?.type', '=', 'gallery']],
			'fields' => [
				'gallery_title' => [
					'type'  => 'text',
					'title' => Text::_('COM_SPPAGEBUILDER_ADDON_TITLE'),
					'desc'  => Text::_('COM_SPPAGEBUILDER_ADDON_TITLE_DESC'),
				],

				'gallery_heading_selector' => [
					'type'   => 'headings',
					'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_HEADINGS'),
					'desc'   => Text::_('COM_SPPAGEBUILDER_ADDON_HEADINGS_DESC'),
					'std'   => 'h3',
				],

				'gallery_title_typography' => [
					'type'     => 'typography',
					'title'  	=> Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
					'fallbacks'   => [
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

				'gallery_title_text_color' => [
					'type'   => 'color',
					'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
				],

				'gallery_title_margin_separator' => [
					'type' => 'separator',
				],

				'gallery_title_margin_top' => [
					'type'       => 'slider',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN_TOP'),
					'max'        => 400,
					'responsive' => true,
				],

				'gallery_title_margin_bottom' => [
					'type'       => 'slider',
					'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN_BOTTOM'),
					'max'        => 400,
					'responsive' => true,
				],
			],
		],
        'link' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
            'depends' => [['attribute?.type', '=', 'image']],
            'fields' => [
                'link' => [
                    'type'  => 'link',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LINK'),
                    'default_tab' => 'page',
                ],
            ],
        ],
        'general' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_GENERAL'),
            'depends' => [['attribute?.type', '!=', 'gallery']],
            'fields' => [
                'mp4_enable' => [
                    'type'   => 'radio',
                    'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_SOURCE'),
                    'values' => [
                        0 => 'YouTube/Vimeo',
                        1 => 'MP4',
                    ],
                    'depends' => [['attribute?.type', '=', 'video']],
                    'std' => 0,
                ],
                'show_control' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_CONTROL'),
                    'std'     => 1,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 1]],
                ],

                'video_loop' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_LOOP'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video', ['mp4_enable', '=', 1]]],
                ],

                'video_mute' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_MUTE'),
                    'std'     => 1,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 1]],
                ],

                'autoplay_video' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_AUTOPLAY'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 1]],
                ],

                'download_video' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_DOWNLOAD'),
                    'std'     => 1,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 1]],
                ],

                'show_rel_video' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_OWN_CHANNEL_REL'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 0]],
                ],

                'no_cookie' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_NO_COOKIE'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_NO_COOKIE_DESC'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 0]],
                ],

                'youtube_shorts' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_YOUTUBE_SHORTS'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_YOUTUBE_SHORTS_DESC'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 0]],
                ],

                'vimeo_show_author' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_SHOW_AUTHOR'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_SHOW_AUTHOR_DESC'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 0]],
                ],

                'vimeo_mute_video' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_MUTE_VIDEO'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_MUTE_VIDEO_DESC'),
                    'std'     => 1,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 0]],
                ],

                'vimeo_show_author_profile' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_SHOW_AUTHOR_PROFILE'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_SHOW_AUTHOR_PROFILE_DESC'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 0]],
                ],

                'vimeo_show_video_title' => [
                    'type'    => 'checkbox',
                    'title'   => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_SHOW_VIDEO_TITLE'),
                    'desc'    => Text::_('COM_SPPAGEBUILDER_ADDON_VIDEO_VIMEO_SHOW_VIDEO_TITLE_DESC'),
                    'std'     => 0,
                    'depends' => [['attribute?.type', '=', 'video'], ['mp4_enable', '=', 0]],
                ],

                'width' => [
                    'type' => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_WIDTH'),
                    'max' => 2000,
                    'min' => 0,
                    'responsive' => true,
                ],

                'height' => [
                    'type' => 'slider',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HEIGHT'),
                    'max' => 2000,
                    'min' => 0,
                    'responsive' => true,
                ],

                'image_fit' => [
                    'type'  => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT'),
                    'std'   => 'cover',
                    'depends' => [['attribute?.type', '!=', 'video']],
                    'values' => [
                        'none'          => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_NONE'),
                        'cover'         => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_COVER'),
                        'contain'       => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_CONTAIN'),
                        'fill'          => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_FILL'),
                        'scale-down'    => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_IMAGE_FIT_SCALE_DOWN'),
                    ],
                ],
                'aspect_ratio' => [
                    'type' => 'select',
                    'title' => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO'),
                    'values' => [
                        'none'   => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_NONE'),
                        '16/9'   => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_WIDESCREEN'),
                        '4/3'    => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_STANDARD'),
                        '1/1'    => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_SQUARE'),
                        '3/2'    => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_PHOTOGRAPHY'),
                        '9/16'   => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_PORTRAIT'),
                        '21/9'   => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_ULTRA_WIDE'),
                        'custom' => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_ASPECT_RATIO_CUSTOM'),
                    ],
                    'depends' => [['attribute?.type', '!=', 'video']],
                ],
                'custom_aspect_ratio' => [
                    'type' => 'text',
                    'title' => Text::_('COM_SPPAGEBUILDER_COLLECTION_THUMBNAIL_CUSTOM_ASPECT_RATIO'),
                    'placeholder' => 'e.g. 16/9',
                    'depends' => [
                        ['aspect_ratio', '=', 'custom'],
                        'depends' => [['attribute?.type', '!=', 'video']],
                    ],
                ],
                'padding' => [
                    'type'       => 'padding',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
                    'responsive' => true,
                ],
                'margin' => [
                    'type'       => 'margin',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
                    'responsive' => true,
                ],
                'border_separator' => [
                    'type' => 'separator',
                ],
                'border' => [
                    'type'  => 'border',
                    'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER'),
                ],
                'radius' => [
                    'type'       => 'slider',
                    'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_RADIUS'),
                    'min'        => 0,
                    'max'        => 100,
                    'responsive' => true,
                ],
            ],
        ],
        'effects' => [
            'title'  => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_EFFECTS'),
            'depends' => [['attribute?.type', '=', 'image']],
            'fields' => [
                'is_effects_enabled' => [
                    'type'      => 'checkbox',
                    'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_IMAGE_EFFECTS'),
                    'std'       => 0,
                    'is_header' => 1,
                ],

                'image_effects' => [
                    'type'    => 'effects',
                    'depends' => [
                        ['is_effects_enabled', '=', 1],
                    ],
                ],
            ],
        ],
    ],
]);
