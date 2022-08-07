<?php

namespace App\Extension\Crud;

use App\Extension\ApiContext;
use App\Extension\ControllerContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

#[AutoconfigureTag('controller.service_arguments')]
final class Controller
{
    private $configs;

    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private ControllerContext $controller,
        private ApiContext $api,
        #[TaggedIterator('app.crud')]
        iterable $configs,
    ) {
        $this->configs = iterator_to_array($configs);
    }

    public function index(Request $request, array $_crud): Response
    {
        $crud = $this->getConfig($request, $_crud);

        if ($crud->wantJson()) {
            return $this->api->json(
                $this->controller->paginate(
                    $crud->entity,
                    $crud->getPaginationSearchable(),
                    $crud->getPaginationFilter($request),
                    $crud->getPaginationModifier(),
                    $request,
                ),
            );
        }

        $context = compact('crud');

        return $this->render($crud, $context);
    }

    protected function render(Accessor $crud, array $context = null, array $extensions = null): Response
    {
        $base = $context ?? array();

        if (!isset($base['_title'])) {
            $base['_title'] = $crud->getTitle();
        }

        if (!isset($base['nav'])) {
            $base['nav'] = $this->getNav($crud);
        }

        return $this->controller->render(
            $crud->getTemplate(),
            $crud->getTemplateContext($base, $extensions),
        );
    }

    protected function getNav(Accessor $crud): array
    {
        return array(
            array(
                'text' => 'Home',
                'url' => $crud->path(),
            ),
        );
    }

    protected function getConfig(Request $request, array $crud): Accessor
    {
        return Accessor::fromRouteArgument(
            $this->em,
            $this->urlGenerator,
            $this->security,
            $request,
            $this->configs[$crud['config']] ?? null,
            $crud,
        );
    }
}