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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\FieldTypes;
use JoomShaper\SPPageBuilder\DynamicContent\Site\CollectionHelper;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\CollectionIds;

class SppagebuilderAddonDynamic_content_image extends SppagebuilderAddons
{
    public function render()
    {   
        $settings = $this->addon->settings;
        $class = $settings->class ?? '';
        $collectionId = isset($settings->dynamic_item['collection_id']) ? $settings->dynamic_item['collection_id'] : null;

        $input = Factory::getApplication()->input;
        $collectionType = $input->get('collection_type') ?? 'normal-source';
        
        if (empty($settings->attribute)) {
            return '';
        }

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

        if (empty($settings->dynamic_item)) {
            return 'No data!';
        }

        $attributeType = $settings->attribute->type ?? 'image';
        $attributePath = $settings->attribute->path ?? '';
        $attributeId = $settings->attribute->id ?? '';

        $src = CollectionHelper::getDynamicContentData($settings->attribute, $settings->dynamic_item) ?? '';
        if (isset($settings->dynamic_item['collection_id']) && $settings->dynamic_item['collection_id'] === CollectionIds::ARTICLES_COLLECTION_ID) {
            $src = $settings->dynamic_item[$settings->attribute->path];
            if (strpos($src, '/') === 0) {
                $src = substr($src, 1);
            }
        }

        $src = trim($src);

        $poster = '';


        if($settings->attribute->type === 'gallery'){
            $output = $this->generateGalleryRender($src, $settings, $class);
            return $output;
        }

        if ($attributeType === FieldTypes::VIDEO) {
            $poster = isset(json_decode($src)->poster) ? json_decode($src)->poster : $poster;
            $src = isset(json_decode($src)->src) ? json_decode($src)->src : $src;
        }

        $aspectRatio = $settings->aspect_ratio ?? '';

        if ($aspectRatio === 'custom') {
            $aspectRatio = $settings->custom_aspect_ratio ?? '';
        }

        if ($aspectRatio === 'none') {
            $aspectRatio = '';
        }

        $imageFit = $settings->image_fit ?? 'cover';

        $variables = [
            '--sppb-dc-image-fit' => $imageFit,
            '--sppb-dc-aspect-ratio' => $aspectRatio,
        ];
        $cssVariables = '';

        foreach ($variables as $key => $value) {
            if (empty($value)) {
                continue;
            }

            $cssVariables .= $key . ': ' . $value . '; ';
        }

        $linkAttributes = [
            'href' => '',
            'target' => '',
            'rel' => '',
            'has_link' => false,
        ];
        $link = $settings->link ?? null;
        $hasLink = false;

        if (!empty($link)) {
            $linkOptions = [
                'url' => CollectionHelper::createDynamicContentLink($link, CollectionHelper::prepareItemForLink($settings->dynamic_item, $settings->attribute)),
                'target' => $link->new_tab ? '_blank' : null,
                'nofollow' => $link->nofollow ?? null,
                'noreferrer' => $link->noreferrer ?? null,
                'noopener' => $link->noopener ?? null,
            ];
            $linkAttributes = CollectionHelper::generateLinkAttributes($linkOptions);
        }

        $wrapperSelector = 'div';
        $attributes = '';
        $hasLink = $linkAttributes['has_link'] ?? false;

        if ($hasLink) {
            $wrapperSelector = 'a';
            $linkUrl = $linkAttributes['href'];
            
            $app = Factory::getApplication();
            $option = $app->input->get('option', '', 'string');
            $view = $app->input->get('view', '', 'string');
            if ($option === 'com_content' && ($view === 'category' || $view === 'archive' || $view === 'featured' || $view === 'article') && !empty($settings->dynamic_item['link'])) {
                $linkUrl = $settings->dynamic_item['link'];
            }

            $attributes .= 'href="' . $linkUrl . '"';
            $attributes .= $linkAttributes['target'] ? ' target="' . $linkAttributes['target'] . '"' : '';
            $attributes .= $linkAttributes['rel'] ? ' rel="' . $linkAttributes['rel'] . '"' : '';
        }

        if (strpos($src, 'http') === false) {
            $src = Uri::root(true) . '/' . $src;
            $poster = Uri::root(true) . '/' . $poster;
        }

        if ($attributeType === FieldTypes::IMAGE && $attributePath === 'profile_image' && $attributeId === -20) {
            if (empty($src) || $src === '/') {
                $avatarColor = "#4285F4";
                $textColor = $this->getTextColor($avatarColor);
                $initials = $this->getInitials(!empty($settings->dynamic_item['username']) ? $settings->dynamic_item['username'] : '');
                $src = $this->toDataUrl($avatarColor, $textColor, $initials);
            }
        }

        $output = '<' . $wrapperSelector . ' class="sppb-dynamic-content-image-wrapper ' . $class . '" style="' . $cssVariables . '" ' . $attributes . '>';
        if ($attributeType === FieldTypes::VIDEO && $src !== '/') {
            if (self::stringIncludesArray(['youtu.be', 'www.youtube.com', 'youtube.com', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], $src)) {

                $url 			= (isset($settings->url) && $settings->url) ? $settings->url : '';
                $video_title 	= "";
                $no_cookie 		= (isset($settings->no_cookie) && $settings->no_cookie) ? $settings->no_cookie : 0;
                $show_rel_video = (isset($settings->show_rel_video) && $settings->show_rel_video) ? '&rel=1' : '&rel=0';
                $youtube_shorts = (isset($settings->youtube_shorts) && $settings->youtube_shorts) ? $settings->youtube_shorts : 0;
                $aspect_ratio   = (isset($settings->aspect_ratio) && $settings->aspect_ratio && $youtube_shorts) ? $settings->aspect_ratio : '16by9';

                $vimeo_show_author 			= (isset($settings->vimeo_show_author) && $settings->vimeo_show_author) ? "byline=1" : "byline=0";
                $vimeo_mute_video  			= (isset($settings->vimeo_mute_video) && $settings->vimeo_mute_video) ? "muted=1" : "muted=0";
                $vimeo_show_video_title  	= (isset($settings->vimeo_show_video_title) && $settings->vimeo_show_video_title) ? "title=1" : "title=0";
                $vimeo_show_author_profile  = (isset($settings->vimeo_show_author_profile) && $settings->vimeo_show_author_profile) ? "portrait=1" : "portrait=0";

                if ($src)
                {
                    $video = parse_url($src);
        
                    $youtube_no_cookie = $no_cookie ? '-nocookie' : '';
        
                    if(array_key_exists('host', $video)) {
                        switch ($video['host'])
                        {
                            case 'youtu.be':
                                $id 		 = trim($video['path'], '/');
                                $src 		 = '//www.youtube' . $youtube_no_cookie . '.com/embed/' . $id . '?iv_load_policy=3' . $show_rel_video;
                                $video_title = (isset($settings->video_title) && $settings->video_title) ? $settings->video_title : Text::_("COM_SPPAGEBUILDER_ADDON_VIDEO_TITLE_DEFAULT_TEXT");
                                break;
        
                            case 'www.youtube.com':
                            case 'youtube.com':
                                $query = [];
                                $playlist_id = null;
        
                                if(array_key_exists('query', $video)) {
                                    parse_str($video['query'], $query);
                                }
        
                                if($video['path'] === '/playlist') {
                                    if (preg_match('/\blist=([^&]+)/', $video['query'], $matches)) {
                                        $playlist_id = $matches[1];
                                    }
                                }
        
                                $src 		 = '';
                                
                                if($playlist_id) {
                                    $src 	 = '//www.youtube.com/embed/?listType=playlist&list=' . $playlist_id;
                                } else {
                                    $id  		 = ($youtube_shorts) ? str_replace('/shorts/', "", $video['path']) : $query['v'];
                                    $src 	 = '//www.youtube' . $youtube_no_cookie . '.com/embed/' . $id . '?iv_load_policy=3' . $show_rel_video;
                                }
        
                                $video_title = (isset($settings->video_title) && $settings->video_title) ? $settings->video_title : Text::_("COM_SPPAGEBUILDER_ADDON_VIDEO_TITLE_DEFAULT_TEXT");
                                break;
                                
                            case 'vimeo.com':
                            case 'www.vimeo.com':
                            case 'player.vimeo.com':
                                $initialSrc = $url;
        
                                if($video['host'] !== 'player.vimeo.com') {
                                    $id = trim($video['path'], '/');
                                    $initialSrc = "//player.vimeo.com/video/{$id}";
                                }
        
                                $embeddedParameter = array($vimeo_mute_video, $vimeo_show_author, $vimeo_show_author_profile, $vimeo_show_video_title);
                                $src = self::setEmbeddedParameter($embeddedParameter, $initialSrc);
                                break;
                        }
                    }

                    $output .= '<div class="sppb-video-block sppb-embed-responsive sppb-embed-responsive-'.$aspect_ratio.'">';
                    $output .= '<iframe class="sppb-embed-responsive-item' . '" ' . ('src="' . $src . '"') . ' allow="accelerometer" webkitAllowFullScreen mozallowfullscreen allowFullScreen loading="lazy" ></iframe>';
                    $output .= '</div>';
                }
            } else {
                $show_control = (isset($settings->show_control) && $settings->show_control) ? $settings->show_control : 0;
                $enable_download = (isset($settings->download_video) && $settings->download_video) ? $settings->download_video : 0;
                $video_loop = (isset($settings->video_loop) && $settings->video_loop) ? $settings->video_loop : 0;
                $autoplay_video = (isset($settings->autoplay_video) && $settings->autoplay_video) ? $settings->autoplay_video : 0;
                $video_mute = (isset($settings->video_mute) && $settings->video_mute) ? $settings->video_mute : 0;
    
                $output .= '<video style="width: 100%" ' . ($video_loop != 0 ? ' loop' : '') . '' . ($autoplay_video != 0 ? ' autoplay' : '') . '' . ($show_control != 0 ? ' controls' : '') . '' . ($video_mute != 0 ? ' muted' : '') . (!empty($poster) && $poster !== '/' ? (' poster="' . $poster . '"') : '') . ($enable_download ? '' : ' controlsList="nodownload" oncontextmenu="return false;"') . ' playsinline>
                    <source src="' . $src . '" type="video/mp4" />
                        Your browser does not support the video tag.
                </video>';
            }
        } else {

            if($src && $src !== '/')
            {
                $output .= '<img src="' . $src . '" alt="Dynamic Content Image" class="sppb-dynamic-content-image" style="object-fit: ' . $imageFit . '; aspect-ratio: ' . $aspectRatio . ';" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';" />';
                if ($attributeType === FieldTypes::IMAGE && $attributePath === 'profile_image' && $attributeId === -20) {
                    if (strpos($src, 'www.gravatar.com') !== false) {
                        $avatarColor = "#4285F4";
                        $textColor = $this->getTextColor($avatarColor);
                        $initials = $this->getInitials(!empty($settings->dynamic_item['username']) ? $settings->dynamic_item['username'] : '');
                        $src = $this->toDataUrl($avatarColor, $textColor, $initials);
                        $output .= '<img src="' . $src . '" alt="Dynamic Content Image" class="sppb-dynamic-content-image" style="object-fit: ' . $imageFit . '; aspect-ratio: ' . $aspectRatio . ';" onerror="this.style.display=\'none\';" />';
                    }
                }
            }
            
        }
        $output .= '</' . $wrapperSelector . '>';

        return $output;
    }

