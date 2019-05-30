<?php
/**
 * Auth controller.
 *
 * @category Controller
 * @copyright (c) 2017 Katarzyna Dam
 */
namespace Controller;

use Form\LoginType;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthController
 *
 * @uses Form\LoginType
 * @uses Silex\Application
 * @uses Silex\Api\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @package Controller
 */
class AuthController implements ControllerProviderInterface
{
    /**
     * Routing settings.
     *
     * @access public
     * @param \Silex\Application $app Silex application
     * @return Silex\ControllerCollection Result
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->match('login', [$this, 'loginAction'])
                   ->method('GET|POST')
                   ->bind('auth_login');
        $controller->match('logout', [$this, 'logoutAction'])
                   ->bind('auth_logout');

        return $controller;
    }

    /**
     * Login action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function loginAction(Application $app, Request $request)
    {
        $user = ['login' => $app['session']->get('_security.last_username')];
        $form = $app['form.factory']->createBuilder(LoginType::class, $user)->getForm();

        return $app['twig']->render(
            'auth/login.html.twig',
            [
                'form' => $form->createView(),
                'error' => $app['security.last_error']($request),
                'active' => 'logowanie',
            ]
        );
    }

    /**
     * Logout action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function logoutAction(Application $app)
    {
        $app['session']->clear();

        return $app['twig']->render('auth/logout.html.twig', ['active' => 'home']);
    }
}
