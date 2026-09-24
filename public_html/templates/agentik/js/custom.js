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

		initStickyHeader();
		initAdaptiveHeader();
		initDragScroll();
		initFanStats();
		initMarquee();
		initTeamReveal();
		initJournalReveal();
	});

	/**
	 * Блог: статьи проявляются по мере прокрутки.
	 *
	 * Наблюдаем за каждой статьёй отдельно, а не за всем списком: страница
	 * длинная, и разворот внизу должен оживать, когда до него дошли, а не
	 * заранее. Подъём и проявление описаны в custom.css, здесь только момент
	 * и небольшая задержка по порядку внутри ряда.
	 */
	function initJournalReveal() {
		var articles = document.querySelectorAll(
			".kdc-journal .sppb-addon-article, .kdc-jh-feed .sppb-addon-article"
		);

		if (!articles.length) {
			return;
		}

		if (!("IntersectionObserver" in window)) {
			Array.prototype.forEach.call(articles, function (article) {
				article.classList.add("kdc-in");
			});

			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) {
						return;
					}

					var index = Number(entry.target.dataset.kdcIndex || 0);

					entry.target.style.transitionDelay = index * 0.08 + "s";
					entry.target.classList.add("kdc-in");
					observer.unobserve(entry.target);
				});
			},
			{ threshold: 0.15 }
		);

		Array.prototype.forEach.call(articles, function (article, i) {
			// Задержка считается внутри ряда, иначе у нижних статей она
			// накопилась бы до неприличной паузы.
			article.dataset.kdcIndex = i % 2;
			observer.observe(article);
		});
	}

	/**
	 * Команда: карточки проявляются при прокрутке.
	 *
	 * Всё движение описано в custom.css (подъём + проявление, задержки по
	 * колонкам), здесь только момент запуска: когда ряд показался на экране,
	 * вешаем класс. Один раз - обратно карточки не прячутся.
	 */
	function initTeamReveal() {
		var row = document.querySelector(".kdc-team");

		if (!row) {
			return;
		}

		if (!("IntersectionObserver" in window)) {
			row.classList.add("kdc-team-in");
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add("kdc-team-in");
						observer.unobserve(entry.target);
					}
				});
			},
			{ threshold: 0.2 }
		);

		observer.observe(row);
	}

	/**
	 * Белый фон шапки при прокрутке.
	 *
	 * Тема считает порог один раз при загрузке: getHeaderOffset() в main.js
	 * берёт $('#sp-header').offset().top, а шапка у Agentik всегда
	 * position: fixed - для такой шапки offset().top равен текущей прокрутке.
	 * Если страницу перезагрузить не с самого верха (браузер восстанавливает
	 * позицию прокрутки до того, как отработает скрипт), порогом становится
	 * та самая позиция: фон тогда появляется не после hero, а где-нибудь у
	 * блока «Команда» - или не появляется вовсе.
	 *
	 * Считаем сами и только от прокрутки. Обработчик вешается после
	 * шаблонного, поэтому на каждом событии последнее слово за ним.
	 */
	function initStickyHeader() {
		var header = document.getElementById("sp-header");

		if (!header || !document.body.classList.contains("sticky-header")) {
			return;
		}

		var options = (window.Joomla && Joomla.getOptions("data")) || {};
		var offset = 100;

		if (options.header && options.header.stickyOffset) {
			offset = Number(options.header.stickyOffset) || 100;
		}

		function apply() {
			var scrolled = window.pageYOffset || document.documentElement.scrollTop || 0;

			header.classList.toggle("header-sticky", scrolled >= offset);
		}

		/*
		 * Состояние выставляем в кадре, а не прямо в обработчике прокрутки.
		 * Шаблонный обработчик на каждом событии снимает класс по своему
		 * сбитому порогу, и кто из нас окажется вторым - зависит от порядка
		 * подписки. requestAnimationFrame снимает вопрос: наш вызов идёт
		 * после всех обработчиков события и перед отрисовкой кадра, то есть
		 * последнее слово всегда за ним.
		 */
		var queued = false;

		function schedule() {
			if (queued) {
				return;
			}

			queued = true;
			window.requestAnimationFrame(function () {
				queued = false;
				apply();
			});
		}

		window.addEventListener("scroll", schedule, { passive: true });
		window.addEventListener("load", schedule);
		schedule();
	}

	/**
	 * Портфолио: две бегущие ленты фотографий.
	 *
	 * Движение отдано css-анимации, а не покадровому скрипту, и это главное.
	 * Раньше ленту двигал main.js шаблона: каждый кадр он читал offsetWidth у
	 * всех плиток (браузер из-за этого пересчитывал раскладку прямо посреди
	 * кадра) и переписывал transform каждой из них. Пока страницу крутят,
	 * главный поток занят - кадры приходят неровно, а шаг был задан в
	 * пикселях НА КАДР, поэтому лента то ускорялась, то дёргалась, и на
	 * 120-герцовом экране ехала вдвое быстрее.
	 *
	 * Теперь скрипт только один раз собирает «пояс» и отдаёт его css:
	 * анимация transform идёт в композиторе, независимо от главного потока,
	 * поэтому прокрутка на неё не влияет вообще. Скорость задана в пикселях
	 * в секунду и одинакова на любой частоте экрана.
	 *
	 * Приём со сдвигом на -50%: пояс склеен из двух одинаковых половин, и
	 * когда первая уезжает ровно на свою длину, картинка совпадает сама с
	 * собой - шва не видно, склейки в коде не нужно.
	 */
	function initMarquee() {
		var SPEED = 60; // пикселей в секунду
		var GAP = 30;

		var lanes = document.querySelectorAll(
			".rtl-infinity-scroller-kdc, .ltr-infinity-scroller-kdc"
		);

		if (!lanes.length) {
			return;
		}

		/*
		 * Ленты растянуты на всю ширину окна отрицательными полями, и в
		 * расчёте участвует vw - а в vw входит вертикальная полоса прокрутки.
		 * Без поправки блок вылезал бы за экран на её ширину и добавлял
		 * горизонтальный скролл. Отдаём ширину полосы в CSS переменной.
		 */
		function scrollbarWidth() {
			var w = window.innerWidth - document.documentElement.clientWidth;

			document.documentElement.style.setProperty(
				"--kdc-sbw",
				(w > 0 ? w : 0) + "px"
			);
		}

		scrollbarWidth();

		var still =
			window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

		var belts = [];

		Array.prototype.forEach.call(lanes, function (node) {
			var items = Array.prototype.filter.call(node.children, function (el) {
				return el.tagName.toLowerCase() === "div";
			});

			if (!items.length) {
				return;
			}

			/*
			 * Ленивая загрузка тут только мешает: плитка почти всё время за
			 * краем экрана, и картинка не успевает подгрузиться к моменту,
			 * когда её привозит анимация.
			 */
			Array.prototype.forEach.call(node.querySelectorAll("img"), function (img) {
				var src = img.getAttribute("data-src");

				if (src && !img.getAttribute("src")) {
					img.setAttribute("src", src);
				}
			});

			var belt = document.createElement("div");

			belt.className = "kdc-belt";
			node.appendChild(belt);
			items.forEach(function (item) {
				belt.appendChild(item);
			});

			belts.push({
				node: node,
				belt: belt,
				items: items,
				rtl: node.classList.contains("rtl-infinity-scroller-kdc"),
				running: false,
			});
		});

		if (!belts.length) {
			return;
		}

		function copyOf(item) {
			var copy = item.cloneNode(true);
			var originals = item.querySelectorAll("img");

			// У клона своя ленивая загрузка не сработает - адрес ставим сразу.
			Array.prototype.forEach.call(copy.querySelectorAll("img"), function (img, k) {
				var origin = originals[k];
				var src =
					origin &&
					(origin.getAttribute("src") ||
						origin.getAttribute("data-src") ||
						origin.currentSrc);

				if (src) {
					img.setAttribute("src", src);
					img.removeAttribute("data-src");
					img.className = img.className.replace(/\blazyload\w*\b/g, "");
				}
			});

			copy.setAttribute("aria-hidden", "true");

			return copy;
		}

		function build(lane) {
			// Пересборка: оставляем только исходные плитки.
			while (lane.belt.children.length > lane.items.length) {
				lane.belt.removeChild(lane.belt.lastChild);
			}

			var one = 0;

			lane.items.forEach(function (item) {
				one += item.offsetWidth + GAP;
			});

			if (!one) {
				return;
			}

			/*
			 * Половина пояса должна перекрывать ленту, иначе в кадре появится
			 * пустота. Повторяем исходный набор нужное число раз, а потом
			 * дублируем всю половину - её длина и есть шаг анимации.
			 */
			var repeats = Math.max(1, Math.ceil(lane.node.clientWidth / one));
			var half = one * repeats;
			var i;
			var k;

			for (k = 1; k < repeats; k++) {
				for (i = 0; i < lane.items.length; i++) {
					lane.belt.appendChild(copyOf(lane.items[i]));
				}
			}

			var firstHalf = Array.prototype.slice.call(lane.belt.children);

			for (i = 0; i < firstHalf.length; i++) {
				lane.belt.appendChild(copyOf(firstHalf[i]));
			}

			lane.half = half;
			lane.belt.style.animationDuration = half / SPEED + "s";
			lane.belt.classList.add(lane.rtl ? "kdc-belt-rtl" : "kdc-belt-ltr");
		}

		belts.forEach(build);

		if (still) {
			belts.forEach(function (lane) {
				lane.belt.style.animation = "none";
			});

			return;
		}

		/*
		 * Пока блок за экраном, анимацию ставим на паузу: композитору нечего
		 * считать, а после возврата лента продолжает с того же места.
		 */
		if ("IntersectionObserver" in window) {
			var observer = new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						belts.forEach(function (lane) {
							if (lane.node === entry.target) {
								lane.belt.classList.toggle("kdc-paused", !entry.isIntersecting);
							}
						});
					});
				},
				{ rootMargin: "100px 0px" }
			);

			belts.forEach(function (lane) {
				observer.observe(lane.node);
			});
		}

		/*
		 * Ширины плиток зависят от брейкпоинта - после ресайза пояс надо
		 * пересобрать и заново посчитать длительность. С задержкой, чтобы не
		 * делать это на каждый пиксель перетаскивания окна.
		 */
		var resizeTimer = null;
		var lastWidth = window.innerWidth;

		window.addEventListener("resize", function () {
			scrollbarWidth();

			if (window.innerWidth === lastWidth) {
				return;
			}

			lastWidth = window.innerWidth;
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(function () {
				belts.forEach(build);
			}, 250);
		});
	}

	/**
	 * Блок статистики: веер карточек раскрывается, когда блок появляется на
	 * экране.
	 *
	 * Вся геометрия - в custom.css: у каждой колонки свои --fan-x/--fan-y/
	 * --fan-r, а до раскрытия они применяются с коэффициентом 0.12, то есть
	 * карточки лежат почти стопкой в центре. Здесь остаётся только повесить
	 * класс - дальше работает css-переход.
	 *
	 * IntersectionObserver, а не отслеживание скролла: браузер сам считает
	 * пересечение, без обработчика на каждый кадр прокрутки. Порог 0.25 -
	 * веер трогается, когда видна четверть блока, а не по касанию края.
	 */
	function initFanStats() {
		var section = document.querySelector(".fan-fact-section");

		if (!section) {
			return;
		}

		if (!("IntersectionObserver" in window)) {
			// Старый браузер - показываем раскрытым, без анимации.
			section.classList.add("fan-open");
			return;
		}

		function open() {
			section.classList.add("fan-open");
			observer.disconnect();
			window.removeEventListener("scroll", check);
			window.removeEventListener("load", check);
		}

		/*
		 * Подстраховка к наблюдателю: если блок уже виден (открыли страницу с
		 * якорем, вернулись «назад» на позицию, дорисовались ленивые картинки
		 * и блок выехал на экран) - считаем пересечение сами. Наблюдатель в
		 * таких случаях иногда не присылает первую запись.
		 */
		function check() {
			var box = section.getBoundingClientRect();
			var visible = Math.min(box.bottom, window.innerHeight) - Math.max(box.top, 0);

			if (box.height && visible / box.height >= 0.25) {
				open();
			}
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						// Раскрытие одноразовое: обратно карточки не
						// складываются, иначе блок мигал бы при каждой
						// прокрутке мимо.
						open();
					}
				});
			},
			{ threshold: 0.25 }
		);

		observer.observe(section);
		window.addEventListener("scroll", check, { passive: true });
		window.addEventListener("load", check);
		check();
	}

	/**
	 * Лента коллекций: прокрутка мышью - зажал и повёл вбок.
	 *
	 * Сама лента (.services-home-variation-2) - обычный горизонтальный
	 * скроллер (overflow-x: auto). На тачскрине и трекпаде он листается сам,
	 * а вот мышью - нечем: колесо крутит страницу, полосу прокрутки тема
	 * прячет (scrollbar-width: none). Поэтому добавляем перетаскивание.
	 *
	 * Только для мыши: pointerType 'touch' и 'pen' пропускаем, чтобы не
	 * перебивать родную инерционную прокрутку пальцем - она уже работает и
	 * работает лучше, чем любая эмуляция.
	 */
	function initDragScroll() {
		var lanes = document.querySelectorAll(".services-home-variation-2");

		// Порог в пикселях, после которого движение считается протягиванием,
		// а не щелчком: иначе каждое перетаскивание заканчивалось бы открытием
		// той карточки, с которой начали тянуть.
		var DRAG_THRESHOLD = 5;

		Array.prototype.forEach.call(lanes, function (lane) {
			var startX = 0;
			var startScroll = 0;
			var dragging = false;
			var moved = false;

			lane.addEventListener("pointerdown", function (event) {
				if (event.pointerType !== "mouse" || event.button !== 0) {
					return;
				}

				dragging = true;
				moved = false;
				startX = event.clientX;
				startScroll = lane.scrollLeft;

				// У темы на ленте scroll-behavior: smooth - при ручном
				// перетаскивании каждая установка scrollLeft анимировалась бы
				// и картинка тянулась за курсором с задержкой.
				lane.style.scrollBehavior = "auto";
				lane.style.cursor = "grabbing";
				lane.style.userSelect = "none";
			});

			lane.addEventListener("pointermove", function (event) {
				if (!dragging) {
					return;
				}

				var dx = event.clientX - startX;

				if (!moved && Math.abs(dx) > DRAG_THRESHOLD) {
					moved = true;
					// Перехватываем указатель только когда стало ясно, что это
					// протягивание: иначе обычный щелчок по карточке терял бы
					// цель события.
					if (lane.setPointerCapture) {
						lane.setPointerCapture(event.pointerId);
					}
				}

				if (moved) {
					lane.scrollLeft = startScroll - dx;
					event.preventDefault();
				}
			});

			function endDrag(event) {
				if (!dragging) {
					return;
				}

				// Досчитываем по координате отпускания: последнее pointermove
				// может не дойти до конечной точки (браузер объединяет события
				// при быстром движении), и лента останавливалась бы чуть
				// раньше, чем курсор.
				if (moved && event && typeof event.clientX === "number") {
					lane.scrollLeft = startScroll - (event.clientX - startX);
				}

				dragging = false;
				lane.style.scrollBehavior = "";
				lane.style.cursor = "";
				lane.style.userSelect = "";

				if (event && event.pointerId && lane.releasePointerCapture) {
					try {
						lane.releasePointerCapture(event.pointerId);
					} catch (e) {
						// Указатель мог не захватываться - это не ошибка.
					}
				}
			}

			lane.addEventListener("pointerup", endDrag);
			lane.addEventListener("pointercancel", endDrag);

			// Намеренно НЕ слушаем pointerleave: при быстром движении курсор
			// успевает выйти за пределы ленты, и протягивание обрывалось на
			// полпути. Указатель захвачен, поэтому pointerup придёт сюда в
			// любом случае; window - страховка на случай, если захват не
			// установился (например, мышь отпустили вне окна).
			window.addEventListener("pointerup", endDrag);

			// Гасим щелчок, которым завершилось протягивание: ссылка карточки
			// ведёт на страницу коллекции, и без этого лента "листалась" бы
			// прямиком в переход по ссылке.
			lane.addEventListener(
				"click",
				function (event) {
					if (moved) {
						event.preventDefault();
						event.stopPropagation();
						moved = false;
					}
				},
				true
			);

			// Родной драг-призрак картинки перебивает перетаскивание ленты.
			Array.prototype.forEach.call(lane.querySelectorAll("img"), function (img) {
				img.setAttribute("draggable", "false");
			});
		});
	}

	/**
	 * Header contrast over the hero slider.
	 *
	 * The header sits transparently on top of the hero, but the logo text and
	 * the menu links are near-black (#171717), so they vanish whenever a dark
	 * photo scrolls in - and the slides genuinely swing both ways here, from
	 * luminance 44 (the dark library shot) to 208 (the bright hallway).
	 *
	 * So: measure how bright each slide actually is *behind the header strip*,
	 * and flip the header to its light treatment while a dark slide is showing.
	 *
	 * Measuring matters more than it sounds. The slides are CSS backgrounds with
	 * background-size:cover, so what you see is a crop whose framing depends on
	 * the viewport's aspect ratio - averaging the whole file would happily call
	 * a photo "light" because of a bright area that is cropped out of view, or
	 * sits far below the header. We reproduce the cover math instead and sample
	 * only the strip the header covers.
	 */
	function initAdaptiveHeader() {
		var $header = $("#sp-header");
		var stage = document.querySelector(".hero-wrap .sp-slider-outer-stage");

		if (!$header.length || !stage) {
			return;
		}

		// The hero headline is a single overlay above the slider (not one per
		// slide), so like the logo it has to follow whichever photo is showing.
		var $title = $(".hero-wrap .sppb-addon-header .sppb-addon-title").first();

		// Relative luminance of the header's default near-black text (#171717),
		// used to weigh "keep it dark" against "flip it white" per slide.
		var DARK_TEXT_LUMINANCE = relativeLuminance(0x17, 0x17, 0x17);

		buildLightLogo();

		var slides = [].slice.call(stage.querySelectorAll(".sp-item"));
		var measured = false;

		whenStageHasSize(function () {
			measureAll().then(function () {
				measured = true;
				apply();
			});
		});

		/**
		 * Wait until the hero actually has a size before measuring.
		 *
		 * The crop maths divides by the stage's width and height, and a page
		 * opened in a background tab lays out lazily - the stage reports 0x0
		 * there, every sample comes back empty, and the header would stay
		 * un-adapted for the whole visit. ResizeObserver lets us start the
		 * moment real dimensions exist.
		 */
		function whenStageHasSize(callback) {
			var box = stage.getBoundingClientRect();

			if (box.width && box.height) {
				callback();
				return;
			}

			if (typeof ResizeObserver !== "function") {
				// Old browser: give layout a moment, then measure regardless.
				setTimeout(callback, 500);
				return;
			}

			var observer = new ResizeObserver(function (entries, self) {
				var rect = stage.getBoundingClientRect();

				if (rect.width && rect.height) {
					self.disconnect();
					callback();
				}
			});

			observer.observe(stage);
		}

		/**
		 * Watch the thumbnail dots, not the slides.
		 *
		 * Timing a real transition shows why: on click the dot's "active" class
		 * moves within ~40ms, while the slides sit in their prev-item/next-item
		 * limbo and the incoming slide only inherits "active" once the 600ms
		 * animation has finished (~730ms). Keying off the slides therefore
		 * recoloured the header *after* the photo had already arrived. The dots
		 * flip at the start, so the header now changes over together with the
		 * picture.
		 *
		 * Dots and slides share an index, so the dot tells us which slide is
		 * coming. The slides stay observed as a fallback for layouts where the
		 * dot controller is switched off.
		 */
		var dots = [].slice.call(stage.parentNode.querySelectorAll(".sp-dots li"));

		var observer = new MutationObserver(function () {
			if (measured) {
				apply();
			}
		});

		dots.forEach(function (dot) {
			observer.observe(dot, { attributes: true, attributeFilter: ["class"] });
		});

		slides.forEach(function (slide) {
			observer.observe(slide, { attributes: true, attributeFilter: ["class"] });
		});

		// A resize re-crops every background, which can change what is behind
		// the header - e.g. rotating a phone.
		var resizeTimer = null;
		$(window).on("resize.adaptiveHeader", function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				measureAll().then(apply);
			}, 250);
		});

		function apply() {
			var incoming = null;

			// Preferred: the dot that just lit up names the slide being shown,
			// and it does so the instant the transition starts.
			for (var i = 0; i < dots.length; i++) {
				if (dots[i].classList.contains("active")) {
					incoming = slides[i];
					break;
				}
			}

			// No dot controller on this slider - fall back to the slide itself.
			if (!incoming) {
				incoming = stage.querySelector(".sp-item.active") || slides[0];
			}

			if (!incoming || incoming.dataset.headerDark === undefined) {
				return;
			}

			$header.toggleClass("header-on-dark", incoming.dataset.headerDark === "1");

			// The headline sits far below the header, over a different part of
			// the photo, so it gets its own verdict. It is white by default and
			// only needs flipping when the picture behind it is light.
			if ($title.length && incoming.dataset.titleDark !== undefined) {
				$title.toggleClass("hero-text-on-light", incoming.dataset.titleDark === "0");
			}
		}

		function measureAll() {
			return Promise.all(slides.map(measureSlide));
		}

		function measureSlide(slide) {
			var bgEl = slide.querySelector(".sp-background");

			if (!bgEl) {
				return Promise.resolve();
			}

			var match = getComputedStyle(bgEl).backgroundImage.match(/url\(["']?(.*?)["']?\)/);

			if (!match || !match[1]) {
				return Promise.resolve();
			}

			return loadImage(match[1]).then(function (img) {
				if (!img) {
					return;
				}

				try {
					var headerLum = sample(img, headerRegion());

					if (headerLum !== null) {
						slide.dataset.headerDark = prefersWhiteText(headerLum) ? "1" : "0";
					}

					var titleRect = titleRegion();
					var titleLum = titleRect ? sample(img, titleRect) : null;

					if (titleLum !== null) {
						slide.dataset.titleDark = prefersWhiteText(titleLum) ? "1" : "0";
					}
				} catch (e) {
					// A tainted canvas (image served cross-origin without CORS)
					// throws on getImageData - leave the defaults rather than guess.
				}
			});
		}

		/**
		 * Pick whichever colour actually reads better on this photo rather than
		 * guessing at a brightness cut-off.
		 *
		 * A fixed threshold gets mid-tones wrong, and this hero is full of them:
		 * the lavender slide averages 132/255, which looks "light" but is
		 * squarely in the middle - there dark text still scores ~4.9:1 while
		 * white manages only ~3.75:1. Comparing the two WCAG contrast ratios
		 * puts the switch exactly where the better-reading option changes over.
		 */
		function prefersWhiteText(lum) {
			var contrastOnDarkText = (lum + 0.05) / (DARK_TEXT_LUMINANCE + 0.05);
			var contrastOnWhiteText = 1.05 / (lum + 0.05);

			return contrastOnWhiteText > contrastOnDarkText;
		}

		/** The strip the fixed header covers, in stage coordinates. */
		function headerRegion() {
			var box = stage.getBoundingClientRect();

			return { x: 0, y: 0, w: box.width, h: $header.outerHeight() || 90 };
		}

		/** The headline's own box, in stage coordinates. */
		function titleRegion() {
			if (!$title.length) {
				return null;
			}

			var box = stage.getBoundingClientRect();
			var rect = $title[0].getBoundingClientRect();

			if (!rect.width || !rect.height) {
				return null;
			}

			return {
				x: rect.left - box.left,
				y: rect.top - box.top,
				w: rect.width,
				h: rect.height,
			};
		}

		function loadImage(url) {
			return new Promise(function (resolve) {
				var img = new Image();

				img.crossOrigin = "anonymous";

				img.onerror = function () {
					resolve(null);
				};

				img.onload = function () {
					resolve(img);
				};

				img.src = url;
			});
		}

		/**
		 * Average relative luminance of one region of a slide, as it is actually
		 * shown on screen.
		 *
		 * `region` is given in stage coordinates (0,0 = top-left of the hero).
		 * Because the slides are CSS backgrounds with background-size:cover, the
		 * visible picture is a crop whose framing depends on the viewport's
		 * aspect ratio - sampling the file directly would read parts that are
		 * cropped away. So the cover maths is reproduced here and the region is
		 * translated into source-image pixels before sampling.
		 */
		function sample(img, region) {
			var box = stage.getBoundingClientRect();

			var natW = img.naturalWidth;
			var natH = img.naturalHeight;

			if (!natW || !natH || !box.width || !box.height || !region) {
				return null;
			}

			// Replicate background-size: cover + background-position: center.
			var scale = Math.max(box.width / natW, box.height / natH);
			var offsetX = (box.width - natW * scale) / 2;
			var offsetY = (box.height - natH * scale) / 2;

			var sx = Math.max(0, (region.x - offsetX) / scale);
			var sy = Math.max(0, (region.y - offsetY) / scale);
			var sw = Math.min(natW - sx, region.w / scale);
			var sh = Math.min(natH - sy, region.h / scale);

			if (sw <= 0 || sh <= 0) {
				return null;
			}

			// Downscale hard: we want the average, not the detail.
			var canvas = document.createElement("canvas");
			canvas.width = 48;
			canvas.height = 12;

			var ctx = canvas.getContext("2d", { willReadFrequently: true });
			ctx.drawImage(img, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

			var data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
			var total = 0;
			var count = 0;

			for (var i = 0; i < data.length; i += 4) {
				total += relativeLuminance(data[i], data[i + 1], data[i + 2]);
				count++;
			}

			return count ? total / count : null;
		}

		/**
		 * WCAG relative luminance (0-1).
		 *
		 * Averaging raw 0-255 channel values would be wrong here: sRGB is
		 * gamma-encoded, so mid-grey sits near 0.21 in light terms, not 0.5.
		 * Contrast ratios are defined on this linearised scale, so the averages
		 * have to live on it too.
		 */
		function relativeLuminance(r, g, b) {
			return 0.2126 * toLinear(r) + 0.7152 * toLinear(g) + 0.0722 * toLinear(b);
		}

		function toLinear(channel) {
			var c = channel / 255;

			return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		}

		/**
		 * The logo is a single PNG: green door mark plus near-black wordmark.
		 * Rather than animating a filter on it - which would wash the logo
		 * through a grey midpoint on the way - we lay a white copy over the
		 * original and cross-fade the two.
		 */
		function buildLightLogo() {
			$(".logo").each(function () {
				var $box = $(this);
				var img = $box.find("img.logo-image")[0];

				if (!img || $box.find(".logo-image-light").length) {
					return;
				}

				var src = img.getAttribute("data-src") || img.currentSrc || img.src;

				if (!src) {
					return;
				}

				var light = document.createElement("img");
				light.className = "logo-image-light";
				light.src = src;
				light.alt = "";
				light.setAttribute("aria-hidden", "true");
				light.setAttribute("draggable", "false");

				$box.addClass("has-light-logo").append(light);
			});
		}
	}

})(jQuery);
