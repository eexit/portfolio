<?php

// Bootstraping
require_once __DIR__ . '/../vendor/Silex/silex.phar';
date_default_timezone_set('America/New_York');
$app = new Silex\Application();
$app['domain']  = 'http://local.dev.photo.eexit';
$app['smak.portfolio.enable_fresh_flag'] = true;
$app['smak.portfolio.fresh_flag_interval'] = 'P3D';
$app['smak.portfolio.gallery_pattern'] = '/(\d{2})?([-[:alpha:]]+)/';

$app['cache.dir'] = __DIR__ . '/../cache';
$app['cache.max_age'] = 3600 * 24 * 10;
$app['cache.expires'] = 3600 * 24 * 10;

$app['debug'] = true;

$app['autoloader']->registerNamespaces(array(
    'Symfony'   =>  __DIR__ . '/../vendor/Symfony/src',
    'Smak'      =>  __DIR__ . '/../vendor/Smak/lib'
));

// Useful namespaces
use Smak\Portfolio\Silex\Provider\SmakServiceProvider;

use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Registers Symfony Session component extension
$app->register(new SessionServiceProvider());
$app['session']->start();

$app->register(new HttpCacheServiceProvider(), array(
    'http_cache.cache_dir'  => $app['cache.dir']
));

// Registers Symfony Validator component extension
$app->register(new ValidatorServiceProvider(), array(
   'validator.class_path'   => __DIR__ . '/../vendor/Symfony/src' 
));

// Registers Twig extension
$app->register(new TwigServiceProvider(), array(
    'twig.class_path'       => __DIR__ . '/../vendor/Twig/lib',
    'twig.path'             => __DIR__ . '/views',
    'twig.options'          => array(
        'strict_variables'  => false,
        //'cache'             => $app['cache.dir']
    )
));

// Registers Smak Portfolio extension
$app->register(new SmakServiceProvider(), array(
    'smak.portfolio.content_path' => __DIR__ . '/../web/content',
    'smak.portfolio.public_path'  => $app['domain'] . '/content',
    'smak.portfolio.view_path'    => $app['twig.path'] . '/content_views'
));

// Registers Monolog extension
$app->register(new MonologServiceProvider(), array(
    'monolog.class_path'    => __DIR__ . '/../vendor/monolog/src',
    'monolog.logfile'       => __DIR__ . '/../log/portfolio.log',
    'monolog.name'          => 'portfolio',
    'monolog.level'         => 300
));

// Registers Swiftmailer extension
$app->register(new SwiftmailerServiceProvider(), array(
    'swiftmailer.class_path'    => __DIR__ . '/../vendor/swiftmailer/lib/classes'
));


$app['twig']->addGlobal('domain', $app['domain']);
$app['twig']->addGlobal('smak_public_path', $app['smak.portfolio.public_path']);
//$app['swiftmailer.transport'] = \Swift_SmtpTransport::newInstance('smtp.bbox.fr', 25);
$app['cache.defaults'] = array(
    'Cache-Control'     => sprintf('public, max-age=%d, s-maxage=%d, must-revalidate, proxy-revalidate', $app['cache.max_age'], $app['cache.max_age']),
    'Expires'           => date('r', time() + $app['cache.expires'])
);

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