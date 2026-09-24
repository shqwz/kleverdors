<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;

class SppagebuilderAddonDynamic_content_text extends SppagebuilderAddons
{

    function autocorrect_html(string $html): string {
        if (empty($html)) {
            return '';
        }

        libxml_use_internal_errors(true);
    
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
        libxml_clear_errors();
    
        return $dom->saveHTML();
    }

    function renderRatingStars($rating, $attribute, $settings) {
        if (empty($rating) || !is_numeric($rating)) {
            return '';
        }

        $rating = floatval($rating);
        $maxRating = !empty($settings->rating_max_length) ? intval($settings->rating_max_length) : null;
        $maxRating = $maxRating ?? ceil($rating);
        $isInteger = isset($attribute->number_format) && $attribute->number_format === 'integer';
        
        // Check if custom icon is selected
        $customIcon = isset($settings->rating_icon) && !empty($settings->rating_icon) ? $settings->rating_icon : null;
        
        $output = '<div class="sppb-rating-stars">';
        
        for ($i = 1; $i <= $maxRating; $i++) {
            $isFilled = $rating >= $i;
            $isHalfFilled = !$isInteger && $rating >= ($i - 0.5) && $rating < $i;
            
            $starClass = 'sppb-rating-star';
            if ($isFilled) {
                $starClass .= ' sppb-rating-star-filled';
            } elseif ($isHalfFilled) {
                $starClass .= ' sppb-rating-star-half';
            }
            
            if ($customIcon) {
                if ($isHalfFilled) {
                    $starClass = 'sppb-rating-star sppb-rating-custom-half';
                    $output .= '<span class="' . $starClass . '"><i class="' . $customIcon . '"></i><i class="' . $customIcon . ' half-overlay"></i></span>';
                } else {
                    $output .= '<span class="' . $starClass . '"><i class="' . $customIcon . '"></i></span>';
                }
            } else {
                $output .= '<span class="' . $starClass . '">★</span>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }

    public function render()
    {        
        $settings = $this->addon->settings;
        $selector = isset($settings->selector) ? $settings->selector : 'p';
        $isDownloadable = isset($settings->is_downloadable) ? $settings->is_downloadable : 0;
        $fileValueOverride = !empty($settings->file_value_override) ? $settings->file_value_override : null;
        $defaultContent = $settings->default_text ?? '';
        $class = $settings->class ?? '';
        $collectionId = isset($settings->dynamic_item['collection_id']) ? $settings->dynamic_item['collection_id'] : null;

        $input = Factory::getApplication()->input;
        $collectionType = $input->get('collection_type') ?? 'normal-source';

        // If the dynamic content value is not come from the parent collection addon,
        // that means this addon placed into a detail page, so we need to get the data from the detail page.
        if (empty($settings->dynamic_item) && $collectionType === 'articles') {
            $settings->dynamic_item = CollectionHelper::getDetailPageDataFromArticles();
        } else if (empty($settings->dynamic_item) && $collectionType === 'tags') {
            $settings->dynamic_item = CollectionHelper::getDetailPageDataFromTags();
        } else if (empty($settings->dynamic_item)) {
            $settings->dynamic_item = CollectionHelper::getDetailPageData();
        }

        if (is_object($settings->dynamic_item)) {
            $settings->dynamic_item = json_decode(json_encode($settings->dynamic_item), true);
        }

        if (empty($settings->dynamic_item) || empty($settings->attribute)) {
            $content = $defaultContent ? $defaultContent : Text::_('COM_SPPAGEBUILDER_DYNAMIC_CONTENT_TEXT_NO_DATA');
            return '<' . $selector . ' class="sppb-dynamic-content-text"' . '>' . $content . '</' . $selector . '>';
        }

        $linkAttributes = [
            'href' => '',
            'target' => '',
            'rel' => '',
            'has_link' => false,
        ];

        $link = $settings->link ?? null;
        $hasLink = false;

        $hasDynamicLink = isset($settings->dynamic_link_switch) && $settings->dynamic_link_switch ? true : false;
        $dynamicLink = isset($settings->dynamic_link) ? $settings->dynamic_link : null;
        $dynamicLinkUrl = CollectionHelper::getDynamicContentData($dynamicLink, $settings->dynamic_item);

        if (!empty($link)) {
            $linkOptions = [
                'url' => CollectionHelper::createDynamicContentLink(
                    $link, CollectionHelper::prepareItemForLink($settings->dynamic_item, $settings->attribute)
                ),
                'target' => $link->new_tab ? '_blank' : null,
                'nofollow' => $link->nofollow ?? null,
                'noreferrer' => $link->noreferrer ?? null,
                'noopener' => $link->noopener ?? null,
            ];
            $linkAttributes = CollectionHelper::generateLinkAttributes($linkOptions);
        }

        $content = CollectionHelper::getDynamicContentData($settings->attribute, $settings->dynamic_item) ?? '';

        if (isset($settings->dynamic_item['collection_id']) && ($settings->dynamic_item['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID || $settings->dynamic_item['collection_id'] === CollectionIds::TAGS_COLLECTION_ID)) {
            $content = $settings->dynamic_item[$settings->attribute->path];
        }

        $attributeType = $settings->attribute->type ?? 'text';

        if ($attributeType === FieldTypes::DATETIME) {
            $content = CollectionHelper::formatDate($content, $settings->attribute);
        } elseif ($attributeType === FieldTypes::RATING) {
            $content = $this->renderRatingStars($content, $settings->attribute, $settings);
        } elseif ($attributeType === FieldTypes::LINK) {
            $linkOptions = [
                'url' => $content ?? null,
                'target' => isset($settings->attribute->link) ? ($settings->attribute->link->target ?? null) : null,
                'nofollow' => isset($settings->attribute->link) ? ($settings->attribute->link->nofollow ?? null) : null,
                'noreferrer' => isset($settings->attribute->link) ? ($settings->attribute->link->noreferrer ?? null) : null,
                'noopener' => isset($settings->attribute->link) ? ($settings->attribute->link->noopener ?? null) : null,
            ];
            $linkAttributes = CollectionHelper::generateLinkAttributes($linkOptions);
            $content = (isset($settings->attribute->link) && $settings->attribute->link->text) ? $settings->attribute->link->text : $linkAttributes['href'];
        }

        if (empty(strip_tags($content)) && $attributeType !== FieldTypes::RATING) {
            return '';
        }

        $output = '';
        $hasLink = $linkAttributes['has_link'] ?? false;

        $downloadAction = $isDownloadable ? ' download="' . $content . '"' : '';

        if ($hasLink && !$hasDynamicLink) {
            $linkUrl = $linkAttributes['href'] ?? '/';
            $attributes = $linkAttributes['target'] ? ' target="' . $linkAttributes['target'] . '"' : '';
            $attributes .= $linkAttributes['rel'] ? ' rel="' . $linkAttributes['rel'] . '"' : '';
            $app = Factory::getApplication();
            $option = $app->input->get('option', '', 'string');
            $view = $app->input->get('view', '', 'string');
            if ($option === 'com_content' && ($view === 'category' || $view === 'archive' || $view === 'featured' || $view === 'article') && !empty($settings->dynamic_item['link'])) {
                $linkUrl = $settings->dynamic_item['link'];
            }
            $output .= '<a href="' . $linkUrl . '" class="sppb-dynamic-content-text__link" data-preload-collection ' . $attributes . ' >';
        }

        if($hasDynamicLink && $dynamicLink){
            $dynamicLinkOptions = [
                'url' => $dynamicLinkUrl ?? null,
                'target' => isset($dynamicLink->link) ? ($dynamicLink->link->target ?? null) : null,
                'nofollow' => isset($dynamicLink->link) ? ($dynamicLink->link->nofollow ?? null) : null,
                'noreferrer' => isset($dynamicLink->link) ? ($dynamicLink->link->noreferrer ?? null) : null,
                'noopener' => isset($dynamicLink->link) ? ($dynamicLink->link->noopener ?? null) : null,
            ];

            $dynamicLinkAttributes = CollectionHelper::generateLinkAttributes($dynamicLinkOptions);
            $dynamicAttributes = $dynamicLinkAttributes['target'] ? ' target="' . $dynamicLinkAttributes['target'] . '"' : '';
            $dynamicAttributes .= $dynamicLinkAttributes['rel'] ? ' rel="' . $dynamicLinkAttributes['rel'] . '"' : '';
            $dynamicUrl = $dynamicLinkAttributes['href'] ?? '/';
               
            $output .= '<a href="' . $dynamicUrl . '" class="sppb-dynamic-content-text__link" data-preload-collection ' . $dynamicAttributes . ' >';
        }

        if ($isDownloadable && $attributeType === FieldTypes::FILE) {
            $linkUrl = '/' . $content;
            $attributes = '';
            $attributes .= $linkAttributes['rel'] ? ' rel="' . $linkAttributes['rel'] . '"' : '';
            $output .= '<a href="' . $linkUrl . '" class="sppb-dynamic-content-file__link" data-preload-collection ' . $attributes . $downloadAction . '" >';
        }

        $icon = $settings->icon ?? null;

        if (!empty($fileValueOverride) && $attributeType === FieldTypes::FILE) {
            $content = $fileValueOverride;
        }

        $classNames = 'sppb-dynamic-content-text';

        if ($attributeType === FieldTypes::RICH_TEXT) {
            $selector = 'div';
            $content = '<div class="sppb-dynamic-content__is-rich-text">' . $this->autocorrect_html($content) . '</div>';
        }

        if ($attributeType === FieldTypes::RATING) {
            $selector = 'div';
        }

        if (!empty($icon) && !empty(strip_tags($content))) {
            $iconPosition = $settings->icon_position ?? 'left';
            $iconContent = '<i class="sppb-dynamic-content-text__icon ' . $icon . '"></i>';

            if ($iconPosition === 'left') {
                $content = $iconContent . $content;
            } else {
                $content = $content . $iconContent;
            }
        }

        if (!empty($class)) {
            $classNames .= ' ' . $class;
        }
        
        $output .= '<' . $selector . ' class="' . $classNames . '"' . '>' . $content . '</' . $selector . '>';

        if ($isDownloadable && $attributeType === FieldTypes::FILE) {
            $output .= '</a>';
        }

        if ($hasLink) {
            $output .= '</a>';
        }

        return $output;
    }

    public function css()
    {
        $css = '';

        $addon_id = '#sppb-addon-' . $this->addon->id;
        $settings = $this->addon->settings;
        $cssHelper = new CSSHelper($addon_id);
        $settings->title_text_shadow = CSSHelper::parseBoxShadow($settings, 'title_text_shadow', true);

        $css .= $cssHelper->generateStyle('.sppb-dynamic-content-text, .sppb-dynamic-content-text a', $settings, [
            'color'             => 'color',
            'alignment'         => 'justify-content',
            'title_margin'      => 'margin',
            'title_padding'     => 'padding',
            'title_text_shadow' => 'text-shadow',
        ], false);

        $iconWrapperStyle = $cssHelper->generateStyle('.sppb-dynamic-content-text', $settings, ['icon_gap' => 'gap'], false);
        $iconStyle = $cssHelper->generateStyle('.sppb-dynamic-content-text__icon', $settings, ['icon_color' => 'color', 'icon_size' => 'font-size'], false);

        $css .= $cssHelper->typography('.sppb-dynamic-content-text', $settings, 'typography');
        $css .= $iconWrapperStyle . $iconStyle;

        if (isset($settings->attribute->type) && $settings->attribute->type === FieldTypes::RATING) {
            $css .= '
                .sppb-rating-stars {
                    display: inline-flex;
                    gap: ' . (!empty($settings->rating_gap) ? $settings->rating_gap : '2px') . ';
                    align-items: center;
                }
                .sppb-rating-star {
                    color: ' . (!empty($settings->rating_empty_color) ? $settings->rating_empty_color : '#d1d5db') . ';
                    font-size: ' . (!empty($settings->rating_size) ? $settings->rating_size : '1.2em') . ';
                    line-height: 1;
                    transition: color 0.2s ease;
                }
                .sppb-rating-star-filled {
                    color: ' . (!empty($settings->rating_color) ? $settings->rating_color : '#fbbf24') . ';
                }
                .sppb-rating-star-half {
                    color: ' . (!empty($settings->rating_empty_color) ? $settings->rating_empty_color : '#d1d5db') . ';
                    position: relative;
                }
                .sppb-rating-star-half::after {
                    content: "★";
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 50%;
                    height: 100%;
                    color: ' . (!empty($settings->rating_color) ? $settings->rating_color : '#fbbf24') . ';
                    overflow: hidden;
                }
                /* Custom icon styles */
                .sppb-rating-star i {
                    font-size: inherit;
                    color: inherit;
                }
                .sppb-rating-custom-half {
                    position: relative;
                }
                .sppb-rating-custom-half i:first-child {
                    color: ' . (!empty($settings->rating_empty_color) ? $settings->rating_empty_color : '#d1d5db') . ';
                    position: relative;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 1;
                }
                .sppb-rating-custom-half i.half-overlay {
                    color: ' . (!empty($settings->rating_color) ? $settings->rating_color : '#fbbf24') . ';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: ' . (!empty($settings->rating_size) ? ('calc(' . $settings->rating_size . ' / 2)') : '0.6em') . ';
                    height: 100%;
                    z-index: 1;
                    overflow: hidden;
                }
            ';
        }
        return $css;
    }

    public static function getTemplate() {
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output  = '<style type="text/css">';

		$output .= $lodash->generateTransformCss('', 'data.transform');
		$output .= $lodash->typography('.sppb-dynamic-content-text', 'data.typography');

		$output .= '</style>';

		return $output;
	}
}