    public function css()
    {
        $css = '';

        $addon_id   = '#sppb-addon-' . $this->addon->id;
        $settings   = $this->addon->settings;
        $cssHelper  = new CSSHelper($addon_id);

        $isEffectsEnabled = (isset($settings->is_effects_enabled) && $settings->is_effects_enabled) ? $settings->is_effects_enabled : 0;

        if ($isEffectsEnabled) {
            $settings->image_effects = $cssHelper::parseCssEffects($settings, 'image_effects');
        }

        $imageEffectStyle = $cssHelper->generateStyle(
            '.sppb-dynamic-content-image-wrapper .sppb-dynamic-content-image',
            $settings,
            ['image_effects' => 'filter'],
            false
        );

        $transformCss = $cssHelper->generateTransformStyle(
            '.sppb-dynamic-content-image-wrapper',
            $settings,
            'transform'
        );

        $imageBorderRadius = $cssHelper->generateStyle(
            '.sppb-dynamic-content-image-wrapper',
            $settings,
            ['radius' => 'border-radius'],
            ['border_radius' => false],
            ['border_radius' => 'spacing']
        );

        $imageBorderStyle = $cssHelper->border('.sppb-dynamic-content-image-wrapper', $settings, 'border');

        $imageWrapperStyle = $cssHelper->generateStyle(
            '.sppb-dynamic-content-image-wrapper',
            $settings,
            ['width' => 'width', 'height' => 'height'],
            'px'
        );

        $imageMarginPaddingStyle = $cssHelper->generateStyle(
            '.sppb-dynamic-content-image-wrapper',
            $settings,
            ['margin' => 'margin', 'padding' => 'padding'],
            false
        );

        $staticStyles = $cssHelper->generateStyle(
            '.sppb-dynamic-content-image-wrapper', $settings, [], '', [], [], false,
            'width: 100%; overflow: hidden; aspect-ratio: var(--sppb-dc-aspect-ratio);'
        );
        $staticImageStyles = $cssHelper->generateStyle(
            '.sppb-dynamic-content-image-wrapper .sppb-dynamic-content-image', $settings, [], '', [], [], false,
            'width: 100%; height: 100%; object-fit: var(--sppb-dc-image-fit);'
        );

        $css .= $staticStyles;
        $css .= $staticImageStyles;
        $css .= $imageWrapperStyle;
        $css .= $imageBorderStyle;
        $css .= $imageBorderRadius;
        $css .= $imageEffectStyle;
        $css .= $transformCss;
        $css .= $imageMarginPaddingStyle;

        if (($this->addon->settings->attribute->type ?? null) === 'gallery') {

            $border_radius = (isset($settings->gallery_border_radius) && $settings->gallery_border_radius) ? $settings->gallery_border_radius : 0;

            if ($border_radius) {
                $border_radius = explode(" ", $settings->gallery_border_radius);
            }

            if (is_array($border_radius) && (count($border_radius) > 2)) {
                $galleryImageStyle = $cssHelper->generateStyle('.sppb-gallery img', $settings, ['gallery_width' => 'width', 'gallery_height' => 'height', 'gallery_border_radius' => 'border-radius'],
                [
                    'gallery_border_radius' => false
                ],
                [
                    'gallery_border_radius' => 'spacing'
                ]);
            } else {
                $galleryImageStyle = $cssHelper->generateStyle('.sppb-gallery img', $settings, ['gallery_width' => 'width', 'gallery_height' => 'height', 'gallery_border_radius' => 'border-radius']);
            }

            $galleryStyle = $cssHelper->generateStyle('.sppb-gallery', $settings, ['gallery_item_gap' => 'margin: -%s', 'item_alignment' => 'justify-content'], ['item_alignment' => false]);
            $galleryItemStyle = $cssHelper->generateStyle('.sppb-gallery li', $settings, ['gallery_item_gap' => 'margin']);
            
            $css .= $galleryStyle;
            $css .= $galleryItemStyle;
            $css .= $galleryImageStyle;
        }

        return $css;
    }

