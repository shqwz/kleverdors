<?php
/**
 * @package Helix_Ultimate_Framework
 * @author JoomShaper <support@joomshaper.com>
 * Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */

defined('_JEXEC') or die('Restricted Direct Access!');

use HelixUltimate\Framework\Core\HelixUltimate;
use HelixUltimate\Framework\Platform\Helper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication();
$this->setHtml5(true);

/**
 * Собранный скрипт темы (cache/com_templates/templates/agentik/<хэш>.js)
 * Helix пересобирает только раз в cachetime минут, а имя у него не меняется.
 * После заливки новых custom.js/main.js сайт до этого момента отдавал старый
 * код вместе с новыми стилями. Если исходники новее сборки - удаляем её,
 * и Helix соберёт свежую на этой же странице (compress_js идёт позже, в
 * onBeforeCompileHead).
 */
if ($app->isClient('site'))
{
	$kdcBundles = glob(JPATH_ROOT . '/cache/com_templates/templates/agentik/*.js') ?: [];

	if ($kdcBundles)
	{
		$kdcSources = max(array_map('filemtime', glob(__DIR__ . '/js/*.js') ?: [__FILE__]));

		foreach ($kdcBundles as $kdcBundle)
		{
			if (filemtime($kdcBundle) < $kdcSources)
			{
				@unlink($kdcBundle);
			}
		}
	}
}

/**
 * Лёгкие картинки: рядом с фото в images/ лежат сжатые копии
 * <файл>.jpg.webp. На хостинге статику отдаёт nginx мимо .htaccess,
 * поэтому подставляем WebP прямо в готовую страницу - только там, где
 * копия есть. Теги <meta> (картинки для соцсетей) не трогаем.
 */
