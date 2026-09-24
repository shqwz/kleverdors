(() => {
    'use strict';
    
    jQuery(document).ready(function ($) {
        const bxSliderSelector = '.sppb-dc-bxslider';
        const observeAttributes = ['data-enable-slider', 'data-enable-arrows', 'data-slider-style', 'data-image-per-slide'];
        let sliderInstance = {};
        let initialLoad = {};

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                var newNodes = mutation.addedNodes;
                if (newNodes !== null) {
                    var $nodes = $(newNodes);
    
                    $nodes.each(function () {
                        var $node = $(this);
                        if ($node.find(bxSliderSelector)) {
                            $node.find(bxSliderSelector).each(function () {
                                const $slider = $(this);
                                const addonId = $slider.attr('data-addon-id');
                                const addon_id = '#sppb-addon-' + addonId;
                                const enableSlider = $slider.attr('data-enable-slider') === 'true' ? true : false;
                                const enableArrows = $slider.attr('data-enable-arrows') === 'true' ? true : false;
                                const sliderStyle = enableSlider ? ($slider.attr('data-slider-style') || 'thumb') : null;
                                const imagePerSlide = $slider.attr('data-image-per-slide') || 1;
    
                                if (!initialLoad[addonId] && !sliderInstance[addonId] && enableSlider && sliderStyle === 'carousel') {
                                    jQuery(function () {
                                        "use strict";

                                        sliderInstance[addonId] = [];
                                
                                        jQuery(addon_id + ' ' + bxSliderSelector).each(function() {
                                            const instance = jQuery(this).bxSlider({
                                              minSlides: imagePerSlide,
                                              maxSlides: imagePerSlide,
                                              slideWidth: 1140,
                                                controls: enableArrows,
                                                nextText: `<i class="fa fa-angle-right" aria-hidden="true"></i>`,
                                                prevText: `<i class="fa fa-angle-left" aria-hidden="true"></i>`,
                                            });
                                            sliderInstance[addonId].push(instance);
                                        });
                                    });
                                    initialLoad[addonId] = true;
                                }
                                
                                if (!initialLoad[addonId] && !sliderInstance[addonId] && enableSlider && sliderStyle === 'thumb') {
                                    jQuery(function () {
                                        "use strict";

                                        sliderInstance[addonId] = [];

                                        jQuery(addon_id + ' ' + bxSliderSelector).each(function(itemIndex) {
                                            const instance = jQuery(this).bxSlider({
                                                pagerCustom: "#sppb-dc-bxpager-" + addonId + '-' + itemIndex,
                                                controls: enableArrows,
                                                nextText: `<i class="fa fa-angle-right" aria-hidden="true"></i>`,
                                                prevText: `<i class="fa fa-angle-left" aria-hidden="true"></i>`,
                                            });
                                            sliderInstance[addonId].push(instance);
                                        });
                                    });
                                    initialLoad[addonId] = true;
                                }
                                if (!initialLoad[addonId] && !sliderInstance[addonId] && enableSlider === false) {
                                    initialLoad[addonId] = true;
                                }
                            });
                        }
                    });
                }
            });
        });

        var attributesObserver = new MutationObserver(function (mutations) {
            const processedClasses = new Set();
            for (const mutation of mutations) {
                if (
                    mutation.type === 'attributes' &&
                    observeAttributes.includes(mutation.attributeName)
                ) {
                    const addonId = $(mutation.target).attr('data-addon-id');
                    const addon_id = '#sppb-addon-' + addonId;
                    const enableSlider = $(mutation.target).attr('data-enable-slider') === 'true' ? true : false;
                    const enableArrows = $(mutation.target).attr('data-enable-arrows') === 'true' ? true : false;
                    const sliderStyle = enableSlider ? ($(mutation.target).attr('data-slider-style') || 'thumb') : null;
                    const imagePerSlide = $(mutation.target).attr('data-image-per-slide') || 1;
                    const targetClass = mutation.target.className;
                    const isEnableSliderChanged = initialLoad[addonId] && mutation.attributeName === 'data-enable-slider' && enableSlider;

                    if (isEnableSliderChanged) {
                        continue;
                    }

                    if (processedClasses.has(targetClass)) continue;
                    processedClasses.add(targetClass);

                    if (initialLoad[addonId] && mutation.attributeName === 'data-enable-slider' && enableSlider) {
                        continue;
                    }

                    if (sliderInstance[addonId]) {
                        if (typeof sliderInstance[addonId]?.destroySlider === 'function') {
                            sliderInstance[addonId].destroySlider();
                            sliderInstance[addonId] = null;
                        } else if (Array.isArray(sliderInstance[addonId])) {
                            sliderInstance[addonId].forEach(instance => instance.destroySlider());
                            sliderInstance[addonId] = null;
                        }
                    }

                    if (initialLoad[addonId] && !sliderInstance[addonId] && enableSlider && sliderStyle === 'carousel') {
                        jQuery(function () {
                            "use strict";
                            sliderInstance[addonId] = [];
                            
                    
                            jQuery(addon_id + ' ' + bxSliderSelector).each(function() {
                                const instance = jQuery(this).bxSlider({
                                  minSlides: imagePerSlide,
                                  maxSlides: imagePerSlide,
                                  slideWidth: 1140,
                                 controls: enableArrows,
                                 nextText: `<i class="fa fa-angle-right" aria-hidden="true"></i>`,
                                 prevText: `<i class="fa fa-angle-left" aria-hidden="true"></i>`,
                                });
                                sliderInstance[addonId].push(instance);
                            });
                        });
                    }
                    
                    if (initialLoad[addonId] && !sliderInstance[addonId] && enableSlider && sliderStyle === 'thumb') {
                        jQuery(function () {
                            "use strict";
                            
                            sliderInstance[addonId] = []
                    
                            jQuery(addon_id + ' ' + bxSliderSelector).each(function(itemIndex) {
                                const instance = jQuery(this).bxSlider({
                                    pagerCustom: "#sppb-dc-bxpager-" + addonId + '-' + itemIndex,
                                    controls: enableArrows,
                                    nextText: `<i class="fa fa-angle-right" aria-hidden="true"></i>`,
                                    prevText: `<i class="fa fa-angle-left" aria-hidden="true"></i>`,
                                });
                                sliderInstance[addonId].push(instance);
                              });
                        });
                    }
              }
            }
          })
    
        var config = {
            childList: true,
            subtree: true,
            attributes: true,
        };

        var attributesConfig = {
            attributes: true,
            subtree: true,
            attributeOldValue: true
        }
    
        // Pass in the target node, as well as the observer options
        observer.observe(document.body, config);
        attributesObserver.observe(document.body, attributesConfig);
    })
})();