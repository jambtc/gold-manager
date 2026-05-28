<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\widgets\Alert;
use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <?= $this->render('_head') ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<?= $this->render('_header') ?>

<main id="main" class="flex-grow-1 pt-5" role="main">
    <div class="container pt-4 mb-5">

        <?= Alert::widget() ?>
        
        <div class="animate__animated animate__fadeIn">
            <?= $content ?>
        </div>
    </div>
</main>

<?= $this->render('_footer') ?>

<?php $this->endBody() ?>
<div class="modal fade" id="gmConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content gm-card" style="border:1px solid var(--border);background:var(--card-bg)">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-white">Conferma azione</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <p id="gmConfirmMessage" class="mb-0 text-muted-gm"></p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-gold btn-sm" id="gmConfirmOk">Conferma</button>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    function lockExpiredOrder(el) {
        if (!el) return;
        var lockNode = el.closest('[data-expire-lock="order"]');
        if (!lockNode || lockNode.dataset.orderLocked === '1') return;
        lockNode.dataset.orderLocked = '1';
        lockNode.querySelectorAll('input, button, select, textarea').forEach(function(ctrl){
            ctrl.disabled = true;
            if (ctrl.classList && ctrl.classList.contains('btn-gold')) {
                ctrl.classList.remove('btn-gold');
                ctrl.classList.add('btn-outline-secondary');
            }
        });
    }

    var confirmModalEl = document.getElementById('gmConfirmModal');
    var confirmMessageEl = document.getElementById('gmConfirmMessage');
    var confirmOkEl = document.getElementById('gmConfirmOk');
    var confirmModal = null;
    var confirmResolve = null;

    function ensureConfirmModal() {
        if (!confirmModalEl || !window.bootstrap) return null;
        if (!confirmModal) {
            confirmModal = new bootstrap.Modal(confirmModalEl, {backdrop: 'static', keyboard: true});
            confirmModalEl.addEventListener('hidden.bs.modal', function () {
                if (confirmResolve) {
                    var resolve = confirmResolve;
                    confirmResolve = null;
                    resolve(false);
                }
            });
            if (confirmOkEl) {
                confirmOkEl.addEventListener('click', function () {
                    if (!confirmResolve) return;
                    var resolve = confirmResolve;
                    confirmResolve = null;
                    resolve(true);
                    confirmModal.hide();
                });
            }
        }
        return confirmModal;
    }

    window.gmConfirm = function(message) {
        return new Promise(function(resolve){
            var modal = ensureConfirmModal();
            if (!modal) {
                resolve(window.confirm(message || 'Confermare operazione?'));
                return;
            }
            confirmMessageEl.textContent = message || 'Confermare operazione?';
            confirmResolve = resolve;
            modal.show();
        });
    };

    if (window.yii && typeof window.yii.confirm === 'function') {
        window.yii.confirm = function(message, ok, cancel){
            window.gmConfirm(message).then(function(confirmed){
                if (confirmed) {
                    if (typeof ok === 'function') ok();
                } else {
                    if (typeof cancel === 'function') cancel();
                }
            });
            return false;
        };
    }

    function updateCountdowns(){
        document.querySelectorAll('.auction-countdown[data-expires]').forEach(function(el){
            var exp = parseInt(el.dataset.expires, 10) * 1000;
            var diff = exp - Date.now();
            if(diff <= 0){
                el.textContent = 'Scaduta';
                el.style.color = 'var(--accent-red)';
                lockExpiredOrder(el);
                return;
            }
            var h = Math.floor(diff / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            el.textContent = 'Scade tra ' + (h > 0 ? h + 'h ' : '') + m + 'm';
            if(diff < 900000)       el.style.color = 'var(--accent-red)';
            else if(diff < 3600000) el.style.color = 'var(--gold)';
            else                    el.style.color = '';
        });
    }
    updateCountdowns();
    setInterval(updateCountdowns, 30000);
})();
</script>
</body>
</html>
<?php $this->endPage() ?>
