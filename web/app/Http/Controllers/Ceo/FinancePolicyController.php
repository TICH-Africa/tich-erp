<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancePolicyService;
use App\Services\Sidebar\CeoSidebarNotificationService;
use App\Services\StaffPortalService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancePolicyController extends Controller
{
    public function __construct(
        protected FinancePolicyService $policies,
        protected StaffPortalService $staffPortal,
        protected StoredFileService $files,
        protected CeoSidebarNotificationService $sidebar,
    ) {}

    public function index(Request $request): View
    {
        $policy = $this->policies->currentPublishedPolicy();
        if (! $policy) {
            return view('ceo.finance-policy.index', [
                'policy' => null,
                'signoff' => null,
                'staff' => $this->staffPortal->staffForUser($request->user()),
                'ceoSigned' => false,
                'document' => null,
            ]);
        }

        $staff = $this->staffPortal->staffForUser($request->user());
        $signoff = $this->policies->signoffProgress($policy);
        $ceoSigned = $policy->signoffs()
            ->where('signed_role', 'CEO')
            ->exists();

        return view('ceo.finance-policy.index', [
            'policy' => $policy,
            'signoff' => $signoff,
            'staff' => $staff,
            'ceoSigned' => $ceoSigned,
            'document' => $this->documentMeta($policy->file_path),
        ]);
    }

    public function sign(Request $request): RedirectResponse
    {
        $policy = $this->policies->currentPublishedPolicy();
        abort_unless($policy, 404);

        $data = $request->validate([
            'signed_name' => ['required', 'string', 'max:200'],
            'employee_number' => ['nullable', 'string', 'max:100'],
            'signature' => ['nullable', 'string', 'max:300'],
        ]);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 403, 'CEO staff profile required to sign.');

        try {
            $this->policies->signOff(
                $policy,
                $request->user(),
                $data,
                $request->ip(),
                $staff->department
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['policy' => $e->getMessage()]);
        }

        $this->sidebar->broadcastCounts();

        return redirect()
            ->route('ceo.finance-policy.index')
            ->with('status', 'Financial policy digitally signed by CEO.');
    }

    public function view(): StreamedResponse
    {
        return $this->stream(true);
    }

    public function download(): StreamedResponse
    {
        return $this->stream(false);
    }

    /**
     * @return array{filename: string, mime: string, size: int}|null
     */
    private function documentMeta(?string $path): ?array
    {
        $relative = $path ? $this->files->relativePath($path) : null;
        if (! $relative || ! Storage::disk('public')->exists($relative)) {
            return null;
        }

        return [
            'filename' => basename($relative),
            'mime' => Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream',
            'size' => (int) Storage::disk('public')->size($relative),
        ];
    }

    private function stream(bool $inline): StreamedResponse
    {
        $policy = $this->policies->currentPublishedPolicy();
        abort_unless($policy, 404);

        $relative = $this->files->relativePath($policy->file_path);
        abort_unless($relative && Storage::disk('public')->exists($relative), 404);

        $filename = preg_replace('/[^\w.\-() ]+/u', '_', basename($relative)) ?: 'policy';
        $mime = Storage::disk('public')->mimeType($relative) ?: 'application/octet-stream';

        if ($inline) {
            return Storage::disk('public')->response($relative, $filename, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        return Storage::disk('public')->download($relative, $filename, ['Content-Type' => $mime]);
    }
}
