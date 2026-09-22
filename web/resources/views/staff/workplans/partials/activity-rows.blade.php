@php
    $rows = $activities ?? [];
@endphp
<div class="tich-mt-4" id="workplan-activities">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
        <h3 class="tich-h3">Activities</h3>
        <button type="button" class="tich-btn tich-btn-secondary tich-btn--sm" id="workplan-add-activity">Add row</button>
    </div>
    <div class="tich-table-wrap tich-mt-4">
        <table class="tich-admin-table" id="workplan-activities-table">
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>KPI</th>
                    <th>Resources</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td><input type="text" name="activities[{{ $i }}][activity]" value="{{ old('activities.'.$i.'.activity', $row['activity'] ?? '') }}" class="tich-input" maxlength="500"></td>
                        <td><input type="date" name="activities[{{ $i }}][timeline_start]" value="{{ old('activities.'.$i.'.timeline_start', $row['timeline_start'] ?? '') }}" class="tich-input"></td>
                        <td><input type="date" name="activities[{{ $i }}][timeline_end]" value="{{ old('activities.'.$i.'.timeline_end', $row['timeline_end'] ?? '') }}" class="tich-input"></td>
                        <td><input type="text" name="activities[{{ $i }}][kpi]" value="{{ old('activities.'.$i.'.kpi', $row['kpi'] ?? '') }}" class="tich-input" maxlength="500"></td>
                        <td><input type="text" name="activities[{{ $i }}][resources]" value="{{ old('activities.'.$i.'.resources', $row['resources'] ?? '') }}" class="tich-input" maxlength="500"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('workplan-add-activity');
    var tbody = document.querySelector('#workplan-activities-table tbody');
    if (!btn || !tbody) return;
    btn.addEventListener('click', function () {
        var i = tbody.querySelectorAll('tr').length;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="activities[' + i + '][activity]" class="tich-input" maxlength="500"></td>' +
            '<td><input type="date" name="activities[' + i + '][timeline_start]" class="tich-input"></td>' +
            '<td><input type="date" name="activities[' + i + '][timeline_end]" class="tich-input"></td>' +
            '<td><input type="text" name="activities[' + i + '][kpi]" class="tich-input" maxlength="500"></td>' +
            '<td><input type="text" name="activities[' + i + '][resources]" class="tich-input" maxlength="500"></td>';
        tbody.appendChild(tr);
    });
})();
</script>
