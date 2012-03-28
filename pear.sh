#!/bin/sh
rm -f Autoload*.tgz
mkdir -p tmp/TheSeer/Autoload
cp -r src/* tmp/TheSeer/Autoload
cp package.xml tmp
cp phpab.* tmp
cd tmp
pear package
mv Autoload*.tgz ..
cd ..
rm -rf tmp
