<?php
$portfolio = require_once __DIR__ . '/../src/portfolio.php';
if ($portfolio['debug']) {
    $portfolio['twig']->clearCacheFiles();
    $portfolio['http_cache']->getStore()->cleanup();
    $portfolio->run();
} else {
    $portfolio['http_cache']->run();
}
?>