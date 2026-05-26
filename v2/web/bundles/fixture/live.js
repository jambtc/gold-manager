(function () {
    const cfg = window.GM_LIVE_CONFIG || {};
    const qs = new URLSearchParams(window.location.search || '');
    const fixtureId = String(cfg.fixtureId || qs.get('id') || '');
    const POLL_URL    = String(cfg.pollUrl || (fixtureId ? ('/fixture/get-events?fixtureId=' + encodeURIComponent(fixtureId)) : ''));
    const UPDATES_URL = String(cfg.updatesUrl || (fixtureId ? ('/fixture/get-event-updates?fixtureId=' + encodeURIComponent(fixtureId)) : ''));
    const STREAM_URL  = String(cfg.streamUrl || (fixtureId ? ('/stream/' + encodeURIComponent(fixtureId)) : ''));
    const SCORERS_URL = String(cfg.scorersUrl || (fixtureId ? ('/fixture/get-scorers?fixtureId=' + encodeURIComponent(fixtureId)) : ''));
    const FORMATIONS_URL = String(cfg.formationsUrl || (fixtureId ? ('/fixture/formations?fixtureId=' + encodeURIComponent(fixtureId)) : ''));
    const POLL_MS   = 3000;
    const UPDATE_MS = 2000;
    const USER_SIDE = cfg.userSide || null;
    const HOME_NAME = String(cfg.homeName || '');
    const AWAY_NAME = String(cfg.awayName || '');
    const INITIAL_STYLE = String(cfg.initialStyle || 'balanced');
    const INITIAL_MARKING = String(cfg.initialMarking || 'zone');
    const INITIAL_OFFSIDE = Number(cfg.initialOffside || 0);
    const INITIAL_FOCUS = String(cfg.initialFocus || '');
    const TRAINED_LABELS = cfg.trainedLabels || {};
    const FIXTURE_STATUS = Number(cfg.fixtureStatus || 0);
    const STATUS_SCHEDULED = 0;
    const STATUS_PLAYING = 1;
    const STATUS_FINISHED = 2;

    const SUSPENSE_ENABLED = !!cfg.suspenseEnabled;
    const COMMENTARY_STREAM_ENABLED = !!cfg.commentaryStreamEnabled;
    const SUSPENSE_REVEAL_MS = Number(cfg.suspenseDelayMs || 0);
    const AFTER_REVEAL_PAUSE_MS = 1200;
    const GOAL_CELEBRATION_MS = 5000;
    const QUIET_PHRASES_FROM_DB = Array.isArray(cfg.quietPhrases) ? cfg.quietPhrases : [];
    const QUIET_PLAYERS = Array.isArray(cfg.quietPlayers) ? cfg.quietPlayers : [];

    function appendQueryParam(url, key, value) {
        if (!url) {
            return '';
        }
        const separator = url.indexOf('?') >= 0 ? '&' : '?';
        return url + separator + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
    }

    const icons = {
        pre_match: '🏟️',
        goal: '⚽',
        gk_save: '🧤',
        near_miss: '💨',
        substitution: '🔄',
        tactic_change: '📋',
        half_time: '🔔',
        full_time: '🏁',
        kickoff: '🎯',
        second_half_start: '▶️',
        yellow_card: '🟨',
        red_card: '🟥',
        injury: '🚑',
        corner: '🚩',
        woodwork: '🥅',
        post: '🥅',
        crossbar: '🥅'
    };

    const SHOW_TYPES = new Set([
        'pre_match',
        'goal',
        'gk_save',
        'near_miss',
        'substitution',
        'tactic_change',
        'half_time',
        'full_time',
        'kickoff',
        'second_half_start',
        'yellow_card',
        'red_card',
        'injury',
        'corner',
        'woodwork',
        'post',
        'crossbar'
    ]);

    const SUSPENSE_TYPES = new Set([
        'goal',
        'gk_save',
        'near_miss',
        'corner',
        'woodwork',
        'post',
        'crossbar'
    ]);
    const STREAMABLE_TYPES = new Set(['pre_match', 'goal', 'half_time', 'full_time']);

    let lastId = Number(cfg.lastEventId || 0);

    /*
     * backendFinished:
     * il backend ha già finito di simulare la partita.
     *
     * frontendFinished:
     * il frontend ha finito di mostrare la partita all'utente.
     *
     * NON sono la stessa cosa.
     */
    let backendFinished = !!cfg.isFinished;
    let frontendFinished = false;

    let pollTimer = null;
    let reconnectTimer = null;
    let updateTimer = null;
    let eventSource = null;

    // SSE stale detection
    let lastSseHeartbeat = 0;
    let sseWatchdogTimer = null;
    let reconnectAttempt = 0;
    const SSE_STALE_MS  = 20000;
    const SSE_WATCH_MS  = 10000;

    // ── SIP-0064: Attack Indicator ──────────────────────────────
    var _aiPos = 50, _aiDecay = null;

    function aiSet(target, surge) {
        _aiPos = Math.max(2, Math.min(98, target));
        var dot = document.getElementById('ai-dot');
        var fh  = document.getElementById('ai-fill-home');
        var fa  = document.getElementById('ai-fill-away');
        if (!dot) return;
        dot.classList.toggle('ai-surge', !!surge);
        dot.style.left = _aiPos + '%';
        if (fh) fh.style.width = (_aiPos > 50 ? (_aiPos - 50) * 2 : 0) + '%';
        if (fa) fa.style.width = (_aiPos < 50 ? (50 - _aiPos) * 2 : 0) + '%';
    }

    function aiUpdate(evType, side) {
        if (_aiDecay) { clearTimeout(_aiDecay); _aiDecay = null; }
        var isHome = side === 'home';
        if (evType === 'goal') {
            aiSet(isHome ? 97 : 3, true);
            setTimeout(function () { aiSet(50, false); }, 1800);
        } else if (evType === 'gk_save' || evType === 'near_miss') {
            var surge   = isHome ? 83 : 17;
            var rebound = isHome ? 61 : 39;
            aiSet(surge, true);
            setTimeout(function () { aiSet(rebound, false); }, 500);
            _aiDecay = setTimeout(function () { aiSet(_aiPos + (50 - _aiPos) * 0.5, false); }, 3500);
        } else if (evType === 'half_time' || evType === 'full_time' || evType === 'kickoff') {
            aiSet(50, false);
        } else if (evType === 'attack') {
            var drift = isHome ? Math.min(76, _aiPos + 14) : Math.max(24, _aiPos - 14);
            aiSet(drift, false);
            _aiDecay = setTimeout(function () { aiSet(_aiPos + (50 - _aiPos) * 0.35, false); }, 2800);
        }
    }
    // ────────────────────────────────────────────────────────────

    let currentMin = Number(cfg.initialMinute || 0);
    let seenEventIds = {};
    let pendingUpdateIds = {};
    let quietTicks = 0;
    let suspenseLock = false;
    let pendingEvents = [];
    let lastEventMinute = 0;
    let pollingFallbackActive = false;
    const DEV_LIVE_UI = !!cfg.isDevLiveUi;
    let debugLive = DEV_LIVE_UI && localStorage.getItem('gm_live_debug') === '1';
    let streamBuffers = {};

    function dbg() {
        if (!debugLive) {
            return;
        }
        var args = Array.prototype.slice.call(arguments);
        args.unshift('[GM LIVE]');
        console.log.apply(console, args);
    }

    function setTransportStatus(mode, extra) {
        var el = document.getElementById('live-transport-badge');
        if (!el) {
            return;
        }

        var label = mode;
        if (extra) {
            label += ' · ' + extra;
        }

        el.textContent = 'LIVE: ' + label;
        var banner = document.getElementById('half-time-banner');
        if (mode === 'HALF_TIME' && banner) {
            banner.classList.remove('d-none');
            if (countdown) { countdown.textContent = '15'; }
        }
        el.style.borderColor = 'var(--border)';
        el.style.background = 'rgba(255,255,255,.04)';
        el.style.color = 'var(--text-secondary)';

        if (mode === 'SSE') {
            el.style.borderColor = 'rgba(34,197,94,.5)';
            el.style.background = 'rgba(34,197,94,.12)';
            el.style.color = '#86efac';
            return;
        }
        if (mode === 'CONNECTING') {
            el.style.borderColor = 'rgba(96,165,250,.5)';
            el.style.background = 'rgba(96,165,250,.12)';
            el.style.color = '#93c5fd';
            return;
        }
        if (mode === 'WAIT') {
            el.style.borderColor = 'rgba(148,163,184,.5)';
            el.style.background = 'rgba(148,163,184,.12)';
            el.style.color = '#cbd5e1';
            return;
        }
        if (mode === 'POLL') {
            el.style.borderColor = 'rgba(245,158,11,.5)';
            el.style.background = 'rgba(245,158,11,.12)';
            el.style.color = '#fcd34d';
            return;
        }
        if (mode === 'HALF_TIME') {
            el.style.borderColor = 'rgba(59,130,246,.6)';
            el.style.background = 'rgba(59,130,246,.18)';
            el.style.color = '#bfdbfe';
            return;
        }
        if (mode === 'RETRY') {
            el.style.borderColor = 'rgba(239,68,68,.5)';
            el.style.background = 'rgba(239,68,68,.12)';
            el.style.color = '#fca5a5';
        }
    }

    function syncDebugToggleUi() {
        var btn = document.getElementById('live-debug-toggle');
        if (!btn) {
            return;
        }
        btn.textContent = debugLive ? 'DBG ON' : 'DBG OFF';
        btn.classList.toggle('active', debugLive);
    }

    function styleLabel(style) {
        if (style === 'ultra_defensive') return 'Difensivo';
        if (style === 'all_out_attack') return 'Offensivo';
        return 'Bilanciato';
    }

    function updateHalfTimeUi(state) {
        var banner = document.getElementById('half-time-banner');
        var countdown = document.getElementById('half-time-countdown');
        var phase = normalizePhase(state && state.phase);
        if (phase !== 'half_time') {
            if (banner) banner.classList.add('d-none');
            return;
        }
        if (banner) banner.classList.remove('d-none');
        if (countdown) {
            var ticks = Number(state && state.half_time_ticks || 0);
            var remaining = Math.max(0, 15 - ticks);
            countdown.textContent = String(remaining);
        }
    }

    function markingLabel(marking) {
        return marking === 'man' ? 'A uomo' : 'A zona';
    }

    function offsideLabel(offside) {
        return parseInt(offside, 10) > 0 ? 'Sì' : 'No';
    }

    function focusLabel(focus) {
        return (TRAINED_LABELS && TRAINED_LABELS[focus]) ? TRAINED_LABELS[focus] : 'N/D';
    }

    var LIVE_STYLE = INITIAL_STYLE || 'balanced';
    var LIVE_MARKING = INITIAL_MARKING || 'zone';
    var LIVE_OFFSIDE = parseInt(INITIAL_OFFSIDE, 10) > 0 ? 1 : 0;
    var LIVE_FOCUS = INITIAL_FOCUS || '';

    function setButtonActive(id, isActive) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('is-active', !!isActive);
    }

    function syncLiveButtons(style, marking, offside) {
        setButtonActive('btn-style-def', style === 'ultra_defensive');
        setButtonActive('btn-style-bal', style === 'balanced');
        setButtonActive('btn-style-att', style === 'all_out_attack');

        setButtonActive('btn-mark-zone', marking === 'zone');
        setButtonActive('btn-mark-man', marking === 'man');

        var offsideNum = parseInt(offside, 10) > 0 ? 1 : 0;
        setButtonActive('btn-offside-yes', offsideNum === 1);
        setButtonActive('btn-offside-no', offsideNum === 0);
    }

    function setLiveTacticBadge(style, marking, offside, focus) {
        LIVE_STYLE = style || LIVE_STYLE || 'balanced';
        LIVE_MARKING = marking || LIVE_MARKING || 'zone';
        LIVE_OFFSIDE = parseInt(offside, 10) > 0 ? 1 : 0;
        LIVE_FOCUS = focus || LIVE_FOCUS || '';

        var bStyle = document.getElementById('live-badge-style');
        var bMark = document.getElementById('live-badge-marking');
        var bOff = document.getElementById('live-badge-offside');
        var bFocus = document.getElementById('live-badge-focus');
        if (bStyle) bStyle.textContent = 'Stile: ' + styleLabel(LIVE_STYLE);
        if (bMark) bMark.textContent = 'Marcatura: ' + markingLabel(LIVE_MARKING);
        if (bOff) bOff.textContent = 'Fuorigioco: ' + offsideLabel(LIVE_OFFSIDE);
        if (bFocus) bFocus.textContent = 'Tattica: ' + focusLabel(LIVE_FOCUS);
        syncLiveButtons(LIVE_STYLE, LIVE_MARKING, LIVE_OFFSIDE);
    }

    window.toggleLiveDebug = function () {
        if (!DEV_LIVE_UI) {
            return;
        }
        debugLive = !debugLive;
        localStorage.setItem('gm_live_debug', debugLive ? '1' : '0');
        syncDebugToggleUi();
        dbg('debug mode enabled');
    };

    function startClock() {
        // SIP-0042: clock is server-driven, no local timer.
    }

    var QUIET_PHRASES_FALLBACK = [
        'Fase interlocutoria a centrocampo, le squadre si studiano.',
        'Possesso palla prolungato senza azioni pericolose.',
        'Ritmi blandi in questo frangente del match.',
        'Le difese tengono, nessuna conclusione degna di nota.',
        'Squadre corte, il gioco langue nella zona mediana.',
        'Pressione alta ma senza sbocchi, il portiere è spettatore.',
        'Bella geometria di passaggi, manca però la verticalizzazione.',
        'Cambio tattico nell\'aria, entrambi gli allenatori pensano.',
        'Il pubblico attende una scossa, ma il campo tace.',
        'Fase di studio: le energie si gestiscono in vista del finale.'
    ];
    var QUIET_PHRASES = (Array.isArray(QUIET_PHRASES_FROM_DB) && QUIET_PHRASES_FROM_DB.length > 0)
        ? QUIET_PHRASES_FROM_DB
        : QUIET_PHRASES_FALLBACK;

    var PHASE_LABELS = {
        not_started: 'IN ATTESA',
        first_half: 'PRIMO TEMPO',
        half_time: 'INTERVALLO',
        second_half: 'SECONDO TEMPO',
        finished: 'FINALE'
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setPhaseLabel(phase) {
        var el = document.getElementById('match-phase');
        var banner = document.getElementById('half-time-banner');
        var countdown = document.getElementById('half-time-countdown');
        var normalized = normalizePhase(phase);

        if (!el || !normalized) {
            return;
        }

        el.textContent = PHASE_LABELS[normalized] || String(normalized).replace(/_/g, ' ').toUpperCase();
        if (banner) {
            if (normalized === 'half_time') {
                banner.classList.remove('d-none');
                if (countdown) { countdown.textContent = '15'; }
            } else {
                banner.classList.add('d-none');
            }
        }
    }

    function normalizePhase(phase) {
        if (!phase) {
            return '';
        }

        return String(phase).trim().toLowerCase();
    }

    function updateHalfTimeCountdown(ticks) {
        var countdown = document.getElementById('half-time-countdown');
        if (!countdown) { return; }
        var remaining = Math.max(0, 15 - (Number(ticks) || 0));
        countdown.textContent = String(remaining);
    }

    function pauseTimers() {
        // SIP-0042: keep SSE/poll active, buffer incoming events while suspense is playing.
    }

    function resumeTimers() {
        if (frontendFinished || suspenseLock || pendingEvents.length === 0) {
            return;
        }

        while (!suspenseLock && pendingEvents.length > 0) {
            processEvent(pendingEvents.shift());
        }
    }

    function teamBadge(teamSide) {
        var isOurs = USER_SIDE && teamSide === USER_SIDE;
        var name = teamSide === 'home' ? HOME_NAME : AWAY_NAME;
        var color = isOurs ? 'var(--gold)' : '#f87171';
        var bg = isOurs ? 'rgba(245,158,11,.15)' : 'rgba(248,113,113,.12)';
        var pfx = isOurs ? '🛡️ ' : '⚔️ ';

        return '<span style="font-size:.62rem;font-weight:700;background:' + bg + ';color:' + color +
            ';padding:.08rem .35rem;border-radius:.25rem;white-space:nowrap">' +
            escapeHtml(pfx + name) +
            '</span>';
    }

    function renderItem(item, ev, suspenseMode) {
        var isKickoff = ev.type === 'kickoff' || ev.type === 'pre_match';
        var minute = ev.type === 'pre_match' ? 'PRE' : (isKickoff ? '0' : ev.minute);
        var icon = suspenseMode ? '⏳' : icons[ev.type] || '·';

        var labelColor = ev.type === 'goal'
            ? 'var(--gold)'
            : ev.type === 'gk_save'
                ? 'var(--accent-blue)'
                : 'var(--text-primary)';

        var header = isKickoff ? '' : teamBadge(ev.team_side);

        if (suspenseMode) {
            header += '<span style="font-weight:700;font-size:.75rem;letter-spacing:.04em;color:var(--text-secondary)">...</span>';
        } else {
            var label = String(ev.type || '').replace(/_/g, ' ').toUpperCase();

            // Type label
            header += '<span style="font-weight:700;font-size:.75rem;letter-spacing:.04em;color:' + labelColor + '">' +
                escapeHtml(label) +
                '</span>';

            // Goal: score + scorer
            if (ev.type === 'goal') {
                if (ev.home_score !== null && ev.home_score !== undefined) {
                    header += '<span style="font-weight:700;color:var(--gold);font-size:.75rem">&nbsp;' +
                        escapeHtml(ev.home_score) + '–' + escapeHtml(ev.away_score) +
                        '</span>';
                }
                if (ev.scorer_name) {
                    header += '<span style="font-size:.7rem;color:var(--gold);font-weight:600">&nbsp;⚽ ' +
                        escapeHtml(ev.scorer_name) + '</span>';
                }
            }

            // Substitution: out → in
            if (ev.type === 'substitution' && (ev.out_name || ev.in_name)) {
                header += '<span style="font-size:.7rem;color:var(--text-secondary)">&nbsp;' +
                    escapeHtml(ev.out_name || '?') + ' ↗ ' +
                    escapeHtml(ev.in_name  || '?') + '</span>';
            }

            // Yellow/red card + injury: player name
            if ((ev.type === 'yellow_card' || ev.type === 'red_card' || ev.type === 'injury') && ev.player_name) {
                var playerColor = ev.type === 'yellow_card' ? '#fbbf24'
                    : ev.type === 'red_card' ? '#f87171' : '#fca5a5';
                header += '<span style="font-size:.7rem;color:' + playerColor + ';font-weight:600">&nbsp;' +
                    escapeHtml(ev.player_name) + '</span>';
            }
        }

        var body = '';

        if (suspenseMode) {
            body = '<div style="font-size:.8rem;font-style:italic;color:var(--text-secondary);margin-top:.08rem">' +
                escapeHtml(ev.suspense_text || '...') +
                '</div>';
        } else {
            var desc = ev.description || '';

            body = desc
                ? '<div class="event-description" style="font-size:.8rem;font-style:italic;color:var(--text-secondary);margin-top:.08rem">"' +
                    escapeHtml(desc) +
                  '"</div>'
                : '';
        }

        item.innerHTML =
            '<div class="event-time">' + escapeHtml(minute) + '\'</div>' +
            '<div class="report-event-icon">' + icon + '</div>' +
            '<div style="flex:1;min-width:0" data-event-body="1">' +
                '<div style="display:flex;align-items:center;gap:.2rem;flex-wrap:wrap;margin-bottom:.05rem">' +
                    header +
                '</div>' +
                body +
            '</div>';
    }

    function trackPendingUpdate(ev) {
        if (!ev || !ev.id) {
            return;
        }

        if (ev.description) {
            delete pendingUpdateIds[ev.id];
            return;
        }

        pendingUpdateIds[ev.id] = true;
    }

    function updateEventDescription(ev) {
        if (!ev || !ev.id || !ev.description) {
            return;
        }

        var item = document.querySelector('[data-event-id="' + ev.id + '"]');
        if (!item) {
            return;
        }

        var desc = item.querySelector('.event-description');
        var html = '"' + escapeHtml(ev.description) + '"';

        if (desc) {
            desc.innerHTML = html;
        } else {
            var body = item.querySelector('[data-event-body]');
            if (body) {
                body.insertAdjacentHTML(
                    'beforeend',
                    '<div class="event-description" style="font-size:.8rem;font-style:italic;color:var(--text-secondary);margin-top:.08rem">' + html + '</div>'
                );
            }
        }

        delete pendingUpdateIds[ev.id];
        item.style.transition = 'background .3s';
        item.style.background = 'rgba(59,130,246,.08)';
        setTimeout(function () { item.style.background = ''; }, 600);

        if (ev.weather || ev.field_condition || ev.spectators) {
            updatePreMatchInfo(ev);
        }
    }

    function updatePreMatchInfo(ev) {
        var weatherEl = document.getElementById('info-weather');
        var fieldEl = document.getElementById('info-field');
        var spectatorsEl = document.getElementById('info-spectators');

        if (weatherEl && ev.weather) {
            weatherEl.textContent = ev.weather;
        }
        if (fieldEl && ev.field_condition) {
            fieldEl.textContent = ev.field_condition;
        }
        if (spectatorsEl && ev.spectators !== null && ev.spectators !== undefined) {
            var n = parseInt(ev.spectators, 10);
            spectatorsEl.textContent = Number.isNaN(n) ? String(ev.spectators) : n.toLocaleString('it-IT');
        }
    }

    function ensureEventDescriptionElement(eventId) {
        var item = document.querySelector('[data-event-id="' + eventId + '"]');
        if (!item) {
            return null;
        }

        var desc = item.querySelector('.event-description');
        if (!desc) {
            var body = item.querySelector('[data-event-body]');
            if (!body) {
                return null;
            }
            body.insertAdjacentHTML(
                'beforeend',
                '<div class="event-description" style="font-size:.8rem;font-style:italic;color:var(--text-secondary);margin-top:.08rem"></div>'
            );
            desc = item.querySelector('.event-description');
        }

        return desc;
    }

    function startTypedStream(eventId) {
        if (!eventId) {
            return;
        }
        streamBuffers[eventId] = '';
        var desc = ensureEventDescriptionElement(eventId);
        if (desc) {
            desc.textContent = '';
        }
    }

    function appendTypedToken(eventId, token) {
        if (!eventId || !token) {
            return;
        }
        if (!Object.prototype.hasOwnProperty.call(streamBuffers, eventId)) {
            streamBuffers[eventId] = '';
        }
        streamBuffers[eventId] += token;
        var desc = ensureEventDescriptionElement(eventId);
        if (desc) {
            desc.textContent = '"' + streamBuffers[eventId];
        }
    }

    function finalizeTypedStream(eventId, fullText) {
        if (!eventId) {
            return;
        }
        var finalText = fullText || streamBuffers[eventId] || '';
        if (!finalText) {
            delete streamBuffers[eventId];
            return;
        }
        var item = document.querySelector('[data-event-id="' + eventId + '"]');
        if (!item) {
            streamBuffers[eventId] = finalText;
            return;
        }
        delete streamBuffers[eventId];
        updateEventDescription({ id: eventId, description: finalText });
    }

    function applyBufferedStreamToItem(eventId) {
        if (!eventId || !Object.prototype.hasOwnProperty.call(streamBuffers, eventId)) {
            return;
        }
        var desc = ensureEventDescriptionElement(eventId);
        if (desc) {
            desc.textContent = '"' + streamBuffers[eventId];
        }
    }

    function handleCommentaryPayload(payload) {
        if (!payload || !payload.id) {
            return;
        }
        var phase = String(payload.phase || '').toLowerCase();
        if (phase === 'start') {
            if (COMMENTARY_STREAM_ENABLED) {
                startTypedStream(payload.id);
            }
            return;
        }
        if (phase === 'token') {
            if (COMMENTARY_STREAM_ENABLED) {
                appendTypedToken(payload.id, payload.token || '');
            }
            return;
        }
        if (phase === 'done') {
            var fullText = payload.description || '';
            if (COMMENTARY_STREAM_ENABLED) {
                finalizeTypedStream(payload.id, fullText);
            } else if (fullText) {
                updateEventDescription({ id: payload.id, description: fullText });
            }
        }
    }

    function buildItem(ev, suspenseMode) {
        var item = document.createElement('div');
        item.className = 'event-item';
        if (ev.id) {
            item.setAttribute('data-event-id', ev.id);
        }
        renderItem(item, ev, suspenseMode);
        trackPendingUpdate(ev);
        if (ev.id) {
            applyBufferedStreamToItem(ev.id);
        }
        return item;
    }

    function revealInPlace(item, ev) {
        renderItem(item, ev, false);
        trackPendingUpdate(ev);

        item.style.transition = 'background .3s';
        item.style.background = 'rgba(245,158,11,.08)';

        setTimeout(function () {
            item.style.background = '';
        }, 600);
    }

    function showBroadcast(icon, html, colorVar) {
        var noMsg = document.getElementById('no-events-msg');

        if (noMsg) {
            noMsg.remove();
        }

        var log = document.getElementById('event-log');
        var item = document.createElement('div');

        item.className = 'event-item';
        item.style.cssText = 'background:rgba(255,255,255,.03);border-bottom:1px solid var(--border)';

        item.innerHTML =
            '<div class="event-time" style="color:' + (colorVar || 'var(--text-secondary)') + '">—</div>' +
            '<div class="report-event-icon">' + icon + '</div>' +
            '<div style="flex:1;font-size:.8rem;font-style:italic;color:var(--text-secondary)">' +
                html +
            '</div>';

        log.insertBefore(item, log.firstChild);

        return item;
    }

    function processEvent(ev) {
        if (ev && ev.id) {
            seenEventIds[ev.id] = true;
        }

        var noMsg = document.getElementById('no-events-msg');

        if (noMsg) {
            noMsg.remove();
        }

        var log = document.getElementById('event-log');

        if (!log) {
            return false;
        }

        if (ev && ev.minute !== undefined && ev.minute !== null) {
            lastEventMinute = parseInt(ev.minute, 10) || 0;
            updateClock(lastEventMinute);
        }

        // ── PRE_MATCH: cerimonia prima del fischio d'inizio ──────────────
        if (ev.type === 'pre_match') {
            // Fase 1: messaggio LLM pre-partita (dallo stadio)
            var desc = ev.description || 'I giocatori scendono in campo tra il boato del pubblico.';
            showBroadcast('🏟️', '<strong style="color:var(--accent-green)">Pre-partita</strong><br><em>' + desc + '</em>', 'var(--accent-green)');
            updatePreMatchInfo(ev);
            return false;
        }

        if (ev.type === 'kickoff') {
            log.insertBefore(buildItem(ev, false), log.firstChild);
            // If somehow kickoff arrives without pre_match (CPU match), start clock
            startClock();
            return false;
        }

        var hasSuspense = Boolean(
            SUSPENSE_ENABLED &&
            ev.suspense_text &&
            ev.suspense_text.length > 0 &&
            SUSPENSE_TYPES.has(ev.type)
        );

        if (ev.type === 'goal') {
            suspenseLock = true;
            pauseTimers();

            var goalItem = buildItem(ev, hasSuspense);
            log.insertBefore(goalItem, log.firstChild);

            var revealGoal = function () {
                revealInPlace(goalItem, ev);

                if (ev.home_score !== null && ev.home_score !== undefined) {
                    var homeScore = document.getElementById('home-score');
                    var awayScore = document.getElementById('away-score');

                    if (homeScore) {
                        homeScore.textContent = ev.home_score;
                    }

                    if (awayScore) {
                        awayScore.textContent = ev.away_score;
                    }
                }

                triggerGoalCelebration();
                refreshScorerSheet();
                aiUpdate('goal', ev.team_side);
            };

            if (hasSuspense) {
                setTimeout(revealGoal, SUSPENSE_REVEAL_MS);
            } else {
                revealGoal();
            }

            return false;
        }

        if (ev.type === 'half_time') {
            aiUpdate('half_time', ev.team_side);
            suspenseLock = true;
            pauseTimers();

            log.insertBefore(buildItem(ev, false), log.firstChild);
            setPhaseLabel('half_time');
            updateHalfTimeUi({ phase: 'half_time', half_time_ticks: 0 });

            var htBanner = showBroadcast(
                '📊',
                '<strong style="color:var(--gold)">Analisi del primo tempo</strong><br>' +
                    escapeHtml(ev.description || 'Riepilogo in elaborazione...'),
                'var(--gold)'
            );

            setTimeout(function () {
                if (htBanner.parentNode) {
                    htBanner.remove();
                }

                suspenseLock = false;
                resumeTimers();
            }, 25000);
            return false;
        }

            return false;
        }

        if (ev.type === 'second_half_start') {
            log.insertBefore(buildItem(ev, false), log.firstChild);
            setPhaseLabel('second_half');
            updateHalfTimeUi({ phase: 'second_half', half_time_ticks: 15 });
            return false;
        }

        if (ev.type === 'full_time') {
            aiUpdate('full_time', ev.team_side);
            log.insertBefore(buildItem(ev, false), log.firstChild);

            backendFinished = true;
            frontendFinished = true;
            suspenseLock = true;
            pauseTimers();

            var ftBanner = showBroadcast(
                '📋',
                '<strong style="color:var(--text-secondary)">Riepilogo della partita</strong><br>' +
                    escapeHtml(ev.description || 'Analisi finale in elaborazione...'),
                null
            );

            setTimeout(function () {
                setPhaseLabel('finished');

                if (ftBanner.parentNode) {
                    ftBanner.remove();
                }

                var minuteEl = document.getElementById('match-minute');
                var progressEl = document.getElementById('match-progress-bar');

                if (minuteEl) {
                    minuteEl.textContent = '90';
                }

                if (progressEl) {
                    progressEl.style.width = '100%';
                }
            }, 18000);

            return false;
        }

        if (ev.type === 'second_half_start') {
            log.insertBefore(buildItem(ev, false), log.firstChild);
            setPhaseLabel('second_half');
            return false;
        }

        if (ev.type === 'tactic_change') {
            if (USER_SIDE && ev.team_side === USER_SIDE) {
                var styleVal = ev.tactic || INITIAL_STYLE || 'balanced';
                var markingVal = ev.marking || INITIAL_MARKING || 'zone';
                var offsideVal = (ev.offside_trap !== undefined) ? ev.offside_trap : INITIAL_OFFSIDE;
                var focusVal = ev.trained_tactic || INITIAL_FOCUS || '';

                setLiveTacticBadge(styleVal, markingVal, offsideVal, focusVal);
            }
        }

        // Attack indicator for suspense events
        if (ev.type === 'gk_save' || ev.type === 'near_miss') {
            aiUpdate(ev.type, ev.team_side);
        } else if (ev.type === 'attack_attempt' || ev.type === 'midfield_duel') {
            aiUpdate('attack', ev.team_side);
        }

        // Refresh formations on substitution
        if (ev.type === 'substitution' && window._refreshFormationsOnSub) {
            window._refreshFormationsOnSub();
        }

        var evItem = buildItem(ev, hasSuspense);
        log.insertBefore(evItem, log.firstChild);

        if (hasSuspense) {
            suspenseLock = true;
            pauseTimers();

            setTimeout(function () {
                revealInPlace(evItem, ev);

                setTimeout(function () {
                    suspenseLock = false;
                    resumeTimers();
                }, AFTER_REVEAL_PAUSE_MS);
            }, SUSPENSE_REVEAL_MS);
        }

        return false;
    }

    function addQuietComment(minute) {
        var noMsg = document.getElementById('no-events-msg');

        if (noMsg) {
            noMsg.remove();
        }

        var log = document.getElementById('event-log');

        if (!log) {
            return;
        }

        var item = document.createElement('div');
        var phraseTpl = QUIET_PHRASES[Math.floor(Math.random() * QUIET_PHRASES.length)];
        var phrase = (function renderQuietPhrase(template, min) {
            if (!template) {
                return '';
            }
            var homeScoreEl = document.getElementById('home-score');
            var awayScoreEl = document.getElementById('away-score');
            var player = (Array.isArray(QUIET_PLAYERS) && QUIET_PLAYERS.length > 0)
                ? QUIET_PLAYERS[Math.floor(Math.random() * QUIET_PLAYERS.length)]
                : 'un giocatore';
            var out = String(template);
            out = out.replaceAll('{home_team}', HOME_NAME || 'la squadra di casa');
            out = out.replaceAll('{away_team}', AWAY_NAME || 'la squadra ospite');
            out = out.replaceAll('{player_attacker}', player);
            out = out.replaceAll('{player_defender}', 'il difensore');
            out = out.replaceAll('{player_gk}', 'il portiere');
            out = out.replaceAll('{player_assist}', 'un compagno');
            out = out.replaceAll('{player_in}', 'il nuovo entrato');
            out = out.replaceAll('{player_out}', 'il giocatore uscente');
            out = out.replaceAll('{minute}', String(min || '?'));
            out = out.replaceAll('{score_home}', homeScoreEl ? String(homeScoreEl.textContent || '0') : '0');
            out = out.replaceAll('{score_away}', awayScoreEl ? String(awayScoreEl.textContent || '0') : '0');
            // Clean any unresolved placeholder left from future tokens.
            out = out.replace(/\{[a-z_]+\}/gi, '').replace(/\s{2,}/g, ' ').trim();
            return out;
        })(phraseTpl, minute);

        item.className = 'event-item';
        item.innerHTML =
            '<div class="event-time" style="color:var(--text-secondary);opacity:.6">' +
                escapeHtml(minute) +
            '\'</div>' +
            '<div class="report-event-icon" style="opacity:.4">·</div>' +
            '<div style="flex:1;font-size:.78rem;font-style:italic;color:var(--text-secondary);opacity:.7">' +
                escapeHtml(phrase) +
            '</div>';

        log.insertBefore(item, log.firstChild);
    }

    function updateClock(minute) {
        if (minute === undefined || minute === null || isNaN(minute)) {
            return;
        }

        var parsed = parseInt(minute, 10);
        if (parsed < 0) {
            parsed = 0;
        }

        if (parsed > currentMin) {
            if (lastEventMinute !== parsed) {
                quietTicks++;
                if (quietTicks > 0 && quietTicks % 4 === 0 && parsed < 88) {
                    addQuietComment(parsed);
                }
            } else {
                quietTicks = 0;
            }
        }

        currentMin = Math.max(currentMin, parsed);

        var minuteEl = document.getElementById('match-minute');
        if (minuteEl) {
            minuteEl.textContent = currentMin;
        }

        var progressEl = document.getElementById('match-progress-bar');
        if (progressEl) {
            var pct = Math.min(100, currentMin / 90 * 100);
            progressEl.style.width = pct + '%';
        }
    }

    function updateScore(homeScore, awayScore) {
        if (homeScore === undefined || awayScore === undefined || homeScore === null || awayScore === null) {
            return;
        }

        var homeEl = document.getElementById('home-score');
        var awayEl = document.getElementById('away-score');
        if (homeEl) {
            homeEl.textContent = homeScore;
        }
        if (awayEl) {
            awayEl.textContent = awayScore;
        }
    }

    function applyStateTick(payload) {
        if (!payload) {
            return;
        }

        updateClock(payload.minute);

        // SIP-0064: smooth attack indicator if Go sends attack_side in state
        if (payload.attack_side) {
            aiUpdate('attack', payload.attack_side);
        }

        // Derive phase from minute if Go worker doesn't send it or sends 'not_started'
        var phase = payload.phase;
        var normalized = normalizePhase(phase);
        if ((!normalized || normalized === 'not_started') && payload.minute > 0) {
            if (payload.minute < 45) phase = 'first_half';
            else if (payload.minute === 45) phase = 'half_time';
            else if (payload.minute <= 90) phase = 'second_half';
            else phase = 'finished';
        }
        setPhaseLabel(phase);
        if (normalizePhase(phase) === 'half_time') {
            updateHalfTimeUi(payload);
        }

        if (payload.phase && normalizePhase(payload.phase) === 'finished') {
            backendFinished = true;
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
            if (!frontendFinished) {
                setPhaseLabel('finished');
                var progressEl = document.getElementById('match-progress-bar');
                if (progressEl) {
                    progressEl.style.width = '100%';
                }
            }
        }

        updateScore(payload.home_score, payload.away_score);
    }

    function refreshScorerSheet() {
        fetch(SCORERS_URL, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (!data.success) return;
                var sheet = data.sheet || {home:[],away:[]};
                var el = document.getElementById('scorer-sheet');
                if (!el) return;

                var home = sheet.home || [];
                var away = sheet.away || [];
                if (!home.length && !away.length) {
                    el.innerHTML = '<div class="text-muted-gm text-center py-2" style="font-size:.78rem">Nessun gol</div>';
                    return;
                }

                var fmtSide = function(scorers, side) {
                    if (!scorers.length) return '';
                    var col = side === 'home' ? (USER_SIDE === 'home' ? 'var(--gold)' : '#94a3b8') : (USER_SIDE === 'away' ? 'var(--gold)' : '#94a3b8');
                    return scorers.map(function(s) {
                        var pen = s.is_penalty ? ' <span style="font-size:.62rem;opacity:.7">(R)</span>' : '';
                        return '<div style="color:' + col + ';font-size:.78rem;padding:.1rem 0">⚽ ' + escapeHtml(s.player_name) + ' <span style="color:var(--text-secondary)">' + s.minute + "'" + '</span>' + pen + '</div>';
                    }).join('');
                };

                var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">'
                    + '<div style="text-align:left">' + fmtSide(home, 'home') + '</div>'
                    + '<div style="text-align:right">' + fmtSide(away, 'away') + '</div>'
                    + '</div>';
                el.innerHTML = html;
            })
            .catch(function(){});
    }

    function triggerGoalCelebration() {
        pauseTimers();

        var flash = document.createElement('div');
        flash.className = 'goal-flash-overlay';
        document.body.appendChild(flash);

        setTimeout(function () {
            flash.remove();
        }, 1200);

        var banner = document.createElement('div');
        banner.className = 'goal-banner';
        banner.innerHTML =
            '<h1 style="color:#f59e0b;font-weight:900;margin:0;font-size:3rem;letter-spacing:0.1em;text-shadow:0 0 10px rgba(0,0,0,0.5)">GOOOOOOL!</h1>' +
            '<p style="margin:0.5rem 0 0 0;font-size:1.2rem;text-transform:uppercase;letter-spacing:0.05em;color:#fff">Rete! La partita si infiamma!</p>';

        document.body.appendChild(banner);

        var duration = 4000;
        var end = Date.now() + duration;

        (function frame() {
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 4,
                    angle: 60,
                    spread: 55,
                    origin: { x: 0, y: 0.8 }
                });

                confetti({
                    particleCount: 4,
                    angle: 120,
                    spread: 55,
                    origin: { x: 1, y: 0.8 }
                });
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
            bubble.style.left = 20 + Math.random() * 60 + 'vw';
            bubble.style.top = 30 + Math.random() * 40 + 'vh';
            bubble.style.fontSize = 1.5 + Math.random() * 2 + 'rem';

            document.body.appendChild(bubble);

            setTimeout(function () {
                bubble.remove();
            }, 2000);
        }, 300);

        setTimeout(function () {
            clearInterval(chantInterval);
            banner.remove();

            suspenseLock = false;
            resumeTimers();
        }, GOAL_CELEBRATION_MS);
    }

    function enqueueEvent(ev) {
        if (!ev || !SHOW_TYPES.has(ev.type)) {
            return;
        }

        if (ev.id && seenEventIds[ev.id]) {
            updateEventDescription(ev);
            return;
        }

        if (ev.id > lastId) {
            lastId = ev.id;
        }

        if (suspenseLock) {
            var alreadyBuffered = pendingEvents.some(function (queuedEv) {
                return queuedEv.id === ev.id;
            });
            if (!alreadyBuffered) {
                pendingEvents.push(ev);
                pendingEvents.sort(function (a, b) {
                    if (a.minute === b.minute) {
                        return (a.id || 0) - (b.id || 0);
                    }
                    return (a.minute || 0) - (b.minute || 0);
                });
            }
            return;
        }

        processEvent(ev);
    }

    function refreshPendingUpdates() {
        var ids = Object.keys(pendingUpdateIds);
        if (ids.length === 0) {
            return;
        }

        fetch(appendQueryParam(UPDATES_URL, 'ids', ids.join(',')), {
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) {
                    return;
                }

                (data.events || []).forEach(function (ev) {
                    updateEventDescription(ev);
                });
            })
            .catch(function () {});
    }

    function normalizeStreamEvent(payload) {
        if (!payload || payload.type !== 'event') {
            return null;
        }

        return {
            id: payload.id || null,
            minute: payload.minute,
            type: payload.event_type || payload.type,
            team_side: payload.team_side || 'home',
            suspense_text: payload.suspense_text || null,
            description: payload.description || null,
            home_score: payload.home_score ?? null,
            away_score: payload.away_score ?? null,
            weather: payload.weather ?? null,
            field_condition: payload.field_condition ?? null,
            spectators: payload.spectators ?? null
        };
    }

    function startEventStream() {
        if (!window.EventSource || eventSource) {
            if (!window.EventSource) {
                setTransportStatus('POLL', 'no-sse');
                enablePollingFallback();
            }
            return;
        }

        if (FIXTURE_STATUS === STATUS_SCHEDULED) {
            setTransportStatus('WAIT', 'pre-match');
        } else {
            setTransportStatus('CONNECTING');
        }
        eventSource = new EventSource(STREAM_URL);
        dbg('opening SSE', STREAM_URL);

        eventSource.onopen = function () {
            pollingFallbackActive = false;
            reconnectAttempt = 0;
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
            lastSseHeartbeat = Date.now();
            setTransportStatus('SSE');
            dbg('SSE connected');
            startSseWatchdog();
        };

        eventSource.onmessage = function (message) {
            lastSseHeartbeat = Date.now(); // reset stale timer on any message
            try {
                var payload = JSON.parse(message.data);

                if (payload.type === 'event_update') {
                    updateEventDescription(payload);
                    return;
                }
                if (payload.type === 'commentary_event') {
                    handleCommentaryPayload(payload);
                    return;
                }
                if (payload.type === 'llm_stream_start') {
                    handleCommentaryPayload({ type: 'commentary_event', phase: 'start', id: payload.id, source: 'llm' });
                    return;
                }
                if (payload.type === 'llm_stream_token') {
                    handleCommentaryPayload({ type: 'commentary_event', phase: 'token', id: payload.id, token: payload.token || '', source: 'llm' });
                    return;
                }
                if (payload.type === 'llm_stream_done') {
                    handleCommentaryPayload({ type: 'commentary_event', phase: 'done', id: payload.id, description: payload.description || '', source: 'llm' });
                    return;
                }
                if (payload.type === 'commentary_stream_start') {
                    handleCommentaryPayload({ type: 'commentary_event', phase: 'start', id: payload.id, source: 'template' });
                    return;
                }
                if (payload.type === 'commentary_stream_token') {
                    handleCommentaryPayload({ type: 'commentary_event', phase: 'token', id: payload.id, token: payload.token || '', source: 'template' });
                    return;
                }
                if (payload.type === 'commentary_stream_done') {
                    handleCommentaryPayload({ type: 'commentary_event', phase: 'done', id: payload.id, description: payload.description || '', source: 'template' });
                    return;
                }
                if (payload.type === 'event_reveal') {
                    updateEventDescription({ id: payload.id, description: payload.description || '' });
                    return;
                }

                if (payload.type === 'state') {
                    applyStateTick(payload);
                    return;
                }

                var ev = normalizeStreamEvent(payload);
                if (ev) {
                    enqueueEvent(ev);
                }
            } catch (e) {
                dbg('SSE parse error', e);
                return;
            }
        };

        eventSource.onerror = function (reason) {
            dbg('SSE error', reason);
            closeSse();
            enablePollingFallback('sse-error');
        };
    }

    function closeSse() {
        stopSseWatchdog();
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
    }

    function startSseWatchdog() {
        stopSseWatchdog();
        sseWatchdogTimer = setInterval(function () {
            if (!eventSource) { stopSseWatchdog(); return; }
            var age = Date.now() - lastSseHeartbeat;
            if (age > SSE_STALE_MS) {
                dbg('SSE stale (' + Math.round(age / 1000) + 's) → fallback');
                closeSse();
                enablePollingFallback('stale');
            }
        }, SSE_WATCH_MS);
    }

    function stopSseWatchdog() {
        if (sseWatchdogTimer) {
            clearInterval(sseWatchdogTimer);
            sseWatchdogTimer = null;
        }
    }

    function enablePollingFallback(reason) {
        if (pollingFallbackActive) {
            return;
        }

        pollingFallbackActive = true;
        // Only show RETRY in debug; regular users see nothing
        setTransportStatus('RETRY', reason || 'fallback');
        dbg('poll fallback active, reason:', reason);
        poll(true);
        pollTimer = setInterval(function () {
            poll(false);
        }, POLL_MS);

        // Exponential backoff: 8s, 16s, 32s, max 60s
        if (!reconnectTimer) {
            reconnectAttempt++;
            var delay = Math.min(8000 * Math.pow(2, reconnectAttempt - 1), 60000);
            dbg('SSE reconnect in', delay / 1000 + 's (attempt ' + reconnectAttempt + ')');
            setTransportStatus('RETRY', 'retry ' + Math.round(delay / 1000) + 's');
            reconnectTimer = setTimeout(function () {
                reconnectTimer = null;
                if (pollingFallbackActive) {
                    pollingFallbackActive = false;
                    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                }
                startEventStream();
            }, delay);
        }
    }

    function poll(force) {
        if (backendFinished && !force) {
            return;
        }

        fetch(appendQueryParam(POLL_URL, 'lastEventId', lastId), {
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.success) {
                    return;
                }
                dbg('poll events', (data.events || []).length, 'lastId', lastId);

                (data.events || []).forEach(function (ev) {
                    enqueueEvent(ev);
                });

                if (data.state) {
                    applyStateTick(data.state);
                }
            })
            .catch(function () {
                /*
                 * Errore silenzioso: riproverà al prossimo polling.
                 */
                dbg('poll error');
            });
    }

    window.sendTactic = function (tactic, options) {
        var status = document.getElementById('live-action-status');
        var opts = options || {};
        var styleVal = tactic || LIVE_STYLE || 'balanced';
        var markingVal = opts.marking || LIVE_MARKING || 'zone';
        var offsideVal = (opts.offside_trap !== undefined) ? String(opts.offside_trap) : String(LIVE_OFFSIDE);
        var focusVal = (opts.trained_tactic !== undefined) ? opts.trained_tactic : (LIVE_FOCUS || '');
        var prevStyle = LIVE_STYLE;
        var prevMarking = LIVE_MARKING;
        var prevOffside = LIVE_OFFSIDE;
        var prevFocus = LIVE_FOCUS;

        if (!status) {
            return;
        }

        status.style.display = 'block';
        status.textContent = 'Aggiorno assetto live…';
        setLiveTacticBadge(styleVal, markingVal, offsideVal, focusVal);

        fetch(String(cfg.tacticUrl || ''), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: encodeURIComponent(String(cfg.csrfParam || '_csrf')) + '=' + encodeURIComponent(String(cfg.csrfToken || ''))
                + '&tactic=' + encodeURIComponent(styleVal)
                + '&marking=' + encodeURIComponent(markingVal)
                + '&offside_trap=' + encodeURIComponent(offsideVal)
                + '&trained_tactic=' + encodeURIComponent(focusVal),
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                status.textContent = data.message || (data.success ? 'Inviato!' : 'Errore');
                if (data.success) {
                    setLiveTacticBadge(styleVal, markingVal, offsideVal, focusVal);
                } else {
                    setLiveTacticBadge(prevStyle, prevMarking, prevOffside, prevFocus);
                }

                setTimeout(function () {
                    status.style.display = 'none';
                }, 3000);
            })
            .catch(function () {
                setLiveTacticBadge(prevStyle, prevMarking, prevOffside, prevFocus);
                status.textContent = 'Errore durante l\'invio del comando.';

                setTimeout(function () {
                    status.style.display = 'none';
                }, 3000);
            });
    };

    window.sendLiveMarking = function (marking) {
        window.sendTactic(LIVE_STYLE || 'balanced', {
            marking: marking,
            offside_trap: LIVE_OFFSIDE,
            trained_tactic: LIVE_FOCUS || ''
        });
    };

    window.sendLiveOffside = function (offside) {
        window.sendTactic(LIVE_STYLE || 'balanced', {
            marking: LIVE_MARKING || 'zone',
            offside_trap: parseInt(offside, 10) > 0 ? 1 : 0,
            trained_tactic: LIVE_FOCUS || ''
        });
    };

    function bindLiveUiActions() {
        document.querySelectorAll('button[data-tactic]').forEach(function (button) {
            button.addEventListener('click', function () {
                var tactic = button.getAttribute('data-tactic') || 'balanced';
                window.sendTactic(tactic);
            });
        });
        document.querySelectorAll('button[data-marking]').forEach(function (button) {
            button.addEventListener('click', function () {
                var marking = button.getAttribute('data-marking') || 'zone';
                window.sendLiveMarking(marking);
            });
        });
        document.querySelectorAll('button[data-offside]').forEach(function (button) {
            button.addEventListener('click', function () {
                var offside = parseInt(button.getAttribute('data-offside') || '0', 10);
                window.sendLiveOffside(offside);
            });
        });

        var debugToggle = document.getElementById('live-debug-toggle');
        if (debugToggle && typeof window.toggleLiveDebug === 'function') {
            debugToggle.addEventListener('click', function () {
                window.toggleLiveDebug();
            });
        }

        var adminSimForm = document.getElementById('admin-simulate-form');
        if (adminSimForm) {
            adminSimForm.addEventListener('submit', function () {
                var submitBtn = adminSimForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Simulando…';
                }
            });
        }
    }

    /*
     * Avvio live.
     */
    document.querySelectorAll('[data-event-id]').forEach(function (item) {
        seenEventIds[item.getAttribute('data-event-id')] = true;
        if (!item.querySelector('.event-description')) {
            pendingUpdateIds[item.getAttribute('data-event-id')] = true;
        }
    });
    if (DEV_LIVE_UI) {
        syncDebugToggleUi();
    }
    bindLiveUiActions();
    setLiveTacticBadge(INITIAL_STYLE, INITIAL_MARKING, INITIAL_OFFSIDE, INITIAL_FOCUS);
    setTransportStatus('INIT');

    poll(true); // backlog sync once at load/reload
    startEventStream();
    updateTimer = setInterval(refreshPendingUpdates, UPDATE_MS);
    refreshPendingUpdates();
    refreshScorerSheet();
    aiSet(50, false);
    if (window.GMFixtureFormationUi && typeof window.GMFixtureFormationUi.init === 'function') {
        window.GMFixtureFormationUi.init({
            formationsUrl: FORMATIONS_URL,
            withTabSwitcher: false,
            autoLoad: true,
            viewerSide: USER_SIDE
        });
    }
}());

