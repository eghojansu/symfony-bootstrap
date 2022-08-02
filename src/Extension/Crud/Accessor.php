<?php

namespace App\Extension\Crud;

use App\Extension\Utils;
use App\Extension\Crud\Concern\WithTitle;
use App\Extension\Crud\Concern\WithColumns;
use App\Extension\Crud\Concern\WithColumnsIgnore;
use App\Extension\Crud\Concern\WithContext;
use App\Extension\Crud\Concern\WithFilter;
use App\Extension\Crud\Concern\WithModifier;
use App\Extension\Crud\Concern\WithSearchable;
use App\Extension\Crud\Concern\WithTemplate;
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
        return $this->config instanceof WithFilter ? $this->config->getFilter($this->action, $request) : null;
    }

    public function getPaginationSearchable(): array|bool|null
    {
        return $this->config instanceof WithSearchable ? $this->config->getSearchable() : null;
    }

    public function getPaginationModifier(): \Closure|null
    {
        return $this->config instanceof WithModifier ? $this->config->getModifier() : null;
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

        if ($this->config instanceof WithColumns) {
            return $this->config->getColumns($this->action, $columns);
        }

        return $columns;
    }

    public function getIgnoredColumns(): array
    {
        if ($this->config instanceof WithColumnsIgnore && !$this->config->mergeIgnoredColumn($this->action)) {
            return $this->config->getIgnoredColumns($this->action);
        }

        return array_merge(
            match ($this->action) {
                default => array('id', 'password', 'createdAt', 'updatedAt', 'deletedAt'),
            },
            $this->config instanceof WithColumnsIgnore ? $this->config->getIgnoredColumns($this->action) : array(),
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

        if ($this->config instanceof WithTemplate) {
            $template = $this->config->getTemplate($this->action);
        }

        return ($this->template ?? Resource::TEMPLATE_PREFIX) . '.' . $template;
    }

    public function getTemplateContext(array $context = null, array $extensions = null): array
    {
        $newContext = $context ?? array();

        if ($this->config instanceof WithContext) {
            $newContext = $this->config->getContext($this->action, $newContext);
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