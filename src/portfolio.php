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

// Checks the freshness of a set
$app['smak.portfolio.fresh_flag'] = $app->protect(function(Set $set) use ($app) {

    // Checks if the fresh flag parameter is enabled
    if ($app['smak.portfolio.enable_fresh_flag']) {
        $freshness = new DateTime('now');
        $freshness->sub(new DateInterval($app['smak.portfolio.fresh_flag_interval']));
        $set->is_fresh = ($set->getSplInfo()->getMTime() >= $freshness->getTimestamp());
    }

    return $set;
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
        $set = $app['smak.portfolio.load']($sets->current());

        // If the sets is unloadable
        if (!$set) {

            // Removes the item and continue
            $sets->offsetUnset($sets->key());
            continue;
        }

        // Applies fresh flag
        $app['smak.portfolio.fresh_flag']($set);

        // As ArrayIterator::offsetUnset() resets the pointer, this condition avoids duplicates in the result array
        if (!in_array($set, $results)) {

            // If the set is flagged as fresh, it should appear as first element
            $set->is_fresh ? array_unshift($results, $set) : array_push($results, $set);
        }

        $sets->next();
    }

    // Returns false if there were sets but no ready set to show
    if (empty($results)) {
        return false;
    }

    // Saves sets in session
    $app['session']->set('smak.portfolio.sets', $results);
    return $results;
});


######################
####    ROUTES    ####
######################

// Index
$app->get('/', function() use ($app) {
    $cache_headers = $app['cache.defaults'];
    $sets = $app['smak.portfolio.set_provider']();

    if (!empty($sets)) {

        // This is VERY important to loop on reversed sets to get the lastest last-modified
        foreach (array_reverse($sets) as $set) {

            // If the set is flagged as fresh, updates the last-modified HTTP header
            if ($set->is_fresh) {
                $cache_headers['Last-Modified'] = date('r', $set->getSplInfo()->getMTime());
            }
        }
    }

    // Builds the response
    $response = $app['twig']->render('index.html.twig', array(
        'sets'  => $sets
    ));

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
});

$app->get('/{year}.html', function($year) use ($app) {
    $cache_headers = $app['cache.defaults'];

    // Gets sets and filters for the requested year
    $sets = array_filter($app['smak.portfolio.set_provider'](), function(Set $set) use ($app, $year) {
        return sprintf('/%s', $app->escape($year)) == $set->smak_subpath;
    });

    // Redirects to home page if not sets match the criteria
    if (empty($sets)) {
        return $app->abort(404);
    }

    // This is VERY important to loop on reversed sets to get the lastest last-modified
    foreach (array_reverse($sets) as $set) {

        // If the set is flagged as fresh, updates the last-modified HTTP header
        if ($set->is_fresh) {
            $cache_headers['Last-Modified'] = date('r', $set->getSplInfo()->getMTime());
        }
    }

    // Builds the response
    $response = $app['twig']->render('index.html.twig', array(
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

    if (!$found) {
        return $app->abort(404);
    }

    // If the set is flagged as fresh, updates the last-modified HTTP header
    if ($set->is_fresh) {
        $cache_headers['Last-Modified'] = date('r', $set->getSplInfo()->getMTime());
    }

    // Navigation links generation
    $nav['next'] = 0 < $sets->key() ? $sets[$sets->key() - 1] : null;
    $nav['prev'] = count($sets) > $sets->key() ? $sets[$sets->key() + 1] : null;

    // Builds the response
    $response = $app['twig']->render($set->twig_subpath, array(
        'standalone' => true,
        'set'        => $set,
        'nav'        => $nav
    ));

    // Sends the response   
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
})
->assert('year', '\d{4}')
->assert('set_name', trim($app['smak.portfolio.gallery_pattern'], '/'));

// About page
$app->get('/about.html', function() use ($app) {
    // Caching management
    $cache_headers = $app['cache.defaults'];
    $twig_file = new SplFileInfo($app['twig.path'] . DIRECTORY_SEPARATOR . 'about.html.twig');
    $cache_headers['Last-Modified'] = date('r', $twig_file->getMTime());

    // Builds the response
    $response = $app['twig']->render('about.html.twig');

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $cache_headers);
});

// Contact page (GET)
$app->get('/contact.html', function() use ($app) {
    // Caching management
    $cache_headers = $app['cache.defaults'];
    $twig_file = new SplFileInfo($app['twig.path'] . DIRECTORY_SEPARATOR . 'contact.html.twig');
    $cache_headers['Last-Modified'] = date('r', $twig_file->getMTime());

    // Builds the response
    return $app['twig']->render('contact.html.twig');

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
