<?php

// Namespace
use Symfony\Component\HttpFoundation\Response;

$app->get('/phpsess', function() use ($app) {
    return new Response(sprintf('session.save_path: %s', ini_get('session.save_path')));
});