    public static function getTemplate() {
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$output  = '<style type="text/css">';

		$output .= $lodash->generateTransformCss('', 'data.transform');

        $output .= $lodash->alignment('justify-content', '.sppb-gallery', 'data.item_alignment');
		$output .= $lodash->unit('width', '.sppb-gallery img', 'data.gallery_width', 'px');
		$output .= $lodash->unit('height', '.sppb-gallery img', 'data.gallery_height', 'px');

		$output .= '<# if((data.border_radius + "").split(" ").length < 2) { #>';
		$output .= $lodash->unit('border-radius', '.sppb-gallery img', 'data.gallery_border_radius', 'px');
		$output .= '<# } else { #>';
		$output .= '.sppb-gallery img {
			{{window.getSplitRadius(data.gallery_border_radius)}}	
		}';
		$output .= '<# } #>';

		$output .= $lodash->unit('margin', '.sppb-gallery li', 'data.gallery_item_gap', 'px');
		$output .= $lodash->unit('margin', '.sppb-gallery', 'data.gallery_item_gap', 'px', true, '-');

		// Title
		$titleTypographyFallbacks = [
			'font'           => 'data.title_font_family',
			'size'           => 'data.title_fontsize',
			'line_height'    => 'data.title_lineheight',
			'letter_spacing' => 'data.title_letterspace',
			'uppercase'      => 'data.title_font_style?.uppercase',
			'italic'         => 'data.title_font_style?.italic',
			'underline'      => 'data.title_font_style?.underline',
			'weight'         => 'data.title_font_style?.weight',
		];

