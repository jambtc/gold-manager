(function () {
    'use strict';

    // ── Countdown ────────────────────────────────────────────────────────────

    function fmtRemaining(sec) {
        sec = Math.max(0, sec | 0);
        var d = Math.floor(sec / 86400);
        var h = Math.floor((sec % 86400) / 3600);
        var m = Math.floor((sec % 3600) / 60);
        var s = sec % 60;
        if (d > 0) return d + 'g ' + h + 'h ' + m + 'm ' + s + 's';
        if (h > 0) return h + 'h ' + m + 'm ' + s + 's';
        if (m > 0) return m + 'm ' + s + 's';
        return s + 's';
    }

    function lockExpiredRow(el) {
        var row = el.closest('[data-expire-lock="order"]');
        if (!row || row.dataset.orderLocked === '1') return;
        row.dataset.orderLocked = '1';
        row.querySelectorAll('input, button, select, textarea').forEach(function (ctrl) {
            ctrl.disabled = true;
            ctrl.classList.replace('btn-gold', 'btn-outline-secondary');
        });
    }

    function tickCountdowns() {
        var now = Math.floor(Date.now() / 1000);
        var modalEl = document.getElementById('staffBidModal');
        var expiredText = (modalEl && modalEl.dataset.expiredText) || 'scaduto';

        document.querySelectorAll('.auction-countdown[data-expires]').forEach(function (el) {
            var exp = parseInt(el.getAttribute('data-expires') || '0', 10);
            var pref = el.getAttribute('data-prefix') || '';
            if (!exp || isNaN(exp)) { el.textContent = '--'; return; }
            var rem = exp - now;
            if (rem <= 0) {
                el.textContent = expiredText;
                el.classList.remove('text-warning');
                el.classList.add('text-danger');
                lockExpiredRow(el);
                return;
            }
            el.textContent = pref + fmtRemaining(rem);
        });
    }

    tickCountdowns();
    setInterval(tickCountdowns, 1000);

    // ── Bid modal ────────────────────────────────────────────────────────────

    var modalEl = document.getElementById('staffBidModal');
    if (!modalEl) return;

    var fmtEuro = function (n) { return '€' + Number(n || 0).toLocaleString('it-IT'); };

    var currentAsk = 0;
    var currentDiscount = 1.00;

    function updateFeePreview() {
        var amountEl = document.getElementById('staffBidModalAmount');
        var feeEl    = document.getElementById('staffBidFeePreview');
        var totalEl  = document.getElementById('staffBidTotalPreview');
        var lenEl    = document.getElementById('staffBidModalContractLength');
        if (!amountEl || !feeEl || !totalEl || !lenEl) return;

        var salary  = parseInt(amountEl.value, 10) || 0;
        var seasons = parseInt(lenEl.value, 10) || 1;
        // estimated fee: salary × seasons × 0.375 (midpoint of 0.25-0.50)
        var fee     = Math.ceil(salary * seasons * 0.375 / 1000) * 1000;
        var total   = salary * seasons;
        feeEl.textContent  = fmtEuro(fee);
        totalEl.textContent = fmtEuro(total);
    }

    document.querySelectorAll('.js-bid-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var now = Math.floor(Date.now() / 1000);
            var exp = parseInt(btn.dataset.expires || '0', 10);
            if (exp > 0 && exp <= now) return;

            currentAsk      = parseInt(btn.dataset.ask || '0', 10);
            currentDiscount = 1.00;
            var current     = parseInt(btn.dataset.currentBid || '0', 10);

            document.getElementById('staffBidModalLabel').textContent = btn.dataset.candidate || '—';
            document.getElementById('staffBidModalRole').textContent  = btn.dataset.role || '';
            document.getElementById('staffBidModalAsk').textContent   = fmtEuro(currentAsk);
            document.getElementById('staffBidModalForm').action       = btn.dataset.action || '';

            // Reset contract length to 1 season
            var lenInput = document.getElementById('staffBidModalContractLength');
            if (lenInput) lenInput.value = 1;
            document.querySelectorAll('.js-contract-len').forEach(function (b) {
                var active = b.dataset.len === '1';
                b.classList.toggle('btn-gold', active);
                b.classList.toggle('btn-outline-secondary', !active);
            });

            var startAmount = current > 0 ? current : currentAsk;
            document.getElementById('staffBidModalAmount').value = startAmount;

            var wrap = document.getElementById('staffBidModalCurrentWrap');
            if (current > 0) {
                wrap.style.display = '';
                document.getElementById('staffBidModalCurrent').textContent = fmtEuro(current);
            } else {
                wrap.style.display = 'none';
            }

            updateFeePreview();
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            setTimeout(function () {
                var el = document.getElementById('staffBidModalAmount');
                if (el) el.focus();
            }, 250);
        });
    });

    // Contract length buttons
    document.querySelectorAll('.js-contract-len').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var len      = parseFloat(btn.dataset.len || '1');
            currentDiscount = parseFloat(btn.dataset.discount || '1.00');
            var lenInput = document.getElementById('staffBidModalContractLength');
            if (lenInput) lenInput.value = len;

            document.querySelectorAll('.js-contract-len').forEach(function (b) {
                var active = b === btn;
                b.classList.toggle('btn-gold', active);
                b.classList.toggle('btn-outline-secondary', !active);
            });

            // Suggest discounted salary
            var amountEl = document.getElementById('staffBidModalAmount');
            if (amountEl) {
                amountEl.value = Math.ceil(currentAsk * currentDiscount / 1000) * 1000;
            }
            updateFeePreview();
        });
    });

    // Update preview on amount change
    var amountInput = document.getElementById('staffBidModalAmount');
    if (amountInput) {
        amountInput.addEventListener('input', updateFeePreview);
    }

    // +15% button inside modal
    var raise15Btn = document.getElementById('staffBidRaise15');
    if (raise15Btn) {
        raise15Btn.addEventListener('click', function () {
            var amountEl = document.getElementById('staffBidModalAmount');
            if (!amountEl) return;
            var current = parseInt(amountEl.value, 10) || currentAsk;
            amountEl.value = Math.ceil(current * 1.15 / 1000) * 1000;
            updateFeePreview();
        });
    }

    // Confirm
    var confirmBtn = document.getElementById('staffBidModalConfirm');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            var amount = parseInt(document.getElementById('staffBidModalAmount').value, 10);
            if (!amount || amount < 1) return;
            document.getElementById('staffBidModalFee').value = amount;
            document.getElementById('staffBidModalForm').submit();
        });
    }
}());
