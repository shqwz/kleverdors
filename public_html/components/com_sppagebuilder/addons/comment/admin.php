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
		'type'       => 'content',
		'addon_name' => 'comment',
		'title'      => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT'),
		'desc'       => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_DESC'),
		'category'   => 'Content',
		'icon'       => '<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M7.9668 13.823H12.9974M7.9668 9.63086H10.4821" stroke="#6F7CA3" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M12.1691 5.02051H3.93555C3.38326 5.02051 2.93555 5.46822 2.93555 6.02051V17.4002C2.93555 17.9525 3.38326 18.4002 3.93555 18.4002H6.29105V21.7797C6.29105 21.7867 6.29922 21.7906 6.30471 21.7862L10.4929 18.4002H18.703C19.2553 18.4002 19.703 17.9525 19.703 17.4002V12.5694" stroke="#6F7CA3" stroke-width="1.3" stroke-linecap="round"/>
						<ellipse cx="17.3784" cy="7.39731" rx="3.68696" ry="3.68686" fill="#6F7CA3"/>
						<path d="M15.875 7.39743H18.8838M17.3794 5.89307V8.90179" stroke="white" stroke-width="0.8" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						',
		'settings' => [
			'title' => [
				'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
				'fields' => [
					'title_text' => [
						'type' => 'text',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_TITLE'),
						'std' => 'Comments',
					],
					'title_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
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
					'title_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'       => '#000000',
					],
					'title_shadow' => [
						'type' => 'boxshadow',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
					],
					'title_alignment' => [
						'type' => 'buttons',
						'title' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_ALIGN"),
						'std'        => ['xl' => 'flex-start', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
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
					'title_separator' => [
						'type' => 'separator',
					],
					'comment_count' => [
						'type' => 'checkbox',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_COUNT'),
						'std' => '1',
					],
					'comment_count_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'comment_count_font_family',
							'size'           => 'comment_count_fontsize',
							'line_height'    => 'comment_count_lineheight',
							'letter_spacing' => 'comment_count_letterspace',
							'uppercase'      => 'comment_count_font_style.uppercase',
							'italic'         => 'comment_count_font_style.italic',
							'underline'      => 'comment_count_font_style.underline',
							'weight'         => 'comment_count_font_style.weight',
						],
						'depends' => [
							'comment_count' => '1',
						],
					],
					'comment_count_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'       => '#7A7C85',
						'depends' => [
							'comment_count' => '1',
						],
					],
					'comment_count_shadow' => [
						'type' => 'boxshadow',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
						'depends' => [
							'comment_count' => '1',
						],
					],
					'comment_count_position' =>[
						'type' => 'buttons',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_COUNT_POSITION'),
						'values' => [
							[
								'label' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_COUNT_POSITION_LEFT'),
								'value' => 'left'
							],
							[
								'label' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_COUNT_POSITION_RIGHT'),
								'value' => 'right'
							],
						],
						'depends' => [
							'comment_count' => '1',
						],
						'std' => 'right',
					],
				],
			],

			'label' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_LABEL'),
				'fields' => [
					'enable_label' => [
						'type'      => 'checkbox',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_LABEL'),
						'values'    => [
							'0' => 'No',
							'1' => 'Yes',
						],
						'std'       => '1',
						'is_header' => true,
					],
					'label_text' => [
						'type' => 'text',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_LABEL'),
						'std' => 'Leave a comment',
						'depends' => [
							'enable_label' => '1',
						],
					],
					'label_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'       => '#000000',
						'depends' => [
							'enable_label' => '1',
						],
					],
					'label_text_shadow' => [
						'type' => 'boxshadow',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
						'depends' => [
							'enable_label' => '1',
						],
					],
					'label_alignment' => [
						'type' => 'buttons',
						'title' => Text::_("COM_SPPAGEBUILDER_ADDON_DISPLAY_FLEX_ALIGN"),
						'std'        => ['xl' => 'flex-start', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
						'depends' => [
							'enable_label' => '1',
						],
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
				],
			],

			'comment_field' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD'),
				'fields' => [
					'comment_field_placeholder' => [
						'type' => 'text',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_PLACEHOLDER'),
						'std' => 'Share your thoughts...',
					],
					'comment_field_height' => [
						'type' => 'slider',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_HEIGHT'),
						'std' => 88,
						'min' => 50,
						'max' => 500,
						'step' => 1,
					],
					'comment_field_padding' => [
						'type' => 'padding',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
						'std' => '16px 16px 16px 16px',
					],
					'comment_field_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'comment_field_font_family',
							'size'           => 'comment_field_fontsize',
							'line_height'    => 'comment_field_lineheight',
							'letter_spacing' => 'comment_field_letterspace',
							'uppercase'      => 'comment_field_font_style.uppercase',
							'italic'         => 'comment_field_font_style.italic',
							'underline'      => 'comment_field_font_style.underline',
							'weight'         => 'comment_field_font_style.weight',
						],
					],
					'comment_field_separator' => [
						'type' => 'separator',
					],
					'comment_field_placeholder_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_PLACEHOLDER_COLOR'),
						'std'       => '#646E8F',
					],
					'comment_field_typing_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_TYPING_COLOR'),
						'std'       => '#000000',
					],
					'comment_field_background_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_BACKGROUND_COLOR'),
						'std'       => '#FFFFFF',
					],
					'comment_field_background_hover_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_BACKGROUND_HOVER_COLOR'),
						'std'       => '#FFFFFF',
					],
					'comment_field_placeholder_hover_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_PLACEHOLDER_HOVER_COLOR'),
						'std'       => '#7A7C85',
					],
					'comment_field_border_separator' => [
						'type' => 'separator',
					],
					'comment_field_border_width' => [
						'type' => 'padding',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_BORDER_WIDTH'),
						'std' => '1px 1px 1px 1px',
					],
					'comment_field_border_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_BORDER_COLOR'),
						'std'       => '#D3D7EB',
					],
					'comment_field_border_focus_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_FIELD_BORDER_FOCUS_COLOR'),
						'std'       => '#3366FF',
					],
				],
			],

			'post_button' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_POST_BUTTON'),
				'fields' => [
					'post_button_text' => [
						'type' => 'text',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT'),
						'desc' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_TEXT_DESC'),
						'inline' => true,
						'std'  => 'Leave a comment',
					],
					'post_button_aria_label' => [
						'type' => 'text',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ARIA_LABEL'),
						'desc' => Text::_('COM_SPPAGEBUILDER_GLOBAL_ARIA_LABEL_DESC'),
						'std'  => ''
                	],
					'post_button_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'post_button_font_family',
							'size'           => 'post_button_fontsize',
							'line_height'    => 'post_button_lineheight',
							'letter_spacing' => 'post_button_letterspace',
							'uppercase'      => 'post_button_font_style.uppercase',
							'italic'         => 'post_button_font_style.italic',
							'underline'      => 'post_button_font_style.underline',
							'weight'         => 'post_button_font_style.weight',
						],
					],
					'enable_anonymous_comment' => [
						'type'      => 'checkbox',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_ENABLE_ANONYMOUS_COMMENT'),
						'desc'      => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_ENABLE_ANONYMOUS_COMMENT_DESC'),
						'std'       => 1,
					],
					'post_button_type' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_STYLE'),
						'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_STYLE_DESC'),
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
							'custom'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
						],
						'std'    => 'default',
					],
					'post_button_link_padding_bottom' => [
						'type'    => 'slider',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_PADDING_BOTTOM'),
						'max'     => 100,
						'depends' => [['post_button_type', '=', 'link']],
					],
					'post_button_appearance' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE'),
						'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_DESC'),
						'values' => [
							''         => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_FLAT'),
							'gradient' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_GRADIENT'),
							'outline'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_OUTLINE'),
						],
						'std'     => '',
						'depends' => [['post_button_type', '!=', 'link']],
					],
					'post_button_shape' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE'),
						'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_DESC'),
						'values' => [
							'rounded' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUNDED'),
							'square'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_SQUARE'),
							'round'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUND'),
						],
						'std'   => 'rounded',
						'depends' => [['post_button_type', '!=', 'link']],
					],
					'post_button_size' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE'),
						'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DESC'),
						'values' => [
							''       => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DEFAULT'),
							'lg'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_LARGE'),
							'xlg'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_XLARGE'),
							'sm'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_SMALL'),
							'xs'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_EXTRA_SAMLL'),
							'custom' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
						],
					],
					'post_button_padding' => [
						'type'       => 'padding',
						'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_PADDING'),
						'std'        => '',
						'responsive' => true,
						'depends'    => [['post_button_size', '=', 'custom']],
                	],
					'post_button_block' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_BLOCK'),
						'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_BLOCK_DESC'),
						'values' => [
							''               => Text::_('JNO'),
							'sppb-btn-block' => Text::_('JYES'),
						],
						'depends' => [['post_button_type', '!=', 'link']],
					],
					'post_button_alignment' => [
						'type'              => 'alignment',
						'title'             => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
						'responsive'        => true,
						'std'               => [
							'xl' => 'flex-end',
							'lg' => '',
							'md' => '',
							'sm' => '',
							'xs' => '',
						]
					],
					'post_button_icon' => [
						'type'  => 'icon',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_ICON'),
					],
					'post_button_icon_position' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_ICON_POSITION'),
						'values' => [
							'left'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_LEFT'),
							'right' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RIGHT'),
						],
						'std' => 'left',
					],
					'post_button_icon_margin' => [
						'type'       => 'margin',
						'title'      => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_ICON_MARGIN'),
						'responsive' => true,
						'std'        => ['xl' => '0px 0px 0px 0px', 'lg' => '', 'md' => '', 'sm' => '', 'xs' => ''],
					],
					'post_button_style_state' => [
						'type'   => 'radio',
						'values' => [
							'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
							'hover' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
						],
						'std' => 'normal',
                	],
					'post_button_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'    => '#FFFFFF',
						'depends' => [['post_button_style_state', '=', 'normal'], ['post_button_type', '=', 'custom']],
					],
					'post_button_color_hover' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'    => '#FFFFFF',
						'depends' => [['post_button_style_state', '=', 'hover'], ['post_button_type', '=', 'custom']],
					],
					'post_button_background_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
						'std'    => '#3366FF',
						'depends' => [
							['post_button_style_state', '=', 'normal'],
							['post_button_appearance', '!=', 'gradient'],
							['post_button_type', '=', 'custom'],
						],
					],
					'post_button_background_color_hover' => [
						'type'    => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
						'std'     => '#0037DD',
						'depends' => [
							['post_button_style_state', '=', 'hover'],
							['post_button_appearance', '!=', 'gradient'],
							['post_button_type', '=', 'custom'],
						],
					],
					'post_button_background_gradient' => [
						'type' => 'gradient',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
						'std'  => [
							"color"  => "#3366FF",
							"color2" => "#0037DD",
							"deg"    => "45",
							"type"   => "linear"
						],
						'depends' => [
							['post_button_style_state', '=', 'normal'],
							['post_button_appearance', '=', 'gradient'],
						],
					],
					'post_button_background_gradient_hover' => [
						'type'  => 'gradient',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
						'std'   => [
							"color"  => "#0037DD",
							"color2" => "#3366FF",
							"deg"    => "45",
							"type"   => "linear"
						],
						'depends' => [
							['post_button_style_state', '=', 'hover'],
							['post_button_appearance', '=', 'gradient'],
						],
					],
					'post_button_link_style_state' => [
						'type'   => 'radio',
						'values' => [
							'normal' => Text::_('Normal'),
							'hover' => Text::_('Hover'),
						],
						'std' => 'normal',
						'depends' => [['post_button_type', '=', 'link']],
					],
					'post_button_link_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'    => '#3366FF',
						'depends' => [
							['post_button_link_style_state', '=', 'normal'],
							['post_button_type', '=', 'link'],
						],
					],
					'post_button_link_border_width' => [
						'type'    => 'slider',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_WIDTH'),
						'max'     => 10,
						'std'     => 1,
						'depends' => [
							['post_button_link_style_state', '=', 'normal'],
							['post_button_type', '=', 'link'],
						],
					],
					'post_button_link_border_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_COLOR'),
						'std'    => '#3366FF',
						'depends' => [
							['post_button_link_style_state', '=', 'normal'],
							['post_button_type', '=', 'link'],
						],
					],
					'post_button_link_hover_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'    => '#0037DD',
						'depends' => [
							['post_button_link_style_state', '=', 'hover'],
							['post_button_type', '=', 'link'],
						],
					],
					'post_button_link_border_hover_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BORDER_COLOR'),
						'std'    => '#0037DD',
						'depends' => [
							['post_button_link_style_state', '=', 'hover'],
							['post_button_type', '=', 'link'],
						],
					],
				],
			],

			'edit_comment' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_EDIT_COMMENT'),
				'fields' => [
					'edit_comment_button_item' => [
						'type'	=> 'repeatable',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_EDIT_COMMENT_BUTTON'),
						'fixed' => true,
						'std' 	=> [
							['title' => 'Cancel'],
							['title' => 'Update'],
						],
						'attr'  => [
							'title' => [
								'type'  => 'text',
								'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_BUTTON_ITEM_TITLE'),
								'disabled' => true,
							],
							'edit_comment_button_typography' => [
								'type'      => 'typography',
								'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
								'fallbacks' => [
									'font'           => 'edit_comment_button_font_family',
									'size'           => 'edit_comment_button_fontsize',
									'line_height'    => 'edit_comment_button_lineheight',
									'letter_spacing' => 'edit_comment_button_letterspace',
									'uppercase'      => 'edit_comment_button_font_style.uppercase',
									'italic'         => 'edit_comment_button_font_style.italic',
									'underline'      => 'edit_comment_button_font_style.underline',
									'weight'         => 'edit_comment_button_font_style.weight',
								],
							],
							'edit_comment_button_appearance' => [
								'type'   => 'select',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE'),
								'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_DESC'),
								'values' => [
									''         => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_FLAT'),
									'gradient' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_GRADIENT'),
									'outline'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_OUTLINE'),
								],
								'std'     => '',
							],
							'edit_comment_button_shape' => [
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
							'edit_comment_button_size' => [
								'type'   => 'select',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE'),
								'desc'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DESC'),
								'values' => [
									''       => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DEFAULT'),
									'lg'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_LARGE'),
									'xlg'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_XLARGE'),
									'sm'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_SMALL'),
									'xs'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_EXTRA_SAMLL'),
									'custom' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
								],
							],
							'edit_comment_button_separator' => [
								'type' => 'separator',
							],
							'edit_comment_button_style' =>[
								'type'   => 'radio',
								'title'  => Text::_('COM_SPPAGEBUILDER_COMMENT_BUTTON_STYLE'),
								'values' => [
									'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
									'hover' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
								],
								'std' => 'normal',
							],
							'edit_comment_button_color' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
								'std'    => '#FFFFFF',
								'depends' => [['edit_comment_button_style', '=', 'normal']],
							],
							'edit_comment_button_color_hover' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
								'std'    => '#FFFFFF',
								'depends' => [['edit_comment_button_style', '=', 'hover']],
							],
							'edit_comment_button_background_color' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'    => '#3366FF',
								'depends' => [
									['edit_comment_button_style', '=', 'normal'],
									['edit_comment_button_appearance', '!=', 'gradient'],
								],
							],
							'edit_comment_button_background_color_hover' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'    => '#0037DD',
								'depends' => [
									['edit_comment_button_style', '=', 'hover'],
									['edit_comment_button_appearance', '!=', 'gradient'],
								],
							],
							'edit_comment_button_background_gradient' => [
								'type' => 'gradient',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'  => [
									"color"  => "#3366FF",
									"color2" => "#0037DD",
									"deg"    => "45",
									"type"   => "linear"
								],
								'depends' => [
									['edit_comment_button_style', '=', 'normal'],
									['edit_comment_button_appearance', '=', 'gradient'],
								],
							],
							'edit_comment_button_background_gradient_hover' => [
								'type'  => 'gradient',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'   => [
									"color"  => "#0037DD",
									"color2" => "#3366FF",
									"deg"    => "45",
									"type"   => "linear"
								],
								'depends' => [
									['edit_comment_button_style', '=', 'hover'],
									['edit_comment_button_appearance', '=', 'gradient'],
								],
							],
						],
					],
					'edit_comment_button_gap' => [
						'type' => 'slider',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_GAP'),
						'std' => 10,
						'min' => 0,
						'max' => 100,
					],
					'edit_comment_button_alignment' => [
						'type'              => 'alignment',
						'title'             => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
						'responsive'        => true,
						'std'               => [
							'xl' => 'flex-end',
							'lg' => '',
							'md' => '',
							'sm' => '',
							'xs' => '',
						]
					],
				],
			],

			'commentator_name' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENTATOR_NAME'),
				'fields' => [
					'commentator_name_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'commentator_name_font_family',
							'size'           => 'commentator_name_fontsize',
							'line_height'    => 'commentator_name_lineheight',
							'letter_spacing' => 'commentator_name_letterspace',
							'uppercase'      => 'commentator_name_font_style.uppercase',
							'italic'         => 'commentator_name_font_style.italic',
							'underline'      => 'commentator_name_font_style.underline',
							'weight'         => 'commentator_name_font_style.weight',
						],
					],
					'commentator_name_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'       => '#000000',
					],
					'commentator_avatar_color' => [
						'type'      => 'color',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_AVATAR_COLOR'),
						'std'       => '#4285F4',
					],

					'commentator_name_text_shadow' => [
						'type'      => 'boxshadow',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
					],
					'commentator_name_alignment' => [
						'type'              => 'alignment',
						'title'             => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
					],
				],
			],

			'time' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_TIME'),
				'fields' => [
					'enable_time' => [
						'type'      => 'checkbox',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_TIME_ENABLE'),
						'std'       => 1,
					],
					'time_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'time_font_family',
							'size'           => 'time_fontsize',
							'line_height'    => 'time_lineheight',
							'letter_spacing' => 'time_letterspace',
							'uppercase'      => 'time_font_style.uppercase',
							'italic'         => 'time_font_style.italic',
							'underline'      => 'time_font_style.underline',
							'weight'         => 'time_font_style.weight',
						],
						'depends' => [
							'enable_time' => 1,
						],
					],
					'time_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'     => '#7A7C85',
						'depends' => [
							'enable_time' => 1,
						],
					],
					'time_text_shadow' => [
						'type'    => 'boxshadow',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
						'depends' => [
							'enable_time' => 1,
						],
					],
					'time_alignment' => [
						'type'    => 'alignment',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
						'available_options' => ['left', 'center', 'right'],
						'responsive' => true,
						'depends' => [
							'enable_time' => 1,
						],
					],
					'time_separator' => [
						'type' => 'separator',
					],
					'show_edited_time' => [
						'type'      => 'checkbox',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_SHOW_EDITED_TIME'),
						'std'       => 1,
						'depends'   => [
							'enable_time' => 1,
						],
					],
					'edited_time_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'edited_time_font_family',
							'size'           => 'edited_time_fontsize',
							'line_height'    => 'edited_time_lineheight',
							'letter_spacing' => 'edited_time_letterspace',
							'uppercase'      => 'edited_time_font_style.uppercase',
							'italic'         => 'edited_time_font_style.italic',
							'underline'      => 'edited_time_font_style.underline',
							'weight'         => 'edited_time_font_style.weight',
						],
						'depends' => [
							'enable_time' => 1,
							'show_edited_time' => 1,
						],
					],
					'edited_time_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'     => '#7A7C85',
						'depends' => [
							'enable_time' => 1,
							'show_edited_time' => 1,
						],
					],
					'edited_time_text_shadow' => [
						'type'    => 'boxshadow',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
						'depends' => [
							'enable_time' => 1,
							'show_edited_time' => 1,
						],
					],
					'edited_time_alignment' => [
						'type'    => 'alignment',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
						'available_options' => ['left', 'center', 'right'],
						'responsive' => true,
						'depends' => [
							'enable_time' => 1,
							'show_edited_time' => 1,
						],
					],
				],
			],

			'posted_comment' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_POSTED_COMMENT'),
				'fields' => [
					'posted_comment_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'posted_comment_font_family',
							'size'           => 'posted_comment_fontsize',
							'line_height'    => 'posted_comment_lineheight',
							'letter_spacing' => 'posted_comment_letterspace',
							'uppercase'      => 'posted_comment_font_style.uppercase',
							'italic'         => 'posted_comment_font_style.italic',
							'underline'      => 'posted_comment_font_style.underline',
							'weight'         => 'posted_comment_font_style.weight',
						],
					],
					'posted_comment_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'     => '#484F66',
					],
					'posted_comment_text_shadow' => [
						'type'    => 'boxshadow',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
					],
				],
			],

			'ellipsis_action' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_ELLIPSIS_ACTION'),
				'fields' => [
					'ellipsis_button_style' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_STYLE'),
						'values' => [
							'custom'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
							'default'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_DEFAULT'),
							'primary'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_PRIMARY'),
							'secondary'=> Text::_('COM_SPPAGEBUILDER_GLOBAL_SECONDARY'),
						],
						'std'    => 'custom',
					],
					'ellipsis_appearance' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE'),
						'values' => [
							'flat'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_FLAT'),
							'outline'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_OUTLINE'),
						],
						'std'    => 'flat',
					],
					'ellipsis_shape' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE'),
						'values' => [
							'rounded' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUNDED'),
							'square'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_SQUARE'),
							'round'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUND'),
						],
						'std'   => 'rounded',
					],
					'ellipsis_size' => [
						'type'   => 'select',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE'),
						'values' => [
							''       => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DEFAULT'),
							'lg'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_LARGE'),
							'xlg'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_XLARGE'),
							'sm'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_SMALL'),
							'xs'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_EXTRA_SAMLL'),
							'custom' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
						],
						'std'   => '',
					],
					'ellipsis_margin' => [
						'type'  => 'margin',
						'title' => Text::_('COM_SPPAGEBUILDER_GLOBAL_MARGIN'),
						'responsive' => true,
					],
					'ellipsis_position' => [
						'type'    => 'alignment',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ALIGNMENT'),
						'std'     => 'right',
						'available_options' => ['left', 'center', 'right'],
						'responsive' => true,
					],
					'ellipsis_style_state' => [
						'type'   => 'radio',
						'values' => [
							'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
							'hover'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
						],
						'std' => 'normal',
					],
					'ellipsis_icon_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON_COLOR'),
						'std'    => '#6F7CA3',
						'depends' => [['ellipsis_style_state', '=', 'normal']],
					],
					'ellipsis_icon_bg_color' => [
						'type'   => 'color',
						'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON_BG_COLOR'),
						'depends' => [['ellipsis_style_state', '=', 'normal']],
					],
					'ellipsis_action_button_item' => [
						'type'  => 'repeatable',
						'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_ELLIPSIS_ACTION_BUTTON'),
						'fixed' => true,
						'std'  => [
							['title' => 'Edit'],
							['title' => 'Delete'],
						],
						'attr'  => [
							'title' => [
								'type'  => 'text',
								'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_BUTTON_ITEM_TITLE'),
								'disabled' => true,
							],
							'ellipsis_action_button_typography' => [
								'type'      => 'typography',
								'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
								'fallbacks' => [
									'font'           => 'ellipsis_action_button_font_family',
									'size'           => 'ellipsis_action_button_fontsize',
									'line_height'    => 'ellipsis_action_button_lineheight',
									'letter_spacing' => 'ellipsis_action_button_letterspace',
									'uppercase'      => 'ellipsis_action_button_font_style.uppercase',
									'italic'         => 'ellipsis_action_button_font_style.italic',
									'underline'      => 'ellipsis_action_button_font_style.underline',
									'weight'         => 'ellipsis_action_button_font_style.weight',
								],
							],
							'ellipsis_action_button_appearance' => [
								'type'   => 'select',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE'),
								'values' => [
									''         => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_FLAT'),
									'gradient' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_GRADIENT'),
									'outline'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_APPEARANCE_OUTLINE'),
								],
								'std'     => '',
							],
							'ellipsis_action_button_shape' => [
								'type'   => 'select',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE'),
								'values' => [
									'rounded' => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUNDED'),
									'square'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_SQUARE'),
									'round'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SHAPE_ROUND'),
								],
								'std'   => 'rounded',
							],
							'ellipsis_action_button_size' => [
								'type'   => 'select',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE'),
								'values' => [
									''       => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_DEFAULT'),
									'lg'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_LARGE'),
									'xlg'    => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_XLARGE'),
									'sm'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_SMALL'),
									'xs'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_BUTTON_SIZE_EXTRA_SAMLL'),
									'custom' => Text::_('COM_SPPAGEBUILDER_GLOBAL_CUSTOM'),
								],
							],
							'ellipsis_action_button_separator' => [
								'type' => 'separator',
							],
							'ellipsis_action_button_style' =>[
								'type'   => 'radio',
								'title'  => Text::_('COM_SPPAGEBUILDER_COMMENT_BUTTON_STYLE'),
								'values' => [
									'normal' => Text::_('COM_SPPAGEBUILDER_GLOBAL_NORMAL'),
									'hover' => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER'),
								],
								'std' => 'normal',
							],
							'ellipsis_action_button_color' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
								'std'    => '#FFFFFF',
								'depends' => [['ellipsis_action_button_style', '=', 'normal']],
							],
							'ellipsis_action_button_color_hover' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
								'std'    => '#FFFFFF',
								'depends' => [['ellipsis_action_button_style', '=', 'hover']],
							],
							'ellipsis_action_button_background_color' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'    => '#3366FF',
								'depends' => [
									['ellipsis_action_button_style', '=', 'normal'],
									['ellipsis_action_button_appearance', '!=', 'gradient'],
								],
							],
							'ellipsis_action_button_background_color_hover' => [
								'type'   => 'color',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'    => '#0037DD',
								'depends' => [
									['ellipsis_action_button_style', '=', 'hover'],
									['ellipsis_action_button_appearance', '!=', 'gradient'],
								],
							],
							'ellipsis_action_button_background_gradient' => [
								'type' => 'gradient',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'  => [
									"color"  => "#3366FF",
									"color2" => "#0037DD",
									"deg"    => "45",
									"type"   => "linear"
								],
								'depends' => [
									['ellipsis_action_button_style', '=', 'normal'],
									['ellipsis_action_button_appearance', '=', 'gradient'],
								],
							],
							'ellipsis_action_button_background_gradient_hover' => [
								'type'  => 'gradient',
								'title'  => Text::_('COM_SPPAGEBUILDER_GLOBAL_BACKGROUND_COLOR'),
								'std'   => [
									"color"  => "#0037DD",
									"color2" => "#3366FF",
									"deg"    => "45",
									"type"   => "linear"
								],
								'depends' => [
									['ellipsis_action_button_style', '=', 'hover'],
									['ellipsis_action_button_appearance', '=', 'gradient'],
								],
							],
						],
					],
				],
			],

			'likes' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_LIKES'),
				'fields' => [
					'enable_likes' => [
						'type'      => 'checkbox',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_LIKES_ENABLE'),
						'values'    => [
							'0' => 'No',
							'1' => 'Yes',
						],
						'std'       => '1',
						'is_header' => true,
					],
					'likes_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'likes_font_family',
							'size'           => 'likes_fontsize',
							'line_height'    => 'likes_lineheight',
							'letter_spacing' => 'likes_letterspace',
							'uppercase'      => 'likes_font_style.uppercase',
							'italic'         => 'likes_font_style.italic',
							'underline'      => 'likes_font_style.underline',
							'weight'         => 'likes_font_style.weight',
						],
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_text_shadow' => [
						'type'    => 'boxshadow',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_separator_start' => [
						'type' => 'separator',
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'     => '#7A7C85',
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_hover_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER_COLOR'),
						'std'     => '#343D57',
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_focused_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_FOCUSED_COLOR'),
						'std'     => '#EE563D',
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_separator_end' => [
						'type' => 'separator',
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_icon' => [
						'type'    => 'icon',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
						'std'     => '',
						'depends' => [
							'enable_likes' => '1',
						],
					],
					'likes_icon_position' => [
						'type'    => 'buttons',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON_POSITION'),
						'values'  => [
							[
								'label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LEFT'),
								'value' => 'left'
							],
							[
								'label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RIGHT'),
								'value' => 'right'
							],
						],
						'std'     => 'left',
						'depends' => [
							'enable_likes' => '1',
						],
					],
				],
			],

			'reply' => [
				'title' => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_REPLY'),
				'fields' => [
					'enable_reply' => [
						'type'      => 'checkbox',
						'title'     => Text::_('COM_SPPAGEBUILDER_ADDON_COMMENT_REPLY_ENABLE'),
						'values'    => [
							'0' => 'No',
							'1' => 'Yes',
						],
						'std'       => '1',
						'is_header' => true,
					],
					'reply_typography' => [
						'type'      => 'typography',
						'title'     => Text::_('COM_SPPAGEBUILDER_GLOBAL_TYPOGRAPHY'),
						'fallbacks' => [
							'font'           => 'reply_font_family',
							'size'           => 'reply_fontsize',
							'line_height'    => 'reply_lineheight',
							'letter_spacing' => 'reply_letterspace',
							'uppercase'      => 'reply_font_style.uppercase',
							'italic'         => 'reply_font_style.italic',
							'underline'      => 'reply_font_style.underline',
							'weight'         => 'reply_font_style.weight',
						],
						'depends' => [
							'enable_reply' => '1',
						],
					],
					'reply_text_shadow' => [
						'type'    => 'boxshadow',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_TEXT_SHADOW'),
						'depends' => [
							'enable_reply' => '1',
						],
					],
					'reply_separator_start' => [
						'type' => 'separator',
						'depends' => [
							'enable_reply' => '1',
						],
					],
					'reply_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_COLOR'),
						'std'     => '#7A7C85',
						'depends' => [
							'enable_reply' => '1',
						],
					],
					'reply_hover_color' => [
						'type'    => 'color',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_HOVER_COLOR'),
						'std'     => '#343D57',
						'depends' => [
							'enable_reply' => '1',
						],
					],
					'reply_separator_end' => [
						'type' => 'separator',
						'depends' => [
							'enable_reply' => '1',
						],
					],
					'reply_icon' => [
						'type'    => 'icon',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON'),
						'std'     => '',
						'depends' => [
							'enable_reply' => '1',
						],
					],
					'reply_icon_position' => [
						'type'    => 'buttons',
						'title'   => Text::_('COM_SPPAGEBUILDER_GLOBAL_ICON_POSITION'),
						'values'  => [
							[
								'label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_LEFT'),
								'value' => 'left'
							],
							[
								'label' => Text::_('COM_SPPAGEBUILDER_GLOBAL_RIGHT'),
								'value' => 'right'
							],
						],
						'std'     => 'left',
						'depends' => [
							'enable_reply' => '1',
						],
					],
				],
			],
		],
	]
);