/**
 * @package Helix Ultimate Framework
 * @author JoomShaper https://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2023 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */

// Preloader
jQuery(window).on("load", function () {
    if (jQuery(".sp-loader-with-logo").length > 0) {
        move();
    }
    jQuery(".sp-pre-loader").fadeOut(500, function () {
        jQuery(this).remove();
    });
});

/**
 * Move the progress bar
 */
function move() {
    var elem = document.getElementById("line-load");
    var width = 1;
    var id = setInterval(frame, 10);
    function frame() {
        if (width >= 100) {
            clearInterval(id);
        } else {
            width++;
            elem.style.width = width + "%";
        }
    }
}

jQuery(function ($) {
    /**
     * Helix settings data
     *
     */
    var settings = Joomla.getOptions("data") || {};

    /**
     * sticky header
     *
     * @param {string} className the header className
     */
    var handleStickiness = function (className, offsetTop) {
        if ($("body:not(.layout-edit-iframe)").hasClass(className)) {
            var $header = $("#sp-header");
            var headerHeight = $header.outerHeight();
            var $stickyHeaderPlaceholder = $(".sticky-header-placeholder");
            let $stickyOffset = "100";

            if (
                settings.header !== undefined &&
                settings.header.stickyOffset !== undefined
            ) {
                $stickyOffset = settings.header.stickyOffset || "100";
            }

            var stickyHeader = function () {
                var scrollTop = $(window).scrollTop();
                if (scrollTop >= offsetTop + Number($stickyOffset)) {
                    $header.addClass("header-sticky");
                    $stickyHeaderPlaceholder.height(headerHeight);
                } else {
                    if ($header.hasClass("header-sticky")) {
                        $header.removeClass("header-sticky");
                        $stickyHeaderPlaceholder.height("inherit");
                    }
                }
            };

            stickyHeader();
            $(window).scroll(function () {
                stickyHeader();
            });

            if ($("body").hasClass("layout-boxed")) {
                var windowWidth = $header.parent().outerWidth();
                $header.css({ "max-width": windowWidth, left: "auto" });
            }
        } else {
            var $header = $("#sp-header");
            if ($header.hasClass("header-sticky")) {
                $header.removeClass("header-sticky");
            }
            $(window).off("scroll");
        }
    };

    /**
     * Calculate the header offset based on the
     * backend preview iframe site and real site.
     *
     * @return  integer The offset value
     */
    function getHeaderOffset() {
        /**
         * Real site header offset top
         *
         */
        let $header = $("#sp-header");
        let stickyHeaderTop = $header.offset().top;

        /**
         * Backend edit preview iframe header offset top
         *
         */
        let $backHeader = $("body.back-panel").find("#sp-header");
        let backPanelStickyHeaderTop = null;

        /**
         * If the class .back-panel exists,
         * that means this is from backend preview frame,
         * then get the offset top, as it varies with the original one.
         *
         */
        if ($backHeader.length > 0) {
            backPanelStickyHeaderTop = $backHeader.offset().top;
        }

        // By Default the header offset is the original header top
        let headerOffset = stickyHeaderTop;

        /**
         * If back panel sticky header top has value rather than null
         * that means the device type is changes from desktop to table or mobile or vice-versa.
         * If value found then subtract the settings topbar height from the offset top.
         *
         */
        if (backPanelStickyHeaderTop !== null) {
            headerOffset = backPanelStickyHeaderTop - settings.topbarHeight;
            headerOffset = headerOffset < 0 ? stickyHeaderTop : headerOffset;
        }

        return headerOffset;
    }

    const headerExist = $("#sp-header");
    if (headerExist.length > 0) {
        handleStickiness("sticky-header", getHeaderOffset());
    }

    // go to top
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $(".sp-scroll-up").fadeIn();
        } else {
            $(".sp-scroll-up").fadeOut(400);
        }
    });

    $(".sp-scroll-up").click(function () {
        $("html, body").animate(
            {
                scrollTop: -60,
            },
            600,
        );
        return false;
    });

    //mega menu
    $(".sp-megamenu-wrapper")
        .parent()
        .parent()
        .css("position", "static")
        .parent()
        .css("position", "relative");
    $(".sp-menu-full").each(function () {
        $(this).parent().addClass("menu-justify");
    });

    $(
        "#offcanvas-toggler, .offcanvas-toggler-secondary, .offcanvas-toggler-full",
    ).on("click", function (event) {
        event.preventDefault();
        openOffcanvas();
    });

    // Close handlers
    $(".close-offcanvas, .offcanvas-overlay").on("click", function (event) {
        event.preventDefault();
        closeOffcanvas();
    });

    // Open function
    function openOffcanvas() {
        $(".offcanvas-init").addClass("offcanvas-active full-offcanvas");
        $(document.body).css("overflow", "hidden");

        // Make offcanvas interactive
        $(".offcanvas-menu").removeAttr("inert").attr("tabindex", "0");

        // Set focus to close button (better for accessibility)
        setTimeout(() => {
            $(".close-offcanvas").focus();
        }, 100);

        // Add keyboard trap
        $(document).on("keydown.offcanvas", handleOffcanvasKeyboard);
    }

    // Close function
    function closeOffcanvas() {
        $(".offcanvas-init").removeClass("offcanvas-active full-offcanvas");
        $(document.body).css("overflow", "");

        // Make offcanvas non-interactive
        $(".offcanvas-menu").attr("inert", "").attr("tabindex", "-1");

        // Return focus to the trigger
        $("#offcanvas-toggler").focus();

        // Remove keyboard trap
        $(document).off("keydown.offcanvas");
    }

    // Keyboard trap handler on offcanvas
    function handleOffcanvasKeyboard(e) {
        if (!$(".offcanvas-init").hasClass("offcanvas-active")) return;

        const $offcanvas = $(".offcanvas-menu");
        const focusable =
            'a[href], button, input, textarea, select, [tabindex="0"]';
        const $focusable = $offcanvas.find(focusable).filter(":visible");

        // Escape key closes
        if (e.key === "Escape") {
            e.preventDefault();
            closeOffcanvas();
            return;
        }

        // Tab key containment
        if (e.key === "Tab") {
            const $first = $focusable.first();
            const $last = $focusable.last();

            if (!$offcanvas[0].contains(document.activeElement)) {
                e.preventDefault();
                $first.focus();
                return;
            }

            if (e.shiftKey && document.activeElement === $first[0]) {
                e.preventDefault();
                $last.focus();
            } else if (!e.shiftKey && document.activeElement === $last[0]) {
                e.preventDefault();
                $first.focus();
            }
        }
    }

    // Prevent focus on inert elements
    $(document).on("focusin", function (e) {
        if ($(e.target).closest("[inert]").length) {
            e.preventDefault();
            $("#offcanvas-toggler").focus();
        }
    });

    // Load inert polyfill if needed
    if (!("inert" in document.createElement("div"))) {
        const inertPolyfill = document.createElement("script");
        inertPolyfill.src =
            "https://cdn.jsdelivr.net/npm/inert-polyfill@3.1.1/inert.min.js";
        document.head.appendChild(inertPolyfill);
    }

    // Modal Menu
    if ($("#modal-menu").length > 0) {
        let $modalToggler = $("#modal-menu-toggler");
        let $modalMenu = $("#modal-menu");
        let $body = $("body");

        $modalToggler.on("click", function (e) {
            e.preventDefault();
            $modalMenu.toggleClass("active");
            $body.toggleClass("modal-menu-active");
            $(this).toggleClass("active");
        });

        // modal menu close with escape
        $(document).keyup(function (e) {
            if (e.key == "Escape") {
                $modalMenu.removeClass("active");
                $modalToggler.removeClass("active");
                $body.removeClass("modal-menu-active");
            }
        });
    }

    // Tooltip
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"], .hasTooltip'),
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            html: true,
        });
    });

    // Popover
    const popoverTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="popover"], .hasPopover'),
    );
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Article Ajax voting
    $(".article-ratings .rating-star").on("click", function (event) {
        event.preventDefault();
        var $parent = $(this).closest(".article-ratings");

        var request = {
            option: "com_ajax",
            template: template,
            action: "rating",
            rating: $(this).data("number"),
            article_id: $parent.data("id"),
            format: "json",
        };

        $.ajax({
            type: "POST",
            data: request,
            beforeSend: function () {
                $parent.find(".fa-spinner").show();
            },
            success: function (response) {
                var data = $.parseJSON(response);
                $parent.find(".ratings-count").text(data.message);
                $parent.find(".fa-spinner").hide();

                if (data.status) {
                    $parent.find(".rating-symbol").html(data.ratings);
                }

                setTimeout(function () {
                    $parent
                        .find(".ratings-count")
                        .text("(" + data.rating_count + ")");
                }, 3000);
            },
        });
    });

    //  Cookie consent
    $(".sp-cookie-allow").on("click", function (event) {
        event.preventDefault();

        var date = new Date();
        date.setTime(date.getTime() + 30 * 24 * 60 * 60 * 1000);
        var expires = "; expires=" + date.toGMTString();
        document.cookie = "spcookie_status=ok" + expires + "; path=/";

        $(this).closest(".sp-cookie-consent").fadeOut();
    });

    $(".btn-group label:not(.active)").click(function () {
        var label = $(this);
        var input = $("#" + label.attr("for"));

        if (!input.prop("checked")) {
            label
                .closest(".btn-group")
                .find("label")
                .removeClass("active btn-success btn-danger btn-primary");
            if (input.val() === "") {
                label.addClass("active btn-primary");
            } else if (input.val() == 0) {
                label.addClass("active btn-danger");
            } else {
                label.addClass("active btn-success");
            }
            input.prop("checked", true);
            input.trigger("change");
        }
        var parent = $(this).parents("#attrib-helix_ultimate_blog_options");
        if (parent) {
            showCategoryItems(parent, input.val());
        }
    });
    $(".btn-group input[checked=checked]").each(function () {
        if ($(this).val() == "") {
            $("label[for=" + $(this).attr("id") + "]").addClass(
                "active btn btn-primary",
            );
        } else if ($(this).val() == 0) {
            $("label[for=" + $(this).attr("id") + "]").addClass(
                "active btn btn-danger",
            );
        } else {
            $("label[for=" + $(this).attr("id") + "]").addClass(
                "active btn btn-success",
            );
        }
        var parent = $(this).parents("#attrib-helix_ultimate_blog_options");
        if (parent) {
            parent.find("*[data-showon]").each(function () {
                $(this).hide();
            });
        }
    });

    function showCategoryItems(parent, value) {
        var controlGroup = parent.find("*[data-showon]");

        controlGroup.each(function () {
            var data = $(this).attr("data-showon");
            data = typeof data !== "undefined" ? JSON.parse(data) : [];
            if (data.length > 0) {
                if (
                    typeof data[0].values !== "undefined" &&
                    data[0].values.includes(value)
                ) {
                    $(this).slideDown();
                } else {
                    $(this).hide();
                }
            }
        });
    }

    $(window).on("scroll", function () {
        var scrollBar = $(".sp-reading-progress-bar");
        if (scrollBar.length > 0) {
            var s = $(window).scrollTop(),
                d = $(document).height(),
                c = $(window).height();
            var scrollPercent = (s / (d - c)) * 100;
            const position = scrollBar.data("position");
            if (position === "top") {
                // var sticky = $('.header-sticky');
                // if( sticky.length > 0 ){
                //     sticky.css({ top: scrollBar.height() })
                // }else{
                //     sticky.css({ top: 0 })
                // }
            }
            scrollBar.css({ width: `${scrollPercent}%` });
        }
    });

    // Error Alert close issue fix for Joomla 3
    var observer = new MutationObserver(function (mutations) {
        $("#system-message-container .alert .close").attr(
            "data-bs-dismiss",
            "alert",
        );
    });
    var target = document.querySelector("#system-message-container");
    observer.observe(target, {
        attributes: true,
    });
});

