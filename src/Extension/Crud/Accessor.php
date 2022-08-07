<?php

namespace App\Extension\Crud;

use App\Extension\Crud\Concern\WithButtons;
use App\Extension\Utils;
use App\Extension\Crud\Concern\WithTitle;
use App\Extension\Crud\Concern\WithColumns;
use App\Extension\Crud\Concern\WithColumnsIgnore;
use App\Extension\Crud\Concern\WithContext;
use App\Extension\Crud\Concern\WithFilter;
use App\Extension\Crud\Concern\WithModifier;
use App\Extension\Crud\Concern\WithRole;
use App\Extension\Crud\Concern\WithSearchable;
use App\Extension\Crud\Concern\WithTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

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
            'roles' => $crud->roles,
        );
    }

    public static function fromRouteArgument(
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        Security $security,
        Request $request,
        Configurator|null $config,
        array $crud,
    ): static {
        return new static(
            $em,
            $urlGenerator,
            $security,
            $request,
            $config,
            $crud['name'],
            $crud['entity'],
            $crud['action'],
            $crud['indexController'],
            $crud['template'],
            $crud['title'],
            $crud['roles'],
        );
    }

    public function __construct(
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private Request $request,
        public Configurator|null $config,
        public string $name,
        public string $entity,
        public string $action,
        public string|bool|null $indexController,
        public string|null $template,
        public string|null $title,
        public array|null $roles,
    ) {}

    public function wantJson(): bool
    {
        return 'json' === $this->request->getPreferredFormat();
    }

    public function path(string $action = null, array $parameters = null): string
    {
        return $this->urlGenerator->generate($this->name . '_' . ($action ?? $this->action), $parameters ?? array());
    }

    public function getPaginationFilter(): array|null
    {
        return $this->config instanceof WithFilter ? $this->config->getFilter($this->action, $this->request) : null;
    }

    public function getPaginationSearchable(): array|bool|null
    {
        return $this->config instanceof WithSearchable ? $this->config->getSearchable() : null;
    }

    public function getPaginationModifier(): \Closure|null
    {
        return $this->config instanceof WithModifier ? $this->config->getModifier() : null;
    }

    public function getColumns(): array
    {
        $meta = $this->em->getClassMetadata($this->entity);
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

    public function getRole(string $action = null): string|null
    {
        if ($this->config instanceof WithRole) {
            return $this->config->getRole($action ?? $this->action);
        }

        return match($action ?? $this->action) {
            Resource::ACTION_INDEX => null,
            default => 'ROLE_ADMIN',
        };
    }

    public function isCreatable(): bool
    {
        return $this->isGranted(Resource::ACTION_CREATE);
    }

    public function isEditable(): bool
    {
        return $this->isGranted(Resource::ACTION_E);
    }

    public function isGranted(string $action = null): bool
    {
        $role = $this->getRole($action ?? $this->action);

        return !$role || $this->security->isGranted($role);
    }

    public function getButtons(): array
    {
        if ($this->config instanceof WithButtons) {
            return $this->config->getButtons($this->security, $this->urlGenerator);
        }

        $buttons = array();

        if ($this->isGranted(Resource::ACTION_CREATE)) {
            $buttons[] = array(
                'extend' => 'link',
                'text' => '<i class="bi-plus-circle me-1"></i> New',
            );
        }

        return $buttons;
    }
}