		$output .= $lodash->typography('.sppb-addon-title', 'data.gallery_title_typography', $titleTypographyFallbacks);
        $output .= $lodash->color('color', '.sppb-addon-title', 'data.gallery_title_text_color');
		$output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.gallery_title_margin_top', 'px');
		$output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.gallery_title_margin_bottom', 'px');
        

		$output .= '</style>';

		return $output;
	}


    private function generateGalleryRender($src, $settings, $class){
        $listIndex = !empty($this->addon->listIndex) ? $this->addon->listIndex : 0;
        $src = json_decode($src);
        $title = (isset($settings->gallery_title) && $settings->gallery_title) ? $settings->gallery_title : '';
		$heading_selector = (isset($settings->gallery_heading_selector) && $settings->gallery_heading_selector) ? $settings->gallery_heading_selector : 'h3';
		$item_alignment = (isset($settings->gallery_item_alignment) && $settings->gallery_item_alignment) ? $settings->gallery_item_alignment : '';
		$item_alignment = AddonUtils::parseDeviceData($item_alignment, SpPgaeBuilderBase::$defaultDevice);
        $itemId = $settings->dynamic_item['id'];

        $sliderStyle = !empty($settings->enable_slider) ? (!empty($settings->slider_style) ? $settings->slider_style : 'thumb') : false;

        $output  = '<div class="sppb-dynamic-content-gallery ' . $class . '">';
		$output .= ($title) ? '<' . $heading_selector . ' class="sppb-addon-title">' . $title . '</' . $heading_selector . '>' : '';
		$output .= '<div class="sppb-addon-content">';

		if (isset($src) && count((array) $src))
		{
            $output .= '<ul data-item-id="' . $itemId . '" class="sppb-dc-bxslider sppb-gallery clearfix gallery-item-' . $item_alignment . '">';
			foreach ($src as $key => $value)
			{
				
				$thumb_src = isset($value->src) ? $value->src : '';

				if (!is_string($thumb_src)) {
					$thumb_src = '';
				}

				if (!empty($thumb_src))
				{
					if (strpos($thumb_src, "http://") !== false || strpos($thumb_src, "https://") !== false)
					{
						$thumb_src = $thumb_src;
					}
					else
					{
						$thumb_src = Uri::base(true) . '/' . $thumb_src;
					}

					$output .= '<li>';
					$output .= ($thumb_src) ? '<a href="' . $thumb_src . '" class="sppb-gallery-btn">' : '';
					$output .= '<img class="sppb-img-responsive" src="'. $thumb_src . '" data-large="' . $thumb_src . '"loading="lazy" style="object-fit: cover;" />';
					$output .= ($thumb_src) ? '</a>' : '';
					$output .= '</li>';
				}
			}
            $output .= '</ul>';
		}

        if ($sliderStyle && $sliderStyle === 'thumb') {
            $output .= '<div data-item-id="' . $itemId . '" id="sppb-dc-bxpager-' . $this->addon->id . '-'. $itemId .'" class="sppb-dc-bxpager">';
            if (isset($src) && count((array) $src))
            {
                foreach ($src as $key => $value)
                {
    				$thumb_src = isset($value->src) ? $value->src : '';
                    if (!is_string($thumb_src)) {
                        $thumb_src = '';
                    }
    
                    if (!empty($thumb_src))
                    {
                        if (strpos($thumb_src, "http://") !== false || strpos($thumb_src, "https://") !== false)
                        {
                            $thumb_src = $thumb_src;
                        }
                        else
                        {
                            $thumb_src = Uri::base(true) . '/' . $thumb_src;
                        }
                    }
                    $output .= '<a data-slide-index="' . $key . '" href="javascript:void(0)"><img src="' . $thumb_src . '"></a>';
                }
            }
            $output .= '</div>';
        }

		$output .= '</div>';
		$output .= '</div>';

		return $output;
    }

    private function getInitials($name) {
		$words = explode(' ', trim($name));
		$initials = '';
		if (count($words) > 1) {
			$initials .= strtoupper(substr($words[0], 0, 1));
			$initials .= strtoupper(substr(end($words), 0, 1));
		} else {
			$initials .= strtoupper(substr($name, 0, 1));
		}
		return $initials;
	}

	private function getTextColor($color) {
		$r = hexdec(substr($color, 1, 2));
		$g = hexdec(substr($color, 3, 2));
		$b = hexdec(substr($color, 5, 2));

		$brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return ($brightness > 128) ? '#000000' : '#FFFFFF';
	}

    private function toDataUrl(string $avatarColor, string $textColor, string $initials, int $size = 45): string
    {
        $avatarColor = trim($avatarColor);
        $textColor   = trim($textColor);
        $initialsEsc = htmlspecialchars(strtoupper($initials), ENT_QUOTES, 'UTF-8');
    
        $fontSize = (int) round($size * 0.44);
    
        $svg = '
    <svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" role="img" aria-label="avatar">
      <circle cx="' . $size / 2 . '" cy="' . $size / 2 . '" r="' . $size / 2 . '" fill="' . $avatarColor . '" />
      <text x="50%" y="55%"
            fill="' . $textColor . '"
            font-family="system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif"
            font-size="' . $fontSize . '"
            font-weight="600"
            text-anchor="middle"
            dominant-baseline="middle">
        ' . $initialsEsc . '
      </text>
    </svg>
    ';
    
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }    
    
    
    

    public function stylesheets()
	{
        if (isset($this->addon->settings->attribute) && $this->addon->settings->attribute->type === 'gallery') {
            return array(Uri::base(true) . '/components/com_sppagebuilder/assets/css/magnific-popup.css', Uri::base(true) . '/components/com_sppagebuilder/assets/css/jquery.bxslider.min.css');
        }
		
	}

    public function scripts()
	{
        if (isset($this->addon->settings->attribute) && $this->addon->settings->attribute->type === 'gallery') {
            HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/jquery.bxslider.min.js', [], ['defer' => true]);
            HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/jquery.magnific-popup.min.js', []);
        }
        HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/addons/dc-gallery-bxslider.js', [], ['defer' => true]);
	}

    public function js()
	{
        if (isset($this->addon->settings->attribute) && $this->addon->settings->attribute->type === 'gallery') {
            $enableSlider = (isset($this->addon->settings->enable_slider) && !empty($this->addon->settings->enable_slider)) ? true : false;
            $enableArrows = (isset($this->addon->settings->enable_arrows) && !empty($this->addon->settings->enable_arrows)) ? true : false;
            $sliderStyle = (isset($this->addon->settings->slider_style) && !empty($this->addon->settings->slider_style)) ?  $this->addon->settings->slider_style : 'thumb';
            $imagePerSlide = (isset($this->addon->settings->image_per_slide) && !empty($this->addon->settings->image_per_slide)) ? $this->addon->settings->image_per_slide : 1;


            $addon_id = '#sppb-addon-' . $this->addon->id;
            $js = 'jQuery(function($){
                    function initMagnificPopup() {
                        $("' . $addon_id . ' ul li:not(.bx-clone)").magnificPopup({
                            delegate: "a",
                            type: "image",
                            mainClass: "mfp-no-margins mfp-with-zoom",
                            gallery:{
                                enabled:true
                            },
                            image: {
                                verticalFit: true
                            },
                            zoom: {
                                enabled: true,
                                duration: 300
                            }
                        });
                    }
                    initMagnificPopup();

                    const targetNode = document.getElementById("sp-page-builder");
                    if (targetNode) {
                        const observer = new MutationObserver(function(mutationsList, observer) {
                            for(const mutation of mutationsList) {
                                if (mutation.type === "childList") {
                                    if ($( "' . $addon_id . '" ).length) {
                                        initMagnificPopup();
                                    }
                                }
                            }
                        });
                        observer.observe(targetNode, { childList: true, subtree: true });
                    }
                });';

            if ($enableSlider && $sliderStyle === 'carousel') {
                $js .= '
                jQuery(function($){
                    function initBxSliderCarousel() {
                        if ($("' . $addon_id . ' .sppb-dc-bxslider").length) {
                            $("' . $addon_id . ' .sppb-dc-bxslider").bxSlider({
                                minSlides: ' . $imagePerSlide . ',
                                maxSlides: ' . $imagePerSlide  . ',
                                controls: ' . ($enableArrows ? "true" : "false")  . ',
                                nextText: `<i class="fa fa-angle-right" aria-hidden="true"></i>`,
                                prevText: `<i class="fa fa-angle-left" aria-hidden="true"></i>`,
                            });
                        }
                    }
                    initBxSliderCarousel();

                    const targetNode = document.getElementById("sp-page-builder");
                    if (targetNode) {
                        const observer = new MutationObserver(function(mutationsList, observer) {
                            for(const mutation of mutationsList) {
                                if (mutation.type === "childList") {
                                    if ($("' . $addon_id . '").length) {
                                        initBxSliderCarousel();
                                    }
                                }
                            }
                        });
                        observer.observe(targetNode, { childList: true, subtree: true });
                    }
                });';
            }
            if ($enableSlider && $sliderStyle === 'thumb') {
                $js .= '
                jQuery(function($){
                    function initBxSliderThumb() {
                        $("' . $addon_id . ' .sppb-dc-bxslider").each(function(itemIndex) {
                            const itemId = $(this).data("item-id");
                            $(this).bxSlider({
                                pagerCustom: "#sppb-dc-bxpager-' . $this->addon->id . '"+ "-" + itemId,
                                controls: ' . ($enableArrows ? "true" : "false")  . ',
                                nextText: `<i class="fa fa-angle-right" aria-hidden="true"></i>`,
                                prevText: `<i class="fa fa-angle-left" aria-hidden="true"></i>`,
                            });
                        });
                    }
                    initBxSliderThumb();

                    const targetNode = document.getElementById("sp-page-builder");
                    if (targetNode) {
                        const observer = new MutationObserver(function(mutationsList, observer) {
                            for(const mutation of mutationsList) {
                                if (mutation.type === "childList") {
                                    if ($("' . $addon_id . '").length) {
                                        initBxSliderThumb();
                                    }
                                }
                            }
                        });
                        observer.observe(targetNode, { childList: true, subtree: true });
                    }
                });';
            }
            return $js;
        }
	}
    /**
	 * Set embedded settings for vimeo video player
	 *
	 * @param  array 	$embeddedParameter   Array of embedded settings which will be added on vimeo player.
	 * @param  string 	$src				 "src" attribute of iframe
	 * @return void
	 * @since  4.0.8
	 */
	public static function setEmbeddedParameter($embeddedParameter, $src){
		
		$embeddedString = "";
		$separator 		= "&";	
			
		foreach ($embeddedParameter as $key => $value) {
			$embeddedString .= ($key > 0) ? $separator . $value : $value ;
		}

		$src = $src . '?' . $embeddedString;			
		return $src;
	}

    public static function stringIncludesArray(array $arr, string $src): bool {
        foreach ($arr as $word) {
            if (strpos($src, $word) !== false) {
                return true;
            }
        }
        return false;
    }
}
