{{-- Alumni register table. Expects: $students, $editable (bool), $showRoute (route name for the record) --}}
<div class="tich-card tich-table-panel">
    <div class="tich-table-wrap">
        <table class="tich-admin-table">
            <thead>
                <tr>
                    <th>Graduate</th>
                    <th>Admission no.</th>
                    <th>Programme</th>
                    <th>Graduation year</th>
                    <th>Graduation date</th>
                    <th>Award/class</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td>
                            <strong>{{ $student->displayName() }}</strong>
                            <p class="tich-caption">{{ ucfirst((string) $student->enrollment_status) }}</p>
                        </td>
                        <td class="tich-caption">{{ $student->registration_number ?: '-' }}</td>
                        <td class="tich-caption">{{ $student->program?->program_name ?? '-' }}</td>
                        <td>{{ $student->graduation_year ?: '-' }}</td>
                        <td class="tich-caption">{{ $student->graduation_date?->format('d M Y') ?? '-' }}</td>
                        <td class="tich-caption">{{ $student->graduation_award ?: '-' }}</td>
                        <td class="tich-flex-wrap" style="gap:0.4rem;">
                            @if ($editable)
                                <a href="{{ route('administration.alumni.edit', $student) }}" class="tich-btn tich-btn-secondary" style="padding:0.3rem 0.6rem;font-size:0.82rem;">Edit</a>
                                <form method="POST" action="{{ route('administration.alumni.undo', $student) }}" onsubmit="return confirm('Remove {{ $student->displayName() }} from the alumni register and put them back on the current student list?')">
                                    @csrf
                                    <button type="submit" class="tich-btn tich-btn-ghost" style="padding:0.3rem 0.6rem;font-size:0.82rem;">Remove</button>
                                </form>
                            @else
                                <a href="{{ route($showRoute, $student) }}" class="tich-btn tich-btn-primary" style="padding:0.3rem 0.6rem;font-size:0.82rem;">Open</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    @include('partials.states.table-empty', [
                        'colspan' => 7,
                        'title' => 'No graduates on the register yet',
                        'meta' => 'Register a graduate one at a time, or upload a list of a past cohort.',
                        'icon' => 'users',
                    ])
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($students, 'hasPages') && $students->hasPages())
        <div class="tich-mt-4">{{ $students->links() }}</div>
    @endif
</div>
