<?php
/**
 * Routing and controllers
 *
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\HttpFoundation\Response
 * @uses Controller\AuthController
 * @uses Controller\UserController
 * @uses Controller\CommentController
 * @copyright (c) 2017 Katarzyna Dam
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Controller\AuthController;
use Controller\UserController;
use Controller\CommentController;
use Controller\PhotoController;
use Controller\PageController;

$app->get('/contact', function () use ($app) {
    return $app['twig']->render('page/contact.html.twig', ['active' => 'contact']);
})->bind('contact');

//$app->get('/portfolio', function () use ($app) {
//    return $app['twig']->render('page/portfolio.html.twig', ['active' => 'portfolio']);
//})->bind('portfolio');

$app->get('/rejestracja', function () use ($app) {
    return $app['twig']->render('page/rejestracja.html.twig', ['active' => 'rejestracja']);
})->bind('rejestracja');

$app->get('/admin', function () use ($app) {
    return $app['twig']->render('page/admin.html.twig', ['active' => 'admin']);
})->bind('admin');

/* Ukryta strona dla tych, którzy kliknęli button wysyłania Send It na stronie kontakt w przyszłości */
$app->get('/road-to-nowhere', function () use ($app) {
    return $app['twig']->render('pages/road.twig', ['active' => 'road-to-nowhere']);
})->bind('road');

$app->mount('/auth', new AuthController());

$app->mount('/user', new UserController());

$app->mount('/comment', new CommentController());

$app->mount('/portfolio', new PhotoController());

$app->mount('/', new PageController());

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
