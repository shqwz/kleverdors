<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
//no direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use JoomShaper\SPPageBuilder\DynamicContent\Services\CollectionsService;
use JoomShaper\SPPageBuilder\DynamicContent\Services\FilterService;

class SppagebuilderAddonDynamic_content_filter extends SppagebuilderAddons
{
    public function render(){
        $settings = $this->addon->settings;
        $class = $settings->class ?? '';

        $collectionId = isset($settings->source) && $settings->source ? $settings->source : -1;
        $collectionSchema = (new CollectionsService)->fetchCollectionSchema($collectionId ?? -1);
        $globalFilterLayout = isset($settings->filter_layout) && $settings->filter_layout ? $settings->filter_layout : '';

        $resetLabel = isset($settings->reset_label) && $settings->reset_label ? $settings->reset_label : 'Reset';
        $resetBtnPosition = isset($settings->reset_btn_position) && $settings->reset_btn_position ? $settings->reset_btn_position : 'start';

        $matchButtonBehavior = isset($settings->match_button_behavior) && !empty($settings->match_button_behavior) ? true : false;

        $isFilterApplied = false;
        $query = Uri::getInstance()->getQuery();
        parse_str($query, $query);
        foreach($query as $key => $value){
            if(strpos($key, 'dc_filter_') !== false || strpos($key, 'dc_query_') !== false){
                $isFilterApplied = true;
                break;
            }
        }

        $output = '';
        $output .= '<div class="sppb-addon-dynamic-content-filter-wrapper ' . $class . '">';
        $output .= '<div class="sppb-addon-dynamic-content-filter-list">';

        if($resetBtnPosition === 'start'){
            if(isset($settings->match_button_style) && $settings->match_button_style){
                $settings = $this->addon->settings;
                $class = '';
                $class .= (isset($settings->button_type) && $settings->button_type) ? ' dc-filter-btn-' . $settings->button_type : '';
                $class .= (isset($settings->button_block) && $settings->button_block) ? ' ' . $settings->button_block : '';
                $class .= (isset($settings->button_shape) && $settings->button_shape) ? ' dc-filter-btn-' . $settings->button_shape : ' dc-filter-btn-rounded';
                $class .= (isset($settings->button_appearance) && $settings->button_appearance) ? ' dc-filter-btn-' . $settings->button_appearance : '';

                if($matchButtonBehavior && !$isFilterApplied){
                    $class .= ' dc-filter-btn-selected';
                }

                $output .= '<div class="sppb-addon-dynamic-content-filter-item-content">';
                $output .= '<button class="sppb-addon-dynamic-content-filter-reset-btn-alt dc-filter-btn ' . $class . '">' . $resetLabel . '</button>';
                $output .= '</div>';
            } else {
                $output .= '<div class="sppb-addon-dynamic-content-filter-reset-btn-wrapper">';
                $output .= '<button class="sppb-addon-dynamic-content-filter-reset-btn">' . $resetLabel . '</button>';
                $output .= '</div>';
            }
        }

        if(!empty($settings->filter_items) && isset($settings->filter_items)){
            foreach ($settings->filter_items as $key => $item) {
                $filterMode = isset($item->filter_model) && !empty($item->filter_model) ? $item->filter_model : 'search_filter';
                $fieldId = isset($item->field_name) && $item->field_name ? $item->field_name : '';
               
                if (empty($fieldId) && $filterMode !== 'search_filter') {
                    continue;
                }

                $fieldName = isset($item->title) && $item->title ? $item->title :  $this->getFieldName($fieldId, $collectionSchema);
                $hideTitle = isset($item->hide_title) && $item->hide_title ? $this->getBoolean($item->hide_title) : false;
                $filterLayout = isset($item->filter_layout_override) && $item->filter_layout_override ? $item->filter_layout_override : $globalFilterLayout;
                $filterControlType = isset($item->filter_control_type) && $item->filter_control_type ? $item->filter_control_type : '';
                $fieldValues = $filterMode !== 'search_filter' ? (new FilterService)->fetchFieldValuesById($fieldId) : ['all_field'];
                $showCount = isset($item->show_count) && $item->show_count ? $this->getBoolean($item->show_count) : false;
                $itemSettings = isset($settings) && $settings ? $settings : null;
                $itemWidth = $filterLayout === 'accordion' ? 'sppb-filter-accordion-width' : '';
                $switchClassOverride = $filterControlType === 'switch' ? ' switch-override ' : '';

                $output .= '<div class="sppb-addon-dynamic-content-filter-item ' . $itemWidth. $switchClassOverride . '">';
                if($filterLayout === 'flat'){
                    $output .= $this->getFlatLayout($fieldName, $fieldValues, $hideTitle, $filterControlType, $showCount, $itemSettings, $collectionId, $filterMode, $fieldId);
                }elseif($filterLayout === 'dropdown'){
                    $output .= $this->getDropdownLayout($fieldName, $fieldValues, $hideTitle, $filterControlType, $showCount, $collectionId, $filterMode, $fieldId);
                }elseif($filterLayout === 'accordion'){
                    $output .= $this->getAccordionLayout($fieldName, $fieldValues, $hideTitle, $filterControlType, $showCount, $collectionId, $filterMode, $fieldId);
                }
                $output .= '</div>';
            }
        }

        if($resetBtnPosition === 'end'){
            if(isset($settings->match_button_style) && $settings->match_button_style){
                $settings = $this->addon->settings;
                $class = '';
                $class .= (isset($settings->button_type) && $settings->button_type) ? ' dc-filter-btn-' . $settings->button_type : '';
                $class .= (isset($settings->button_block) && $settings->button_block) ? ' ' . $settings->button_block : '';
                $class .= (isset($settings->button_shape) && $settings->button_shape) ? ' dc-filter-btn-' . $settings->button_shape : ' dc-filter-btn-rounded';
                $class .= (isset($settings->button_appearance) && $settings->button_appearance) ? ' dc-filter-btn-' . $settings->button_appearance : '';

                if($matchButtonBehavior && !$isFilterApplied){
                    $class .= ' dc-filter-btn-selected';
                }

                $output .= '<div class="sppb-addon-dynamic-content-filter-item-content">';
                $output .= '<button class="sppb-addon-dynamic-content-filter-reset-btn-alt dc-filter-btn ' . $class . '">' . $resetLabel . '</button>';
                $output .= '</div>';
            } else {
                $output .= '<div class="sppb-addon-dynamic-content-filter-reset-btn-wrapper">';
                $output .= '<button class="sppb-addon-dynamic-content-filter-reset-btn">' . $resetLabel . '</button>';
                $output .= '</div>';
            }
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    private function getFlatLayout($fieldName, $fieldValues, $hideTitle, $filterControlType, $showCount, $itemSettings, $collectionId, $filterModel, $fieldId){
        $output = '';

        if(!$hideTitle){
            $output .= '<div class="sppb-addon-dynamic-content-filter-item-title">';
            $output .= '<span class="sppb-addon-dynamic-content-filter-item-title-text">' . $fieldName . '</span>';
            $output .= '</div>';
        }

        $output .= '<div class="sppb-addon-dynamic-content-filter-item-content">';
        $output .= $this->getFilterControlItem($fieldValues, $filterControlType, $showCount, $itemSettings, $collectionId, $filterModel, $fieldId);
        $output .= '</div>';
        

        return $output;
    }

    
    private function getAccordionLayout($fieldName, $fieldValues, $hideTitle, $filterControlType, $showCount, $collectionId, $filterModel, $fieldId){
        $output = '';
    
        $output .= '<div class="sppb-panel sppb-panel-custom">';
        
        $output .= '<button type="button" style="background: none; border-bottom: 1px solid #D5D7E0;" class="sppb-reset-button-styles sppb-w-full sppb-panel-heading sppb-addon-dynamic-content-filter-item-title">';
        
        if(!$hideTitle){
            $output .= '<span class="sppb-panel-title sppb-addon-dynamic-content-filter-item-title-text">' . $fieldName . '</span>';
        }
        
        $output .= '<span class="sppb-toggle-direction"><i class="fa fa-chevron-right" aria-hidden="true"></i></span>';
        $output .= '</button>';
        
        $output .= '<div class="sppb-panel-collapse" style="display: none;">';
        $output .= '<div class="sppb-panel-body sppb-addon-dynamic-content-filter-item-content">';
        $output .= $this->getFilterControlItem($fieldValues, $filterControlType, $showCount, null, $collectionId, $filterModel, $fieldId);
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '</div>';
    
        return $output;
    }

    private function getDropdownLayout($fieldName, $fieldValues, $hideTitle, $filterControlType, $showCount, $collectionId, $filterMode, $fieldId){
        $output = '';
        $uniqueId = 'sppb-dropdown-' . uniqid();
    
        $output .= '<div class="sppb-addon-dynamic-content-filter-dropdown-wrapper">';
        
        if(!$hideTitle){
            $output .= '<div class="sppb-addon-dynamic-content-filter-dropdown-trigger" data-dropdown-id="' . $uniqueId . '">';
            $output .= '<span class="sppb-addon-dynamic-content-filter-item-title-text">' . $fieldName . '</span>';
            $output .= '<span class="sppb-addon-dynamic-content-filter-dropdown-arrow"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>';
            $output .= '</div>';
        }
    
        $output .= '<div class="sppb-addon-dynamic-content-filter-dropdown-content" id="' . $uniqueId . '-content" >';
        $output .= '<div id="sppb-addon-' . $this->addon->id . '">';
        $output .= '<div class="sppb-addon-dynamic-content-filter-dropdown-popup" id="' . $uniqueId . '-popup">';
        $output .= '<div class="sppb-addon-dynamic-content-filter-item-content">';
        $output .= $this->getFilterControlItem($fieldValues, $filterControlType, $showCount, null, $collectionId, $filterMode, $fieldId);
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '</div>';
    
        return $output;
    }

    private function getFilterControlItem($fieldValues, $filterControlType, $showCount, $itemSettings, $collectionId, $filterMode, $fieldId){
        if(empty($fieldValues) || !is_array($fieldValues)){
            return '';
        }

        $output = '';
        $filterService = new FilterService();
        
        if ($filterMode === 'search_filter') {
            $value = '';
            $query = Uri::getInstance()->getQuery();
            parse_str($query, $query);
            if (isset($query['dc_query_' . $collectionId])) {
                $value = $query['dc_query_' . $collectionId];
            }
            $output .= '<div class="sppb-addon-dynamic-content-filter-item-search-option">';
            $output .= '<div class="sppb-addon-dynamic-content-filter-search-wrapper' . (!empty($itemSettings->search_icon) ? ' has-icon' : '') . '">';
            if (!empty($itemSettings->search_icon)) {
                $output .= '<span class="sppb-addon-dynamic-content-filter-search-icon ' . $itemSettings->search_icon . '"></span>';
            }
            $output .= '<input type="text" name="filter_search" value="' . $value . '" placeholder="' . ($itemSettings->search_placeholder ?? '') . '" data-filter-search data-filter-collection-id="' . $collectionId . '" />';
            $output .= '</div>';

            $output .= '</div>';
        } else {
            switch($filterControlType){
                    case 'input': {
                        $retrievedValue = '';
                        $query = Uri::getInstance()->getQuery();
                        parse_str($query, $query);
                        if (isset($query['dc_filter_' . $fieldId])) {
                            $retrievedValue = $query['dc_filter_' . $fieldId];
                        }
                        
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-input-option">';
                        $output .= '<input type="text" class="sppb-addon-dynamic-content-filter-input" name="filter_input" value="' . $retrievedValue . '" data-filter-field-id="' . $fieldId . '">';
                        $output .= '</div>';
                        break;
                    }
                    case 'radio': {
                        $retrievedValue = '';
                        $query = Uri::getInstance()->getQuery();
                        parse_str($query, $query);
                        if (isset($query['dc_filter_' . $fieldId])) {
                            $retrievedValue = $query['dc_filter_' . $fieldId];
                        }

                        $optionItems = $filterService->getCollectionOptionFieldsData($fieldId);

                        if(!empty($optionItems)){
                            foreach($optionItems as $value => $label){
                                $output .= '<div class="sppb-addon-dynamic-content-filter-item-radio-option">';
                                $output .= '<input type="radio" hidden name="filter_option_'. $fieldId . '" value="' . $value . '" data-filter-field-id="' . $fieldId . '" ' . ($retrievedValue === $value ? 'checked' : '') . '>';
                                $output .= '<div class="sppb-addon-dynamic-content-filter-fake-radio-input"></div>';
                                $output .= '<label>' . $label . '</label>';
                                
                                if($showCount){
                                    $count = $this->getFieldCount($value, $fieldValues);
                                    $output .= '<label class="sppb-addon-dynamic-content-filter-item-count">(' . $count . ')</label>';
                                }
                                $output .= '</div>';
                            }
                        } else {
                            $isFieldIdReference = $filterService->isReferenceField($fieldId, $collectionId);
                            $referenceCounts = [];
                            if($isFieldIdReference){
                                $referenceCounts = $filterService->getCollectionItemsFromReferenceField($fieldId, $collectionId);
                            }
                            
                            foreach(array_unique(array_filter($fieldValues, fn($value) => !empty($value))) as $value){
                                $output .= '<div class="sppb-addon-dynamic-content-filter-item-radio-option">';
                                $output .= '<input type="radio" hidden name="filter_option_'. $fieldId . '" value="' . $value . '" data-filter-field-id="' . $fieldId . '" ' . ($retrievedValue === $value ? 'checked' : '') . '>';
                                $output .= '<div class="sppb-addon-dynamic-content-filter-fake-radio-input"></div>';
                                $output .= '<label>' . $value . '</label>';
                                
                                if($showCount){
                                    if($isFieldIdReference){
                                        $count = isset($referenceCounts[$value]) ? $referenceCounts[$value] : 0;
                                    } else {
                                        $count = $this->getFieldCount($value, $fieldValues);
                                    }
                                    $output .= '<label class="sppb-addon-dynamic-content-filter-item-count">(' . $count . ')</label>';
                                }
                                $output .= '</div>';
                            }
                        }
                        break;
                    }

                case 'checkbox': {
                    $retrievedValue = '';
                    $retrievedValues = [];
                    $query = Uri::getInstance()->getQuery();
                    parse_str($query, $query);
                    if (isset($query['dc_filter_' . $fieldId])) {
                        $retrievedValue = $query['dc_filter_' . $fieldId];
                        $retrievedValues = explode(',', $retrievedValue);
                    }
                    $optionItems = $filterService->getCollectionOptionFieldsData($fieldId);

                    if(!empty($optionItems)){
                        foreach($optionItems as $value => $label){
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-checkbox-option">';
                        $output .= '<input type="checkbox" hidden name="filter_option" value="' . $value . '" data-filter-field-id="' . $fieldId . '" ' . (in_array($value, $retrievedValues) ? 'checked' : '') . '>';
                        $output .= '<div class="sppb-addon-dynamic-content-filter-fake-checkbox-input"><i style="display: none" class="fa fa-check" aria-hidden="true"></i></div>';
                        $output .= '<label>' . $label . '</label>';

                        if($showCount){
                            $count = $this->getFieldCount($value, $fieldValues);
                            $output .= '<label class="sppb-addon-dynamic-content-filter-item-count">(' . $count . ')</label>';
                        }
                        $output .= '</div>';
                        }
                    } else {
                        $isFieldIdReference = $filterService->isReferenceField($fieldId, $collectionId);
                        $referenceCounts = [];
                        if($isFieldIdReference){
                            $referenceCounts = $filterService->getCollectionItemsFromReferenceField($fieldId, $collectionId);
                        }
                        
                        foreach(array_unique(array_filter($fieldValues, fn($value) => !empty($value))) as $value){
                            $output .= '<div class="sppb-addon-dynamic-content-filter-item-checkbox-option">';
                            $output .= '<input type="checkbox" hidden name="filter_option" value="' . $value . '" data-filter-field-id="' . $fieldId . '" ' . (in_array($value, $retrievedValues) ? 'checked' : '') . '>';
                            $output .= '<div class="sppb-addon-dynamic-content-filter-fake-checkbox-input"><i style="display: none" class="fa fa-check" aria-hidden="true"></i></div>';
                            $output .= '<label>' . $value . '</label>';
                            
                            if($showCount){
                                if($isFieldIdReference){
                                    $count = isset($referenceCounts[$value]) ? $referenceCounts[$value] : 0;
                                } else {
                                    $count = $this->getFieldCount($value, $fieldValues);
                                }
                                
                                $output .= '<label class="sppb-addon-dynamic-content-filter-item-count">(' . $count . ')</label>';
                            }
                            $output .= '</div>';
                        }
                    }
                    break;
                }
                    case 'slider': {
                        $min = $this->getFieldMinimumValue(array_unique(array_filter($fieldValues, fn($value) => !empty($value))));
                        $max = $this->getFieldMaximumValue(array_unique(array_filter($fieldValues, fn($value) => !empty($value))));

                        $minValue = $min;
                        $maxValue = $max;

                        $retrievedValue = '';
                        $retrievedValues = [];
                        $query = Uri::getInstance()->getQuery();
                        parse_str($query, $query);
                        if (isset($query['dc_filter_' . $fieldId]) && !empty($query['dc_filter_' . $fieldId])) {
                            $retrievedValue = $query['dc_filter_' . $fieldId];
                            $retrievedValues = explode('l-r', $retrievedValue);
                        }

                        if (!empty($retrievedValues) && count($retrievedValues) === 2) {
                            $minValue = $retrievedValues[0];
                            $maxValue = $retrievedValues[1];
                        }
                    
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-slider-option">';
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-slider-wrapper">';
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-slider-labels">';
                        $output .= '<span class="slider-min-value" data-value="'.$minValue.'" data-field-id="' . $fieldId  . '" >'.$minValue.'</span>';
                        $output .= '<span>-</span>';
                        $output .= '<span class="slider-max-value" data-value="'.$maxValue.'" data-field-id="' . $fieldId  . '" >'.$maxValue.'</span>';
                        $output .= '</div>';
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-slider-container">';
                        $output .= '<div class="dual-range-track">';
                        $output .= '<div class="dual-range-fill"></div>';
                        $output .= '</div>';
                        $output .= '<input type="range" min="'.$min.'" max="'.$max.'" value="'.$minValue.'" class="dual-range-input min-thumb" data-field-id="' . $fieldId  . '" name="filter_slider_min">';
                        $output .= '<input type="range" min="'.$min.'" max="'.$max.'" value="'.$maxValue.'" class="dual-range-input max-thumb" data-field-id="' . $fieldId  . '" name="filter_slider_max">';
                        $output .= '</div>';
                        $output .= '</div>';
                        $output .= '</div>';    
                        break;
                    }

                case 'switch': {
                        $retrievedValue = '';
                        $query = Uri::getInstance()->getQuery();
                        parse_str($query, $query);
                        if (isset($query['dc_filter_' . $fieldId])) {
                            $retrievedValue = $query['dc_filter_' . $fieldId];
                        }

                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-switch-option" data-field-id="' . $fieldId  . '">';
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-switch-wrapper">';
                        $output .= '<input type="checkbox" hidden name="filter_option"  ' . (!empty($retrievedValue) ? 'checked' : '') . '>';
                        $output .= '<span class="sppb-addon-dynamic-content-filter-item-switch-slider"></span>';
                        $output .= '</div>';
                        
                        if($showCount){
                            $count = $this->getFieldCount(1, $fieldValues);
                            $output .= '<label class="sppb-addon-dynamic-content-filter-item-count">(' . $count . ')</label>';
                        }

                        $output .= '</div>';
                    break;
                }

                case 'button': {
                    $retrievedValue = '';
                    $query = Uri::getInstance()->getQuery();
                    parse_str($query, $query);
                    if (isset($query['dc_filter_' . $fieldId])) {
                        $retrievedValue = $query['dc_filter_' . $fieldId];
                    }

                    $settings = $this->addon->settings;

                    $class = '';
                    $class .= (isset($settings->button_type) && $settings->button_type) ? ' dc-filter-btn-' . $settings->button_type : '';
                    $class .= (isset($settings->button_block) && $settings->button_block) ? ' ' . $settings->button_block : '';
                    $class .= (isset($settings->button_shape) && $settings->button_shape) ? ' dc-filter-btn-' . $settings->button_shape : ' dc-filter-btn-rounded';
                    $class .= (isset($settings->button_appearance) && $settings->button_appearance) ? ' dc-filter-btn-' . $settings->button_appearance : '';

                    $isFieldIdReference = $filterService->isReferenceField($fieldId, $collectionId);
                    $referenceCounts = [];
                    if($isFieldIdReference){
                        $referenceCounts = $filterService->getCollectionItemsFromReferenceField($fieldId, $collectionId);
                    }
                    
                    foreach(array_unique(array_filter($fieldValues, fn($value) => !empty($value))) as $value){
                        if ($retrievedValue === $value) {
                            $selectedClass = ' dc-filter-btn-selected';
                        } else {
                            $selectedClass = '';
                        }
                        $output .= '<div class="sppb-addon-dynamic-content-filter-item-button-option">';
                        $output .= '<button class=" dc-filter-btn '. $class . $selectedClass . '"  type="button" name="filter_option" data-value="' . $value . '" data-field-id="' . $fieldId . '">' . $value . '</button>';

                        if($showCount){
                            if($isFieldIdReference){
                                $count = isset($referenceCounts[$value]) ? $referenceCounts[$value] : 0;
                            } else {
                                $count = $this->getFieldCount($value, $fieldValues);
                            }
                            $output .= '<label class="sppb-addon-dynamic-content-filter-item-count">(' . $count . ')</label>';
                        }

                        $output .= '</div>';
                    }
                    break;
                }

                case 'date': {
                    $output .= '<div class="sppb-addon-dynamic-content-filter-item-date-option">';
                    $output .= '<div class="sppb-addon-dynamic-content-filter-date-picker-container">';
                    $output .= '<input type="text" class="sppb-addon-dynamic-content-filter-date-picker" placeholder="Select date" name="filter_date" data-filter-field-id="' . $fieldId . '" readonly>';
                    $output .= '<span class="sppb-addon-dynamic-content-filter-date-icon"><i class="fas fa-calendar-alt"></i></span>';
                    $output .= '</div>';
                    $output .= '</div>';
                    break;
                }

                case 'date-range': {
                    $output .= '<div class="sppb-addon-dynamic-content-filter-item-date-option">';
                    $output .= '<div class="sppb-addon-dynamic-content-filter-date-picker-container" style="width: 260px">';
                    $output .= '<input type="text" class="sppb-addon-dynamic-content-filter-date-picker" date-range="true" placeholder="Select date" name="filter_date" data-filter-field-id="' . $fieldId . '"  readonly>';
                    $output .= '<span class="sppb-addon-dynamic-content-filter-date-icon"><i class="fas fa-calendar-alt"></i></span>';
                    $output .= '</div>';
                    $output .= '</div>';
                    break;
                }
            }
    }
    return $output;
    }

    public function js(){
        $addonId = '#sppb-addon-' . $this->addon->id;
        $settings = $this->addon->settings;

        $js = '';
        $js .= 'jQuery(document).ready(function($) {
            
        var addonId = "' . $this->addon->id . '";
        if (window["sppbAddonInitialized_" + addonId]) {
            return;
        }
        window["sppbAddonInitialized_" + addonId] = true;

        const isEditMode = window.location.href.includes("/edit/") || window.location.href.includes("layout=edit");
        
        function initializeDropdowns() {
            $("'. $addonId . ' .sppb-addon-dynamic-content-filter-dropdown-trigger").each(function() {
                const $trigger = $(this);

                if ($trigger.data("dropdown-initialized"))
                {
                    return;
                }
                
                const dropdownId = $trigger.data("dropdown-id");
                const $content = $("#" + dropdownId + "-content");
                
                $content.appendTo("#sppb-dropdown-portal");
                
                const $popup = $("#" + dropdownId + "-popup");
                const triggerOffset = $trigger.offset();
                const portalOffset = $("#sppb-dropdown-portal").offset();
                
                $popup.css({
                    "position": "absolute",
                    "top": (triggerOffset.top - portalOffset.top) + $trigger.outerHeight() + "px",
                    "left": (triggerOffset.left - portalOffset.left) + "px",
                    "width": "auto",
                    "z-index": 9999
                });
                
                $trigger.on("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isActive = $trigger.hasClass("active");
                    
                    $(".sppb-addon-dynamic-content-filter-dropdown-popup").removeClass("active");
                    $(".sppb-addon-dynamic-content-filter-dropdown-trigger").removeClass("active");
                    
                    if (!isActive) {
                        $popup.addClass("active");
                        $trigger.addClass("active");
                    } else {
                        $popup.removeClass("active");
                        $trigger.removeClass("active");
                    }
                });
                
                $trigger.data("dropdown-initialized", true);
            });
        }
        
        function observeDropdownChanges() {
            const observer = new MutationObserver(function(mutations) {
                let hasNewDropdowns = false;
                
                mutations.forEach(function(mutation) {
                    if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && $(node).find(".sppb-addon-dynamic-content-filter-dropdown-trigger").length) {
                                hasNewDropdowns = true;
                            }
                        });
                    }
                });
                
                if (hasNewDropdowns) {
                    initializeDropdowns();
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
        
        // Radio
        $("'. $addonId . ' .sppb-addon-dynamic-content-filter-item-radio-option").on("click", function() {
            $(this).find("input[type=radio]").prop("checked", true).trigger("change");

            if (isEditMode) {
                return;
            }

            var value = $(this).find("input[type=radio]").val();
            var fieldId = $(this).find("input[type=radio]").data("filter-field-id");

            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            if (value) {
                params.set("dc_filter_" + fieldId, value);
            } else {
                params.delete("dc_filter_" + fieldId);
            }

            url.search = params.toString();
            window.location.href = url.toString();
        });
        
        // Checkbox
        $("'. $addonId . ' .sppb-addon-dynamic-content-filter-item-checkbox-option").on("click", function() {
        const checkbox = $(this).find("input[type=checkbox]");
        const checkboxValue = checkbox.val();
        const isChecked = !checkbox.prop("checked");
        
        $(this).closest(".sppb-addon-dynamic-content-filter-wrapper").find(".sppb-addon-dynamic-content-filter-item-checkbox-option input[type=checkbox]").each(function() {
            if ($(this).val() === checkboxValue) {
                $(this).prop("checked", isChecked).trigger("change");
            }
        });

        if (isEditMode) {
            return;
        }

        const checkedValues = [];
        var valueString = "";

        $(this).parent().find(".sppb-addon-dynamic-content-filter-item-checkbox-option").each(function () {
            const input = $(this).find("input[type=checkbox]");
            if (input.prop("checked")) {
                checkedValues.push(input.val());
            }
        });

        valueString = checkedValues.join(",");
        var fieldId = $(this).find("input[type=checkbox]").data("filter-field-id");

        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);

        if (valueString) {
            params.set("dc_filter_" + fieldId, valueString);
        } else {
            params.delete("dc_filter_" + fieldId);
        }

        url.search = params.toString();
        window.location.href = url.toString();
        
        });

        setTimeout (() => {
            $("'. $addonId . ' input[data-filter-search]").on("keypress", function(e) {
                if (isEditMode) {
                    return;
                }
                var collectionId = $(this).data("filter-collection-id");
                if (e.which == 13) {
                    e.preventDefault();
                    var currentUrl = window.location.href;
                    var value = $(this).val();
                    if (value) {
                        const url = new URL(window.location.href);
                        const params = new URLSearchParams(url.search);

                        params.set("dc_query_" + collectionId, value);

                        url.search = params.toString();
                        window.location.href = url.toString();
                    } else if (!value) {
                        const url = new URL(window.location.href);
                        const params = new URLSearchParams(url.search);

                        params.delete("dc_query_" + collectionId);

                        url.search = params.toString();
                        window.location.href = url.toString();
                    }
                }
            });

            $("'. $addonId . ' .sppb-addon-dynamic-content-filter-input").on("keypress", function(e) {
                if (isEditMode) {
                    return;
                }
                if (e.which == 13) {
                e.preventDefault();
                var value = $(this).val();
                var fieldId = $(this).data("filter-field-id");

                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);

                if (value) {
                    params.set("dc_filter_" + fieldId, value);
                } else {
                    params.delete("dc_filter_" + fieldId);
                }

                url.search = params.toString();
                window.location.href = url.toString();
                }
            });

            $("'. $addonId . ' .sppb-addon-dynamic-content-filter-reset-btn").on("click", function() {
                if (isEditMode) {
                    return;
                }
            const url = window.location.href;
            const urlObj = new URL(url);
                const params = urlObj.searchParams;
                const extracted = {};

                for (const key of [...params.keys()]) {
                    if (key.startsWith("dc_query") || key.startsWith("dc_filter")) {
                    extracted[key] = params.get(key);
                    params.delete(key);
                    }
                }

                const cleanedUrl = urlObj.origin + urlObj.pathname + (params.toString() ? `?${params}` : "") + urlObj.hash;
                window.location.href = cleanedUrl;
            });
            
        $("'. $addonId . ' .sppb-addon-dynamic-content-filter-reset-btn-alt").on("click", function() {
            if (isEditMode) {
                return;
            }
            const url = window.location.href;
            const urlObj = new URL(url);
                const params = urlObj.searchParams;
                const extracted = {};

                for (const key of [...params.keys()]) {
                    if (key.startsWith("dc_query") || key.startsWith("dc_filter")) {
                    extracted[key] = params.get(key);
                    params.delete(key);
                    }
                }

                const cleanedUrl = urlObj.origin + urlObj.pathname + (params.toString() ? `?${params}` : "") + urlObj.hash;
                window.location.href = cleanedUrl;
            });

            // Button
            $("'. $addonId . ' .sppb-addon-dynamic-content-filter-item-button-option button").on("click", function() {
            if (isEditMode) {
                return;
            }
            const value = $(this).data("value");
            $(this).closest(".sppb-addon-dynamic-content-filter-item").find("input[name=filter_option]").val(value).trigger("change");
            var fieldId = $(this).data("field-id");

            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            if (value) {
                params.set("dc_filter_" + fieldId, value);
            } else {
                params.delete("dc_filter_" + fieldId);
            }

            url.search = params.toString();
            window.location.href = url.toString();
        });

        var minSliders = $("'. $addonId . ' .min-thumb");
        var maxSliders = $("'. $addonId . ' .max-thumb");

        minSliders.on("click", function () {
            if (isEditMode) {
                return;
            }
            const minValue = $(minSliders).closest(".sppb-addon-dynamic-content-filter-item-slider-wrapper").find(".slider-min-value").attr("data-value");
            const maxValue = $(maxSliders).closest(".sppb-addon-dynamic-content-filter-item-slider-wrapper").find(".slider-max-value").attr("data-value");

            const minSliderValue = minValue;
            const maxSliderValue = maxValue;

            var fieldId = $(minSliders).attr("data-field-id");

            const processedValue = minSliderValue + "l-r" + maxSliderValue;
            
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            if (processedValue) {
                params.set("dc_filter_" + fieldId, processedValue);
            } else {
                params.delete("dc_filter_" + fieldId);
            }

            url.search = params.toString();
            window.location.href = url.toString();

        });

        maxSliders.on("click", function () {
            if (isEditMode) {
                return;
            }
        const minValue = $(minSliders).closest(".sppb-addon-dynamic-content-filter-item-slider-wrapper").find(".slider-min-value").attr("data-value");
            const maxValue = $(maxSliders).closest(".sppb-addon-dynamic-content-filter-item-slider-wrapper").find(".slider-max-value").attr("data-value");

            const minSliderValue = minValue;
            const maxSliderValue = maxValue;

            const processedValue = minSliderValue + "l-r" + maxSliderValue;

            var fieldId = $(maxSliders).attr("data-field-id");

            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            if (processedValue) {
                params.set("dc_filter_" + fieldId, processedValue);
            } else {
                params.delete("dc_filter_" + fieldId);
            }

            url.search = params.toString();
            window.location.href = url.toString();

        });

            minSliders.on("input change", function() {
            const container = $(this).closest(".sppb-addon-dynamic-content-filter-item-slider-container");
            const maxSlider = container.find(".max-thumb");
            
            if (parseInt($(this).val()) > parseInt(maxSlider.val())) {
                $(this).val(maxSlider.val());
            }
            
            updateSliderFill(container);
            
            const value = $(this).val();
            $(this).closest(".sppb-addon-dynamic-content-filter-item-slider-wrapper")
                .find(".slider-min-value")
                .text(value)
                .attr("data-value", value);
        });
        
        maxSliders.on("input change", function() {
            const container = $(this).closest(".sppb-addon-dynamic-content-filter-item-slider-container");
            const minSlider = container.find(".min-thumb");
            
            if (parseInt($(this).val()) < parseInt(minSlider.val())) {
                $(this).val(minSlider.val());
            }
            
            updateSliderFill(container);
            
            const value = $(this).val();
            $(this).closest(".sppb-addon-dynamic-content-filter-item-slider-wrapper")
                .find(".slider-max-value")
                .text(value)
                .attr("data-value", value);
        });

                    // Date
        var localeEn = {
            "days": ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            "daysShort": ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
            "daysMin": ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
            "months": ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            "monthsShort": ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            "today": "Today",
            "clear": "Clear",
            "dateFormat": "MM/dd/yyyy",
            "timeFormat": "hh:mm aa",
            "firstDay": 0
        };

        $("'. $addonId . ' .sppb-addon-dynamic-content-filter-date-picker").each(function() {
        var datepickerId = "sppb-addon-'. $this->addon->id .'";
        var isDateRange = $(this).attr("date-range") === "true";

            var fieldId = $(this).data("filter-field-id");

            var currentUrl = window.location.href;
            var queryString = currentUrl.split("?")[1];
            var urlParams = new URLSearchParams(queryString);
            var dateValue = urlParams.get("dc_filter_" + fieldId);
            var dateValue = dateValue ? decodeURIComponent(dateValue) : "";

            if (isDateRange) {
                dateValue = dateValue?.split("l-to-r");
                if (dateValue?.length === 1 && !dateValue[0]) {
                    dateValue = "";
                }
            }

            if (isEditMode) {
                dateValue = "";
            }

            new AirDatepicker(this, {
                dateFormat: "yyyy-MM-dd",
                autoClose: true,
                range: isDateRange,
                position: "bottom left",
                classes: "sppb-addon-dynamic-content-filter-datepicker-dropdown",
                selectedDates: dateValue ? (!isDateRange ? [new Date(dateValue)] : [new Date(dateValue[0]), new Date(dateValue[1])]) : [],
                locale: localeEn,
                onShow: function(dp) {
                    setTimeout(function() {
                        $(".air-datepicker").attr("id", datepickerId);
                    }, 0);
                },
                onSelect: function(date) {
                    $(this.el).trigger("change");

                    if (isEditMode) {
                        return;
                    }

                    var formattedDate = date?.formattedDate;

                    const url = new URL(window.location.href);
                    const params = new URLSearchParams(url.search);

                    if (isDateRange) {
                        if (Array.isArray(formattedDate) && formattedDate.length === 2) {
                            const formattedDateString = `${formattedDate[0]}l-to-r${formattedDate[1]}`;
                            params.set("dc_filter_" + fieldId, formattedDateString);
                            url.search = params.toString();
                            window.location.href = url.toString();
                        } else if (!formattedDate || formattedDate?.length === 0) {
                            params.delete("dc_filter_" + fieldId);
                            url.search = params.toString();
                            window.location.href = url.toString();
                        }
                    } else {
                        if (formattedDate) {
                            params.set("dc_filter_" + fieldId, "dateval-" + formattedDate);
                            url.search = params.toString();
                            window.location.href = url.toString();
                        } else {
                            params.delete("dc_filter_" + fieldId);
                            url.search = params.toString();
                            window.location.href = url.toString();
                        }
                    }
                },
                buttons: [
                    {
                        content: "Clear",
                        className: "air-datepicker-button-clear",
                        onClick: (dp) => {
                            dp.clear();
                            $(dp.el).trigger("change");
                        }
                    }
                ]
            });
        });
        }, 0);

