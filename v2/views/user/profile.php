<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\User $user */
/** @var app\models\Team|null $team */

use yii\helpers\Html;

$this->title = Yii::t('app', 'Profile');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="user-profile">
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger mb-3"><?= Html::encode((string) Yii::$app->session->getFlash('error')) ?></div>
    <?php endif; ?>
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success mb-3"><?= Html::encode((string) Yii::$app->session->getFlash('success')) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted-gm"><?= Yii::t('app', 'Account and linked club data.') ?></p>
        </div>
        <span class="role-badge"><?= Html::encode((string) $user->role) ?></span>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="gm-card h-100">
                <h3 class="h5 text-white mb-3"><?= Yii::t('app', 'Account') ?></h3>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted-gm">Username</span>
                    <span class="text-white fw-bold"><?= Html::encode($user->username) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted-gm"><?= Yii::t('app', 'Status') ?></span>
                    <span class="text-white"><?= Html::encode((string) $user->status) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted-gm"><?= Yii::t('app', 'Created on') ?></span>
                    <span class="text-white"><?= date('d/m/Y H:i', (int) $user->created_at) ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="gm-card h-100">
                <h3 class="h5 text-white mb-3"><?= Yii::t('app', 'Club') ?></h3>
                <?php if ($team): ?>
                    <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                        <span class="text-muted-gm"><?= Yii::t('app', 'Team') ?></span>
                        <span class="text-white fw-bold"><?= Html::encode($team->name) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom border-secondary">
                        <span class="text-muted-gm"><?= Yii::t('app', 'Budget') ?></span>
                        <span class="text-gold fw-bold">EUR <?= number_format((int) $team->budget, 0, ',', '.') ?></span>
                    </div>
                    <div class="pt-3">
                        <div class="text-muted-gm small mb-2"><?= Yii::t('app', 'Club settings') ?></div>
                        <?= Html::beginForm(['/user/profile'], 'post', ['class' => 'row g-2 align-items-end']) ?>
                            <div class="col-12">
                                <label class="form-label text-muted-gm small mb-1"><?= Yii::t('app', 'Team name') ?></label>
                                <input type="text" name="team_name" value="<?= Html::encode((string) $team->name) ?>" class="form-control" maxlength="255" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted-gm small mb-1"><?= Yii::t('app', 'Left color') ?></label>
                                <input type="color" name="color_left" value="<?= Html::encode((string) $team->color_left) ?>" class="form-control form-control-color w-100" style="height:2.25rem;max-width:none">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted-gm small mb-1"><?= Yii::t('app', 'Right color') ?></label>
                                <input type="color" name="color_right" value="<?= Html::encode((string) $team->color_right) ?>" class="form-control form-control-color w-100" style="height:2.25rem;max-width:none">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-outline-gold btn-sm mt-1"><?= Yii::t('app', 'Save club') ?></button>
                            </div>
                        <?= Html::endForm() ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted-gm mb-0"><?= Yii::t('app', 'No team assigned.') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Language Preference ───────────────────────────────────────────── -->
    <div class="col-12">
        <div class="gm-card">
            <h3 class="h5 text-white mb-3"><?= Yii::t('app', 'Preferences') ?></h3>
            <?= Html::beginForm(['/user/profile'], 'post', ['class' => 'd-flex align-items-end gap-3 flex-wrap']) ?>
                <div>
                    <label class="form-label text-muted-gm small mb-1"><?= Yii::t('app', 'Language') ?></label>
                    <select name="language" class="form-select" style="background:#0f172a;border:1px solid var(--border);color:#fff;border-radius:.5rem;min-width:160px">
                        <option value="it-IT" <?= ($user->language ?? 'it-IT') === 'it-IT' ? 'selected' : '' ?>>🇮🇹 Italiano</option>
                        <option value="en-US" <?= ($user->language ?? 'it-IT') === 'en-US' ? 'selected' : '' ?>>🇺🇸 English</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-gold btn-sm"><?= Yii::t('app', 'Save') ?></button>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<!-- ── Telegram Notifications ─────────────────────────────────────────────── -->
