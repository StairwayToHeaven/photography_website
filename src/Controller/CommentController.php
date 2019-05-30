<?php
/**
 * Commentcontroller.
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
use Repository\CommentRepository;
use Repository\UserRepository;
use Form\CommentType;

/**
 * Class CommentController.
 *
 * @uses Silex\Application
 * @uses Silex\Api\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Form\Extension\Core\Type\FormType
 * @uses Symfony\Component\Form\Extension\Core\Type\HiddenType
 * @uses Repository\CommentRepository
 * @uses Repository\UserRepository
 * @uses Form\CommentType
 * @package Controller
 */
class CommentController implements ControllerProviderInterface
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
        $controller->match(
            '/add',
            array($this, 'addAction')
        )->bind('comment-add');
        $controller->match(
            '/index',
            array($this, 'indexAction')
        )->bind('comment-index');
        $controller->match(
            '/delete/{id}',
            array($this, 'deleteAction')
        )->bind('comment-delete');
        $controller->match(
            '/edit/{id}',
            array($this, 'editAction')
        )->bind('comment-edit');

        return $controller;
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
            $comment = [];
            $form = $app['form.factory']->createBuilder(CommentType::class, $comment)->getForm();
            $form->handleRequest($request);
            $commentRepository = new CommentRepository($app['db']);
            if ($form->isSubmitted() && $form->isValid()) {
                $commentData = $form->getData();
                $token = $app['security.token_storage']->getToken();
                $userInfo = $token->getUser();
                $username = $userInfo->getUsername();
                $userModel = new UserRepository($app['db']);
                $commentData['user_id'] = $userModel->getUserIdByLogin($username);
                $commentRepository->save($commentData);
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.comment_successfully_add',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('portfolio'), 301);
            }

            return $app['twig']->render(
                'comment/add.html.twig',
                [
                    'form' => $form->createView(),
                    'active' => 'portfolio',
                ]
            );
        } catch (\PDOException $e) {
            $app->abort(500, $app['translator']->trans('error_500'));

            return 'error_500';
        }
    }
    /**
     * Index action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @throws \PDOException
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, Request $request)
    {
        try {
            $user = [];
            $form = $app['form.factory']->createBuilder(CommentType::class, $user)->getForm();
            $form->handleRequest($request);
            $commentRepository = new CommentRepository($app['db']);
            $comments = $commentRepository->findAll();

            return $app['twig']->render(
                'comment/index.html.twig',
                [
                    'active' => 'portfolio',
                    'comments' => $comments,
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
        $commentRepository = new CommentRepository($app['db']);
        $comment = $commentRepository->findById($id);

        if (!$comment) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('portfolio'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $comment)
            ->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('portfolio'),
                301
            );
        }

        return $app['twig']->render(
            'comment/delete.html.twig',
            [
                'comment' => $comment,
                'form' => $form->createView(),
                'active' => 'portfolio',
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
        $commentRepository = new CommentRepository($app['db']);
        $comment = $commentRepository->findToEdit($id);

        if (!$comment) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('portfolio'));
        }

        $form = $app['form.factory']->createBuilder(CommentType::class, $comment, array(
                        'validation_groups' => 'comment-edit',
        ))->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentData = $form->getData();
            $commentRepository->save($commentData);
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
            'comment/edit.html.twig',
            [
                'comment' => $comment,
                'form' => $form->createView(),
                'active' => 'portfolio',
            ]
        );
    }
}
