#!/usr/bin/env bash
set -euo pipefail

SLUG="dfx-feed-for-telegram-channels"
VERSION=$(grep -oP "Version:\s*\K[0-9.]+" "$SLUG.php" 2>/dev/null || echo "0.0.0")
DIST_DIR="dist"
STAGING="$DIST_DIR/staging"
ZIP_FILE="$DIST_DIR/${SLUG}-${VERSION}.zip"

rm -rf "$STAGING" "$ZIP_FILE"
mkdir -p "$STAGING/$SLUG"

rsync -a --exclude-from=.distignore --exclude=dist/ ./ "$STAGING/$SLUG/"

cd "$STAGING"
zip -rq "../../$ZIP_FILE" "$SLUG/"
cd ../..
rm -rf "$STAGING"

echo "Built $ZIP_FILE ($(du -h "$ZIP_FILE" | cut -f1), $(unzip -l "$ZIP_FILE" | tail -1 | awk '{print $2}') files)"
