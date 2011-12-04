<?php

require_once __DIR__ . '/bootstrap.php';

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