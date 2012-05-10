<?php

require_once __DIR__ . '/bootstrap.php';

use Smak\Portfolio\Collection;
use Smak\Portfolio\Set;
use Smak\Portfolio\SortHelper;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// User info for email
$app['get_client_info'] = $app->protect(function() use ($app) {
    return array(
        'ip'        => $app['request']->server->get('REMOTE_ADDR'),
        'date'      => date('r', $app['request']->server->get('REQUEST_TIME')),
        'agent'     => $app['request']->server->get('HTTP_USER_AGENT')
    );
});

// Twig loader (handles last-mod file + re-compile file if not fresh)
$app['twig.template_loader'] = $app->protect(function($template_name) use ($app) {
    clearstatcache();

    // Gets the cache file modified time
    $template_cache_file = $app['twig']->getCacheFilename($template_name);
    $template_mtime = is_file($template_cache_file) ? filemtime($template_cache_file) : 0;

    // If there is a newer version of the template
    if (false == $app['twig']->isTemplateFresh($template_name, $template_mtime)) {
        // Deletes the cached file
        @unlink($template_cache_file);
        $app['http_cache']->getStore()->purge($app['request']->getUri());
        
        // Compiles and caches the newer version
        $app['twig']->loadTemplate($template_name);
        return time();
    }

    return $template_mtime;
});