        // Switch
        $("'. $addonId . ' .sppb-addon-dynamic-content-filter-item-switch-option").on("click", function() {
            const checkbox = $(this).find("input[type=checkbox]");
            checkbox.prop("checked", !checkbox.prop("checked")).trigger("change");

            if (isEditMode) {
                return;
            }

            var fieldId = $(this).attr("data-field-id");

            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            if (!params.get("dc_filter_" + fieldId)) {
                params.set("dc_filter_" + fieldId, 1);
            } else {
                params.delete("dc_filter_" + fieldId);
            }

            url.search = params.toString();
            window.location.href = url.toString();
        });

        // Slider
        var minSliders = $("'. $addonId . ' .min-thumb");
        var maxSliders = $("'. $addonId . ' .max-thumb");
        
        function updateSliderFill(container) {
            const minInput = container.find(".min-thumb");
            const maxInput = container.find(".max-thumb");
            const track = container.find(".dual-range-track");
            const fill = container.find(".dual-range-fill");
            
            const min = parseInt(minInput.attr("min"));
            const max = parseInt(maxInput.attr("max"));
            const minVal = parseInt(minInput.val());
            const maxVal = parseInt(maxInput.val());
            
            const minPercent = ((minVal - min) / (max - min)) * 100;
            const maxPercent = ((maxVal - min) / (max - min)) * 100;
            
            fill.css({
                "left": minPercent + "%",
                "width": (maxPercent - minPercent) + "%"
            });
        }
        
