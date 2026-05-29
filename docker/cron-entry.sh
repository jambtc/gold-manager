#!/usr/bin/env bash
set -euo pipefail

ROOT="/var/www/html/v2"
TMP_CRON="/tmp/gold-manager-cron"
CRON_ENABLED="${CRON_ENABLED:-1}"
CRON_TZ="${CRON_TZ:-Europe/Rome}"
FLOCK="$(command -v flock || true)"
LOCKS="/var/www/html/runtime/locks"
LOGS="/var/www/html/runtime/logs"

mkdir -p "$LOCKS" "$LOGS"

register_job() {
    local enabled="$1" schedule="$2" cmd="$3" log="$4" label="$5"
    if [[ "$enabled" != "1" ]]; then
        echo "[cron] disabled: $label"
        return
    fi
    if [[ -z "$schedule" ]]; then
        echo "[cron] no schedule: $label"
        return
    fi
    local guarded="mkdir -p $LOCKS $LOGS"
    if [[ -n "$FLOCK" ]]; then
        guarded+=" && $FLOCK -n $LOCKS/${label}.lock -c 'cd $ROOT && $cmd'"
    else
        guarded+=" && cd $ROOT && $cmd"
    fi
    echo "$schedule $guarded >> $LOGS/${label}.log 2>&1" >> "$TMP_CRON"
    touch "$LOGS/${label}.log"
    echo "[cron] enabled: $label ($schedule)"
}

# ── Build crontab ────────────────────────────────────────────────────────────
{
    echo "SHELL=/bin/bash"
    echo "PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
    echo "CRON_TZ=${CRON_TZ}"
} > "$TMP_CRON"

register_job \
    "${CRON_ENABLE_PAY_WAGES:-1}" \
    "${CRON_PAY_WAGES_SCHEDULE:-0 0 * * *}" \
    "php yii economy/pay-wages" \
    "$LOGS/pay-wages.log" \
    "pay-wages"

register_job \
    "${CRON_ENABLE_WEEKLY_RECOVERY:-1}" \
    "${CRON_WEEKLY_RECOVERY_SCHEDULE:-0 1 * * 1}" \
    "php yii economy/apply-weekly-recovery" \
    "$LOGS/weekly-recovery.log" \
    "weekly-recovery"

register_job \
    "${CRON_ENABLE_DAILY_TRAINING:-${CRON_ENABLE_WEEKLY_TRAINING:-1}}" \
    "${CRON_DAILY_TRAINING_SCHEDULE:-${CRON_WEEKLY_TRAINING_SCHEDULE:-0 2 * * *}}" \
    "php yii economy/apply-daily-training" \
    "$LOGS/daily-training.log" \
    "daily-training"

register_job \
    "${CRON_ENABLE_REFRESH_CPU_FORMATIONS:-1}" \
    "${CRON_REFRESH_CPU_FORMATIONS_SCHEDULE:-0 0 * * *}" \
    "php yii game/refresh-cpu-formations" \
    "$LOGS/refresh-cpu-formations.log" \
    "refresh-cpu-formations"

register_job \
    "${CRON_ENABLE_SCOUTING:-1}" \
    "${CRON_SCOUTING_SCHEDULE:-*/30 * * * *}" \
    "php yii scouting/process" \
    "$LOGS/scouting.log" \
    "scouting"

register_job \
    "${CRON_ENABLE_RUN_FIXTURES:-1}" \
    "${CRON_RUN_FIXTURES_SCHEDULE:-*/5 * * * *}" \
    "php yii game/run-fixtures" \
    "$LOGS/run-fixtures.log" \
    "run-fixtures"

register_job \
    "${CRON_ENABLE_RESOLVE_MARKET_AUCTIONS:-1}" \
    "${CRON_RESOLVE_MARKET_AUCTIONS_SCHEDULE:-*/5 * * * *}" \
    "php yii economy/resolve-market-auctions" \
    "$LOGS/resolve-market-auctions.log" \
    "resolve-market-auctions"

register_job \
    "${CRON_ENABLE_NOTIFICATION_DISPATCH:-1}" \
    "${CRON_NOTIFICATION_DISPATCH_SCHEDULE:-* * * * *}" \
    "php yii notification/dispatch" \
    "$LOGS/notification-dispatch.log" \
    "notification-dispatch"

register_job \
    "${CRON_ENABLE_PRE_MATCH_NOTIFICATIONS:-1}" \
    "${CRON_PRE_MATCH_NOTIFICATIONS_SCHEDULE:-* * * * *}" \
    "php yii economy/send-pre-match-notifications" \
    "$LOGS/pre-match-notifications.log" \
    "pre-match-notifications"

crontab "$TMP_CRON"

LOADED="$(grep -Ecv '^(#|$|[A-Za-z_][A-Za-z0-9_]*=)' "$TMP_CRON" || true)"
echo "[cron] ${LOADED} job(s) loaded."

# ── Start ────────────────────────────────────────────────────────────────────
if [[ "$CRON_ENABLED" != "1" ]]; then
    echo "[cron] CRON_ENABLED=0 — sleeping."
    exec tail -f /dev/null
fi

cleanup() {
    [[ -n "${CRON_PID:-}" ]] && kill "$CRON_PID" 2>/dev/null || true
    [[ -n "${TAIL_PID:-}" ]] && kill "$TAIL_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

echo "[cron] Starting cron daemon (TZ=${CRON_TZ})"
cron -f &
CRON_PID=$!

LOG_FILES=(
    "$LOGS/pay-wages.log"
    "$LOGS/weekly-recovery.log"
    "$LOGS/daily-training.log"
    "$LOGS/refresh-cpu-formations.log"
    "$LOGS/scouting.log"
    "$LOGS/run-fixtures.log"
    "$LOGS/resolve-market-auctions.log"
    "$LOGS/notification-dispatch.log"
    "$LOGS/pre-match-notifications.log"
)
tail -n 0 -F "${LOG_FILES[@]}" &
TAIL_PID=$!

wait -n "$CRON_PID" "$TAIL_PID"
