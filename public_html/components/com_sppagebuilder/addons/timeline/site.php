<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderAddonTimeline extends SppagebuilderAddons
{
    /**
     * The addon frontend render method.
     * The returned HTML string will render to the frontend page.
     *
     * @return  string  The HTML string.
     * @since   1.0.0
     */
    public function render()
    {
      $settings = $this->addon->settings;
        $class = (isset($this->addon->settings->class) && $this->addon->settings->class) ? $this->addon->settings->class : '';
        $heading_selector = (isset($this->addon->settings->heading_selector) && $this->addon->settings->heading_selector) ? $this->addon->settings->heading_selector : 'h3';
        $title = (isset($this->addon->settings->title) && $this->addon->settings->title) ? $this->addon->settings->title : '';
        $timeline_img_shape = (isset($settings->image_shape) && $settings->image_shape) ? $settings->image_shape : 'sppb-image-round';

        $output = '';
        $output .= '<div class="sppb-addon sppb-addon-timeline ' . $class . '">';
        $output .= '<div class="sppb-addon-timeline-text-wrap">';
        $output .= ($title) ? '<' . $heading_selector . ' class="sppb-addon-title">' . $title . '</' . $heading_selector . '>' : '';
        $output .= '</div>';

        $output .= '<div class="sppb-addon-timeline-wrapper">';

        foreach ($this->addon->settings->sp_timeline_items as $key => $timeline) {
            $oddEven = ($key & 1) === 0 ? 'even' : 'odd';
            $output .= '<div class="sppb-row timeline-movement ' . $oddEven . '">';
            $output .= '<div class="timeline-badge"></div>';
            $timeline_img = isset($timeline->image) && $timeline->image ? $timeline->image : '';
            $timeline_img_src = isset($timeline_img->src) ? $timeline_img->src : $timeline_img;
            $alt_text = isset($timeline_img->alt) ? $timeline_img->alt : '';

            if ($oddEven === 'odd') {
                $output .= '<div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item">';

                if(isset($timeline->end_date) && $timeline->end_date && trim($timeline->end_date) != ''){
                  if(isset($timeline->date) && $timeline->date){
                  $output .= '<p class="timeline-date text-end text-right">' . $timeline->date . ' - ' . $timeline->end_date . '</p>';
                  }
                }else{
                  if(isset($timeline->date) && $timeline->date){
                    $output .= '<p class="timeline-date text-end text-right">' . $timeline->date . '</p>';
                  }
                }
                
                $output .= '</div>';
                $output .= '<div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item">';
                $output .= '<div class="timeline-panel">';

                if (isset($timeline->title) && $timeline->title) {
                    $output .= '<p class="title">' . $timeline->title . '</p>';
                }

                if(isset($timeline_img_src) && $timeline_img_src){
                  $output .= '<div class="sppb-image-container-left">';
                  $output .= '<img src="' . $timeline_img_src . '" class="' . $timeline_img_shape . '" alt="'.$alt_text.'">';
                  $output .= '</div>';
                }

                if (isset($timeline->content) && $timeline->content) {
                    $output .= '<div class="details">' . $timeline->content . '</div>';
                }

                $output .= '</div>';
                $output .= '</div>';
            } elseif ($oddEven === 'even') {
                $output .= '<div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item mobile-block">';

                if(isset($timeline->end_date) && $timeline->end_date && trim($timeline->end_date) != ''){
                  if(isset($timeline->date) && $timeline->date){
                  $output .= '<p class="timeline-date text-end text-left">' . $timeline->date . ' - ' . $timeline->end_date . '</p>';
                  }
                }else{
                  if(isset($timeline->date) && $timeline->date){
                    $output .= '<p class="timeline-date text-start text-left">' . $timeline->date . '</p>';
                  }
                }

                $output .= '</div>';
                $output .= '<div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item">';
                $output .= '<div class="timeline-panel left-part">';

                if (isset($timeline->title) && $timeline->title) {
                    $output .= '<p class="title">' . $timeline->title . '</p>';
                }

                if(isset($timeline_img_src) && $timeline_img_src){
                  $output .= '<div class="sppb-image-container-right">';
                  $output .= '<img src="' . $timeline_img_src . '" class="' . $timeline_img_shape . '" alt="'.$alt_text.'">';
                  $output .= '</div>';
                }

                if (isset($timeline->content) && $timeline->content) {
                    $output .= '<div class="details">' . $timeline->content . '</div>';
                }

                $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item mobile-hidden">';

                if(isset($timeline->end_date) && $timeline->end_date && trim($timeline->end_date) != ''){
                  if(isset($timeline->date) && $timeline->date){
                  $output .= '<p class="timeline-date text-start text-left">' . $timeline->date . ' - ' . $timeline->end_date . '</p>';
                  }
                }else{
                  if(isset($timeline->date) && $timeline->date){
                    $output .= '<p class="timeline-date text-start text-left">' . $timeline->date . '</p>';
                  }
                }

                $output .= '</div>';
            }

            $output .= '</div>';
        }

        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * Generate the CSS string for the frontend page.
     *
     * @return     string     The CSS string for the page.
     * @since     1.0.0
     */
    public function css()
    {
        $settings = $this->addon->settings;
        $addon_id = '#sppb-addon-' . $this->addon->id;
        $cssHelper = new CSSHelper($addon_id);
        

        $css = '';

        $barStyle = $cssHelper->generateStyle('.sppb-addon-timeline .sppb-addon-timeline-wrapper:before,.sppb-addon-timeline .sppb-addon-timeline-wrapper .timeline-badge:after,.sppb-addon-timeline .timeline-movement.even:before', $settings, ['bar_color' => 'background-color'], false);
        $barBorderStyle = $cssHelper->generateStyle('.sppb-addon-timeline .sppb-addon-timeline-wrapper .timeline-badge:before,.sppb-addon-timeline .timeline-movement.even:after', $settings, ['bar_color' => 'border-color'], false);
        $panelStyle = $cssHelper->generateStyle('.sppb-addon-timeline .timeline-panel', $settings, ['background_color' => 'background-color'], false);
        $panelStyle .= $cssHelper->generateStyle('.sppb-addon-timeline .timeline-panel:before', $settings, ['background_color' => ['border-right-color', 'border-top-color']], false);
        $contentStyle = $cssHelper->generateStyle('.sppb-addon-timeline .timeline-panel .details', $settings, ['item_content_color' => 'color'], false);
        $titleStyle = $cssHelper->generateStyle('.sppb-addon-timeline .timeline-panel .title', $settings, ['item_title_color' => 'color'], false);
        $dateStyle = $cssHelper->generateStyle('.sppb-addon-timeline .timeline-date', $settings, ['item_date_color' => 'color'], false);
        $contentFontStyle = $cssHelper->typography('.sppb-addon-timeline .timeline-panel .details', $settings, 'item_content_typography');
        $titleFontStyle = $cssHelper->typography('.sppb-addon-timeline .timeline-panel .title', $settings, 'item_title_typography');
        $dateFontStyle = $cssHelper->typography('.sppb-addon-timeline .timeline-date', $settings, 'item_date_typography');
        $transformCss = $cssHelper->generateTransformStyle('.sppb-addon-timeline', $settings, 'transform');

        $imageStyleWidth =  $cssHelper->generateStyle('.sppb-addon-timeline .timeline-item img', $settings, ['image_width' => ['width']], false);
        $imageStyleHeight =  $cssHelper->generateStyle('.sppb-addon-timeline .timeline-item img', $settings, ['image_height' => ['height']], false);

        $css .= $barStyle;
        $css .= $dateStyle;
        $css .= $panelStyle;
		    $css .= $transformCss;
        $css .= $titleStyle;
        $css .= $contentStyle;
        $css .= $dateFontStyle;
        $css .= $titleFontStyle;
        $css .= $barBorderStyle;
        $css .= $contentFontStyle;
        $css .= $imageStyleWidth;
        $css .= $imageStyleHeight;

        return $css;
    }

    /**
     * Generate the lodash template string for the frontend editor.
     *
     * @return     string     The lodash template string.
     * @since     1.0.0
     */
    public static function getTemplate()
    {

        $lodash = new Lodash('#sppb-addon-{{data.id}}');
        $output = '
        <#  
            let image_shape = data.image_shape || "sppb-image-round"
        #>
        <style type="text/css"> ';
        $output .= $lodash->color('background-color', '.timeline-panel', 'data.background_color');
        $output .= $lodash->unit('border-color', '.timeline-panel:before', 'data.background_color');
        $output .= $lodash->color('background-color', '.sppb-addon-timeline .sppb-addon-timeline-wrapper:before, .sppb-addon-timeline .sppb-addon-timeline-wrapper .timeline-badge:after, .timeline-movement.even:before', 'data.bar_color');
        $output .= $lodash->unit('border-color', '.sppb-addon-timeline .sppb-addon-timeline-wrapper .timeline-badge:before, .sppb-addon-timeline .timeline-movement.even:after', 'data.bar_color');
        $output .= $lodash->color('color', '.title', 'data.item_title_color');
        $output .= $lodash->color('color', '.details', 'data.item_content_color');
        $output .= $lodash->color('color', '.timeline-date', 'data.item_date_color');
        //Title
        $titleTypographyFallbacks = [
            'font' => 'data.title_font_family',
            'size' => 'data.title_fontsize',
            'line_height' => 'data.title_lineheight',
            'letter_spacing' => 'data.title_letterspace',
            'uppercase' => 'data.title_font_style?.uppercase',
            'italic' => 'data.title_font_style?.italic',
            'underline' => 'data.title_font_style?.underline',
            'weight' => 'data.title_font_style?.weight',
        ];

        $output .= $lodash->typography('.sppb-addon-title', 'data.title_typography', $titleTypographyFallbacks);
        $output .= $lodash->unit('margin-top', '.sppb-addon-title', 'data.title_margin_top', 'px');
		    $output .= $lodash->unit('margin-bottom', '.sppb-addon-title', 'data.title_margin_bottom', 'px');

        //Item Title
        $output .= $lodash->typography('.title', 'data.item_title_typography');
        //Image
        $output .= $lodash->unit('width', '.timeline-item img', 'data.image_width', false);
        $output .= $lodash->unit('height', '.timeline-item img', 'data.image_height', false);
        //Content
        $output .= $lodash->typography('.details', 'data.item_content_typography');
        //Date
        $output .= $lodash->typography('.timeline-date', 'data.item_date_typography');

		    $output .= $lodash->generateTransformCss('.sppb-addon-timeline ', 'data.transform');
        $output .= '
        </style>
        <div class="sppb-addon sppb-addon-timeline {{ data.class }}">
          <div class="sppb-addon-timeline-text-wrap">
            <# if( !_.isEmpty( data.title ) ){ #><{{ data.heading_selector }} class="sppb-addon-title sp-inline-editable-element" data-id={{data.id}} data-fieldName="title" contenteditable="true">{{ data.title }}</{{ data.heading_selector }}><# } #>
          </div>

          <div class="sppb-addon-timeline-wrapper">
            <#
              _.each(data.sp_timeline_items, function(timeline_item, key){
                let oddeven = ((key%2) == 0 ) ? "even":"odd";
            #>
              <div class="sppb-row timeline-movement {{oddeven}}">
                <div class="timeline-badge"></div>
                <#
                  if(oddeven == "odd") {
                #>
                  <div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item">
                  <# if (!_.isEmpty(timeline_item.end_date) && timeline_item.end_date.trim().length !== 0) { #>
                    <p class="timeline-date text-end text-right">{{ timeline_item.date }} - {{ timeline_item.end_date }}</p>
                    <# } else { #>
                      <p class="timeline-date text-end text-right">{{ timeline_item.date }}</p>
                      <# } #>
                  </div>
                  <div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item">
                    <div class="timeline-panel">
                      <p class="title sp-editable-content" id="addon-title-{{data.id}}-{{key}}" data-id={{data.id}} data-fieldName="sp_timeline_items-{{key}}-title">{{ timeline_item.title }}</p>
                            <#
                            var timelineImg = {}
                            if (typeof timeline_item.image !== "undefined" && typeof timeline_item.image.src !== "undefined") {
                                timelineImg = timeline_item.image
                            } else {
                                timelineImg = {src: timeline_item.image}
                            }
                            if (!_.isEmpty(timelineImg.src)) { #>
                                <# if(timelineImg.src.indexOf("https://") == -1 && timelineImg.src.indexOf("http://") == -1){ #>
                                  <div class="sppb-image-container-left">
                                    <img class="{{ image_shape }}" src=\'{{ pagebuilder_base + timelineImg.src }}\' alt="{{ timelineImg.alt ?? "" }}">
                                  </div>
                                <# } else { #>
                                  <div class="sppb-image-container-left">
                                    <img class="{{ image_shape }}" src=\'{{ timelineImg.src }}\' alt="{{ timelineImg.alt ?? "" }}">
                                  </div>
                                <# } #>
                            <# } #>
                      <div class="details sp-editable-content" id="addon-content-{{data.id}}-{{key}}" data-id={{data.id}} data-fieldName="sp_timeline_items-{{key}}-content">{{{ timeline_item.content }}}</div>
                    </div>
                  </div>

                <# } else { #>

                  <div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item mobile-block">
                  <# if (!_.isEmpty(timeline_item.end_date) && timeline_item.end_date.trim().length !== 0) { #>
                    <p class="timeline-date text-start text-left">{{ timeline_item.date }} - {{ timeline_item.end_date }}</p>
                    <# } else { #>
                      <p class="timeline-date text-start text-left">{{ timeline_item.date }}</p>
                      <# } #>
                  </div>
                  <div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item">
                    <div class="timeline-panel left-part">
                      <p class="title sp-editable-content" id="addon-title-{{data.id}}-{{key}}" data-id={{data.id}} data-fieldName="sp_timeline_items-{{key}}-title">{{ timeline_item.title }}</p>
                      <#
                            var timelineImg = {}
                            if (typeof timeline_item.image !== "undefined" && typeof timeline_item.image.src !== "undefined") {
                                timelineImg = timeline_item.image
                            } else {
                                timelineImg = {src: timeline_item.image}
                            }
                            if (!_.isEmpty(timelineImg.src)) { #>
                                <# if(timelineImg.src.indexOf("https://") == -1 && timelineImg.src.indexOf("http://") == -1){ #>
                                  <div class="sppb-image-container-right">
                                    <img class="{{ image_shape }}" src=\'{{ pagebuilder_base + timelineImg.src }}\' alt="{{ timelineImg.alt ?? "" }}">
                                  </div>
                                <# } else { #>
                                  <div class="sppb-image-container-right">
                                    <img class="{{ image_shape }}" src=\'{{ timelineImg.src }}\' alt="{{ timelineImg.alt ?? "" }}">
                                  </div>
                                <# } #>
                            <# } #>
                      <div class="details sp-editable-content" id="addon-content-{{data.id}}-{{key}}" data-id={{data.id}} data-fieldName="sp_timeline_items-{{key}}-content">{{{ timeline_item.content }}}</div>
                    </div>
                  </div>
                  <div class="sppb-col-xs-12 sppb-col-sm-6 timeline-item mobile-hidden">
                  <# if (!_.isEmpty(timeline_item.end_date) && timeline_item.end_date.trim().length !== 0) { #>
                    <p class="timeline-date text-start text-left">{{ timeline_item.date }} - {{ timeline_item.end_date }}</p>
                    <# } else { #>
                      <p class="timeline-date text-start text-left">{{ timeline_item.date }}</p>
                      <# } #>
                  </div>

                <# } #>
              </div>
            <# }) #>
          </div>
        </div>';
        return $output;
    }
}