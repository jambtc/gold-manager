(function (w) {
    'use strict';

    function safeEl(id) {
        return id ? document.getElementById(id) : null;
    }

    function zoneToXY(zone, side, width, height, cfg) {
        var zoneNum = parseInt(zone, 10);
        if (!zoneNum || zoneNum < 1) {
            zoneNum = 1;
        }
        var col = (zoneNum - 1) % 7;
        var row = Math.floor((zoneNum - 1) / 7);
        var x = (col + 0.5) / 7 * (width - 24) + 12;
        var viewerSide = cfg && (cfg.viewerSide === 'home' || cfg.viewerSide === 'away') ? cfg.viewerSide : null;
        var shouldInvert = viewerSide ? (side === viewerSide) : (side === 'home');
        var ratio = (row + 0.5) / 9;
        var y = shouldInvert
            ? (1 - ratio) * (height - 24) + 12
            : ratio * (height - 24) + 12;
        return { x: x, y: y };
    }

    function drawPlayer(ctx, x, y, player, colorL, colorR) {
        var p = player || {};
        var radius = 12;
        var alpha = p.subbed_off ? 0.35 : 1.0;

        var grad = ctx.createRadialGradient(x - 3, y - 3, 1, x, y, radius);
        grad.addColorStop(0, colorL || '#2563eb');
        grad.addColorStop(1, colorR || '#1e40af');
        ctx.globalAlpha = alpha;
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.fillStyle = grad;
        ctx.fill();

        if (p.injured) {
            ctx.strokeStyle = '#ef4444';
            ctx.lineWidth = 2;
            ctx.stroke();
        } else if (p.is_captain) {
            ctx.strokeStyle = '#f59e0b';
            ctx.lineWidth = 2;
            ctx.stroke();
        } else {
            ctx.strokeStyle = 'rgba(255,255,255,.4)';
            ctx.lineWidth = 1;
            ctx.stroke();
        }

        ctx.fillStyle = '#fff';
        ctx.font = 'bold 9px system-ui';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(p.number || '', x, y);

        if (p.is_captain) {
            ctx.font = '8px system-ui';
            ctx.fillText('C', x + radius - 2, y - radius + 2);
        }

        ctx.font = '8px system-ui';
        ctx.fillStyle = p.subbed_off ? 'rgba(255,255,255,.3)' : '#fff';
        var name = (String(p.name || '').trim() || '—').split(' ').pop().substring(0, 8);
        ctx.fillText(name, x, y + radius + 7);
        ctx.globalAlpha = 1.0;
    }

    function drawPitch(ctx, width, height) {
        ctx.fillStyle = '#1a4731';
        ctx.fillRect(0, 0, width, height);
        for (var i = 0; i < 9; i++) {
            ctx.fillStyle = i % 2 === 0 ? 'rgba(255,255,255,.04)' : 'transparent';
            ctx.fillRect(0, i * height / 9, width, height / 9);
        }
        ctx.strokeStyle = 'rgba(255,255,255,.3)';
        ctx.lineWidth = 1;
        ctx.strokeRect(12, 12, width - 24, height - 24);
        ctx.beginPath();
        ctx.moveTo(12, height / 2);
        ctx.lineTo(width - 12, height / 2);
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(width / 2, height / 2, 22, 0, Math.PI * 2);
        ctx.stroke();
        var pw = 80;
        var ph = 50;
        ctx.strokeRect((width - pw) / 2, 12, pw, ph);
        ctx.strokeRect((width - pw) / 2, height - 12 - ph, pw, ph);
    }

    function renderSide(side, team, cfg) {
        var model = team || {};
        var nameEl = safeEl('form-' + side + '-name');
        var moduleEl = safeEl('form-' + side + '-module');
        if (nameEl) {
            nameEl.textContent = model.team_name || '';
        }
        if (moduleEl) {
            moduleEl.textContent = model.module ? '(' + model.module + ')' : '';
        }

        var canvas = safeEl('pitch-' + side);
        if (!canvas) {
            return;
        }
        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }
        var width = canvas.width;
        var height = canvas.height;
        drawPitch(ctx, width, height);

        var players = Array.isArray(model.players) ? model.players : [];
        players.forEach(function (player) {
            var pos = zoneToXY(player.zone, side, width, height, cfg);
            drawPlayer(ctx, pos.x, pos.y, player, model.color_left, model.color_right);
        });

        var byPos = { GK: [], DF: [], MF: [], FW: [] };
        players.forEach(function (player) {
            if (byPos[player.position]) {
                byPos[player.position].push(player);
            }
        });
        var posLabels = { GK: 'P', DF: 'D', MF: 'C', FW: 'A' };
        var html = '';
        ['GK', 'DF', 'MF', 'FW'].forEach(function (pos) {
            if (!byPos[pos].length) {
                return;
            }
            var names = byPos[pos].map(function (player) {
                var surname = (String(player.name || '').trim() || '—').split(' ').pop();
                return player.subbed_off
                    ? '<span style="opacity:.4;text-decoration:line-through">' + surname + '</span>'
                    : surname;
            }).join(', ');
            html += '<span style="color:var(--gold);font-weight:700">' + posLabels[pos] + ':</span> ' + names + '<br>';
        });
        var listEl = safeEl('list-' + side);
        if (listEl) {
            listEl.innerHTML = html;
        }
    }

    function setTabState(isForm, cfg) {
        var cronaca = safeEl(cfg.tabCronacaPanelId);
        var form = safeEl(cfg.tabFormazioniPanelId);
        var btnCronaca = safeEl(cfg.tabBtnCronacaId);
        var btnForm = safeEl(cfg.tabBtnFormazioniId);
        if (cronaca) {
            cronaca.style.display = isForm ? 'none' : '';
        }
        if (form) {
            form.style.display = isForm ? '' : 'none';
        }
        if (btnCronaca) {
            btnCronaca.className = isForm ? 'btn btn-sm btn-outline-secondary' : 'btn btn-sm btn-gold';
        }
        if (btnForm) {
            btnForm.className = isForm ? 'btn btn-sm btn-gold' : 'btn btn-sm btn-outline-secondary';
        }
    }

    function init(config) {
        var cfg = Object.assign({
            formationsUrl: '',
            loadingId: 'formations-loading',
            contentId: 'formations-content',
            tabCronacaPanelId: 'tab-cronaca-panel',
            tabFormazioniPanelId: 'tab-formazioni-panel',
            tabBtnCronacaId: 'tab-btn-cronaca',
            tabBtnFormazioniId: 'tab-btn-formazioni',
            autoLoad: true,
            withTabSwitcher: false,
            viewerSide: null
        }, config || {});
        if (cfg.viewerSide !== 'home' && cfg.viewerSide !== 'away') {
            cfg.viewerSide = null;
        }

        var formationsLoaded = false;

        function loadFormations() {
            if (!cfg.formationsUrl) {
                return;
            }
            fetch(cfg.formationsUrl, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.success) {
                        return;
                    }
                    var loading = safeEl(cfg.loadingId);
                    var content = safeEl(cfg.contentId);
                    if (loading) {
                        loading.style.display = 'none';
                    }
                    if (content) {
                        content.style.display = '';
                    }
                    renderSide('home', data.home || data.home_team || {}, cfg);
                    renderSide('away', data.away || data.away_team || {}, cfg);
                    formationsLoaded = true;
                })
                .catch(function () {});
        }

        w._refreshFormationsOnSub = function () {
            formationsLoaded = false;
            var panel = safeEl(cfg.tabFormazioniPanelId);
            if (!panel || panel.style.display !== 'none') {
                loadFormations();
            }
        };

        if (cfg.withTabSwitcher) {
            w.switchLiveTab = function (tab) {
                var isForm = tab === 'formazioni';
                setTabState(isForm, cfg);
                if (isForm && !formationsLoaded) {
                    loadFormations();
                }
            };
        }

        if (cfg.autoLoad) {
            loadFormations();
        }
    }

    w.GMFixtureFormationUi = { init: init };
}(window));

