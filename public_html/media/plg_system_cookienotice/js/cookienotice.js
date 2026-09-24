/* plg_system_cookienotice */
(function () {
  function setCookie(name, value, maxAgeSeconds) {
    var cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value)
      + "; max-age=" + String(maxAgeSeconds)
      + "; path=/; samesite=lax";

    if (window.location.protocol === "https:") {
      cookie += "; secure";
    }

    document.cookie = cookie;
  }

  function initBanner(banner) {
    var cookieName = banner.getAttribute("data-cookie-name") || "cn_accepted";
    var maxAge = parseInt(banner.getAttribute("data-max-age") || "15552000", 10);
    var delay = parseInt(banner.getAttribute("data-delay") || "0", 10);

    var show = function () {
      banner.classList.add("show");
    };

    var hide = function () {
      banner.classList.remove("show");

      window.setTimeout(function () {
        banner.remove();
      }, 220);
    };

    var acceptBtn = banner.querySelector(".jt-cookie-accept");
    var closeBtn = banner.querySelector(".jt-cookie-close");

    if (acceptBtn) {
      acceptBtn.addEventListener("click", function () {
        setCookie(cookieName, "1", maxAge);
        hide();
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener("click", function () {
        hide();
      });
    }

    if (delay > 0) {
      window.setTimeout(show, delay);
    } else {
      show();
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    var banner = document.querySelector(".jt-cookie-notice");

    if (!banner) {
      return;
    }

    initBanner(banner);
  });
})();
