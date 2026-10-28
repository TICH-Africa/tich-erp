<?php

namespace App\Services;

use App\Models\StaffWeeklyTimeLog;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a weekly time log into the signed paper document the employee, their manager and HR print.
 */
class WeeklyTimeLogDocumentService
{
    public const VIEW = 'time-logs.document';

    public function __construct(
        protected WeeklyTimeLogService $timeLogs,
        protected PrintDocumentService $printDocuments,
    ) {}

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function page(StaffWeeklyTimeLog $log, array $overrides = []): View
    {
        return $this->printDocuments->render(self::VIEW, $this->data($log, $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function download(StaffWeeklyTimeLog $log, array $overrides = []): StreamedResponse
    {
        $data = $this->data($log, $overrides);

        return $this->printDocuments->downloadPdf(self::VIEW, $data, $data['filename']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function data(StaffWeeklyTimeLog $log, array $overrides = []): array
    {
        $log->loadMissing(['days', 'staff.department', 'manager', 'hrReviewer']);
        $staff = $log->staff;

        return array_merge([
            'log' => $log,
            'departments' => $staff ? $this->timeLogs->departmentsForStaff($staff) : collect(),
            'documentTitle' => 'Staff Weekly Time Log',
            'documentRef' => (string) ($log->log_code ?? $log->id),
            'filename' => 'weekly-time-log-'.Str::slug(collect([
                $staff?->employee_number ?? $staff?->id,
                $log->week_ref,
            ])->filter()->implode('-')).'.pdf',
        ], $overrides);
    }
}