        $("'. $addonId . ' .sppb-addon-dynamic-content-filter-item-slider-container").each(function() {
            updateSliderFill($(this));
        });
        
        $(document).off("click.sliderValues' . $this->addon->id . '").on("click.sliderValues' . $this->addon->id . '", "'. $addonId . ' .slider-min-value, '. $addonId . ' .slider-max-value", function() {
            if('. (isset($settings->editable_label_field) && $settings->editable_label_field ? 'false' : 'true') . '){
                return;
            }
            
            makeInputEditable($(this));
        });
        
        function makeInputEditable(element) {
            const value = element.attr("data-value");
            const isMin = element.hasClass("slider-min-value");
            const fieldId = element.attr("data-field-id");
            
            const input = $("<input>")
                .attr("type", "number")
                .attr("class", isMin ? "slider-min-input" : "slider-max-input")
                .attr("value", value)
                .on("blur", function() {
                    const newValue = $(this).val();
                    const container = $(this).closest(".sppb-addon-dynamic-content-filter-item-slider-wrapper");
                    const sliderName = isMin ? "filter_slider_min" : "filter_slider_max";
                    const slider = container.find(`[name="${sliderName}"]`);
                    
                    const min = parseInt(slider.attr("min"));
                    const max = parseInt(slider.attr("max"));
                    let validValue = parseInt(newValue);
                    
                    if (isNaN(validValue)) {
                        validValue = isMin ? min : max;
                    } else {
                        validValue = Math.max(min, Math.min(validValue, max));
                    }
                    
                    if (isMin) {
                        const maxValue = parseInt(container.find("[name=filter_slider_max]").val());
                        validValue = Math.min(validValue, maxValue);
                    } else {
                        const minValue = parseInt(container.find("[name=filter_slider_min]").val());
                        validValue = Math.max(validValue, minValue);
                    }

                    let queryValueMin = null;
                    let queryValueMax = null;

                    if (isMin) {
                        queryMinValue = validValue;
                        queryMaxValue = jQuery("' . $addonId . ' .slider-max-value").attr("data-value");
                    } else {
                        queryMinValue = jQuery("' . $addonId . ' .slider-min-value").attr("data-value");
                        queryMaxValue = validValue;
                    }
                    
                    slider.val(validValue).trigger("change");
                    
                    $(this).replaceWith(
                        $("<span>")
                            .attr("class", isMin ? "slider-min-value" : "slider-max-value")
                            .attr("data-value", validValue)
                            .text(validValue)
                    );

                    if (isEditMode) {
                        return;
                    }

                    const processedValue = queryMinValue + "l-r" + queryMaxValue;

                    const url = new URL(window.location.href);
                    const params = new URLSearchParams(url.search);

                    if (processedValue) {
                        params.set("dc_filter_" + fieldId, processedValue);
                    } else {
                        params.delete("dc_filter_" + fieldId);
                    }

                    url.search = params.toString();
                    window.location.href = url.toString();
                })
                .on("keydown", function(e) {
                    if (e.key === "Enter") {
                        $(this).blur();
                    }
                });
            
            element.replaceWith(input);
            input.focus();
        }
        
