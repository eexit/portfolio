<?php

// Bootstraping
require_once __DIR__ . '/../vendor/Silex/silex.phar';
date_default_timezone_set('America/New_York');
$app = new Silex\Application();

$app['debug'] = true;
$app['smak.portfolio.enable_fresh_flag'] = false;
$app['cache.max_age'] = 3600 * 24 * 10;
$app['cache.expires'] = 3600 * 24 * 10;
$app['domain']  = 'http://local.dev.photo.eexit';
$app['smpt.domain'] = 'mail.optonline.net';
$app['smpt.port'] = 25;
$app['smak.portfolio.fresh_flag_interval'] = 'P5D';
$app['smak.portfolio.gallery_pattern'] = '/(\d{2})?([-[:alpha:]]+)/';
$app['cache.dir'] = __DIR__ . '/../cache';

$app['autoloader']->registerNamespaces(array(
    'Symfony'   =>  __DIR__ . '/../vendor/Symfony/src',
    'Smak'      =>  __DIR__ . '/../vendor/Smak/lib'
));

// Namespaces
use Smak\Portfolio\Silex\Provider\SmakServiceProvider;

use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;

use Symfony\Component\HttpFoundation\Response;

// Registers Symfony Session component extension
$app->register(new SessionServiceProvider());
$app['session']->start();

// Registers Symfony Cache component extension
$app->register(new HttpCacheServiceProvider(), array(
    'http_cache.cache_dir'  => $app['cache.dir'],
    'http_cache.options'    => array(
        'allow_reload'      => true,
        'allow_revalidate'  => true
)));

// Registers Symfony Validator component extension
$app->register(new ValidatorServiceProvider(), array(
   'validator.class_path'   => __DIR__ . '/../vendor/Symfony/src' 
));

// Registers Twig extension
$app->register(new TwigServiceProvider(), array(
    'twig.class_path'       => __DIR__ . '/../vendor/Twig/lib',
    'twig.path'             => array(
        __DIR__ . '/views',
        __DIR__ . '/../web/content'
    ),
    'twig.options'          => array(
        'charset'           => 'utf-8',
        'strict_variables'  => true,
        'cache'             => $app['cache.dir']
    )
));

// Registers Smak Portfolio extension
$app->register(new SmakServiceProvider(), array(
    'smak.portfolio.content_path' => __DIR__ . '/../web/content',
    'smak.portfolio.public_path'  => $app['domain'] . '/content'
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

// Swiftmailer transport configuration
$app['swiftmailer.transport'] = \Swift_SmtpTransport::newInstance($app['smpt.domain'], $app['smpt.port']);

// Default cache values
$app['cache.defaults'] = array(
    'Cache-Control'     => sprintf('public, max-age=%d, s-maxage=%d, must-revalidate, proxy-revalidate', $app['cache.max_age'], $app['cache.max_age']),
    'Expires'           => date('r', time() + $app['cache.expires'])
);

// Application error handling
$app->error(function(\Exception $e, $code) use ($app) {

    if ($app['debug']) {
        return;
    }

    switch ($code) {
        case '404':
            $app['monolog']->addError(sprintf('%s Error on %s', $code, $app['request']->server->get('REQUEST_URI')));
            $response = $app['twig']->render('error.html.twig');
            break;
        default:
            $app['monolog']->addCritical(sprintf('%s Error on %s', $code, $app['request']->server->get('REQUEST_URI')));
            $response = $app['twig']->render('error.html.twig');
            break;
    }

    return new Response($response, $code);
});