// This closure is the core of the application. It fetch all sets and order them in the right way
$app['smak.portfolio.set_provider'] = $app->protect(function() use ($app) {

    // If the debug mod is disabled or and sets are already in session
    if (!$app['debug'] && $app['session']->has('smak.portfolio.sets')) {
        return $app['session']->get('smak.portfolio.sets');
    }

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

    while ($sets->valid()) {

        $set = $sets->current();
        $smak_template = $set->getTemplate();

        // If there is no template file or the set has no content
        if (null == $smak_template || 0 == $set->count()) {
            // Skips it and continue
            $sets->offsetUnset($sets->key());
            continue;
        }

        // Template view helpers
        $set->smak_subpath = dirname(substr($set->getSplInfo()->getRealPath(), strlen(realpath($app['smak.portfolio.content_path']))));
        $set->twig_subpath = sprintf('%s/%s/%s', $set->smak_subpath, $set->getSplInfo()->getBasename(), $smak_template->getBasename());
        
        // Adds a formatted name for routes (suppresses 00- if the set starts by 00-)
        $set->link_name = preg_match('/^00/', $set->name) ?substr($set->name, 3) : $set->name;

        // Template last-mod time
        $template_mtime = $app['twig.template_loader']($set->twig_subpath);

        // Checks if the fresh flag parameter is enabled
        if ($app['smak.portfolio.enable_fresh_flag']) {

            // Set the set fresh if it is
            $freshness = new DateTime('now');
            $freshness->sub(new DateInterval($app['smak.portfolio.fresh_flag_interval']));
            $set->is_fresh = ($set->getTemplate()->getMTime() >= $freshness->getTimestamp());
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


######################
####    ROUTES    ####
######################

// Index page
$app->get('/', function() use ($app) {
    $template_name = 'index.html.twig';
    $cache_headers = $app['cache.defaults'];
    $sets = $app['smak.portfolio.set_provider']();

    // Updates the Last-Modified HTTP header
    $cache_headers['Last-Modified'] = date('r', $app['twig.template_loader']($template_name));

    // Builds the response
    $response = $app['twig']->render($template_name, array(
        'sets'  => $sets
    ));

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
});

// Sets by year page
$app->get('/{year}.html', function($year) use ($app) {
    $template_name = 'index.html.twig';
    $cache_headers = $app['cache.defaults'];

    // Gets sets and filters for the requested year
    $sets = array_filter($app['smak.portfolio.set_provider'](), function(Set $set) use ($app, $year) {
        return sprintf('/%s', $app->escape($year)) == $set->smak_subpath;
    });

    // Redirects to home page if not sets match the criteria
    if (empty($sets)) {
        return $app->abort(404);
    }

    // Updates the Last-Modified HTTP header
    $cache_headers['Last-Modified'] = date('r', $app['twig.template_loader']($template_name));

    // Builds the response
    $response = $app['twig']->render($template_name, array(
        'sets'  => $sets
    ));

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
})->assert('year', '\d{4}');

// Set page
$app->get('/{year}/{set_name}.html', function($year, $set_name) use ($app) {
    $found = false;
    $cache_headers = $app['cache.defaults'];

    // Includes sets in a ArrayIterator to 
    $sets = new ArrayIterator($app['smak.portfolio.set_provider']());

    // No sets available
    if (empty($sets)) {
        return $app->abort(404);
    }

    // Builds set full-name for matching
    $set_path = sprintf('/%s/%s', $app->escape($year), $app->escape($set_name));

    // Loops on available sets
    while ($sets->valid()) {

        // Current loop set
        $set = $sets->current();

        // If the current loop set is the one
        if ($set_path == sprintf('%s/%s', $set->smak_subpath, $set->name)) {
            $found = true;
            break;
        }

        // Keeps looping
        $sets->next();
    }

    // Throws a 404 error if no set found
    if (!$found) {
        return $app->abort(404);
    }

    // Navigation links generation
    $nav['next'] = 0 < $sets->key() ? $sets[$sets->key() - 1] : null;
    $nav['prev'] = count($sets) > $sets->key() ? $sets[$sets->key() + 1] : null;

    // Loads and gets the template age
    $template_age = $app['twig.template_loader']($set->twig_subpath);

    // Updates the Last-Modified HTTP header
    $cache_headers['Last-Modified'] = date('r', $template_age);

    // Builds the response
    $response = $app['twig']->render($set->twig_subpath, array(
        'standalone'    => true,
        'last_mod'      => date('F jS, Y', $template_age),
        'set'           => $set,
        'nav'           => $nav
    ));

    // Sends the response   
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
})
->assert('year', '\d{4}')
->assert('set_name', trim($app['smak.portfolio.gallery_pattern'], '/'))
->convert('set_name', function($set_name) {
    return preg_match('/^\d{2}/', $set_name) ? $set_name : sprintf('00-%s', $set_name);
});

// About page
$app->get('/about.html', function() use ($app) {
    $template_name = 'about.html.twig';
    $cache_headers = $app['cache.defaults'];

    // Loads and gets the template age
    $template_age = $app['twig.template_loader']($template_name);

    // Updates the Last-Modified HTTP header
    $cache_headers['Last-Modified'] = date('r', $template_age);

    // Builds the response
    $response = $app['twig']->render($template_name, array(
        'last_mod'  => date('F jS, Y', $template_age)
    ));

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
});

// Contact page (GET)
$app->get('/contact.html', function() use ($app) {
    $template_name = 'contact.html.twig';
    $cache_headers = $app['cache.defaults'];

    // Updates the Last-Modified HTTP header
    $cache_headers['Last-Modified'] = date('r', $app['twig.template_loader']($template_name));

    // Builds the response
    $response = $app['twig']->render($template_name);

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
});

// Contact page (POST)
$app->post('/contact.html', function() use ($app) {
    $field_data = array(
        'name'      => $app['request']->get('name'),
        'email'     => $app['request']->get('email'),
        'message'   => $app['request']->get('message')
    );
    
    $field_constraints = array(
        'name' => array(
            new Constraints\NotBlank(),
            new Constraints\MinLength(3)
        ),
        'email' => array(
            new Constraints\NotBlank(),
            new Constraints\Email()
        ),
        'message' => array(
            new Constraints\NotBlank()
    ));

    // Loops on field contraints
    foreach ($field_constraints as $field => $constraints) {
        foreach ($constraints as $constraint) {
            // Gets contraint violation
            $violations = $app['validator']->validateValue($field_data[$field], $constraint);
            
            // If there are violation
            if ($violations->count()) {
                foreach ($violations as $violation) {
                    // Appends the violation message to the message stack
                    $violations_messages[$field] = $violation->getMessage();
                }
            }
        }
    }
    
    // Returns to the form including errors
    if (!empty($violations_messages)) {
        return $app['twig']->render('contact.html.twig', array(
            'post'          => $field_data,
            'violations'    => $violations_messages
        ));
    }

    // Prepares the email
    $mail = \Swift_Message::newInstance()
        ->setSubject('New email from the portfolio!')
        ->setSender('no-reply@eexit.net')
        ->setFrom(array(trim($field_data['email']) => trim($field_data['name'])))
        ->setReturnPath(trim($field_data['email']))
        ->setTo(array('photography@eexit.net' => 'Joris Berthelot'))
        ->setCC(((bool) $app['request']->get('copy')) ? array($field_data['email'] => $field_data['name']) : null)
        ->setBody($app['twig']->render('email.html.twig', array(
            'sender'    => $app->escape($field_data['name']),
            'email'     => $app->escape($field_data['email']),
            'message'   => nl2br($app->escape($field_data['message'])),
            'user'      => $app['get_client_info']()
        )), 'text/html');

    // Sends the email
    $app['mailer']->send($mail);

    // Adds send confirmation
    $app['session']->setFlash('notice', 'Your message has been successfully sent!');

    // Redirects to the contact page
    return $app->redirect('/contact.html');
});


return $app;

?>
