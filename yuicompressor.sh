#!/bin/sh
path=`pwd`
compressor=$path/vendor/yuicompressor/yuicompressor-2.4.7.jar
css_path=$path/web/ui/styles
js_path=$path/web/ui/js
echo "Portfolio UI Compression"
echo "------------------------"
# CSS Compression
rm -rf $css_path/portfolio*
cat $css_path/src/bootstrap.min.css >> $css_path/portfolio-min.css.tmp
cat $css_path/src/portfolio.css >> $css_path/portfolio-min.css.tmp
java -jar $compressor --type css -o $css_path/portfolio-min.css $css_path/portfolio-min.css.tmp
java -jar $compressor --type css -o $css_path/portfolio-ns-min.css $css_path/src/portfolio-ns.css
rm -rf $css_path/*.tmp
echo "[ OK ] CSS Compression"
# JS Compression
rm -rf $js_path/portfolio*
cat $js_path/src/jquery.min.js >> $js_path/portfolio-min.js.tmp
cat $js_path/src/jquery.tools.min.js >> $js_path/portfolio-min.js.tmp
cat $js_path/src/jquery.mousewheel.js >> $js_path/portfolio-min.js.tmp
cat $js_path/src/jquery.getscrollbarwidth.js >> $js_path/portfolio-min.js.tmp
cat $js_path/src/jquery.sizes.min.js >> $js_path/portfolio-min.js.tmp
#cat $js_path/src/jquery.lazyload.min.js >> $js_path/portfolio-min.js.tmp
cat $js_path/src/jquery.easing.1.3.js >> $js_path/portfolio-min.js.tmp
cat $js_path/src/portfolio.js >> $js_path/portfolio-min.js.tmp
java -jar $compressor --type js -o $js_path/portfolio-min.js $js_path/portfolio-min.js.tmp
rm -rf $js_path/*.tmp
echo "[ OK ] JS Compression"