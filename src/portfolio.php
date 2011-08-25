<?php
use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Bootstraping
require_once __DIR__ . '/../vendor/Silex/silex.phar';
$app = new Silex\Application();

// Registers Symfony Validator component extension
$app->register(new Silex\Extension\ValidatorExtension(), array(
   'validator.class_path'   => __DIR__ . '/../vendor/Symfony/src' 
));

// Registers Twig extension
$app->register(new Silex\Extension\TwigExtension(), array(
   'twig.class_path'    => __DIR__ . '/../vendor/Twig/lib',
   'twig.path'          => __DIR__ . '/views'
));

// Registers Monolog extension
$app->register(new Silex\Extension\MonologExtension(), array(
    'monolog.class_path'    => __DIR__ . '/../vendor/monolog/src',
    'monolog.logfile'       => __DIR__ . '/../log/portfolio.log',
    'monolog.name'          => 'portfolio',
    'monolog.level'         => 300
));

$app['debug'] = true;

// Application error handling
$app->error(function(\Exception $e) use ($app) {
    if ($e instanceof NotFoundHttpException) {
        $content = sprintf('<h1>%d - %s (%s)</h1>',
            $e->getStatusCode(),
            Response::$statusTexts[$e->getStatusCode()],
            $app['request']->getRequestUri()
        );
        return new Response($content, $e->getStatusCode());
    }
    
    if ($e instanceof HttpException) {
        return new Response('<h1>Oops!</h1><h2>Something went wrong...</h2><p>You should go eat some cookies while we\'re fixing this feature!</p>', $e->getStatusCode());
    }
});

// ======================
// = Route declarations =
// ======================

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.twig');
})->bind('home');

$app->get('/about.html', function() use ($app) {
    return $app['twig']->render('about.twig');
})->bind('about');

$app->get('/contact.html', function() use ($app) {
    return $app['twig']->render('contact.twig');
})->bind('contact');

$app->post('/contact.html', function() use ($app) {
    $field_data = array(
        'name'      => $app['request']->get('name'),
        'email'     => $app['request']->get('email'),
        'message'   => $app['request']->get('message')
    );
    $field_constraints = array(
        'name'      => array(
            new Constraints\NotBlank(),
            new Constraints\MinLength(3)
        ),
        'email'     => array(
            new Constraints\NotBlank(),
            new Constraints\Email()
        ),
        'message'   => array(
            new Constraints\NotBlank()
        )
    );

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
        return $app['twig']->render('contact.twig', array(
            'post'          => $field_data,
            'violations'    => $violations_messages
        ));   
    }
    
    /*
        TODO 
    */
    
    return $app['twig']->render('contact.twig', array(
        'confirmation' => 'Your message has been successfully sent!'
    ));
});

$app->get('/sets.html', function() use ($app) {
    return $app['twig']->render('sets.twig');
})->bind('sets');

$app->get('/{set}.html', function($set) use ($app) {
    return $app->escape($set);
});

$app->get('/{set}/{gallery}.html', function($set, $gallery) use ($app) {
    return $app->escape($set) . '/' . $app->escape($gallery);
});

return $app;
?>