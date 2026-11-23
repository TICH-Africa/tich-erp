<?php

namespace App\Http\Controllers;

use App\Models\EfundiOpportunity;
use App\Models\JobVacancy;
use App\Services\AuditService;
use App\Services\EfundiOpportunityNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
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
            ? EfundiOpportunity::query()->with('jobVacancy')->orderByDesc('created_at')->limit(100)->get()
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
            'opportunity' => new EfundiOpportunity([
                'is_published' => true,
                'opportunity_scope' => EfundiOpportunity::SCOPE_EXTERNAL,
            ]),
            'types' => EfundiOpportunity::types(),
            'scopes' => EfundiOpportunity::scopes(),
            'linkableVacancies' => $this->linkableVacancies(),
            'publicUrl' => route('e-fundi.index'),
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
            'scopes' => EfundiOpportunity::scopes(),
            'linkableVacancies' => $this->linkableVacancies($opportunity),
            'publicUrl' => route('e-fundi.index'),
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'opportunity_type' => ['required', 'in:'.implode(',', array_keys(EfundiOpportunity::types()))],
            'opportunity_scope' => ['required', Rule::in([EfundiOpportunity::SCOPE_EXTERNAL, EfundiOpportunity::SCOPE_INTERNAL])],
            'job_vacancy_id' => ['nullable', 'integer', Rule::exists('job_vacancies', 'id')],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:50000'],
            'application_url' => ['nullable', 'string', 'max:500'],
            'application_email' => ['nullable', 'string', 'max:255'],
            'application_deadline' => ['nullable', 'date'],
        ]);

        $validator = validator($validated);
        $this->validateApplicationContact($validator, $validated);
        $validator->validate();

        $validated = $validator->validated();

        if ($validated['opportunity_scope'] === EfundiOpportunity::SCOPE_INTERNAL) {
            $request->validate([
                'job_vacancy_id' => ['required', 'integer', Rule::exists('job_vacancies', 'id')],
            ]);

            $vacancy = JobVacancy::query()->findOrFail((int) $validated['job_vacancy_id']);
            abort_unless($vacancy->is_published, 422, 'Link a vacancy that HR has published on the careers page.');

            $validated['application_url'] = null;
            $validated['application_email'] = null;
        } else {
            $validated['job_vacancy_id'] = null;
        }

        return $validated;
    }

    /** @param  array<string, mixed>  $validated */
    protected function validateApplicationContact(Validator $validator, array &$validated): void
    {
        $url = trim((string) ($validated['application_url'] ?? ''));
        $email = trim((string) ($validated['application_email'] ?? ''));

        $validated['application_url'] = $url !== '' ? $url : null;
        $validated['application_email'] = $email !== '' ? $email : null;

        $validator->after(function (Validator $v) use (&$validated): void {
            $url = (string) ($validated['application_url'] ?? '');
            $email = (string) ($validated['application_email'] ?? '');

            if ($url !== '') {
                if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
                    $v->errors()->add('application_url', 'Use Application email for email addresses, not Application link.');

                    return;
                }

                if (! preg_match('#^https?://#i', $url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                    $v->errors()->add('application_url', 'Application link must be a valid URL starting with http:// or https://.');
                }
            }

            if ($email !== '') {
                if (preg_match('#^[a-z]+://#i', $email) || str_contains($email, 'www.')) {
                    $v->errors()->add('application_email', 'Use Application link for website URLs, not Application email.');

                    return;
                }

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $v->errors()->add('application_email', 'Application email must be a valid email address.');
                }
            }
        });
    }

    protected function linkableVacancies(?EfundiOpportunity $current = null): Collection
    {
        if (! Schema::hasTable('job_vacancies')) {
            return collect();
        }

        return JobVacancy::query()
            ->with('department:id,dept_name')
            ->where(function ($q) use ($current) {
                EfundiOpportunity::applyOpenVacancyQuery($q);
                if ($current?->job_vacancy_id) {
                    $q->orWhere('id', $current->job_vacancy_id);
                }
            })
            ->orderBy('job_title')
            ->get(['id', 'job_title', 'vacancy_number', 'employment_type', 'is_published', 'is_closed', 'closing_date', 'department_id']);
    }
}