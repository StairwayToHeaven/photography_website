<?php
/**
 * Runs applications and loads config
 *
 * @uses Silex\Application
 * @uses Silex\Provider\AssetServiceProvider
 * @uses Silex\Provider\TwigServiceProvider
 * @uses Silex\Provider\ServiceControllerServiceProvider
 * @uses Silex\Provider\HttpFragmentServiceProvider
 * @uses Silex\Provider\LocaleServiceProvider
 * @uses Silex\Provider\TranslationServiceProvider
 * @uses Silex\Provider\DoctrineServiceProvider
 * @uses Silex\Provider\SecurityServiceProvider
 * @uses Silex\Provider\FormServiceProvider
 * @uses Silex\Provider\ValidatorServiceProvider
 * @uses Silex\Provider\SessionServiceProvider
 * @copyright (c) 2017 Katarzyna Dam
 *
 */
use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\SessionServiceProvider;

$app = new Application();

$app->register(new SessionServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app->register(new LocaleServiceProvider());
$app->register(
    new TranslationServiceProvider(),
    [
        'locale' => 'pl',
        'locale_fallbacks' => array('en'),
    ]
);
$app->extend('translator', function ($translator, $app) {
    $translator->addResource('xliff', __DIR__.'/../translations/messages.en.xlf', 'en', 'messages');
    $translator->addResource('xliff', __DIR__.'/../translations/validators.en.xlf', 'en', 'validators');
    $translator->addResource('xliff', __DIR__.'/../translations/messages.pl.xlf', 'pl', 'messages');
    $translator->addResource('xliff', __DIR__.'/../translations/validators.pl.xlf', 'pl', 'validators');

    return $translator;
});
$app->register(
    new DoctrineServiceProvider(),
    [
        'db.options' => [
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => '14_dam',
            'user'      => '14_dam',
            'password'  => 'H4k2l3p3',
            'charset'   => 'utf8',
            'driverOptions' => [
                1002 => 'SET NAMES utf8',
            ],
        ],
    ]
);
$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(
    new SecurityServiceProvider(),
    [
        'security.firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'main' => [
                'pattern' => '^.*$',
                'form' => [
                    'login_path' => 'auth_login',
                    'check_path' => 'auth_login_check',
                    'default_target_path' => 'home',
                    'username_parameter' => 'login_type[login]',
                    'password_parameter' => 'login_type[password]',
                ],
                'anonymous' => true,
                'logout' => [
                    'logout_path' => 'auth_logout',
                    'target_url' => 'home',
                ],
                'users' => function () use ($app) {
                    return new Provider\UserProvider($app['db']);
                },
            ],
        ],
        'security.access_rules' => [
            ['^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/page/view/.*$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/portfolio/$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/user/add$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/user/view$', 'ROLE_USER'],
            ['^/user/edit.*$', 'ROLE_USER'],
            ['^/comment/index$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ['^/comment/add$', 'ROLE_USER'],
            ['^/.+$', 'ROLE_ADMIN'],
        ],
        'security.role_hierarchy' => [
            'ROLE_ADMIN' => ['ROLE_USER'],
        ],
    ]
);
$app->register(
    new TwigServiceProvider(),
    [
        'twig.path' => dirname(dirname(__FILE__)).'/templates',
    ]
);
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    return $twig;
});

return $app;
