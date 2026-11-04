<?php

namespace App\Http\Middleware;

use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class ResolveAcademicsHub
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($route->parameter('department') instanceof Department) {
            return $next($request);
        }

        if ($this->actionExpectsHub($route)) {
            $hub = Department::findAcademicsHub();
            abort_unless($hub, 404);

            $existing = $route->parameters();

            foreach (array_keys($existing) as $key) {
                $route->forgetParameter($key);
            }

            // Controller actions are unpacked positionally. Hub URLs bind
            // {program} first, so department must be inserted ahead of it.
            $route->setParameter('department', $hub);

            foreach ($existing as $key => $value) {
                if ($key !== 'department') {
                    $route->setParameter($key, $value);
                }
            }
        }

        return $next($request);
    }

    /**
     * Only actions that hint Department may receive it: route parameters are
     * unpacked by position, so a leading Department would land in {plan} or {report}.
     */
    private function actionExpectsHub(Route $route): bool
    {
        try {
            foreach ($route->signatureParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType
                    && ! $type->isBuiltin()
                    && is_a($type->getName(), Department::class, true)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return true;
        }

        return false;
    }
}
