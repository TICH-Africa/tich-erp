<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->student_id && ! \App\Models\Student::query()->where('user_id', $user->id)->exists())) {
            abort(403, 'Student portal access requires an enrolled student account.');
        }

        return $next($request);
    }
}
