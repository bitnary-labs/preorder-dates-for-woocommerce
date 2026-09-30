#!/usr/bin/env bash
# Builds the distributable plugin zip, the same file that goes to WordPress.org.
# Usage: bin/build-zip.sh [output-dir]   (default: ./dist)
set -euo pipefail

slug="bitnary-preorder-dates-for-woocommerce"
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
# The checkout folder keeps the repository's name, so the zip is built through a
# link that carries the plugin slug instead.
stage="$(mktemp -d)"
trap 'rm -rf "${stage:?}"' EXIT
ln -s "$here" "$stage/$slug"
( cd "$stage" && zip -rq "$zipfile" "$slug" \
    -x "*/.*" "*/pro/*" "*/freemius/*" \
       "*/bin/*" "*/dist/*" "*/tests/*" "*/vendor/*" \
       "*/composer.json" "*/composer.lock" "*/phpunit.xml.dist" "*/README.md" )

# Only the path goes to stdout, so a caller can do "zip=$(build.sh)" without
# the summary below breaking the pipe under "set -o pipefail".
unzip -l "$zipfile" | tail -3 >&2
echo "$zipfile"
