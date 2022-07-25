<?php

namespace App\Extension\RBAC;

use App\Entity\Csmenu;
use App\Extension\Utils;
use App\Repository\CsmenuRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper;

final class Menu
{
    const ROOT_DASHBOARD = 'db';
    const ROOT_TOP = 'top';
    const TARGET_CLIENT = 'client';
    const TARGET_EDITING = 'editing';

    private $all;
    private $currentPath;

    public function __construct(
        private CsmenuRepository $repo,
        private RequestStack $requestStack,
        private Security $security,
        private UrlHelper $urlHelper,
        private bool $prefixPath = true,
        private bool $activable = true,
    ) {}

    public static function create(
        int $priority,
        string $id,
        string $name,
        string $path = null,
        string $icon = null,
        string|array $roles = null,
        Csmenu $parent = null,
        string $matcher = null,
        string $hint = null,
        bool $active = null,
        bool $hidden = null,
        array $attrs = null,
    ): Csmenu {
        $menu = new Csmenu();
        $menu->setPriority($priority);
        $menu->setId($id);
        $menu->setName($name);
        $menu->setPath($path);
        $menu->setIcon($icon);
        $menu->setHint($hint);
        $menu->setRoles(Utils::split($roles));
        $menu->setActive($active ?? true);
        $menu->setHidden($hidden ?? false);
        $menu->setMatcher($matcher);
        $menu->setParent($parent);
        $menu->setAttrs($attrs);

        return $menu;
    }

    public static function createFromArray(array $menu): Csmenu
    {
        return self::create(
            $menu['priority'],
            $menu['id'],
            $menu['name'],
            $menu['path'] ?? null,
            $menu['icon'] ?? null,
            $menu['roles'] ?? null,
            $menu['parent'] ?? null,
            $menu['matcher'] ?? null,
            $menu['hint'] ?? null,
            $menu['active'] ?? null,
            $menu['hidden'] ?? null,
            $menu['attrs'] ?? null,
        );
    }

    public function isGranted(string $path): bool
    {
        $menu = $this->repo->findMenu($path);

        return !$menu || !$this->skip($menu);
    }

    public function getRoot(string $root): array
    {
        return $this->build($root, $this->getAll());
    }

    public function getTree(): array
    {
        return $this->buildTree(
            array(self::ROOT_DASHBOARD, self::ROOT_TOP),
            $this->getAll(),
        );
    }

    public function getEditingTree(): array
    {
        return $this->buildTree(
            array(self::ROOT_DASHBOARD, self::ROOT_TOP),
            $this->repo->findAll(),
            self::TARGET_EDITING,
        );
    }

    public function toEntities(array $tree, Csmenu $parent = null): array
    {
        $priority = 1;

        return Utils::reduce(
            $tree,
            function (array $entities, array $item, string|int $id) use ($parent, &$priority) {
                $menu = self::createFromArray(array_replace(array(
                    'id' => is_int($id) ? Utils::random(8) : $id,
                    'priority' => $priority++,
                ), $item, array(
                    'parent' => $parent,
                )));

                $entities[$menu->getId()] = $menu;

                return $entities + $this->toEntities(
                    $item['items'] ?? array(),
                    $menu,
                );
            },
            array(),
        );
    }

    private function getAll()
    {
        return $this->all ?? ($this->all = $this->repo->getMenu());
    }

    private function serializeForClient(
        Csmenu $menu,
        string $parent,
        array $items,
    ): array {
        return array(
            'id' => $menu->getId(),
            'url' => $this->url($menu),
            'text' => $menu->getName(),
            'hint' => $menu->getHint(),
            'icon' => $menu->getIcon(),
            'order' => $menu->getPriority(),
            'attrs' => $menu->getAttrs(),
            'active' => $this->active($menu, $items),
            'has_child' => count($items) > 0,
        ) + compact('parent', 'items');
    }

    private function serializeForEditing(
        Csmenu $menu,
        string $parent,
        array $items,
    ): array {
        return array(
            'id' => $menu->getId(),
            'path' => $menu->getPath(),
            'name' => $menu->getName(),
            'hint' => $menu->getHint(),
            'icon' => $menu->getIcon(),
            'order' => $menu->getPriority(),
            'active' => $menu->isActive(),
            'hidden' => $menu->isHidden(),
            'roles' => $menu->getRoles(),
            'has_child' => count($items) > 0,
        ) + compact('parent', 'items');
    }

    private function buildTree(
        array $roots,
        array $rows,
        string $target = null,
        bool $activable = null,
    ): array {
        return Utils::reduce(
            $roots,
            fn (array|null $menu, string $root) => array_merge($menu ?? array(), array(
                $root => $this->build($root, $rows, $target, $activable),
            )),
        );
    }

    private function build(
        string $parent,
        array $rows,
        string $target = null,
    ): array {
        $menu = Utils::reduce(
            $rows,
            function (
                array $menu,
                Csmenu $row,
                $key,
                array $rows,
            ) use ($parent, $target) {
                if (
                    $parent !== $row->getParent()?->getId()
                    || $this->skip($row)
                ) {
                    return $menu;
                }

                $items = $this->build($row->getId(), $rows, $target);

                $menu[$row->getId()] = match($target) {
                    self::TARGET_EDITING => $this->serializeForEditing($row, $parent, $items),
                    default => $this->serializeForClient($row, $parent, $items),
                };

                return $menu;
            },
            array(),
        );

        uasort($menu, static fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return $menu;
    }

    private function skip(Csmenu $menu): bool
    {
        return (
            $menu->getRoles()
            && Utils::all(
                $menu->getRoles(),
                fn (string $role) => !$this->security->isGranted($role),
            )
        );
    }

    private function isMatch(Csmenu $menu, string $path = null): bool
    {
        return (
            $menu->getPath()
            && preg_match(
                $menu->getMatcher() ?? '/^' . preg_quote($menu->getPath(), '/') . '/',
                $this->getCurrentPath(),
            )
        );
    }

    private function url(Csmenu $menu): string
    {
        $path = $menu->getPath();

        return '#' === ($path[0] ?? '#') ? ($path ?? '#') : $this->urlHelper->getRelativePath($path);
    }

    private function active(Csmenu $menu, array $children): bool
    {
        return $this->activable && (
            $this->isMatch($menu)
            || Utils::some($children, static fn (array $child) => $child['active'])
        );
    }

    private function getCurrentPath(): string
    {
        return $this->currentPath ?? (
            $this->currentPath = $this->requestStack->getCurrentRequest()->getPathInfo()
        );
    }
}