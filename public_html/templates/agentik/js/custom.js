/**
 * Site-specific JS overrides for the Agentik template.
 * Loaded automatically by templates/agentik/index.php if this file exists.
 */
/*
 * Ленивая загрузка картинок (lazysizes подключается после этого файла).
 * По умолчанию картинка начинает грузиться за ~500px до экрана: при быстрой
 * прокрутке она не успевает, и фото «проявляются» уже на глазах, а их
 * загрузка и декодирование приходятся ровно на момент прокрутки. Берём
 * запас чуть больше экрана - картинки приходят заранее.
 */
window.lazySizesConfig = window.lazySizesConfig || {};
window.lazySizesConfig.expand = Math.round(Math.max(window.innerHeight || 0, 600) * 1.25);

/*
 * Уменьшенные копии (data-srcset, см. index.php шаблона): lazysizes берёт
 * ширину картинки на странице. Но карточка с object-fit: cover обрезает
 * снимок, а не сжимает: широкое фото в высокой карточке растянуто по высоте,
 * и нужная ширина - высота × пропорции снимка. Без поправки браузер взял
 * бы слишком мелкий файл, и фото стало бы мыльным.
 */
document.addEventListener("lazybeforesizes", function (e) {
	var img = e.target;
	var ratio = Number(img.getAttribute("data-kdc-ratio"));

	if (!ratio || !img.offsetHeight || window.getComputedStyle(img).objectFit !== "cover") {
		return;
	}

	e.detail.width = Math.max(e.detail.width, Math.ceil(img.offsetHeight * ratio));
});

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
		initFeedLowRes();
		initArticlesLoadMore();
		initCarouselAutoplayOnView();
		initCarouselClickSelect();
		initDoorConfigurator();
		initLazyBackgrounds();
		initMobileMenuContacts();
	});

	/**
	 * Меню на телефоне: под кнопкой с телефоном - «Написать в MAX» и
	 * Telegram-канал (ссылки те же, что на сайте: чат MAX из подвала,
	 * канал Telegram из hero главной). Вид - custom.css.
	 */
	function initMobileMenuContacts() {
		var box = document.querySelector(".offcanvas-menu .offcanvas-inner");

		if (!box || box.querySelector(".kdc-menu-contacts")) {
			return;
		}

		/*
		 * Меню выезжает под шапкой, а шапка остаётся на месте: её «бургер»
		 * становится крестиком и закрывает меню. Штатно Helix по нему
		 * только открывает - перехватываем раньше его обработчика. Заодно
		 * отдаём в CSS высоту шапки, чтобы меню начиналось ровно под ней.
		 */
		var header = document.getElementById("sp-header");

		document.addEventListener(
			"click",
			function (e) {
				var toggler = e.target.closest("#offcanvas-toggler, .offcanvas-toggler-secondary");

				if (!toggler || !header || !header.contains(toggler)) {
					return;
				}

				document.documentElement.style.setProperty("--kdc-menu-top", header.offsetHeight + "px");

				if (document.body.classList.contains("offcanvas-active")) {
					e.preventDefault();
					e.stopPropagation();

					var close = document.querySelector(".close-offcanvas");

					if (close) {
						close.click();
					}
				}
			},
			true
		);

		var links = document.createElement("div");
		links.className = "kdc-menu-contacts";
		links.innerHTML =
			'<a href="https://max.ru/u/f9LHodD0cOKRfg5uDmdnqx6xUrXV8DrqTvOpOUtZpFRVLFV-lvqyAo9GJiY" target="_blank" rel="noopener">Написать в MAX</a>' +
			'<a href="https://t.me/kleverdors" target="_blank" rel="noopener">Telegram-канал</a>';
		box.appendChild(links);
	}

	/**
	 * Фон секции «CTA» на главной (fonvangog.webp, ~250 КБ) - почти в самом
	 * низу страницы, а грузился вместе с первым экраном и отнимал канал у
	 * hero. Пока секция далеко, custom.css снимает фон (.kdc-bg-in ещё нет);
	 * за полтора экрана до неё возвращаем - к появлению он уже загружен.
	 */
	function initLazyBackgrounds() {
		var sections = document.querySelectorAll(".cta-mockup-section");

		if (!sections.length) {
			return;
		}

		if (!("IntersectionObserver" in window)) {
			Array.prototype.forEach.call(sections, function (el) {
				el.classList.add("kdc-bg-in");
			});
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add("kdc-bg-in");
						observer.unobserve(entry.target);
					}
				});
			},
			{ rootMargin: "150% 0px" }
		);

		Array.prototype.forEach.call(sections, function (el) {
			observer.observe(el);
		});
	}

	/**
	 * Конфигуратор двери на странице коллекции (первый экран). Скрипт
	 * отдельный и тяжёлый - подгружаем только там, где он нужен.
	 */
	function initDoorConfigurator() {
		if (!document.querySelector(".kdc-cd-hero")) {
			return;
		}

		// Адрес (и сжатую копию, если она свежая) выбирает шаблон - ссылка
		// preload в <head>; без неё - исходник.
		var pre = document.getElementById("kdc-cfg-js");
		var s = document.createElement("script");
		s.src = pre ? pre.getAttribute("href") : "/templates/agentik/js/kdc-configurator.js?v=151";
		s.defer = true;
		document.body.appendChild(s);
	}

	/**
	 * Карусели SPPB (image_carousel, например «Хронология коллекций» на
	 * странице «О компании»): автопрокрутка стартует сразу при загрузке,
	 * и пока посетитель долистает до блока, лента уже уехала к 2017 году.
	 *
	 * Держим автопрокрутку выключенной, пока карусель не на экране, и
	 * запускаем, когда она показалась; ушла с экрана - снова стоп.
	 * options.autoplay тоже переключаем: плагин сам перезапускает таймер
	 * после каждого слайда, по фокусу окна и после ресайза, если этот флаг
	 * включён.
	 */
	function initCarouselAutoplayOnView() {
		if (!("IntersectionObserver" in window)) {
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					var inst = entry.target.kdcCarousel;

					if (!inst) {
						return;
					}

					if (entry.isIntersecting) {
						inst.options.autoplay = true;

						if (inst.timer === 0) {
							inst.startLoop();
						}
					} else {
						inst.options.autoplay = false;
						inst.stopLoop();
					}
				});
			},
			{ threshold: 0.35 }
		);

		whenCarouselsReady('.sppb-carousel-extended[data-autoplay="1"]', function (el, inst) {
			inst.options.autoplay = false;
			inst.stopLoop();
			observer.observe(el);
		});
	}

	/**
	 * Карусель с центральным элементом («Хронология коллекций»).
	 *
	 * 1. Клик по любому кружку ставит его в центр (штатно плагин сдвигает
	 *    ленту только на шаг и только для крайних элементов).
	 * 2. Центр считаем по геометрии: штатный расчёт ошибается на один
	 *    элемент, когда боковой отступ шире элемента (узкие десктопы).
	 * 3. Лента бесконечная - по краям стоят копии. Штатно плагин
	 *    перескакивает с копии на оригинал посреди движения, и увеличенный
	 *    кружок сбрасывается и начинает расти заново. Здесь перескок делается
	 *    ДО анимации и незаметно (без переходов, размеры сохраняются), а
	 *    потом лента плавно едет к цели. Через это же идёт автопрокрутка:
	 *    Next/Prev плагина подменены.
	 */
	function initCarouselClickSelect() {
		var CLICK_SPEED = 900;
		var CENTER = "sppb-carousel-extended-item-center";

		// Какой элемент реально стоит по центру окна при текущем сдвиге ленты.
		// Элемент i занимает [i*w, i*w + w - margin), окно - [pos, pos + ширина).
		function visualCenter(inst) {
			var w = inst.itemWidth;
			var m = inst.viewPort && inst.viewPort.margin ? inst.viewPort.margin : 0;
			var half = inst.$sliderList.outerWidth() / 2;

			return Math.round((inst._currentPosition + half - (w - m) / 2) / w);
		}

		// Центр и два соседа получают свои классы - по ним и идёт вся
		// анимация размера (custom.css). Штатные правила темы завязаны на
		// nth-child(3n), из-за чего соседи на каждом шаге меняли направление
		// сдвига и дёргались.
		function setCenter(inst, index) {
			var $items = inst.$outerStage.children();

			$items.removeClass(CENTER + " kdc-prev kdc-next");
			$items.eq(index).addClass(CENTER + " active");
			$items.eq(index - 1).addClass("kdc-prev active");
			$items.eq(index + 1).addClass("kdc-next active");

			// Точки под лентой: по одной на каждые options.items элементов.
			// Штатный расчёт падает на части позиций (:nth-child(1.5)).
			if (inst.$dotContainer) {
				var n = inst._numberOfItems;
				var real = (((index - inst._clones) % n) + n) % n;
				var $dots = inst.$dotContainer.children("li");

				$dots.removeClass("active");
				$dots.eq(Math.min(Math.floor(real / inst.options.items), $dots.length - 1)).addClass("active");
			}
		}

		function place(inst, pos, speed) {
			var transition = speed ? "all " + speed + "ms ease 0s" : "0s";

			inst.$outerStage.css({
				"-webkit-transition": transition,
				transition: transition,
				"-webkit-transform": "translate3D(-" + pos + "px,0px,0px)",
				transform: "translate3D(-" + pos + "px,0px,0px)"
			});
			inst._currentPosition = pos;

			// Плагин падает на расчёте точки-индикатора для некоторых позиций
			// (:nth-child(5.5)); центр к этому моменту уже выставлен.
			try {
				inst.processActivationWorker();
			} catch (e) {}

			// Точки плагин отмечает уже после центра - повторяем за ним.
			setCenter(inst, visualCenter(inst));
		}

		// Если по центру копия - мгновенно встаём на такой же оригинал.
		// Копия и оригинал выглядят одинаково, а переходы на это время
		// выключены, так что глазу ничего не видно.
		function settle(el, inst) {
			var n = inst._numberOfItems;
			var c = visualCenter(inst);
			var shift = 0;

			if (c >= inst._clones + n) {
				shift = -n;
			} else if (c < inst._clones) {
				shift = n;
			}

			if (!shift) {
				return;
			}

			el.classList.add("kdc-carousel-jump");
			place(inst, inst._currentPosition + shift * inst.itemWidth, 0);
			void el.offsetWidth;
			el.classList.remove("kdc-carousel-jump");
		}

		function go(el, inst, steps, speed) {
			if (!steps) {
				return;
			}

			// settle() уже зафиксировал позицию принудительным пересчётом
			// (offsetWidth), так что анимация стартует от неё, а не склеивается.
			settle(el, inst);
			place(inst, inst._currentPosition + steps * inst.itemWidth, speed);
		}

		whenCarouselsReady(".sppb-carousel-extended-center", function (el, inst) {
			el.classList.add("kdc-carousel-pick");

			// Вызов из init (t === 0) не трогаем: там лента ещё не выставлена.
			var ownCenter = inst.applyCenterMode;

			inst.applyCenterMode = function (t, i) {
				if (t === 0 || !this.$sliderList) {
					return ownCenter.call(this, t, i);
				}

				setCenter(this, visualCenter(this));
			};

			// Штатно по центру встаёт второй элемент ленты; хронология должна
			// начинаться с первого (2005). Сдвигаем без анимации.
			function centerOn(real) {
				el.classList.add("kdc-carousel-jump");
				place(inst, inst._currentPosition + (inst._clones + real - visualCenter(inst)) * inst.itemWidth, 0);
				void el.offsetWidth;
				el.classList.remove("kdc-carousel-jump");
			}

			centerOn(0);

			// При ресайзе плагин делает destroy() + init() и снова ставит в
			// центр второй элемент. Возвращаем ту коллекцию, что была в центре.
			// Перехватываем именно destroy/init: плагин зовёт их через this,
			// а свой onResize мог запомнить ещё до нас (ресайз при загрузке).
			var ownDestroy = inst.destroy;
			var ownInit = inst.init;

			inst.destroy = function () {
				var n = this._numberOfItems;

				this.kdcReal = (((visualCenter(this) - this._clones) % n) + n) % n;
				return ownDestroy.apply(this, arguments);
			};

			inst.init = function () {
				ownInit.apply(this, arguments);
				centerOn(this.kdcReal || 0);
			};

			// Автопрокрутка и свайп зовут Next/Prev.
			inst.Next = function () {
				go(el, inst, 1, inst.options.speed);
			};

			inst.Prev = function () {
				go(el, inst, -1, inst.options.speed);
			};

			el.addEventListener(
				"click",
				function (e) {
					var dot = e.target.closest(".sppb-carousel-extended-dots li");

					// Точка - переход к первому элементу своей группы.
					if (dot) {
						e.stopPropagation();
						e.preventDefault();

						var group = $(dot).index();
						var n = inst._numberOfItems;
						var real = (((visualCenter(inst) - inst._clones) % n) + n) % n;
						var jump = group * inst.options.items - real;

						go(el, inst, jump, CLICK_SPEED);

						if (inst.options.autoplay) {
							inst.stopLoop();
							inst.startLoop();
						}

						return;
					}

					var item = e.target.closest(".sppb-carousel-extended-item");

					if (!item || e.target.closest("a")) {
						return;
					}

					e.stopPropagation();
					e.preventDefault();

					if (inst.isDragging || inst.hasMoved) {
						return;
					}

					var steps = inst.$outerStage.children().index(item) - visualCenter(inst);

					if (!steps) {
						return;
					}

					// Шаг считаем до settle: после перескока на том же месте
					// окна стоит двойник, и шаг до цели не меняется.
					go(el, inst, steps, CLICK_SPEED);

					// Посетитель выбрал сам - даём посмотреть, прежде чем лента
					// поедет дальше.
					if (inst.options.autoplay) {
						inst.stopLoop();
						inst.startLoop();
					}
				},
				true
			);
		});
	}

	/**
	 * Экземпляры spCarousel создаются в document.ready самого плагина.
	 * Ждём их и запоминаем на элементе: при ресайзе плагин делает
	 * destroy() + init() и стирает свои $.data, а объект остаётся тем же.
	 */
	function whenCarouselsReady(selector, callback) {
		var carousels = document.querySelectorAll(selector);
		var tries = 0;

		if (!carousels.length) {
			return;
		}

		(function attach() {
			var pending = Array.prototype.filter.call(carousels, function (el) {
				return !el.kdcCarousel && !$.data(el, "spCarousel");
			});

			if (pending.length && tries++ < 50) {
				setTimeout(attach, 100);
				return;
			}

			Array.prototype.forEach.call(carousels, function (el) {
				var inst = el.kdcCarousel || $.data(el, "spCarousel");

				if (inst) {
					el.kdcCarousel = inst;
					callback(el, inst);
				}
			});
		})();
	}

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

		// Заголовки в ленте блога обрезаны до трёх строк - полный текст
		// показываем подсказкой при наведении.
		var titleHints = function (root) {
			root.querySelectorAll(".kdc-journal .sppb-addon-article h5 a:not([title])").forEach(function (a) {
				a.title = a.textContent.trim();
			});
		};

		titleHints(document);

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
			// В блоге ряд из трёх карточек, на главной - тоже три.
			article.dataset.kdcIndex = i % 3;
			observer.observe(article);
		});

		// Кнопка «Показать ещё» дописывает статьи в ленту запросом - без
		// этого они так и остались бы прозрачными.
		if (!("MutationObserver" in window)) {
			return;
		}

		document.querySelectorAll(".kdc-journal .sppb-addon-content > .sppb-row").forEach(function (row) {
			new MutationObserver(function () {
				titleHints(document);
				row.querySelectorAll(".sppb-addon-article:not([data-kdc-index])").forEach(function (article) {
					var all = row.querySelectorAll(".sppb-addon-article");

					article.dataset.kdcIndex = Array.prototype.indexOf.call(all, article) % 3;
					observer.observe(article);
				});
			}).observe(row, { childList: true });
		});
	}

	/**
	 * «Из жизни Клевердорс»: мелкая обложка в крупной ячейке.
	 *
	 * Снимок не уже ячейки - занимает её на всю ширину, как обычно: так
	 * видно больше всего. Заметно уже (растягивать больше чем на 15%) - он
	 * размылся бы; тогда ставим его по центру во всю высоту, а ячейку
	 * заполняет его же размытая копия (стили - .kdc-lowres в custom.css).
	 * Сравниваем с шириной ячейки в CSS-пикселях: на ретине фото шире
	 * ячейки смотрится нормально. Картинки грузятся лениво, поэтому
	 * проверяем по загрузке и заново при смене ширины.
	 */
	function initFeedLowRes() {
		var imgs = document.querySelectorAll(".kdc-jh-feed .sppb-article-img-wrap img");

		if (!imgs.length) {
			return;
		}

		var check = function (img) {
			var wrap = img.closest(".sppb-article-img-wrap");

			if (!wrap || !img.naturalWidth) {
				return;
			}

			var low = wrap.clientWidth / img.naturalWidth > 1.15;

			wrap.classList.toggle("kdc-lowres", low);
			wrap.style.setProperty("--kdc-cover", low ? 'url("' + img.currentSrc.replace(/"/g, "%22") + '")' : "");
		};

		Array.prototype.forEach.call(imgs, function (img) {
			img.addEventListener("load", function () { check(img); });

			if (img.complete) {
				check(img);
			}
		});

		var timer;

		window.addEventListener("resize", function () {
			clearTimeout(timer);
			timer = setTimeout(function () {
				Array.prototype.forEach.call(imgs, check);
			}, 150);
		});
	}

	/**
	 * Блог: кнопка «Показать ещё» (articles-pagination.js из SPPB).
	 *
	 * У плагина один счётчик страниц на всю страницу, а лент у нас шесть
	 * (по вкладке на категорию): нажали «ещё» во «Всех» - и вкладка
	 * «Проекты» потом начнёт не со второй страницы. Держим свой счётчик на
	 * каждой кнопке и перед штатным обработчиком подставляем его в общий.
	 */
	function initArticlesLoadMore() {
		document.querySelectorAll(".kdc-journal .sppb-addon-articles__pagination").forEach(function (box) {
			var button = box.querySelector("[data-sppb-articles-load-more-button]");
			var row = box.closest(".sppb-addon-articles").querySelector(".sppb-addon-content > .sppb-row");
			var limitInput = box.querySelector('[name="sppb-articles-limit"]');
			var limit = limitInput ? Number(limitInput.value) : 0;
			var count = function () {
				return row ? row.querySelectorAll(".sppb-addon-article").length : 0;
			};

			if (!button || !row || !limit) {
				return;
			}

			// Число страниц плагин считает по всем статьям сайта, а не по
			// категории вкладки - кнопка висела и там, где статей 3-5.
			// Не хватает на вторую страницу - кнопка не нужна.
			if (count() < limit) {
				box.remove();
				return;
			}

			// Плагин блокирует кнопку на время запроса и снимает блок после.
			// Пришло меньше полной страницы - дальше грузить нечего.
			var before = 0;

			new MutationObserver(function () {
				if (!button.disabled && count() - before < limit) {
					box.remove();
				}
			}).observe(button, { attributes: true, attributeFilter: ["disabled"] });

			// Перехват на родителе срабатывает раньше обработчика на кнопке.
			box.addEventListener(
				"click",
				function (e) {
					if (!e.target.closest("[data-sppb-articles-load-more-button]")) {
						return;
					}

					var shown = Number(button.dataset.kdcPage || 1);

					before = count();

					try {
						// eslint-disable-next-line no-undef
						sppbArtcileAddonCurrentPage = shown;
					} catch (err) {
						return;
					}

					button.dataset.kdcPage = shown + 1;

					// Плагин на время запроса пишет на кнопке «Loading...»
					// (строка зашита в articles-pagination.js). Его обработчик
					// срабатывает после нашего - подменяем текст следом.
					setTimeout(function () {
						if (button.disabled) {
							button.textContent = "Загружаем…";
						}
					}, 0);
				},
				true
			);
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
		function measure() {
			queued = false;

			var box = section.getBoundingClientRect();
			var visible = Math.min(box.bottom, window.innerHeight) - Math.max(box.top, 0);

			if (box.height && visible / box.height >= 0.25) {
				open();
			}
		}

		// Замер геометрии - раз в кадр, а не на каждое событие прокрутки:
		// getBoundingClientRect посреди обработчика заставлял браузер
		// досчитывать раскладку вне очереди.
		var queued = false;

		function check() {
			if (!queued) {
				queued = true;
				window.requestAnimationFrame(measure);
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
		measure();
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

				// crossOrigin нужен только чужому домену: для своего он
				// превращал запрос в CORS, и тот же снимок hero скачивался и
				// декодировался второй раз мимо уже загруженного фона.
				try {
					if (new URL(url, window.location.href).origin !== window.location.origin) {
						img.crossOrigin = "anonymous";
					}
				} catch (e) {
					img.crossOrigin = "anonymous";
				}

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

	/* Страница коллекции: кнопка «Назад» в каталог справа от названия. */
	$(function () {
		var title = document.querySelector(".kdc-cd-hero-title");

		if (!title || title.parentNode.querySelector(".kdc-back")) {
			return;
		}

		var back = document.createElement("a");
		back.className = "kdc-back";
		back.href = "/katalogproduktsii";
		back.innerHTML = '<span aria-hidden="true">←</span><span class="kdc-back-t">Назад</span>';
		back.setAttribute("aria-label", "Назад в каталог");
		back.title = "Назад в каталог";

		var row = document.createElement("div");
		row.className = "kdc-cd-title-row";
		title.parentNode.insertBefore(row, title);
		row.appendChild(title);
		row.appendChild(back);
	});

})(jQuery);