        // Accordion
        $("'. $addonId . ' .sppb-panel-heading").on("click", function(e) {
            e.preventDefault();
            e.stopPropagation(); 
            
            const $this = $(this);
            const $collapse = $this.next(".sppb-panel-collapse");
            
            if ($this.hasClass("active")) {
                $this.removeClass("active");
                $collapse.slideUp();
            } else {
                $this.addClass("active");
                $collapse.slideDown();
            }
            
            $this.find(".sppb-toggle-direction i").toggleClass("fa-chevron-right fa-chevron-right");
        });
        
        // Dropdown
        if (!$("#sppb-dropdown-portal").length) {
            $("body").append("<div id=\'sppb-dropdown-portal\'></div>");
        }
        
        $("#sppb-dropdown-portal").css({
            "position": "relative",
            "z-index": 9999,
            "width": "auto",
        });
        
        initializeDropdowns();
        observeDropdownChanges();
        
        $(document).off("click.dropdown' . $this->addon->id . '").on("click.dropdown' . $this->addon->id . '", function(e) {
            if (!$(e.target).closest(".sppb-addon-dynamic-content-filter-dropdown-wrapper, .sppb-addon-dynamic-content-filter-dropdown-popup, .air-datepicker, .air-datepicker-global-container").length) {
                $(".sppb-addon-dynamic-content-filter-dropdown-popup").removeClass("active");
                $(".sppb-addon-dynamic-content-filter-dropdown-trigger").removeClass("active");
            }
        });
        
