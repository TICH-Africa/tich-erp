{{-- Toggle duration/end fields when contract type is permanent. --}}
<script>
    (function () {
        var typeSelect = document.getElementById(@json($contractTypeSelectId));
        if (!typeSelect) return;

        var termBlocks = document.querySelectorAll(@json($termFieldSelector));
        var durationInput = document.getElementById(@json($durationInputId ?? ''));
        var endInput = document.getElementById(@json($endInputId ?? ''));
        var renewableWrap = @json($renewableWrapId ?? null)
            ? document.getElementById(@json($renewableWrapId ?? ''))
            : null;
        var permanentHints = @json($permanentHintSelector ?? null)
            ? document.querySelectorAll(@json($permanentHintSelector ?? '.js-contract-permanent-hint'))
            : [];

        function syncPermanentUi() {
            var permanent = typeSelect.value === 'permanent';
            termBlocks.forEach(function (el) {
                el.style.display = permanent ? 'none' : '';
            });
            if (renewableWrap) {
                renewableWrap.style.display = permanent ? 'none' : '';
            }
            permanentHints.forEach(function (el) {
                el.style.display = permanent ? '' : 'none';
            });
            if (permanent) {
                if (durationInput) durationInput.value = '';
                if (endInput) endInput.value = '';
            }
        }

        typeSelect.addEventListener('change', syncPermanentUi);
        syncPermanentUi();
    })();
</script>
