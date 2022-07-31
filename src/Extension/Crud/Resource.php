<?php

namespace App\Extension\Crud;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Resource
{
    const PATH_PREFIX = null;
    const TEMPLATE_PREFIX = 'crud';
    const ACTION_INDEX = 'index';
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';

    public function __construct(
        public string|null $path = null,
        public string|null $name = null,
        public string|null $title = null,
        public string|null $config = null,
        public string|null $template = null,
        public string|bool|null $jsController = null,
        public string|null $enabled = 'ICRUDOP',
    ) {}

    public function indexable(): bool
    {
        return $this->enabled && -1 < strpos($this->enabled, 'I');
    }

    public function creatable(): bool
    {
        return $this->enabled && -1 < strpos($this->enabled, 'C');
    }

    public function viewable(): bool
    {
        return $this->enabled && -1 < strpos($this->enabled, 'R');
    }

    public function editable(): bool
    {
        return $this->enabled && -1 < strpos($this->enabled, 'U');
    }

    public function removeable(): bool
    {
        return $this->enabled && -1 < strpos($this->enabled, 'D');
    }

    public function restorable(): bool
    {
        return $this->enabled && -1 < strpos($this->enabled, 'O');
    }

    public function destroyable(): bool
    {
        return $this->enabled && -1 < strpos($this->enabled, 'P');
    }
}