        $(document).off("click.dropdownPopup' . $this->addon->id . '").on("click.dropdownPopup' . $this->addon->id . '", "'. $addonId . ' .sppb-addon-dynamic-content-filter-dropdown-popup", function(e) {
            e.stopPropagation();
        });
        });';
        
        return $js;
    }

    public function scripts()
	{
		return [
            HTMLHelper::_('script', 'components/com_sppagebuilder/assets/js/air-datepicker.min.js', []),
		];
	}

    public function stylesheets()
	{
		return [
			Uri::base(true) . '/components/com_sppagebuilder/assets/css/air-datepicker.min.css',
		];
	}

    private function getFieldCount($value, $fieldValues){
        $count = 0;
        foreach($fieldValues as $fieldValue){
            if($fieldValue == $value){
                $count++;
            }
        }
        return $count;
    }

    private function getBoolean($value){
        if($value === 'true' || $value === true || $value === 1){
            return true;
        }else{
            return false;
        }
    }

    public function css(){
        $addon_id = '#sppb-addon-' . $this->addon->id;
		$settings = $this->addon->settings;
		$cssHelper = new CSSHelper($addon_id);
        $css = '';

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item', [], [], [], [], [], [],
            'display: flex; align-items: flex-start; width: auto; flex-direction: column;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item.switch-override',[], [], [], [], [], [],
        'flex-direction: row; gap: 8px;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item.sppb-filter-accordion-width', [], [], [], [], [], [],
        'width: 100%;'
        );

        $css .= $cssHelper->generateStyle('.sppb-panel.sppb-panel-custom', [], [], [], [], [], [],
            'width: 100%;'
        );
        
        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-button-option', [], [], [], [], [], [],
            'display: flex; align-items: center; gap: 8px;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn', [], [], [], [], [], [],
        'outline: none; border: none; cursor: pointer; padding: 8px 12px; box-shadow: 0 0 0 0 #FFFFFF; display: inline-block; text-align: center; transition: all 0.15s ease-in-out;');

        $css .= $cssHelper->generateStyle('.dc-filter-btn-default', [], [], [], [], [], [],
            'background: #eff1f4; color: inherit; 
             '
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-default:hover, .dc-filter-btn-default.dc-filter-btn-selected', [], [], [], [], [], [],
            'color: inherit; background: #d7dadd; border-color: #d7dadd;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-rounded', [], [], [], [], [], [],
            'border-radius: 4px;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-round', [], [], [], [], [], [],
            'border-radius: 32px;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-square', [], [], [], [], [], [],
            'border-radius: 0;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-outline', [], [], [], [], [], [],
            'border: 1px solid #eff1f4; color: #d7dadd; background: transparent;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-block', [], [], [], [], [], [],
            'width: 100%;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-outline:hover, .dc-filter-btn-outline.dc-filter-btn-selected', [], [], [], [], [], [],
            'color: inherit; background: #eff1f4; border-color: #eff1f4;'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-gradient', [], [], [], [], [], [],
            'background-image: linear-gradient(-180deg, #eff1f2 0%, #c2c3c3 100%); color: #6a6a6a; border: none; '
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-gradient:hover, .dc-filter-btn-gradient.dc-filter-btn-selected', [], [], [], [], [], [],
            'color: inherit; background: #d7dadd; border-color: #d7dadd; background-image: linear-gradient(-180deg, #eff1f2 0%, #c2c3c3 100%);'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-custom',
                    $settings,
                    [
                        'button_color' => 'color',
                        'button_background_color' => 'background',
                    ],
                    [
                        'button_color' => false,
                        'button_background_color' => false,
                    ]
                    );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-custom:hover, .dc-filter-btn-custom.dc-filter-btn-selected',
                    $settings,
                    [
                        'button_color_hover' => 'color',
                        'button_background_color_hover' => 'background',
                    ],
                    [
                        'button_color_hover' => false,
                        'button_background_color_hover' => false,
                    ]
                    );

        $backgroundGradient = $cssHelper->parseColor($settings, 'button_background_gradient');
        $backgroundGradientHover = $cssHelper->parseColor($settings, 'button_background_gradient_hover');

        $css .= $cssHelper->generateStyle('.dc-filter-btn-custom.dc-filter-btn-gradient',
                    $settings,
                    [
                        'button_color' => 'color',
                    ],
                    [
                       'button_color' => false,
                    ]
                    );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-custom.dc-filter-btn-gradient:hover, .dc-filter-btn-custom.dc-filter-btn-gradient.dc-filter-btn-selected',
                    $settings,
                    [
                        'button_color_hover' => 'color',
                    ],
                    [
                       'button_color_hover' => false,
                    ]
                    );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-custom.dc-filter-btn-gradient',
        [],[],[],[],[],[],'background-image: ' . $backgroundGradient . ';'
        );

        $css .= $cssHelper->generateStyle('.dc-filter-btn-custom.dc-filter-btn-gradient:hover, .dc-filter-btn-custom.dc-filter-btn-gradient.dc-filter-btn-selected',
        [],[],[],[],[],[],'background-image: ' . $backgroundGradientHover . ';'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-reset-btn-wrapper',
                     $settings,
                     [
                        'reset_btn_alignment' => 'justify-content',               
                     ],
                     [
                        'reset_btn_alignment' => false,
                       
                     ],[],[],[],
                     'display: flex;'
                     );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-reset-btn',
                        $settings,
                        [
                            'reset_btn_color' => 'color',
                        ],
                        [
                            'reset_btn_color' => false,
                        ]
                        ,[],[],[],
                        'background: transparent; border: none; cursor: pointer; padding: 0; text-decoration: underline;');


        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-switch-option',
                    [],[],[],[],[],[],
                    'display: flex; align-items: center; cursor: pointer; gap: 8px;'
                    );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-switch-wrapper',
                     [],[],[],[],[],[],
                     'position: relative; display: inline-block; width: 36px; height: 20px;');

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-switch-slider',
                     $settings,
                     [
                        'switch_track_off_color' => 'background',
                        'switch_track_border_radius' => 'border-radius',
                     ],
                     [
                        'switch_track_off_color' => false
                     ],[],[],[],
                     '
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    -webkit-transition: .4s;
                    transition: .4s;
                    display: flex;
                    align-items: center;
                     ');

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-switch-slider:before',
                     $settings,
                     [
                        'switch_thumb_off_color' => 'background',
                        'switch_thumb_size' => ['height', 'width'],
                        'switch_thumb_border_radius' => 'border-radius',
                     ],
                     [
                        'switch_thumb_off_color' => false
                     ],[],[],[],
                     ' position: absolute;
                        content: "";
                        margin: 0px 2px;
                        
                        -webkit-transition: .4s;
                        transition: .4s;');

        
        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-switch-option input:checked + .sppb-addon-dynamic-content-filter-item-switch-slider:before',
                     $settings,['switch_thumb_on_color' => 'background'],['switch_thumb_on_color' => false]);


        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-switch-option input:checked + .sppb-addon-dynamic-content-filter-item-switch-slider',
                     $settings,
                     [
                        'switch_track_on_color' => 'background'
                     ],
                     [
                        'switch_track_on_color' => false
                     ]);


        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-switch-option input:checked + .sppb-addon-dynamic-content-filter-item-switch-slider:before',
                     [],[],[],[],[],[],
                     'transform: translateX(16px);');


        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-slider-wrapper', [], [], [], [], [], [], 
            'width: 100%; position: relative;');

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-slider-labels', $settings, ['slider_label_color' => 'color'], ['slider_label_color' => false], [], [], [], 
            'display: flex; gap: 12px; align-items: center; height: 32px; justify-content: center;');

        $css .= $cssHelper->typography('.sppb-addon-dynamic-content-filter-item-slider-labels', $settings, 'slider_label_typography',
                                        [
                                            'font'           => 'slider_label_font_family',
                                            'size'           => 'slider_label_fontsize',
                                            'line_height'    => 'slider_label_lineheight',
                                            'letter_spacing' => 'slider_label_letterspace',
                                            'uppercase'      => 'slider_label_font_style.uppercase',
                                            'italic'         => 'slider_label_font_style.italic',
                                            'underline'      => 'slider_label_font_style.underline',
                                            'weight'         => 'slider_label_font_style.weight',
                                        ]
                                        );

        $css .= $cssHelper->generateStyle('.slider-min-value, .slider-max-value', [], [], [], [], [], [], 
            'padding: 2px 5px; ');

        $css .= $cssHelper->generateStyle('.slider-min-input, .slider-max-input', [], [], [], [], [], [], 
            'width: 80px; padding: 2px 5px; border-radius: 4px; border: 1px solid #ddd;');

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-sliders', [], [], [], [], [], [], 
            'display: flex; gap: 12px; align-items: center;');


        $activeSegmentColor = isset($settings->active_segment_color) ? $settings->active_segment_color : '#007bff';
        $trackColor = isset($settings->slider_track_color) ? $settings->slider_track_color : '#e5e5e5';

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-slider-container', [], [], [], [], [], [], 
                'position: relative; height: 40px; width: 200px;');

        $css .= $cssHelper->generateStyle('.dual-range-track', $settings, 
            ['slider_track_height' => 'height'], [], [], [], [], 
            'position: absolute; width: 100%; top: 50%; transform: translateY(-50%); background:' . $trackColor . '; border-radius: 8px;');

        $css .= $cssHelper->generateStyle('.dual-range-fill', $settings, 
            ['slider_track_height' => 'height'], [], [], [], [], 
            'position: absolute; background: ' .$activeSegmentColor . '; border-radius: 8px;');

        $css .= $cssHelper->generateStyle('.dual-range-input', $settings, [], [], [], [], [], 
            'position: absolute; width: 100%; -webkit-appearance: none; background: transparent; top: 50%; transform: translateY(-50%); pointer-events: none;');

        $css .= $cssHelper->generateStyle('.dual-range-input:focus', [], [], [], [], [], [], 
            'outline: none;');

        $css .= $cssHelper->generateStyle('.dual-range-input::-webkit-slider-thumb', $settings,
            [
                'slider_thumb_color' => 'background',
                'slider_thumb_size' => ['height', 'width'],
                'slider_thumb_border_radius' => 'border-radius',
            ],
            [
                'slider_thumb_color' => false
            ],
            [],[],[],
            '-webkit-appearance: none; pointer-events: auto; cursor: move; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,0.3);'
        );

        $css .= $cssHelper->generateStyle('.dual-range-input::-moz-range-thumb', $settings,
            [
                'slider_thumb_color' => 'background',
                'slider_thumb_size' => ['height', 'width'],
                'slider_thumb_border_radius' => 'border-radius',
            ],
            [
                'slider_thumb_color' => false
            ],
            [],[],[],
            'pointer-events: auto; cursor: pointer; border: none; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,0.3);'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-fake-checkbox-input'
                                            , $settings,
                                            ['checkbox_radio_background_color' => 'background',
                                             'checkbox_radio_size' => ['width', 'height'],
                                             'checkbox_radio_margin' => 'margin',
                                             'checkbox_radio_border_radius' => 'border-radius'
                                            ],
                                            ['checkbox_radio_background_color' => false,
                                             'checkbox_radio_margin' => false],
                                            [],[],[],
                                            'position: relative; display:flex; align-items:center;'
                                         );


        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-fake-radio-input',
                                            $settings,
                                            ['checkbox_radio_background_color' => 'background',
                                             'checkbox_radio_size' => ['width', 'height'],
                                             'checkbox_radio_margin' => 'margin'
                                            ],
                                            ['checkbox_radio_background_color' => false,
                                             'checkbox_radio_margin' => false],
                                            [],[],[],
                                            'position: relative; display:flex; align-items:center; border-radius: 50%;'
                                         );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-radio-option input:checked + .sppb-addon-dynamic-content-filter-fake-radio-input',
                                            $settings,
                                            ['checkbox_radio_background_color_active' => 'background'],
                                            ['checkbox_radio_background_color_active' => false]
                                         );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-checkbox-option input:checked + .sppb-addon-dynamic-content-filter-fake-checkbox-input',
                                            $settings,
                                            ['checkbox_radio_background_color_active' => 'background'],
                                            ['checkbox_radio_background_color_active' => false]
                                         );
        
        $css .= $cssHelper->border('.sppb-addon-dynamic-content-filter-fake-radio-input', $settings, 'checkbox_radio_border');

        $css .= $cssHelper->border('.sppb-addon-dynamic-content-filter-fake-checkbox-input', $settings, 'checkbox_radio_border');
       

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-radio-option input:checked + .sppb-addon-dynamic-content-filter-fake-radio-input:after',
                                            $settings,
                                            ['checkbox_radio_check_color' => 'background'],
                                            ['checkbox_radio_check_color' => false]
                                            ,[],[],[],
                                            'content: ""; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60%; height: 60%; border-radius: 50%;'
                                        );
        $currentCheckboxBorderRadius = $cssHelper->getResponsiveValue($settings->checkbox_radio_border_radius);
        $checkboxBorderRadius = isset($currentCheckboxBorderRadius) &&  $currentCheckboxBorderRadius ? (float)$currentCheckboxBorderRadius * 0.6 : '';
        $checkFontSize = $cssHelper->getResponsiveValue($settings->checkbox_radio_size);
        $checkFontSize = isset($checkFontSize) &&  $checkFontSize ? (float)$checkFontSize * 0.4 : '';

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-checkbox-option input:checked + .sppb-addon-dynamic-content-filter-fake-checkbox-input:after',
                                        $settings,
                                        ['checkbox_radio_check_color' => 'background',
                                         
                                        ],
                                        ['checkbox_radio_check_color' => false,
                                        ]
                                        ,[],[],[],
                                        'content: ""; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; height: 80%; border-radius: ' . $checkboxBorderRadius . 'px;'
                                        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-checkbox-option input:checked + .sppb-addon-dynamic-content-filter-fake-checkbox-input i',
                                            $settings,
                                            ['checkbox_radio_background_color_active' => 'color'],
                                            ['checkbox_radio_background_color_active' => false]
                                            ,[],[],[],
                                            'display: block !important; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1; font-size: ' . $checkFontSize . 'px;'
                                        );

        

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-radio-option',
                                            [],[],[],[],[],[],
                                            'display: flex; align-items: center; cursor: pointer; gap: 8px'
                                        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-checkbox-option',
                                            [],[],[],[],[],[],
                                            'display: flex; align-items: center; cursor: pointer; gap: 8px'
                                        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-radio-option label',
                                            [],[],[],[],[],[],
                                            'cursor: pointer;'
                                        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-search-option',
                                            [],[],[],[],[],[],
                                            'display: flex; align-items: center; cursor: pointer;');
        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-checkbox-option label',
                                            [],[],[],[],[],[],
                                            'cursor: pointer;'
                                        );

        $isRow = $settings->direction === 'row' ? true : false;

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-list',
                                           [],[],[],[],[],[],
                                           'display:flex; flex-wrap: wrap;' . ($isRow ? ' align-items: start;' : '')
                                        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-content',
                                            [],[],[],[],[],[],
                                            'display:flex;'
                                        );

        $keyToChange = $settings->direction === 'column' ? 'align-items' : 'justify-content';

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-list',
                                            $settings,
                                            [
                                                'direction' => 'flex-direction',
                                                'gap' => 'gap',
                                                'align_items' => $keyToChange,
                                            ],
                                            [
                                                'direction' => false,
                                                'gap' => 'px',
                                                'align_items' => false,
                                            ],   
                                        );


        $keyToChange = $settings->item_direction === 'column' ? 'align-items' : 'justify-content';

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-content',
                                            $settings,
                                            [
                                                'item_direction' => 'flex-direction',
                                                'list_item_gap' => 'gap',
                                                'align_list_items' => $keyToChange,
                                                'list_item_color' => 'color',
                                            ],
                                            [
                                                'item_direction' => false,
                                                'list_item_gap' => 'px',
                                                'align_list_items' => false,
                                                'list_item_color' => false,
                                            ],   
                                        );

        $css .= $cssHelper->typography('.sppb-addon-dynamic-content-filter-item-content', $settings, 'list_item_typography',
                                        [
                                            'font'           => 'list_item_font_family',
                                            'size'           => 'list_item_fontsize',
                                            'line_height'    => 'list_item_lineheight',
                                            'letter_spacing' => 'list_item_letterspace',
                                            'uppercase'      => 'list_item_font_style.uppercase',
                                            'italic'         => 'list_item_font_style.italic',
                                            'underline'      => 'list_item_font_style.underline',
                                            'weight'         => 'list_item_font_style.weight',
                                        ]
                                        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-title', $settings, ['title_margin' => 'margin'], false);
        
        $css .= $cssHelper->typography('.sppb-addon-dynamic-content-filter-item-title-text', $settings, 'title_typography', [
                                        [
                                            'font'           => 'title_font_family',
                                            'size'           => 'title_fontsize',
                                            'line_height'    => 'title_lineheight',
                                            'letter_spacing' => 'title_letterspace',
                                            'uppercase'      => 'title_font_style.uppercase',
                                            'italic'         => 'title_font_style.italic',
                                            'underline'      => 'title_font_style.underline',
                                            'weight'         => 'title_font_style.weight',
                                        ]
                                      ]);

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-title-text, .sppb-toggle-direction i', $settings, ['title_color' => 'color'], false);

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-item-count', $settings, ['list_item_count_color' => 'color'], false);

            $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-search-wrapper, .sppb-addon-dynamic-content-filter-search-wrapper input',
            $settings,
            [
                'search_background_color' => 'background',
                'search_color' => 'color',
                'search_padding' => 'padding'
            ],
            [
                'search_background_color' => false,
                'search_color' => false,
                'search_padding' => false
            ],
            [], null, true
        );

        $css .= $cssHelper->border('.sppb-addon-dynamic-content-filter-search-wrapper', $settings, 'search_border');


        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-search-wrapper',
            $settings,
            ['search_border_radius' => 'border-radius'],
            ['search_border_radius' => 'px'],
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-search-wrapper input',
            $settings,
            ['search_border_radius' => 'border-radius'],
            ['search_border_radius' => 'px'],
            [],
            null,
            false,
            'border: none;',
        );

        $css .= $cssHelper->typography('.sppb-addon-dynamic-content-filter-search-wrapper input', $settings, 'search_typography');

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-search-wrapper input::placeholder',
            $settings,
            ['search_placeholder_color' => 'color'],
            ['search_placeholder_color' => false]
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-search-icon',
            $settings,
            [
                'search_icon_color' => 'color',
                'search_icon_size' => 'font-size'
            ],
            [
                'search_icon_color' => false,
                'search_icon_size' => 'px'
            ],
            [], [], [],
            'pointer-events: none; z-index: 1; user-select: none;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-search-wrapper',
            [],
            [],
            [],
            [],
            [],
            [],
            'position: relative; width: 100%; display: flex; cursor: default;'
        );

        $css .= $cssHelper->generateStyle(
            '.sppb-addon-dynamic-content-filter-search-wrapper.has-icon',
            $settings,
            [
                'search_icon_spacing' => 'gap',
            ],
            [
                'search_icon_spacing' => 'px',
            ],
            [],
            [],
            [],
        );

        $css .= $cssHelper->generateStyle(
            '.sppb-addon-dynamic-content-filter-search-wrapper.has-icon span',
            $settings,
            [],
            [],
            [],
            [],
            [],
            'margin-top: auto; margin-bottom: auto; margin-left: 8px;'
        );

        $css .= $cssHelper->generateStyle(
            '.sppb-addon-dynamic-content-filter-date-picker-container', 
            [], 
            [], 
            [], 
            [], 
            [], 
            [],
            'position: relative; width: 100%; max-width: 260px;'
        );

        $css .= $cssHelper->generateStyle(
            '.sppb-addon-dynamic-content-filter-date-picker', 
            $settings,
            [
                'date_input_background_color' => 'background',
                'date_input_color' => 'color'
            ],
            [
                'date_input_background_color' => false,
                'date_input_color' => false
            ],
            [], 
            [], 
            [],
            'width: 100%; cursor: pointer;'
        );

        $css .= $cssHelper->border('.sppb-addon-dynamic-content-filter-date-picker', $settings, 'date_input_border');

        $css .= $cssHelper->generateStyle(
            '.sppb-addon-dynamic-content-filter-date-picker', 
            $settings,
            ['date_input_border_radius' => 'border-radius'],
        );

        $css .= $cssHelper->generateStyle(
            '.sppb-addon-dynamic-content-filter-date-picker', 
            $settings,
            ['date_input_padding' => 'padding'],
            ['date_input_padding' => false],
            [],
            [],
            ['date_input_padding' => true]
        );

        $css .= $cssHelper->generateStyle(
            '.sppb-addon-dynamic-content-filter-date-icon',
            $settings,
            [
                'date_input_color' => 'color'
            ], 
            [
                'date_input_color' => false
            ], 
            [], 
            [], 
            [],
            'position: absolute; right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; padding: 10px;'
        );

        $css .= $cssHelper->typography('.sppb-addon-dynamic-content-filter-date-picker', $settings, 'date_typography');

        $css .= $cssHelper->generateStyle(
            '&.sppb-addon-dynamic-content-filter-datepicker-dropdown', 
            $settings,
            [
                'date_background_color' => 'background',
                'date_color' => 'color',
                'date_border_radius' => 'border-radius',
            ],
            [
                'date_background_color' => false,
                'date_color' => false,
            ]
        );

        $css .= $cssHelper->border('&.sppb-addon-dynamic-content-filter-datepicker-dropdown', $settings, 'date_border');

        $css .= $cssHelper->typography('&.sppb-addon-dynamic-content-filter-datepicker-dropdown', $settings, 'date_typography');

        $css .= $cssHelper->generateStyle(
            '.air-datepicker-cell.-selected-, .air-datepicker-cell.-selected-.-focus-', 
            $settings,
            [
                'selected_range_highlight_color' => 'background',
                'date_cell_color' => 'color'
            ],
            [
                'selected_range_highlight_color' => false,
                'date_cell_color' => false
            ],
        );

        $css .= $cssHelper->generateStyle(
            '.air-datepicker-cell', 
            $settings,
            [
                'date_cell_background_color' => 'background',
                'date_cell_color' => 'color'
            ],
            [
                'date_cell_background_color' => false,
                'date_cell_color' => false
            ]
        );

        $css .= $cssHelper->generateStyle('.air-datepicker-nav--title:hover', $settings,
            [
                'date_cell_background_color_hover' => 'background',
                'date_cell_color_hover' => 'color'
            ],
            [
                'date_cell_background_color_hover' => false,
                'date_cell_color_hover' => false
            ]
        );

        $css .= $cssHelper->generateStyle('.air-datepicker-nav--title i', $settings, ['date_color' => 'color'], ['date_color' => false]);

        $css .= $cssHelper->generateStyle('.air-datepicker-body--day-name', $settings,
            [
                'date_cell_background_color' => 'background',
                'date_cell_color' => 'color'
            ],
            [
                'date_cell_background_color' => false,
                'date_cell_color' => false
            ]
        );

        $css .= $cssHelper->generateStyle(
            '.air-datepicker-cell:hover', 
            $settings,
            [
                'date_cell_background_color_hover' => 'background',
                'date_cell_color_hover' => 'color'
            ],
            [
                'date_cell_background_color_hover' => false,
                'date_cell_color_hover' => false
            ]
        );

        $css .= $cssHelper->generateStyle(
            '.air-datepicker-cell.-in-range-', 
            $settings,
            ['selected_range_highlight_color' => 'background'],
            ['selected_range_highlight_color' => false],
            [], 
            [], 
            [],
            'opacity: 0.5;'
        );

        $css .= $cssHelper->generateStyle(
            '.air-datepicker-nav--action, .air-datepicker-button', 
            $settings,
            [
                'date_cell_background_color' => 'background',
                'date_cell_color' => 'color'
            ],
            [
                'date_cell_background_color' => false,
                'date_cell_color' => false
            ]
        );

        $css .= $cssHelper->generateStyle(
            '.air-datepicker-button-clear', [], [], [], [], [], [],
            'border: none; padding: 6px 12px; cursor: pointer; margin-top: 5px; font-size: 14px;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-wrapper', [], [], [], [], [], [],
        'position: relative; width: 100%;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-trigger', [], [], [], [], [], [],
        'display: flex; align-items: center; cursor: pointer; width: 100%;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-arrow', [], [], [], [], [], [],
        'margin-left: 10px; transition: transform 0.3s ease;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-trigger.active .sppb-addon-dynamic-content-filter-dropdown-arrow', [], [], [], [], [], [],
        'transform: rotate(180deg);'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-popup', [], [], [], [], [], [],
        'background: white; padding: 16px; position: absolute; top: calc(100% + 5px); left: 0; width: 100%; z-index: 999; box-shadow: 0 5px 15px rgba(0,0,0,0.1); display: none;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-popup.active', [], [], [], [], [], [],
        'display: block;'
        );

        $css .= $cssHelper->generateStyle(':no-parent'.'#sppb-dropdown-portal', [], [], [], [], [], [],
        'position: absolute; top: 0; left: 0; width: 0; height: 0;'
    );
    
        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-popup', [], [], [], [], [], [],
            'background: white; padding: 16px; width: 100%; box-shadow: 0 5px 15px rgba(0,0,0,0.1); display: none; border-radius: 4px;'
        );

        $css .= $cssHelper->generateStyle('.sppb-addon-dynamic-content-filter-dropdown-popup.active', [], [], [], [], [], [],
            'display: block;'
        );

        $input_css = '';
        $input_css .= (isset($settings->input_background) && $settings->input_background) ? 'background: ' . $settings->input_background . ';' : '';
        $input_css .= (isset($settings->input_color) && $settings->input_color) ? 'color: ' . $settings->input_color . ';' : '';
        
        if (isset($settings->input_border) && is_object($settings->input_border)) {
            $input_css .= (isset($settings->input_border->border_width) && $settings->input_border->border_width) ? 'border-width: ' . $settings->input_border->border_width . ';' : '';
            $input_css .= (isset($settings->input_border->border_color) && $settings->input_border->border_color) ? 'border-color: ' . $settings->input_border->border_color . ';' : '';
            $input_css .= (isset($settings->input_border->border_style) && $settings->input_border->border_style) ? 'border-style: ' . $settings->input_border->border_style . ';' : '';
        }

        $input_css .= (isset($settings->input_border_radius) && $settings->input_border_radius) ? 'border-radius: ' . $settings->input_border_radius . ';' : 'border-radius: 0;';

        if (isset($settings->input_padding)) {
            $input_css .= 'padding: ' . $settings->input_padding . ' !important;';
        }

        if ($input_css) {
            $css .= $addon_id . ' .sppb-addon-dynamic-content-filter-input {';
            $css .= $input_css;
            $css .= 'width: 100%;';
            $css .= 'outline: none;';
            $css .= 'height: 20px;';
            $css .= '}';
        }

        return $css;
    }

    private function getFieldName($fieldId, $collectionSchema)
    {
       foreach ($collectionSchema as $field) {
            if ($field->getItem()->id == $fieldId) {
                return $field->getItem()->name;
            }
        }

        return '';
    }

    private function getFieldMinimumValue($fieldValues){
        if(empty($fieldValues) || !is_array($fieldValues)){
            return 0;
        }

        $minValue = min($fieldValues);
        return $minValue;
    }

    private function getFieldMaximumValue($fieldValues){
        if(empty($fieldValues) || !is_array($fieldValues)){
            return 0;
        }

        $maxValue = max($fieldValues);
        return $maxValue;
    }
}