if ($app->isClient('site') && $app->input->get('helixMode', '') !== 'edit')
{
	$app->getDispatcher()->addListener('onAfterRender', static function () use ($app) {
		$body = $app->getBody();

		if (!$body || stripos($body, 'images/') === false)
		{
			return;
		}

		// Класс на <html> - одним атрибутом (второй class браузер игнорирует).
		$kdcHtmlClass = static function ($html, $cls) {
			return preg_replace_callback('~<html\b([^>]*)>~i', static function ($m) use ($cls) {
				if (preg_match('~\sclass\s*=\s*(["\'])(.*?)\1~i', $m[1], $c))
				{
					$attrs = str_replace($c[0], ' class="' . trim($c[2] . ' ' . $cls) . '"', $m[1]);
				}
				else
				{
					$attrs = $m[1] . ' class="' . $cls . '"';
				}

				return '<html' . $attrs . '>';
			}, $html, 1);
		};

		/**
		 * Лишние стили, которые тема подключает второй раз:
		 * - шрифты Google (Inter, Source Serif Pro): те же начертания уже лежат
		 *   на сайте (media/com_sppagebuilder/assets/google-fonts) и подключаются
		 *   конструктором страниц. Внешняя копия - лишний запрос к стороннему
		 *   серверу и те же файлы второй раз; у Source Serif Pro она к тому же
		 *   ничего не добавляет (начертания 500 нет и у Google);
		 * - joomla-fontawesome: те же иконки Font Awesome 6 приходят из
		 *   font-awesome-6.min.css конструктора страниц (около 255 КБ вместе со
		 *   шрифтом).
		 */
		$body = preg_replace(
			'~<link\b[^>]*(?:fonts\.googleapis\.com/css|media/system/css/joomla-fontawesome(?:\.min)?\.css)[^>]*>\s*~i',
			'',
			$body
		);

		/**
		 * Скрипты и стили, которыми на сайте никто не пользуется (проверено по
		 * всем 52 страницам: ни окон, ни тостов, ни поповеров, ни кнопок-
		 * переключателей Bootstrap, ни переключателя цвета конструктора).
		 * 8 запросов на каждой странице. Если такие элементы появятся в
		 * редакторе страниц - убрать соответствующее имя из списка.
		 */
		$body = preg_replace(
			'~<(?:script|link)\b[^>]*(?:bootstrap/js/(?:alert|button|popover|scrollspy|toast|modal)\.min\.js|com_sppagebuilder/assets/(?:js|css)/color-switcher\.(?:js|css))[^>]*>(?:\s*</script>)?\s*~i',
			'',
			$body
		);

		/**
		 * Font Awesome (~275 КБ: стили FA6, сдвиги FA4, шрифт) ради трёх значков:
		 * стрелка вправо, «наверх», шеврон - их рисует custom.css (html.kdc-no-fa).
		 * Выкидываем FA только если на странице нет других значков FA: любой
		 * другой класс fa-* (добавили в редакторе) оставляет FA как есть.
		 * Значки, которые скрипты создают на лету, проверены по всем 52
		 * страницам - кроме этих трёх их нет.
		 */
		$kdcMarkup = preg_replace('~<(script|style)\b.*?</\1>~is', '', $body);
		preg_match_all('~\bclass\s*=\s*["\']([^"\']*)["\']~i', $kdcMarkup, $kdcClasses);
		$kdcFa = array_filter(preg_split('~\s+~', implode(' ', $kdcClasses[1])), static function ($c) {
			return preg_match('~^fa[srb]?(-|$)~', $c) && !in_array($c, ['fa', 'fas', 'fa-arrow-right-long', 'fa-angle-up', 'fa-chevron-right'], true);
		});

		if (!$kdcFa)
		{
			$body = preg_replace(
				'~<link\b[^>]*(?:font-awesome-[56]\.min\.css|font-awesome-v4-shims\.css)[^>]*>\s*~i',
				'',
				$body
			);
			$body = $kdcHtmlClass($body, 'kdc-no-fa');
		}

		/**
		 * Облегчение страницы (замерено по всем 52 страницам сайта):
		 *
		 * 1. Строки перевода в настройках скриптов (joomla-script-options):
		 *    конструктор кладёт туда 862 строки своего РЕДАКТОРА (~63 КБ на
		 *    каждой странице), а код сайта использует 10 (обратный отсчёт и
		 *    сообщения Joomla). Оставляем только их.
		 */
		$body = preg_replace_callback('~(<script type="application/json" class="joomla-script-options[^"]*">)(.*?)(</script>)~s', static function ($m) {
			$opt = json_decode($m[2], true);

			if (!is_array($opt) || empty($opt['joomla.jtext']) || !is_array($opt['joomla.jtext']))
			{
				return $m[0];
			}

			$keep = ['COM_SPPAGEBUILDER_DAY', 'COM_SPPAGEBUILDER_DAYS', 'COM_SPPAGEBUILDER_HOUR', 'COM_SPPAGEBUILDER_HOURS',
				'COM_SPPAGEBUILDER_MINUTE', 'COM_SPPAGEBUILDER_MINUTES', 'COM_SPPAGEBUILDER_SECOND', 'COM_SPPAGEBUILDER_SECONDS',
				'ERROR', 'MESSAGE', 'NOTICE', 'WARNING', 'JCLOSE', 'JOK', 'JOPEN'];
			$opt['joomla.jtext'] = array_intersect_key($opt['joomla.jtext'], array_flip($keep));

			return $m[1] . json_encode($opt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . $m[3];
		}, $body, 1);

		/**
		 * 2. Цветовые переменные конструктора: встроенный скрипт (~19 КБ) на
		 *    каждой странице заново выбирал значения для активной темы, а тема
		 *    у сайта всегда «Default». Тот же результат пишем прямо в <html>
		 *    (style - как это делал скрипт) и в <body> (data-sppb-color-mode).
		 */
		$body = preg_replace_callback('~<script>\s*const initColorMode = .*?</script>~s', static function ($m) use (&$kdcColorCss) {
			if (!preg_match('~window\.sppbColorVariables\s*=\s*(\[.*?\]);~s', $m[0], $v) || !preg_match('~activeColorMode = "([^"]+)";\s*const modes~', $m[0], $mode))
			{
				return $m[0];
			}

			$vars = json_decode($v[1], true);

			if (!is_array($vars))
			{
				return $m[0];
			}

			$css = [];

			foreach ($vars as $var)
			{
				if (isset($var['path'][0], $var['path'][1], $var['value']) && $var['path'][1] === $mode[1])
				{
					$css[] = '--sppb-' . str_replace(' ', '-', strtolower(trim((string) $var['path'][0]))) . ': ' . $var['value'];
				}
			}

			$kdcColorCss = [implode(';', $css), $mode[1]];

			return '';
		}, $body, 1);

		if (!empty($kdcColorCss))
		{
			$body = preg_replace_callback('~<html\b([^>]*)>~i', static function ($m) use ($kdcColorCss) {
				return '<html' . $m[1] . ' style="' . htmlspecialchars($kdcColorCss[0], ENT_QUOTES) . '">';
			}, $body, 1);
			$body = preg_replace('~<body\b~i', '<body data-sppb-color-mode="' . htmlspecialchars($kdcColorCss[1], ENT_QUOTES) . '"', $body, 1);
		}

		/**
		 * 3. Файлы, которые ничего не делают:
		 *    - animate.min.css (69 КБ): все анимации появления сайт и так
		 *      заменяет своей (custom.css, kdc-reveal), нужное правило
		 *      .sppb-wow перенесено в custom.css;
		 *    - chosen.css: стили выпадающих списков Helix, на сайте их нет;
		 *    - jquery.parallax.js: параллакс-фонов на сайте нет (конструктор
		 *      вызывает его только если он подключён).
		 */
		$body = preg_replace(
			'~<(?:link|script)\b[^>]*(?:com_sppagebuilder/assets/css/animate\.min\.css|helixultimate/assets/css/chosen\.css|com_sppagebuilder/assets/js/jquery\.parallax\.js)[^>]*>(?:\s*</script>)?\s*~i',
			'',
			$body
		);

		/**
		 * 4. Скрипты не блокируют отрисовку. Раньше 11 скриптов в <head>
		 *    (jQuery, конструктор, тема, lazysizes) грузились и выполнялись по
		 *    одному, и пока последний не выполнен, браузер не показывал
		 *    страницу. С defer они грузятся параллельно с разбором страницы
		 *    и выполняются в том же порядке после него, до DOMContentLoaded.
		 *    Встроенные скрипты, которые ждут jQuery (jQuery(function($){...}) -
		 *    проверено по всем 52 страницам, других нет), запускаем на
		 *    DOMContentLoaded: к этому моменту отложенный jQuery уже выполнен.
		 *    ?kdcsync в адресе - старый порядок, для сравнения.
		 */
		if (strpos((string) ($_SERVER['QUERY_STRING'] ?? ''), 'kdcsync') === false)
		{
			$body = preg_replace_callback('~<script\b([^>]*)\bsrc=("[^"]*"|\'[^\']*\')([^>]*)>~i', static function ($m) {
				$attrs = $m[1] . $m[3];

				if (preg_match('~\b(?:defer|async)\b|type\s*=\s*["\']?(?:module|application/json)~i', $attrs))
				{
					return $m[0];
				}

				return '<script' . $m[1] . 'src=' . $m[2] . $m[3] . ' defer>';
			}, $body);
			$body = preg_replace_callback('~(<script\b(?![^>]*\bsrc=)(?![^>]*json)[^>]*>)(\s*jQuery\s*\(\s*function\s*\(.*?)(</script>)~is', static function ($m) {
				return $m[1] . 'document.addEventListener("DOMContentLoaded",function(){' . $m[2] . "\n});" . $m[3];
			}, $body);
		}

		/**
		 * 5. Слайдер bxslider (CSS + 2 скрипта, ~35 КБ) конструктор подключает
		 *    ко всем галереям, а включается он только у галереи с
		 *    data-enable-slider="true". Таких на сайте нет - галереи плиткой.
		 */
		if (strpos($body, 'data-enable-slider="true"') === false)
		{
			$body = preg_replace(
				'~<(?:link|script)\b[^>]*(?:jquery\.bxslider\.min\.(?:css|js)|addons/dc-gallery-bxslider\.js)[^>]*>(?:\s*</script>)?\s*~i',
				'',
				$body
			);
		}

		$metas = [];
		$body  = preg_replace_callback('~<meta\b[^>]*>~i', static function ($m) use (&$metas) {
			$metas[] = $m[0];

			return "\x01kdcmeta" . (\count($metas) - 1) . "\x01";
		}, $body);

		/**
		 * Двойной слэш в адресах картинок (http://сайт//images/...): его даёт
		 * аддон динамического контента. Браузер и nginx считают такой адрес
		 * отдельным файлом - кэш не переиспользуется между страницами.
		 */
		$body = preg_replace('~((?:(?:src|data-src|data-large|href|content)=["\']|url\(["\']?)https?://[^/"\')]+)//(images/)~i', '$1/$2', $body);

		$body = preg_replace_callback(
			'~(?<![\w/.-])(?:(?:https?:)?//[^/"\'\s()<>]+)?/{0,2}images/[^"\'\s()<>?#\\\\]+?\.(?:jpe?g|png)(?=["\'\s)?#&<])~i',
			static function ($m) {
				$rel = ltrim(preg_replace('~^(?:https?:)?//[^/]+~i', '', $m[0]), '/');
				$rel = rawurldecode(html_entity_decode($rel, ENT_QUOTES));

				if (strpos($rel, '..') !== false || strpos($rel, 'images/') !== 0)
				{
					return $m[0];
				}

				return is_file(JPATH_ROOT . '/' . $rel . '.webp') ? $m[0] . '.webp' : $m[0];
			},
			$body
		);

		/**
		 * Снимки карточек каталога и блока «Наша коллекция» на главной.
		 * Общая таблица подмен сохраняет одинаковые фотографии коллекций.
		 */
		$kdcCatalogImages = [
			'images/modeli/P-6.jpeg.webp' => 'images/modeli/catalog/interiors-20261006/classic-p.webp',
			'images/modeli/catalog/realta.webp' => 'images/modeli/catalog/realta.webp',
			'images/modeli/catalog/provans.webp' => 'images/modeli/catalog/provans.webp',
			'images/modeli/catalog/lajt.webp' => 'images/modeli/catalog/interiors-20261006/lajt.webp',
			'images/modeli/catalog/21vek.webp' => 'images/modeli/catalog/interiors-20261006/21vek.webp',
			'images/modeli/line/vizual/ddfee7d1-edb8-4475-b3d8-1005e4449f87.webp' => 'images/modeli/catalog/interiors-20261006/line.webp',
			'images/modeli/neoklassika/vizual/6a287f56-5e5d-4ee3-a46d-ca16ff60ffe5.webp' => 'images/modeli/catalog/interiors-20261006/neoklassika.webp',
			'images/modeli/catalog/martin.webp' => 'images/modeli/catalog/interiors-20261006/martin.webp',
			'images/modeli/catalog/art-kraft.webp' => 'images/modeli/catalog/interiors-20261006/art-kraft.webp',
			'images/modeli/catalog/m01.webp' => 'images/modeli/catalog/m01.webp',
			'images/modeli/catalog/shpon.webp' => 'images/modeli/catalog/shpon.webp',
			'images/modeli/bigproem/P2-sirina-4-m-1.jpg.webp' => 'images/modeli/catalog/interiors-20261006/bigproem.webp',
			'images/modeli/Realta_13.jpg.webp' => 'images/modeli/catalog/realta.webp',
			'images/modeli/b/vizual/f2742c4c-3d48-4c95-a0bb-949a66f9c10d.webp' => 'images/modeli/catalog/provans.webp',
			'images/modeli/m01/visual/ca670454-5a32-41fa-96de-d1e7df3249d5.webp' => 'images/modeli/catalog/m01.webp',
			'images/modeli/shpon/525abe58-1c00-418b-99e9-b893c7e1c3e5.webp' => 'images/modeli/catalog/shpon.webp',
			'images/modeli/21vek/vizual/40d62c7b-3eeb-4229-be29-ecdadfddc060.webp' => 'images/modeli/catalog/interiors-20261006/21vek.webp',
			'images/modeli/lite/vizual/c7ed2df0-5dba-403c-b0d9-7775680743e0.webp' => 'images/modeli/catalog/interiors-20261006/lajt.webp',
			'images/modeli/martin/vizual/53d8e058-5b72-467c-9671-48e069a10d6d.webp' => 'images/modeli/catalog/interiors-20261006/martin.webp',
			'images/modeli/art/vizual/bd610ac3-171f-4d1f-96f0-5b3a97fecc3d.webp' => 'images/modeli/catalog/interiors-20261006/art-kraft.webp',
		];
		if (rtrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/') === '/katalogproduktsii')
		{
			$body = strtr($body, $kdcCatalogImages);
		}
		elseif (strpos($body, 'hero-wrap') !== false)
		{
			// На главной меняем только фотографии ссылок-карточек коллекций.
			$body = preg_replace_callback(
				'~<a\b[^>]*\bclass="[^"]*\bsppb-dynamic-content-image-wrapper\b[^"]*"[^>]*>.*?</a>~s',
				static function ($m) use ($kdcCatalogImages) {
					return strtr($m[0], $kdcCatalogImages);
				},
				$body
			);
		}

		// Обновлённые иллюстрации «Индивидуальных решений» с прозрачным фоном.
		// Новые имена обходят длительный кэш картинок на хостинге.
		if (strpos($body, 'hero-wrap') !== false)
		{
			$kdcSolutionImages = [
				'images/2026/forsait/reshenie-2.webp' => 'images/solutions/sketch-20261006.webp',
				'images/2026/forsait/reshenie-3.webp' => 'images/solutions/opening-20261006.webp',
				'images/2026/forsait/reshenie-1.webp' => 'images/solutions/dimensions-2100-700-20261006.webp',
			];
			$body = preg_replace_callback('~<img\b[^>]*>~i', static function ($m) use ($kdcSolutionImages) {
				foreach ($kdcSolutionImages as $old => $new)
				{
					if (strpos($m[0], $old) === false || !is_file(JPATH_ROOT . '/' . $new))
					{
						continue;
					}
					$tag = str_replace($old, $new, $m[0]);
					$size = getimagesize(JPATH_ROOT . '/' . $new);
					if ($size)
					{
						$tag = preg_replace('~\s(?:width|height)="[^"]*"~i', '', $tag);
						$tag = preg_replace('~<img\b~i', '<img width="' . $size[0] . '" height="' . $size[1] . '"', $tag, 1);
					}
					return $tag;
				}
				return $m[0];
			}, $body);
		}

		// Две независимые подборки портфолио: один снимок попадает только в один ряд.
		if (strpos($body, 'hero-wrap') !== false)
		{
			$kdcPortfolioPhotos = [
				'images/modeli/21vek/portfolio/H11-1.jpg.webp',
				'images/modeli/art/portfolio/A-6_0-1.jpg.webp',
				'images/modeli/b/portfolio/V2-1.jpg.webp',
				'images/modeli/e/portfolio/E-po-eskizu.jpg.webp',
				'images/modeli/line/portfolio/20ba5c90-8482-4fbf-96ce-3177b1a1be5a.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-1-provans-1.jpg.webp',
				'images/modeli/m01/portfolio/M01-moldingi.jpeg.webp',
				'images/modeli/neoklassika/portfolio/S1-1.jpg.webp',
				'images/modeli/p/portfolio/P10-dverki-garderob.webp',
				'images/modeli/realta/portfolio/Realta-1-1.jpg.webp',
				'images/modeli/21vek/portfolio/H11-2.jpg.webp',
				'images/modeli/art/portfolio/A-6_0-2.jpg.webp',
				'images/modeli/b/portfolio/V2-2.jpg.webp',
				'images/modeli/e/portfolio/E1-belyj.jpg.webp',
				'images/modeli/line/portfolio/3eaa5a22-2bd5-4484-a59b-4fa655798e23.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-1-provans-2.jpg.webp',
				'images/modeli/m01/portfolio/h_bknsftsvkzpv-etrzx6lynwapafr7liigmcxemahcli1wbtdktbjzi4zhrdbpaa76qbucnq8v9tjbbiorsiutk.jpg.webp',
				'images/modeli/neoklassika/portfolio/S1-framuga.jpg.webp',
				'images/modeli/p/portfolio/P10.jpg.webp',
				'images/modeli/realta/portfolio/Realta-1-2.jpg.webp',
				'images/modeli/21vek/portfolio/H11-3.jpg.webp',
				'images/modeli/art/portfolio/A-6_0-3.jpg.webp',
				'images/modeli/b/portfolio/V2-3.jpg.webp',
				'images/modeli/e/portfolio/E1-diz-morilka.jpeg.webp',
				'images/modeli/line/portfolio/4d9ef051-4a56-4535-bcc0-5ca5d12aeb00.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-1-provans.jpg.webp',
				'images/modeli/neoklassika/portfolio/S1-kapitel-21-vek.jpg.webp',
				'images/modeli/p/portfolio/P19-1.jpg.webp',
				'images/modeli/realta/portfolio/Realta-1-framuga.webp',
				'images/modeli/21vek/portfolio/H11-4.jpg.webp',
				'images/modeli/art/portfolio/A-6_0.jpg.webp',
				'images/modeli/b/portfolio/V2-4.jpg.webp',
				'images/modeli/e/portfolio/E1-figurnaa-filenka.jpg.webp',
				'images/modeli/line/portfolio/77d3a9b1-8ff8-4945-98d7-19e3b591df2c.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-13-zerkalo.jpg.webp',
				'images/modeli/neoklassika/portfolio/S1-kapitel.jpg.webp',
				'images/modeli/p/portfolio/P19.jpg.webp',
				'images/modeli/realta/portfolio/Realta-1.jpeg.webp',
				'images/modeli/21vek/portfolio/H11.jpg.webp',
				'images/modeli/art/portfolio/detrojt-1.jpg.webp',
				'images/modeli/b/portfolio/V2-5.jpg.webp',
				'images/modeli/e/portfolio/E1-panel.jpg.webp',
				'images/modeli/line/portfolio/93991a60-de2c-4b99-a402-895c243a67b2.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-13.jpg.webp',
				'images/modeli/neoklassika/portfolio/S1-obramlenia.jpg.webp',
				'images/modeli/p/portfolio/P2-1.jpg.webp',
				'images/modeli/realta/portfolio/Realta-1.jpg.webp',
				'images/modeli/21vek/portfolio/H21.jpg.webp',
				'images/modeli/art/portfolio/detrojt.jpg.webp',
				'images/modeli/b/portfolio/V2-6.jpg.webp',
				'images/modeli/e/portfolio/E1-zelenyj.jpg.webp',
				'images/modeli/line/portfolio/9f691bde-eb55-47b6-8488-d0b2772ba98c.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-3-skladnaa-.webp',
				'images/modeli/neoklassika/portfolio/S1.jpg.webp',
				'images/modeli/p/portfolio/P2-2.jpg.webp',
				'images/modeli/realta/portfolio/Realta-11-1.jpg.webp',
				'images/modeli/21vek/portfolio/H22-zerkalo.jpeg.webp',
				'images/modeli/art/portfolio/img_2530.jpg.webp',
				'images/modeli/b/portfolio/V2-7.jpg.webp',
				'images/modeli/e/portfolio/E1.jpg.webp',
				'images/modeli/line/portfolio/Lajn-eskiz-1.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-3.jpg.webp',
				'images/modeli/neoklassika/portfolio/S2-framuga.jpg.webp',
				'images/modeli/p/portfolio/P2-2500.jpg.webp',
				'images/modeli/realta/portfolio/Realta-11.jpg.webp',
				'images/modeli/21vek/portfolio/H22.jpeg.webp',
				'images/modeli/art/portfolio/img_2534.jpg.webp',
				'images/modeli/b/portfolio/V2-falspanel.jpg.webp',
				'images/modeli/e/portfolio/E11-2300-2500.jpg.webp',
				'images/modeli/line/portfolio/Lajn-eskiz-molding-1.jpg.webp',
				'images/modeli/lite/portfolio/Lajt-9-komplanar-1.jpg.webp',
				'images/modeli/neoklassika/portfolio/S3-1.jpg.webp',
				'images/modeli/p/portfolio/P2-3.jpg.webp',
				'images/modeli/realta/portfolio/Realta-13.jpg.webp',
			];
			preg_match_all('~<div\b[^>]*class="[^"]*\b(?:rtl|ltr)-infinity-scroller-kdc\b[^"]*"[^>]*>~i', $body, $kdcLanes, PREG_OFFSET_CAPTURE);
			foreach (array_reverse($kdcLanes[0], true) as $laneIndex => $lane)
			{
				$start = $lane[1] + strlen($lane[0]);
				$depth = 1;
				preg_match_all('~</?div\b[^>]*>~i', substr($body, $start), $divs, PREG_OFFSET_CAPTURE);
				foreach ($divs[0] as $div)
				{
					$depth += stripos($div[0], '</div') === 0 ? -1 : 1;
					if ($depth !== 0) { continue; }
					$cards = '';
					foreach ($kdcPortfolioPhotos as $photoIndex => $photo)
					{
						if ($photoIndex % 2 !== $laneIndex % 2) { continue; }
						$size = getimagesize(JPATH_ROOT . '/' . $photo);
						if (!$size) { continue; }
						$cards .= '<div class="sppb-addon-wrapper addon-root-image"><div class="sppb-addon-single-image-container"><img class="sppb-img-responsive lazyload" data-src="/' . htmlspecialchars($photo, ENT_QUOTES) . '" width="' . $size[0] . '" height="' . $size[1] . '" alt="Дверь из портфолио КЛЕВЕРДОРС" decoding="async"></div></div>';
					}
					$body = substr_replace($body, $cards, $start, $div[1]);
					break;
				}
			}
		}

		/**
		 * Уменьшенные копии: к большим снимкам рядом лежат <файл>.w480/.w800/
		 * .w1200.webp. Картинки грузит lazysizes (data-src), поэтому отдаём ему
		 * data-srcset + data-sizes="auto": он подставит реальную ширину
		 * картинки на странице, и браузер возьмёт ближайший файл, а не
		 * 1600px-оригинал для карточки в 320px. Для object-fit: cover ширину
		 * поправляет custom.js по data-kdc-ratio (пропорции исходника).
		 */
		$body = preg_replace_callback('~<img\b[^>]*>~i', static function ($m) {
			$tag = $m[0];

			if (stripos($tag, 'srcset') !== false
				|| !preg_match('~\sdata-src=(["\'])([^"\'\s,]+\.webp)\1~i', $tag, $src))
			{
				return $tag;
			}

			$url = $src[2];
			$rel = ltrim(preg_replace('~^(?:https?:)?//[^/]+~i', '', $url), '/');
			$rel = rawurldecode(html_entity_decode($rel, ENT_QUOTES));

			if (strpos($rel, '..') !== false || strpos($rel, 'images/') !== 0 || preg_match('~\.w\d+\.webp$~', $rel))
			{
				return $tag;
			}

			$set = [];

			foreach ([480, 800, 1200] as $w)
			{
				if (is_file(JPATH_ROOT . '/' . substr($rel, 0, -5) . '.w' . $w . '.webp'))
				{
					$set[] = substr($url, 0, -5) . '.w' . $w . '.webp ' . $w . 'w';
				}
			}

			$size = $set ? @getimagesize(JPATH_ROOT . '/' . $rel) : false;

			if (!$size || !$size[1])
			{
				return $tag;
			}

			$set[] = $url . ' ' . $size[0] . 'w';
			$attrs = ' data-srcset="' . implode(', ', $set) . '" data-sizes="auto" data-kdc-ratio="' . round($size[0] / $size[1], 4) . '"';

			return preg_replace('~\s*/?>$~', addcslashes($attrs, '\\$') . '$0', $tag, 1);
		}, $body);

		/**
		 * Сжатая копия custom.css (tools/build-min.sh): вдвое меньше. Берём её
		 * только пока она не старше исходника - после правки custom.css без
		 * пересборки отдаётся сам custom.css, а не устаревшая копия.
		 */
		$kdcCss = JPATH_THEMES . '/agentik/css/custom.css';
		$kdcMin = JPATH_THEMES . '/agentik/css/custom.min.css';

		if (is_file($kdcMin) && is_file($kdcCss) && filemtime($kdcMin) >= filemtime($kdcCss))
		{
			$body = preg_replace('~(templates/agentik/css/)custom\.css~', '$1custom.min.css', $body);
		}

		/**
		 * Версия у стилей и скриптов шаблона. custom.css, template.css и
		 * собранный скрипт темы (cache/com_templates/...js - его имя зависит
		 * только от списка файлов, не от содержимого) подключаются без
		 * версии, а nginx на хостинге отдаёт статику с долгим кэшем: телефон,
		 * который уже был на сайте, продолжал показывать старые файлы -
		 * без свежих правок. Дата изменения файла в адресе меняется с каждой
		 * правкой, и браузер берёт новый файл.
		 */
		$body = preg_replace_callback(
			'~(\s(?:href|src)=")((?:https?://[^/"]+)?/*((?:templates/agentik/(?:css|js)|cache/com_templates/templates/agentik)/[^"?#]+\.(?:css|js)))(")~i',
			static function ($m) {
				$file = JPATH_ROOT . '/' . $m[3];

				if (!is_file($file))
				{
					return $m[0];
				}

				$version = filemtime($file);

				// Собранный скрипт Helix перезаписывает раз в 15 минут и без
				// правок - версию берём по его исходникам, а не по нему самому.
				if (strpos($m[3], 'cache/') === 0)
				{
					$version = max(array_map('filemtime', glob(JPATH_THEMES . '/agentik/js/*.js') ?: [$file]));
				}

				return $m[1] . $m[2] . '?v=' . $version . $m[4];
			},
			$body
		);

		/**
		 * Картинки без подписи и без ленивой загрузки (портфолио на страницах
		 * коллекций, превью в блоге): у них нет alt - для читалок экрана и
		 * поиска это пустое место, а без lazy все 30-60 снимков страницы
		 * грузятся сразу. alt берём из заголовка страницы, а ленивую
		 * загрузку включаем только там, где её ещё нет (lazysizes и
		 * loading уже стоящие не трогаем).
		 */
		$kdcTitle = htmlspecialchars(trim((string) $app->getDocument()->getTitle()), ENT_QUOTES);
		$body     = preg_replace_callback('~<img\b[^>]*>~is', static function ($m) use ($kdcTitle) {
			$tag = $m[0];
			$add = '';

			// Логотип в шапке грузим сразу, а не лениво: он на первом экране,
			// а lazysizes подставлял его последним - шапка секунды стояла без лого.
			if (preg_match('~\sclass\s*=\s*["\'][^"\']*\blogo-image\b~i', $tag) && stripos($tag, 'data-src') !== false)
			{
				$tag = preg_replace('~\sdata-srcset\s*=~i', ' srcset=', $tag);
				$tag = preg_replace('~\sdata-src\s*=~i', ' src=', $tag);
				$tag = preg_replace('~\blazyload\b~i', '', $tag);
				$tag = preg_replace('~\s*/?>$~', ' loading="eager" decoding="async"$0', $tag, 1);
			}

			if (!preg_match('~\salt\s*=~i', $tag) && $kdcTitle !== '')
			{
				$add .= ' alt="' . $kdcTitle . '"';
			}

			if (!preg_match('~\sloading\s*=~i', $tag) && !preg_match('~\sclass\s*=\s*["\'][^"\']*\blazyload\b~i', $tag)
				&& !preg_match('~\sfetchpriority\s*=~i', $tag))
			{
				$add .= ' loading="lazy" decoding="async"';
			}

			// Декодирование картинок - не в основном потоке: большая картинка,
			// выехавшая при прокрутке, иначе даёт короткий рывок кадра.
			if (!preg_match('~\sdecoding\s*=~i', $tag) && strpos($add, 'decoding=') === false)
			{
				$add .= ' decoding="async"';
			}

			return $add === '' ? $tag : preg_replace('~\s*/?>$~', addcslashes($add, '\\$') . '$0', $tag, 1);
		}, $body);

		/**
		 * Страница коллекции с конфигуратором: фото комнаты и скрипт
		 * конфигуратора начинают грузиться сразу вместе со страницей, а не
		 * по цепочке после неё (скрипт подключает custom.js после загрузки
		 * страницы, а фото комнаты - сам скрипт).
		 */
		/**
		 * Раскладка «дверь в интерьере» сразу в HTML, а не после скрипта:
		 * иначе при каждой загрузке секунду была видна старая раскладка
		 * (заголовок на белом, пустое место под фото). Классы те же, что
		 * ставит конфигуратор, - только у коллекций, где он есть (есть модели
		 * в models.json). Старое фото в первом экране не нужно - убираем,
		 * чтобы оно не качалось зря.
		 */
		$kdcRoom = false;

		if (strpos($body, 'kdc-cd-hero') !== false)
		{
			$kdcAlias  = rawurldecode(basename(rtrim((string) parse_url(\Joomla\CMS\Uri\Uri::getInstance()->toString(), PHP_URL_PATH), '/')));
			// Файл своей коллекции (1-5 КБ), а не общий на 100 КБ.
			$kdcModels = json_decode((string) @file_get_contents(JPATH_ROOT . '/images/konfigurator/models/' . $kdcAlias . '.json'), true);
			$kdcRoom   = !empty($kdcModels['collections'][$kdcAlias]['models']);
		}

		if ($kdcRoom)
		{
			$body = preg_replace('~(<div\b[^>]*\bclass="[^"]*\bkdc-cd-hero)(?=[\s"])~', '$1 kdc-room', $body, 1);
			$body = preg_replace('~(<div\b[^>]*\bclass="[^"]*\bkdc-cd-hero-photo\b[^"]*"[^>]*>)\s*<img\b[^>]*>~', '$1', $body, 1);
			$body = $kdcHtmlClass($body, 'kdc-room-page');
		}

		if ($kdcRoom && stripos($body, '</head>') !== false)
		{
			// Версии - из начала файлов, целиком их читать не нужно (128 + 57 КБ на каждый запрос).
			$kdcJs  = (string) @file_get_contents(JPATH_THEMES . '/agentik/js/custom.js', false, null, 0, 16000);
			// Свой интерьер коллекции (room в models.json) или общая комната.
			$kdcRoomCfg = $kdcModels['collections'][$kdcAlias]['room'] ?? null;
			$kdcRoomSrc = '/images/konfigurator/room-classic-wall.webp';
			$kdcRoomCss = '';

			if (is_array($kdcRoomCfg) && !empty($kdcRoomCfg['src']) && !empty($kdcRoomCfg['open']) && !empty($kdcRoomCfg['w']))
			{
				$kw = (float) $kdcRoomCfg['w'];
				$kh = (float) $kdcRoomCfg['h'];
				$kt = (float) ($kdcRoomCfg['top'] ?? 0);
				$kb = (float) ($kdcRoomCfg['bottom'] ?? $kh);
				$kx = ($kdcRoomCfg['open'][0] + $kdcRoomCfg['open'][2]) / 2;
				$kdcRoomSrc = '/images/konfigurator/' . rawurlencode($kdcRoomCfg['src']);
				$kdcRoomCss = '<style>:root{--kdc-room-img:url("' . $kdcRoomSrc . '");--kdc-room-wr:' . round($kw / ($kb - $kt), 6)
					. ';--kdc-room-ar:' . round($kh / $kw, 6) . ';--kdc-room-vh:' . round(($kb - $kt) / $kw, 6)
					. ';--kdc-room-vt:' . round($kt / $kw, 6) . ';--kdc-room-cx:' . round($kx / $kw, 6) . '}</style>';
			}

			$kdcPre = '<link rel="preload" as="image" href="' . $kdcRoomSrc . '" fetchpriority="high">' . $kdcRoomCss;

			if (preg_match('~/templates/agentik/js/kdc-configurator\.js\?v=\d+~', $kdcJs, $kdcSrc))
			{
				// Сжатая копия (tools/build-min.sh) - только если она не старше
				// исходника. custom.js берёт адрес скрипта из этой ссылки.
				$kdcMin = JPATH_THEMES . '/agentik/js/kdc-configurator.min.js';

				if (is_file($kdcMin) && filemtime($kdcMin) >= filemtime(JPATH_THEMES . '/agentik/js/kdc-configurator.js'))
				{
					$kdcSrc[0] = str_replace('.js?', '.min.js?', $kdcSrc[0]);
				}

				$kdcPre .= "\n" . '<link rel="preload" as="script" id="kdc-cfg-js" href="' . $kdcSrc[0] . '">';
			}

			/*
			 * Фото первой модели - по тому же адресу, что запросит конфигуратор
			 * ('/' + src + '?kdc=' + VERSION): дверь не ждёт ни скрипта, ни
			 * списка моделей. Ссылка с #m=N откроет другую модель - тогда
			 * просто лишняя загрузка, ничего не ломается.
			 */
			$kdcCfgJs = (string) @file_get_contents(JPATH_THEMES . '/agentik/js/kdc-configurator.js', false, null, 0, 3000);
			$kdcFirst = $kdcModels['collections'][$kdcAlias]['models'][0] ?? null;

			if ($kdcFirst && !empty($kdcFirst['src']) && preg_match("~var VERSION = '(\\d+)'~", $kdcCfgJs, $kdcVer))
			{
				$kdcImg = '/' . ltrim($kdcFirst['src'], '/');
				$kdcImg = implode('/', array_map('rawurlencode', explode('/', $kdcImg))) . '?kdc=' . $kdcVer[1];
				$kdcPre .= "\n" . '<link rel="preload" as="image" href="' . htmlspecialchars($kdcImg, ENT_QUOTES) . '" fetchpriority="high">';

				// Список моделей коллекции и готовая разметка первой двери
				// (адреса - как у запросов конфигуратора, с ?v=<версия>).
				$kdcBase = '/images/konfigurator/';
				$kdcV    = '?v=' . $kdcVer[1];
				$kdcA    = rawurlencode($kdcAlias);
				$kdcPre .= "\n" . '<link rel="preload" as="fetch" crossorigin="anonymous" href="' . $kdcBase . 'models/' . $kdcA . '.json' . $kdcV . '">';

				if (is_file(JPATH_ROOT . $kdcBase . 'baked/' . $kdcAlias . '/0.json'))
				{
					$kdcPre .= "\n" . '<link rel="preload" as="fetch" crossorigin="anonymous" href="' . $kdcBase . 'baked/' . $kdcA . '/0.json' . $kdcV . '">';
					$kdcPre .= "\n" . '<link rel="preload" as="image" href="' . $kdcBase . 'baked/' . $kdcA . '/0.png' . $kdcV . '">';
				}
			}

			// В самое начало <head> (после meta viewport): браузер начинает
			// грузить эти файлы до стилей и скриптов шаблона, а не после них.
			$body = preg_match('~<meta name="viewport"[^>]*>~i', $body)
				? preg_replace('~<meta name="viewport"[^>]*>~i', '$0' . "\n" . $kdcPre, $body, 1)
				: preg_replace('~<head>~i', '$0' . "\n" . $kdcPre, $body, 1);
		}

		$body = preg_replace_callback('~\x01kdcmeta(\d+)\x01~', static function ($m) use ($metas) {
			return $metas[(int) $m[1]];
		}, $body);

		/* Заменяем только hero главной. Остальные блоки остаются в Page Builder.
		 * Считаем вложенные div, чтобы не обрезать соседнюю секцию регуляркой.
		 * Фон, фирменный логотип и HTML-текст выведены отдельными слоями.
		 */
		if (preg_match('~<div\b[^>]*\bclass="[^"]*\bhero-wrap\b[^"]*"[^>]*>~', $body, $kdcHeroStart, PREG_OFFSET_CAPTURE))
		{
			$kdcHeroOffset = $kdcHeroStart[0][1];
			$kdcHeroDepth = 0;
			preg_match_all('~</?div\b[^>]*>~i', substr($body, $kdcHeroOffset), $kdcHeroTags, PREG_OFFSET_CAPTURE);

			foreach ($kdcHeroTags[0] as $kdcHeroTag)
			{
				$kdcHeroDepth += stripos($kdcHeroTag[0], '</div') === 0 ? -1 : 1;

				if ($kdcHeroDepth === 0)
				{
					$kdcHeroLength = $kdcHeroTag[1] + strlen($kdcHeroTag[0]);
					$body = substr_replace($body, require __DIR__ . '/hero-reference.php', $kdcHeroOffset, $kdcHeroLength);
					$body = $kdcHtmlClass($body, 'kdc-reference-homepage');
					break;
				}
			}
		}

		$app->setBody($body);
	});
}

/**
 * Load the framework bootstrap file for enabling the HelixUltimate\Framework namespacing.
 *
 * @since	2.0.0
 */
$bootstrap_path = JPATH_PLUGINS . '/system/helixultimate/bootstrap.php';

if (file_exists($bootstrap_path))
{
	require_once $bootstrap_path;
}
else
{
	die('Install and activate <a target="_blank" rel="noopener noreferrer" href="https://www.joomshaper.com/helix">Helix Ultimate Framework</a>.');
}

/**
 * Get the theme instance from Helix framework.
 *
 * @var		$theme		The theme object from the class HelixUltimate.
 * @since	1.0.0
 */
$theme = new HelixUltimate;
$template = Helper::loadTemplateData();
$this->params = $template->params;


/** Load needed data for javascript */
Helper::flushSettingsDataToJs();

$requestFromIframe = $app->input->get('helixMode', '') === 'edit';

// Coming Soon
if (!$requestFromIframe) 
{
	$user = Factory::getUser();

	if (!\is_null($this->params->get('comingsoon', null)) && !$user->authorise('core.admin'))
	{
		header("Location: " . Route::_(Uri::root(true) . "/index.php?templateStyle={$template->id}&tmpl=comingsoon", false));
		exit();
	}
}

$scssVars = $theme->getSCSSVariables();

$boxedLayout = $this->params->get('boxed_layout');

$containerMaxWidth = $this->params->get('container_max_width');

// Body Background Image
if ($boxedLayout && $this->params->get('body_bg_image'))
{
	$bg_image = $this->params->get('body_bg_image');
	$body_style = 'background-image: url(' . Uri::base(true) . '/' . $bg_image . ');';
	$body_style .= 'background-repeat: ' . $this->params->get('body_bg_repeat') . ';';
	$body_style .= 'background-size: ' . $this->params->get('body_bg_size') . ';';
	$body_style .= 'background-attachment: ' . $this->params->get('body_bg_attachment') . ';';
	$body_style .= 'background-position: ' . $this->params->get('body_bg_position') . ';';
	$body_style = 'body.site {' . $body_style . '}';
	$this->addStyledeclaration($body_style);
}

// Custom CSS
if ($custom_css = $this->params->get('custom_css'))
{
	$this->addStyledeclaration($custom_css);
}

$progress_bar_position = $this->params->get('reading_timeline_position');

if($app->input->get('view') === 'article' && $this->params->get('reading_time_progress', 0))
{
	$progress_style = 'position:fixed;';
	$progress_style .= 'z-index:9999;';
	$progress_style .= 'height:'.$this->params->get('reading_timeline_height').';';
	$progress_style .= 'background-color:'.$this->params->get('reading_timeline_bg').';';
	$progress_style .= $progress_bar_position == 'top' ? 'top:0;' : 'bottom:0;';
	$progress_style = '.sp-reading-progress-bar { '.$progress_style.' }';
	$this->addStyledeclaration($progress_style);
}

// Custom JS
if ($custom_js = $this->params->get('custom_js', null))
{
	$this->addScriptDeclaration($custom_js);
}
?>

<!doctype html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
	<head>
		<?php echo $theme->googleAnalytics(); ?>

		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<?php

		$theme->head();
		$theme->loadFontAwesome();
		$theme->add_js('main.js');

		/**
		 * Add custom.js for user
		 */
		if (file_exists(JPATH_THEMES . '/' . $template->template . '/js/custom.js'))
		{
			$theme->add_js('custom.js');
		}

		$theme->add_scss('master', $scssVars, 'template');

		if($this->direction === 'rtl')
		{
			$theme->add_scss('rtl', $scssVars, 'rtl');
		}

		$theme->add_scss('presets', $scssVars, 'presets/' . $scssVars['preset']);

		$theme->add_scss('custom', $scssVars, 'custom-compiled');
		$theme->add_css('custom.css');

		//Before Head
		if ($before_head = $this->params->get('before_head'))
		{
			echo $before_head . "\n";
		}
		?>
		<?php if (!empty($containerMaxWidth)) :?>
			<style>.container, .sppb-row-container { max-width: <?php echo $containerMaxWidth . 'px'; ?>; }</style>
		<?php endif; ?>
	</head>
	<body class="<?php echo $theme->bodyClass(); ?>">


		<?php if ($this->params->get('after_body', '')): ?>
			<?php echo $this->params->get('after_body') . "\n"; ?>
		<?php endif ?>

		<?php if($this->params->get('preloader')) : ?>
			<div class="sp-pre-loader">
				<?php echo $theme->getPreloader($this->params->get('loader_type', '')); ?>
			</div>
		<?php endif; ?>

		<div class="body-wrapper">
			<div class="body-innerwrapper">
				<?php echo $theme->getHeaderStyle(); ?>
				<main id="sp-main">
					<?php $theme->render_layout(); ?>
				</main>
			</div>
		</div>

		<!-- Off Canvas Menu -->
		<div class="offcanvas-overlay"></div>
		<!-- Rendering the offcanvas style -->
		<!-- If canvas style selected then render the style -->
		<!-- otherwise (for old templates) attach the offcanvas module position -->
		<?php if (!empty($this->params->get('offcanvas_style', '1-LeftAlign'))): ?>
			<?php echo $theme->getOffcanvasStyle(); ?>
		<?php else : ?>
			<div class="offcanvas-menu">
				<a href="#" class="close-offcanvas" role="button" aria-label="<?php echo Text::_('HELIX_ULTIMATE_CLOSE_OFFCANVAS_ARIA_LABEL'); ?>"><span class="fas fa-times" aria-hidden="true"></span></a>
				<div class="offcanvas-inner">
					<?php if ($this->countModules('offcanvas')) : ?>
						<jdoc:include type="modules" name="offcanvas" style="sp_xhtml" />
					<?php else: ?>
						<p class="alert alert-warning">
							<?php echo Text::_('HELIX_ULTIMATE_NO_MODULE_OFFCANVAS'); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
		

		<?php $theme->after_body(); ?>

		<jdoc:include type="modules" name="debug" style="none" />

		<!-- Go to top -->
		<?php if ($this->params->get('goto_top', 0)) : ?>
			<a href="#" class="sp-scroll-up" aria-label="<?php echo Text::_('HELIX_ULTIMATE_SCROLL_UP_ARIA_LABEL'); ?>"><span class="fas fa-angle-up" aria-hidden="true"></span></a>
		<?php endif; ?>
		<?php if( $app->input->get('view') === 'article' && $this->params->get('reading_time_progress', 0) ): ?>
			<div data-position="<?php echo $progress_bar_position; ?>" class="sp-reading-progress-bar"></div>
		<?php endif; ?>
	</body>
</html>
