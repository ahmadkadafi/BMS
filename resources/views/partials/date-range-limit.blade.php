{{-- Batasi input #filterFrom / #filterTo maksimal $maxDays hari (inklusif) --}}
<script>
(function () {
    const maxRangeDays = @json($maxDays);
    const fromInput = document.getElementById('filterFrom');
    const toInput = document.getElementById('filterTo');
    const today = toInput.max;

    function shiftDate(value, days) {
        const date = new Date(value + 'T00:00:00');
        date.setDate(date.getDate() + days);
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    function minDate(a, b) {
        return a < b ? a : b;
    }

    function syncLimits() {
        // "Dari" bebas dipilih; "Sampai" dibatasi maksimal maxRangeDays hari setelahnya
        if (fromInput.value) {
            toInput.min = fromInput.value;
            toInput.max = minDate(shiftDate(fromInput.value, maxRangeDays - 1), today);
        }
    }

    fromInput.addEventListener('change', function () {
        if (!fromInput.value) {
            return;
        }
        const latestTo = minDate(shiftDate(fromInput.value, maxRangeDays - 1), today);
        if (!toInput.value || toInput.value < fromInput.value || toInput.value > latestTo) {
            toInput.value = latestTo < fromInput.value ? fromInput.value : latestTo;
        }
        syncLimits();
    });

    toInput.addEventListener('change', function () {
        if (!toInput.value) {
            return;
        }
        const earliestFrom = shiftDate(toInput.value, -(maxRangeDays - 1));
        if (!fromInput.value || fromInput.value > toInput.value || fromInput.value < earliestFrom) {
            fromInput.value = earliestFrom;
        }
        syncLimits();
    });

    syncLimits();
})();
</script>