// Handle accessibility on off-canvas dropdown menus
jQuery(function ($) {
    const menuSelector = ".menu-deeper.menu-parent";
    const togglerSelector = ".menu-toggler";
    const childSelector = ".menu-child";

    // Toggle submenu open/close
    function toggleSubmenu($item, open = null) {
        const $submenu = $item.children(childSelector);
        const isOpen = $item.hasClass("menu-parent-open");

        if (open === null) open = !isOpen;

        if (open) {
            $item.addClass("menu-parent-open").attr("aria-expanded", "true");
            $submenu.slideDown(150);
        } else {
            $item
                .removeClass("menu-parent-open")
                .attr("aria-expanded", "false");
            $submenu.slideUp(150);
        }
    }

    // Prevent event bubbling from toggler to link
    $(document).on("click", togglerSelector, function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $item = $(this).closest(menuSelector);
        const isOpen = $item.hasClass("menu-parent-open");
        toggleSubmenu($item, !isOpen);
    });

    // Handle menu link click or Enter/Space key
    $(document).on("click keydown", `${menuSelector} > a`, function (event) {
        const isToggleKey =
            event.type === "click" ||
            event.key === "Enter" ||
            event.key === " ";
        const isKeyboard = event.key === "Enter" || event.key === " ";

        if (isToggleKey) {
            const $item = $(this).closest(menuSelector);
            const $submenu = $item.children(childSelector);

            // If submenu exists
            if ($submenu.length) {
                // If it's Space key (prevent scrolling), toggle submenu only
                if (event.key === " ") {
                    event.preventDefault();
                    toggleSubmenu($item);
                }
                // If it's Enter or click — follow link (do not preventDefault)
                // but also toggle submenu if desired
                else if (event.key === "Enter") {
                    toggleSubmenu($item, true);
                } else if (event.type === "click") {
                    toggleSubmenu($item, true);
                }
            }
        }
    });

    // Handle arrow key navigation
    $(document).on("keydown", `${menuSelector} > a`, function (event) {
        const $currentItem = $(this).closest("li");
        const $siblings = $currentItem.parent().children("li:visible");
        const index = $siblings.index($currentItem);

        if (event.key === "ArrowDown") {
            event.preventDefault();
            // If submenu is open, focus first child link
            const $submenu = $currentItem.children(childSelector);
            if ($submenu.length && $currentItem.hasClass("menu-parent-open")) {
                const $firstChildLink = $submenu
                    .children("li:visible")
                    .find("a")
                    .first();
                if ($firstChildLink.length) {
                    $firstChildLink.focus();
                    return;
                }
            }
            // Otherwise, move to next sibling
            $siblings
                .eq((index + 1) % $siblings.length)
                .find("a")
                .first()
                .focus();
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            // Move to previous sibling
            $siblings
                .eq((index - 1 + $siblings.length) % $siblings.length)
                .find("a")
                .first()
                .focus();
        } else if (event.key === "ArrowRight") {
            // Open nested submenu if present
            const $submenu = $currentItem.children(childSelector);
            if ($submenu.length) {
                event.preventDefault();
                toggleSubmenu($currentItem, true);
                const $firstChildLink = $submenu
                    .children("li:visible")
                    .find("a")
                    .first();
                if ($firstChildLink.length) {
                    $firstChildLink.focus();
                }
            }
        } else if (event.key === "ArrowLeft" || event.key === "Escape") {
            event.preventDefault();
            // Close only the current open dropdown
            toggleSubmenu($currentItem, false);
            $currentItem.children("a").first().focus();
        }
    });

    // Handle arrow key navigation inside submenu
    $(document).on("keydown", `${childSelector} > li > a`, function (event) {
        const $currentItem = $(this).closest("li");
        const $siblings = $currentItem.parent().children("li:visible");
        const index = $siblings.index($currentItem);

        if (event.key === "ArrowDown") {
            event.preventDefault();
            $siblings
                .eq((index + 1) % $siblings.length)
                .find("a")
                .first()
                .focus();
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            $siblings
                .eq((index - 1 + $siblings.length) % $siblings.length)
                .find("a")
                .first()
                .focus();
        } else if (event.key === "ArrowRight") {
            // Open nested submenu if present
            const $submenu = $currentItem.children(childSelector);
            if ($submenu.length) {
                event.preventDefault();
                toggleSubmenu($currentItem, true);
                const $firstChildLink = $submenu
                    .children("li:visible")
                    .find("a")
                    .first();
                if ($firstChildLink.length) {
                    $firstChildLink.focus();
                }
            }
        } else if (event.key === "ArrowLeft" || event.key === "Escape") {
            event.preventDefault();
            // Close submenu and focus parent
            const $parentMenu = $currentItem.parents(menuSelector).first();
            toggleSubmenu($parentMenu, false);
            $parentMenu.children("a").first().focus();
        }
    });

    // Close all menus when clicking outside
    $(document).on("click", function (event) {
        if (!$(event.target).closest(".menu-deeper").length) {
            $(menuSelector)
                .removeClass("menu-parent-open")
                .attr("aria-expanded", "false");
            $(childSelector).slideUp(150);
        }
    });

    //mark all menu-parents as aria-haspopup
    $(menuSelector)
        .attr("aria-haspopup", "true")
        .attr("aria-expanded", "false");
});

