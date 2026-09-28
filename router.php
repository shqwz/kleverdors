<?php
/**
 * Router for PHP's built-in dev server (php -S ... router.php).
 *
 * Two jobs:
 *
 * 1. Static files are served with no-cache headers. The built-in server
 *    sends no cache headers at all, and the template links custom.css /
 *    template.css without any ?version parameter, so a phone testing over
 *    the LAN will happily keep showing a stale stylesheet after an edit.
 *
 * 2. Everything else goes through Joomla's front controller, so SEF URLs
 *    behave roughly like they do behind nginx in production.
 */

// Don't use parse_url() here: Helix's lazy-loading builds asset URLs as
// Uri::base() . '/images/...', which yields a doubled slash ("…:8000//images/…").
// parse_url() reads a leading "//" as a protocol-relative URL and hands back
// "images" as the HOST, dropping it from the path - so every lazy-loaded image
// 404s. Split the query off by hand and collapse the leading slashes instead.
$uri = $_SERVER['REQUEST_URI'];
$queryPos = strpos($uri, '?');
$path = $queryPos === false ? $uri : substr($uri, 0, $queryPos);
$path = '/' . ltrim($path, '/');

// Decode percent-escapes before touching the filesystem - some assets have
// spaces in their path (e.g. the "Instrument Serif" webfont), and matching the
// raw "%20" form against disk makes them 404.
$file = __DIR__ . '/public_html' . rawurldecode($path);

// Редактор стёкол конфигуратора (tools/glass-editor.html) и его сохранение.
// Есть только здесь, в локальном роутере: на хостинг не попадает.
if ($path === '/__kdc/glass-editor') {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    // Скрипт конфигуратора - с датой правки в адресе, иначе браузер держит
    // старую копию и редактор показывает не то, что сайт.
    $cfgV = filemtime(__DIR__ . '/public_html/templates/agentik/js/kdc-configurator.js');
    echo str_replace('kdc-configurator.js?editor', 'kdc-configurator.js?editor=' . $cfgV, file_get_contents(__DIR__ . '/tools/glass-editor.html'));
    return true;
}
if ($path === '/__kdc/save-models') {
    header('Content-Type: application/json; charset=utf-8');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $local = $ip === '127.0.0.1' || $ip === '::1' || preg_match('~^(192\.168|10)\.~', $ip);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$local) {
        http_response_code(403);
        echo '{"ok":false,"error":"forbidden"}';
        return true;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data) || !isset($data['collections']) || !is_array($data['collections'])) {
        http_response_code(400);
        echo '{"ok":false,"error":"bad json"}';
        return true;
    }
    $models = __DIR__ . '/public_html/images/konfigurator/models.json';
    // Редактор открыт давно, а файл с тех пор менялся (другая вкладка,
    // правка вручную) - не затираем, просим обновить.
    clearstatcache();
    $base = $_SERVER['HTTP_X_KDC_BASE'] ?? '';
    if ($base !== '' && $base !== gmdate('D, d M Y H:i:s', filemtime($models)) . ' GMT') {
        http_response_code(409);
        echo '{"ok":false,"error":"stale"}';
        return true;
    }
    $backups = __DIR__ . '/_backups/models';
    if (!is_dir($backups)) {
        mkdir($backups, 0777, true);
    }
    copy($models, $backups . '/models-' . date('Ymd-His') . '.json');
    file_put_contents($models, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

    // Браузеры (и nginx на хостинге) держат старые копии: поднимаем VERSION
    // в конфигураторе (models.json?v=) и ?v= у самого скрипта в custom.js.
    $cfgJs = __DIR__ . '/public_html/templates/agentik/js/kdc-configurator.js';
    $js = file_get_contents($cfgJs);
    $js = preg_replace_callback("~var VERSION = '(\d+)';~", function ($m) { return "var VERSION = '" . ($m[1] + 1) . "';"; }, $js, 1);
    file_put_contents($cfgJs, $js);
    $customJs = __DIR__ . '/public_html/templates/agentik/js/custom.js';
    $cj = file_get_contents($customJs);
    $cj = preg_replace_callback('~kdc-configurator\.js\?v=(\d+)~', function ($m) { return 'kdc-configurator.js?v=' . ($m[1] + 1); }, $cj, 1);
    file_put_contents($customJs, $cj);
    foreach (glob(__DIR__ . '/public_html/cache/com_templates/templates/agentik/*.js') ?: [] as $f) {
        unlink($f);
    }
    clearstatcache();
    echo json_encode(['ok' => true, 'mtime' => gmdate('D, d M Y H:i:s', filemtime($models)) . ' GMT']);
    return true;
}

if ($path !== '/' && is_file($file)) {
    // Only the two files we actually hand-edit are served through PHP with
    // no-cache. Everything else (the homepage alone pulls 183 CSS/JS files,
    // plus images and fonts) goes out through the built-in server's own fast
    // static path and stays cacheable - routing all of it through PHP with
    // no-store made every page load re-download ~180 files, which is what
    // made the site crawl on a phone.
    static $noCache = [
        '/templates/agentik/css/custom.css' => 'text/css',
        '/templates/agentik/js/custom.js'   => 'application/javascript',
        // Редактор стёкол сверяет дату файла перед сохранением.
        '/images/konfigurator/models.json'  => 'application/json',
    ];

    // Helix склеивает все скрипты шаблона в один файл в cache/com_templates,
    // и имя этого файла - хэш от ИМЁН исходников, а не от их содержимого.
    // После правки custom.js адрес не меняется, и браузер продолжает крутить
    // старую копию из своего кэша. Раздаём бандл без кэширования - иначе
    // проверять изменения приходится с очисткой кэша вручную.
    $type = isset($noCache[$path]) ? $noCache[$path] : null;

    if ($type === null && strpos($path, '/cache/com_templates/templates/') === 0) {
        $type = substr($path, -3) === '.js' ? 'application/javascript' : 'text/css';
    }

    if ($type === null) {
        return false;
    }

    header('Content-Type: ' . $type);
    header('Cache-Control: no-cache, must-revalidate');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
    readfile($file);

    return true;
}

// A missing file that clearly asks for a static asset must 404, not fall through
// to Joomla - otherwise every dead asset URL answers with the full 330KB homepage,
// which silently wrecks any page-weight measurement.
if (preg_match('~\.(css|js|jpe?g|png|gif|webp|svg|ico|woff2?|ttf|eot|map|mp4|webm)$~i', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "404";
    return true;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public_html/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__ . '/public_html/index.php';
