/**
 * Site-specific JS overrides for the Agentik template.
 * Loaded automatically by templates/agentik/index.php if this file exists.
 */
(function ($) {
	"use strict";

	$(document).ready(function () {

		/**
		 * Hero slider (SP Page Builder "js_slideshow" addon): disable free
		 * drag/swipe-to-change-slide on the image itself.
		 *
		 * Why: the addon's own script (components/com_sppagebuilder/assets/js/js_slider.js)
		 * binds mousedown/touchstart on the slide stage and calls preventDefault()
		 * on every touchmove while dragging, which blocks native page scrolling
		 * on mobile the moment a finger lands on the hero image. Slide navigation
		 * must only happen through the arrow buttons and the thumbnail dots
		 * (both use separate "click" handlers and are unaffected by this).
		 *
		 * We only remove the drag-initiating handlers, namespaced ".jsSlider"
		 * by the plugin itself, so nothing else the plugin binds (autoplay
		 * pause on hover, click nav, etc.) is touched.
		 */
		$(".sp-slider-outer-stage").off(
			"mousedown.jsSlider touchstart.jsSlider touchcancel.jsSlider mouseup.jsSlider touchend.jsSlider"
		);

		// Belt-and-suspenders: prevent the browser's native "drag this image"
		// ghost on the slide images themselves.
		$(".sppb-addon-sp-slider img").attr("draggable", false);
	});

})(jQuery);
