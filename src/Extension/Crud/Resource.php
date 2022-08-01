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
        public string|bool|null $indexController = null,
        public string|null $enabled = 'ICRUDOP',
    ) {}
}