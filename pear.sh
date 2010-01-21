#!/bin/sh
rm -f Autoload*.tgz
mkdir -p tmp/TheSeer/Autoload
cp -r src/* tmp/TheSeer/Autoload
cp package.xml tmp
cp phpab.bat tmp
cd tmp
sed -e "s@require __DIR__ . '/src/@require 'TheSeer/Autoload/@" ../phpab.php > phpab.php
pear package
mv Autoload*.tgz ..
cd ..
rm -rf tmp
