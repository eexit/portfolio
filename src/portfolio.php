<?php

require_once __DIR__ . '/bootstrap.php';

use Smak\Portfolio\Collection;
use Smak\Portfolio\Set;
use Smak\Portfolio\SortHelper;

//  ===========
//  = HELPERS =
//  ===========

// User info for email
$app['get_client_info'] = $app->protect(function() use ($app) {
    return array(
        'ip'    => $app['request']->server->get('REMOTE_ADDR'),
        'date'  => date('r', $app['request']->server->get('REQUEST_TIME')),
        'agent' => $app['request']->server->get('HTTP_USER_AGENT')
    );
});

// Twig loader (handles last-mod file + re-compile file if not fresh)
$app['twig.template_loader'] = $app->protect(function($template_name) use ($app) {

    // Returns immediately the current time when in debug mod
    if ($app['debug']) {
        return;
    }

    // Gets the cache file and its modified time
    $cache      = $app['twig']->getCacheFilename($template_name);
    $cache_time = is_file($cache) ? filectime($cache) : 0;

    // If there is a newer version of the template
    if (false === $app['twig']->isTemplateFresh($template_name, $cache_time)) {

        // Deletes the cached file
        @unlink($cache);

        // Flushes the application HTTP cache for the current URI
        $app['http_cache']->getStore()->invalidate($app['request']);
    }
});

$app['portfolio.template_http_cache_helper'] = $app->protect(function($template_name) use ($app) {
    $template_abspath = $app['twig.content_path'] . DIRECTORY_SEPARATOR . $template_name;
    $cache_headers['Last-Modified'] = date('r', filemtime($template_abspath));
});

// This closure is the core of the application. It fetch all sets and order them in the right way
$app['smak.portfolio.set_provider'] = $app->protect(function() use ($app) {

    // Result set
    $results = array();

    // Gets sets
    $sets = $app['smak.portfolio']
        ->name($app['smak.portfolio.gallery_pattern'])
        ->sort(SortHelper::reverseName())
        ->getAll();

    // Return false is there is nothing to show
    if (empty($sets)) {
        return null;
    }

    // If the debug mod is disabled or and sets are already in session
    if (!$app['debug'] && $app['session']->has('smak.portfolio.sets')) {
        $session_sets = $app['session']->get('smak.portfolio.sets');
        if (count($sets) == count($session_sets)) {
            return $session_sets;
        }
    }

    clearstatcache();

    while ($sets->valid()) {

        $set           = $sets->current();
        $smak_template = $set->getTemplate();

        // If there is no template file or the set has no content
        if (null == $smak_template || 0 == $set->count()) {
            // Skips it and continue
            $sets->offsetUnset($sets->key());
            continue;
        }

        // Template view helpers
        $set->smak_subpath   = dirname(substr($set->getSplInfo()->getRealPath(), strlen(realpath($app['smak.portfolio.content_path']))));
        $set->twig_subpath   = sprintf('%s/%s/%s', $set->smak_subpath, $set->getSplInfo()->getBasename(), $smak_template->getBasename());
        $set->template_mtime = filemtime($app['smak.portfolio.content_path'] . $set->twig_subpath);
        
        // Adds a formatted name for routes (suppresses 00- if the set starts by 00-)
        $set->link_name      = preg_match('/^00/', $set->name) ?substr($set->name, 3) : $set->name;

        // Checks if the fresh flag parameter is enabled
        if ($app['smak.portfolio.enable_fresh_flag']) {

            // Set the set fresh if it is
            $freshness = new DateTime('now');
            $freshness->sub(new DateInterval($app['smak.portfolio.fresh_flag_interval']));
            $set->is_fresh = ($smak_template->getMTime() >= $freshness->getTimestamp());
        }

        // As ArrayIterator::offsetUnset() resets the pointer, this condition avoids duplicates in the result array
        if (!in_array($set, $results)) {

            // If the set is flagged as fresh, it should appear as first element
            $set->is_fresh ? array_unshift($results, $set) : array_push($results, $set);
        }

        $sets->next();
    }

    // Saves sets in session
    $app['session']->set('smak.portfolio.sets', $results);
    $app['twig']->clearCacheFiles();
    $app['twig']->clearTemplateCache();
    $app['http_cache']->getStore()->cleanup();

    return $results;
});


//  ======================
//  = Application routes =
//  ======================

$app->mount('/', include 'frontend.routes.php');

// $app->mount('/b', include 'backend.routes.php');

return $app;
