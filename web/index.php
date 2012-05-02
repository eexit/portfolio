<?php
$portfolio = require_once __DIR__ . '/../src/portfolio.php';
if ($portfolio['debug']) {
    $portfolio->run();    
} else {
    $portfolio['http_cache']->run();
}
?>