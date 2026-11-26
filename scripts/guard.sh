#!/usr/bin/env bash
set -euo pipefail

FORBIDDEN=$(git ls-files | grep -E '^(_PESSOAL/|\.superpowers/|keys/)|\.pem$|(^|/)\.env$' || true)
if [ -n "$FORBIDDEN" ]; then
    echo "ERROR: Forbidden files found in git index:" >&2
    echo "$FORBIDDEN" >&2
    exit 1
fi
echo "Guard check passed: index is clean."
