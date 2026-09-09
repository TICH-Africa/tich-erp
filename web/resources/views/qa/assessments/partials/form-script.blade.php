<script>
    (function () {
        const container = document.getElementById('qa-items');
        const addBtn = document.getElementById('qa-add-item');
        if (!container || !addBtn) return;

        const renumber = () => {
            container.querySelectorAll('.qa-item-row').forEach((row, i) => {
                const badge = row.querySelector('.qa-criterion__badge');
                if (badge) badge.textContent = 'Criterion ' + (i + 1);
            });
        };

        addBtn.addEventListener('click', function () {
            const index = container.querySelectorAll('.qa-item-row').length;
            const wrap = document.createElement('article');
            wrap.className = 'qa-criterion qa-item-row';
            wrap.innerHTML =
                '<div class="qa-criterion__badge">Criterion ' + (index + 1) + '</div>' +
                '<div class="qa-criterion__fields">' +
                '<div class="tich-form-group qa-criterion__text"><label class="tich-label">Criterion text</label><textarea name="items[' + index + '][text]" class="tich-input" rows="2" required></textarea></div>' +
                '<div class="qa-criterion__meta">' +
                '<div class="tich-form-group qa-criterion__category"><label class="tich-label">Category</label><input name="items[' + index + '][category]" class="tich-input" placeholder="e.g. Teaching, Records"></div>' +
                '<div class="tich-form-group qa-criterion__weight"><label class="tich-label">Weight</label><input type="number" step="0.01" min="0.01" name="items[' + index + '][weight]" class="tich-input" value="1"></div>' +
                '<div class="tich-form-group qa-criterion__score"><label class="tich-label">Max score</label><input type="number" step="1" min="1" name="items[' + index + '][max_score]" class="tich-input" value="100"></div>' +
                '<div class="tich-form-group qa-criterion__evidence"><label class="qa-check"><input type="checkbox" name="items[' + index + '][requires_evidence]" value="1" checked><span>Require evidence upload</span></label></div>' +
                '</div></div>';
            container.appendChild(wrap);
            renumber();
            wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            const focusField = wrap.querySelector('textarea');
            if (focusField) focusField.focus();
        });
    })();
</script>
