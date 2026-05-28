(function () {
  "use strict";

  var cfg = window.GM_TRAINING_PROGRESS_CFG || null;
  if (!cfg || !cfg.enabled) return;
  if (typeof Chart === "undefined") return;

  var playerChart = null;
  var statLabels = cfg.statLabels || {};
  var statKeys = Object.keys(statLabels);
  var colors = ["#f59e0b", "#3b82f6", "#22c55e", "#ef4444", "#a855f7", "#06b6d4", "#f97316", "#84cc16", "#ec4899", "#94a3b8"];

  function markActive(playerId) {
    document.querySelectorAll(".player-progress-item").forEach(function (btn) {
      var isActive = parseInt(btn.getAttribute("data-player-id") || "0", 10) === playerId;
      btn.classList.toggle("active", isActive);
      btn.style.borderColor = isActive ? "rgba(245,158,11,.55)" : "var(--border)";
      btn.style.background = isActive ? "rgba(245,158,11,.12)" : "";
    });
  }

  function loadPlayer(playerId) {
    if (!playerId) return;
    fetch(cfg.progressUrl + "?playerId=" + playerId + "&weeks=14", { credentials: "same-origin" })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        var nameEl = document.getElementById("progress-player-name");
        if (nameEl) {
          var pname = data.player && data.player.name ? data.player.name : "—";
          var ppos = data.player && data.player.position ? " (" + data.player.position + ")" : "";
          nameEl.textContent = pname + ppos;
        }

        var snaps = data.snapshots || [];
        if (snaps.length < 2) {
          var deltaEl = document.getElementById("delta-badges");
          if (deltaEl) {
            deltaEl.innerHTML = '<span class="text-muted-gm small">Non abbastanza dati (servono almeno 2 snapshot giornalieri).</span>';
          }
          if (playerChart) {
            playerChart.destroy();
            playerChart = null;
          }
          return;
        }

        var labels = snaps.map(function (s) {
          return s.snapshot_date.slice(5);
        });
        var datasets = statKeys.map(function (k, i) {
          return {
            label: statLabels[k],
            data: snaps.map(function (s) {
              return s[k];
            }),
            borderColor: colors[i % colors.length],
            backgroundColor: "transparent",
            tension: 0.35,
            pointRadius: 2.8,
            borderWidth: 2,
          };
        });

        var chartEl = document.getElementById("playerChart");
        if (!chartEl) return;
        var ctx = chartEl.getContext("2d");
        if (playerChart) playerChart.destroy();
        playerChart = new Chart(ctx, {
          type: "line",
          data: { labels: labels, datasets: datasets },
          options: {
            responsive: true,
            plugins: { legend: { labels: { color: "#94a3b8", boxWidth: 11 } } },
            scales: {
              x: { ticks: { color: "#64748b" } },
              y: { ticks: { color: "#64748b" }, suggestedMin: 0, suggestedMax: 99 },
            },
          },
        });

        var last = snaps[snaps.length - 1];
        var deltas = last.deltas || {};
        var html = "";
        statKeys.forEach(function (k) {
          var d = deltas[k];
          if (d === null || d === undefined) {
            html += '<span style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.4rem;padding:.2rem .5rem;font-size:.72rem;color:var(--text-secondary)"><strong>' + statLabels[k] + "</strong> —</span>";
            return;
          }
          d = parseInt(d, 10);
          var col = d > 0 ? "var(--accent-green)" : d < 0 ? "var(--accent-red)" : "var(--text-secondary)";
          var sign = d > 0 ? "+" : "";
          html += '<span style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.4rem;padding:.2rem .5rem;font-size:.72rem;color:' + col + '"><strong>' + statLabels[k] + "</strong> " + sign + d + "</span>";
        });
        var badgesEl = document.getElementById("delta-badges");
        if (badgesEl) badgesEl.innerHTML = html;
        markActive(playerId);
      })
      .catch(function () {});
  }

  document.querySelectorAll(".player-progress-item").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var id = parseInt(this.getAttribute("data-player-id") || "0", 10);
      loadPlayer(id);
    });
  });

  if (cfg.firstPlayerId > 0) loadPlayer(cfg.firstPlayerId);
})();

