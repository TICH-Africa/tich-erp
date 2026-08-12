<?php

namespace App\Support;

class AccessManagementContext
{
    public function __construct(
        public readonly string $prefix,
        public readonly string $layout,
        public readonly string $contentSection,
    ) {}

    public static function admin(): self
    {
        return new self('admin', 'layouts.admin', 'admin-content');
    }

    public static function ict(): self
    {
        return new self('ict', 'layouts.ict', 'ict-content');
    }

    public function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return route("{$this->prefix}.{$name}", $parameters, $absolute);
    }
}
