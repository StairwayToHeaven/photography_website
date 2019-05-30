<?php
/**
 * Usercontroller.
 *
 * @category Controller
 * @copyright (c) 2017 Katarzyna Dam
 */

namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\UserRepository;
use Form\UserType;

/**
 * Class UserController.
 *
 * @uses Silex\Application
 * @uses Silex\Api\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Form\Extension\Core\Type\FormType
 * @uses Symfony\Component\Form\Extension\Core\Type\HiddenType
 * @uses Repository\UserRepository
 * @uses Form\UserType
 * @package Controller
 */
class UserController implements ControllerProviderInterface
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
        $userController = $app['controllers_factory'];
        $userController->match(
            '/add',
            array($this, 'addAction')
        )->bind('user-add');
        $userController->match(
            '/view',
            array($this, 'viewAction')
        )->bind('user-view');
        $userController->match('/index/{page}', array($this, 'indexAction'))
            ->value('page', 1)
            ->bind('user-index');
        $userController->match(
            '/delete/{id}',
            array($this, 'deleteAction')
        )->bind('user-delete');
        $userController->match(
            '/edit/{id}',
            array($this, 'editAction')
        )->bind('user-edit');

        return $userController;
    }
    /**
    * Index action.
    *
    * @access public
    * @param \Silex\Application $app  Silex application
    * @param int                $page Current page number
    * @return string Output
    */
    public function indexAction(Application $app, $page = 1)
    {
        $user = new UserRepository($app['db']);
        $view = array();
        $view['view'] = $user->findAll($page, 10);
        $view['active'] = 'admin';

        return $app['twig']->render('user/index.html.twig', $view);
    }
    /**
     * View action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewAction(Application $app, Request $request)
    {
        $token = $app['security.token_storage']->getToken();
        $userInfo = $token->getUser();
        $username = $userInfo->getUsername();
        $userRepository = new UserRepository($app['db']);
        $id = array();
        $id = $userRepository->getUserIdByLogin($username);
        $user = $userRepository->findToEdit($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('portfolio'));
        }

        $form = $app['form.factory']->createBuilder(UserType::class, $user, array(
                        'validation_groups' => 'user-view',
        ))->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            $userData['app'] = $app;
                $userRepository->save($userData);
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('portfolio'), 301);
        }

        return $app['twig']->render(
            'user/view.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
                'active' => 'admin',
            ]
        );
    }
    /**
     * Add action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @throws \PDOException
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request)
    {
        try {
            $user = [];
            $form = $app['form.factory']->createBuilder(UserType::class, $user)->getForm();
            $form->handleRequest($request);
            $userModel = new UserRepository($app['db']);
            if ($form->isSubmitted() && $form->isValid()) {
                $userData = $form->getData();
                if ($userModel->loginUnique($userData['login'])) {
                    $userData['app'] = $app;
                    $userData['role_id'] = '2';
                    $userModel->save($userData);
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'success',
                            'message' => 'message.user_successfully_add',
                        ]
                    );

                    return $app->redirect($app['url_generator']->generate('home'), 301);
                } else {
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'warning',
                            'message' => 'message.user_login_exist',
                        ]
                    );
                }
            }

            return $app['twig']->render(
                'user/add.html.twig',
                [
                    'form' => $form->createView(),
                    'active' => 'rejestracja',
                ]
            );
        } catch (\PDOException $e) {
            $app->abort(500, $app['translator']->trans('error_500'));

            return 'error_500';
        }
    }
    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user-index', [ 'page' => '1' ]));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $user)
            ->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('user-index', [ 'page' => '1' ]),
                301
            );
        }

        return $app['twig']->render(
            'user/delete.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
                'active' => 'admin',
            ]
        );
    }
    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findToEdit($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('user-index', ['page' => '1']));
        }

        $form = $app['form.factory']->createBuilder(UserType::class, $user, array(
                        'validation_groups' => 'user-edit',
        ))->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            $userData['app'] = $app;
            if ($userRepository->loginUniqueInEdit($userData)) {
                $userRepository->save($userData);
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('user-index', ['page' => '1']), 301);
            } else {
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'warning',
                            'message' => 'message.user_login_exist',
                        ]
                    );
            }
        }

        return $app['twig']->render(
            'user/edit.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
                'active' => 'admin',
            ]
        );
    }
}
