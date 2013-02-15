<?php

ini_set('display_errors', 0);
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('America/New_York');

// Bootstraping
require_once __DIR__ . '/../vendor/autoload.php';
$app = new Silex\Application();

// Application settins
$app['debug']             = (bool) getenv('DEV_ENV');
$app['cache.max_age']     = 3600 * 24 * 90;
$app['cache.expires']     = 3600 * 24 * 90;
$app['cache.path']        = __DIR__ . '/../cache';
$app['twig.content_path'] = __DIR__ . '/views';
$app['domain']            = 'http://photography.eexit.net';
$app['domain']            = 'http://local.photo.eexit.net';

// Mailer settings
$app['mail.subject'] = 'New email from the portfolio!';
$app['mail.sender']  = 'no-reply@eexit.net';
$app['mail.to']      = array('photography@eexit.net' => 'Joris Berthelot');

// Content display settings
$app['smak.portfolio.enable_fresh_flag']   = true;
$app['smak.portfolio.fresh_flag_interval'] = 'P30D';
$app['smak.portfolio.gallery_pattern']     = '/(\d{2})?([-[:alpha:]]+)/';

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
    'http_cache.cache_dir'  => $app['cache.path'],
    'http_cache.options'    => array(
        'allow_reload'      => true,
        'allow_revalidate'  => true
)));

// Registers Symfony Validator component extension
$app->register(new ValidatorServiceProvider());

// Registers Smak Portfolio extension
$app->register(new SmakServiceProvider(), array(
    'smak.portfolio.content_path'   => __DIR__ . '/../web/content',
    'smak.portfolio.public_path'    => $app['domain'] . '/content'
));

// Registers Twig extension
$app->register(new TwigServiceProvider(), array(
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
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile'       => __DIR__ . '/../log/portfolio.log',
    'monolog.name'          => 'portfolio',
    'monolog.level'         => 300
));

// Registers Swiftmailer extension
$app->register(new SwiftmailerServiceProvider(), array(
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
