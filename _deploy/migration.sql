-- ============================================================
-- КЛЕВЕРДОРС: изменения в базе данных
-- Выполнить в phpMyAdmin на базе cr57261_klever
-- ПЕРЕД ВЫПОЛНЕНИЕМ СДЕЛАТЬ ЭКСПОРТ БАЗЫ (вкладка "Экспорт")
-- ============================================================

-- 1. Пути картинок, переведённых в WebP (главная страница)
UPDATE w2jdq_sppagebuilder SET content =
REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(content,
  'vetrova.png','vetrova.webp'),
  'mobil03.png','mobil03.webp'),
  '6551.png','6551.webp'),
  '0001.jpg','0001.webp'),
  '0002.jpg','0002.webp'),
  '0003.jpeg','0003.webp'),
  '0004.jpeg','0004.webp'),
  '0005.jpg','0005.webp'),
  'fonvangog.jpg','fonvangog.webp'),
  'slide_realta_999.jpg','slide_realta_999.webp')
WHERE id = 5;

-- 2. Удаление мёртвых регистраций иконочных шрифтов (следы взлома).
--    Это 131 запись, ведущая на удалённые файлы: каждая давала
--    лишний запрос с ошибкой 404 при каждом открытии любой страницы.
DELETE FROM w2jdq_sppagebuilder_assets
WHERE type = 'iconfont' AND name <> 'icomoon';

-- 3. Высота шапки на мобильных: 60px -> 50px
UPDATE w2jdq_template_styles
SET params = REPLACE(params, '"header_height_xs":"60px"', '"header_height_xs":"50px"')
WHERE id = 13;

-- 4. Включение отложенной загрузки картинок.
--    Параметра в настройках ещё нет, поэтому добавляем его в начало JSON.
UPDATE w2jdq_template_styles
SET params = CONCAT('{"image_lazy_loading":"1",', SUBSTRING(params, 2))
WHERE id = 13 AND params NOT LIKE '%image_lazy_loading%';

-- Проверка результата:
-- SELECT (content LIKE '%0001.webp%') AS webp_ok FROM w2jdq_sppagebuilder WHERE id=5;
-- SELECT COUNT(*) AS iconfont_ostalos FROM w2jdq_sppagebuilder_assets;  -- должно быть 1
