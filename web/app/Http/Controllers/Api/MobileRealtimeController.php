<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\EmployeePortalService;
use App\Services\RBACService;
use App\Services\StaffPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileRealtimeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (config('broadcasting.default') !== 'reverb') {
            return response()->json(['enabled' => false]);
        }

        $conn = config('broadcasting.connections.reverb');
        $key = $conn['key'] ?? null;
        if (! filled($key)) {
            return response()->json(['enabled' => false]);
        }

        $options = $conn['options'] ?? [];
        $channels = $this->channelsForUser($user);

        return response()->json([
            'enabled' => $channels !== [],
            'key' => $key,
            'host' => $options['host'] ?? $request->getHost(),
            'port' => (int) ($options['port'] ?? 443),
            'scheme' => $options['scheme'] ?? 'https',
            'use_tls' => ($options['scheme'] ?? 'https') === 'https',
            'auth_path' => 'broadcasting/auth',
            'event' => 'sidebar.counts.updated',
            'channels' => $channels,
        ]);
    }

    /**
     * @return list<string> Pusher private channel names (with private- prefix).
     */
    private function channelsForUser($user): array
    {
        $channels = [];
        $rbac = app(RBACService::class);

        if (app(EmployeePortalService::class)->staffForUser($user) !== null) {
            $channels[] = "private-employee.sidebar.{$user->id}";
        }

        $staff = app(StaffPortalService::class)->staffForUser($user);
        if ($staff) {
            $teachingRoles = ['Lecturer/Tutor', 'HOD', 'Dean of Students', 'Academic Registrar', 'Super Admin'];
            $hasTeachingRole = collect($teachingRoles)->contains(fn (string $role) => $rbac->hasRole($user, $role));
            if ($staff->is_teaching_staff || $hasTeachingRole || $rbac->hasPermission($user, 'academics.read')) {
                $channels[] = "private-staff.sidebar.{$user->id}";
            }
        }

        $isStudent = $user->student_id
            || Student::query()->where('user_id', $user->id)->exists();
        if ($isStudent) {
            $channels[] = "private-student.sidebar.{$user->id}";
        }

        if ($rbac->hasPermission($user, 'procurement.read')) {
            $channels[] = 'private-procurement.sidebar';
        }

        return array_values(array_unique($channels));
    }
}
