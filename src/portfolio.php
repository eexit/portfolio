<?php
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

require_once __DIR__ . '/../vendor/Silex/silex.phar';

$app = new Silex\Application();

$app->register(new Silex\Extension\TwigExtension(), array(
   'twig.class_path'    => __DIR__ . '/../vendor/Twig/lib',
   'twig.path'          => __DIR__ . '/views'
));

$app->register(new Silex\Extension\MonologExtension(), array(
    'monolog.class_path'    => __DIR__ . '/../vendor/monolog/src',
    'monolog.logfile'       => __DIR__ . '/../log/portfolio.log',
    'monolog.name'          => 'portfolio',
    'monolog.level'         => 300
));

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
        return new Response('<p>You should go eat some cookies while we\'re fixing this feature!', $e->getStatusCode());
    }
});

$app->get('/', function() use ($app) {
    return $app['twig']->render('index.twig', array(
        'hello' => 'Hello world!'
    ));
});

return $app;
?>