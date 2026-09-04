<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentLifecycleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LifecycleRequestController extends DepartmentAcademicsController
{
    protected function authorizeReviewer(Request $request, Department $department): Department
    {
        $hub = $this->authorizeHub($request, $department, allowSuggestionsOnly: true);
        abort_unless(
            $request->user()->hasAnyRole([
                'Academic Registrar',
                'Dean of Students',
                'Super Admin',
                'Head of Academics',
            ]),
            403,
            'Only Academic Registrar / Dean of Students can manage deferment requests.'
        );

        return $hub;
    }

    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeReviewer($request, $department);
        $status = (string) $request->query('status', 'pending');

        $requests = StudentLifecycleRequest::query()
            ->with(['student.applicant', 'student.program:id,program_code,program_name'])
            ->where('request_type', 'deferment')
            ->when($status !== '' && $status !== 'all', function ($q) use ($status) {
                if ($status === 'pending') {
                    $q->whereIn('status', ['pending', 'partially_approved', 'on_hold']);
                } else {
                    $q->where('status', $status);
                }
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.lifecycle-requests.index', [
            'department' => $hub,
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest): View
    {
        $hub = $this->authorizeReviewer($request, $department);
        $lifecycleRequest->load([
            'student.applicant',
            'student.program',
            'registrarReviewer',
            'deanReviewer',
        ]);

        $user = $request->user();

        return view('academics.lifecycle-requests.show', [
            'department' => $hub,
            'lifecycleRequest' => $lifecycleRequest,
            'types' => StudentLifecycleRequest::TYPES,
            'canActAsRegistrar' => $user->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']),
            'canActAsDean' => $user->hasAnyRole(['Dean of Students', 'Super Admin']),
        ]);
    }

    public function approve(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest): RedirectResponse
    {
        return $this->decide($request, $department, $lifecycleRequest, 'approved');
    }

    public function reject(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest): RedirectResponse
    {
        return $this->decide($request, $department, $lifecycleRequest, 'rejected', notesRequired: true);
    }

    public function hold(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest): RedirectResponse
    {
        return $this->decide($request, $department, $lifecycleRequest, 'on_hold', notesRequired: true);
    }

    public function downloadAttachment(Request $request, Department $department, StudentLifecycleRequest $lifecycleRequest, int $index)
    {
        $this->authorizeReviewer($request, $department);
        $attachments = $lifecycleRequest->attachments ?? [];
        abort_unless(isset($attachments[$index]['path']), 404);
        $path = $attachments[$index]['path'];
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            $attachments[$index]['original_name'] ?? basename($path)
        );
    }

    protected function decide(
        Request $request,
        Department $department,
        StudentLifecycleRequest $lifecycleRequest,
        string $decision,
        bool $notesRequired = false,
    ): RedirectResponse {
        $this->authorizeReviewer($request, $department);
        abort_unless($lifecycleRequest->isOpenForReview() || $lifecycleRequest->status === 'on_hold', 422);

        $role = (string) $request->input('review_role', '');
        $user = $request->user();
        $asRegistrar = $role === 'registrar' && $user->hasAnyRole(['Academic Registrar', 'Super Admin', 'Head of Academics']);
        $asDean = $role === 'dean' && $user->hasAnyRole(['Dean of Students', 'Super Admin']);
        abort_unless($asRegistrar || $asDean, 403);

        $validated = $request->validate([
            'reviewer_notes' => ($notesRequired ? 'required' : 'nullable').'|string|max:2000',
        ]);

        $notes = $validated['reviewer_notes'] ?? null;

        if ($asRegistrar) {
            abort_unless(in_array($lifecycleRequest->registrar_status, ['pending', 'on_hold'], true), 422);
            $lifecycleRequest->registrar_status = $decision;
            $lifecycleRequest->registrar_notes = $notes;
            $lifecycleRequest->registrar_reviewed_by_user_id = $user->id;
            $lifecycleRequest->registrar_reviewed_at = now();
        } else {
            abort_unless(in_array($lifecycleRequest->dean_status, ['pending', 'on_hold'], true), 422);
            $lifecycleRequest->dean_status = $decision;
            $lifecycleRequest->dean_notes = $notes;
            $lifecycleRequest->dean_reviewed_by_user_id = $user->id;
            $lifecycleRequest->dean_reviewed_at = now();
        }

        $lifecycleRequest->syncOverallStatus();
        $lifecycleRequest->reviewer_notes = trim(collect([
            $lifecycleRequest->registrar_notes ? 'Registrar: '.$lifecycleRequest->registrar_notes : null,
            $lifecycleRequest->dean_notes ? 'Dean: '.$lifecycleRequest->dean_notes : null,
        ])->filter()->implode("\n"));
        $lifecycleRequest->reviewed_by_user_id = $user->id;
        $lifecycleRequest->reviewed_at = now();
        $lifecycleRequest->save();

        $label = match ($decision) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => 'put on hold',
        };

        return redirect()
            ->route('departments.academics.lifecycle-requests.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['lifecycleRequest' => $lifecycleRequest->id]
            ))
            ->with('success', 'Deferment request '.$label.'.');
    }
}
