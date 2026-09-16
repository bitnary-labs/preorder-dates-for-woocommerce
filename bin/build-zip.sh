#!/usr/bin/env bash
# Builds the distributable plugin zip, the same file that goes to WordPress.org.
# Usage: bin/build-zip.sh [output-dir]   (default: ./dist)
set -euo pipefail

slug="preorder-dates-for-woocommerce"
here="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
out="${1:-$here/dist}"
mkdir -p "$out"

version="$(grep -m1 '^ \* Version:' "$here/$slug.php" | awk '{print $3}')"
stable="$(grep -m1 '^Stable tag:' "$here/readme.txt" | awk '{print $3}')"
if [[ "$version" != "$stable" ]]; then
  echo "Version mismatch: plugin header $version, readme Stable tag $stable" >&2
  exit 1
fi

zipfile="$out/$slug-$version.zip"
rm -f "$zipfile"
( cd "$here/.." && zip -rq "$zipfile" "$slug" \
    -x "*/.git/*" "*/.github/*" "*/.gitignore" "*/.wordpress-org/*" \
       "*/bin/*" "*/dist/*" "*/tests/*" "*/vendor/*" \
       "*/composer.json" "*/composer.lock" "*/phpunit.xml.dist" "*/.phpcs.xml.dist" \
       "*/.phpunit.result.cache" "*/README.md" "*/.DS_Store" )

echo "$zipfile"
unzip -l "$zipfile" | tail -3
