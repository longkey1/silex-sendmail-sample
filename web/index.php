<?php
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();

// validator
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app['validator.mapping.class_metadata_factory'] = new Symfony\Component\Validator\Mapping\ClassMetadataFactory(
    new Symfony\Component\Validator\Mapping\Loader\YamlFileLoader(__DIR__ . '/../config/validation.yml')
);

// swiftmailer
$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app['swiftmailer.options'] = [
    'transport' => getenv('MAIL_TRANSPORT'),
    'host' => getenv('MAIL_HOST'),
    'port' => getenv('MAIL_PORT'),
    'username' => getenv('MAIL_USERNAME'),
    'password' => getenv('MAIL_PASSWORD'),
    'auth_mode' => getenv('MAIL_AUTH_MODE'),
];

// twig
$app->register(new Silex\Provider\TwigServiceProvider());
$app['twig.path'] = __DIR__ . '/../views';

// maildata
class Maildata
{
    public $name;
    public $email;
    public $memo;
}
$app['mail.builder'] = function() use ($app) {
    $mail = new MailData;
    $mail->name = $app['request']->get('name');
    $mail->email = $app['request']->get('email');
    $mail->memo = $app['request']->get('memo');

    return $mail;
};

// routing
$app->post('/send', function () use ($app) {
    $mail = $app['mail.builder'];
    $errors = $app['validator']->validate($mail);
    if (count($errors) > 0) {
        return $app->json(['success' => false, 'errors' => $errors]);
    }
    $message = \Swift_Message::newInstance()
        ->setSubject('send mail test')
        ->setFrom('longkey1@gmail.com')
        ->setTo($app['request']->get('email'))
        ->setBody($app['twig']->render('mail.text.twig', [
            'name' => $app['request']->get('name'),
            'memo' => $app['request']->get('memo'),
        ]))
    ;
    if ($app['mailer']->send($message) == false) {
        return $app->json(['success' => false]);
    }

    return $app->json(['success' => true]);

});

$app->run();
