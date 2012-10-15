#!/bin/sh
rm -f Autoload*.tgz
mkdir -p tmp/TheSeer/Autoload
cp -r src/* tmp/TheSeer/Autoload
cp package.xml phpunit.xml.dist LICENSE README.md tmp
cp -r tests tmp
cp phpab.* tmp
cd tmp
php ../../DirectoryScanner/samples/pear-package.php ../package.xml . | xmllint --format - > package.xml
pear package
mv Autoload*.tgz ..
cd ..
rm -rf tmp
