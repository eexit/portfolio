<?php
$portfolio = require_once __DIR__ . '/../src/portfolio.php';
$portfolio['debug'] ? $portfolio->run() : $portfolio['http_cache']->run();
exit;
?>