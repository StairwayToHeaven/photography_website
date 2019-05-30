<?php
/**
 * PhotoController.
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
use Repository\PhotoRepository;
use Form\PhotoType;

/**
 * Class PhotoController.
 *
 * @uses Silex\Application
 * @uses Silex\Api\ControllerProviderInterface
 * @uses Symfony\Component\HttpFoundation\Request
 * @uses Symfony\Component\Form\Extension\Core\Type\FormType
 * @uses Symfony\Component\Form\Extension\Core\Type\HiddenType
 * @uses Symfony\Component\Form\Extension\Core\Type\ChoiceType
 * @uses Repository\PhotoRepository
 * @uses Form\PhotoType
 * @package Controller
 */
class PhotoController implements ControllerProviderInterface
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
        $photoController = $app['controllers_factory'];
        $photoController->match(
            '/add',
            array($this, 'addAction')
        )->bind('photo-add');
        $photoController->match(
            '/delete/{id}',
            array($this, 'deleteAction')
        )->bind('photo-delete');
        $photoController->match(
            '',
            array($this, 'indexAction')
        )->bind('portfolio');

        return $photoController;
    }
    /**
    * Index action.
    *
    * @access public
    * @param \Silex\Application $app Silex application
    * @return string Output
    */
    public function indexAction(Application $app)
    {
        $photo = new PhotoRepository($app['db']);
        $view = array();
        $view['photos'] = $photo->findAll();
        $view['active'] = 'portfolio';

        return $app['twig']->render('photo/index.html.twig', $view);
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
            $photo = [];
            $form = $app['form.factory']->createBuilder(PhotoType::class, $photo)->getForm();
            $form->handleRequest($request);
            $photoModel = new PhotoRepository($app['db']);
            if ($form->isSubmitted() && $form->isValid()) {
                $photo = $form->getData();
                $photo['files'] = $request->files->get($form->getName());
                $photoModel->saveImage(
                    $photo['files'],
                    realpath(dirname(dirname(__DIR__))).'/web/img/',
                    $photo['title']
                );
                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.photo_successfully_added',
                    ]
                );

                return $app->redirect(
                    $app['url_generator']->generate('portfolio'),
                    301
                );
            }

            return $app['twig']->render(
                'photo/add.html.twig',
                [
                    'photo' => $photo,
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
        $photoRepository = new PhotoRepository($app['db']);
        $photo = $photoRepository->find($id);

        if (!$photo) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('portfolio'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $photo)
            ->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->getData();
            $photoRepository->delete(
                $photo['id'],
                $photo['url'],
                realpath(dirname(dirname(__DIR__))).'/web/img/'
            );

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.photo_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('portfolio'),
                301
            );
        }

        return $app['twig']->render(
            'photo/delete.html.twig',
            [
                'photo' => $photo,
                'form' => $form->createView(),
                'active' => 'portfolio',
            ]
        );
    }
}
