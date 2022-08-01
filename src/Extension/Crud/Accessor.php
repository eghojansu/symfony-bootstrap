<?php

namespace App\Extension\Crud;

use App\Extension\Utils;
use App\Extension\Crud\Concern\WithTitle;
use App\Extension\Crud\Concern\ColumnsFormat;
use App\Extension\Crud\Concern\ColumnsIgnore;
use App\Extension\Crud\Concern\TemplateContext;
use App\Extension\Crud\Concern\FilterPagination;
use App\Extension\Crud\Concern\ModifyPagination;
use App\Extension\Crud\Concern\SearchablePagination;
use App\Extension\Crud\Concern\TemplateSelector;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Component\HttpFoundation\Request;

final class Accessor
{
    public static function toRouteArgument(
        Resource $crud,
        string $action,
        string $name,
        string $entity,
    ): array {
        return compact('name', 'entity', 'action') + array(
            'config' => $crud->config,
            'indexController' => $crud->indexController,
            'template' => $crud->template,
            'title' => $crud->title,
        );
    }

    public static function fromRouteArgument(
        Configurator|null $config,
        array $crud,
    ): static {
        return new static(
            $config,
            $crud['name'],
            $crud['entity'],
            $crud['action'],
            $crud['indexController'],
            $crud['template'],
            $crud['title'],
        );
    }

    public function __construct(
        public Configurator|null $config,
        public string $name,
        public string $entity,
        public string $action,
        public string|bool|null $indexController,
        public string|null $template,
        public string|null $title,
    ) {}

    public function getPaginationFilter(Request $request): array|null
    {
        return $this->config instanceof FilterPagination ? $this->config->getPaginationFilter($request) : null;
    }

    public function getPaginationSearchable(): array|bool|null
    {
        return $this->config instanceof SearchablePagination ? $this->config->getPaginationSearchable() : null;
    }

    public function getPaginationModifier(): \Closure|null
    {
        return $this->config instanceof ModifyPagination ? $this->config->getPaginationModifier() : null;
    }

    public function getColumns(ClassMetadata $meta): array
    {
        $ignores = $this->getIgnoredColumns();
        $columns = Utils::reduce(
            $meta->getFieldNames(),
            static function (array $columns, string $data) use ($meta, $ignores) {
                if (in_array($data, $ignores)) {
                    return $columns;
                }

                $columns[] = compact('data') + array(
                    'title' => Utils::caseTitle($data),
                );

                return $columns;
            },
            array(),
        );

        if ($this->config instanceof ColumnsFormat) {
            return $this->config->getColumnsFormat($this->action, $columns);
        }

        return $columns;
    }

    public function getIgnoredColumns(): array
    {
        return array_merge(
            ($this->config instanceof ColumnsIgnore ?
                $this->config->getIgnoredColumns($this->action) :
                null
            ) ?? match ($this->action) {
                default => array('id', 'password', 'createdAt', 'updatedAt', 'deletedAt'),
            },
            ($this->config instanceof ColumnsIgnore ? $this->config->getIgnoredColumnsAppend($this->action) : null) ?? array(),
        );
    }

    public function getTitle(): string
    {
        $title = $this->title ?? Utils::caseTitle($this->action . ' ' . $this->name);

        if ($this->config instanceof WithTitle) {
            return $this->config->getTitle($this->action, $title);
        }

        return $title;
    }

    public function getTemplate(): string
    {
        $template = $this->action;

        if ($this->config instanceof TemplateSelector) {
            $template = $this->config->getSelectedTemplate($this->action);
        }

        return ($this->template ?? Resource::TEMPLATE_PREFIX) . '.' . $template;
    }

    public function getTemplateContext(array $context = null, array $extensions = null): array
    {
        $newContext = $context ?? array();

        if ($this->config instanceof TemplateContext) {
            $newContext = $this->config->getTemplateContext($this->action, $newContext);
        }

        if (!$extensions) {
            return $newContext;
        }

        return Utils::reduce(
            $extensions,
            fn (array $context, callable $resolve, string $name) => $context + (
                isset($context[$name]) ? array() : array(
                    $name => $resolve($this),
                )
            ),
            $newContext,
        );
    }
}