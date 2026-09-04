<?php

namespace App\Services;

use App\Models\ExaminationPaper;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Models\User;
use App\Services\Sidebar\StaffSidebarNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExaminationPaperService
{
    public const MODERATOR_ROLES = ['Academic Registrar', 'HOD', 'Super Admin', 'Head of Academics'];

    public function __construct(
        protected StoredFileService $files,
        protected StaffSidebarNotificationService $sidebarNotifications,
    ) {}

    /**
     * @param  array{unit_id:int,semester_id:int,exam_type:string,version?:string,auto_table?:bool}  $data
     */
    public function submitDraft(Staff $staff, array $data, UploadedFile $file, bool $autoTable = true): ExaminationPaper
    {
        $this->assertCanPrepare($staff, (int) $data['unit_id'], (int) $data['semester_id']);

        $path = $this->files->store($file, 'examination-papers/drafts', 'public');

        $paper = ExaminationPaper::query()->create([
            'unit_id' => (int) $data['unit_id'],
            'semester_id' => (int) $data['semester_id'],
            'exam_type' => $data['exam_type'],
            'version' => $data['version'] ?? 'A',
            'draft_file_path' => $path,
            'status' => 'draft',
            'prepared_by' => $staff->id,
            'created_at' => now(),
        ]);

        if ($autoTable) {
            $paper = $this->table($paper);
        }

        $this->forgetSidebarCaches($staff);

        return $paper;
    }

    public function table(ExaminationPaper $paper): ExaminationPaper
    {
        abort_unless($paper->status === 'draft', 422, 'Only draft papers can be tabled for moderation.');

        $paper->fill([
            'status' => 'tabled',
            'tabled_at' => now(),
        ])->save();

        $this->forgetSidebarCaches($paper->preparer);

        return $paper->fresh();
    }

    public function tableForModeration(ExaminationPaper $paper, Staff $staff): ExaminationPaper
    {
        abort_unless((int) $paper->prepared_by === (int) $staff->id || $this->userCanModerate($staff), 403);

        return $this->table($paper);
    }

    public function moderate(ExaminationPaper $paper, Staff $staff, ?UploadedFile $moderatedFile = null, ?string $notes = null): ExaminationPaper
    {
        $this->assertCanModerate($staff);
        abort_unless($paper->status === 'tabled', 422, 'Only tabled papers can be moderated.');

        $updates = [
            'status' => 'moderated',
            'moderated_at' => now(),
        ];

        if ($moderatedFile) {
            $updates['moderated_file_path'] = $this->files->store($moderatedFile, 'examination-papers/moderated', 'public');
        }

        unset($notes);

        $paper->fill($updates)->save();
        $this->forgetSidebarCaches($staff);

        return $paper->fresh();
    }

    public function approve(ExaminationPaper $paper, Staff $staff, ?UploadedFile $approvedFile = null): ExaminationPaper
    {
        $this->assertCanModerate($staff);
        abort_unless($paper->status === 'moderated', 422, 'Only moderated papers can be approved.');

        $updates = [
            'status' => 'approved',
            'approved_by' => $staff->id,
            'approved_at' => now(),
        ];

        if ($approvedFile) {
            $updates['approved_file_path'] = $this->files->store($approvedFile, 'examination-papers/approved', 'public');
        } elseif ($paper->moderated_file_path) {
            $updates['approved_file_path'] = $paper->moderated_file_path;
        } elseif ($paper->draft_file_path) {
            $updates['approved_file_path'] = $paper->draft_file_path;
        }

        $paper->fill($updates)->save();
        $this->forgetSidebarCaches($staff);

        return $paper->fresh();
    }

    public function assertCanPrepare(Staff $staff, int $unitId, int $semesterId): void
    {
        $ownsAllocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('unit_id', $unitId)
            ->where('semester_id', $semesterId)
            ->where('is_active', 1)
            ->exists();

        if ($ownsAllocation) {
            return;
        }

        $user = $this->userForStaff($staff);
        $isTeachingLead = $user && $user->hasAnyRole(['HOD', 'Academic Registrar', 'Super Admin', 'Head of Academics']);

        abort_unless(
            $isTeachingLead || ($user && $user->hasAnyRole(['Lecturer/Tutor'])),
            403,
            'You must be a lecturer allocated to this unit to submit an examination paper.'
        );

        abort_unless(
            $isTeachingLead,
            403,
            'You are not allocated to this unit for the selected semester.'
        );
    }

    public function assertCanModerate(Staff $staff): void
    {
        abort_unless($this->userCanModerate($staff), 403, 'Only HOD / Academic Registrar can moderate or approve papers.');
    }

    public function userCanModerate(?Staff $staff): bool
    {
        if (! $staff) {
            return false;
        }

        $user = $this->userForStaff($staff);

        return $user && $user->hasAnyRole(self::MODERATOR_ROLES);
    }

    public function userCanModerateUser(?User $user): bool
    {
        return $user && $user->hasAnyRole(self::MODERATOR_ROLES);
    }

    /**
     * @return \Illuminate\Support\Collection<int, ExaminationPaper>
     */
    public function papersForStaff(Staff $staff)
    {
        $unitIds = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('is_active', 1)
            ->pluck('unit_id')
            ->unique()
            ->all();

        return ExaminationPaper::query()
            ->with(['unit', 'semester', 'preparer', 'approver'])
            ->where(function ($query) use ($staff, $unitIds) {
                $query->where('prepared_by', $staff->id);
                if ($unitIds !== []) {
                    $query->orWhereIn('unit_id', $unitIds);
                }
            })
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Pending moderation count for HOD/Registrar badges (department-scoped when possible).
     */
    public function pendingModerationCount(?Staff $staff): int
    {
        if (! $staff || ! $this->userCanModerate($staff)) {
            return 0;
        }

        $query = ExaminationPaper::query()->where('status', 'tabled');

        $departmentId = (int) ($staff->department_id ?? 0);
        if ($departmentId > 0 && ! $this->userForStaff($staff)?->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics'])) {
            $query->whereIn('unit_id', function ($sub) use ($departmentId) {
                $sub->select('id')->from('units')->where('department_id', $departmentId);
            });
        }

        return (int) $query->count();
    }

    private function userForStaff(Staff $staff): ?User
    {
        if ($staff->relationLoaded('user') && $staff->user) {
            return $staff->user;
        }

        return User::query()
            ->where(function ($query) use ($staff) {
                $query->where('staff_id', $staff->id);
                if ($staff->user_id) {
                    $query->orWhere('id', $staff->user_id);
                }
            })
            ->first();
    }

    private function forgetSidebarCaches(?Staff $staff): void
    {
        if ($staff) {
            $this->sidebarNotifications->forget($staff);
        }

        if ($staff && $staff->department_id) {
            $peerIds = DB::table('staff')
                ->where('department_id', $staff->department_id)
                ->where('id', '!=', $staff->id)
                ->pluck('id');

            foreach ($peerIds as $peerId) {
                Cache::forget(StaffSidebarNotificationService::CACHE_KEY_PREFIX.$peerId);
            }
        }
    }
}
