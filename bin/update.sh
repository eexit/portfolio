#!/bin/sh
path=`pwd`
vendor=$path/vendor
cd /tmp
curl http://twitter.github.com/bootstrap/assets/bootstrap.zip -o bootstrap.zip
unzip -oq bootstrap.zip
cp -f bootstrap/css/bootstrap.min.css $path/web/ui/styles/src/bootstrap.min.css
cd $path
git status