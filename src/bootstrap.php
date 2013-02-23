<?php

ini_set('display_errors', getenv('DEV_ENV'));
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('Europe/Paris');

// Namespace
use Symfony\Component\HttpFoundation\Response;

// Bootstraping
require_once __DIR__ . '/../vendor/autoload.php';
$app = new Silex\Application();

// Application settins
$app['debug']             = ((bool) getenv('DEV_ENV') || '127.0.0.1' == $_SERVER['SERVER_ADDR']);
$app['cache.path']        = __DIR__ . '/../cache';
$app['twig.content_path'] = __DIR__ . '/views';
$app['cache.max_age']     = $app['cache.expires'] = 3600 * 24 * 90;

// Mailer settings
$app['mail.subject'] = 'New email from the portfolio!';
$app['mail.sender']  = 'no-reply@eexit.net';
$app['mail.to']      = array('photography@eexit.net' => 'Joris Berthelot');

// Content display settings
$app['smak.portfolio.enable_fresh_flag']   = false;
$app['smak.portfolio.fresh_flag_interval'] = 'P30D';
$app['smak.portfolio.gallery_pattern']     = '/(\d{2})?([-[:alpha:]]+)/';

if ($app['debug']) {
    $app['domain']               = 'http://local.photo.eexit.net';
    $app['session_storage_path'] = sys_get_temp_dir();
} else {
    $app['domain']               = 'http://photography.eexit.net';
    $app['session_storage_path'] = '/homez.466/joris/phpsessions';
}

// Registers Symfony Session component extension
$app->register(new Silex\Provider\SessionServiceProvider(array(
    'session.storage.save_path' => $app['session_storage_path']
)));
$app['session']->start();

// Registers Symfony Cache component extension
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir'  => $app['cache.path'],
    'http_cache.options'    => array(
        'allow_reload'      => true,
        'allow_revalidate'  => true
)));

// Registers Symfony Validator component extension
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Registers Smak Portfolio extension
$app->register(new Smak\Portfolio\Silex\Provider\SmakServiceProvider(), array(
    'smak.portfolio.content_path'   => __DIR__ . '/../web/content',
    'smak.portfolio.public_path'    => $app['domain'] . '/content'
));

// Registers Twig extension
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'             => array(
        $app['twig.content_path'],
        $app['smak.portfolio.content_path']
    ),
    'twig.options'          => array(
        'charset'           => 'utf-8',
        'strict_variables'  => true,
        'cache'             => $app['cache.path']
    )
));

// Registers Monolog extension
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile'       => __DIR__ . '/../log/portfolio.log',
    'monolog.name'          => 'portfolio',
    'monolog.level'         => 300
));

// Registers Swiftmailer extension
$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.class_path'    => __DIR__ . '/../vendor/swiftmailer/swiftmailer/lib/classes'
));
$app['mailer'] = \Swift_Mailer::newInstance(\Swift_MailTransport::newInstance());

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