<?php
$tgToken  = trim((string) ($user->telegram_bot_token ?? ''));
$tgChatId = trim((string) ($user->telegram_chat_id   ?? ''));
$tgLinked = $tgToken !== '' && $tgChatId !== '';
$tgEnabled = (bool) ($user->telegram_enabled ?? true);
?>
<div class="gm-card mt-4"
     id="tg-root"
     data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>"
     data-csrf-token="<?= Html::encode(Yii::$app->request->getCsrfToken()) ?>"
     data-verify-url="<?= Html::encode(\yii\helpers\Url::to(['/user/telegram-verify'])) ?>"
     data-link-start-url="<?= Html::encode(\yii\helpers\Url::to(['/user/telegram-link-start'])) ?>"
     data-link-confirm-url="<?= Html::encode(\yii\helpers\Url::to(['/user/telegram-link-confirm'])) ?>"
     data-test-url="<?= Html::encode(\yii\helpers\Url::to(['/user/telegram-test'])) ?>"
     data-revoke-url="<?= Html::encode(\yii\helpers\Url::to(['/user/telegram-revoke'])) ?>"
     data-toggle-url="<?= Html::encode(\yii\helpers\Url::to(['/user/telegram-toggle'])) ?>">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="h5 text-white mb-0"><i class="bi bi-telegram text-gold me-2"></i><?= Yii::t('app', 'Telegram notifications') ?></h3>
        <?php if ($tgLinked): ?>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="tg-toggle" <?= $tgEnabled ? 'checked' : '' ?>>
            <label class="form-check-label text-muted-gm" for="tg-toggle"><?= $tgEnabled ? Yii::t('app', 'Active') : Yii::t('app', 'Disabled') ?></label>
        </div>
        <?php endif; ?>
    </div>

    <div class="text-muted-gm small mb-3 p-3 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid var(--border)">
        <ol class="mb-0 ps-3" style="line-height:1.8">
            <li><?= Yii::t('app', 'Open Telegram') ?> → <code>@BotFather</code> → <code>/newbot</code> → <?= Yii::t('app', 'copy the API token') ?></li>
            <li><?= Yii::t('app', 'Paste the token and click') ?> <strong>1) <?= Yii::t('app', 'Verify') ?></strong></li>
            <li><?= Yii::t('app', 'Click') ?> <strong>2) <?= Yii::t('app', 'Generate link') ?></strong> → <strong><?= Yii::t('app', 'Open') ?></strong> → <?= Yii::t('app', 'send') ?> <code>/start</code> <?= Yii::t('app', 'to the bot') ?></li>
            <li><?= Yii::t('app', 'Click') ?> <strong>3) <?= Yii::t('app', 'Confirm') ?></strong> <?= Yii::t('app', 'to register the chat_id') ?></li>
            <li><?= Yii::t('app', 'Click') ?> <strong>4) Test</strong> <?= Yii::t('app', 'to verify receipt') ?></li>
        </ol>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="text-muted-gm small d-block mb-1"><?= Yii::t('app', 'Telegram bot token') ?></label>
            <div class="d-flex gap-2">
                <input type="password" id="tg-token" value="<?= Html::encode($tgToken) ?>"
                       class="form-control" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem"
                       placeholder="123456:ABCDEF..." autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="tg-token-show"><?= Yii::t('app', 'Show') ?></button>
            </div>
        </div>
        <div class="col-md-4">
            <label class="text-muted-gm small d-block mb-1"><?= Yii::t('app', 'Chat ID') ?></label>
            <input type="text" id="tg-chat-id" value="<?= Html::encode($tgChatId) ?>"
                   class="form-control" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem"
                   placeholder="-1001234567890" readonly>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="tg-btn-verify">1) <?= Yii::t('app', 'Verify bot') ?></button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="tg-btn-link-start">2) <?= Yii::t('app', 'Generate link') ?></button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="tg-btn-confirm">3) <?= Yii::t('app', 'Confirm /start') ?></button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="tg-btn-test">4) Test</button>
        <?php if ($tgLinked): ?>
        <button type="button" class="btn btn-outline-danger btn-sm" id="tg-btn-revoke"><?= Yii::t('app', 'Revoke') ?></button>
        <?php endif; ?>
    </div>

    <div id="tg-link-wrap" style="display:none" class="mb-3">
        <label class="text-muted-gm small d-block mb-1"><?= Yii::t('app', 'One-time deep link') ?></label>
        <div class="d-flex gap-2">
            <input type="text" id="tg-deep-link" class="form-control"
                   style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem" readonly>
            <a href="#" id="tg-deep-link-open" class="btn btn-gold btn-sm px-3" target="_blank"><?= Yii::t('app', 'Open') ?></a>
        </div>
    </div>

    <div id="tg-status" class="text-muted-gm small">
        <?= $tgLinked
            ? '<span style="color:var(--accent-green)">✅ ' . Yii::t('app', 'Telegram connected') . ($tgEnabled ? ' ' . Yii::t('app', 'and active') : ' (' . Yii::t('app', 'notifications disabled') . ')') . '</span>'
            : Yii::t('app', 'Use the buttons above to connect your Telegram bot.') ?>
    </div>
</div>

