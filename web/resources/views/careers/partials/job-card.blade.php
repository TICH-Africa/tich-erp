@php
    $deptName = $vacancy->department->dept_name ?? 'General';
    $employment = ucfirst((string) $vacancy->employment_type);
    $slots = (int) ($vacancy->slots_available ?? 1);
    $searchBlob = strtolower(trim(implode(' ', array_filter([
        $vacancy->job_title ?? null,
        $deptName,
        $employment,
        $vacancy->position_grade ?? null,
        $vacancy->job_description ?? null,
        $vacancy->min_qualification ?? null,
        $vacancy->salary_scale ?? null,
        $vacancy->benefits ?? null,
        $vacancy->requirements ?? null,
    ]))));
@endphp
<article
    class="tich-job-card"
    data-live-search-item
    data-search="{{ $searchBlob }}"
    data-department="{{ $vacancy->department_id ?? '' }}"
    data-employment="{{ strtolower((string) $vacancy->employment_type) }}"
>
    <div class="tich-job-card__body">
        <p class="tich-job-card__meta">
            {{ $deptName }}
            ·
            <span>{{ $employment }}</span>
        </p>

        <div class="tich-job-card__badges">
            @if ($vacancy->position_grade)
                <span class="tich-job-card__badge">{{ $vacancy->position_grade }}</span>
            @endif
            <span class="tich-job-card__badge">{{ $slots }} opening{{ $slots === 1 ? '' : 's' }}</span>
        </div>

        <h2 class="tich-job-card__title">{{ $vacancy->job_title }}</h2>

        @if ($vacancy->job_description)
            <p class="tich-job-card__excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($vacancy->job_description), 160) }}</p>
        @endif

        <dl class="tich-job-card__details">
            @if ($vacancy->min_qualification)
                <div>
                    <dt>Minimum qualification</dt>
                    <dd>{{ $vacancy->min_qualification }}</dd>
                </div>
            @endif
            @if ($vacancy->salary_scale)
                <div>
                    <dt>Salary scale</dt>
                    <dd>{{ $vacancy->salary_scale }}</dd>
                </div>
            @endif
            @if ($vacancy->benefits)
                <div>
                    <dt>Benefits</dt>
                    <dd>{{ \Illuminate\Support\Str::limit($vacancy->benefits, 100) }}</dd>
                </div>
            @endif
            <div>
                <dt>Closing date</dt>
                <dd>{{ $vacancy->closing_date?->format('M j, Y') ?? 'Open until filled' }}</dd>
            </div>
        </dl>

        <div class="tich-job-card__actions">
            <a href="{{ route('careers.show', $vacancy) }}" class="tich-job-card__link">View details →</a>
            <a href="{{ route('vacancies.apply.create', $vacancy) }}" class="tich-job-card__apply">Apply now</a>
        </div>
    </div>
</article>
