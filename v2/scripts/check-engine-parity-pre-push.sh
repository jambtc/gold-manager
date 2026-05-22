#!/usr/bin/env bash
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
cd "${ROOT}"

PHP_ENGINE="v2/components/MatchEngine.php"
GO_ENGINE="v2/worker-go/engine/match.go"

if git rev-parse --verify "@{upstream}" >/dev/null 2>&1; then
  RANGE="@{upstream}..HEAD"
else
  if git rev-parse --verify HEAD~1 >/dev/null 2>&1; then
    RANGE="HEAD~1..HEAD"
  else
    echo "[parity] No previous commit available. Skip."
    exit 0
  fi
fi

CHANGED="$(git diff --name-only "${RANGE}" || true)"
PHP_CHANGED=0
GO_CHANGED=0

if echo "${CHANGED}" | grep -qx "${PHP_ENGINE}"; then
  PHP_CHANGED=1
fi
if echo "${CHANGED}" | grep -qx "${GO_ENGINE}"; then
  GO_CHANGED=1
fi

if [[ "${PHP_CHANGED}" -ne "${GO_CHANGED}" ]]; then
  echo "[parity] ERROR: engine change not mirrored."
  echo "[parity] Changed in range: ${RANGE}"
  echo "[parity] Rule: if one engine changes, verify/update other before push."
  echo "[parity] Required pair:"
  echo "  - ${PHP_ENGINE}"
  echo "  - ${GO_ENGINE}"
  exit 1
fi

echo "[parity] OK."
