#!/usr/bin/env python3
"""
Уменьшенные копии больших фото для srcset.

Рядом с каждым WebP шире 1000px и тяжелее 40 КБ кладёт <файл>.w480/.w800/
.w1200.webp (только ширины меньше исходника). Шаблон (templates/agentik/
index.php, onAfterRender) сам находит эти копии и добавляет картинкам
data-srcset - lazysizes берёт файл по реальной ширине на странице.
Исходником служит JPEG/PNG рядом (файл.jpg для файл.jpg.webp), если он есть.
Уже готовые и не устаревшие копии пропускаются, так что можно запускать
после каждой заливки новых фото.

Запуск из public_html:  python3 ../tools/make-image-variants.py images
Нужны cwebp и webpinfo (brew install webp).
"""
import os, re, subprocess, sys
root = sys.argv[1]
WIDTHS = (480, 800, 1200)
made = skipped = 0
saved_src = 0; saved_var = 0
for dp, dn, fn in os.walk(root):
    for f in fn:
        if not f.endswith('.webp') or re.search(r'\.w\d+\.webp$', f):
            continue
        p = os.path.join(dp, f)
        if os.path.getsize(p) < 40 * 1024:
            continue
        info = subprocess.run(['webpinfo', '-quiet', '-summary', p], capture_output=True, text=True).stdout
        m = re.search(r'Width: (\d+)\s+Height: (\d+)', info) or re.search(r'Canvas size (\d+) x (\d+)', info)
        if not m:
            info = subprocess.run(['webpinfo', p], capture_output=True, text=True).stdout
            m = re.search(r'Width: (\d+)\s*\n\s*Height: (\d+)', info) or re.search(r'Canvas size (\d+) x (\d+)', info)
        if not m:
            print('?? dims', p); continue
        w = int(m.group(1))
        if w < 1000:
            continue
        base = p[:-5]
        src = p
        for ext in ('.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG'):
            if base.endswith(ext) and os.path.isfile(base):
                src = base
        for vw in WIDTHS:
            if vw > w * 0.85:
                continue
            out = base + '.w%d.webp' % vw
            if os.path.isfile(out) and os.path.getmtime(out) >= os.path.getmtime(p):
                skipped += 1; continue
            r = subprocess.run(['cwebp', '-quiet', '-q', '82', '-m', '6', '-sharp_yuv', '-metadata', 'icc', '-resize', str(vw), '0', src, '-o', out], capture_output=True, text=True)
            if r.returncode:
                print('ERR', p, r.stderr[:200]); continue
            made += 1
print('made', made, 'skipped', skipped)
