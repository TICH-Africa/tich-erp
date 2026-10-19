<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\UnitAllocation;
use App\Models\UnitContent;
use App\Services\StaffMobilePortalSerializer;
use App\Services\StoredFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected StaffMobilePortalSerializer $serializer,
        protected StoredFileService $files,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->serializer->content($this->portalStaff($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);

        $validated = $request->validate([
            'unit_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:300'],
            'content_text' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        abort_unless(
            UnitAllocation::query()
                ->where('staff_id', $staff->id)
                ->where('unit_id', (int) $validated['unit_id'])
                ->where('is_active', 1)
                ->exists(),
            403,
            'You are not assigned to this unit.'
        );

        $filePath = null;
        $originalFilename = null;
        $mimeType = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $this->files->store($file, 'learning-content', 'public');
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
        }

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('unit_id', (int) $validated['unit_id'])
            ->where('is_active', 1)
            ->first();

        UnitContent::query()->create([
            'unit_id' => (int) $validated['unit_id'],
            'unit_allocation_id' => $allocation?->id,
            'created_by' => $staff->id,
            'title' => $validated['title'],
            'content_type' => $filePath ? 'document' : 'lesson_note',
            'content_text' => $validated['content_text'] ?? null,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'status' => 'published',
            'published_at' => now(),
            'display_order' => 0,
        ]);

        return response()->json([
            'message' => 'Material posted to students.',
            ...$this->serializer->content($staff),
        ], 201);
    }
}
