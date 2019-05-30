<?php
/**
 * Pagecontroller.
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
use Repository\PageRepository;
use Form\PageType;

/**
 * Class PageController.
 *
 * @uses Silex\Application
 * @uses Silex\Api\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Form\Extension\Core\Type\FormType
 * @uses Symfony\Component\Form\Extension\Core\Type\HiddenType
 * @uses Repository\PageRepository
 * @uses Form\PageType
 * @package Controller
 */
class PageController implements ControllerProviderInterface
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
        $pageController = $app['controllers_factory'];
        $pageController->match(
            '/',
            array($this, 'homeAction')
        )->bind('home');
        $pageController->match(
            '/admin/pages',
            array($this, 'indexAction')
        )->bind('page-admin');
        $pageController->match(
            '/page/view/{slug}',
            array($this, 'viewAction')
        )->bind('page-view');
        $pageController->match(
            '/page/edit/{slug}',
            array($this, 'editAction')
        )->bind('page-edit');
        $pageController->match(
            '/page/delete/{slug}',
            array($this, 'deleteAction')
        )->bind('page-delete');
        $pageController->match(
            '/page/add',
            array($this, 'addAction')
        )->bind('page-add');
        $pageController->match(
            '/menu/{active}',
            array($this, 'menuAction')
        )->bind('page-menu');

        return $pageController;
    }
    /**
    * Home action.
    *
    * @access public
    * @param \Silex\Application $app Silex application
    * @return string Output
    */
    public function homeAction(Application $app)
    {
        $view = array();
        $view['active'] = 'home';

        return $app['twig']->render('page/index.html.twig', $view);
    }
    /**
     * Index action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, Request $request)
    {
        $page = new PageRepository($app['db']);
        $view = array();
        $view['pages'] = $page->findAll();
        $view['active'] = 'admin';

        return $app['twig']->render('page/admin.html.twig', $view);
    }
    /**
     * View action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param string                                    $slug    Page slug
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewAction(Application $app, $slug, Request $request)
    {
        $pageRepository = new PageRepository($app['db']);
        $page = $pageRepository->find($slug);

        if (!$page) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home'));
        }

        return $app['twig']->render(
            'page/base.html.twig',
            [
                'page' => $page,
                'active' => $page['slug'],
            ]
        );
    }
    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param string                                    $slug    Record slug
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $slug, Request $request)
    {
        $pageRepository = new PageRepository($app['db']);
        $page = $pageRepository->find($slug);

        if (!$page) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('page-admin'));
        }

        $form = $app['form.factory']->createBuilder(PageType::class, $page, array(
                        'validation_groups' => 'page-edit',
        ))->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageData = $form->getData();
            $pageData['app'] = $app;
                $pageRepository->save($pageData);
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('page-admin'), 301);
        }

        return $app['twig']->render(
            'page/edit.html.twig',
            [
                'page' => $page,
                'form' => $form->createView(),
                'active' => 'admin',
            ]
        );
    }
    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param string                                    $slug    Record slug
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $slug, Request $request)
    {
        $pageRepository = new PageRepository($app['db']);
        $page = $pageRepository->find($slug);

        if (!$page) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('page-admin'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $page)
            ->add('slug', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageRepository->delete($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('page-admin'),
                301
            );
        }

        return $app['twig']->render(
            'page/delete.html.twig',
            [
                'page' => $page,
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
            $page = [];
            $form = $app['form.factory']->createBuilder(PageType::class, $page)->getForm();
            $form->handleRequest($request);
            $pageModel = new PageRepository($app['db']);
            if ($form->isSubmitted() && $form->isValid()) {
                $pageData = $form->getData();
                $pageModel->save($pageData);
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.page_successfully_add',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('page-admin'), 301);
            }

            return $app['twig']->render(
                'page/add.html.twig',
                [
                    'form' => $form->createView(),
                    'active' => 'page-add',
                ]
            );
        } catch (\PDOException $e) {
            $app->abort(500, $app['translator']->trans('error_500'));

            return 'error_500';
        }
    }
    /**
     * Menu action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param string                                    $active  Active page
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function menuAction(Application $app, $active, Request $request)
    {
        $page = new PageRepository($app['db']);
        $view = array();
        $view['active'] = $active;
        $view['pages'] = $page->findAll();

        return $app['twig']->render('page/menu.html.twig', $view);
    }
}
