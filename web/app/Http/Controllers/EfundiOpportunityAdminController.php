<?php

namespace App\Http\Controllers;

use App\Models\EfundiOpportunity;
use App\Services\AuditService;
use App\Services\EfundiOpportunityNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class EfundiOpportunityAdminController extends Controller
{
    public function __construct(
        protected AuditService $audit,
        protected EfundiOpportunityNotificationService $notifier,
    ) {}

    public function index(Request $request): View
    {
        $ctx = $this->context($request);

        $opportunities = Schema::hasTable('efundi_opportunities')
            ? EfundiOpportunity::query()->orderByDesc('created_at')->limit(100)->get()
            : collect();

        return view($ctx['viewPrefix'].'.index', [
            ...$ctx,
            'opportunities' => $opportunities,
            'types' => EfundiOpportunity::types(),
            'publicUrl' => route('e-fundi.index'),
        ]);
    }

    public function create(Request $request): View
    {
        $ctx = $this->context($request);

        return view($ctx['viewPrefix'].'.form', [
            ...$ctx,
            'opportunity' => new EfundiOpportunity(['is_published' => true]),
            'types' => EfundiOpportunity::types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $ctx = $this->context($request);
        $validated = $this->validated($request);

        $publish = $request->boolean('is_published');
        $opportunity = EfundiOpportunity::query()->create([
            ...$validated,
            'slug' => EfundiOpportunity::uniqueSlug($validated['title']),
            'is_published' => $publish,
            'published_at' => $publish ? now() : null,
            'posted_via' => $ctx['module'],
            'posted_by_user_id' => $request->user()?->id,
        ]);

        $notified = 0;
        if ($publish) {
            $notified = $this->notifier->notifyAllStudents($opportunity->fresh());
        }

        $this->audit->log(
            'efundi.opportunity.published',
            'efundi_opportunities',
            $opportunity->id,
            null,
            ['posted_via' => $ctx['module'], 'notified_students' => $notified],
            null,
            'success',
            $request->user()?->id,
            $request
        );

        $message = $publish
            ? "Opportunity published. {$notified} students notified."
            : 'Opportunity saved as draft.';

        return redirect()->route($ctx['routePrefix'].'.index')->with('status', $message);
    }

    public function edit(Request $request, EfundiOpportunity $opportunity): View
    {
        $ctx = $this->context($request);

        return view($ctx['viewPrefix'].'.form', [
            ...$ctx,
            'opportunity' => $opportunity,
            'types' => EfundiOpportunity::types(),
        ]);
    }

    public function update(Request $request, EfundiOpportunity $opportunity): RedirectResponse
    {
        $ctx = $this->context($request);
        $validated = $this->validated($request, $opportunity);

        $wasPublished = (bool) $opportunity->is_published;
        $publish = $request->boolean('is_published');

        $opportunity->fill($validated);
        if ($opportunity->isDirty('title')) {
            $opportunity->slug = EfundiOpportunity::uniqueSlug($validated['title'], $opportunity->id);
        }
        $opportunity->is_published = $publish;
        if ($publish && ! $opportunity->published_at) {
            $opportunity->published_at = now();
        }
        if (! $publish) {
            $opportunity->published_at = null;
        }
        $opportunity->save();

        $notified = 0;
        if ($publish && (! $wasPublished || $request->boolean('notify_students'))) {
            $opportunity->forceFill(['students_notified_at' => null])->save();
            $notified = $this->notifier->notifyAllStudents($opportunity->fresh());
        }

        $message = $notified > 0
            ? "Saved. {$notified} students notified."
            : 'Opportunity updated.';

        return redirect()->route($ctx['routePrefix'].'.index')->with('status', $message);
    }

    public function destroy(Request $request, EfundiOpportunity $opportunity): RedirectResponse
    {
        $ctx = $this->context($request);
        $id = $opportunity->id;
        $opportunity->delete();

        $this->audit->log(
            'efundi.opportunity.deleted',
            'efundi_opportunities',
            $id,
            null,
            null,
            null,
            'success',
            $request->user()?->id,
            $request
        );

        return redirect()->route($ctx['routePrefix'].'.index')->with('status', 'Opportunity removed.');
    }

    public function notify(Request $request, EfundiOpportunity $opportunity): RedirectResponse
    {
        $ctx = $this->context($request);
        abort_unless($opportunity->is_published, 422);

        $opportunity->forceFill(['students_notified_at' => null])->save();
        $notified = $this->notifier->notifyAllStudents($opportunity->fresh());

        return redirect()
            ->route($ctx['routePrefix'].'.index')
            ->with('status', "Notification sent to {$notified} students.");
    }

    /** @return array<string, mixed> */
    protected function context(Request $request): array
    {
        $prefix = $request->route()?->getPrefix() ?? '';
        $isMarketing = str_contains($prefix, 'marketing');

        return $isMarketing
            ? [
                'module' => 'marketing',
                'routePrefix' => 'marketing.efundi',
                'viewPrefix' => 'marketing.efundi',
            ]
            : [
                'module' => 'ict',
                'routePrefix' => 'ict.efundi',
                'viewPrefix' => 'ict.efundi',
            ];
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?EfundiOpportunity $existing = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'opportunity_type' => ['required', 'in:'.implode(',', array_keys(EfundiOpportunity::types()))],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:50000'],
            'application_url' => ['nullable', 'url', 'max:500'],
            'application_email' => ['nullable', 'email', 'max:255'],
            'application_deadline' => ['nullable', 'date'],
        ]);
    }
}