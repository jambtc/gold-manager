(function () {
    'use strict';

    var cfg = window.GM_REPLAY_CONFIG || {};
    var qs = new URLSearchParams(window.location.search || '');
    var fixtureId = String(cfg.fixtureId || qs.get('id') || '');
    var EVENTS = Array.isArray(cfg.events) ? cfg.events : [];
    var FORMATIONS_URL = String(cfg.formationsUrl || (fixtureId ? ('/fixture/formations?fixtureId=' + encodeURIComponent(fixtureId)) : ''));
    var VIEWER_SIDE = cfg.viewerSide || null;
    var icons = {
        goal: '⚽', gk_save: '🧤', near_miss: '💨', substitution: '🔄',
        tactic_change: '📋', half_time: '🔔', full_time: '🏁',
        kickoff: '🎯', second_half_start: '▶️', yellow_card: '🟨', red_card: '🟥',
        midfield_duel: '⚔️', attack_attempt: '🏃'
    };
    var SHOW_TYPES = new Set([
        'pre_match',
        'goal', 'gk_save', 'near_miss', 'substitution', 'tactic_change',
        'half_time', 'full_time', 'kickoff', 'second_half_start', 'yellow_card', 'red_card',
        'midfield_duel', 'attack_attempt'
    ]);
    var phaseLabels = {
        first_half: 'PRIMO TEMPO', half_time: 'INTERVALLO',
        second_half: 'SECONDO TEMPO', finished: 'FINALE'
    };

    var speedMs = 1000;
    var timer = null;
    var currentMin = 0;
    var evIdx = 0;
    var paused = false;
    var homeGoals = 0;
    var awayGoals = 0;
    var saves = 0;
    var nearMiss = 0;

    var maxMinute = EVENTS.length ? EVENTS[EVENTS.length - 1].minute : 90;

    function el(id) { return document.getElementById(id); }
    function phaseTargetEl() { return el('phase-badge') || el('match-phase'); }
    function setPhaseBadgeHtml(html) {
        var target = phaseTargetEl();
        if (target) { target.innerHTML = html; }
    }

    if (window.GMFixtureFormationUi && typeof window.GMFixtureFormationUi.init === 'function') {
        window.GMFixtureFormationUi.init({
            formationsUrl: FORMATIONS_URL,
            withTabSwitcher: false,
            autoLoad: true,
            viewerSide: VIEWER_SIDE
        });
    }

    function setPhase(phase) {
        var label = phaseLabels[phase] || String(phase || '').replace(/_/g, '').toUpperCase();
        var color = phase === 'finished' ? '#6b7280' : (phase === 'half_time' ? '#f59e0b' : '#10b981');
        setPhaseBadgeHtml(
            '<span class="badge-gm px-3 py-1" style="background:' + color + '22;color:' + color +
            ';border:1px solid ' + color + '44;font-size:.72rem;letter-spacing:.06em">' + label + '</span>'
        );
    }

    function triggerReplayGoalCelebration() {
        var wasPlaying = (timer !== null);
        if (wasPlaying) {
            clearInterval(timer);
            timer = null;
            if (el('btn-play')) { el('btn-play').disabled = true; }
            if (el('btn-pause')) { el('btn-pause').disabled = true; }
        }

        var flash = document.createElement('div');
        flash.className = 'goal-flash-overlay';
        document.body.appendChild(flash);
        setTimeout(function () { flash.remove(); }, 1200);

        var banner = document.createElement('div');
        banner.className = 'goal-banner';
        banner.innerHTML = '<h1 style="color:#f59e0b;font-weight:900;margin:0;font-size:3rem;letter-spacing:0.1em;text-shadow:0 0 10px rgba(0,0,0,0.5)">GOOOOOOL!</h1>' +
            '<p style="margin:0.5rem 0 0 0;font-size:1.2rem;text-transform:uppercase;letter-spacing:0.05em;color:#fff">Rete! Azione straordinaria!</p>';
        document.body.appendChild(banner);

        var duration = 4000;
        var end = Date.now() + duration;
        (function frame() {
            if (typeof confetti === 'function') {
                confetti({ particleCount: 4, angle: 60, spread: 55, origin: { x: 0, y: 0.8 } });
                confetti({ particleCount: 4, angle: 120, spread: 55, origin: { x: 1, y: 0.8 } });
            }
            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        }());

        var chants = ['ALÈ!', 'GOL!', 'SIAMO NOI!', 'FORZA!', 'RETE!'];
        var chantInterval = setInterval(function () {
            var chant = chants[Math.floor(Math.random() * chants.length)];
            var bubble = document.createElement('div');
            bubble.className = 'chant-bubble';
            bubble.textContent = chant;
            bubble.style.left = (20 + Math.random() * 60) + 'vw';
            bubble.style.top = (30 + Math.random() * 40) + 'vh';
            bubble.style.fontSize = (1.5 + Math.random() * 2) + 'rem';
            document.body.appendChild(bubble);
            setTimeout(function () { bubble.remove(); }, 2000);
        }, 300);

        setTimeout(function () {
            clearInterval(chantInterval);
            banner.remove();
            if (wasPlaying && !paused && currentMin < Math.max(90, maxMinute)) {
                timer = setInterval(tick, speedMs);
                if (el('btn-play')) { el('btn-play').disabled = true; }
                if (el('btn-pause')) { el('btn-pause').disabled = false; }
            } else if (!wasPlaying && !paused && currentMin < Math.max(90, maxMinute)) {
                if (el('btn-play')) { el('btn-play').disabled = false; }
                if (el('btn-pause')) { el('btn-pause').disabled = true; }
            }
        }, 4000);
    }

    function appendEvent(ev) {
        var noMsg = el('no-events-msg');
        if (noMsg) { noMsg.remove(); }
        var log = el('event-log');
        if (!log) { return; }
        var item = document.createElement('div');
        item.className = 'event-item';
        item.style.animation = 'fadeIn .3s ease';

        var icon = icons[ev.type] || '·';
        var detail = '';
        if (ev.detail) {
            if (ev.detail.length > 20) {
                detail = '<span class="text-white-50 d-block mt-1 italic-desc" style="font-style: italic; font-size: 0.88rem; color: #a1a1aa !important;">"' + ev.detail + '"</span>';
            } else {
                detail = ' <span style="color:var(--text-secondary);font-size:.78rem">' + ev.detail + '</span>';
            }
        }

        item.innerHTML =
            '<div class="event-time">' + ev.minute + '\'</div>' +
            '<div class="report-event-icon">' + icon + '</div>' +
            '<div style="flex:1"><span class="fw-bold text-white small" style="text-transform:uppercase;letter-spacing:.04em;color:var(--gold) !important">' +
            String(ev.type || '').replace(/_/g, ' ') + '</span>' + detail + '</div>';
        log.insertBefore(item, log.firstChild);

        if (ev.type === 'goal') {
            if (ev.team_side === 'home') { homeGoals++; } else { awayGoals++; }
            if (el('home-score')) { el('home-score').textContent = homeGoals; }
            if (el('away-score')) { el('away-score').textContent = awayGoals; }
            if (el('stat-home-goals')) { el('stat-home-goals').textContent = homeGoals; }
            if (el('stat-away-goals')) { el('stat-away-goals').textContent = awayGoals; }
            if (el('home-score') && el('home-score').parentElement && el('home-score').parentElement.parentElement) {
                el('home-score').parentElement.parentElement.style.textShadow = '0 0 20px var(--gold)';
                setTimeout(function () {
                    if (el('home-score') && el('home-score').parentElement && el('home-score').parentElement.parentElement) {
                        el('home-score').parentElement.parentElement.style.textShadow = '';
                    }
                }, 800);
            }
            triggerReplayGoalCelebration();
        }
        if (ev.type === 'gk_save') {
            saves++;
            if (el('stat-saves')) { el('stat-saves').textContent = saves; }
        }
        if (ev.type === 'near_miss') {
            nearMiss++;
            if (el('stat-near')) { el('stat-near').textContent = nearMiss; }
        }
        if (ev.type === 'half_time') { setPhase('half_time'); }
        if (ev.type === 'second_half_start') { setPhase('second_half'); }
        if (ev.type === 'full_time') {
            setPhase('finished');
            stopReplay();
        }
    }

    function tick() {
        currentMin++;
        if (el('match-clock')) { el('match-clock').textContent = currentMin + '\''; }
        if (el('stat-minute')) { el('stat-minute').textContent = currentMin + '\''; }
        if (el('match-progress-bar')) {
            el('match-progress-bar').style.width = Math.min(100, (currentMin / 90 * 100)) + '%';
        }
        while (evIdx < EVENTS.length && EVENTS[evIdx].minute <= currentMin) {
            if (SHOW_TYPES.has(EVENTS[evIdx].type)) {
                appendEvent(EVENTS[evIdx]);
            }
            evIdx++;
        }
        if (currentMin >= Math.max(90, maxMinute)) {
            stopReplay();
        }
    }

    window.startReplay = function () {
        if (timer) { return; }
        if (currentMin === 0) { setPhase('first_half'); }
        paused = false;
        if (el('btn-play')) { el('btn-play').disabled = true; }
        if (el('btn-pause')) { el('btn-pause').disabled = false; }
        timer = setInterval(tick, speedMs);
    };

    window.pauseReplay = function () {
        clearInterval(timer);
        timer = null;
        if (el('btn-play')) { el('btn-play').disabled = false; }
        if (el('btn-pause')) { el('btn-pause').disabled = true; }
    };

    window.resetReplay = function () {
        clearInterval(timer);
        timer = null;
        currentMin = 0;
        evIdx = 0;
        homeGoals = 0;
        awayGoals = 0;
        saves = 0;
        nearMiss = 0;
        if (el('home-score')) { el('home-score').textContent = '0'; }
        if (el('away-score')) { el('away-score').textContent = '0'; }
        if (el('match-clock')) { el('match-clock').textContent = '0\''; }
        if (el('stat-minute')) { el('stat-minute').textContent = '—'; }
        if (el('stat-home-goals')) { el('stat-home-goals').textContent = '0'; }
        if (el('stat-away-goals')) { el('stat-away-goals').textContent = '0'; }
        if (el('stat-saves')) { el('stat-saves').textContent = '0'; }
        if (el('stat-near')) { el('stat-near').textContent = '0'; }
        if (el('match-progress-bar')) { el('match-progress-bar').style.width = '0%'; }
        if (el('event-log')) {
            el('event-log').innerHTML = '<div id="no-events-msg" class="text-center py-5 text-muted-gm">' +
                '<i class="bi bi-play-circle d-block fs-1 mb-2 opacity-25"></i>Premi Avvia per iniziare il replay</div>';
        }
        if (el('btn-play')) { el('btn-play').disabled = false; }
        if (el('btn-pause')) { el('btn-pause').disabled = true; }
        setPhaseBadgeHtml('<span class="badge-gm px-3 py-1" style="background:rgba(245,158,11,.15);color:var(--gold);border:1px solid rgba(245,158,11,.3);font-size:.72rem;letter-spacing:.06em">PRONTO</span>');
    };

    function stopReplay() {
        clearInterval(timer);
        timer = null;
        if (el('btn-play')) { el('btn-play').disabled = true; }
        if (el('btn-pause')) { el('btn-pause').disabled = true; }
    }

    window.setSpeed = function (ms, btn) {
        speedMs = ms;
        document.querySelectorAll('.speed-btn').forEach(function (button) {
            button.className = button.className.replace('btn-gold', 'btn-outline-secondary');
        });
        if (btn) {
            btn.className = btn.className.replace('btn-outline-secondary', 'btn-gold');
        }
        if (timer) {
            clearInterval(timer);
            timer = setInterval(tick, speedMs);
        }
    };

    function bindReplayUiActions() {
        var btnPlay = el('btn-play');
        var btnPause = el('btn-pause');
        var btnReset = el('btn-reset');
        if (btnPlay) {
            btnPlay.addEventListener('click', function () { window.startReplay(); });
        }
        if (btnPause) {
            btnPause.addEventListener('click', function () { window.pauseReplay(); });
        }
        if (btnReset) {
            btnReset.addEventListener('click', function () { window.resetReplay(); });
        }

        document.querySelectorAll('.speed-btn[data-speed-ms]').forEach(function (button) {
            button.addEventListener('click', function () {
                var ms = parseInt(button.getAttribute('data-speed-ms') || '1000', 10);
                window.setSpeed(ms, button);
            });
        });
    }

    bindReplayUiActions();
}());
