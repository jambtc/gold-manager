(function () {
    "use strict";

    var cfg = window.GM_HEADER_NOTIFICATIONS || null;
    if (!cfg || !cfg.enabled) return;

    var bell = document.getElementById("gm-news-bell");
    var bellIcon = document.getElementById("gm-news-bell-icon");
    var bellBadge = document.getElementById("gm-news-bell-badge");
    if (!bell || !bellIcon || !bellBadge) return;

    var source = null;
    var pollTimer = null;
    var reconnectTimer = null;
    var reconnectAttempts = 0;
    var lastEventId = Number(cfg.lastEventId || 0);
    var pollMs = Math.max(5000, Number(cfg.pollMs || 12000));

    function applyUnread(count) {
        var unread = Math.max(0, Number(count || 0));
        var hasUnread = unread > 0;

        bellIcon.classList.toggle("bi-bell-fill", hasUnread);
        bellIcon.classList.toggle("text-gold", hasUnread);
        bellIcon.classList.toggle("bi-bell", !hasUnread);

        if (hasUnread) {
            bellBadge.textContent = String(Math.min(unread, 99));
            bellBadge.style.display = "flex";
        } else {
            bellBadge.textContent = "";
            bellBadge.style.display = "none";
        }
    }

    function handlePayload(payload) {
        if (!payload || typeof payload !== "object") return;
        if (typeof payload.latest_id === "number" || typeof payload.latest_id === "string") {
            var idNum = Number(payload.latest_id || 0);
            if (idNum > lastEventId) lastEventId = idNum;
        }
        if (typeof payload.unread_count !== "undefined") {
            applyUnread(Number(payload.unread_count || 0));
        }
    }

    function pollOnce() {
        if (!cfg.pollUrl) return;
        fetch(cfg.pollUrl, {
            method: "GET",
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        }).then(function (res) {
            if (!res.ok) throw new Error("poll failed");
            return res.json();
        }).then(function (data) {
            handlePayload(data);
        }).catch(function () {
            // silent retry via timer
        });
    }

    function startPolling() {
        if (pollTimer) return;
        pollOnce();
        pollTimer = window.setInterval(pollOnce, pollMs);
    }

    function stopPolling() {
        if (!pollTimer) return;
        window.clearInterval(pollTimer);
        pollTimer = null;
    }

    function streamUrl() {
        if (!cfg.streamUrl) return "";
        if (lastEventId <= 0) return cfg.streamUrl;
        var sep = cfg.streamUrl.indexOf("?") >= 0 ? "&" : "?";
        return cfg.streamUrl + sep + "since=" + encodeURIComponent(String(lastEventId));
    }

    function clearReconnect() {
        if (!reconnectTimer) return;
        window.clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }

    function scheduleReconnect() {
        clearReconnect();
        reconnectAttempts += 1;
        var delay = Math.min(15000, 1000 * reconnectAttempts);
        reconnectTimer = window.setTimeout(function () {
            reconnectTimer = null;
            connectStream();
        }, delay);
    }

    function onSseData(event) {
        if (!event || !event.data) return;
        try {
            var payload = JSON.parse(event.data);
            handlePayload(payload);
        } catch (_err) {
            // ignore malformed packet
        }
    }

    function connectStream() {
        if (!window.EventSource || !cfg.streamUrl) {
            startPolling();
            return;
        }

        if (source) {
            source.close();
            source = null;
        }

        try {
            source = new EventSource(streamUrl());
        } catch (_err) {
            startPolling();
            scheduleReconnect();
            return;
        }

        source.addEventListener("news", onSseData);
        source.onmessage = onSseData;
        source.addEventListener("ping", function () {});

        source.onopen = function () {
            reconnectAttempts = 0;
            stopPolling();
        };

        source.onerror = function () {
            if (source) {
                source.close();
                source = null;
            }
            startPolling();
            scheduleReconnect();
        };
    }

    applyUnread(Number(cfg.initialUnread || 0));
    connectStream();
    startPolling();

    window.addEventListener("beforeunload", function () {
        clearReconnect();
        stopPolling();
        if (source) source.close();
    });
})();

