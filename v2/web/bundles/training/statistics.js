(function () {
  "use strict";

  var cfg = window.GM_TRAINING_STATS_CFG || null;
  if (!cfg || !cfg.enabled) return;

  var teamTrendChart = null;

  function renderPlayerRows(targetId, rows, invert) {
    var target = document.getElementById(targetId);
    if (!target) return;
    var out = "";
    (rows || []).forEach(function (p, i) {
      var d = parseInt(p.delta || 0, 10);
      var sign = d > 0 ? "+" : "";
      var col =
        d > 0
          ? "var(--accent-green)"
          : d < 0
            ? "var(--accent-red)"
            : "var(--text-secondary)";
      if (invert && d > 0) col = "var(--text-secondary)";
      out +=
        '<div style="display:flex;justify-content:space-between;padding:.4rem 0;' +
        (i > 0 ? "border-top:1px solid var(--border)" : "") +
        '">' +
        '<div><span class="fw-bold text-white" style="font-size:.82rem">' +
        p.name +
        '</span> <span class="text-muted-gm" style="font-size:.72rem">' +
        p.position +
        '</span></div><span style="color:' +
        col +
        ';font-weight:800;font-size:.85rem">' +
        sign +
        d +
        "</span></div>";
    });
    target.innerHTML = out || '<p class="text-muted-gm small">Dati non disponibili.</p>';
  }

  function metricChip(label, value) {
    return (
      '<div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:.55rem;padding:.45rem .55rem">' +
      '<span class="text-muted-gm" style="font-size:.74rem">' +
      label +
      '</span><span class="text-white fw-bold" style="font-size:.86rem">' +
      value +
      "</span></div>"
    );
  }

  function fmt1(val) {
    var n = Number(val);
    if (!Number.isFinite(n)) return "0.0";
    return n.toFixed(1);
  }

  fetch(cfg.statsUrl, { credentials: "same-origin" })
    .then(function (r) {
      return r.json();
    })
    .then(function (data) {
      var trend = data.trend || {};
      var dates = trend.dates || [];
      var formData = trend.form || [];
      var condData = trend.condition || [];
      var chartEl = document.getElementById("teamTrendChart");
      if (!chartEl || typeof Chart === "undefined") return;

      var ctx = chartEl.getContext("2d");
      if (teamTrendChart) teamTrendChart.destroy();
      teamTrendChart = new Chart(ctx, {
        type: "line",
        data: {
          labels: dates.map(function (d) {
            return String(d).slice(5);
          }),
          datasets: [
            {
              label: cfg.labels.form,
              data: formData,
              borderColor: "#22c55e",
              backgroundColor: "transparent",
              tension: 0.35,
              pointRadius: 2.8,
              borderWidth: 2,
            },
            {
              label: cfg.labels.condition,
              data: condData,
              borderColor: "#f59e0b",
              backgroundColor: "transparent",
              tension: 0.35,
              pointRadius: 2.8,
              borderWidth: 2,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { labels: { color: "#94a3b8" } } },
          scales: {
            x: { ticks: { color: "#64748b" } },
            y: { ticks: { color: "#64748b" }, suggestedMin: 0, suggestedMax: 99 },
          },
        },
      });

      var kpi = data.kpi || {};
      var kpiEl = document.getElementById("kpi-today");
      if (kpiEl) {
        kpiEl.innerHTML =
          metricChip(cfg.labels.avgForm, fmt1(kpi.avg_form)) +
          metricChip(
            cfg.labels.avgCondition,
            fmt1(kpi.avg_condition)
          ) +
          metricChip(
            cfg.labels.avgFreshness,
            fmt1(kpi.avg_freshness)
          );
      }

      renderPlayerRows("top-growth", data.topGrowth || [], false);
      renderPlayerRows("top-drop", data.topDrop || [], true);

      var labels = cfg.tacticLabels || {};
      var t = data.tacticTrend || {};
      var rows = "";
      Object.keys(labels).forEach(function (k) {
        var r = t[k] || { current: 0, delta_7d: 0, alloc: 0 };
        var d = parseInt(r.delta_7d || 0, 10);
        var sign = d > 0 ? "+" : "";
        var col =
          d > 0
            ? "var(--accent-green)"
            : d < 0
              ? "var(--accent-red)"
              : "var(--text-secondary)";
        rows +=
          "<tr><td class=\"text-white\">" +
          labels[k] +
          "</td><td class=\"text-gold fw-bold\">" +
          (r.current || 0) +
          "/100</td><td style=\"color:" +
          col +
          ";font-weight:700\">" +
          sign +
          d +
          "</td><td class=\"text-muted-gm\">" +
          (r.alloc || 0) +
          "/100</td></tr>";
      });
      var trendEl = document.getElementById("tactic-trend-table");
      if (trendEl) {
        var tableHtml =
          '<table class="table table-dark table-sm align-middle mb-0" style="--bs-table-bg:transparent">' +
          "<thead><tr><th>" +
          cfg.labels.tactic +
          "</th><th>" +
          cfg.labels.level +
          "</th><th>" +
          cfg.labels.delta7d +
          "</th><th>" +
          cfg.labels.allocToday +
          "</th></tr></thead><tbody>" +
          rows +
          "</tbody></table>";
        trendEl.innerHTML = rows ? tableHtml : '<p class="text-muted-gm small">Nessun dato tattico disponibile.</p>';
      }
    })
    .catch(function () {});
})();
