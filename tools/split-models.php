<?php
/*
 * Разбивает images/konfigurator/models.json на файлы по коллекциям
 * (images/konfigurator/models/<коллекция>.json): страница коллекции грузит
 * только свои модели, а не все 13 коллекций (было ~100 КБ на каждую).
 * Запуск: php tools/split-models.php (вызывается и редактором стёкол при
 * сохранении - router.php). Полный models.json остаётся запасным.
 */
function kdcSplitModels($root)
{
    $dir  = $root . '/public_html/images/konfigurator';
    $data = json_decode((string) @file_get_contents($dir . '/models.json'), true);

    if (!is_array($data) || empty($data['collections']))
    {
        return 0;
    }

    @mkdir($dir . '/models', 0777, true);

    foreach (glob($dir . '/models/*.json') ?: [] as $old)
    {
        unlink($old);
    }

    $n = 0;

    foreach ($data['collections'] as $alias => $col)
    {
        $out = $data;
        $out['collections'] = [$alias => $col];
        file_put_contents($dir . '/models/' . $alias . '.json', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $n++;
    }

    return $n;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['argv'][0] ?? '') === __FILE__)
{
    echo kdcSplitModels(dirname(__DIR__)), " collections\n";
}
