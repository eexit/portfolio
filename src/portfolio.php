<?php

require_once __DIR__ . '/bootstrap.php';

use Smak\Portfolio\Collection;
use Smak\Portfolio\Set;
use Smak\Portfolio\SortHelper;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Checks the freshness of a set
$app['smak.portfolio.fresh_flag'] = $app->protect(function(Set $set) use ($app) {
    if ($app['smak.portfolio.enable_fresh_flag']) {
        $freshness = new DateTime('now');
        $freshness->sub(new DateInterval($app['smak.portfolio.fresh_flag_interval']));
        $set->is_fresh = ($set->getSplInfo()->getMTime() >= $freshness->getTimestamp());
    }
    return $set;
});

// Custom sorting method
$app['smak.portfolio.custom_sort'] = $app->protect(function(Set $a, Set $b) {
    return preg_match('/^\d{2}/', $b->getSplInfo()->getBasename()) ? 1
        : !strncasecmp($a->getSplInfo()->getBasename(), $b->getSplInfo()->getBasename(), 2);
});

// This closure is the core of the application. It fetch all sets and order them in the right way
$app['smak.portfolio.set_provider'] = $app->protect(function($name_filter = null) use ($app) {

    // If the debug mod is disabled or and sets are already in session
    if (!$app['debug'] && $app['session']->has('smak.portfolio.sets')) {
        return $app['session']->get('smak.portfolio.sets');
    }

    // Results will go there
    $sets = array();

    // Gets first level collections
    $smak_service = $app['smak.portfolio'];

    if (null !== $name_filter) {
        $smak_service->name($name_filter);
    }

    // Gets collections
    $collections = $smak_service->sort(SortHelper::reverse())->getAll();

    // Return false is there is nothing to show
    if (empty($collections)) {
        return false;
    }

    // Loops on  first level collections
    foreach ($collections as $set) {
        // Creates a second level collection from each first level collection
        $collections = $set->asCollection()
            ->name($app['smak.portfolio.gallery_pattern'])
            ->sort(SortHelper::reverse())
            ->getAll();

        // Special ordering, I want unspecified date to be older than specified
        $collections->uasort($app['smak.portfolio.custom_sort']);

        // Loops on sets in second level collection
        foreach ($collections as $set) {

            // If the set is ready and updated
            if ($set = $app['smak.portfolio.load']($set)) {

                // Flag the set with freshness tag if considered as fresh
                $app['smak.portfolio.fresh_flag']($set);

                // If the set is flagged as fresh, it should appear as first element
                if ($set->is_fresh) {
                    array_unshift($sets, $set);
                    // Removes the set from the collection to avoid duplicate
                    unset($collections[$collections->key()]);
                } else {
                    $collections[$collections->key()] = $set;
                }
            }
        }

        // Appends sets to results
        $sets = array_merge($sets, $collections->getArrayCopy());
    }

    // Returns false if there were collections but no ready set to show
    if (empty($sets)) {
        return false;
    }

    // Saves sets in session
    $app['session']->set('smak.portfolio.sets', $sets);
    return $sets;
});

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
})->bind('home');

// About page
$app->get('/about.html', function() use ($app) {
    // Builds the response
    $response = $app['twig']->render('about.html.twig');

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $app['cache.defaults']);
})->bind('about');

// Contact page (GET)
$app->get('/contact.html', function() use ($app) {
    // Builds the response
    $response = $app['twig']->render('contact.html.twig');

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $app['cache.defaults']);
})->bind('contact');

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
    
    if (!empty($violations_messages)) {
        return $app['twig']->render('contact.html.twig', array(
            'post'          => $field_data,
            'violations'    => $violations_messages
        ));
    }

    /*
    $mail = \Swift_Message::newInstance()
        ->setSubject(trim($field_data['name']) . ' sent a message from the portfolio')
        ->setSender('no-reply@eexit.net')
        ->setFrom(array(trim($field_data['email']) => trim($field_data['name'])))
        ->setReturnPath(trim($field_data['email']))
        ->setTo(array('photography@eexit.net' => 'Joris Berthelot'))
        //->setCC(array($field_data->email => $field_data->name))
        ->setBody($app['twig']->render('email.twig', array(
            'sender'    => $field_data['name'],
            'email'     => $field_data['email'],
            'message'   => $field_data['message']
        )), 'text/html');
        
    $app['mailer']->send($mail);
    */

    $app['session']->setFlash('notice', 'Your message has been successfully sent!');
    return $app->redirect('/contact.html');
});

$app->get('/{year}.html', function($year) use ($app) {
    $cache_headers = $app['cache.defaults'];
    $sets = $app['smak.portfolio.set_provider'](sprintf('/^%s/', $app->escape($year)));

    if (!empty($sets)) {

        // This is VERY important to loop on reversed sets to get the lastest last-modified
        foreach (array_reverse($sets) as $set) {

            // If the set is flagged as fresh, updates the last-modified HTTP header
            if ($set->is_fresh) {
                $cache_headers['Last-Modified'] = date('r', $set->getSplInfo()->getMTime());
                $app['cache.defaults'] = $cache_headers;
            }
        }
    }

    // Builds the response
    $response = $app['twig']->render('index.html.twig', array(
        'sets'  => $sets
    ));

    // Sends the response
    return new Response($response, 200, $app['debug'] ? array() : $app['cache.defaults']);
})->assert('year', '\d{4}');

// Set page
$app->get('/{year}/{set_name}.html', function($year, $set_name) use ($app) {
    $cache_headers = $app['cache.defaults'];

    // Includes sets in a ArrayIterator to 
    $sets = new ArrayIterator($app['smak.portfolio.set_provider']());

    // Loops on available sets
    foreach ($sets as $set) {
        if ($set->name == $app->escape($set_name)
            && sprintf('/%s', $app->escape($year)) == $set->smak_subpath) {

            // If the set is flagged as fresh, updates the last-modified HTTP header
            if ($set->is_fresh) {
                $cache_headers['Last-Modified'] = date('r', $set->getSplInfo()->getMTime());
                $app['cache.defaults'] = $cache_headers;
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
            return new Response($response, 200, $app['cache.defaults']);
        }
    }
    
    // TODO -> implements 404
    return $app->redirect('/');
})
->assert('year', '\d{4}')
->assert('set_name', trim($app['smak.portfolio.gallery_pattern'], '/'));

return $app;
?>
