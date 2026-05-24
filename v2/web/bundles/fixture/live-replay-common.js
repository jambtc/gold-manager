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
        var shouldInvert = viewerSide ? (side !== viewerSide) : (side === 'home');
        var ratio = (row + 0.5) / 9;
        var y = shouldInvert
            ? (1 - ratio) * (height - 24) + 12
            : ratio * (height - 24) + 12;
        return { x: x, y: y };
    }

    function compactName(fullName) {
        var clean = String(fullName || '').trim();
        if (!clean) {
            return '—';
        }
        var parts = clean.split(/\s+/);
        if (parts.length === 1) {
            return parts[0];
        }
        var firstInitial = parts[0].charAt(0).toUpperCase();
        var lastName = parts[parts.length - 1];
        return firstInitial + '. ' + lastName;
    }

    function drawPlayer(ctx, x, y, player, colorL, colorR) {
        var p = player || {};
        var alpha = p.subbed_off ? 0.35 : 1.0;

        var viewW = 44;
        var viewH = 52;
        var scale = 0.5; // matches 22x26px jersey
        var shadowRx = 13 * scale;
        var shadowRy = 1.5 * scale;

        ctx.save();
        ctx.globalAlpha = alpha;
        ctx.translate(x - (viewW * scale) / 2, y - (viewH * scale) / 2);
        ctx.scale(scale, scale);

        // Shadow
        ctx.fillStyle = 'rgba(0,0,0,0.35)';
        ctx.beginPath();
        ctx.ellipse(22, 51, 13, 1.5, 0, 0, Math.PI * 2);
        ctx.fill();

        // Gradient
        var grad = ctx.createLinearGradient(0, 0, viewW, viewH);
        grad.addColorStop(0, colorL || '#1d4ed8');
        grad.addColorStop(1, colorR || '#0f2f8f');

        ctx.fillStyle = grad;
        ctx.strokeStyle = 'rgba(0,0,0,0.25)';
        ctx.lineWidth = 0.6;

        // Left sleeve
        ctx.beginPath();
        ctx.moveTo(1, 11);
        ctx.lineTo(4, 22);
        ctx.lineTo(15, 19);
        ctx.lineTo(13, 6);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // Right sleeve
        ctx.beginPath();
        ctx.moveTo(43, 11);
        ctx.lineTo(40, 22);
        ctx.lineTo(29, 19);
        ctx.lineTo(31, 6);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // Body
        ctx.beginPath();
        ctx.moveTo(13, 6);
        ctx.lineTo(15, 19);
        ctx.lineTo(12, 48);
        ctx.lineTo(32, 48);
        ctx.lineTo(29, 19);
        ctx.lineTo(31, 6);
        ctx.quadraticCurveTo(26, 10, 22, 10);
        ctx.quadraticCurveTo(18, 10, 13, 6);
        ctx.closePath();
        ctx.fill();
        if (p.injured) {
            ctx.strokeStyle = '#ef4444';
        } else if (p.is_captain) {
            ctx.strokeStyle = '#f59e0b';
        } else {
            ctx.strokeStyle = 'rgba(0,0,0,0.25)';
        }
        ctx.stroke();

        // Collar
        ctx.beginPath();
        ctx.fillStyle = colorR || '#b45309';
        ctx.moveTo(17, 5);
        ctx.lineTo(22, 13);
        ctx.lineTo(27, 5);
        ctx.lineTo(17, 5);
        ctx.closePath();
        ctx.fill();
        ctx.strokeStyle = 'rgba(0,0,0,0.4)';
        ctx.lineWidth = 0.8;
        ctx.stroke();

        // Number
        ctx.fillStyle = '#fff';
        ctx.font = '900 11px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.shadowColor = 'rgba(0,0,0,0.5)';
        ctx.shadowBlur = 2;
        ctx.fillText(p.number || '', 22, 35);
        ctx.shadowBlur = 0;

        ctx.restore();

        // Name label (outside scaled context)
        var name = compactName(p.name).substring(0, 16);
        var metrics = ctx.measureText(name);
        var padX = 6;
        var boxW = metrics.width + padX * 2;
        var boxH = 12;
        var boxX = x - boxW / 2;
        var boxY = y + (viewH * scale) / 2 + 6;
        var nameGrad = ctx.createLinearGradient(boxX, boxY, boxX, boxY + boxH);
        nameGrad.addColorStop(0, '#123b99');
        nameGrad.addColorStop(1, '#0b2565');
        ctx.fillStyle = nameGrad;
        var r = 2;
        ctx.beginPath();
        ctx.moveTo(boxX + r, boxY);
        ctx.lineTo(boxX + boxW - r, boxY);
        ctx.quadraticCurveTo(boxX + boxW, boxY, boxX + boxW, boxY + r);
        ctx.lineTo(boxX + boxW, boxY + boxH - r);
        ctx.quadraticCurveTo(boxX + boxW, boxY + boxH, boxX + boxW - r, boxY + boxH);
        ctx.lineTo(boxX + r, boxY + boxH);
        ctx.quadraticCurveTo(boxX, boxY + boxH, boxX, boxY + boxH - r);
        ctx.lineTo(boxX, boxY + r);
        ctx.quadraticCurveTo(boxX, boxY, boxX + r, boxY);
        ctx.closePath();
        ctx.fill();
        ctx.fillStyle = p.subbed_off ? 'rgba(255,255,255,.35)' : '#ffffff';
        ctx.font = 'bold 8px system-ui';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(name, x, boxY + boxH / 2 + 0.5);
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
                var label = compactName(player.name);
                return player.subbed_off
                    ? '<span style="opacity:.4;text-decoration:line-through">' + label + '</span>'
                    : label;
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

