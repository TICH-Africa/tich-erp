<?php

namespace App\Providers;

use App\Models\Feedback;
use App\Models\Grievance;
use App\Models\LeaveRequest;
use App\Models\OffboardingRequest;
use App\Models\PolicyAcknowledgement;
use App\Models\RecruitmentApplication;
use App\Models\StaffContract;
use App\Models\StaffDocument;
use App\Models\StaffOnboarding;
use App\Models\StaffProfileChangeRequest;
use App\Services\HrSidebarNotificationService;
use Illuminate\Support\ServiceProvider;

class HrSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcast = function (): void {
            app(HrSidebarNotificationService::class)->broadcastCounts();
        };

        foreach ([
            LeaveRequest::class,
            StaffOnboarding::class,
            RecruitmentApplication::class,
            StaffDocument::class,
            OffboardingRequest::class,
            StaffContract::class,
            StaffProfileChangeRequest::class,
            PolicyAcknowledgement::class,
            Grievance::class,
            Feedback::class,
        ] as $model) {
            $model::saved($broadcast);
            $model::deleted($broadcast);
        }
    }
}
