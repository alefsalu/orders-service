#!/usr/bin/env bash
set -euo pipefail
# Simulate forbidden file detection
output=$(git ls-files | grep -E '^(_PESSOAL/|\.superpowers/|keys/)|\.pem$|(^|/)\.env$' || true)
if [ -n "$output" ]; then
    echo "Guard assertion failed: detected forbidden files: $output"
    exit 1
fi
echo "Guard test passed: no forbidden files tracked."