<script>
(function () {
    var root      = document.getElementById('tg-root');
    var csrf      = { param: root.dataset.csrfParam, token: root.dataset.csrfToken };
    var tokenHash = '';

    function post(url, extra, cb) {
        var body = csrf.param + '=' + encodeURIComponent(csrf.token);
        Object.keys(extra || {}).forEach(function (k) { body += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(extra[k]); });
        fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(cb)
            .catch(function (e) { setStatus('❌ Errore di rete: ' + e.message, 'var(--accent-red)'); });
    }

    function setStatus(msg, color) {
        var el = document.getElementById('tg-status');
        el.innerHTML = '<span style="color:' + (color || 'var(--text-secondary)') + '">' + msg + '</span>';
    }

    // Show/hide token
    document.getElementById('tg-token-show').addEventListener('click', function () {
        var inp = document.getElementById('tg-token');
        inp.type = inp.type === 'password' ? 'text' : 'password';
        this.textContent = inp.type === 'password' ? 'Mostra' : 'Nascondi';
    });

    // 1) Verify
    document.getElementById('tg-btn-verify').addEventListener('click', function () {
        var token = document.getElementById('tg-token').value.trim();
        if (!token) { setStatus('Inserisci il token.'); return; }
        setStatus('Verifica in corso…');
        post(root.dataset.verifyUrl, { token: token }, function (r) {
            setStatus(r.ok ? '✅ Bot verificato: @' + (r.username || '') : '❌ ' + (r.error || 'Errore'), r.ok ? 'var(--accent-green)' : 'var(--accent-red)');
        });
    });

    // 2) Generate deep link
    document.getElementById('tg-btn-link-start').addEventListener('click', function () {
        var token = document.getElementById('tg-token').value.trim();
        if (!token) { setStatus('Verifica prima il bot.'); return; }
        setStatus('Generazione link…');
        post(root.dataset.linkStartUrl, { token: token }, function (r) {
            if (!r.ok) { setStatus('❌ ' + (r.error || 'Errore'), 'var(--accent-red)'); return; }
            tokenHash = r.token_hash || '';
            var wrap = document.getElementById('tg-link-wrap');
            var inp  = document.getElementById('tg-deep-link');
            var open = document.getElementById('tg-deep-link-open');
            inp.value = r.deep_link || '';
            open.href = r.deep_link || '#';
            wrap.style.display = '';
            setStatus('🔗 Apri il link e invia /start al bot, poi clicca Conferma.', 'var(--gold)');
        });
    });

    // 3) Confirm /start
    document.getElementById('tg-btn-confirm').addEventListener('click', function () {
        if (!tokenHash) { setStatus('Genera prima il link.'); return; }
        var token = document.getElementById('tg-token').value.trim();
        setStatus('Ricerca chat_id…');
        post(root.dataset.linkConfirmUrl, { token: token, token_hash: tokenHash }, function (r) {
            if (r.found) {
                document.getElementById('tg-chat-id').value = r.chat_id || '';
                setStatus('✅ Chat ID trovato: ' + (r.chat_id || ''), 'var(--accent-green)');
            } else {
                setStatus('⏳ Chat ID non trovato. Hai inviato /start al bot?', 'var(--gold)');
            }
        });
    });

    // 4) Test
    document.getElementById('tg-btn-test').addEventListener('click', function () {
        setStatus('Invio messaggio di test…');
        post(root.dataset.testUrl, {}, function (r) {
            setStatus(r.ok ? '✅ Messaggio inviato! Controlla Telegram.' : '❌ Invio fallito: ' + (r.error || ''), r.ok ? 'var(--accent-green)' : 'var(--accent-red)');
        });
    });

    // Revoke
    var revokeBtn = document.getElementById('tg-btn-revoke');
    if (revokeBtn) {
        revokeBtn.addEventListener('click', function () {
            var run = function () {
                post(root.dataset.revokeUrl, {}, function (r) {
                    if (r.ok) location.reload();
                });
            };
            if (typeof window.gmConfirm === 'function') {
                window.gmConfirm('Revocare il collegamento Telegram?').then(function (ok) {
                    if (ok) run();
                });
                return;
            }
            if (confirm('Revocare il collegamento Telegram?')) {
                run();
            }
        });
    }

    // Toggle
    var toggleChk = document.getElementById('tg-toggle');
    if (toggleChk) {
        toggleChk.addEventListener('change', function () {
            post(root.dataset.toggleUrl, {}, function (r) {
                var lbl = toggleChk.nextElementSibling;
                if (lbl) lbl.textContent = r.enabled ? 'Attive' : 'Disattivate';
            });
        });
    }
}());
</script>
