/*
 * Готовая разметка дверей конфигуратора: images/konfigurator/baked/<коллекция>/<N>.json|png.
 * Разбор фото в браузере - 0.3 с на компьютере и 1-2 с на телефоне, а результат у модели
 * один и тот же; поэтому его считают заранее, а сайт берёт готовое (kdc-configurator.js:
 * bakeDoor / unbakeDoor). Совпала подпись (sig: модель + стёкла + версия алгоритма) -
 * берётся готовое, не совпала (правили стёкла/модель/фото) - дверь разбирается как раньше.
 *
 * КОГДА ЗАПУСКАТЬ: после правки стёкол в редакторе, добавления/замены моделей или фото.
 * КАК: поднять локальный сервер (preview kleverdors-local), открыть любую страницу коллекции
 * (http://localhost:8000/katalogproduktsii/baget-e), вставить этот файл в консоль браузера.
 * Файлы пишет локальный роутер (/__kdc/save-baked, router.php). Потом - залить папку baked/
 * на хостинг вместе с остальными файлами.
 * Если поменяли сам алгоритм analyse() - увеличить ANALYSE_VERSION в kdc-configurator.js.
 */
(async function () {
	var K = window.KDCConfigurator, V = K.version();
	var data = await (await fetch('/images/konfigurator/models.json?x=' + Date.now())).json();
	var n = 0, fail = [];
	for (var alias of Object.keys(data.collections)) {
		var col = data.collections[alias];
		for (var i = 0; i < col.models.length; i++) {
			var m = col.models[i];
			try {
				var img = await K.loadImg('/' + m.src.replace(/^\//, '') + '?kdc=' + V);
				var bl = m.blank ? await K.loadImg('/' + m.blank.replace(/^\//, '') + '?kdc=' + V) : null;
				var A = K.analyse(img, { detectMetal: col.detectMetal, glass: col.glass, over: m, blank: bl });
				if (!A) { fail.push(alias + '/' + i); continue; }
				var b = K.bakeDoor(A, m, col);
				var r = await (await fetch('/__kdc/save-baked', { method: 'POST', body: JSON.stringify({ col: alias, i: i, meta: b.meta, png: b.png }) })).json();
				if (!r.ok) { fail.push(alias + '/' + i + ' save'); } else { n++; }
			} catch (e) { fail.push(alias + '/' + i + ' ' + e.message); }
		}
	}
	console.log('baked', n, 'fail', fail);
})();
