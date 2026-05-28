#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Runtime code must not read legacy calcolatore table.
# Allowed references: migrations and tests only.
if rg -n "FROM \{\{%calcolatore\}\}|SELECT .*calcolatore|\bcalcolatore\b" \
  "$ROOT_DIR" \
  --glob '!**/migrations/**' \
  --glob '!**/tests/**' \
  --glob '!**/vendor/**' \
  --glob '!**/runtime/**' \
  --glob '!**/docs/**' \
  --glob '!**/scripts/**' \
  --glob '!composer.lock' \
  --glob '!*.md'; then
  echo "ERROR: found runtime references to calcolatore table"
  exit 1
fi

echo "OK: no runtime calcolatore DB reads found"
