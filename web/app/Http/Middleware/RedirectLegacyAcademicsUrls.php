<?php

namespace App\Http\Middleware;

use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLegacyAcademicsUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $department = $request->route('department');

        if (! $department instanceof Department) {
            return $next($request);
        }

        $prefix = 'departments/'.$department->getRouteKey().'/academics';
        $path = trim($request->path(), '/');

        if ($path !== $prefix && ! str_starts_with($path, $prefix.'/')) {
            return $next($request);
        }

        $suffix = $path === $prefix ? '' : substr($path, strlen($prefix) + 1);
        $query = $request->query();
        unset($query['department']);

        if ($department->isLearningDepartment()) {
            $query['learning_department'] = $department->id;
        }

        $target = '/academics'.($suffix !== '' ? '/'.$suffix : '');

        if ($query !== []) {
            $target .= '?'.http_build_query($query);
        }

        return redirect($target, 301);
    }
}