(function () {
    'use strict';

    var cfg = window.GM_LIVE_CONFIG || {};
    var rosterUrl = String(cfg.rosterUrl || '');
    var subUrl = String(cfg.subUrl || '');
    var csrf = String(cfg.csrfToken || '');
    var csrfName = String(cfg.csrfParam || '_csrf');

    var selectedOut = null;
    var selectedIn = null;

    function playerCard(p, side) {
        var freshColor = p.freshness >= 80 ? 'var(--accent-green)' : (p.freshness >= 55 ? 'var(--gold)' : 'var(--accent-red)');
        var posColors = { GK: '#ea580c', DF: '#2563eb', MF: '#16a34a', FW: '#b91c1c' };
        var posColor = posColors[p.position] || '#94a3b8';
        var formVal = (p.form !== undefined && p.form !== null) ? p.form : '—';
        return '<div class="sub-player-card" data-id="' + p.id + '" data-side="' + side + '" data-name="' + p.name + '"'
            + '>'
            + '<div class="sub-player-row">'
            + '<span class="sub-pos-pill" style="background:' + posColor + '22;color:' + posColor + '">' + p.position + '</span>'
            + '<span class="sub-player-name">' + p.name + '</span>'
            + '<span class="sub-player-skill">' + p.general_skill + '</span>'
            + '</div>'
            + '<div class="sub-player-meta">'
            + '<span class="sub-player-fresh" style="color:' + freshColor + '">Fresch. ' + p.freshness + '%</span>'
            + '<span class="sub-player-form">Forma ' + formVal + '</span>'
            + '</div>'
            + '</div>';
    }

    window.openSubModal = function () {
        selectedOut = null;
        selectedIn = null;
        document.getElementById('starters-list').innerHTML = '<div class="text-muted-gm text-center py-3" style="font-size:.8rem">Caricamento...</div>';
        document.getElementById('bench-list').innerHTML = '<div class="text-muted-gm text-center py-3" style="font-size:.8rem">Caricamento...</div>';
        document.getElementById('sub-confirm-btn').disabled = true;
        document.getElementById('sub-selection-info').textContent = 'Seleziona chi esce (sx) e chi entra (dx)';

        var modal = new bootstrap.Modal(document.getElementById('subModal'));
        modal.show();

        fetch(rosterUrl, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                document.getElementById('sub-modal-counter').textContent =
                    'Cambi: ' + data.subs_used + '/5' +
                    (data.phase === 'half_time' ? ' — Intervallo: saranno eseguiti al 2° tempo' : '');

                var sl = document.getElementById('starters-list');
                sl.innerHTML = '';
                if (data.starters.length === 0) {
                    sl.innerHTML = '<div class="text-muted-gm text-center py-3" style="font-size:.8rem">Nessun titolare trovato</div>';
                } else {
                    data.starters.forEach(function (p) { sl.innerHTML += playerCard(p, 'out'); });
                }

                var bl = document.getElementById('bench-list');
                bl.innerHTML = '';
                if (data.bench.length === 0) {
                    bl.innerHTML = '<div class="text-muted-gm text-center py-3" style="font-size:.8rem">Nessun giocatore disponibile</div>';
                } else {
                    data.bench.forEach(function (p) { bl.innerHTML += playerCard(p, 'in'); });
                }

                if (data.subs_used >= 5) {
                    document.getElementById('sub-confirm-btn').textContent = 'Cambi esauriti';
                    document.querySelectorAll('#starters-list .sub-player-card, #bench-list .sub-player-card')
                        .forEach(function (el) { el.style.opacity = '.4'; el.style.cursor = 'not-allowed'; el.onclick = null; });
                }
            })
            .catch(function () {
                document.getElementById('starters-list').innerHTML = '<div class="text-danger small py-2">Errore caricamento rosa.</div>';
            });
    };

    window.selectSubPlayer = function (el) {
        var side = el.getAttribute('data-side');
        var id = parseInt(el.getAttribute('data-id'), 10);
        var name = el.getAttribute('data-name');

        var container = side === 'out' ? '#starters-list' : '#bench-list';
        document.querySelectorAll(container + ' .sub-player-card').forEach(function (c) {
            c.style.background = 'rgba(255,255,255,.02)';
            c.style.borderColor = 'var(--border)';
        });

        el.style.background = 'rgba(245,158,11,.12)';
        el.style.borderColor = 'var(--gold)';

        if (side === 'out') {
            selectedOut = { id: id, name: name };
        } else {
            selectedIn = { id: id, name: name };
        }

        if (selectedOut && selectedIn) {
            document.getElementById('sub-selection-info').innerHTML =
                '<span style="color:var(--gold)">→ ' + selectedOut.name + '</span>'
                + ' &nbsp;⇄&nbsp; '
                + '<span style="color:var(--accent-green)">' + selectedIn.name + '</span>';
            document.getElementById('sub-confirm-btn').disabled = false;
        } else {
            document.getElementById('sub-selection-info').textContent =
                selectedOut ? 'Ora seleziona chi entra →' : 'Ora seleziona chi esce ←';
        }
    };

    window.confirmSub = function () {
        if (!selectedOut || !selectedIn) { return; }

        document.getElementById('sub-confirm-btn').disabled = true;
        document.getElementById('sub-confirm-btn').textContent = 'Invio...';

        var body = encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrf)
            + '&player_out_id=' + selectedOut.id
            + '&player_in_id=' + selectedIn.id;

        fetch(subUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body,
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                bootstrap.Modal.getInstance(document.getElementById('subModal')).hide();

                var msgEl = document.getElementById('sub-msg');
                msgEl.textContent = data.message || (data.success ? 'Sostituzione prenotata!' : 'Errore');
                msgEl.style.display = 'block';
                msgEl.style.color = data.success ? 'var(--accent-green)' : 'var(--accent-red)';
                setTimeout(function () { msgEl.style.display = 'none'; }, 5000);

                if (data.success && data.subs_used !== undefined) {
                    document.getElementById('subs-counter').textContent = data.subs_used + '/5';
                    if (data.subs_used >= 5) {
                        var btn = document.getElementById('sub-btn');
                        btn.disabled = true;
                        btn.textContent = 'Cambi esauriti';
                    }
                }
            })
            .catch(function () {
                document.getElementById('sub-confirm-btn').disabled = false;
                document.getElementById('sub-confirm-btn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Conferma sostituzione';
            });
    };

    function bindSubstitutionUiActions() {
        var subBtn = document.getElementById('sub-btn');
        if (subBtn) {
            subBtn.addEventListener('click', function () { window.openSubModal(); });
        }

        var confirmBtn = document.getElementById('sub-confirm-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () { window.confirmSub(); });
        }

        ['starters-list', 'bench-list'].forEach(function (id) {
            var container = document.getElementById(id);
            if (!container) {
                return;
            }
            container.addEventListener('click', function (event) {
                var target = event.target;
                var card = target && target.closest ? target.closest('.sub-player-card') : null;
                if (!card) {
                    return;
                }
                window.selectSubPlayer(card);
            });
        });
    }

    bindSubstitutionUiActions();
}());
