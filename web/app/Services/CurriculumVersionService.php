<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\CurriculumVersionUnit;
use App\Models\Department;
use App\Models\ProgramUnit;
use App\Models\User;
use Illuminate\Http\Request;

class CurriculumVersionService
{
    public function __construct(
        protected AcademicsAccessService $access,
        protected AuditService $auditService,
    ) {}

    public function createDraft(User $user, Department $hub, AcademicProgram $program, array $data, ?Request $request = null): CurriculumVersion
    {
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $program), 403);

        $latestNumber = CurriculumVersion::query()
            ->where('program_id', $program->id)
            ->max('version_number') ?? 0;

        $version = CurriculumVersion::create([
            'program_id' => $program->id,
            'academic_year_id' => $data['academic_year_id'] ?? null,
            'version_label' => $data['version_label'] ?? ('v'.($latestNumber + 1)),
            'version_number' => $latestNumber + 1,
            'curriculum_format' => $data['curriculum_format'] ?? $program->curriculum_format ?? 'trimester',
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->auditService->log(
            'academics.curriculum_version.created',
            'curriculum_versions',
            $version->id,
            null,
            $version->only(['program_id', 'version_label', 'status']),
            'Curriculum version draft created',
            'success',
            $user->id,
            $request
        );

        return $version;
    }

    public function submit(User $user, Department $hub, CurriculumVersion $version, ?Request $request = null): CurriculumVersion
    {
        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);
        abort_unless($version->status === 'draft', 422);

        $mappedCount = ProgramUnit::query()->where('program_id', $version->program_id)->count();
        abort_if($mappedCount === 0, 422, 'Map at least one active unit to the programme before submitting.');

        $version->update([
            'status' => 'pending_registry',
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'updated_at' => now(),
        ]);

        $this->auditService->log(
            'academics.curriculum_version.submitted',
            'curriculum_versions',
            $version->id,
            ['status' => 'draft'],
            ['status' => 'pending_registry'],
            'Curriculum version submitted for registry verification',
            'success',
            $user->id,
            $request
        );

        return $version->fresh();
    }

    public function approveRegistry(User $user, Department $hub, CurriculumVersion $version, ?Request $request = null): CurriculumVersion
    {
        abort_unless($this->access->canApproveRegistry($user), 403);
        abort_unless($version->status === 'pending_registry', 422);

        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);

        $program = $version->program;
        $needsCeo = $program->status === 'pending_ceo';

        $version->update([
            'status' => $needsCeo ? 'pending_ceo' : 'published',
            'registrar_approved_at' => now(),
            'registrar_approved_by' => $user->id,
            'published_at' => $needsCeo ? null : now(),
            'published_by' => $needsCeo ? null : $user->id,
            'updated_at' => now(),
        ]);

        if (! $needsCeo) {
            $this->snapshotUnits($version);
            $this->supersedePrevious($version);
        }

        $this->auditService->log(
            'academics.curriculum_version.registrar_approved',
            'curriculum_versions',
            $version->id,
            null,
            ['status' => $version->status],
            'Curriculum version approved by registrar',
            'success',
            $user->id,
            $request
        );

        return $version->fresh('items.unit');
    }

    public function approveCeo(User $user, Department $hub, CurriculumVersion $version, ?Request $request = null): CurriculumVersion
    {
        abort_unless($this->access->canApproveCeo($user), 403);
        abort_unless($version->status === 'pending_ceo', 422);

        $version->loadMissing('program');
        abort_unless($this->access->userCanAccessProgramInHub($user, $hub, $version->program), 403);

        $version->update([
            'status' => 'published',
            'ceo_approved_at' => now(),
            'ceo_approved_by' => $user->id,
            'published_at' => now(),
            'published_by' => $user->id,
            'updated_at' => now(),
        ]);

        if ($version->program->status === 'pending_ceo') {
            $version->program->update([
                'status' => 'active',
                'approved_by_ceo_at' => now(),
                'approved_by_ceo_id' => $user->id,
            ]);
        }

        $this->snapshotUnits($version);
        $this->supersedePrevious($version);

        $this->auditService->log(
            'academics.curriculum_version.ceo_approved',
            'curriculum_versions',
            $version->id,
            null,
            ['status' => 'published'],
            'Curriculum version published after CEO approval',
            'success',
            $user->id,
            $request
        );

        return $version->fresh('items.unit');
    }

    private function snapshotUnits(CurriculumVersion $version): void
    {
        CurriculumVersionUnit::query()->where('curriculum_version_id', $version->id)->delete();

        $mappings = ProgramUnit::query()
            ->with('unit')
            ->where('program_id', $version->program_id)
            ->get();

        foreach ($mappings as $mapping) {
            CurriculumVersionUnit::create([
                'curriculum_version_id' => $version->id,
                'unit_id' => $mapping->unit_id,
                'semester' => $mapping->semester,
                'block_id' => $mapping->block_id,
                'is_compulsory' => $mapping->is_compulsory,
                'display_order' => $mapping->display_order,
                'priority' => $mapping->priority,
                'credit_hours' => $mapping->unit?->credit_hours ?? 0,
                'contact_hours' => $mapping->contact_hours ?: ($mapping->unit?->contact_hours ?? 0),
                'total_learning_hours' => $mapping->total_learning_hours ?: ($mapping->unit?->total_learning_hours ?? 0),
            ]);
        }
    }

    private function supersedePrevious(CurriculumVersion $version): void
    {
        CurriculumVersion::query()
            ->where('program_id', $version->program_id)
            ->where('id', '!=', $version->id)
            ->where('status', 'published')
            ->update(['status' => 'superseded', 'updated_at' => now()]);
    }

    public function publishedVersionForProgram(int $programId): ?CurriculumVersion
    {
        return CurriculumVersion::query()
            ->with('items.unit')
            ->where('program_id', $programId)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->first();
    }
}
