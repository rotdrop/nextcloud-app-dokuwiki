#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# App-specific sanity check of the built archive, run by the shared
# build-appstore workflow with APP_ID and ARCHIVE in the environment.
set -euo pipefail

count=$(tar tzf "$ARCHIVE" | grep -Ec "$APP_ID/js/.*\.m?js\$" || true)
echo "JS bundles in archive: $count"
test "$count" -gt 0 || { echo "::error::No JS bundles in the archive"; exit 1; }
for entry in admin-settings app refresh; do
  tar tzf "$ARCHIVE" \
    | grep -E "^$APP_ID/js/($APP_ID-)?$entry-[^/]+\.m?js\$" \
    || { echo "::error::Missing $entry entry bundle"; exit 1; }
done
