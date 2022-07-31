<?php

namespace App\Extension\Crud;

use App\Extension\ApiContext;
use App\Extension\ControllerContext;
use App\Extension\Crud\Concern\ColumnsFormat;
use App\Extension\Crud\Concern\ColumnsIgnore;
use App\Extension\Crud\Concern\CustomTemplate;
use App\Extension\Crud\Concern\FilterPagination;
use App\Extension\Crud\Concern\ModifyPagination;
use App\Extension\Crud\Concern\PaginateFilter;
use App\Extension\Crud\Concern\TemplateContext;
use App\Extension\Crud\Concern\TemplateSelector;
use App\Extension\Crud\Concern\WithTitle;
use App\Extension\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('controller.service_arguments')]
final class Controller
{
    private $configs;

    public function __construct(
        private EntityManagerInterface $em,
        private ControllerContext $controller,
        private ApiContext $api,
        #[TaggedIterator('app.crud')]
        iterable $configs,
    ) {
        $this->configs = iterator_to_array($configs);
    }

    public function index(Request $request, array $crud): Response
    {
        $config = $this->configs[$crud['config']] ?? null;

        if (self::wantJson($request)) {
            return $this->api->data(
                $this->controller->paginate(
                    $crud['entity'],
                    $config instanceof FilterPagination ? $config->getPaginationFilter($request) : null,
                    $config instanceof ModifyPagination ? $config->getPaginationModifier() : null,
                    $request,
                ),
            );
        }

        $template = $this->resolveTemplate($config, $crud);
        $parameters = $this->resolveParameters($config, $crud);

        if (!isset($parameters['columns'])) {
            $parameters['columns'] = $this->resolveColumns($config, $crud);
        }

        if (!isset($parameters['_title'])) {
            $parameters['_title'] = $this->resolveTitle($config, $crud);
        }

        return $this->controller->render($template, $parameters);
    }

    protected function resolveTemplate(Configurator|null $config, array $crud): string
    {
        $view = $config instanceof TemplateSelector ? $config->getSelectedTemplate($crud['action']) : $crud['action'];

        return ($config['template'] ?? Resource::TEMPLATE_PREFIX) . '.' . $view;
    }

    protected function resolveParameters(Configurator|null $config, array $crud): array
    {
        return $config instanceof TemplateContext ? $config->getTemplateContext($crud['action']) : array();
    }

    protected function resolveTitle(Configurator|null $config, array $crud): string
    {
        if ($config instanceof WithTitle) {
            return $config->getTitle($crud['action']);
        }

        return $crud['title'] ?? Utils::caseTitle($crud['action'] . ' ' . $crud['name']);
    }

    protected function resolveColumns(Configurator|null $config, array $crud): array
    {
        if ($config instanceof ColumnsFormat) {
            return $config->getColumnsFormat($crud['action']);
        }

        $meta = $this->em->getClassMetadata($crud['entity']);
        $ignores = array_merge(
            (
                $config instanceof ColumnsIgnore ?
                    $config->getIgnoredColumns($crud['action']) :
                    null
            ) ?? match ($crud['action']) {
                default => array('id', 'password', 'createdAt', 'updatedAt', 'deletedAt'),
            },
            ($config instanceof ColumnsIgnore ? $config->getIgnoredColumnsAppend($crud['action']) : null) ?? array(),
        );
        $columns = Utils::reduce(
            $meta->getFieldNames(),
            static function (array $columns, string $field) use ($meta, $ignores) {
                if (in_array($field, $ignores)) {
                    return $columns;
                }

                $columns[] = $field;

                return $columns;
            },
            array(),
        );

        return $columns;
    }

    protected static function wantJson(Request $request): bool
    {
        return 'json' === $request->getPreferredFormat();
    }
}