// Handle accessibility on megamenu and profile dropdowns
jQuery(function ($) {
    const menuSelectors = ".sp-megamenu-parent > li, .sp-profile-wrapper";

    $(menuSelectors).each(function () {
        const $menuItem = $(this);
        const $trigger = $menuItem.children("a, button");
        const $dropdown = $menuItem.children(
            ".sp-dropdown, .sp-profile-dropdown",
        );

        if ($dropdown.length) {
            setupDropdownEvents($menuItem, $trigger, $dropdown);
        }
    });

    bindNestedDropdowns("body");

    function setupDropdownEvents($menuItem, $trigger, $dropdown) {
        // Show on focus or mouseenter
        $trigger.on("focus mouseenter", function () {
            openMenu($menuItem, $dropdown);
        });

        $menuItem.on("mouseenter", function () {
            openMenu($menuItem, $dropdown);
        });

        // Hide on focusout or mouseleave
        $menuItem.on("mouseleave focusout", function () {
            setTimeout(function () {
                if (
                    !$menuItem.find(":focus").length &&
                    !$menuItem.is(":hover")
                ) {
                    closeMenu($menuItem, $dropdown);
                }
            }, 100);
        });

        // Keyboard trigger
        $trigger.on("keydown", function (event) {
            switch (event.key) {
                case " ":
                    event.preventDefault();
                    openMenu($menuItem, $dropdown);
                    break;
                case "ArrowDown":
                    event.preventDefault();
                    openMenu($menuItem, $dropdown);
                    focusFirstItem($dropdown);
                    break;
                case "Escape":
                    event.preventDefault();
                    closeMenu($menuItem, $dropdown);
                    $trigger.focus();
                    break;
            }
        });

        // Prevent click from toggling menu
        $trigger.on("click", function (event) {
            if ($dropdown.length) {
                openMenu($menuItem, $dropdown);
            }
        });
    }

    function bindNestedDropdowns(containerSelector) {
        $(containerSelector)
            .find(" .sp-has-child")
            .each(function () {
                const $subItem = $(this);
                const $trigger = $subItem.children("a, button");
                const $subDropdown = $subItem.children(".sp-dropdown");

                if ($subDropdown.length) {
                    setupDropdownEvents($subItem, $trigger, $subDropdown);
                    bindNestedDropdowns($subDropdown);
                }
            });
    }

    function openMenu($item, $dropdown) {
        $dropdown.show();
        // Only force display for .sp-profile-dropdown
        if ($dropdown.hasClass("sp-profile-dropdown")) {
            $dropdown.attr("style", "display: block !important");
        }
        bindKeyboardNavigation($dropdown);
    }

    function closeMenu($item, $dropdown) {
        $dropdown.hide();
        // Reset style for .sp-profile-dropdown
        if ($dropdown.hasClass("sp-profile-dropdown")) {
            $dropdown.removeAttr("style");
        }
    }

    function focusFirstItem($dropdown) {
        const $focusable = $dropdown.find("a, button").filter(":visible");
        if ($focusable.length) {
            $focusable.first().focus();
        }
    }

    function bindKeyboardNavigation($dropdown) {
        const $items = $dropdown.find("a, button").filter(":visible");

        $items.off("keydown").on("keydown", function (event) {
            const currentIndex = $items.index(this);
            let newIndex = -1;

            if (event.key === "ArrowDown") {
                event.preventDefault();
                newIndex = (currentIndex + 1) % $items.length;
            } else if (event.key === "ArrowUp") {
                event.preventDefault();
                newIndex = (currentIndex - 1 + $items.length) % $items.length;
            } else if (event.key === "Escape") {
                event.preventDefault();

                // Reset style for .sp-profile-dropdown
                if ($dropdown.hasClass("sp-profile-dropdown")) {
                    $dropdown.removeAttr("style");
                }

                const $currentDropdown = $(this).closest(".sp-dropdown");
                const $parentItem = $currentDropdown.parent(
                    " .sp-has-child, .sp-megamenu-parent > li",
                );

                // Check if this is the root-level dropdown
                const isRoot = $parentItem
                    .parent()
                    .is(".sp-megamenu-parent, .sp-megamenu-parent > ul, nav");

                if (isRoot) {
                    // Close all menus
                    $(menuSelectors).each(function () {
                        const $item = $(this);
                        closeMenu($item, $item.children(".sp-dropdown"));
                    });
                } else {
                    // Close only current submenu and focus its trigger
                    const $trigger = $parentItem.children("a, button");
                    closeMenu($parentItem, $currentDropdown);
                    if ($trigger.length) {
                        $trigger.focus();
                    }
                }
                return;
            }

            if (newIndex > -1) {
                $items.eq(newIndex).focus();
            }
        });
    }

    // Close all menus on outside click
    $(document).on("click", function (event) {
        if (!$(event.target).closest(menuSelectors).length) {
            $(menuSelectors).each(function () {
                const $item = $(this);
                closeMenu($item, $item.children(".sp-dropdown"));
            });
        }
    });

    // Home Testimonials Wrapper
    if ($(".real-stories-testimonials").length) {
        $(".real-stories-testimonials .sppb-item").each(function () {
            const $item = $(this);
            if ($item.children(".sppb-item-wrap").length) return;
            $item.wrapInner('<div class="sppb-item-wrap"></div>');
        });
    }

    //Custom wrapper

    if (!$(".real-stories-testimonials").length) return;

    $(".real-stories-testimonials .sppb-item").each(function () {
        const $item = $(this);

        if ($item.children(".sppb-item-wrap").length) return;

        $item.wrapInner('<div class="sppb-item-wrap"></div>');
    });

    //Home Vertical Scroll

    const config = {
        container: ".home-business-scroll",
        item: ".sppb-addon-wrapper.addon-root-heading",
        visibleCount: 5,
        opacities: [1, 1, 1, 0.5, 0.2],
        interval: 2400,
        speed: 400,
        easing: "linear",
        pauseOnHover: true,
    };

    const $container = $(config.container);
    const $items = $container.find(config.item);
    if (!$container.length || $items.length < 2) return;

    if (!$container.find(".business-scroll-track").length) {
        $container.wrapInner('<div class="business-scroll-track"></div>');
    }

    const $track = $container.find(".business-scroll-track");
    const itemHeight = $items.first().outerHeight(true);

    $track.find(config.item).last().prependTo($track);
    $track.css("top", -itemHeight);

    function applyOpacity() {
        const $list = $track.find(config.item);
        $list.css("opacity", config.opacities.at(-1) || 0.2);

        for (let i = 0; i < config.visibleCount; i++) {
            $list.eq(i).css("opacity", config.opacities[i] ?? 0.2);
        }
    }

    let isRunning = true;
    let timeoutId = null;

    function loop() {
        if (!isRunning) return;

        $track.animate({ top: -itemHeight * 2 }, config.speed, function () {
            $track.find(config.item).first().appendTo($track);
            $track.css("top", -itemHeight);
            applyOpacity();

            timeoutId = setTimeout(loop, config.interval);
        });
    }

    applyOpacity();
    timeoutId = setTimeout(loop, config.interval);

    if (config.pauseOnHover) {
        $container.on("mouseenter", function () {
            isRunning = false;
            clearTimeout(timeoutId);
            $track.stop(true, true);
        });

        $container.on("mouseleave", function () {
            if (!isRunning) {
                isRunning = true;
                timeoutId = setTimeout(loop, config.interval);
            }
        });
    }
    // Animated Scroller
    function createAnimatedScroller(selector, gap) {
        const scroller = document.querySelector(selector);
        if (scroller) {
            let currentLeftValue = 0;
            let interval = null;

            // Style the wrapper and scroller
            scroller.style.display = "flex";
            scroller.style.whiteSpace = "nowrap";
            scroller.style.position = "relative";

            // Duplicate content for seamless scrolling
            const content = scroller.innerHTML;
            scroller.innerHTML = content + content;

            function animationStart() {
                interval = setInterval(animationLoop, gap);
            }

            function animationStop() {
                clearInterval(interval);
            }

            function resetPosition() {
                const firstChild = scroller.firstElementChild;
                if (firstChild) {
                    currentLeftValue = 0;
                    scroller.style.transform = `translateX(0)`;
                }
            }

            function animationLoop() {
                const firstChild = scroller.firstElementChild;
                if (!firstChild) return;

                currentLeftValue -= 1;

                // Reset position when first item is fully scrolled
                if (Math.abs(currentLeftValue) >= firstChild.offsetWidth + 20) {
                    scroller.appendChild(firstChild);
                    currentLeftValue = 0;
                }

                scroller.style.transform = `translateX(${currentLeftValue}px)`;
            }

            // Event listeners
            scroller.addEventListener("mouseover", animationStop);
            scroller.addEventListener("mouseout", animationStart);
            window.addEventListener("resize", resetPosition);

            // Start animation
            animationStart();
        }
    }

    createAnimatedScroller(".services-collection-scroller", 20);
});

