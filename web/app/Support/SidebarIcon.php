<?php

namespace App\Support;

class SidebarIcon
{
    public static function forRoute(?string $route): string
    {
        if ($route === null || $route === '') {
            return config('tich-sidebar-icons.default', 'circle');
        }

        $routes = config('tich-sidebar-icons.routes', []);

        if (isset($routes[$route])) {
            return $routes[$route];
        }

        $base = preg_replace('/\.(index|dashboard|show)$/', '', $route) ?? $route;

        foreach ($routes as $configuredRoute => $icon) {
            $configuredBase = preg_replace('/\.(index|dashboard|show)$/', '', $configuredRoute) ?? $configuredRoute;

            if ($base === $configuredBase) {
                return $icon;
            }
        }

        return config('tich-sidebar-icons.default', 'circle');
    }

    public static function forSection(?string $section): string
    {
        if ($section === null || $section === '') {
            return config('tich-sidebar-icons.default', 'circle');
        }

        return config("tich-sidebar-icons.sections.{$section}", config('tich-sidebar-icons.default', 'circle'));
    }
}
