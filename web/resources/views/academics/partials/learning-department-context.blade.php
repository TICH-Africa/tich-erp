@if (! empty($learningDepartment))
    <div class="tich-card tich-mb-6" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:1rem; align-items:center;">
        <div>
            <p class="tich-caption">Managing</p>
            <p class="tich-text"><strong>{{ $learningDepartment->dept_name }}</strong> ({{ $learningDepartment->dept_code }})</p>
        </div>
    </div>
@endif
