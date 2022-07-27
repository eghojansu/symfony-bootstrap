<?php

namespace App\Controller;

use App\Extension\ApiContext;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @property ApiContext $api
 */
abstract class Controller extends AbstractController
{
    protected function view(string $path, array $parameters = null): Response
    {
        return $this->render($this->viewFile($path), $parameters ?? array());
    }

    protected function form(
        string $view,
        string $type,
        object|array $data = null,
        callable|bool $persist = false,
        array $parameters = null,
        array $options = null,
        Request &$request = null,
    ): Response {
        /** @var Request */
        $req = $request ?? $this->container->get('request_stack')->getCurrentRequest();
        $form = $this->createForm($type, $data, $options ?? array());
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = 'saved';

            if ($persist) {
                if (is_callable($persist)) {
                    $action = $persist($data, $this->em, $form);
                } else {
                    $this->em->persist($data);
                }
            }

            if ($action instanceof Response) {
                return $action;
            }

            if ('json' === $req->getPreferredFormat()) {
                return $this->api->done(...(is_array($action) ? $action : array('save', $action)));
            }
        }

        return $this->renderForm($this->viewFile($view), ($parameters ?? array()) + compact('form'));
    }

    protected function viewFile(string $path): string
    {
        if (str_ends_with($path, '.twig')) {
            return $path;
        }

        return str_replace('.', '/', $path) . '.html.twig';
    }

    protected static function subscribe(): array
    {
        return array();
    }

    public static function getSubscribedServices(): array
    {
        return self::subscribe() + parent::getSubscribedServices() + array(
            'api' => ApiContext::class,
        );
    }

    public function __get($name)
    {
        if (method_exists($this, $get = '_' . ltrim($name, '_'))) {
            return $this->$get();
        }

        return $this->container->get($name);
    }
}