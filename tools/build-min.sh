#!/bin/sh
# Сжатые копии custom.css -> css/custom.min.css и js/kdc-configurator.js -> js/kdc-configurator.min.js
# (public_html/templates/agentik). Шаблон (index.php) отдаёт каждую вместо исходника, пока она не старше исходника, - после правки
# custom.css / kdc-configurator.js запустите этот скрипт или просто ничего не делайте: старая копия игнорируется.
# Запуск: sh tools/build-min.sh   (нужен интернет при первом запуске - npx скачает csso-cli и terser)
cd "$(dirname "$0")/../public_html/templates/agentik/css" || exit 1
npx --yes csso-cli custom.css --no-restructure --comments none --output custom.min.css && ls -la custom.css custom.min.css
cd ../js || exit 1
npx --yes terser kdc-configurator.js -c -m --comments false -o kdc-configurator.min.js && ls -la kdc-configurator.js kdc-configurator.min.js