$(function () {
    // Infinity Text scroller
    const container = document.querySelector(".scroller-widget");
    if (!container) return; // stop if container not found
    const items = Array.from(container.children).filter(
        (el) => el.tagName.toLowerCase() === "p",
    );
    const speed = 2.2;
    const spacing = 50;
    let positions = [];
    let lastX = container.offsetWidth;

    items.forEach((item) => {
        positions.push(lastX);
        lastX += item.offsetWidth + spacing;
    });
    function animate() {
        items.forEach((item, index) => {
            positions[index] -= speed; // move left

            if (positions[index] + item.offsetWidth < 0) {
                let farthest = -Infinity;
                items.forEach((i, idx) => {
                    if (idx !== index) {
                        farthest = Math.max(
                            farthest,
                            positions[idx] + i.offsetWidth,
                        );
                    }
                });
                positions[index] = farthest + spacing;
            }
            item.style.transform = `translateX(${positions[index]}px) translateY(-50%)`;
        });
        requestAnimationFrame(animate);
    }
    // Start animation
    animate();
});

$(function () {
    // Right To Left Infinity Scroller
    function startSrollerRTL(itemSelector1, speed2 = 1, spacing2 = 30) {
        const logoWrap2 = document.querySelector(itemSelector1);
        if (!logoWrap2) return;
        const items2 = Array.from(logoWrap2.children).filter(
            (el) => el.tagName.toLowerCase() === "div",
        );
        let positions2 = [];
        let startX2 = logoWrap2.offsetWidth;
        items2.forEach((item) => {
            positions2.push(startX2);
            startX2 += item.offsetWidth + spacing2;
        });
        function animate2() {
            items2.forEach((item, index) => {
                positions2[index] -= speed2;
                if (positions2[index] + item.offsetWidth < 0) {
                    let rightMost = Math.max(
                        ...items2.map(
                            (el, i) => positions2[i] + el.offsetWidth,
                        ),
                    );
                    positions2[index] = rightMost + spacing2;
                }
                item.style.transform = `translateX(${positions2[index]}px) translateY(-50%)`;
            });
            requestAnimationFrame(animate2);
        }
        animate2();
    }
    // Use anywhere
    startSrollerRTL(".rtl-infinity-scroller1");
    startSrollerRTL(".rtl-infinity-scroller2");

    // Left To Right Infinity Scroller
    function startSliderLTR(itemSelector2, speed = 1, spacing = 30) {
        const container = document.querySelector(itemSelector2);
        if (!container) return;
        const items = Array.from(container.children).filter(
            (el) => el.tagName.toLowerCase() === "div",
        );
        let positions = [];
        let startX = 0;

        items.forEach((item) => {
            positions.push(startX);
            startX -= item.offsetWidth + spacing;
        });
        function animate3() {
            items.forEach((item, i) => {
                positions[i] += speed;
                if (positions[i] > container.offsetWidth) {
                    let leftMost = Math.min(...positions);
                    positions[i] = leftMost - item.offsetWidth - spacing;
                }
                item.style.transform = `translateX(${positions[i]}px) translateY(-50%)`;
            });
            requestAnimationFrame(animate3);
        }
        animate3();
    }

    startSliderLTR(".ltr-infinity-scroller1");
    startSliderLTR(".ltr-infinity-scroller3");

    // Testimonial Add Class
    $(".testimonial-style .sppb-carousel-extended-item").each(function () {
        const $item = $(this);

        const $img = $item.find(".sppb-testimonial-carousel-img-wrap").detach();
        const $content1 = $item
            .find(".sppb-testimonial-carousel-item-content")
            .detach();
        const $content2 = $item
            .find(".sppb-testimonial-carousel-content-wrap")
            .detach();

        const $innerContent = $(
            '<div class="sppb-carousel-extended-item-inner"></div>',
        ).append($content1, $content2);
        const $outerInner = $(
            '<div class="sppb-carousel-extended-item-wrap"></div>',
        ).append($img, $innerContent);

        $item.empty().append($outerInner);
    });
});
