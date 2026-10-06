/* =====================================================================
 * КЛЕВЕРДОРС - конфигуратор двери на странице коллекции.
 *
 * Встаёт в первый экран коллекции: справа вместо фото - дверь,
 * слева под описанием - выбор модели, цвета, стекла и
 * наличника. Модели коллекций - images/konfigurator/models.json.
 *
 * Геометрию двери (коробка, полотно, стекло, ручка/петли) скрипт
 * находит на картинке сам, поэтому фото моделей можно менять, не
 * трогая код: дверь по центру на светлом фоне, подписи по углам
 * не мешают.
 * ===================================================================== */
(function () {
	'use strict';

	var BASE = '/images/konfigurator/';
	// Поменяйте после правки models.json - браузеры возьмут свежий список.
	var VERSION = '130';

	// Каталоги цветов грузятся из colors.json (RAL Classic и NCS): [код, название, hex].
	// Пока файл не пришёл - несколько базовых RAL, чтобы конфигуратор не остался без цветов.
	var RAL = [['9016', 'Транспортный белый', '#f7fbf5'], ['7044', 'Серый шёлк', '#bdbdb2'], ['7016', 'Антрацитово-серый', '#373f43']];
	var NCS = [];
	var DEFAULT_RAL = '7044';
	var RAL_GROUPS = [['1', 'Жёлтые и бежевые'], ['2', 'Оранжевые'], ['3', 'Красные'], ['4', 'Фиолетовые'], ['5', 'Синие'], ['6', 'Зелёные'], ['7', 'Серые'], ['8', 'Коричневые'], ['9', 'Белые и чёрные']];
	// Оттенки NCS как в атласе: Y, Y10R ... Y90R, R, R10B ... G90Y (по 40) и нейтральные.
	var NCS_HUES = [['N', 'Нейтральные (серые)']];
	[['Y', 'R', 'Жёлтый'], ['R', 'B', 'Красный'], ['B', 'G', 'Синий'], ['G', 'Y', 'Зелёный']].forEach(function (f) {
		NCS_HUES.push([f[0], f[2] + ' (' + f[0] + ')']);
		for (var n = 10; n <= 90; n += 10) { NCS_HUES.push([f[0] + n + f[1], f[0] + n + f[1]]); }
	});
	var VENEER = [
		['Дуб белёный', '#dccfb9', 'oak'], ['Дуб натуральный', '#c9a171', 'oak'], ['Дуб золотой', '#bb8a50', 'oak'],
		['Дуб серый', '#9c948a', 'oak'], ['Орех', '#7a5a3c', 'walnut'], ['Орех тёмный', '#553e2d', 'walnut']
	];
	var MIRROR = function (a, b, c) {
		return { tint: 'linear-gradient(125deg,' + a + ',' + b + ' 38%,' + c + ' 52%,' + b + ' 70%,' + a + ')', mirror: true, blur: 0 };
	};
	var GLASS = [
		{ title: 'Прозрачные', items: [
			{ id: 'c1', name: 'Прозрачное стекло, триплекс', tint: 'rgba(230,236,234,.10)', blur: 0 },
			{ id: 'c2', name: 'Прозрачное бронзовое стекло, триплекс', tint: 'rgba(130,92,55,.38)', blur: 0 },
			{ id: 'c3', name: 'Прозрачное серое стекло, триплекс', tint: 'rgba(70,70,70,.38)', blur: 0 }] },
		{ title: 'Матовые', items: [
			{ id: 'm1', name: 'Сатинированное белое стекло, триплекс', tint: 'rgba(246,246,243,.68)', blur: 10 },
			{ id: 'm2', name: 'Сатинированное бронзовое стекло, триплекс', tint: 'rgba(196,146,88,.62)', blur: 10 },
			{ id: 'm3', name: 'Сатинированное серое стекло, триплекс', tint: 'rgba(118,116,112,.66)', blur: 10 }] },
		{ title: 'Зеркала', items: [
			assign({ id: 'z1', name: 'Зеркало серебро' }, MIRROR('#c4c8ca', '#e9ebec', '#f8f9f9')),
			assign({ id: 'z2', name: 'Зеркало бронза' }, MIRROR('#8a6c50', '#b39578', '#d0b89f')),
			assign({ id: 'z3', name: 'Зеркало графит' }, MIRROR('#5f6163', '#8d9092', '#b2b5b7'))] }
	];
	var ALL_GLASS = [].concat.apply([], GLASS.map(function (g) { return g.items; }));
	// Фон превью: цвет стены и пол.
	// Шпон в выборе цвета временно скрыт (см. Configurator).
	var HIDE_VENEER = true;
	var WALLS = [
		['Светлая', '#f4f3f0'], ['Бежевая', '#e7dfd2'], ['Серая', '#cfd0cc'], ['Графит', '#56585a']
	];
	var FLOORS = [
		['Светлый ламинат', '#d9c9ae'], ['Тёмный ламинат', '#5c4637']
	];
	var PORTALS = [
		{ id: 'classic', name: 'Классический наличник', s: 1 },
		{ id: 'wide', name: 'Широкий гладкий наличник', s: 1.5 },
		{ id: 'flush', name: 'Компланарный наличник', s: 0.3 }
	];
	var PMAX = 1.5;
	var ROOM = BASE + 'room.jpg';
	// Интерьер вместо нарисованных стены и пола: фото на всю ширину экрана,
	// дверь встаёт на стену. open - место полотна с коробкой в пикселях
	// исходника [лево, верх, право, пол]: на этом фото проёма нет, дверь
	// ставим между большим молдингом и узкой панелью справа в масштабе
	// комнаты (стена ~2.75 м = 715 px, полотно с коробкой ~0.87 x 2.06 м),
	// увеличено на 10% по просьбе владельца.
	// focus - где на экране держать середину двери (доля ширины сцены)
	// на компьютере и на телефоне. null - прежняя сцена.
	// Фото с проёмом: room-classic.webp, open [1016, 126, 1302, 790].
	// top - с какой строки кадра показывать сверху (потолок и карниз
	// отрезаны: 40% свободного места над дверью).
	// bottom - докуда показываем кадр снизу: пола на фото много, нижнюю
	// половину пола (755-941 px) отрезаем. Просвет под полотном в интерьере
	// не заливаем: сквозь него виден паркет (полоса .kdc-cfg-underfloor).
	var INTERIOR = { src: BASE + 'room-classic-wall.webp', w: 1672, h: 941, top: 68, bottom: 812, open: [1088, 169, 1336, 757], focus: [0.7, 0.5] };
	var WOOD = { walnut: BASE + 'wood-walnut.jpg', oak: BASE + 'wood-oak.jpg' };

	function assign(a, b) { for (var k in b) { a[k] = b[k]; } return a; }
	function hex(h) { return [1, 3, 5].map(function (i) { return parseInt(h.slice(i, i + 2), 16); }); }
	function clamp(v, a, b) { return v < a ? a : v > b ? b : v; }
	function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
	function pct(v, t) { return (v / t * 100) + '%'; }
	function median(arr) { if (!arr.length) { return 0; } arr.sort(function (a, b) { return a - b; }); return arr[arr.length >> 1]; }
	function loadImg(src) {
		return new Promise(function (res) {
			var i = new Image();
			i.decoding = 'async';
			i.onload = function () { res(i); };
			i.onerror = function () { res(null); };
			i.src = src;
		});
	}

	/* ------------------------------------------------------------------
	 * Разбор картинки: где дверь, коробка, полотно, стекло и металл.
	 * ------------------------------------------------------------------ */
	function analyse(img, opt) {
		var MAXS = 1100;
		var k = Math.min(1, MAXS / Math.max(img.naturalWidth, img.naturalHeight));
		var W = Math.round(img.naturalWidth * k), H = Math.round(img.naturalHeight * k), KS = k;
		var c = document.createElement('canvas');
		c.width = W; c.height = H;
		var cx = c.getContext('2d', { willReadFrequently: true });
		cx.drawImage(img, 0, 0, W, H);
		var d = cx.getImageData(0, 0, W, H).data;

		// Фон - по углам картинки.
		var bgs = [];
		[[3, 3], [W - 4, 3], [3, H - 4], [W - 4, H - 4]].forEach(function (p) {
			var o = (p[1] * W + p[0]) * 4;
			bgs.push([d[o], d[o + 1], d[o + 2], d[o + 3]]);
		});
		var bg = [0, 1, 2].map(function (q) { return median(bgs.map(function (b) { return b[q]; })); });
		var transparentBg = median(bgs.map(function (b) { return b[3]; })) < 40;
		// Фон - заливкой от краёв картинки: светлое полотно или матовое
		// стекло внутри контура двери фоном не считаются.
		var bgm = new Uint8Array(W * H);
		(function () {
			function near(o) {
				if (d[o + 3] < 40) { return true; }
				if (transparentBg) { return false; }
				return Math.abs(d[o] - bg[0]) + Math.abs(d[o + 1] - bg[1]) + Math.abs(d[o + 2] - bg[2]) < 24;
			}
			var q = [], i;
			for (i = 0; i < W; i++) { q.push(i, (H - 1) * W + i); }
			for (i = 0; i < H; i++) { q.push(i * W, i * W + W - 1); }
			while (q.length) {
				var j = q.pop();
				if (bgm[j] || !near(j * 4)) { continue; }
				bgm[j] = 1;
				var jx = j % W;
				if (jx > 0) { q.push(j - 1); }
				if (jx < W - 1) { q.push(j + 1); }
				if (j >= W) { q.push(j - W); }
				if (j < W * (H - 1)) { q.push(j + W); }
			}
		})();
		function isBg(o) { return bgm[o >> 2] === 1; }

		// Колонки и строки, занятые дверью: самая длинная сплошная полоса.
		function longestRun(occ, thr) {
			var best = [0, -1], s = -1;
			for (var i = 0; i <= occ.length; i++) {
				if (i < occ.length && occ[i] >= thr) { if (s < 0) { s = i; } } else if (s >= 0) {
					if (i - 1 - s > best[1] - best[0]) { best = [s, i - 1]; }
					s = -1;
				}
			}
			return best;
		}
		var colOcc = new Float32Array(W), x, y, o;
		for (x = 0; x < W; x++) {
			var n = 0;
			for (y = 0; y < H; y += 2) { if (!isBg((y * W + x) * 4)) { n++; } }
			colOcc[x] = n / (H / 2);
		}
		var xr = longestRun(colOcc, 0.45);
		var rowOcc = new Float32Array(H);
		for (y = 0; y < H; y++) {
			var m = 0;
			for (x = xr[0]; x <= xr[1]; x += 2) { if (!isBg((y * W + x) * 4)) { m++; } }
			rowOcc[y] = m / ((xr[1] - xr[0] + 1) / 2);
		}
		var yr = longestRun(rowOcc, 0.3);
		var X0 = xr[0], Y0 = yr[0], w = xr[1] - xr[0] + 1, h = yr[1] - yr[0] + 1;
		if (w < 40 || h < 80) { return null; }
		// Оставляем только то, что связано с самой дверью: подписи и
		// цена рядом с дверью - отдельные островки, их убираем в фон.
		(function () {
			var door = new Uint8Array(W * H), q = [], sx = X0 + (w >> 1), sy = Y0 + (h >> 1), j, jx, jy;
			// Старт - ближайший к центру не-фоновый пиксель.
			for (var r0 = 0; r0 < 40 && !q.length; r0++) {
				for (var dy0 = -r0; dy0 <= r0 && !q.length; dy0++) {
					for (var dx0 = -r0; dx0 <= r0; dx0++) {
						var s0 = (sy + dy0) * W + sx + dx0;
						if (!bgm[s0]) { q.push(s0); break; }
					}
				}
			}
			var minX = W, maxX = 0, minY = H, maxY = 0;
			while (q.length) {
				j = q.pop();
				if (door[j] || bgm[j]) { continue; }
				door[j] = 1;
				jx = j % W; jy = (j / W) | 0;
				if (jx < minX) { minX = jx; } if (jx > maxX) { maxX = jx; }
				if (jy < minY) { minY = jy; } if (jy > maxY) { maxY = jy; }
				if (jx > 0) { q.push(j - 1); }
				if (jx < W - 1) { q.push(j + 1); }
				if (jy > 0) { q.push(j - W); }
				if (jy < H - 1) { q.push(j + W); }
			}
			if (maxX - minX < 40 || maxY - minY < 80) { return; }
			for (j = 0; j < W * H; j++) { if (!door[j]) { bgm[j] = 1; } }
			// Ширина - по средней части высоты, где наличники сплошные:
			// базы внизу, капители и карнизы вверху бывают шире коробки.
			var ya2 = minY + Math.round((maxY - minY) * 0.25), yb2 = minY + Math.round((maxY - minY) * 0.75);
			var full = function (xx) {
				var n = 0, t = 0;
				for (var yy = ya2; yy <= yb2; yy += 2) { t++; if (door[yy * W + xx]) { n++; } }
				return n / t >= 0.97;
			};
			var l2 = minX, r2 = maxX;
			while (l2 < maxX && !full(l2)) { l2++; }
			while (r2 > l2 && !full(r2)) { r2--; }
			if (r2 - l2 > (maxX - minX) * 0.6) {
				for (var yy3 = 0; yy3 < H; yy3++) {
					for (var xx3 = minX; xx3 < l2; xx3++) { bgm[yy3 * W + xx3] = 1; }
					for (xx3 = r2 + 1; xx3 <= maxX; xx3++) { bgm[yy3 * W + xx3] = 1; }
				}
				minX = l2; maxX = r2;
			}
			X0 = minX; Y0 = minY; w = maxX - minX + 1; h = maxY - minY + 1;
		})();

		function lum(o) { return d[o] * 0.3 + d[o + 1] * 0.59 + d[o + 2] * 0.11; }

		// Коробка: самая тёмная линия (зазор между наличником и полотном)
		// слева, справа и сверху.
		var ya = Math.round(h * 0.22), yb = Math.round(h * 0.44);
		var colL = new Float32Array(w);
		for (x = 0; x < w; x++) {
			var s = 0, cnt = 0;
			for (y = ya; y < yb; y += 2) { o = ((Y0 + y) * W + X0 + x) * 4; s += lum(o); cnt++; }
			colL[x] = s / cnt;
		}
		function argmin(arr, a, b) {
			var bi = a;
			for (var i = a; i <= b; i++) { if (arr[i] < arr[bi]) { bi = i; } }
			return bi;
		}
		var lx = argmin(colL, Math.round(w * 0.05), Math.round(w * 0.2)), rx = argmin(colL, Math.round(w * 0.8), Math.round(w * 0.95));
		var midL = median(Array.prototype.slice.call(colL, Math.round(w * 0.2), Math.round(w * 0.8)));
		var rowL = new Float32Array(Math.round(h * 0.15));
		for (y = 0; y < rowL.length; y++) {
			var s2 = 0, c2 = 0;
			for (x = Math.round(w * 0.3); x < Math.round(w * 0.7); x += 2) { o = ((Y0 + y) * W + X0 + x) * 4; s2 += lum(o); c2++; }
			rowL[y] = s2 / c2;
		}
		// Верхний наличник обычно той же ширины, что боковые: ищем
		// зазор около этой высоты, а не первую тёмную линию (это может
		// быть рамка филёнки).
		var sideW = (lx + (w - rx)) / 2;
		var ty = argmin(rowL, Math.max(2, Math.round(sideW * 0.55)), Math.min(rowL.length - 1, Math.round(sideW * 1.6)));
		var hasCasing = !(opt.over && opt.over.casing === false) && ty > h * 0.015 &&
			colL[lx] < midL * 0.82 && colL[rx] < midL * 0.82 &&
			Math.abs(lx - (w - rx)) < Math.max(lx, w - rx) * 0.35;
		var cl = hasCasing ? lx + 2 : 0, cr = hasCasing ? rx - 1 : w, ct = hasCasing ? ty + 2 : 0;

		// Основной цвет полотна - медиана по полотну.
		var lums = [], px = [];
		for (y = Math.round(ct + (h - ct) * 0.08); y < h * 0.92; y += 3) {
			for (x = cl + 3; x < cr - 3; x += 3) {
				o = ((Y0 + y) * W + X0 + x) * 4;
				if (!isBg(o) || transparentBg) { lums.push(lum(o)); px.push(o); }
			}
		}
		var baseLum = median(lums.slice()) || 200;
		var acc = [0, 0, 0], an = 0;
		px.forEach(function (p) {
			if (Math.abs(lum(p) - baseLum) < baseLum * 0.04) { acc[0] += d[p]; acc[1] += d[p + 1]; acc[2] += d[p + 2]; an++; }
		});
		var base = an ? acc.map(function (v) { return v / an; }) : [baseLum, baseLum, baseLum];
		var bs = base[0] + base[1] + base[2] || 1;
		var bn = [base[0] / bs, base[1] / bs, base[2] / bs];

		// Выделяем кадр двери; фон - прозрачный.
		var out = document.createElement('canvas');
		out.width = w; out.height = h;
		var ox = out.getContext('2d', { willReadFrequently: true });
		ox.drawImage(c, X0, Y0, w, h, 0, 0, w, h);
		var od = ox.getImageData(0, 0, w, h);
		var dd = od.data;
		// keep: 0..1 - сколько оставить от оригинала (металл, стекло);
		// chroma-mask для поиска стекла.
		var keep = new Float32Array(w * h), cm = new Uint8Array(w * h), gm = new Uint8Array(w * h);
		var metal = opt.detectMetal !== false, mlab = null, mcomp = [];
		for (y = 0; y < h; y++) {
			for (x = 0; x < w; x++) {
				var i = y * w + x, p = i * 4, so = ((Y0 + y) * W + X0 + x) * 4;
				if (isBg(so)) { dd[p + 3] = 0; continue; }
				var sum = dd[p] + dd[p + 1] + dd[p + 2];
				if (sum < 45) { continue; }
				var cd = Math.abs(dd[p] / sum - bn[0]) + Math.abs(dd[p + 1] / sum - bn[1]) + Math.abs(dd[p + 2] / sum - bn[2]);
				if (cd > 0.045) { cm[i] = 1; }
				if (cm[i] || (dd[p] * 0.3 + dd[p + 1] * 0.59 + dd[p + 2] * 0.11) > baseLum * 1.06) { gm[i] = 1; }
				if (metal) { keep[i] = clamp((cd - 0.03) / 0.035, 0, 1); }
			}
		}
		ox.putImageData(od, 0, 0);

		// Фурнитура - компактные детали (ручка, петли). Длинные тонкие
		// полосы «не того» цвета - это тени в канавках и на фасках, их
		// перекрашиваем вместе с полотном.
		// Фурнитура на двери - только ручка и петли; всё остальное (тени,
		// канавки, фаски) перекрашиваем. Петли - у края полотна, ручка -
		// крупная плотная деталь в средней по высоте части двери.
		// Граница маски без ступенек: крайний пиксель в каждой строке
		// (и столбце) - медиана крайних пикселей соседних строк. Середину
		// не трогаем - вырезы (полукруг под ручку) остаются как есть.
		function smoothMask(m, W2, H2) {
			var R2 = 5;
			var pass = function (src, n, len, at) {
				var f = new Int32Array(n), l = new Int32Array(n), i, j, k;
				for (i = 0; i < n; i++) {
					f[i] = -1; l[i] = -1;
					for (j = 0; j < len; j++) { if (src[at(i, j)]) { if (f[i] < 0) { f[i] = j; } l[i] = j; } }
				}
				var med = function (arr, i0) {
					var v = [];
					for (k = Math.max(0, i0 - R2); k <= Math.min(n - 1, i0 + R2); k++) { if (arr[k] >= 0) { v.push(arr[k]); } }
					v.sort(function (p, q) { return p - q; });
					return v[v.length >> 1];
				};
				var o = new Uint8Array(src);
				for (i = 0; i < n; i++) {
					if (f[i] < 0) { continue; }
					var fs = med(f, i), ls = med(l, i);
					for (j = 0; j < len; j++) {
						if (j < fs || j > ls) { o[at(i, j)] = 0; }
						else if (j < f[i] || j > l[i]) { o[at(i, j)] = 1; }
					}
				}
				return o;
			};
			var r = pass(m, H2, W2, function (i, j) { return i * W2 + j; });
			return pass(r, W2, H2, function (i, j) { return j * W2 + i; });
		}
		// Точка (x, y) внутри рамки «не заливать» (с поворотом).
		function inWall(wl, x, y) {
			var dx = x - wl.cx, dy = y - wl.cy;
			return Math.abs(dx * wl.cs + dy * wl.sn) <= wl.hw && Math.abs(-dx * wl.sn + dy * wl.cs) <= wl.hh;
		}
		// «Не трогать» (hold в models.json): участок, где дверь остаётся как
		// на фото, даже если он внутри стекла. Форма - прямоугольник r, овал
		// в рамке r (oval) или треугольник (tri).
		function holdIn(hd, x, y) {
			if (hd.rot) { return inWall(hd.rot, x, y); }
			if (hd.tri) { return inTri(hd.tri, x, y); }
			if (hd.oval) { var ux = (x - hd.cx) / hd.rx, uy = (y - hd.cy) / hd.ry; return ux * ux + uy * uy < 1; }
			return x >= hd.x0 && x < hd.x1 && y >= hd.y0 && y < hd.y1;
		}
		// Доля пикселя (px, py) вне всех участков: у края - по 4x4 точкам,
		// чтобы дуга шла без ступенек.
		function holdCover(hs, px, py) {
			var near = [], k, i, j, n = 0;
			for (k = 0; k < hs.length; k++) {
				var hd = hs[k];
				if (px + 1 > hd.x0 && px < hd.x1 && py + 1 > hd.y0 && py < hd.y1) { near.push(hd); }
			}
			if (!near.length) { return 1; }
			for (j = 0; j < 4; j++) {
				for (i = 0; i < 4; i++) {
					var sx = px + (i + 0.5) / 4, sy = py + (j + 0.5) / 4, out = true;
					for (k = 0; k < near.length && out; k++) { if (holdIn(near[k], sx, sy)) { out = false; } }
					if (out) { n++; }
				}
			}
			return n / 16;
		}
		// Доля пикселя (px, py) внутри треугольника: у края - по 4x4 точкам.
		function triCover(t, px, py) {
			var c = 0, i, j;
			for (i = 0; i < 4; i += 3) { for (j = 0; j < 4; j += 3) { if (inTri(t, px + (i + 0.5) / 4, py + (j + 0.5) / 4)) { c++; } } }
			if (c === 4) { return 1; }
			if (!c && !inTri(t, px + 0.5, py + 0.5)) {
				// Вершина могла попасть внутрь пикселя - тогда считаем честно.
				var hit = t.some(function (v) { return v[0] >= px && v[0] < px + 1 && v[1] >= py && v[1] < py + 1; });
				if (!hit) { return 0; }
			}
			var n = 0;
			for (j = 0; j < 4; j++) { for (i = 0; i < 4; i++) { if (inTri(t, px + (i + 0.5) / 4, py + (j + 0.5) / 4)) { n++; } } }
			return n / 16;
		}
		function inTri(t, px, py) {
			var s1 = (t[1][0] - t[0][0]) * (py - t[0][1]) - (t[1][1] - t[0][1]) * (px - t[0][0]);
			var s2 = (t[2][0] - t[1][0]) * (py - t[1][1]) - (t[2][1] - t[1][1]) * (px - t[1][0]);
			var s3 = (t[0][0] - t[2][0]) * (py - t[2][1]) - (t[0][1] - t[2][1]) * (px - t[2][0]);
			return (s1 >= 0 && s2 >= 0 && s3 >= 0) || (s1 <= 0 && s2 <= 0 && s3 <= 0);
		}
		function fitting(x0, x1, y0, y1, n, fl) {
			var lw = cr - cl, edge = Math.max(6, lw * 0.08);
			if (x0 >= cr - edge || x1 <= cl + edge) { return true; }
			var bw9 = x1 - x0 + 1, bh9 = y1 - y0 + 1, cy9 = (y0 + y1) / 2;
			return n >= 200 && fl >= 0.25 && Math.max(bw9, bh9) / Math.min(bw9, bh9) <= 5 && cy9 > h * 0.3 && cy9 < h * 0.75;
		}
		if (metal) {
			mlab = new Int32Array(w * h);
			var mst = new Int32Array(w * h), mOK = new Uint8Array(w * h), mn = 0;
			for (var mi = 0; mi < w * h; mi++) {
				if (!cm[mi] || mlab[mi]) { continue; }
				mn++;
				var msp = 0, mpix = [], mx0 = w, mx1 = 0, my0 = h, my1 = 0;
				mst[msp++] = mi; mlab[mi] = mn;
				while (msp) {
					var mq = mst[--msp], mqx = mq % w, mqy = (mq / w) | 0;
					mpix.push(mq);
					if (mqx < mx0) { mx0 = mqx; } if (mqx > mx1) { mx1 = mqx; }
					if (mqy < my0) { my0 = mqy; } if (mqy > my1) { my1 = mqy; }
					if (mqx > 0 && cm[mq - 1] && !mlab[mq - 1]) { mlab[mq - 1] = mn; mst[msp++] = mq - 1; }
					if (mqx < w - 1 && cm[mq + 1] && !mlab[mq + 1]) { mlab[mq + 1] = mn; mst[msp++] = mq + 1; }
					if (mqy > 0 && cm[mq - w] && !mlab[mq - w]) { mlab[mq - w] = mn; mst[msp++] = mq - w; }
					if (mqy < h - 1 && cm[mq + w] && !mlab[mq + w]) { mlab[mq + w] = mn; mst[msp++] = mq + w; }
				}
				var mbw = mx1 - mx0 + 1, mbh = my1 - my0 + 1, asp = Math.max(mbw, mbh) / Math.min(mbw, mbh);
				// Крупные области решает поиск стекла ниже.
				if (mpix.length < 30) { continue; }
				// Деталь плотная (ручка, петля) - оставляем целиком.
				var fill = mpix.length / (mbw * mbh);
				if (asp <= 12 && fill >= 0.12 && fitting(mx0, mx1, my0, my1, mpix.length, fill)) {
					for (var mk = 0; mk < mpix.length; mk++) { mOK[mpix[mk]] = 1; }
					mcomp.push({ id: mn, x0: mx0, x1: mx1, y0: my0, y1: my1 });
					continue;
				}
				// Сеть из линий и теней (контур филёнки, притвор, тень под
				// раскладкой) с деталями на ней: оставляем только толстые
				// компактные куски - петли, ручку; тонкие линии и длинные
				// полосы теней уходят в перекраску.
				var thick = new Uint8Array(mbw * mbh), tq, tx, ty, ddx, ddy, tn;
				for (mk = 0; mk < mpix.length; mk++) {
					tx = mpix[mk] % w; ty = (mpix[mk] / w) | 0; tn = 0;
					for (ddy = -2; ddy <= 2; ddy++) { for (ddx = -2; ddx <= 2; ddx++) {
						var ux = tx + ddx, uy = ty + ddy;
						if (ux >= 0 && uy >= 0 && ux < w && uy < h && mlab[uy * w + ux] === mn) { tn++; }
					} }
					if (tn >= 16) { thick[(ty - my0) * mbw + tx - mx0] = 1; }
				}
				for (var ti = 0; ti < mbw * mbh; ti++) {
					if (thick[ti] !== 1) { continue; }
					var tst = [ti], tp = [], ax0 = mbw, ax1 = 0, ay0 = mbh, ay1 = 0;
					thick[ti] = 2;
					while (tst.length) {
						tq = tst.pop(); tp.push(tq); tx = tq % mbw; ty = (tq / mbw) | 0;
						if (tx < ax0) { ax0 = tx; } if (tx > ax1) { ax1 = tx; } if (ty < ay0) { ay0 = ty; } if (ty > ay1) { ay1 = ty; }
						if (tx > 0 && thick[tq - 1] === 1) { thick[tq - 1] = 2; tst.push(tq - 1); }
						if (tx < mbw - 1 && thick[tq + 1] === 1) { thick[tq + 1] = 2; tst.push(tq + 1); }
						if (ty > 0 && thick[tq - mbw] === 1) { thick[tq - mbw] = 2; tst.push(tq - mbw); }
						if (ty < mbh - 1 && thick[tq + mbw] === 1) { thick[tq + mbw] = 2; tst.push(tq + mbw); }
					}
					var aw = ax1 - ax0 + 1, ah = ay1 - ay0 + 1;
					if (tp.length < 20 || Math.max(aw, ah) / Math.min(aw, ah) > 8 || tp.length / (aw * ah) < 0.3) { continue; }
					if (!fitting(mx0 + ax0, mx0 + ax1, my0 + ay0, my0 + ay1, tp.length, tp.length / (aw * ah))) { continue; }
					// Кусок и его сглаженный край (соседние пиксели сети).
					for (var tk = 0; tk < tp.length; tk++) {
						tx = tp[tk] % mbw + mx0; ty = ((tp[tk] / mbw) | 0) + my0;
						for (ddy = -2; ddy <= 2; ddy++) { for (ddx = -2; ddx <= 2; ddx++) {
							var vx = tx + ddx, vy = ty + ddy;
							if (vx >= 0 && vy >= 0 && vx < w && vy < h && mlab[vy * w + vx] === mn) { mOK[vy * w + vx] = 1; }
						} }
					}
				}
			}
			// Сглаженные края деталей (2px) оставляем, остальное - в краску.
			for (var my = 0; my < h; my++) {
				for (var mxx = 0; mxx < w; mxx++) {
					var mix = my * w + mxx;
					if (!keep[mix] || mOK[mix]) { continue; }
					var nearOK = false;
					for (var dy5 = -2; dy5 <= 2 && !nearOK; dy5++) {
						for (var dx5 = -2; dx5 <= 2; dx5++) {
							var yy5 = my + dy5, xx5 = mxx + dx5;
							if (yy5 >= 0 && xx5 >= 0 && yy5 < h && xx5 < w && mOK[yy5 * w + xx5]) { nearOK = true; break; }
						}
					}
					if (!nearOK) { keep[mix] = 0; }
				}
			}
		}

		// Стекло: области «не того» цвета внутри полотна. Блики на стекле
		// по цвету похожи на полотно и рвут его на куски, поэтому соседние
		// куски объединяем, а стекло узнаём по контуру: край проёма почти
		// целиком «стеклянный». Прямоугольное стекло заменяем своим,
		// фигурное (витраж) оставляем как на фото.
		var leafArea = (cr - cl) * (h - ct), lab = new Int32Array(w * h), glass = [], keepGlass = false;
		var stack = new Int32Array(w * h), nl = 0, comps = [];
		for (y = ct; y < h; y++) {
			for (x = cl; x < cr; x++) {
				var st = y * w + x;
				if (!cm[st] || lab[st]) { continue; }
				nl++;
				var sp = 0, area = 0, bx0 = x, bx1 = x, by0 = y, by1 = y;
				stack[sp++] = st; lab[st] = nl;
				while (sp) {
					var q = stack[--sp], qx = q % w, qy = (q / w) | 0;
					area++;
					if (qx < bx0) { bx0 = qx; } if (qx > bx1) { bx1 = qx; }
					if (qy < by0) { by0 = qy; } if (qy > by1) { by1 = qy; }
					var nb = [q - 1, q + 1, q - w, q + w];
					for (var t = 0; t < 4; t++) {
						var r = nb[t];
						if (r < 0 || r >= w * h || lab[r] || !cm[r]) { continue; }
						if ((t === 0 && qx === 0) || (t === 1 && qx === w - 1)) { continue; }
						if (r % w < cl || r % w >= cr || r / w < ct) { continue; }
						lab[r] = nl; stack[sp++] = r;
					}
				}
				if (area >= 25) { comps.push({ b: [bx0, by0, bx1, by1], a: area, ls: [nl] }); }
			}
		}
		// Объединяем куски, чьи рамки касаются (с запасом 4px).
		var merged = true, G = 4;
		while (merged) {
			merged = false;
			for (var ci = 0; ci < comps.length && !merged; ci++) {
				if (comps[ci].a < leafArea * 0.005) { continue; }
				for (var cj = ci + 1; cj < comps.length; cj++) {
					if (comps[cj].a < leafArea * 0.005) { continue; }
					var P = comps[ci].b, Q = comps[cj].b;
					if (P[0] - G <= Q[2] && Q[0] - G <= P[2] && P[1] - G <= Q[3] && Q[1] - G <= P[3]) {
						comps[ci] = { b: [Math.min(P[0], Q[0]), Math.min(P[1], Q[1]), Math.max(P[2], Q[2]), Math.max(P[3], Q[3])], a: comps[ci].a + comps[cj].a, ls: comps[ci].ls.concat(comps[cj].ls) };
						comps.splice(cj, 1);
						merged = true;
						break;
					}
				}
			}
		}
		function near(px, py) {
			for (var dy = -2; dy <= 2; dy++) {
				for (var dx = -2; dx <= 2; dx++) {
					var qx2 = px + dx, qy2 = py + dy;
					if (qx2 >= 0 && qy2 >= 0 && qx2 < w && qy2 < h && gm[qy2 * w + qx2]) { return 1; }
				}
			}
			return 0;
		}
		var mode = opt.glass || 'auto';
		var lineRatio = function (x0, y0, x1, y1) {
			var n = 0, t2 = 0;
			for (var yy2 = y0; yy2 <= y1; yy2++) { for (var xx2 = x0; xx2 <= x1; xx2++) { n += gm[yy2 * w + xx2]; t2++; } }
			return n / t2;
		};
		var rowLum = function (x0, x1, yy2) {
			var s3 = 0;
			for (var xx2 = x0; xx2 <= x1; xx2++) { var o3 = (yy2 * w + xx2) * 4; s3 += dd[o3] * 0.3 + dd[o3 + 1] * 0.59 + dd[o3 + 2] * 0.11; }
			return s3 / (x1 - x0 + 1);
		};
		var cands = [];
		comps.forEach(function (cp) {
			var b = cp.b.slice();
			if ((b[2] - b[0] + 1) * (b[3] - b[1] + 1) < leafArea * 0.012 || b[2] - b[0] < 12 || b[3] - b[1] < 12) { return; }
			// 1) Дотягиваем рамку через блики.
			var grow = true, steps = 0;
			while (grow && steps++ < 400) {
				grow = false;
				if (b[1] - 1 > ct && lineRatio(b[0], b[1] - 1, b[2], b[1] - 1) >= 0.6) { b[1]--; grow = true; }
				if (b[3] + 1 < h && lineRatio(b[0], b[3] + 1, b[2], b[3] + 1) >= 0.6) { b[3]++; grow = true; }
				if (b[0] - 1 > cl && lineRatio(b[0] - 1, b[1], b[0] - 1, b[3]) >= 0.6) { b[0]--; grow = true; }
				if (b[2] + 1 < cr && lineRatio(b[2] + 1, b[1], b[2] + 1, b[3]) >= 0.6) { b[2]++; grow = true; }
			}
			// 2) Светлый край стекла по цвету как полотно - идём до тёмной
			// линии проёма (не дальше четверти размера стекла).
			var lim = Math.round((b[3] - b[1]) * 0.25), dark = baseLum * 0.8, k2;
			// Не нашли тёмную линию в пределах - откатываем.
			for (k2 = 0; k2 < lim && b[1] - 1 > ct && rowLum(b[0], b[2], b[1] - 1) > dark; k2++) { b[1]--; }
			if (k2 >= lim) { b[1] += k2; }
			for (k2 = 0; k2 < lim && b[3] + 1 < h - 1 && rowLum(b[0], b[2], b[3] + 1) > dark; k2++) { b[3]++; }
			if (k2 >= lim) { b[3] -= k2; }
			cands.push({ b: b, ls: cp.ls });
		});
		// 3) Куски одного стекла друг над другом (зазор - планка
		// раскладки) объединяем.
		var mg = true;
		while (mg) {
			mg = false;
			for (var a1 = 0; a1 < cands.length && !mg; a1++) {
				for (var a2 = 0; a2 < cands.length; a2++) {
					if (a1 === a2) { continue; }
					var P2 = cands[a1].b, Q2 = cands[a2].b;
					var sameX = Math.abs(P2[0] - Q2[0]) <= 6 && Math.abs(P2[2] - Q2[2]) <= 6;
					var gap = Q2[1] - P2[3];
					var overlap = P2[0] <= Q2[2] && Q2[0] <= P2[2] && P2[1] <= Q2[3] && Q2[1] <= P2[3];
					// Между кусками одна тёмная линия - планка раскладки;
					// две и больше - окрашенная перемычка с рамками.
					var lines = 0;
					if (sameX && gap >= -2 && gap < h * 0.3) {
						var inDark = false;
						for (var gy = P2[3] + 1; gy < Q2[1]; gy++) {
							var dk = rowLum(Math.max(P2[0], Q2[0]), Math.min(P2[2], Q2[2]), gy) < baseLum * 0.8;
							if (dk && !inDark) { lines++; }
							inDark = dk;
						}
					}
					if ((sameX && gap >= -2 && gap < h * 0.3 && lines <= 1) || overlap) {
						cands[a1] = { b: [Math.min(P2[0], Q2[0]), Math.min(P2[1], Q2[1]), Math.max(P2[2], Q2[2]), Math.max(P2[3], Q2[3])], ls: cands[a1].ls.concat(cands[a2].ls) };
						cands.splice(a2, 1);
						mg = true;
						break;
					}
				}
			}
		}
		// Точный край стекла. У рамки бывает и светлая фаска, и тёмная
		// тень, поэтому ищем не тёмную линию, а самый резкий перепад,
		// у которого с внутренней стороны - то же, что в стекле у этого
		// края. Сравниваем яркость и оттенок: светлое стекло и светлая
		// фаска по яркости одинаковы, но стекло тёплое, фаска серая.
		// [x0, y0, x1, y1) -> уточнённый; Rout - наружу, Rin - внутрь.
		function refine(x0, y0, x1, y1, Rout, Rin) {
			if (Rin === undefined) { Rin = Rout; }
			var gw2 = x1 - x0, gh2 = y1 - y0;
			if (gw2 < 12 || gh2 < 12) { return [x0, y0, x1, y1]; }
			var rowP = {}, colP = {};
			var ra = x0 + Math.round(gw2 * 0.15), rb = x1 - Math.round(gw2 * 0.15);
			var ca = y0 + Math.round(gh2 * 0.15), cb = y1 - Math.round(gh2 * 0.15);
			var acc = function (xs, xe, ys, ye) {
				var sl = 0, sc2 = 0, n = 0;
				for (var yy = ys; yy < ye; yy++) {
					for (var xx = xs; xx < xe; xx++) {
						var o = (yy * w + xx) * 4;
						sl += dd[o] * 0.3 + dd[o + 1] * 0.59 + dd[o + 2] * 0.11; sc2 += dd[o] - dd[o + 2]; n++;
					}
				}
				return n ? [sl / n, sc2 / n] : null;
			};
			var row = function (yy) {
				if (yy < 0 || yy >= h) { return null; }
				if (rowP[yy] === undefined) { rowP[yy] = acc(ra, rb, yy, yy + 1); }
				return rowP[yy];
			};
			var col = function (xx) {
				if (xx < 0 || xx >= w) { return null; }
				if (colP[xx] === undefined) { colP[xx] = acc(xx, xx + 1, ca, cb); }
				return colP[xx];
			};
			// Образец стекла - глубже фаски (18..30px от края).
			var local = function (fn, from, dir, span) {
				var L = [], C = [], dmax = Math.max(4, Math.floor(span / 3));
				for (var q = Math.min(18, dmax - 4); q < Math.min(30, dmax); q++) {
					var t6 = fn(from + q * dir);
					if (t6) { L.push(t6[0]); C.push(t6[1]); }
				}
				if (!L.length) { var f6 = fn(from + dir) || [0, 0]; return f6; }
				return [median(L), median(C)];
			};
			var mean3 = function (fn, a3, dir) {
				var l6 = 0, c6 = 0, n6 = 0;
				for (var q = 0; q < 3; q++) { var v = fn(a3 + q * dir); if (v) { l6 += v[0]; c6 += v[1]; n6++; } }
				return n6 ? [l6 / n6, c6 / n6] : null;
			};
			var edge = function (fn, approx, mu, isStart) {
				var tolL = Math.max(25, mu[0] * 0.14), best = approx, bs = 10;
				var lo = isStart ? approx - Rout : approx - Rin, hi = isStart ? approx + Rin : approx + Rout;
				for (var c = lo; c <= hi; c++) {
					var inn = isStart ? mean3(fn, c, 1) : mean3(fn, c - 1, -1);
					// Снаружи - одна соседняя линия: тонкая тень в 1px
					// при усреднении пропала бы.
					var out = isStart ? fn(c - 1) : fn(c);
					if (!inn || !out || Math.abs(inn[0] - mu[0]) > tolL || Math.abs(inn[1] - mu[1]) > 12) { continue; }
					var sc = Math.abs(out[0] - inn[0]) + 2 * Math.abs(out[1] - inn[1]) - Math.abs(c - approx) * 0.3;
					if (sc > bs) { bs = sc; best = c; }
				}
				return best;
			};
			return [
				edge(col, x0, local(col, x0, 1, gw2), true), edge(row, y0, local(row, y0, 1, gh2), true),
				edge(col, x1, local(col, x1 - 1, -1, gw2), false), edge(row, y1, local(row, y1 - 1, -1, gh2), false)
			];
		}

		// Стекло целиком: заливка изнутри по плавным переходам. Внутри
		// стекла (и в бликах) цвет меняется плавно, на границе с фаской
		// рамки - скачком; охват заливки и есть точный край. Заливка
		// ограничена зоной вокруг примерного прямоугольника, чтобы не
		// утечь в рамку. Сторону, упёршуюся в границу зоны, берём по
		// перепаду (refine).
		function paneEdges(x0, y0, x1, y1, seed) {
			var gw3 = x1 - x0, gh3 = y1 - y0;
			if (gw3 < 16 || gh3 < 16) { return refine(x0, y0, x1, y1, 8); }
			var mX = Math.max(12, Math.round(gw3 * 0.45)), mY = Math.max(12, Math.round(gh3 * 0.45));
			var sx0 = Math.max(0, x0 - mX), sy0 = Math.max(0, y0 - mY), sx1 = Math.min(w, x1 + mX), sy1 = Math.min(h, y1 + mY);
			var SW = sx1 - sx0, SH = sy1 - sy0;
			var L = new Float32Array(SW * SH), R0 = new Float32Array(SW * SH), B0 = new Float32Array(SW * SH), yy, xx, o, i5;
			for (yy = 0; yy < SH; yy++) {
				for (xx = 0; xx < SW; xx++) {
					o = ((sy0 + yy) * w + sx0 + xx) * 4; i5 = yy * SW + xx;
					L[i5] = dd[o] * 0.3 + dd[o + 1] * 0.59 + dd[o + 2] * 0.11; R0[i5] = dd[o]; B0[i5] = dd[o + 2];
				}
			}
			// Порог плавности - по середине стекла.
			var cx0 = x0 - sx0 + Math.round(gw3 * 0.3), cx1 = x1 - sx0 - Math.round(gw3 * 0.3);
			var cy0 = y0 - sy0 + Math.round(gh3 * 0.3), cy1 = y1 - sy0 - Math.round(gh3 * 0.3);
			var diffs = [];
			for (yy = cy0; yy < cy1; yy += 2) { for (xx = cx0; xx < cx1 - 1; xx += 2) { diffs.push(Math.abs(L[yy * SW + xx] - L[yy * SW + xx + 1])); } }
			var T = clamp((diffs.length ? diffs.sort(function (a, b) { return a - b; })[Math.floor(diffs.length * 0.95)] : 4) * 1.6, 5, 12);
			var seen = new Uint8Array(SW * SH), q = [];
			var ix0 = x0 - sx0 + Math.round(gw3 * 0.15), ix1 = x1 - sx0 - Math.round(gw3 * 0.15);
			var iy0 = y0 - sy0 + Math.round(gh3 * 0.15), iy1 = y1 - sy0 - Math.round(gh3 * 0.15);
			// Стекло не прямоугольное (вырез под ручку) - заливку начинаем
			// с указанного участка чистого стекла.
			if (seed) { ix0 = seed[0] - sx0; iy0 = seed[1] - sy0; ix1 = seed[2] - sx0; iy1 = seed[3] - sy0; }
			for (yy = iy0; yy < iy1; yy++) { for (xx = ix0; xx < ix1; xx++) { i5 = yy * SW + xx; seen[i5] = 1; q.push(i5); } }
			var smooth = function (a5, b5) {
				return Math.abs(L[a5] - L[b5]) <= T && Math.abs(R0[a5] - R0[b5]) <= T * 1.3 && Math.abs(B0[a5] - B0[b5]) <= T * 1.3;
			};
			while (q.length) {
				var c5 = q.pop(), cx5 = c5 % SW, cy5 = (c5 / SW) | 0;
				if (cx5 > 0 && !seen[c5 - 1] && smooth(c5, c5 - 1)) { seen[c5 - 1] = 1; q.push(c5 - 1); }
				if (cx5 < SW - 1 && !seen[c5 + 1] && smooth(c5, c5 + 1)) { seen[c5 + 1] = 1; q.push(c5 + 1); }
				if (cy5 > 0 && !seen[c5 - SW] && smooth(c5, c5 - SW)) { seen[c5 - SW] = 1; q.push(c5 - SW); }
				if (cy5 < SH - 1 && !seen[c5 + SW] && smooth(c5, c5 + SW)) { seen[c5 + SW] = 1; q.push(c5 + SW); }
			}
			// Край - где заполнено не меньше половины ряда/колонки
			// (тонкие затёки по планкам не в счёт).
			var colN = new Int32Array(SW), rowN = new Int32Array(SH), maxC = 0, maxR = 0;
			for (yy = 0; yy < SH; yy++) { for (xx = 0; xx < SW; xx++) { if (seen[yy * SW + xx]) { colN[xx]++; rowN[yy]++; } } }
			for (xx = 0; xx < SW; xx++) { if (colN[xx] > maxC) { maxC = colN[xx]; } }
			for (yy = 0; yy < SH; yy++) { if (rowN[yy] > maxR) { maxR = rowN[yy]; } }
			var e0 = 0, e1 = SW - 1, f0 = 0, f1 = SH - 1;
			while (e0 < SW && colN[e0] < maxC * 0.5) { e0++; }
			while (e1 > e0 && colN[e1] < maxC * 0.5) { e1--; }
			while (f0 < SH && rowN[f0] < maxR * 0.5) { f0++; }
			while (f1 > f0 && rowN[f1] < maxR * 0.5) { f1--; }
			var st5 = refine(x0, y0, x1, y1, 8);
			var hits = (e0 <= 0) + (f0 <= 0) + (e1 >= SW - 1) + (f1 >= SH - 1);
			var fl = [
				e0 <= 0 ? st5[0] : sx0 + e0, f0 <= 0 ? st5[1] : sy0 + f0,
				e1 >= SW - 1 ? st5[2] : sx0 + e1 + 1, f1 >= SH - 1 ? st5[3] : sy0 + f1 + 1
			];
			// Заливка утекла в рамку (стекло почти не отличается от неё) -
			// берём прямоугольник по перепаду на рамке, без маски.
			if (hits >= 2 || fl[2] - fl[0] > gw3 * 1.3 + 8 || fl[3] - fl[1] > gh3 * 1.3 + 8) {
				return refine(x0, y0, x1, y1, 12, 12);
			}
			// Заливка могла перетечь на светлую фаску - дожимаем внутрь
			// по перепаду цвета (наружу - не больше 3px).
			var res = refine(fl[0], fl[1], fl[2], fl[3], 3, 16);
			// Маска стекла - сама заливка: так повторяется точная форма
			// (вырез под ручку, раскладка, скруглённые углы).
			res.mk = { x0: sx0, y0: sy0, W: SW, H: SH, d: seen };
			return res;
		}

		// Стекло, отмеченное в models.json вручную (доли кадра двери):
		// края уточняем по рамке.
		var ov = opt.over && opt.over.glass;
		// Участки «не трогать» - не стёкла: откладываем отдельно. Если в
		// разметке только они - стекло ищет скрипт.
		var holds = (ov || []).filter(function (f0) { return f0 && f0.hold; }).map(function (f0) {
			var x0 = f0.r[0] * w, y0 = f0.r[1] * h, x1 = (f0.r[0] + f0.r[2]) * w, y1 = (f0.r[1] + f0.r[3]) * h;
			var hd = { x0: x0, y0: y0, x1: x1, y1: y1 };
			if (f0.oval) { hd.oval = true; hd.cx = (x0 + x1) / 2; hd.cy = (y0 + y1) / 2; hd.rx = (x1 - x0) / 2; hd.ry = (y1 - y0) / 2; }
			if (f0.tri && f0.tri.length === 3) { hd.tri = f0.tri.map(function (v) { return [v[0] * w, v[1] * h]; }); }
			return hd;
		});
		// Рамки «не заливать» ({"r": [...], "wall": true, "rot": градусы}):
		// повёрнутый прямоугольник, которым обводят то, что на фото должно
		// остаться дверью (планки креста). Стекла там нет - это те же участки
		// «не трогать», только с поворотом.
		var walls = (ov || []).filter(function (f0) { return f0 && f0.wall && f0.r; }).map(function (f0) {
			var wl = { cx: (f0.r[0] + f0.r[2] / 2) * w, cy: (f0.r[1] + f0.r[3] / 2) * h, hw: f0.r[2] * w / 2, hh: f0.r[3] * h / 2, a: (f0.rot || 0) * Math.PI / 180 };
			wl.cs = Math.cos(wl.a); wl.sn = Math.sin(wl.a);
			var ex = Math.abs(wl.hw * wl.cs) + Math.abs(wl.hh * wl.sn), ey = Math.abs(wl.hw * wl.sn) + Math.abs(wl.hh * wl.cs);
			// Для редактора - углы (рисует контур).
			var poly = [[-1, -1], [1, -1], [1, 1], [-1, 1]].map(function (sg) {
				return [wl.cx + sg[0] * wl.hw * wl.cs - sg[1] * wl.hh * wl.sn, wl.cy + sg[0] * wl.hw * wl.sn + sg[1] * wl.hh * wl.cs];
			});
			return { x0: wl.cx - ex, y0: wl.cy - ey, x1: wl.cx + ex, y1: wl.cy + ey, rot: wl, poly: poly };
		});
		holds = holds.concat(walls);
		if (holds.length || walls.length) {
			ov = ov.filter(function (f0) { return !f0.hold && !f0.wall; });
			if (!ov.length) { ov = null; }
		}
		if (!ov && opt.glass === 'none') { ov = []; }
		// Есть такое же фото глухой двери (blank): стекло - ровно то, чем
		// кадры отличаются. Форма любая (полукруг, вырез под ручку), край -
		// до пикселя, без подгонки.
		if (opt.blank) {
			var bc = document.createElement('canvas');
			bc.width = w; bc.height = h;
			var bx = bc.getContext('2d', { willReadFrequently: true });
			bx.drawImage(opt.blank, 0, 0, opt.blank.naturalWidth, opt.blank.naturalHeight, -X0, -Y0, W, H);
			var bd = bx.getImageData(0, 0, w, h).data, src0 = c.getContext('2d').getImageData(X0, Y0, w, h).data;
			var dm = new Uint8Array(w * h), dm2 = new Uint8Array(w * h), i9, x9, y9, n9;
			for (y9 = ct; y9 < h; y9++) {
				for (x9 = cl; x9 < cr; x9++) {
					i9 = y9 * w + x9;
					var o9 = i9 * 4;
					var df9 = Math.abs(src0[o9] - bd[o9]) + Math.abs(src0[o9 + 1] - bd[o9 + 1]) + Math.abs(src0[o9 + 2] - bd[o9 + 2]);
					if (df9 > 12) { dm[i9] = 1; } else if (df9 > 6) { dm[i9] = 2; }
				}
			}
			// Слабая разница (край стекла, сглаживание) - стекло, только
			// если вплотную к уверенной; иначе это отсвет на штапике.
			for (var pass = 0; pass < 1; pass++) {
				for (y9 = ct + 1; y9 < h - 1; y9++) {
					for (x9 = cl + 1; x9 < cr - 1; x9++) {
						i9 = y9 * w + x9;
						if (dm[i9] === 2 && (dm[i9 - 1] === 1 || dm[i9 + 1] === 1 || dm[i9 - w] === 1 || dm[i9 + w] === 1)) { dm[i9] = 3; }
					}
				}
				for (i9 = 0; i9 < w * h; i9++) { if (dm[i9] === 3) { dm[i9] = 1; } }
			}
			for (i9 = 0; i9 < w * h; i9++) { if (dm[i9] === 2) { dm[i9] = 0; } }
			// Шум JPEG - одиночные точки: пиксель стекла, если вокруг
			// (3x3) их большинство; затем дыры внутри стекла заливаем.
			for (y9 = ct + 1; y9 < h - 1; y9++) {
				for (x9 = cl + 1; x9 < cr - 1; x9++) {
					i9 = y9 * w + x9;
					n9 = dm[i9 - w - 1] + dm[i9 - w] + dm[i9 - w + 1] + dm[i9 - 1] + dm[i9] + dm[i9 + 1] + dm[i9 + w - 1] + dm[i9 + w] + dm[i9 + w + 1];
					dm2[i9] = n9 >= 5 ? 1 : 0;
				}
			}
			// Блик на стекле местами совпадает с филёнкой глухой двери -
			// на краю остаются зазубрины. Закрываем щели до 3px:
			// расширяем маску и сжимаем обратно.
			var morph = function (m, grow) {
				var t = new Uint8Array(w * h), o2 = new Uint8Array(w * h), R = 3, xx, yy, k2, v2;
				for (yy = 0; yy < h; yy++) { for (xx = 0; xx < w; xx++) {
					v2 = grow ? 0 : 1;
					for (k2 = -R; k2 <= R; k2++) { var x2 = xx + k2; var m2 = x2 < 0 || x2 >= w ? (grow ? 0 : 1) : m[yy * w + x2]; if (grow ? m2 : !m2) { v2 = grow ? 1 : 0; break; } }
					t[yy * w + xx] = v2;
				} }
				for (yy = 0; yy < h; yy++) { for (xx = 0; xx < w; xx++) {
					v2 = grow ? 0 : 1;
					for (k2 = -R; k2 <= R; k2++) { var y2 = yy + k2; var m3 = y2 < 0 || y2 >= h ? (grow ? 0 : 1) : t[y2 * w + xx]; if (grow ? m3 : !m3) { v2 = grow ? 1 : 0; break; } }
					o2[yy * w + xx] = v2;
				} }
				return o2;
			};
			dm2 = morph(morph(dm2, true), false);
			var crisp = false;
			// Чёткий край: разница кадров дрожит на кромке (отсвет стекла на
			// штапике). У глухой двери на месте стекла - гладкая филёнка,
			// обведённая той же канавкой, что и стекло. Заливаем филёнку
			// глухого фото изнутри маски до канавки - край идёт ровно по
			// линии на фото. Не вышло (филёнка не гладкая) - остаётся маска.
			(function () {
				var Lb = new Float32Array(w * h), ii, er = morph(dm2, false), zone = morph(dm2, true);
				er = morph(er, false);
				for (ii = 0; ii < w * h; ii++) { var ob = ii * 4; Lb[ii] = bd[ob] * 0.3 + bd[ob + 1] * 0.59 + bd[ob + 2] * 0.11; }
				zone = morph(zone, true);
				var vals = [], seeds = [];
				for (ii = 0; ii < w * h; ii++) { if (er[ii]) { seeds.push(ii); if (!(ii % 7)) { vals.push(Lb[ii]); } } }
				if (!seeds.length) { return; }
				vals.sort(function (p, q) { return p - q; });
				var fl = vals[vals.length >> 1], seen = new Uint8Array(w * h), st = seeds.slice(), n0 = 0, n1 = 0;
				seeds.forEach(function (q) { seen[q] = 1; });
				var okp = function (a2, b2) { return zone[b2] && !seen[b2] && Math.abs(Lb[b2] - Lb[a2]) <= 5 && Lb[b2] > fl - 32 && Lb[b2] < fl + 20; };
				while (st.length) {
					var q = st.pop(), qx = q % w;
					if (qx > 0 && okp(q, q - 1)) { seen[q - 1] = 1; st.push(q - 1); }
					if (qx < w - 1 && okp(q, q + 1)) { seen[q + 1] = 1; st.push(q + 1); }
					if (q >= w && okp(q, q - w)) { seen[q - w] = 1; st.push(q - w); }
					if (q < w * (h - 1) && okp(q, q + w)) { seen[q + w] = 1; st.push(q + w); }
				}
				// Заливка прошла через бледную линию на штапик - там кадры
				// одинаковые: стекло только там, где хоть немного отличаются.
				for (ii = 0; ii < w * h; ii++) {
					if (!seen[ii]) { continue; }
					var o4 = ii * 4;
					if (Math.abs(src0[o4] - bd[o4]) + Math.abs(src0[o4 + 1] - bd[o4 + 1]) + Math.abs(src0[o4 + 2] - bd[o4 + 2]) <= 4) { seen[ii] = 0; }
				}
				for (ii = 0; ii < w * h; ii++) { n0 += dm2[ii]; n1 += seen[ii]; }
				// Заливка должна почти совпасть с маской: утекла или застряла -
				// филёнка не гладкая, оставляем разницу кадров.
				if (n1 > n0 * 0.9 && n1 < n0 * 1.1) {
					// Одиночные выступы и щербины на кромке - большинством 3x3
					// (дважды), сам край не сдвигается.
					for (var rep = 0; rep < 2; rep++) {
						var sm = new Uint8Array(w * h);
						for (var yy = 1; yy < h - 1; yy++) {
							for (var xx = 1; xx < w - 1; xx++) {
								var k3 = yy * w + xx;
								sm[k3] = seen[k3 - w - 1] + seen[k3 - w] + seen[k3 - w + 1] + seen[k3 - 1] + seen[k3] + seen[k3 + 1] + seen[k3 + w - 1] + seen[k3 + w] + seen[k3 + w + 1] >= 5 ? 1 : 0;
							}
						}
						seen = sm;
					}
					dm2 = seen; crisp = true;
				}
			})();
			var dl = new Int32Array(w * h), dn = 0;
			for (var s9 = 0; s9 < w * h; s9++) {
				if (!dm2[s9] || dl[s9]) { continue; }
				dn++;
				var st9 = [s9], px9 = [], bx0 = w, bx1 = 0, by0 = h, by1 = 0;
				dl[s9] = dn;
				while (st9.length) {
					var q9 = st9.pop(), qx = q9 % w, qy = (q9 / w) | 0;
					px9.push(q9);
					if (qx < bx0) { bx0 = qx; } if (qx > bx1) { bx1 = qx; } if (qy < by0) { by0 = qy; } if (qy > by1) { by1 = qy; }
					if (qx > 0 && dm2[q9 - 1] && !dl[q9 - 1]) { dl[q9 - 1] = dn; st9.push(q9 - 1); }
					if (qx < w - 1 && dm2[q9 + 1] && !dl[q9 + 1]) { dl[q9 + 1] = dn; st9.push(q9 + 1); }
					if (qy > 0 && dm2[q9 - w] && !dl[q9 - w]) { dl[q9 - w] = dn; st9.push(q9 - w); }
					if (qy < h - 1 && dm2[q9 + w] && !dl[q9 + w]) { dl[q9 + w] = dn; st9.push(q9 + w); }
				}
				if (px9.length < leafArea * 0.01) { continue; }
				var bw9 = bx1 - bx0 + 1, bh9 = by1 - by0 + 1, md = new Uint8Array(bw9 * bh9);
				px9.forEach(function (q) { md[(((q / w) | 0) - by0) * bw9 + (q % w) - bx0] = 1; });
				if (!crisp) { md = smoothMask(md, bw9, bh9); }
				var gd = [bx0, by0, bw9, bh9];
				gd.mk = { x0: bx0, y0: by0, W: bw9, H: bh9, d: md };
				gd.exact = true;
				glass.push(gd);
			}
			ov = [];
		}
		if (ov) {
			cands = [];
			ov.forEach(function (f0) {
				var f = Array.isArray(f0) ? f0 : f0.r;
				var gx0 = Math.round(f[0] * w), gy0 = Math.round(f[1] * h), gx1 = Math.round((f[0] + f[2]) * w), gy1 = Math.round((f[1] + f[3]) * h);
				var snap = Array.isArray(f0) ? f[4] !== 0 : f0.snap !== false;
				// Треугольное стекло: tri - три вершины (доли кадра), r - их
				// рамка. Режем ровно по вершинам, без подгонки.
				var tri = !Array.isArray(f0) && f0.tri && f0.tri.length === 3 ? f0.tri.map(function (v) { return [v[0] * w, v[1] * h]; }) : null;
				if (tri) {
					snap = false;
					gx0 = Math.floor(Math.min(tri[0][0], tri[1][0], tri[2][0])); gx1 = Math.ceil(Math.max(tri[0][0], tri[1][0], tri[2][0]));
					gy0 = Math.floor(Math.min(tri[0][1], tri[1][1], tri[2][1])); gy1 = Math.ceil(Math.max(tri[0][1], tri[1][1], tri[2][1]));
				}
				// Витраж режут свинцовые перемычки - заливка не подходит,
				// уточняем только по перепаду на рамке.
				var isKeep = !Array.isArray(f0) && f0.keep;
				var sd = !Array.isArray(f0) && f0.seed ? [Math.round(f0.seed[0] * w), Math.round(f0.seed[1] * h), Math.round((f0.seed[0] + f0.seed[2]) * w), Math.round((f0.seed[1] + f0.seed[3]) * h)] : null;
				// flood: false - в стекле «картинка» (отражение комнаты),
				// заливка по ней рвётся: только прямоугольник по рамке.
				var noFlood = !Array.isArray(f0) && f0.flood === false;
				var rr = !snap ? [gx0, gy0, gx1, gy1] : (isKeep || noFlood) ? refine(gx0, gy0, gx1, gy1, Math.max(8, Math.round(w * 0.02))) : paneEdges(gx0, gy0, gx1, gy1, sd);
				var l3 = rr[0], t3 = rr[1], r3 = rr[2] - 1, b3 = rr[3] - 1;
				var gr = [l3, t3, r3 - l3 + 1, b3 - t3 + 1];
				// Ручное стекло - ровный прямоугольник: маска заливки даёт
				// рваный край по бликам. Маска нужна только стеклу с вырезом
				// (у него задан seed).
				gr.mk = sd ? rr.mk || null : null;
				// Без подгонки - ровно как нарисовано, фацет край не двигает.
				gr.exact = !snap;
				if (tri) { gr.tri = tri; }
				if (!Array.isArray(f0) && f0.keep) {
					// Витраж: проём как на фото, стекло не меняем.
					keepGlass = true;
					for (var ky2 = gr[1]; ky2 < gr[1] + gr[3]; ky2++) { for (var kx2 = gr[0]; kx2 < gr[0] + gr[2]; kx2++) { keep[ky2 * w + kx2] = 1; } }
					return;
				}
				// Раскладка: [колонки, ряды] - планки цвета полотна.
				if (!Array.isArray(f0) && f0.bars) { gr.bars = f0.bars; }
				// Планки в произвольных местах: barX/barY - доли кадра двери
				// (как r), их расставляет редактор стёкол.
				if (!Array.isArray(f0) && (f0.barX || f0.barY)) {
					gr.barX = (f0.barX || []).map(function (v) { return v * w; });
					gr.barY = (f0.barY || []).map(function (v) { return v * h; });
				}
				// Тонкая латунная решётка поверх стекла (как в макете).
				if (!Array.isArray(f0) && f0.lines) { gr.lines = f0.lines; }
				// Косой крест из планок цвета полотна.
				// Планки креста обведены рамками - они уже на фото, свой крест
				// не рисуем.
				var traced = walls.some(function (wl) { return wl.x1 > gr[0] && wl.x0 < gr[0] + gr[2] && wl.y1 > gr[1] && wl.y0 < gr[1] + gr[3]; });
				if (!Array.isArray(f0) && f0.cross && !traced) { gr.cross = f0.cross; }
				glass.push(gr);
			});
		}
		cands.forEach(function (cp) {
			var b = cp.b, bw = b[2] - b[0] + 1, bh = b[3] - b[1] + 1;
			if (bw * bh < leafArea * 0.018) { return; }
			var cover = lineRatio(b[0], b[1], b[2], b[3]), ring = 0, rn = 0, i2;
			for (i2 = 0; i2 < bw; i2 += 2) { ring += near(b[0] + i2, b[1] + 2) + near(b[0] + i2, b[3] - 2); rn += 2; }
			for (i2 = 0; i2 < bh; i2 += 2) { ring += near(b[0] + 2, b[1] + i2) + near(b[2] - 2, b[1] + i2); rn += 2; }
			ring /= rn;
			if (mode !== 'keep' && cover >= 0.4 && ring >= 0.5) {
				var rf = paneEdges(b[0], b[1], b[2] + 1, b[3] + 1);
				var ga = [rf[0], rf[1], rf[2] - rf[0], rf[3] - rf[1]];
				ga.mk = rf.mk || null;
				glass.push(ga);
			} else if (mode === 'keep' && cover >= 0.25) {
				// Витраж: весь проём как на фото.
				keepGlass = true;
				var rk = refine(b[0], b[1], b[2] + 1, b[3] + 1, 10);
				for (var ky = rk[1]; ky < rk[3]; ky++) { for (var kx = rk[0]; kx < rk[2]; kx++) { keep[ky * w + kx] = 1; } }
			} else if (cover >= 0.25) {
				keepGlass = true;
				// Оставляем «стеклянные» пиксели и всё, что ими окружено
				// (блики внутри витража): заливка от края рамки по
				// остальным пикселям - что не залилось, то внутри.
				var bwh = bw * bh, out2 = new Uint8Array(bwh), qs = [], yy, xx;
				var inM = function (ix, iy) { return cm[(b[1] + iy) * w + b[0] + ix]; };
				for (xx = 0; xx < bw; xx++) { qs.push(xx, 0, xx, bh - 1); }
				for (yy = 0; yy < bh; yy++) { qs.push(0, yy, bw - 1, yy); }
				while (qs.length) {
					var fy = qs.pop(), fx = qs.pop();
					if (fx < 0 || fy < 0 || fx >= bw || fy >= bh) { continue; }
					var fi = fy * bw + fx;
					if (out2[fi] || inM(fx, fy)) { continue; }
					out2[fi] = 1;
					qs.push(fx + 1, fy, fx - 1, fy, fx, fy + 1, fx, fy - 1);
				}
				for (yy = 0; yy < bh; yy++) {
					for (xx = 0; xx < bw; xx++) { if (!out2[yy * bw + xx]) { keep[(b[1] + yy) * w + b[0] + xx] = 1; } }
				}
			}
		});
		// Стекло с фацетом (скошенная кромка - тоже стекло): отодвигаем
		// край наружу до тёмной линии, где фацет встречается с рамкой.
		if (opt.over && opt.over.facet) {
			glass.forEach(function (g) {
				if (g.exact) { return; }
				var L7 = function (xx, yy) { var o = (yy * w + xx) * 4; return dd[o] * 0.3 + dd[o + 1] * 0.59 + dd[o + 2] * 0.11; };
				var rowL7 = function (yy) { var s7 = 0, n7 = 0; for (var xx = g[0] + (g[2] >> 2); xx < g[0] + g[2] - (g[2] >> 2); xx++) { s7 += L7(xx, yy); n7++; } return s7 / n7; };
				var colL7 = function (xx) { var s7 = 0, n7 = 0; for (var yy = g[1] + (g[3] >> 2); yy < g[1] + g[3] - (g[3] >> 2); yy++) { s7 += L7(xx, yy); n7++; } return s7 / n7; };
				var darkest = function (fn, from, dir, lim) {
					var best = from, bl = 1e9;
					for (var k7 = 3; k7 <= 12; k7++) {
						var c7 = from + k7 * dir;
						if (c7 < 0 || c7 >= lim) { break; }
						var v7 = fn(c7);
						if (v7 < bl) { bl = v7; best = c7; }
					}
					return best;
				};
				var x0 = darkest(colL7, g[0], -1, w) + 1, x1 = darkest(colL7, g[0] + g[2] - 1, 1, w);
				var y0 = darkest(rowL7, g[1], -1, h) + 1, y1 = darkest(rowL7, g[1] + g[3] - 1, 1, h);
				// Фацет по периметру одной ширины: берём наибольшую (блик
				// внутри кромки останавливает поиск раньше; лишний 1px
				// приходится на тёмную тень рамки и не виден).
				var ks = [g[0] - x0, g[1] - y0, x1 - (g[0] + g[2]), y1 - (g[1] + g[3])].sort(function (a, b) { return a - b; });
				var K = ks[3];
				x0 = Math.max(0, g[0] - K); y0 = Math.max(0, g[1] - K);
				x1 = Math.min(w, g[0] + g[2] + K); y1 = Math.min(h, g[1] + g[3] + K);
				g[0] = x0; g[1] = y0; g[2] = x1 - x0; g[3] = y1 - y0;
				g.mk = null;
			});
		}

		// Решётка из одинаковых стёкол: заливка иногда перетекает на фаску
		// у одного из них. Стёкла одного ряда - одной высоты, одной колонки -
		// одной ширины; за образец берём меньшее (перетёкшее всегда больше).
		(function () {
			var ov2 = function (a0, a1, b0, b1) { return Math.max(0, Math.min(a1, b1) - Math.max(a0, b0)) / Math.min(a1 - a0, b1 - b0); };
			for (var ia = 0; ia < glass.length; ia++) {
				for (var ib = 0; ib < glass.length; ib++) {
					if (ia === ib) { continue; }
					var A6 = glass[ia], B6 = glass[ib];
					if (A6.exact) { continue; }
					// Один ряд, похожая высота: ровняем большее по меньшему.
					if (ov2(A6[1], A6[1] + A6[3], B6[1], B6[1] + B6[3]) > 0.6 && A6[3] > B6[3] + 4 && A6[3] <= B6[3] * 1.4) {
						A6[1] = B6[1]; A6[3] = B6[3];
					}
					// Одна колонка, похожая ширина.
					if (ov2(A6[0], A6[0] + A6[2], B6[0], B6[0] + B6[2]) > 0.6 && A6[2] > B6[2] + 4 && A6[2] <= B6[2] * 1.4) {
						A6[0] = B6[0]; A6[2] = B6[2];
					}
				}
			}
		})();
		// Окно под стекло: эти пиксели двери делаем прозрачными, а новое
		// стекло кладём под дверь - край совпадает с фото до пикселя.
		glass.forEach(function (g) {
			var gx = g[0], gy = g[1], gw4 = g[2], gh4 = g[3], loc = new Uint8Array(gw4 * gh4), xx, yy, mk = g.mk;
			var cov = holds.length || g.tri ? new Float32Array(gw4 * gh4).fill(1) : null;
			// Фурнитура (ручка), заходящая в стекло снаружи, остаётся как на
			// фото: иначе прямоугольник стекла срезает её край.
			var guard = new Uint8Array(gw4 * gh4);
			mcomp.forEach(function (mc) {
				var inside = mc.x0 >= gx && mc.x1 < gx + gw4 && mc.y0 >= gy && mc.y1 < gy + gh4;
				var apart = mc.x1 < gx || mc.x0 >= gx + gw4 || mc.y1 < gy || mc.y0 >= gy + gh4;
				if (inside || apart || (mc.x1 - mc.x0 + 1) * (mc.y1 - mc.y0 + 1) > gw4 * gh4 * 0.5) { return; }
				// Ручка выходит за стекло заметно; блик на стекле, задевший
				// край, - на пару пикселей.
				var ext = Math.max(gx - mc.x0, mc.x1 - gx - gw4 + 1, gy - mc.y0, mc.y1 - gy - gh4 + 1);
				if (ext < Math.max(6, Math.round(w * 0.015))) { return; }
				// Только то, что продолжает ручку от края стекла: в каждой строке
				// (столбце), где ручка пересекает край, идём внутрь, пока идёт
				// ручка. Блик на стекле, слипшийся с ней по цвету, не трогаем.
				var isH = function (x, y) { return x >= 0 && y >= 0 && x < w && y < h && mlab[y * w + x] === mc.id; };
				var cap = Math.max(8, Math.round(w * 0.06));
				var run = function (x, y, dx, dy) {
					var n0 = 0;
					while (n0++ < cap && x >= gx && x < gx + gw4 && y >= gy && y < gy + gh4 && isH(x, y)) {
						guard[(y - gy) * gw4 + x - gx] = 1;
						// сглаженный край детали
						if (dx) { if (y > gy) { guard[(y - 1 - gy) * gw4 + x - gx] = 1; } if (y < gy + gh4 - 1) { guard[(y + 1 - gy) * gw4 + x - gx] = 1; } }
						else { if (x > gx) { guard[(y - gy) * gw4 + x - 1 - gx] = 1; } if (x < gx + gw4 - 1) { guard[(y - gy) * gw4 + x + 1 - gx] = 1; } }
						x += dx; y += dy;
					}
					if (x >= gx && x < gx + gw4 && y >= gy && y < gy + gh4) { guard[(y - gy) * gw4 + x - gx] = 1; }
				};
				var y9, x9;
				// Снаружи ручка идёт сплошь хотя бы 6px (рычаг); блик, чуть
				// вылезший за край рамки стекла, - на 1-3px.
				var out6 = function (x, y, dx, dy) { for (var k = 1; k <= 6; k++) { if (!isH(x + dx * k, y + dy * k)) { return false; } } return true; };
				for (y9 = gy; y9 < gy + gh4; y9++) {
					if (out6(gx, y9, -1, 0)) { run(gx, y9, 1, 0); }
					if (out6(gx + gw4 - 1, y9, 1, 0)) { run(gx + gw4 - 1, y9, -1, 0); }
				}
				for (x9 = gx; x9 < gx + gw4; x9++) {
					if (out6(x9, gy, 0, -1)) { run(x9, gy, 0, 1); }
					if (out6(x9, gy + gh4 - 1, 0, 1)) { run(x9, gy + gh4 - 1, 0, -1); }
				}
			});
			for (yy = 0; yy < gh4; yy++) {
				for (xx = 0; xx < gw4; xx++) {
					var inm = 1;
					if (mk) {
						var mx8 = gx + xx - mk.x0, my8 = gy + yy - mk.y0;
						inm = mx8 >= 0 && my8 >= 0 && mx8 < mk.W && my8 < mk.H ? mk.d[my8 * mk.W + mx8] : 0;
					}
					// Треугольник: доля пикселя внутри - край без ступенек.
					if (g.tri) { cov[yy * gw4 + xx] = triCover(g.tri, gx + xx, gy + yy); inm = cov[yy * gw4 + xx] > 0 ? 1 : 0; }
					loc[yy * gw4 + xx] = inm;
				}
			}
			if (mk) {
				// Блики, не вошедшие в заливку, но окружённые стеклом, - тоже
				// стекло; то, что связано с рамкой (раскладка, вырез), - нет.
				var reach = new Uint8Array(gw4 * gh4), q8 = [];
				for (xx = 0; xx < gw4; xx++) { q8.push(xx, (gh4 - 1) * gw4 + xx); }
				for (yy = 0; yy < gh4; yy++) { q8.push(yy * gw4, yy * gw4 + gw4 - 1); }
				while (q8.length) {
					var j8 = q8.pop();
					if (reach[j8] || loc[j8]) { continue; }
					reach[j8] = 1;
					var jx8 = j8 % gw4;
					if (jx8 > 0) { q8.push(j8 - 1); }
					if (jx8 < gw4 - 1) { q8.push(j8 + 1); }
					if (j8 >= gw4) { q8.push(j8 - gw4); }
					if (j8 < gw4 * (gh4 - 1)) { q8.push(j8 + gw4); }
				}
				for (j8 = 0; j8 < gw4 * gh4; j8++) { if (!loc[j8] && !reach[j8]) { loc[j8] = 1; } }
			}
			// «Не трогать»: вычитаем из стекла, край - со сглаживанием.
			// (cov есть и у треугольника - там уже доля пикселя.)
			if (cov) {
				for (yy = 0; yy < gh4; yy++) {
					for (xx = 0; xx < gw4; xx++) {
						var ci = yy * gw4 + xx;
						if (!loc[ci]) { continue; }
						if (holds.length) { cov[ci] *= holdCover(holds, gx + xx, gy + yy); }
						if (cov[ci] <= 0) { loc[ci] = 0; }
					}
				}
			}
			for (yy = 0; yy < gh4; yy++) {
				for (xx = 0; xx < gw4; xx++) {
					var gi8 = (gy + yy) * w + gx + xx;
					// Латунная решётка внутри стекла - как на фото.
					if (guard[yy * gw4 + xx]) { continue; }
					if (!loc[yy * gw4 + xx]) { if (cm[gi8] && !g.tri) { keep[gi8] = 1; } continue; }
					// Край выреза: полотно полупрозрачное поверх стекла и
					// перекрашивается вместе с дверью - дуга без ступенек.
					if (cov && cov[yy * gw4 + xx] < 1) { dd[gi8 * 4 + 3] = Math.round(255 * (1 - cov[yy * gw4 + xx])); continue; }
					keep[gi8] = 1;
					dd[gi8 * 4 + 3] = 0;
				}
			}
			delete g.mk;
		});
		if (glass.length) { ox.putImageData(od, 0, 0); }

		return {
			src: out, w: w, h: h, cl: cl, cr: cr, ct: ct, casing: hasCasing,
			baseLum: baseLum, keep: keep, glass: glass, keepGlass: keepGlass, holds: holds,
			x0: X0, y0: Y0, ks: KS
		};
	}

	/* ------------------------------------------------------------------
	 * Перекраска: сохраняем свет и тени фото, меняем цвет.
	 * ------------------------------------------------------------------ */
	var TEX = {};
	function loadTex() {
		return Promise.all(Object.keys(WOOD).map(function (kind) {
			return loadImg(WOOD[kind]).then(function (i) {
				if (!i) { return; }
				var c = document.createElement('canvas');
				c.width = i.naturalWidth; c.height = i.naturalHeight;
				var x = c.getContext('2d', { willReadFrequently: true });
				x.drawImage(i, 0, 0);
				TEX[kind] = { w: c.width, h: c.height, d: x.getImageData(0, 0, c.width, c.height).data };
			});
		}));
	}
	function woodAmp(hx) { var t = hex(hx), l = (t[0] * 0.3 + t[1] * 0.59 + t[2] * 0.11) / 255; return 0.9 - 0.65 * l; }
	function grainAt(T, u, v, amp) {
		if (!T) { return 1; }
		var tx = ((Math.round(u) % T.w) + T.w) % T.w, ty = ((Math.round(v) % T.h) + T.h) % T.h;
		return 1 + (T.d[(ty * T.w + tx) * 4] - 128) / 400 * amp;
	}
	var SWATCH = {};
	function woodSwatch(hx, kind) {
		var T = TEX[kind];
		if (!T) { return ''; }
		var key = kind + hx;
		if (SWATCH[key]) { return SWATCH[key]; }
		var n = 72, c = document.createElement('canvas');
		c.width = c.height = n;
		var x = c.getContext('2d'), id = x.createImageData(n, n), t = hex(hx), amp = woodAmp(hx);
		for (var y = 0; y < n; y++) {
			for (var xx = 0; xx < n; xx++) {
				var g = grainAt(T, 150 + xx * 2, 380 + y * 2, amp), p = (y * n + xx) * 4;
				for (var q = 0; q < 3; q++) { id.data[p + q] = Math.min(255, t[q] * g); }
				id.data[p + 3] = 255;
			}
		}
		x.putImageData(id, 0, 0);
		return (SWATCH[key] = c.toDataURL());
	}

	function recolor(A, color, useGrain) {
		var w = A.w, h = A.h;
		var c = document.createElement('canvas');
		c.width = w; c.height = h;
		var x = c.getContext('2d', { willReadFrequently: true });
		x.drawImage(A.src, 0, 0);
		var id = x.getImageData(0, 0, w, h), d = id.data;
		var t = hex(color.hex), T = color.kind && useGrain ? TEX[color.kind] : null, amp = T ? woodAmp(color.hex) : 0;
		for (var yy = 0; yy < h; yy++) {
			for (var xx = 0; xx < w; xx++) {
				var i = yy * w + xx, p = i * 4;
				if (!d[p + 3]) { continue; }
				var kp = A.keep[i];
				if (kp >= 1) { continue; }
				var l = (d[p] * 0.3 + d[p + 1] * 0.59 + d[p + 2] * 0.11) / A.baseLum;
				var gv = T ? (yy < A.ct ? grainAt(T, yy + 200, xx + 300, amp) : grainAt(T, xx + 5, yy, amp)) : 1;
				for (var q = 0; q < 3; q++) {
					var tk = t[q] * gv, v = l <= 1 ? tk * l : tk + (255 - tk) * Math.min(1, (l - 1) * 3);
					d[p + q] = v * (1 - kp) + d[p + q] * kp;
				}
			}
		}
		x.putImageData(id, 0, 0);
		return c;
	}

	/* Наличник: растягиваем только плоскую часть профиля, край и
	   внутренняя ступенька остаются чёткими. */
	function prof(u, cw, P) {
		var O = 14, I = 8;
		if (cw < O + I + 4) { return Math.min(P - 1, Math.floor(u * P / cw)); }
		if (u < O) { return u; }
		if (u >= cw - I) { return P - (cw - u); }
		return Math.min(P - I - 1, O + Math.floor((u - O) * (P - O - I) / (cw - O - I)));
	}
	/* Планка наличника из исходного фото бывает с огрехами вырезки:
	   - у края - редкие крошки фона (светлые точки за наличником): столбец,
	     где непрозрачна меньшая часть, - не наличник, очищаем его целиком;
	     а пропуски в столбцах наличника (край срезан) заполняем по столбцу;
	   - низ обрезан неровно: последние ряды частично прозрачные, сквозь них
	     видна стена. Нижние 12 рядов заменяем рядом над обрезом. */
	function cleanStrip(cv) {
		var W = cv.width, H = cv.height, x = cv.getContext('2d');
		if (!W || H < 40) { return; }
		var im = x.getImageData(0, 0, W, H), d = im.data, u, y;
		for (u = 0; u < W; u++) {
			var n = 0;
			for (y = 0; y < H; y++) { if (d[(y * W + u) * 4 + 3] > 128) { n++; } }
			if (n < H * 0.5) { for (y = 0; y < H; y++) { d[(y * W + u) * 4 + 3] = 0; } continue; }
			// Столбец наличника, но с пропусками (на фото край местами срезан):
			// профиль по длине одинаковый - заполняем пропуск ближайшим
			// непрозрачным пикселем этого же столбца.
			var last = -1;
			for (y = 0; y < H; y++) {
				var o = (y * W + u) * 4;
				if (d[o + 3] > 128) { last = o; continue; }
				var src = last, k;
				if (src < 0) { for (k = y + 1; k < H; k++) { if (d[(k * W + u) * 4 + 3] > 128) { src = (k * W + u) * 4; break; } } }
				if (src >= 0) { d[o] = d[src]; d[o + 1] = d[src + 1]; d[o + 2] = d[src + 2]; d[o + 3] = 255; }
			}
		}
		x.putImageData(im, 0, 0);
		var B = 12;
		x.clearRect(0, H - B, W, B);
		x.drawImage(cv, 0, H - B - 1, W, 1, 0, H - B, W, B);
	}

	function frame(A) {
		if (!A.casing) { return { CW: A.w, CH: A.h }; }
		return { CW: Math.ceil(A.cr - A.cl + 2 * A.cl * PMAX + 2), CH: Math.ceil(A.h - A.ct + A.ct * PMAX + 2) };
	}
	function compose(A, src, s) {
		var F = frame(A);
		if (!A.casing) { return { canvas: src, x0: 0, y0: 0, lw: 0, th: 0, CW: F.CW, CH: F.CH }; }
		var w = A.w, bodyH = A.h - A.ct, leafW = A.cr - A.cl;
		var PL = A.cl, PR = w - A.cr, cw = Math.max(8, Math.round(PL * s));
		var Wd = leafW + cw * 2, x0 = Math.round((F.CW - Wd) / 2), y0 = F.CH - bodyH - cw;
		var c = document.createElement('canvas');
		c.width = F.CW; c.height = F.CH;
		var x = c.getContext('2d');
		var L = bodyH + cw, vl = document.createElement('canvas'), vr = document.createElement('canvas');
		vl.width = vr.width = cw; vl.height = vr.height = L;
		var lx = vl.getContext('2d'), rx = vr.getContext('2d');
		for (var u = 0; u < cw; u++) {
			var sl = prof(u, cw, PL), sr = w - 1 - prof(u, cw, PR);
			lx.drawImage(src, sl, A.ct, 1, bodyH, u, cw, 1, bodyH); lx.drawImage(src, sl, A.ct, 1, cw, u, 0, 1, cw);
			rx.drawImage(src, sr, A.ct, 1, bodyH, u, cw, 1, bodyH); rx.drawImage(src, sr, A.ct, 1, cw, u, 0, 1, cw);
		}
		cleanStrip(vl); cleanStrip(vr);
		x.drawImage(src, A.cl, A.ct, leafW, bodyH, x0 + cw, y0 + cw, leafW, bodyH);
		x.drawImage(vl, x0, y0);
		x.save(); x.translate(x0 + Wd, y0); x.scale(-1, 1); x.drawImage(vr, 0, 0); x.restore();
		// Верхний наличник: боковая планка, повёрнутая на 90°, углы - на ус.
		x.save();
		x.beginPath(); x.moveTo(x0, y0); x.lineTo(x0 + Wd, y0); x.lineTo(x0 + Wd - cw, y0 + cw); x.lineTo(x0 + cw, y0 + cw); x.closePath(); x.clip();
		x.setTransform(0, 1, 1, 0, x0, y0);
		var from = Math.min(cw + 40, Math.max(0, L - Wd)), len = Math.min(Wd, L - from);
		x.drawImage(vl, 0, from, cw, len, 0, 0, cw, Wd);
		x.restore();
		x.save(); x.strokeStyle = 'rgba(0,0,0,.18)'; x.lineWidth = 1;
		x.beginPath(); x.moveTo(x0 + 0.5, y0 + 0.5); x.lineTo(x0 + cw, y0 + cw); x.moveTo(x0 + Wd - 0.5, y0 + 0.5); x.lineTo(x0 + Wd - cw, y0 + cw); x.stroke(); x.restore();
		return { canvas: c, x0: x0, y0: y0, lw: cw, th: cw, CW: F.CW, CH: F.CH };
	}

	/* Просвет под полотном в исходнике прозрачный - сквозь него виден пол.
	   Снизу вверх закрашиваем прозрачные пиксели между стойками коробки,
	   пока строки с такими «дырами» не кончатся. */
	function sealGap(cv, rgb) {
		var W = cv.width, H = cv.height, x = cv.getContext('2d'), c = rgb || [14, 13, 12];
		var maxH = Math.max(4, Math.round(H * 0.03)), y0 = H - maxH;
		var im = x.getImageData(0, y0, W, maxH), d = im.data, hit = false;
		for (var y = maxH - 1; y >= 0; y--) {
			var row = y * W * 4, l = -1, r = -1, k, n = 0;
			for (k = 0; k < W; k++) { if (d[row + k * 4 + 3] > 128) { if (l < 0) { l = k; } r = k; } }
			if (l < 0) { continue; }
			for (k = l; k <= r; k++) {
				var o = row + k * 4, a = d[o + 3];
				if (a < 250) {
					var t = a / 255;
					d[o] = Math.round(d[o] * t + c[0] * (1 - t)); d[o + 1] = Math.round(d[o + 1] * t + c[1] * (1 - t)); d[o + 2] = Math.round(d[o + 2] * t + c[2] * (1 - t));
					d[o + 3] = 255; n++;
				}
			}
			if (n) { hit = true; } else if (hit) { break; }
		}
		if (hit) { x.putImageData(im, 0, y0); }
		return cv;
	}

	function copyCanvas(cv) {
		var c = document.createElement('canvas');
		c.width = cv.width; c.height = cv.height;
		c.getContext('2d').drawImage(cv, 0, 0);
		return c;
	}

	function toUrl(canvas) {
		return new Promise(function (res) {
			if (canvas.toBlob) {
				canvas.toBlob(function (b) { res(b ? URL.createObjectURL(b) : canvas.toDataURL('image/png')); }, 'image/png');
			} else { res(canvas.toDataURL('image/png')); }
		});
	}

	/* ------------------------------------------------------------------
	 * Готовая разметка двери (images/konfigurator/baked/<коллекция>/<N>.*).
	 * Разбор фото (analyse) - 0.3 с на компьютере и 1-2 с на телефоне, и
	 * результат у модели всегда один и тот же. Поэтому он считается заранее
	 * (tools/bake-doors.html, локально) и кладётся рядом: .json - размеры и
	 * разметка стёкол, .png - маска двери (канал R) и карта «оставить как на
	 * фото» (канал G). На сайте остаётся собрать дверь из фото и маски - ~20 мс.
	 * Файлы действуют, пока совпадает подпись (sig): правка модели, стёкол
	 * или алгоритма меняет подпись - тогда дверь разбирается как раньше.
	 * ------------------------------------------------------------------ */
	var ANALYSE_VERSION = 1;
	// ?nobaked в адресе - сравнить скорость с разбором фото (для проверки).
	var BAKED = !/[?&]nobaked\b/.test(location.search);
	var PRE = {};

	// Разметка двери и маска: один раз на модель; init запрашивает первую
	// модель заранее - пока собирается панель выбора, файлы уже в пути.
	function bakedLoad(alias, i) {
		var key = alias + '/' + i;
		if (!PRE[key]) {
			var base = BASE + 'baked/' + encodeURIComponent(alias) + '/' + i;
			PRE[key] = Promise.all([
				fetch(base + '.json?v=' + VERSION, { credentials: 'same-origin' }).then(function (r) { return r.ok ? r.json() : null; }),
				loadImg(base + '.png?v=' + VERSION)
			]);
		}
		return PRE[key];
	}

	function bakeSig(m, col) {
		// Цена и название на разбор фото не влияют - правка цены не должна
		// сбрасывать готовую разметку.
		var mm = {}, k;
		for (k in m) { if (k !== 'price' && k !== 'name') { mm[k] = m[k]; } }
		var str = JSON.stringify([ANALYSE_VERSION, mm, col.detectMetal, col.glass]), h = 5381, i;
		for (i = 0; i < str.length; i++) { h = ((h << 5) + h + str.charCodeAt(i)) | 0; }
		return (h >>> 0).toString(36);
	}

	function bakeDoor(A, m, col) {
		var w = A.w, h = A.h, cv = document.createElement('canvas');
		cv.width = w; cv.height = h;
		var x = cv.getContext('2d', { willReadFrequently: true });
		var sd = A.src.getContext('2d').getImageData(0, 0, w, h).data, od = x.createImageData(w, h), i;
		for (i = 0; i < w * h; i++) {
			od.data[i * 4] = sd[i * 4 + 3];
			od.data[i * 4 + 1] = Math.round(A.keep[i] * 255);
			od.data[i * 4 + 3] = 255;
		}
		x.putImageData(od, 0, 0);
		var meta = { sig: bakeSig(m, col), w: w, h: h, cl: A.cl, cr: A.cr, ct: A.ct, casing: A.casing, baseLum: A.baseLum,
			keepGlass: A.keepGlass, glass: A.glass, holds: A.holds, x0: A.x0, y0: A.y0, ks: A.ks };
		return { meta: meta, png: cv.toDataURL('image/png') };
	}

	function unbakeDoor(img, meta, pack) {
		var W = Math.round(img.naturalWidth * meta.ks), H = Math.round(img.naturalHeight * meta.ks), w = meta.w, h = meta.h;
		var c = document.createElement('canvas');
		c.width = W; c.height = H;
		// willReadFrequently - как в analyse: иначе масштабирование у программной
		// и аппаратной канвы чуть разное, и фото расходится с разобранным.
		c.getContext('2d', { willReadFrequently: true }).drawImage(img, 0, 0, W, H);
		var out = document.createElement('canvas');
		out.width = w; out.height = h;
		var ox = out.getContext('2d', { willReadFrequently: true });
		ox.drawImage(c, meta.x0, meta.y0, w, h, 0, 0, w, h);
		var od = ox.getImageData(0, 0, w, h), dd = od.data;
		var pc = document.createElement('canvas');
		pc.width = w; pc.height = h;
		var px = pc.getContext('2d', { willReadFrequently: true });
		px.drawImage(pack, 0, 0);
		var pd = px.getImageData(0, 0, w, h).data, keep = new Float32Array(w * h), i;
		for (i = 0; i < w * h; i++) { dd[i * 4 + 3] = pd[i * 4]; keep[i] = pd[i * 4 + 1] / 255; }
		ox.putImageData(od, 0, 0);
		return { src: out, w: w, h: h, cl: meta.cl, cr: meta.cr, ct: meta.ct, casing: meta.casing, baseLum: meta.baseLum,
			keep: keep, glass: meta.glass, keepGlass: meta.keepGlass, holds: meta.holds };
	}

	/* ------------------------------------------------------------------
	 * Конфигуратор
	 * ------------------------------------------------------------------ */
	function Configurator(hero, cfg) {
		this.hero = hero;
		this.cfg = cfg;
		this.alias = cfg.alias || '';
		this.models = cfg.models;
		this.mats = (cfg.materials || ['ral', 'veneer']).filter(function (m) { return m === 'ral' || m === 'veneer'; });
		// Шпон как вариант цвета пока скрыт (решение владельца): остаётся
		// только у коллекций, где других материалов нет («Шпонированные»).
		// Вернуть - HIDE_VENEER = false.
		if (HIDE_VENEER && this.mats.length > 1) {
			this.mats = this.mats.filter(function (m) { return m !== 'veneer'; });
		}
		if (this.mats.indexOf('ral') >= 0) { this.mats.splice(this.mats.indexOf('ral') + 1, 0, 'ncs'); }
		var mat = this.mats[0], defRal = 0;
		RAL.forEach(function (c, i) { if (c[0] === DEFAULT_RAL) { defRal = i; } });
		this.s = {
			tab: 'model', model: 0, mat: mat, color: mat === 'ral' ? defRal : 1, glass: 'm2',
			portal: 'classic', wall: 0, floor: 0,
			// Каталог в панели цвета: какой открыт, группа/оттенок и строка поиска.
			cat: mat === 'veneer' ? 'ral' : mat, grp: { ral: '*', ncs: '*' }, q: ''
		};
		// #m=3 - открыть сразу модель 3 (ссылка из редактора стёкол).
		var hm = /(?:^#|&)m=(\d+)/.exec(location.hash);
		if (hm && this.models[+hm[1]]) { this.s.model = +hm[1]; }
		this.A = {};      // разбор картинок по моделям
		this.pending = {};
		this.cache = {};  // готовые картинки двери
		this.thumbs = {};
		this.thumbPending = {};
		// Миниатюры моделей (картинки + вырезка двери на каждой) - после первого
		// показа двери: их обработка занимала основной поток ровно тогда, когда
		// нужно рисовать саму дверь.
		var selfD = this;
		if (window.performance && performance.mark) { performance.mark('kdc-start'); }
		this.doorReady = new Promise(function (res) { selfD.doorOk = res; });
		setTimeout(function () { selfD.doorOk(); }, 4000);
		this.crops = {};
		this.token = 0;
		this.build();
		if (window.performance && performance.mark) { performance.mark('kdc-built'); }
	}

	/* Каталоги RAL и NCS пришли после первого показа: подменяем списки,
	   текущий цвет находим по коду в новом списке, панель цвета обновляем. */
	Configurator.prototype.setCatalogs = function (cols) {
		if (!cols || !cols.ral || !cols.ral.length) { return; }
		var s = this.s, cur = s.mat === 'ral' ? RAL[s.color] : null;
		RAL = cols.ral; NCS = cols.ncs || [];
		if (cur) {
			var at = -1;
			RAL.forEach(function (c, i) { if (c[0] === cur[0]) { at = i; } });
			if (at >= 0) { s.color = at; }
		}
		this.cache = {}; this.urls = {};
		this.renderPanel();
		this.renderStage();
	};

	Configurator.prototype.color = function () {
		var s = this.s;
		if (s.mat === 'veneer') {
			var v = VENEER[s.color];
			return { id: 'v' + s.color, name: v[0], hex: v[1], kind: v[2] };
		}
		if (s.mat === 'ncs') {
			var n = NCS[s.color];
			return { id: 'n' + s.color, name: 'NCS ' + n[0], hex: n[2] };
		}
		var r = RAL[s.color];
		return { id: 'r' + s.color, name: 'RAL ' + r[0] + ' ' + r[1], hex: r[2] };
	};

	Configurator.prototype.analysis = function (i) {
		var self = this;
		if (this.A[i] !== undefined) { return Promise.resolve(this.A[i]); }
		if (this.pending[i]) { return this.pending[i]; }
		var m = this.models[i];
		// ?kdc - сервер отдаёт исходник, а не сжатую WebP-копию: разметка
		// двери по пикселям проверена на исходниках.
		// blank - то же фото без стекла: стекло вырезаем по разнице кадров.
		var main = loadImg('/' + m.src.replace(/^\//, '') + '?kdc=' + VERSION);
		// Готовая разметка (см. выше): ищем рядом, пока грузится фото. Нет файла
		// или не совпала подпись - обычный разбор фото, как раньше.
		var baked = (BAKED && this.alias) ? bakedLoad(this.alias, i)
			.then(function (r2) { return r2[0] && r2[1] && r2[0].sig === bakeSig(m, self.cfg) ? r2 : null; }).catch(function () { return null; }) : Promise.resolve(null);
		return (this.pending[i] = Promise.all([main, baked]).then(function (r2) {
			var img = r2[0], a = null;
			if (img && r2[1]) {
				try { a = unbakeDoor(img, r2[1][0], r2[1][1]); } catch (e0) { a = null; }
			}
			if (a) { self.A[i] = a; return a; }
			var bl = m.blank ? loadImg('/' + m.blank.replace(/^\//, '') + '?kdc=' + VERSION) : Promise.resolve(null);
			return bl.then(function (blank) {
				try { a = img ? analyse(img, { detectMetal: self.cfg.detectMetal, glass: self.cfg.glass, over: m, blank: blank }) : null; } catch (e) { a = null; }
				self.A[i] = a;
				return a;
			});
		}));
	};

	Configurator.prototype.door = function (i, color, portalId) {
		var A = this.A[i];
		if (!A) { return null; }
		var rk = i + '|' + color.id;
		if (!this.cache[rk]) { this.cache[rk] = recolor(A, color, this.cfg.grain !== false); }
		var ck = rk + '|' + portalId;
		if (!this.cache[ck]) {
			var p = PORTALS.filter(function (x) { return x.id === portalId; })[0] || PORTALS[0];
			var cd = compose(A, this.cache[rk], p.s);
			if (cd.canvas === this.cache[rk]) { cd.canvas = copyCanvas(cd.canvas); }
			if (!INTERIOR) { sealGap(cd.canvas); }
			this.cache[ck] = cd;
		}
		return this.cache[ck];
	};

	/* --- разметка --------------------------------------------------- */
	Configurator.prototype.build = function () {
		var photo = this.hero.querySelector('.kdc-cd-hero-photo');
		var text = this.hero.querySelector('.kdc-cd-hero-text');
		if (!photo || !text) { return; }
		this.hero.classList.add('kdc-cfg-on');
		// Флаг страницы для правил на body (custom.css): вместо body:has(.kdc-cfg-on),
		// из-за которого браузер проверял весь body при каждой смене класса.
		document.body.classList.add('kdc-cfg-page');

		var stage = document.createElement('div');
		stage.className = 'kdc-cfg-stage';
		stage.innerHTML =
			(INTERIOR ? '<img class="kdc-cfg-room" src="' + INTERIOR.src + '" alt="" decoding="async">' : '') +
			'<div class="kdc-cfg-floor"><canvas></canvas></div>' +
			'<div class="kdc-cfg-skirt"></div>' +
			'<div class="kdc-cfg-shadow"></div>' +
			(INTERIOR ? '<div class="kdc-cfg-underfloor"></div>' : '') +
			'<div class="kdc-cfg-scene">' +
				'<div class="kdc-cfg-door"><div class="kdc-cfg-glass-layer"></div><img class="kdc-cfg-door-img" alt=""></div>' +
			'</div>' +
			// Стена и пол: по кружку с текущим цветом - на стене справа и под
			// ним на полу. Касание раскрывает варианты: стены - столбиком,
			// пола - строкой (custom.css).
			// В интерьере стена и пол - фото, кружки выбора скрыты (custom.css) -
			// их ламинат (канвас + toDataURL на каждый пол) собирать незачем.
			(INTERIOR ? '' : '<div class="kdc-cfg-env" role="group" aria-label="Стена и пол">' +
				'<div class="kdc-cfg-env-group is-wall">' +
				'<button type="button" class="kdc-cfg-env-cur" data-kdc="envopen:wall" aria-label="Цвет стены" title="Цвет стены"><span></span></button>' +
				'<div class="kdc-cfg-env-list">' +
				WALLS.map(function (w, i) {
					return '<button type="button" class="kdc-cfg-env-sw" data-kdc="wall:' + i + '" title="Стена: ' + w[0].toLowerCase() + '" aria-label="Стена: ' + w[0].toLowerCase() + '"><span style="background:' + w[1] + '"></span></button>';
				}).join('') +
				'</div></div>' +
				'<div class="kdc-cfg-env-group is-floor">' +
				'<button type="button" class="kdc-cfg-env-cur" data-kdc="envopen:floor" aria-label="Пол" title="Пол"><span></span></button>' +
				'<div class="kdc-cfg-env-list">' +
				FLOORS.map(function (f, i) {
					return '<button type="button" class="kdc-cfg-env-sw" data-kdc="floor:' + i + '" title="' + f[0] + '" aria-label="' + f[0] + '"><span style="background:url(' + laminate(f[1]) + ') 40% 0 / auto 260%,' + f[1] + '"></span></button>';
				}).join('') +
				'</div></div>' +
			'</div>') +
			(INTERIOR ? '<div class="kdc-cfg-ghost" aria-hidden="true"></div>' : '') +
			'<div class="kdc-cfg-loading">Загрузка…</div>';
		photo.innerHTML = '';
		photo.appendChild(stage);
		this.stage = stage;
		if (INTERIOR) {
			var cfg0 = this;
			stage.classList.add('is-room');
			this.hero.classList.add('kdc-room');
			// Без «резинки» вверх (custom.css): над фото комнаты не должен
			// мелькать белый фон страницы.
			document.documentElement.classList.add('kdc-room-page');
			// Размер фото известен заранее - раскладку пересчитываем на всякий
			// случай, когда оно догрузится.
			stage.querySelector('.kdc-cfg-room').addEventListener('load', function () { cfg0.layoutStage(); });
		}

		this.buildStory(text);

		var panel = document.createElement('div');
		panel.className = 'kdc-cfg-panel';
		panel.innerHTML =
			'<div class="kdc-cfg-picker"><div class="kdc-cfg-tabs" role="tablist"></div><div class="kdc-cfg-body"></div></div>' +
			'<button type="button" class="kdc-cfg-open" aria-haspopup="dialog">Выбрать модель и материал' +
				'<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M3 7h11M18 7h3M3 17h4M11 17h10" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round"/><circle cx="16" cy="7" r="2.2" stroke="currentColor" stroke-width="1.6" fill="none"/><circle cx="9" cy="17" r="2.2" stroke="currentColor" stroke-width="1.6" fill="none"/></svg>' +
			'</button>' +
			'<div class="kdc-cfg-summary"></div>';
		this.panel = panel;
		// Вкладки и варианты. На телефоне они уезжают в шторку, поэтому
		// ищем их через picker, а не через panel.
		this.picker = panel.querySelector('.kdc-cfg-picker');

		/* Телефон: выбор - в шторке снизу на пол-экрана, поверх страницы
		   (как у Волховца); дверь над ней меняется сразу. Шторка живёт в
		   body: у обёрток SPPB есть transform/overflow, внутри них fixed
		   ведёт себя как absolute. */
		var sheet = document.createElement('div');
		sheet.className = 'kdc-cfg-sheet';
		sheet.setAttribute('role', 'dialog');
		sheet.setAttribute('aria-label', 'Выбор модели и материала');
		sheet.innerHTML = '<div class="kdc-cfg-sheet-head"><span class="kdc-cfg-sheet-grip"></span>' +
			'<button type="button" class="kdc-cfg-sheet-close" aria-label="Закрыть">&times;</button></div>';
		var backdrop = document.createElement('div');
		backdrop.className = 'kdc-cfg-sheet-backdrop';
		this.sheet = sheet;

		// На телефоне и планшете выбор - под превью, на компьютере - под
		// описанием слева.
		var photoWrap = this.hero.querySelector('.addon-root-dynamic-content-image') || photo.parentNode;
		var mq = window.matchMedia('(max-width: 991px)');
		var mqPhone = window.matchMedia('(max-width: 575px)');
		var self = this;
		var place = function () {
			if (mq.matches) { photoWrap.appendChild(panel); panel.classList.add('is-below'); } else { text.appendChild(panel); panel.classList.remove('is-below'); }
			if (mqPhone.matches) {
				if (!sheet.parentNode) { document.body.appendChild(backdrop); document.body.appendChild(sheet); }
				sheet.appendChild(self.picker);
			} else {
				self.closeSheet();
				panel.insertBefore(self.picker, panel.firstChild);
			}
		};
		[mq, mqPhone].forEach(function (m) {
			if (m.addEventListener) { m.addEventListener('change', place); } else if (m.addListener) { m.addListener(place); }
		});

		this.openSheet = function () {
			if (!mqPhone.matches || sheet.classList.contains('is-open')) { return; }
			// Сцена поднимается так, чтобы кружки стены и пола целиком стояли
			// над шторкой, но верх двери не уходил за край экрана; шапка
			// уезжает вверх. Страницу замораживаем на этой позиции: иначе
			// iPhone листал её жестом по шторке - Safari начинает прокрутку
			// раньше, чем её можно отменить из touchmove.
			var from = window.pageYOffset;
			var door = stage.querySelector('.kdc-cfg-door-img');
			var env = stage.querySelector('.kdc-cfg-env-group.is-floor');
			var free = window.innerHeight - sheet.offsetHeight;
			var maxY = document.documentElement.scrollHeight - window.innerHeight;
			var bottom = (env && env.offsetHeight ? env : stage).getBoundingClientRect().bottom;
			var shift = bottom - (free - 12);
			if (door && door.offsetHeight) { shift = Math.min(shift, door.getBoundingClientRect().top - 8); }
			lockY = Math.max(0, Math.min(maxY, Math.round(from + shift)));
			var bs = document.body.style;
			bs.position = 'fixed'; bs.top = -from + 'px'; bs.left = '0'; bs.right = '0'; bs.width = '100%';
			void document.body.offsetHeight;
			bs.transition = 'top 0.3s cubic-bezier(0.2, 0.8, 0.2, 1)';
			bs.top = -lockY + 'px';
			document.documentElement.classList.add('kdc-sheet-open');
			sheet.style.transform = '';
			sheet.style.height = '';
			sheet.classList.add('is-open');
			backdrop.classList.add('is-open');
			halfH = sheet.offsetHeight;
			self.toListStart();
		};
		var lockY = null;
		this.closeSheet = function () {
			sheet.style.transform = '';
			sheet.classList.remove('is-open');
			backdrop.classList.remove('is-open');
			document.documentElement.classList.remove('kdc-sheet-open');
			if (lockY !== null) {
				var bs = document.body.style, html = document.documentElement, sb = html.style.scrollBehavior;
				bs.position = bs.top = bs.left = bs.right = bs.width = bs.transition = '';
				// Возвращаем позицию мгновенно, без плавной прокрутки темы.
				html.style.scrollBehavior = 'auto';
				window.scrollTo(0, lockY);
				html.style.scrollBehavior = sb;
				lockY = null;
			}
		};
		panel.querySelector('.kdc-cfg-open').addEventListener('click', this.openSheet);
		sheet.querySelector('.kdc-cfg-sheet-close').addEventListener('click', this.closeSheet);
		// Мимо шторки - закрыть. Но кружки стены и пола видны над ней:
		// касание по ним передаём кружку, шторка остаётся.
		backdrop.addEventListener('click', function (e) {
			backdrop.style.pointerEvents = 'none';
			var under = document.elementFromPoint(e.clientX, e.clientY);
			backdrop.style.pointerEvents = '';
			var sw = under && under.closest('.kdc-cfg-env-sw, .kdc-cfg-env-cur');
			if (sw) { sw.click(); } else { self.closeSheet(); }
		});
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { self.closeSheet(); } });

		// Ручка/вкладки: потянуть вверх - шторка растёт за пальцем (до 85%
		// экрана) и остаётся, где отпустили; вниз ниже обычной высоты -
		// возвращается к ней, а дальше чем на 60px - закрывается.
		var dragY = null, dragStartH = 0, dragH = 0, halfH = 0;
		var dragTo = function (h) {
			dragH = h;
			var maxH = window.innerHeight * 0.85;
			if (h >= halfH) {
				sheet.style.height = Math.min(maxH, h) + 'px';
				sheet.style.transform = '';
			} else {
				sheet.style.height = halfH + 'px';
				sheet.style.transform = 'translateY(' + (halfH - h) + 'px)';
			}
		};
		sheet.addEventListener('touchstart', function (e) {
			if (!e.target.closest('.kdc-cfg-sheet-head, .kdc-cfg-tabs')) { return; }
			dragY = e.touches[0].clientY;
			dragStartH = dragH = sheet.offsetHeight;
			sheet.style.transition = 'none';
		}, { passive: true });
		sheet.addEventListener('touchmove', function (e) {
			if (dragY === null) { return; }
			dragTo(dragStartH - (e.touches[0].clientY - dragY));
		}, { passive: true });
		// Жест внутри шторки листает только список, но не страницу под ней.
		// overscroll-behavior не помогает, когда список короткий и сам не
		// прокручивается: тогда браузер отдаёт жест странице.
		var lastY = 0;
		sheet.addEventListener('touchstart', function (e) { lastY = e.touches[0].clientY; }, { passive: true });
		sheet.addEventListener('touchmove', function (e) {
			var y = e.touches[0].clientY, dy = y - lastY, list = e.target.closest('.kdc-cfg-body');
			lastY = y;
			if (!e.cancelable) { return; }
			if (!list) { e.preventDefault(); return; }
			// Короткий список прокручивать нечего - жест гасим целиком.
			if (list.scrollHeight <= list.clientHeight + 1) { e.preventDefault(); return; }
			var atTop = list.scrollTop <= 0, atEnd = list.scrollTop + list.clientHeight >= list.scrollHeight - 1;
			if ((dy > 0 && atTop) || (dy < 0 && atEnd)) { e.preventDefault(); }
		}, { passive: false });
		sheet.addEventListener('touchend', function () {
			if (dragY === null) { return; }
			dragY = null;
			sheet.style.transition = '';
			if (dragH < halfH - 60) {
				self.closeSheet();
			} else if (dragH < halfH) {
				sheet.style.height = '';
				sheet.style.transform = '';
			} else {
				sheet.style.transform = '';
			}
		});
		place();

		var onPick = function (e) {
			var b = e.target.closest('[data-kdc]');
			if (!b || !(self.hero.contains(b) || sheet.contains(b))) { return; }
			var a = b.getAttribute('data-kdc').split(':');
			var v = a[1];
			// Стена и пол - только фон превью, дверь не пересчитываем.
			if (a[0] === 'envopen') {
				var grp = self.stage.querySelector('.kdc-cfg-env-group.is-' + v);
				var was = grp.classList.contains('is-open');
				[].forEach.call(self.stage.querySelectorAll('.kdc-cfg-env-group'), function (g) { g.classList.remove('is-open'); });
				grp.classList.toggle('is-open', !was);
				return;
			}
			if (a[0] === 'wall' || a[0] === 'floor') {
				self.s[a[0]] = +v;
				self.renderEnv();
				[].forEach.call(self.stage.querySelectorAll('.kdc-cfg-env-group'), function (g) { g.classList.remove('is-open'); });
				return;
			}
			if (a[0] === 'cat') {
				self.s.cat = v; self.s.q = '';
				self.renderPanel();
				return;
			}
			var newTab = a[0] === 'tab' && self.s.tab !== v;
			if (a[0] === 'tab') { self.s.tab = v; } else if (a[0] === 'model') { self.s.model = +v; } else if (a[0] === 'color') {
				self.s.mat = v.split('-')[0]; self.s.color = +v.split('-')[1];
			} else if (a[0] === 'glass') { self.s.glass = v; } else if (a[0] === 'portal') { self.s.portal = v; }
			self.update();
			if (newTab) { self.toListStart(); }
		};
		this.hero.addEventListener('click', onPick);
		// Касание мимо раскрытых кружков - свернуть их.
		document.addEventListener('click', function (e) {
			if (self.stage && !e.target.closest('.kdc-cfg-env')) {
				[].forEach.call(self.stage.querySelectorAll('.kdc-cfg-env-group.is-open'), function (g) { g.classList.remove('is-open'); });
			}
		});
		sheet.addEventListener('click', onPick);
		// Поиск и выбор группы: перерисовываем только сетку образцов, чтобы поле не теряло фокус.
		var onFilter = function (e) {
			var t = e.target, box = t.closest && t.closest('.kdc-cfg-body');
			if (!box || !(self.hero.contains(t) || sheet.contains(t))) { return; }
			if (t.hasAttribute('data-kdc-search')) { self.s.q = t.value; }
			else if (t.hasAttribute('data-kdc-group')) { self.s.grp[self.s.cat] = t.value; self.s.q = ''; var si = box.querySelector('[data-kdc-search]'); if (si) { si.value = ''; } }
			else { return; }
			var sw = box.querySelector('[data-swatches]');
			if (sw) { sw.innerHTML = self.swatchesHtml(); }
		};
		this.hero.addEventListener('input', onFilter);
		sheet.addEventListener('input', onFilter);
		this.hero.addEventListener('change', onFilter);
		sheet.addEventListener('change', onFilter);
		var ro = window.ResizeObserver ? new ResizeObserver(function () { self.layoutStage(); }) : null;
		if (ro) { ro.observe(stage); } else { window.addEventListener('resize', function () { self.layoutStage(); }); }

		this.renderEnv();
		// Блок «Модельный ряд» ниже на странице скрыт (custom.css), его карточки
		// грузили и обрабатывали те же 16 картинок второй раз - не строим.
		// Интерьер: комнату и силуэт двери раскладываем сразу, не дожидаясь
		// ResizeObserver (он срабатывает кадром позже - мелькал пустой фон).
		if (INTERIOR) { stage.classList.add('is-loading'); this.layoutStage(); this.normalizeCard(); }
		this.update();
	};

	/* Высота карточки на компьютере не зависит от длины описания: высота
	   блока - это высота кадра комнаты, а от неё масштаб и место двери.
	   Описание в две строки делало карточку выше, и дверь у такой
	   коллекции стояла выше и крупнее, чем у остальных. Лишнюю строку
	   забираем у списка моделей (он и так прокручивается). */
	Configurator.prototype.normalizeCard = function () {
		var sub = this.hero.querySelector('.kdc-cd-hero-sub p') || this.hero.querySelector('.kdc-cd-hero-sub');
		var body = this.picker && this.picker.querySelector('.kdc-cfg-body');
		if (!sub || !body || !window.matchMedia) { return; }
		var mq = window.matchMedia('(min-width: 992px)');
		var fit = function () {
			body.style.height = '';
			if (!mq.matches) { return; }
			var lh = parseFloat(getComputedStyle(sub).lineHeight) || 27, lines = Math.round(sub.getBoundingClientRect().height / lh);
			if (lines > 1) { body.style.height = body.getBoundingClientRect().height - (lines - 1) * lh + 'px'; }
		};
		fit();
		if (window.ResizeObserver) { new ResizeObserver(fit).observe(sub); } else { window.addEventListener('resize', fit); }
		if (document.fonts && document.fonts.ready) { document.fonts.ready.then(fit); }
	};

	/* Новая вкладка открывается с начала списка. На телефоне список
	   прокручивается вместе со страницей под закреплёнными вкладками:
	   долистали модели до конца, нажали «Цвет» - и оказывались внизу
	   цветов. Возвращаем страницу так, чтобы список начинался сразу под
	   вкладками. На компьютере у списка своя прокрутка - сбрасываем её. */
	Configurator.prototype.toListStart = function () {
		var body = this.picker.querySelector('.kdc-cfg-body');
		var tabs = this.picker.querySelector('.kdc-cfg-tabs');
		if (!body || !tabs) { return; }
		body.scrollTop = 0;
		var gap = body.getBoundingClientRect().top - tabs.getBoundingClientRect().bottom;
		if (gap < -1) { window.scrollTo(0, window.pageYOffset + gap); }
	};

	// Ламинат: доски вдоль стены, у каждой свой оттенок, продольный
	// рисунок волокон и стыки. Рисуем сами - без загрузки текстур.
	var LAMINATE = {};
	function laminate(hx) {
		if (LAMINATE[hx]) { return LAMINATE[hx]; }
		var W = 960, P = 30, ROWS = 3, H = P * ROWS, L = 320, c = document.createElement('canvas');
		c.width = W; c.height = H;
		var x = c.getContext('2d'), id = x.createImageData(W, H), t = hex(hx), seed = 7;
		var rnd = function () { seed = (seed * 16807) % 2147483647; return seed / 2147483647; };
		var planks = [];
		for (var r = 0; r < ROWS; r++) {
			var off = Math.floor(rnd() * L), list = [];
			for (var k = -1; k <= W / L + 1; k++) {
				// Профиль волокон поперёк доски - случайный, плавный.
				var prof = [];
				for (var j = 0; j < P * 3; j++) { prof.push(rnd()); }
				list.push({ x0: k * L - off, tone: 0.94 + rnd() * 0.12, p1: rnd() * 6.28, p2: rnd() * 6.28, f1: 0.004 + rnd() * 0.004, f2: 0.012 + rnd() * 0.01, prof: prof });
			}
			planks.push(list);
		}
		for (var y = 0; y < H; y++) {
			var row = Math.floor(y / P), yy = y % P, list2 = planks[row];
			for (var xx = 0; xx < W; xx++) {
				var pl = null;
				for (var i = 0; i < list2.length; i++) { if (xx >= list2[i].x0 && xx < list2[i].x0 + L) { pl = list2[i]; break; } }
				var u = xx - pl.x0;
				// Волокна идут вдоль доски и плавно изгибаются; поперёк -
				// случайный профиль (светлее/темнее), плюс мелкое зерно.
				var wy = yy + 2.2 * Math.sin(u * pl.f1 + pl.p1) + 1.1 * Math.sin(u * pl.f2 + pl.p2);
				var fy = (wy + P) * 1.4, i0 = Math.floor(fy), fr = fy - i0, pr = pl.prof, n = pr.length;
				var v = pr[((i0 % n) + n) % n] * (1 - fr) + pr[(((i0 + 1) % n) + n) % n] * fr;
				var g = pl.tone * (1 + (v - 0.5) * 0.09 + (rnd() - 0.5) * 0.025);
				// Фаска по краям доски - чуть темнее.
				if (yy === 0 || u === 0) { g *= 0.72; } else if (yy === 1 || u === 1) { g *= 0.9; } else if (yy === P - 1) { g *= 1.04; }
				var p = (y * W + xx) * 4;
				for (var q = 0; q < 3; q++) { id.data[p + q] = Math.max(0, Math.min(255, t[q] * g)); }
				id.data[p + 3] = 255;
			}
		}
		x.putImageData(id, 0, 0);
		return (LAMINATE[hx] = c.toDataURL('image/jpeg', 0.85));
	}

	// Пол в перспективе, как на фото дверей в интерьере: доски идут на
	// зрителя и сходятся к горизонту на уровне глаз (около ручки двери).
	// Каждая точка экрана пересчитывается в точку на полу: X - вдоль
	// стены, z - от стены к зрителю (в «пикселях у стены»).
	var FLOOR_H = 0.17;
	Configurator.prototype.drawFloor = function () {
		var st = this.stage;
		if (!st) { return; }
		var cv = st.querySelector('.kdc-cfg-floor canvas'), f = FLOORS[this.s.floor] || FLOORS[0];
		var dpr = Math.min(1.5, window.devicePixelRatio || 1);
		var SWc = st.clientWidth, SHc = st.clientHeight;
		var W = Math.round(SWc * dpr), H = Math.round(SHc * FLOOR_H * dpr);
		if (!W || !H) { return; }
		var geo = this.floorGeo || { hH: SHc * (1 - FLOOR_H) * 0.9 };
		var key = W + 'x' + H + f[1] + '|' + Math.round(geo.hH);
		if (this.floorKey === key) { return; }
		this.floorKey = key;
		cv.width = W; cv.height = H;
		var x = cv.getContext('2d'), id = x.createImageData(W, H), d = id.data, t = hex(f[1]);
		var dark = (t[0] * 0.3 + t[1] * 0.59 + t[2] * 0.11) < 120;
		var seed = 11, rnd = function () { seed = (seed * 16807) % 2147483647; return seed / 2147483647; };
		// Горизонт - на высоте ручки (~45% двери над полом).
		var dy0 = geo.hH * 0.45 * dpr, cx = W / 2, F = 1.6 * W;
		var PW = Math.max(40, SWc * 0.12) * dpr, PL = PW * 6.5;
		// Свойства досок - по номеру ряда и отрезка (стабильно).
		var cache = {};
		var plank = function (col, seg) {
			var k = col + ':' + seg;
			if (!cache[k]) {
				var sd = (col * 73856093 ^ seg * 19349663) >>> 0, r = function () { sd = (sd * 16807) % 2147483647 || 1; return sd / 2147483647; };
				var prof = [];
				for (var j = 0; j < 40; j++) { prof.push(r()); }
				cache[k] = { tone: 0.93 + r() * 0.13, p1: r() * 6.28, p2: r() * 6.28, prof: prof };
			}
			return cache[k];
		};
		for (var yy = 0; yy < H; yy++) {
			var dy = dy0 + yy + 0.5, sc = dy0 / dy, z = F * (1 - sc);
			// Ширина фаски в единицах пола - постоянная на экране.
			var bw = 1.3 * dpr / sc;
			var light = (0.84 + 0.16 * Math.min(1, yy / (H * 0.5)));
			for (var xx = 0; xx < W; xx++) {
				var X = (xx + 0.5 - cx) * sc + PW * 50;
				var col = Math.floor(X / PW), v = X - col * PW;
				var zz = z + (col * 0.37 % 1) * PL, seg = Math.floor(zz / PL), u = zz - seg * PL;
				var pl = plank(col, seg);
				// Волокна идут вдоль доски (по z), плавно изгибаясь.
				var wy = v / PW * 26 + 1.6 * Math.sin(u * 0.004 + pl.p1) + 0.8 * Math.sin(u * 0.013 + pl.p2);
				var fy = wy * 1.5, i0 = Math.floor(fy), fr = fy - i0, pr = pl.prof, n = pr.length;
				var vv = pr[((i0 % n) + n) % n] * (1 - fr) + pr[(((i0 + 1) % n) + n) % n] * fr;
				var g = pl.tone * (1 + (vv - 0.5) * 0.1 + (rnd() - 0.5) * 0.025) * light;
				// Фаски: продольные стыки (сходятся к горизонту) и торцы.
				if (v < bw || u < bw * 0.6) { g *= dark ? 0.66 : 0.78; } else if (v < bw * 2) { g *= 0.93; } else if (PW - v < bw) { g *= dark ? 1.08 : 1.04; }
				// Блик лака: светлая полоса перед дверью.
				var sheen = Math.max(0, 1 - Math.abs(xx - cx) / (W * 0.28)) * (0.3 + 0.7 * yy / H) * (dark ? 16 : 9);
				var p = (yy * W + xx) * 4;
				for (var q = 0; q < 3; q++) { d[p + q] = Math.max(0, Math.min(255, t[q] * g + sheen)); }
				d[p + 3] = 255;
			}
		}
		x.putImageData(id, 0, 0);
		// Тень у стены.
		var ao = x.createLinearGradient(0, 0, 0, H * 0.22);
		ao.addColorStop(0, 'rgba(0,0,0,' + (dark ? 0.32 : 0.2) + ')');
		ao.addColorStop(1, 'rgba(0,0,0,0)');
		x.fillStyle = ao;
		x.fillRect(0, 0, W, H * 0.22);
	};

	Configurator.prototype.renderEnv = function () {
		var st = this.stage, s = this.s, self = this;
		if (!st || INTERIOR) { return; }
		var w = WALLS[s.wall] || WALLS[0], f = FLOORS[s.floor] || FLOORS[0];
		st.style.setProperty('--kdc-wall', w[1]);
		this.drawFloor();
		// Светлая дверь на тёмной стене - тень заметнее.
		st.classList.toggle('is-dark-wall', s.wall === 3);
		// Кружки-«текущие»: стена - цветом, пол - тем же ламинатом.
		var cw = st.querySelector('.is-wall .kdc-cfg-env-cur span'), cf = st.querySelector('.is-floor .kdc-cfg-env-cur span');
		var sf = st.querySelector('[data-kdc="floor:' + s.floor + '"] span');
		if (cw) { cw.style.background = w[1]; }
		if (cf && sf) { cf.style.background = sf.style.background; }
		// На светлой стене/полу кружки и раскрытый список - тёмные, иначе
		// белая обводка теряется на фоне.
		var light = function (hx) { var t = hex(hx); return t[0] * 0.3 + t[1] * 0.59 + t[2] * 0.11 > 150; };
		var gw = st.querySelector('.kdc-cfg-env-group.is-wall'), gf = st.querySelector('.kdc-cfg-env-group.is-floor');
		if (gw) { gw.classList.toggle('is-dark-ui', light(w[1])); }
		if (gf) { gf.classList.toggle('is-dark-ui', light(f[1])); }
		[].forEach.call(st.querySelectorAll('[data-kdc^="wall:"],[data-kdc^="floor:"]'), function (b) {
			var k = b.getAttribute('data-kdc').split(':'), on = +k[1] === s[k[0]];
			b.classList.toggle('is-on', on);
			b.setAttribute('aria-pressed', on);
		});
	};

	// Текстуры шпона (~400 КБ) - только когда понадобились: открыли
	// «Цвет» или выбран шпон.
	Configurator.prototype.ensureTex = function () {
		var self = this;
		if (this.texReady || this.texLoading) { return; }
		this.texLoading = true;
		loadTex().then(function () {
			self.texReady = true;
			if (self.texWait) { var fw = self.texWait; self.texWait = null; fw(); }
			self.update();
		});
	};

	/* Есть ли у модели стекло: по разбору фото, если он уже есть, иначе -
	   по признаку glazed из models.json (проставлен заранее, чтобы группы
	   не перестраивались по мере разбора), иначе - по ручной разметке. */
	Configurator.prototype.glazed = function (i) {
		var A = this.A[i], m = this.models[i];
		var keep = Array.isArray(m.glass) && m.glass.some(function (g) { return g && g.keep; });
		if (A) { return A.glass.length > 0 || keep; }
		if (typeof m.glazed === 'boolean') { return m.glazed; }
		if (Array.isArray(m.glass)) { return m.glass.length > 0; }
		return false;
	};

	/* Загрузка новой модели показывается на её плитке в меню, а на сцене
	   остаётся прежняя дверь, пока новая не готова. */
	Configurator.prototype.setBusy = function (i) {
		this.busy = i;
		[].forEach.call(this.picker.querySelectorAll('.kdc-cfg-model'), function (b) {
			var on = +b.getAttribute('data-kdc').split(':')[1] === i;
			b.classList.toggle('is-busy', on);
			if (on) { b.setAttribute('aria-busy', 'true'); } else { b.removeAttribute('aria-busy'); }
		});
	};

	Configurator.prototype.update = function () {
		var self = this, s = this.s;
		// Выбранная модель - в адресе (#m=N): после обновления страницы
		// открывается та же дверь, и ссылкой можно поделиться.
		var hash = s.model ? '#m=' + s.model : '';
		if (location.hash !== hash && (!location.hash || /^#m=\d+$/.test(location.hash)) && window.history && history.replaceState) {
			history.replaceState(history.state, '', location.pathname + location.search + hash);
		}
		if ((s.tab === 'color' && this.mats.indexOf('veneer') >= 0) || s.mat === 'veneer') { this.ensureTex(); }
		// Шпон без текстуры не рисуем - иначе в кэш попадёт дверь без
		// рисунка дерева.
		if (s.mat === 'veneer' && !this.texReady) { this.renderPanel(); return; }
		// Новая модель ещё не разобрана. Пока на сцене ничего нет (первый
		// показ) - «Загрузка…» на сцене; дальше сцена держит прежнюю дверь,
		// а загрузку показывает плитка модели в меню.
		if (this.A[s.model] === undefined && this.stage) {
			if (this.dr) { this.busy = s.model; } else { this.stage.classList.add('is-loading'); }
		}
		this.analysis(s.model).then(function (A) {
			if (window.performance && performance.mark && !self.markedAn) { self.markedAn = true; performance.mark('kdc-analysed'); }
			// Модель без стекла под замену - вкладка «Стекло» не нужна.
			if (s.tab === 'glass' && !(A && A.glass.length)) { s.tab = 'model'; }
			if (s.tab === 'portal' && !(A && A.casing)) { s.tab = 'model'; }
			self.renderPanel();
			self.renderStage();
			if (s.tab === 'model') { self.renderThumbs(); }
		});
		this.renderPanel();
		// Миниатюры не ждут разбора основной двери.
		if (s.tab === 'model') { this.renderThumbs(); }
	};

	// Панель цвета: переключатель каталогов RAL / NCS, поиск по номеру, группа
	// (серия RAL или оттенок NCS) и сетка образцов. Шпон (только у шпонированных
	// коллекций) - отдельной группой, как раньше.
	Configurator.prototype.colorPanelHtml = function () {
		var s = this.s, self = this, html = '', hasCat = this.mats.indexOf('ral') >= 0;
		if (hasCat) {
			var cats = [['ral', 'RAL'], ['ncs', 'NCS']];
			var groups = s.cat === 'ral' ? RAL_GROUPS : NCS_HUES;
			html += '<div class="kdc-cfg-cat"><div class="kdc-cfg-cats" role="tablist">' + cats.map(function (c) {
				return '<button type="button" role="tab" aria-selected="' + (s.cat === c[0]) + '" class="kdc-cfg-catbtn' + (s.cat === c[0] ? ' is-on' : '') + '" data-kdc="cat:' + c[0] + '">' + c[1] + '</button>';
			}).join('') + '</div><div class="kdc-cfg-filters">' +
				'<input type="search" class="kdc-cfg-search" data-kdc-search placeholder="' + (s.cat === 'ral' ? 'Номер RAL' : 'Номер NCS') + '" value="' + esc(s.q) + '" autocomplete="off" inputmode="search" aria-label="Поиск цвета">' +
				'<select class="kdc-cfg-group-sel" data-kdc-group aria-label="' + (s.cat === 'ral' ? 'Серия RAL' : 'Оттенок NCS') + '">' +
				[['*', 'Все цвета (' + (s.cat === 'ral' ? RAL.length : NCS.length) + ')']].concat(groups).map(function (g) { return '<option value="' + g[0] + '"' + (s.grp[s.cat] === g[0] ? ' selected' : '') + '>' + (s.cat === 'ral' && g[0] !== '*' ? g[0] + '000 - ' + g[1] : g[1]) + '</option>'; }).join('') +
				'</select></div></div><div class="kdc-cfg-swatches" data-swatches>' + this.swatchesHtml() + '</div>' +
				'<div class="kdc-cfg-note kdc-cfg-colornote">Цвета на экране приблизительные: точный оттенок подбирается по каталогу RAL или NCS.</div>';
		}
		if (this.mats.indexOf('veneer') >= 0) {
			html += '<div class="kdc-cfg-group"><div class="kdc-cfg-group-title">Шпон</div><div class="kdc-cfg-swatches">' + VENEER.map(function (c, i) {
				var on = s.mat === 'veneer' && s.color === i;
				return '<button type="button" class="kdc-cfg-swatch' + (on ? ' is-on' : '') + '" aria-pressed="' + on + '" data-kdc="color:veneer-' + i + '" title="' + esc(c[0]) + '">' +
					'<span style="background:' + c[1] + ' url(' + woodSwatch(c[1], c[2]) + ') center/cover"></span></button>';
			}).join('') + '</div></div>';
		}
		return html;
	};

	// Образцы текущего каталога с учётом поиска и группы.
	Configurator.prototype.swatchesHtml = function () {
		var s = this.s, cat = s.cat, list = cat === 'ral' ? RAL : NCS, q = s.q.trim().toLowerCase().replace(/^(ral|ncs)\s*/, '').replace(/^s\s+/, ''), g = s.grp[cat], out = [];
		list.forEach(function (c, i) {
			var code = c[0].toLowerCase();
			if (q) { if (code.indexOf(q) < 0 && (cat !== 'ral' || c[1].toLowerCase().indexOf(q) < 0)) { return; } }
			else if (g !== '*' && (cat === 'ral' ? code.charAt(0) !== g : c[1] !== g)) { return; }
			var on = s.mat === cat && s.color === i, name = cat === 'ral' ? 'RAL ' + c[0] + ' ' + c[1] : 'NCS ' + c[0], hex = c[2];
			out.push('<button type="button" class="kdc-cfg-swatch' + (on ? ' is-on' : '') + '" aria-pressed="' + on + '" data-kdc="color:' + cat + '-' + i + '" title="' + esc(name) + '">' +
				'<span style="background:' + hex + '"></span></button>');
		});
		return out.length ? out.join('') : '<div class="kdc-cfg-empty">Ничего не найдено</div>';
	};

	Configurator.prototype.renderPanel = function () {
		var s = this.s, A = this.A[s.model], m = this.models[s.model], color = this.color(), self = this;
		var tabs = [['model', 'Модель'], ['color', 'Цвет']];
		if (A && A.glass.length) { tabs.push(['glass', 'Стекло']); }
		if (A && A.casing) { tabs.push(['portal', 'Наличник']); }
		this.picker.querySelector('.kdc-cfg-tabs').innerHTML = tabs.map(function (t) {
			var on = s.tab === t[0];
			return '<button type="button" role="tab" aria-selected="' + on + '" class="kdc-cfg-tab' + (on ? ' is-on' : '') + '" data-kdc="tab:' + t[0] + '">' + t[1] + '</button>';
		}).join('');

		var html = '';
		if (s.tab === 'model') {
			// Модели - двумя группами: без стекла и со стеклом (как у
			// Волховца). Заголовки - только если есть обе группы.
			var groups = [['Без остекления', []], ['С остеклением', []]];
			this.models.forEach(function (md, i) { groups[self.glazed(i) ? 1 : 0][1].push(i); });
			var both = groups[0][1].length && groups[1][1].length;
			html = groups.filter(function (g) { return g[1].length; }).map(function (g) {
				return '<div class="kdc-cfg-group">' + (both ? '<div class="kdc-cfg-group-title">' + g[0] + '</div>' : '') +
					'<div class="kdc-cfg-models">' + g[1].map(function (i) {
						var md = self.models[i], on = i === s.model, busy = i === self.busy;
						return '<button type="button" class="kdc-cfg-model' + (on ? ' is-on' : '') + (busy ? ' is-busy' : '') + '" aria-pressed="' + on + '"' + (busy ? ' aria-busy="true"' : '') + ' data-kdc="model:' + i + '" title="' + esc(md.name) + '">' +
							'<span class="kdc-cfg-model-img" data-thumb="' + i + '"></span><span class="kdc-cfg-model-name">' + esc(md.name) + '</span></button>';
					}).join('') + '</div></div>';
			}).join('');
		} else if (s.tab === 'color') {
			html = this.colorPanelHtml();
		} else if (s.tab === 'glass') {
			html = GLASS.map(function (g) {
				return '<div class="kdc-cfg-group"><div class="kdc-cfg-group-title">' + g.title + '</div><div class="kdc-cfg-glasses">' +
					g.items.map(function (x) {
						var on = s.glass === x.id;
						return '<button type="button" class="kdc-cfg-glass' + (on ? ' is-on' : '') + '" aria-pressed="' + on + '" data-kdc="glass:' + x.id + '" title="' + esc(x.name) + '">' +
							'<span class="kdc-cfg-glass-ball"></span><span class="kdc-cfg-glass-pane" style="background:' + x.tint + ';-webkit-backdrop-filter:blur(' + (x.blur * 0.4) + 'px);backdrop-filter:blur(' + (x.blur * 0.4) + 'px)"></span></button>';
					}).join('') + '</div></div>';
			}).join('');
		} else if (s.tab === 'portal') {
			html = '<div class="kdc-cfg-portals">' + PORTALS.map(function (p) {
				var on = s.portal === p.id;
				return '<button type="button" class="kdc-cfg-portal' + (on ? ' is-on' : '') + '" aria-pressed="' + on + '" data-kdc="portal:' + p.id + '" title="' + esc(p.name) + '">' +
					'<span class="kdc-cfg-portal-img" data-portal="' + p.id + '"></span><span class="kdc-cfg-model-name">' + esc(p.name) + '</span></button>';
			}).join('') + '</div>' +
			'<div class="kdc-cfg-note">Возможен заказ гладких наличников на классическом коробе: широкий гладкий - 100, 120 или 150 мм; широкий компланарный - 90, 100, 120 или 150 мм.</div>';
		}
		this.picker.querySelector('.kdc-cfg-body').innerHTML = html;

		var g = ALL_GLASS.filter(function (x) { return x.id === s.glass; })[0];
		var parts = [m.name, color.name];
		if (A && A.glass.length) { parts.push(g.name.replace(/, триплекс$/, '')); }
		if (A && A.casing && s.portal !== 'classic') {
			parts.push(PORTALS.filter(function (p) { return p.id === s.portal; })[0].name);
		}
		var price = m.price ? '<div class="kdc-cfg-price">от ' + Number(m.price).toLocaleString('ru-RU') + ' ₽<span>цена за комплект</span></div>' : '';
		// Телефон: состав двери списком «название - значение» (как у
		// Волховца). На компьютере - прежняя строка: там панель постоянной
		// высоты, а список менял бы её от модели к модели.
		var specs = [['Модель', m.name], ['Отделка', s.mat === 'veneer' ? 'Шпон, ' + color.name.toLowerCase() : color.name]];
		if (A && A.glass.length) { specs.push(['Стекло', g.name]); }
		if (A && A.casing) { specs.push(['Наличник', PORTALS.filter(function (p) { return p.id === s.portal; })[0].name]); }
		this.panel.querySelector('.kdc-cfg-summary').innerHTML =
			'<div class="kdc-cfg-caption">' + esc(parts.join(', ')) + '</div>' +
			'<dl class="kdc-cfg-specs">' + specs.map(function (r) { return '<dt>' + r[0] + '</dt><dd>' + esc(r[1]) + '</dd>'; }).join('') + '</dl>' + price +
			'<div class="kdc-cfg-note">Фурнитура подбирается отдельно. Итоговую стоимость рассчитает менеджер.</div>';

		// Уже готовые миниатюры - сразу на место.
		[].forEach.call(this.picker.querySelectorAll('[data-thumb]'), function (el) {
			var t = self.thumbs[el.getAttribute('data-thumb')];
			if (t) { el.appendChild(copyCanvas(t)); }
		});
		if (s.tab === 'portal') { this.renderPortals(); }
	};

	// Под окна стекла в миниатюрах - нейтральное стекло, иначе там дыра.
	function glassUnder(x, A, dr, k, ox2, oy2) {
		if (!A || !A.glass.length) { return; }
		A.glass.forEach(function (r) {
			var l = (dr.x0 + dr.lw + r[0] - A.cl - 1) * k + ox2, t = (dr.y0 + dr.th + r[1] - A.ct - 1) * k + oy2;
			var gr2 = x.createLinearGradient(l, t, l, t + (r[3] + 2) * k);
			gr2.addColorStop(0, '#dcdad4'); gr2.addColorStop(1, '#b8b2a8');
			x.fillStyle = gr2;
			x.fillRect(l, t, (r[2] + 2) * k, (r[3] + 2) * k);
		});
	}

	// Миниатюры моделей - без полного разбора двери (он тяжёлый):
	// лёгкая копия картинки (та же, что в галерее модельного ряда),
	// быстро вырезаем дверь по фону на уменьшенной копии. Все модели
	// грузятся параллельно.
	function quickCrop(img) {
		var k = Math.min(1, 360 / Math.max(img.naturalWidth, img.naturalHeight));
		var W = Math.max(1, Math.round(img.naturalWidth * k)), H = Math.max(1, Math.round(img.naturalHeight * k));
		var c = document.createElement('canvas');
		c.width = W; c.height = H;
		var x = c.getContext('2d', { willReadFrequently: true });
		x.drawImage(img, 0, 0, W, H);
		var d = x.getImageData(0, 0, W, H).data, bg = [d[0], d[1], d[2]], alpha = d[3] < 40;
		var isBg = function (o) { return d[o + 3] < 40 || (!alpha && Math.abs(d[o] - bg[0]) + Math.abs(d[o + 1] - bg[1]) + Math.abs(d[o + 2] - bg[2]) < 24); };
		var run = function (occ, thr) {
			var best = [0, occ.length - 1], bl = -1, st = -1;
			for (var i = 0; i <= occ.length; i++) {
				if (i < occ.length && occ[i] >= thr) { if (st < 0) { st = i; } } else if (st >= 0) {
					if (i - st > bl) { bl = i - st; best = [st, i - 1]; }
					st = -1;
				}
			}
			return best;
		};
		var col = [], xx, yy, n;
		for (xx = 0; xx < W; xx++) { n = 0; for (yy = 0; yy < H; yy++) { if (!isBg((yy * W + xx) * 4)) { n++; } } col.push(n / H); }
		var xr = run(col, 0.45), row = [];
		for (yy = 0; yy < H; yy++) { n = 0; for (xx = xr[0]; xx <= xr[1]; xx++) { if (!isBg((yy * W + xx) * 4)) { n++; } } row.push(n / (xr[1] - xr[0] + 1)); }
		var yr = run(row, 0.3);
		return [xr[0] / k, yr[0] / k, (xr[1] - xr[0] + 1) / k, (yr[1] - yr[0] + 1) / k];
	}

	// Лёгкая картинка модели и рамка двери на ней (общие для миниатюр
	// в конфигураторе и карточек модельного ряда).
	Configurator.prototype.loadCrop = function (idx) {
		var self = this, m = this.models[idx];
		if (this.crops[idx]) { return this.crops[idx]; }
		var url = '/' + m.src.replace(/^\//, ''), light = url + (/\.(jpe?g|png)$/i.test(url) ? '.webp' : '');
		return (this.crops[idx] = loadImg(light).then(function (img) {
			if (img) { return { img: img, url: light }; }
			return loadImg(url).then(function (im2) { return im2 ? { img: im2, url: url } : null; });
		}).then(function (o) {
			if (!o) { return null; }
			o.r = quickCrop(o.img); o.W = o.img.naturalWidth; o.H = o.img.naturalHeight;
			return o;
		}));
	};

	Configurator.prototype.renderThumbs = function () {
		var self = this;
		if (this.thumbsRun) { return; }
		this.thumbsRun = true;
		this.doorReady.then(function () {
			var order = self.models.map(function (m, i) { return i; });
			// Сначала выбранная модель и соседние - их видно в первую очередь.
			order.sort(function (a, b) { return Math.abs(a - self.s.model) - Math.abs(b - self.s.model); });
			var idle = window.requestIdleCallback ? function (f) { window.requestIdleCallback(f, { timeout: 300 }); } : function (f) { setTimeout(f, 16); };
			var next = function () {
				var idx = order.shift();
				if (idx === undefined) { self.thumbsRun = false; return; }
				var put = function () {
					var el = self.picker.querySelector('[data-thumb="' + idx + '"]');
					if (el && self.thumbs[idx] && !el.firstChild) { el.appendChild(copyCanvas(self.thumbs[idx])); }
				};
				if (self.thumbs[idx]) { put(); next(); return; }
				self.loadCrop(idx).then(function (o) {
					if (o && !self.thumbs[idx]) {
						var r = o.r, H = 200, c = document.createElement('canvas');
						c.height = H; c.width = Math.max(1, Math.round(r[2] * H / r[3]));
						c.getContext('2d').drawImage(o.img, r[0], r[1], r[2], r[3], 0, 0, c.width, H);
						self.thumbs[idx] = c;
					}
					put();
					idle(next);
				});
			};
			// Две очереди: загрузка картинок идёт параллельно, обработка - по одной.
			next(); next();
		});
	};

	/* Модельный ряд ниже на странице (по макету владельца): надпись
	   «Коллекция», заголовок, подзаголовок, переключатель «Все модели /
	   Глухие / Со стеклом» и карточки: дверь без фона, название, цена. Клик - модель выбирается в конфигураторе. */
	Configurator.prototype.buildRange = function () {
		var self = this;
		var gal = document.querySelector('#section-id-edb79f02-d4c8-4d8a-ab69-f4c8e73b8b1f .sppb-dynamic-content-gallery');
		if (!gal) { return; }
		// Штатный заголовок секции заменяем своим (ниже, вместе с фильтром).
		var col = gal.closest('.sppb-column');
		var oldTitle = col && [].filter.call(col.querySelectorAll('.sppb-addon-title'), function (t) { return /Модельный ряд/i.test(t.textContent); })[0];
		if (oldTitle) { oldTitle.closest('.sppb-addon-wrapper').classList.add('kdc-range-hide'); }

		var glazed = this.models.map(function (m, i) { return self.glazed(i); });
		var hasBoth = glazed.indexOf(true) >= 0 && glazed.indexOf(false) >= 0;
		var block = document.createElement('div');
		block.className = 'kdc-range-block';
		block.innerHTML =
			'<div class="kdc-range-head">' +
				'<div class="kdc-range-eyebrow">Коллекция</div>' +
				'<h2 class="kdc-range-title">Модельный ряд</h2>' +
				'<p class="kdc-range-sub">Эстетика, надёжность и продуманные детали в каждой модели.</p>' +
			'</div>' +
			(hasBoth ? '<div class="kdc-range-filter" role="tablist">' +
				'<button type="button" class="is-on" data-range-filter="all">Все модели</button>' +
				'<button type="button" data-range-filter="solid">Глухие</button>' +
				'<button type="button" data-range-filter="glass">Со стеклом</button>' +
			'</div>' : '') +
			'<div class="kdc-range">' + this.models.map(function (m, i) {
				var price = m.price ? '<span class="kdc-range-price">' + Number(m.price).toLocaleString('ru-RU') + ' ₽</span><span class="kdc-range-note">цена за комплект</span>' : '';
				return '<button type="button" class="kdc-range-item" data-kdc-pick="' + i + '" data-glass="' + (glazed[i] ? 1 : 0) + '" title="Выбрать модель ' + esc(m.name) + '">' +
					'<span class="kdc-range-pic"><span class="kdc-range-img" data-range="' + i + '"></span></span>' +
					'<span class="kdc-range-meta"><span class="kdc-range-name">' + esc(m.name) + '</span>' + price + '</span></button>';
			}).join('') + '</div>';
		gal.classList.add('kdc-range-on');
		gal.appendChild(block);
		var grid = block.querySelector('.kdc-range');

		block.addEventListener('click', function (e) {
			var f = e.target.closest('[data-range-filter]');
			if (f) {
				var v = f.getAttribute('data-range-filter');
				[].forEach.call(block.querySelectorAll('[data-range-filter]'), function (x) { x.classList.toggle('is-on', x === f); });
				[].forEach.call(grid.children, function (it) {
					var g = it.getAttribute('data-glass') === '1';
					it.hidden = v === 'solid' ? g : v === 'glass' ? !g : false;
				});
				return;
			}
			var b = e.target.closest('[data-kdc-pick]');
			if (!b) { return; }
			self.s.model = +b.getAttribute('data-kdc-pick');
			self.s.tab = 'model';
			self.update();
			var top = self.hero.getBoundingClientRect().top + window.pageYOffset - 100;
			window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
		});
		// Картинки - когда блок близко к экрану. Лёгкая обрезка по двери
		// (без полного разбора фото - он тяжёлый); светлый фон снимка
		// растворяется в карточке через mix-blend-mode: multiply (custom.css).
		var fill = function () {
			self.models.forEach(function (m, i) {
				self.loadCrop(i).then(function (o) {
					var box = grid.querySelector('[data-range="' + i + '"]');
					if (!o || !box || box.firstChild) { return; }
					var r = o.r, im = document.createElement('img');
					im.src = o.url; im.alt = m.name; im.decoding = 'async';
					box.style.aspectRatio = r[2] + ' / ' + r[3];
					box.style.setProperty('--ar', (r[2] / r[3]).toFixed(4));
					assign(im.style, { width: (o.W / r[2] * 100) + '%', left: (-r[0] / r[2] * 100) + '%', top: (-r[1] / r[3] * 100) + '%' });
					box.appendChild(im);
				});
			});
		};
		if (window.IntersectionObserver) {
			var io = new IntersectionObserver(function (en) {
				if (en.some(function (x) { return x.isIntersecting; })) { io.disconnect(); fill(); }
			}, { rootMargin: '600px 0px' });
			io.observe(grid);
		} else { fill(); }
	};

	Configurator.prototype.renderPortals = function () {
		var self = this, s = this.s, color = this.color();
		if (!this.A[s.model]) { return; }
		PORTALS.forEach(function (p) {
			var el = self.picker.querySelector('[data-portal="' + p.id + '"]');
			if (!el || el.firstChild) { return; }
			var dr = self.door(s.model, color, p.id);
			// Верхний угол двери крупно - видна ширина наличника.
			var c = document.createElement('canvas'), n = 180, sc = 0.62 * (430 / (self.A[s.model].w || 430));
			c.width = c.height = n;
			var x = c.getContext('2d');
			x.fillStyle = '#fff'; x.fillRect(0, 0, n, n);
			glassUnder(x, self.A[s.model], dr, sc * 1.6, -dr.x0 * sc * 1.6 + 18, -dr.y0 * sc * 1.6 + 18);
			x.drawImage(dr.canvas, -dr.x0 * sc * 1.6 + 18, -dr.y0 * sc * 1.6 + 18, dr.CW * sc * 1.6, dr.CH * sc * 1.6);
			el.appendChild(c);
		});
	};

	Configurator.prototype.renderStage = function () {
		var self = this, s = this.s, A = this.A[s.model], st = this.stage;
		if (!A) {
			if (!this.dr) { st.classList.toggle('is-loading', A === undefined); }
			if (A === null) { this.setBusy(null); }
			return;
		}
		var color = this.color(), dr = this.door(s.model, color, s.portal);
		var key = s.model + '|' + color.id + '|' + s.portal;
		var token = ++this.stageToken || (this.stageToken = 1);
		var apply = function (url) {
			if (token !== self.stageToken) { return; }
			var img = st.querySelector('.kdc-cfg-door-img');
			img.src = url;
			if (self.doorOk) { self.doorOk(); }
			// Отметка для измерений: performance.getEntriesByName('kdc-door') в консоли.
			if (window.performance && performance.mark && !self.markedDoor) { self.markedDoor = true; performance.mark('kdc-door'); }
			self.dr = dr;
			self.renderGlass();
			self.layoutStage();
			st.classList.remove('is-loading');
			if (self.busy === s.model) { self.setBusy(null); }
		};
		this.urls = this.urls || {};
		if (this.urls[key]) { apply(this.urls[key]); } else {
			toUrl(dr.canvas).then(function (u) { self.urls[key] = u; apply(u); });
		}
	};

	Configurator.prototype.renderGlass = function () {
		var s = this.s, A = this.A[s.model], dr = this.dr, layer = this.stage.querySelector('.kdc-cfg-glass-layer');
		if (!A || !dr) { return; }
		var g = ALL_GLASS.filter(function (x) { return x.id === s.glass; })[0], color = this.color();
		var leafW = A.cr - A.cl, bodyH = A.h - A.ct;
		layer.innerHTML = A.glass.map(function (r) {
			// Стекло лежит под дверью - берём с запасом 2px, край задаёт
			// окно в картинке двери.
			var gx = r[0] - 2, gy = r[1] - 2, gw = r[2] + 4, gh = r[3] + 4;
			var l = dr.x0 + dr.lw + gx - A.cl, t = dr.y0 + dr.th + gy - A.ct;
			var room = g.mirror ? '' :
				'<img src="' + ROOM + '" alt="" style="position:absolute;left:' + (-(gx - A.cl) / gw * 100) + '%;top:' + (-(gy - A.ct) / gh * 100) + '%;width:' + (leafW / gw * 100) + '%;height:' + (bodyH / gh * 100) + '%;object-fit:cover;object-position:50% 60%;filter:blur(.4px) brightness(.95)">' +
				'<div style="position:absolute;inset:0;background:radial-gradient(120% 90% at 50% 45%,rgba(0,0,0,0) 55%,rgba(40,30,20,.22))"></div>';
			var bars = '';
			if (r.bars) {
				var bw = Math.max(1.5, leafW * 0.022), cols = r.bars[0] || 1, rows = r.bars[1] || 1, bi;
				var bar = function (css) { return '<div style="position:absolute;' + css + 'background:' + color.hex + ';box-shadow:0 0 1px rgba(0,0,0,.35),inset 0 0 0 .5px rgba(255,255,255,.25)"></div>'; };
				for (bi = 1; bi < cols; bi++) { bars += bar('top:0;bottom:0;left:calc(' + (bi * 100 / cols) + '% - ' + (bw / gw * 50) + '%);width:' + (bw / gw * 100) + '%;'); }
				for (bi = 1; bi < rows; bi++) { bars += bar('left:0;right:0;top:calc(' + (bi * 100 / rows) + '% - ' + (bw / gh * 50) + '%);height:' + (bw / gh * 100) + '%;'); }
			}
			if (r.barX || r.barY) {
				var bw2 = Math.max(1.5, leafW * 0.022);
				var bar2 = function (css) { return '<div style="position:absolute;' + css + 'background:' + color.hex + ';box-shadow:0 0 1px rgba(0,0,0,.35),inset 0 0 0 .5px rgba(255,255,255,.25)"></div>'; };
				(r.barX || []).forEach(function (bx) { bars += bar2('top:0;bottom:0;left:' + ((bx - gx - bw2 / 2) / gw * 100) + '%;width:' + (bw2 / gw * 100) + '%;'); });
				(r.barY || []).forEach(function (by) { bars += bar2('left:0;right:0;top:' + ((by - gy - bw2 / 2) / gh * 100) + '%;height:' + (bw2 / gh * 100) + '%;'); });
			}
			if (r.cross) {
				// Крест от угла до угла окна (без запаса в 2px).
				// Толщина планки - в долях окна (как на фото, ~7%).
				var cw2 = 7;
				bars += '<svg viewBox="0 0 100 100" preserveAspectRatio="none" style="position:absolute;left:' + (2 / gw * 100) + '%;top:' + (2 / gh * 100) + '%;width:' + ((gw - 4) / gw * 100) + '%;height:' + ((gh - 4) / gh * 100) + '%;overflow:visible">' +
					['0,0 100,100', '100,0 0,100'].map(function (pts) {
						var a9 = pts.split(' ')[0].split(','), b9 = pts.split(' ')[1].split(',');
						return '<line x1="' + a9[0] + '" y1="' + a9[1] + '" x2="' + b9[0] + '" y2="' + b9[1] + '" stroke="rgba(0,0,0,.22)" stroke-width="' + (cw2 + 1.2) + '"/>' +
							'<line x1="' + a9[0] + '" y1="' + a9[1] + '" x2="' + b9[0] + '" y2="' + b9[1] + '" stroke="' + color.hex + '" stroke-width="' + cw2 + '"/>';
					}).join('') + '</svg>';
			}
			if (r.lines) {
				var lc = r.lines[0] || 1, lr = r.lines[1] || 1, li;
				var line = function (css) { return '<div style="position:absolute;' + css + 'background:rgba(110,82,48,.55);box-shadow:0 0 0 .5px rgba(255,240,210,.25)"></div>'; };
				for (li = 1; li < lc; li++) { bars += line('top:0;bottom:0;left:calc(' + (li * 100 / lc) + '% - .5px);width:1px;'); }
				for (li = 1; li < lr; li++) { bars += line('left:0;right:0;top:calc(' + (li * 100 / lr) + '% - .5px);height:1px;'); }
			}
			return '<div class="kdc-cfg-pane" style="left:' + pct(l, dr.CW) + ';top:' + pct(t, dr.CH) + ';width:' + pct(gw, dr.CW) + ';height:' + pct(gh, dr.CH) + '">' + room +
				'<div style="position:absolute;inset:0;background:' + g.tint + ';-webkit-backdrop-filter:blur(' + g.blur + 'px);backdrop-filter:blur(' + g.blur + 'px)"></div>' +
				'<div style="position:absolute;inset:0;background:linear-gradient(115deg,rgba(255,255,255,0) 30%,rgba(255,255,255,.16) 42%,rgba(255,255,255,0) 55%)"></div>' + bars + '</div>';
		}).join('');
	};

	// Дверь по центру на светлом фоне, стоит на линии пола.
	Configurator.prototype.layoutStage = function () {
		var st = this.stage, dr = this.dr;
		if (!st) { return; }
		var SW = st.clientWidth, SH = st.clientHeight;
		if (!SW || !SH) { return; }
		// Пока дверь не готова, в интерьере уже видна комната и силуэт двери.
		if (!dr) { if (INTERIOR) { this.layoutGhost(SW, SH); } return; }
		var scene = st.querySelector('.kdc-cfg-scene'), door = st.querySelector('.kdc-cfg-door');
		if (INTERIOR) { this.layoutRoom(SW, SH, scene, door); return; }
		// Дверь по центру сцены; справа колонка «Стена/Пол» - широкая
		// дверь не должна до неё доставать (поле с обеих сторон).
		// На телефоне колонка лежит строкой на полу - поле не нужно.
		// Кружки стены/пола стоят у правого края - дверь держим от них в стороне.
		var envCur = st.querySelector('.kdc-cfg-env-cur'), EW = envCur ? envCur.offsetWidth + 28 : 16, AW = SW - 2 * EW;
		var FL = SH * (1 - FLOOR_H), hH = FL - SH * 0.06, hW = hH * dr.CW / dr.CH;
		if (hW > AW) { hW = AW; hH = hW * dr.CH / dr.CW; }
		assign(scene.style, { width: hW + 'px', height: hH + 'px', left: (SW - hW) / 2 + 'px', top: FL - hH + 'px' });
		// Контактная тень под дверью.
		var sh = st.querySelector('.kdc-cfg-shadow');
		if (sh) { assign(sh.style, { left: (SW - hW * 1.08) / 2 + 'px', width: hW * 1.08 + 'px', top: FL - 4 + 'px' }); }
		this.floorGeo = { hH: hH };
		this.drawFloor();
		assign(door.style, { left: '0', top: '0', width: '100%', height: '100%' });
	};

	/* Блок «Про коллекцию» под первым экраном - один, по центру (custom.css,
	   .kdc-about-solo); «Палитра без наценки» рядом скрыта: палитра теперь
	   в выборе цвета. (Кнопку «История» в карточке убрали по просьбе владельца.) */
	Configurator.prototype.buildStory = function () {
		var pal = document.querySelector('.kdc-cd-palette'), palCol = pal && pal.closest('.sppb-row-column');
		if (palCol) { palCol.classList.add('kdc-story-moved'); }
		var ab = document.querySelector('.kdc-cd-about'), abCol = ab && ab.closest('.sppb-row-column');
		if (abCol) { abCol.classList.add('kdc-about-solo'); }
	};

	/* Интерьер: фото заполняет сцену (как cover, но со своей точкой
	   фокуса - проём держим справа на компьютере и по центру на телефоне),
	   дверь вписываем в проём: полотно с коробкой закрывает проём целиком,
	   низ - на линии пола, наличник ложится на стену вокруг. */
	/* Кадр комнаты в сцене: масштаб и сдвиг фото (общие для двери и
	   силуэта при загрузке). */
	Configurator.prototype.roomGeo = function (SW, SH) {
		var R = INTERIOR, img = this.stage.querySelector('.kdc-cfg-room');
		var VT = R.top || 0, VH = (R.bottom || R.h) - VT, S = Math.max(SW / R.w, SH / VH), IW = R.w * S, IH = R.h * S;
		var o = R.open, fx = SW < 768 ? R.focus[1] : R.focus[0];
		var ox = Math.min(0, Math.max(SW - IW, SW * fx - (o[0] + o[2]) / 2 * S));
		var oy = Math.min(0, Math.max(SH - VH * S, (SH - VH * S) / 2)) - VT * S;
		assign(img.style, { left: ox + 'px', top: oy + 'px', width: IW + 'px', height: IH + 'px' });
		var pull = this.hero.getBoundingClientRect().top + window.pageYOffset;
		this.hero.style.setProperty('--kdc-room-pull', Math.max(0, Math.round(pull)) + 'px');
		return { S: S, IW: IW, IH: IH, ox: ox, oy: oy };
	};

	/* Загрузка: на месте двери - мерцающий силуэт с наличником. */
	Configurator.prototype.layoutGhost = function (SW, SH) {
		var g = this.roomGeo(SW, SH), o = INTERIOR.open, gh = this.stage.querySelector('.kdc-cfg-ghost');
		if (!gh) { return; }
		var L = g.ox + o[0] * g.S, Rr = g.ox + o[2] * g.S, T = g.oy + o[1] * g.S, B = g.oy + o[3] * g.S, c = (Rr - L) * 0.09;
		assign(gh.style, { left: L - c + 'px', top: T - c + 'px', width: Rr - L + 2 * c + 'px', height: B - T + c + 'px' });
	};

	Configurator.prototype.layoutRoom = function (SW, SH, scene, door) {
		var R = INTERIOR, dr = this.dr, st = this.stage, o = R.open;
		var g = this.roomGeo(SW, SH), S = g.S, IW = g.IW, IH = g.IH, ox = g.ox, oy = g.oy;
		// Полотно с коробкой в кадре двери: без наличника - весь кадр.
		var bx = 0, by = 0, bw = dr.CW, bh = dr.CH;
		if (dr.lw) { bx = dr.x0 + dr.lw; by = dr.y0 + dr.lw; bw = dr.CW - 2 * bx; bh = dr.CH - by; }
		var OL = ox + o[0] * S, OR = ox + o[2] * S, OT = oy + o[1] * S, OB = oy + o[3] * S;
		// Масштаб - только по высоте полотна с коробкой: от наличника он не
		// зависит, дверь при смене наличника остаётся того же размера.
		var k = (OB - OT) / bh;
		var hW = dr.CW * k, hH = dr.CH * k, left = (OL + OR) / 2 - (bx + bw / 2) * k;
		assign(scene.style, { width: hW + 'px', height: hH + 'px', left: left + 'px', top: OB - hH + 'px' });
		var sh = st.querySelector('.kdc-cfg-shadow');
		if (sh) { assign(sh.style, { left: OL - (OR - OL) * 0.06 + 'px', width: (OR - OL) * 1.12 + 'px', top: OB - 5 + 'px' }); }
		// Просвет под полотном: за дверью продолжается тот же паркет. Полоса
		// за дверью показывает пол из кадра чуть ниже порога, с тенью от
		// полотна; видна только сквозь прозрачную щель в картинке двери.
		var uf = st.querySelector('.kdc-cfg-underfloor');
		if (uf) {
			// В ширину по внешним краям наличника: кадр двери шире (запас под
			// широкий наличник), и за его краем полоса легла бы на плинтус; а
			// уже наличника - в углах щели между коробкой и наличником
			// просвечивала бы белая стена. За наличником полосу не видно.
			var px0 = dr.lw ? dr.x0 : 0;
			var gh = Math.max(4, Math.round(hH * 0.035)), gt = OB - gh, gl = left + px0 * k;
			assign(uf.style, {
				left: gl + 'px', width: (dr.CW - 2 * px0) * k + 'px', top: gt + 'px', height: gh + 1 + 'px',
				backgroundImage: 'linear-gradient(rgba(40, 26, 12, 0.5), rgba(40, 26, 12, 0.28)), url("' + R.src + '")',
				backgroundSize: '100% 100%, ' + IW + 'px ' + IH + 'px',
				backgroundPosition: '0 0, ' + (ox - gl) + 'px ' + (oy - (gh + 2) - gt) + 'px'
			});
		}
		// Свет из окна на двери: тот же кадр комнаты, совмещённый со стеной,
		// по контуру двери (маска - сама картинка двери). Фильтр делает тень
		// стены нейтральной для soft-light, светлеют только солнечные полосы.
		// light: false в room коллекции - без этого слоя (на фото нет солнечных
		// пятен, а контрастный плинтус «просвечивал» бы сквозь низ двери).
		var dImg = door.querySelector('.kdc-cfg-door-img'), light = door.querySelector('.kdc-cfg-light');
		if (R.light === false) { if (light) { light.remove(); } this.floorGeo = { hH: hH }; assign(door.style, { left: '0', top: '0', width: '100%', height: '100%' }); return; }
		if (!light) {
			light = document.createElement('div');
			light.className = 'kdc-cfg-light';
			door.appendChild(light);
		}
		var mask = dImg && dImg.src ? 'url("' + dImg.src + '")' : 'none';
		assign(light.style, {
			backgroundImage: 'url("' + R.src + '")',
			backgroundSize: IW + 'px ' + IH + 'px',
			backgroundPosition: (ox - left) + 'px ' + (oy - (OB - hH)) + 'px',
			webkitMaskImage: mask, maskImage: mask
		});
		this.floorGeo = { hH: hH };
		assign(door.style, { left: '0', top: '0', width: '100%', height: '100%' });
	};

	/* ------------------------------------------------------------------ */
	function init() {
		var hero = document.querySelector('.kdc-cd-hero');
		if (!hero || hero.classList.contains('kdc-cfg-on')) { return; }
		var alias = decodeURIComponent(location.pathname.replace(/\/+$/, '').split('/').pop() || '');
		var getJson = function (u) { return fetch(BASE + u + '?v=' + VERSION, { credentials: 'same-origin' }).then(function (r) { return r.json(); }); };
		// Модели - файлом своей коллекции (models/<коллекция>.json, 1-5 КБ, их
		// собирает tools/split-models.php); общий models.json (100 КБ) - запасной.
		var own = getJson('models/' + encodeURIComponent(alias) + '.json').catch(function () { return getJson('models.json'); });
		own.then(function (data) {
			var cfg = data && data.collections && data.collections[alias];
			if (!cfg || !cfg.models || !cfg.models.length) { return; }
			cfg.alias = alias;
			// Свой интерьер у коллекции (room в models.json): фото, кадр и место двери.
			if (cfg.room && cfg.room.src && INTERIOR) {
				INTERIOR = assign(assign({}, INTERIOR), cfg.room);
				INTERIOR.src = BASE + cfg.room.src;
			}
			// Первая модель (или та, что открывает #m=N) - сразу, до сборки панели.
			var hm = /(?:^#|&)m=(\d+)/.exec(location.hash), first = hm && cfg.models[+hm[1]] ? +hm[1] : 0;
			if (BAKED) { bakedLoad(alias, first); }
			var app = new Configurator(hero, cfg);
			// Каталоги цветов (colors.json, ~70 КБ) нужны только на вкладке
			// «Цвет»: грузим после того, как собраны выбор и дверь, с низким
			// приоритетом, чтобы они не задерживали первый показ.
			var late = function () {
				fetch(BASE + 'colors.json?v=' + VERSION, { credentials: 'same-origin', priority: 'low' }).then(function (r) { return r.json(); })
					.then(function (cols) { app.setCatalogs(cols); }).catch(function () {});
			};
			if (window.requestIdleCallback) { window.requestIdleCallback(late, { timeout: 1500 }); } else { setTimeout(late, 300); }
		}).catch(function () {});
	}

	window.KDCConfigurator = { analyse: analyse, recolor: recolor, compose: compose, init: init, bakeDoor: bakeDoor, unbakeDoor: unbakeDoor, bakeSig: bakeSig, loadImg: loadImg, version: function () { return VERSION; } };
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
