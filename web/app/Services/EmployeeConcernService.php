<?php

namespace App\Services;

use App\Models\Grievance;
use App\Models\Staff;
use App\Models\User;

class EmployeeConcernService
{
    /** @var array<string, string> */
    public const CATEGORIES = [
        'workplace_safety' => 'Workplace safety',
        'harassment_discrimination' => 'Harassment or discrimination',
        'management_supervision' => 'Management / supervision',
        'payroll_benefits' => 'Payroll or benefits',
        'working_conditions' => 'Working conditions',
        'colleague_conduct' => 'Colleague conduct',
        'policy_procedure' => 'Policy or procedure',
        'facilities_it' => 'Facilities or IT',
        'other' => 'Other',
    ];

    public function __construct(
        protected AuditService $auditService,
        protected PlatformNotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Staff $staff, User $user, array $data): Grievance
    {
        $category = $data['concern_category'] ?? $data['grievance_type'] ?? 'other';
        $categoryLabel = self::CATEGORIES[$category] ?? ucfirst(str_replace('_', ' ', (string) $category));

        $grievance = Grievance::create([
            'staff_id' => $staff->id,
            'reference_number' => $this->generateReferenceNumber(),
            'grievance_type' => $category,
            'subject' => $data['subject'] ?? null,
            'description' => $data['description'],
            'incident_date' => $data['incident_date'] ?? null,
            'resolution_notes' => $data['resolution_notes'] ?? null,
            'status' => 'open',
            'metadata' => [
                'submitted_via' => 'employee_portal',
                'category_label' => $categoryLabel,
            ],
        ]);

        $this->auditService->log(
            'employee.concern.submitted',
            'grievances',
            $grievance->id,
            null,
            [
                'reference_number' => $grievance->reference_number,
                'staff_id' => $staff->id,
                'category' => $category,
            ],
            'Employee submitted a concern to HR',
            'success',
            $user->id,
        );

        $this->notifyHr($staff, $grievance, $categoryLabel);

        if ($staff->user_id) {
            $this->notifications->notifyUser(
                $staff->user_id,
                'Concern submitted',
                "Your concern {$grievance->reference_number} has been sent to HR for review.",
                'employee_concern',
                (string) $grievance->id,
            );
        }

        return $grievance;
    }

    public function generateReferenceNumber(): string
    {
        $year = now()->year;
        $prefix = "CON-{$year}-";

        $last = Grievance::query()
            ->where('reference_number', 'like', $prefix.'%')
            ->orderByDesc('reference_number')
            ->value('reference_number');

        $next = 1;
        if ($last) {
            $next = ((int) substr($last, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function notifyHr(Staff $staff, Grievance $grievance, string $categoryLabel): void
    {
        $rbac = app(RBACService::class);
        $userIds = User::query()
            ->where('is_active', 1)
            ->get()
            ->filter(fn (User $user) => $rbac->hasPermission($user, 'hr.staff.view'))
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return;
        }

        $subject = $grievance->subject ?: 'No subject';
        $this->notifications->notifyUsers(
            $userIds,
            'New employee concern',
            "{$staff->fullName()} raised {$grievance->reference_number}: {$categoryLabel} — {$subject}",
            'employee_concern',
            (string) $grievance->id,
        );
    }
}
