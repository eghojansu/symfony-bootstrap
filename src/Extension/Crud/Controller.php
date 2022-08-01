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

#[AutoconfigureTag('controller.service_arguments')]
final class Controller
{
    private $configs;

    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private ControllerContext $controller,
        private ApiContext $api,
        #[TaggedIterator('app.crud')]
        iterable $configs,
    ) {
        $this->configs = iterator_to_array($configs);
    }

    public function index(Request $request, array $crud): Response
    {
        $config = $this->getConfig($crud);

        if (self::wantJson($request)) {
            return $this->api->json(
                $this->controller->paginate(
                    $config->entity,
                    $config->getPaginationSearchable(),
                    $config->getPaginationFilter($request),
                    $config->getPaginationModifier(),
                    $request,
                ),
            );
        }

        return $this->render($config, array(
            'columns' => $config->getColumns($this->em->getClassMetadata($config->entity)),
            'controller' => $config->indexController ?? 'list',
        ));
    }

    protected function render(Accessor $config, array $context = null, array $extensions = null): Response
    {
        $base = $context ?? array();

        if (!isset($base['_title'])) {
            $base['_title'] = $config->getTitle();
        }

        if (!isset($base['nav'])) {
            $base['nav'] = $this->getNav($config);
        }

        return $this->controller->render(
            $config->getTemplate(),
            $config->getTemplateContext($base, $extensions),
        );
    }

    protected function getNav(Accessor $config): array
    {
        return array(
            array(
                'text' => 'Home',
                'url' => $this->getPath($config),
            ),
        );
    }

    protected function getPath(Accessor $config, string $action = null, array $parameters = null): string
    {
        return $this->urlGenerator->generate($config->name . '_' . ($action ?? $config->action), $parameters ?? array());
    }

    protected function getConfig(array $crud): Accessor
    {
        return Accessor::fromRouteArgument($this->configs[$crud['config']] ?? null, $crud);
    }

    protected static function wantJson(Request $request): bool
    {
        return 'json' === $request->getPreferredFormat();
    }
}