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

// Application error handling
$app->error(function(\Exception $e) use ($app) {
    if ($e instanceof NotFoundHttpException) {
        $content = vsprintf('<h1>%d - %s (%s)</h1>', array(
           $e->getStatusCode(),
           Response::$statusTexts[$e->getStatusCode()],
           $app['request']->getRequestUri()
        ));
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
});

$app->get('/about.html', function() use ($app) {
    return $app['twig']->render('about.twig');
});

$app->get('/contact.html', function() use ($app) {
    return $app['twig']->render('contact.twig');
});

$app->get('/sets.html', function() use ($app) {
    return $app['twig']->render('sets.twig');
});

$app->get('/{set}.html', function($set) use ($app) {
    return $app->escape($set);
});

$app->get('/{set}/{gallery}.html', function($set, $gallery) use ($app) {
    return $app->escape($set) . '/' . $app->escape($gallery);
});

return $app;
?>