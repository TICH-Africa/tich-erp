<?php

namespace App\Services\Research;

use App\Models\Portal\ResearchProject;
use App\Models\Portal\ResearchProjectDocument;
use App\Models\User;
use App\Services\StoredFileService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResearchActivityService
{
    public const DURATION_UNITS = ['days', 'weeks', 'months', 'years'];

    public const LIFECYCLE_STATUSES = ['upcoming', 'ongoing', 'completed', 'paused'];

    public const VISIBILITIES = ['draft', 'published', 'archived'];

    public function __construct(
        protected StoredFileService $files,
    ) {}

    public function calculateExpectedCompletion(Carbon|string $startDate, int $durationValue, string $durationUnit): Carbon
    {
        $start = $startDate instanceof Carbon ? $startDate->copy()->startOfDay() : Carbon::parse($startDate)->startOfDay();

        return match ($durationUnit) {
            'days' => $start->copy()->addDays($durationValue),
            'weeks' => $start->copy()->addWeeks($durationValue),
            'months' => $start->copy()->addMonthsNoOverflow($durationValue),
            'years' => $start->copy()->addYearsNoOverflow($durationValue),
            default => $start->copy()->addDays($durationValue),
        };
    }

    public function deriveStatus(?Carbon $startDate, ?Carbon $endDate, ?Carbon $asOf = null): string
    {
        $today = ($asOf ?? now())->copy()->startOfDay();

        if ($startDate && $today->lt($startDate->copy()->startOfDay())) {
            return 'upcoming';
        }

        if ($endDate && $today->gt($endDate->copy()->startOfDay())) {
            return 'completed';
        }

        if ($startDate && $today->gte($startDate->copy()->startOfDay())) {
            return 'ongoing';
        }

        return 'upcoming';
    }

    public function syncLifecycleStatus(ResearchProject $project): ResearchProject
    {
        if ($project->status_locked) {
            return $project;
        }

        $derived = $this->deriveStatus(
            $project->start_date,
            $project->end_date,
        );

        if ($project->status !== $derived) {
            $project->status = $derived;
            $project->save();
        }

        return $project;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{title: string, file: UploadedFile}>  $documents
     */
    public function create(array $data, User $user, array $documents = []): ResearchProject
    {
        return DB::transaction(function () use ($data, $user, $documents) {
            $payload = $this->normalizePayload($data, null, $user);
            $payload['created_by'] = $user->staff_id;
            $payload['created_at'] = now();
            $payload['updated_at'] = now();

            $project = ResearchProject::query()->create($payload);
            $this->attachDocuments($project, $documents, $user);

            return $project->fresh(['documents']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{title: string, file: UploadedFile}>  $documents
     */
    public function update(ResearchProject $project, array $data, User $user, array $documents = []): ResearchProject
    {
        return DB::transaction(function () use ($project, $data, $user, $documents) {
            $payload = $this->normalizePayload($data, $project, $user);
            $payload['updated_by'] = $user->staff_id;
            $payload['updated_at'] = now();

            $project->update($payload);
            $this->attachDocuments($project, $documents, $user);

            return $project->fresh(['documents']);
        });
    }

    public function deleteDocument(ResearchProjectDocument $document): void
    {
        $this->files->delete($document->file_path, 'local');
        $document->delete();
    }

    public function delete(ResearchProject $project): void
    {
        foreach ($project->documents as $document) {
            $this->deleteDocument($document);
        }

        if ($project->cover_image_path) {
            $this->files->delete($project->cover_image_path, 'public');
        }

        $project->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, ?ResearchProject $existing, User $user): array
    {
        $start = ! empty($data['start_date']) ? Carbon::parse($data['start_date'])->startOfDay() : null;
        $durationValue = isset($data['duration_value']) ? (int) $data['duration_value'] : null;
        $durationUnit = $data['duration_unit'] ?? null;

        $end = ! empty($data['end_date'])
            ? Carbon::parse($data['end_date'])->startOfDay()
            : null;

        if (! $end && $start && $durationValue && $durationUnit) {
            $end = $this->calculateExpectedCompletion($start, $durationValue, $durationUnit);
        }

        $statusLocked = (bool) ($data['status_locked'] ?? false);
        $manualStatus = $data['status'] ?? null;

        if ($statusLocked && in_array($manualStatus, self::LIFECYCLE_STATUSES, true)) {
            $status = $manualStatus;
        } else {
            $statusLocked = false;
            $status = $this->deriveStatus($start, $end);
        }

        $visibility = $data['visibility'] ?? 'draft';
        $publishedAt = $existing?->published_at;
        if ($visibility === 'published' && ! $publishedAt) {
            $publishedAt = now();
        }

        $title = trim((string) ($data['title'] ?? ''));
        $slug = $this->uniqueSlug($title, $existing?->id);

        $coverPath = $existing?->cover_image_path;
        if (! empty($data['remove_cover']) && $coverPath) {
            $this->files->delete($coverPath, 'public');
            $coverPath = null;
        }
        if (! empty($data['cover_image']) && $data['cover_image'] instanceof UploadedFile) {
            $coverPath = $this->files->replace($coverPath, $data['cover_image'], 'research/covers', 'public', null, true);
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'subtitle' => $data['subtitle'] ?? null,
            'summary' => $data['summary'] ?? '',
            'abstract' => $data['abstract'] ?? null,
            'body' => $this->sanitizeBody((string) ($data['body'] ?? '')),
            'start_date' => $start,
            'duration_value' => $durationValue,
            'duration_unit' => $durationUnit,
            'end_date' => $end,
            'status' => $status,
            'status_locked' => $statusLocked,
            'visibility' => $visibility,
            'published_at' => $publishedAt,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'cover_image_path' => $coverPath,
            'lead_researcher_id' => $data['lead_researcher_id'] ?? $user->staff_id,
        ];
    }

    /**
     * @param  list<array{title: string, file: UploadedFile}>  $documents
     */
    private function attachDocuments(ResearchProject $project, array $documents, User $user): void
    {
        $sort = (int) $project->documents()->max('sort_order');

        foreach ($documents as $row) {
            if (empty($row['file']) || ! ($row['file'] instanceof UploadedFile)) {
                continue;
            }

            $file = $row['file'];
            $path = $file->store('research-documents/'.$project->id, 'local');
            $sort++;

            ResearchProjectDocument::query()->create([
                'research_project_id' => $project->id,
                'title' => trim((string) ($row['title'] ?: $file->getClientOriginalName())),
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'file_size' => $file->getSize(),
                'sort_order' => $sort,
                'created_at' => now(),
                'created_by' => $user->staff_id,
            ]);
        }
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'research-activity';
        $slug = $base;
        $i = 2;

        while (
            ResearchProject::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function sanitizeBody(string $html): string
    {
        $html = preg_replace('#<(script|iframe|object|embed|form|link|meta|style)[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|iframe|object|embed|form|link|meta)\b[^>]*/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/javascript:/i', '', $html) ?? $html;

        return $html;
    }

    /**
     * @return array{total: int, upcoming: int, ongoing: int, completed: int, paused: int, published: int, draft: int}
     */
    public function dashboardStats(): array
    {
        $base = ResearchProject::query();

        return [
            'total' => (clone $base)->count(),
            'upcoming' => (clone $base)->where('status', 'upcoming')->count(),
            'ongoing' => (clone $base)->where('status', 'ongoing')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'paused' => (clone $base)->where('status', 'paused')->count(),
            'published' => (clone $base)->where('visibility', 'published')->count(),
            'draft' => (clone $base)->where('visibility', 'draft')->count(),
        ];
    }
}
