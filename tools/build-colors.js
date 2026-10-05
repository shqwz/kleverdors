// Собирает public_html/images/konfigurator/colors.json: каталоги RAL Classic и NCS для конфигуратора.
// Запуск: node tools/build-colors.js
// Источник - tools/colors-data: ral.txt («Название RAL 1000|hex;...») и ncs.txt («1050-Y90R:hex;...»),
// списки выбора цвета с сайта volhovec.ru (194 RAL Classic и 2050 NCS Index) на 01.10.2026.
const fs = require('fs'), path = require('path');
const rd = function (n) { return fs.readFileSync(path.join(__dirname, 'colors-data', n), 'utf8').trim(); };
const ral = rd('ral.txt').split(';').map(function (s) {
	const p = s.split('|'), m = /^(.*) RAL (\d{4})$/.exec(p[0]);
	return [m[2], m[1], '#' + p[1]];
}).sort(function (a, b) { return a[0] - b[0]; });
const seen = {}, ncs = [];
rd('ncs.txt').split(';').forEach(function (s) {
	const p = s.split(':'), code = 'S ' + p[0];
	if (seen[code]) { return; }
	seen[code] = 1;
	ncs.push([code, p[0].split('-')[1], '#' + p[1]]);
});
fs.writeFileSync(path.join(__dirname, '../public_html/images/konfigurator/colors.json'), JSON.stringify({ ral: ral, ncs: ncs }));
console.log('RAL', ral.length, 'NCS', ncs.length);
