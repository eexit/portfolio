<?php

// Bootstraping
require_once __DIR__ . '/../vendor/Silex/silex.phar';
$app = new Silex\Application();
$app['debug'] = true;

$app['autoloader']->registerNamespaces(array(
    'Symfony'   =>  __DIR__ . '/../vendor/Symfony/src',
    'Smak'      =>  __DIR__ . '/../vendor/Smak/lib'
));

// Useful namespaces
use Smak\Portfolio\Provider\SilexServiceProvider;

use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;

use Symfony\Component\Validator\Constraints;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app->register(new SilexServiceProvider(), array(
    'smak.portfolio.content_repository'   => __DIR__ . '/../web/content'
));

// Registers Symfony Validator component extension
$app->register(new ValidatorServiceProvider(), array(
   'validator.class_path'   => __DIR__ . '/../vendor/Symfony/src' 
));

// Registers Twig extension
$app->register(new TwigServiceProvider(), array(
   'twig.class_path'    => __DIR__ . '/../vendor/Twig/lib',
   'twig.path'          => __DIR__ . '/views',
   'twig.options'       => array('strict_variables' => false)
));

// Registers Monolog extension
$app->register(new MonologServiceProvider(), array(
    'monolog.class_path'    => __DIR__ . '/../vendor/monolog/src',
    'monolog.logfile'       => __DIR__ . '/../log/portfolio.log',
    'monolog.name'          => 'portfolio',
    'monolog.level'         => 300
));

$app->register(new SwiftmailerServiceProvider(), array(
    'swiftmailer.class_path'    => __DIR__ . '/../vendor/swiftmailer/lib/classes'
));

$app['swiftmailer.transport'] = \Swift_SmtpTransport::newInstance('smtp.bbox.fr', 25);

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