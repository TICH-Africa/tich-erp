{{-- When contract type is volunteer: zero gross salary, no pay. --}}
<script>
    (function () {
        var typeSelect = document.getElementById(@json($contractTypeSelectId));
        var grossInput = document.getElementById(@json($grossSalaryInputId));
        if (!typeSelect || !grossInput) return;

        var hint = document.querySelector(@json($volunteerHintSelector ?? '.js-volunteer-hint'));

        function syncVolunteerUi() {
            var volunteer = typeSelect.value === 'volunteer';
            if (volunteer) {
                grossInput.value = '0';
                grossInput.readOnly = true;
                grossInput.removeAttribute('required');
            } else {
                grossInput.readOnly = false;
                if (!grossInput.hasAttribute('data-optional-gross')) {
                    grossInput.setAttribute('required', 'required');
                }
            }
            if (hint) {
                hint.style.display = volunteer ? '' : 'none';
            }
        }

        typeSelect.addEventListener('change', syncVolunteerUi);
        syncVolunteerUi();
    })();
</script>
